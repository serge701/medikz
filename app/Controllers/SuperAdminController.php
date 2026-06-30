<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Config;
use App\Core\Database;
use App\Models\Clinica;
use App\Models\Pago;

/**
 * Panel exclusivo del dueño del SaaS.
 * Acceso: usuarios con superadmin = 1.
 * Permite gestionar clínicas, precios negociados y crear propietarios.
 */
final class SuperAdminController extends Controller
{
    /** GET /superadmin */
    public function index(): void
    {
        Auth::requireSuperAdmin();

        $db   = Database::conn();
        $rows = $db->query(
            "SELECT c.*,
                    u.email   AS owner_email,
                    u.nombre  AS owner_nombre
             FROM clinicas c
             LEFT JOIN usuarios u ON u.clinica_id = c.id
                                  AND u.es_propietario = 1
                                  AND u.deleted_at IS NULL
             ORDER BY c.id DESC"
        )->fetchAll();

        $precioDefault = (int) (Config::get('stripe')['precio_mxn'] ?? 38900);

        $this->render('superadmin/index', [
            'pageTitle'     => 'Super Admin · Clínicas',
            'clinicas'      => $rows,
            'precioDefault' => $precioDefault,
        ]);
    }

    /** GET /superadmin/clinicas/nueva */
    public function create(): void
    {
        Auth::requireSuperAdmin();
        clear_old();

        $this->render('superadmin/form', [
            'pageTitle' => 'Nueva clínica',
            'clinica'   => null,
            'errores'   => [],
        ]);
    }

    /** POST /superadmin/clinicas */
    public function store(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verify();

        $data    = $this->recogerPost();
        $errores = $this->validar($data);

        if ($errores !== []) {
            set_old($_POST);
            $this->render('superadmin/form', [
                'pageTitle' => 'Nueva clínica',
                'clinica'   => null,
                'errores'   => $errores,
            ]);
            return;
        }

        $db           = Database::conn();
        $clinicaModel = new Clinica();

        // 1. Crear clínica con precio negociado
        $precioMensual = $data['precio_mensual'] !== ''
            ? (int) round((float) $data['precio_mensual'] * 100)
            : null;

        $clinicaId = $clinicaModel->create([
            'nombre'          => $data['clinica_nombre'],
            'tipo_plan'       => 'individual',
            'email'           => $data['email'],
            'telefono'        => $data['telefono'],
            'activo'          => 1,
            'precio_mensual'  => $precioMensual,
            'registro_ip'     => '0.0.0.0',
        ]);

        // 2. Activar trial o suscripción según elección
        if ($data['activacion'] === 'trial') {
            $diasTrial = (int) (Config::get('stripe')['trial_dias'] ?? 14);
            $clinicaModel->activarTrial($clinicaId, $diasTrial);
        } elseif ($data['activacion'] === 'activo' && $data['suscripcion_hasta'] !== '') {
            $clinicaModel->update($clinicaId, [
                'estado_saas'       => 'activo',
                'suscripcion_hasta' => $data['suscripcion_hasta'],
            ]);
        }

        // 3. Crear usuario propietario (admin_clinica, es_propietario=1)
        $stmt = $db->prepare(
            "INSERT INTO usuarios
             (clinica_id, nombre, email, password_hash, rol, es_propietario, activo)
             VALUES (:cid, :nombre, :email, :hash, 'admin_clinica', 1, 1)"
        );
        $stmt->execute([
            'cid'    => $clinicaId,
            'nombre' => $data['nombre'],
            'email'  => $data['email'],
            'hash'   => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);
        $usuarioId = (int) $db->lastInsertId();

        // 4. Crear perfil de médico (si tiene cédula o especialidad)
        if ($data['especialidad'] !== '' || $data['cedula'] !== '') {
            $stmt2 = $db->prepare(
                "INSERT INTO medicos
                 (clinica_id, usuario_id, nombre, especialidad, cedula_profesional, activo)
                 VALUES (:cid, :uid, :nombre, :esp, :ced, 1)"
            );
            $stmt2->execute([
                'cid'    => $clinicaId,
                'uid'    => $usuarioId,
                'nombre' => $data['nombre'],
                'esp'    => $data['especialidad'] ?: null,
                'ced'    => $data['cedula'] ?: null,
            ]);
        }

        flash('success', "Clínica \"{$data['clinica_nombre']}\" creada. Usuario: {$data['email']}");
        redirect('superadmin');
    }

    /** GET /superadmin/clinicas/{id}/editar */
    public function edit(array $params): void
    {
        Auth::requireSuperAdmin();
        $clinica = $this->clinicaOr404((int) $params['id']);
        clear_old();

        $this->render('superadmin/form', [
            'pageTitle' => 'Editar clínica · ' . $clinica['nombre'],
            'clinica'   => $clinica,
            'errores'   => [],
        ]);
    }

    /** POST /superadmin/clinicas/{id} */
    public function update(array $params): void
    {
        Auth::requireSuperAdmin();
        Csrf::verify();

        $id      = (int) $params['id'];
        $clinica = $this->clinicaOr404($id);

        $precioMensual = trim((string) ($_POST['precio_mensual'] ?? ''));
        $precioAnual   = trim((string) ($_POST['precio_anual']   ?? ''));
        $estadoSaas    = trim((string) ($_POST['estado_saas']    ?? ''));
        $susHasta      = trim((string) ($_POST['suscripcion_hasta'] ?? ''));
        $trialHasta    = trim((string) ($_POST['trial_ends_at'] ?? ''));

        $cambios = [];

        if ($precioMensual !== '') {
            $cambios['precio_mensual'] = (int) round((float) $precioMensual * 100);
        } elseif (isset($_POST['precio_mensual'])) {
            $cambios['precio_mensual'] = null;
        }

        if ($precioAnual !== '') {
            $cambios['precio_anual'] = (int) round((float) $precioAnual * 100);
        } elseif (isset($_POST['precio_anual'])) {
            $cambios['precio_anual'] = null;
        }

        if (in_array($estadoSaas, ['trial', 'activo', 'suspendido'], true)) {
            $cambios['estado_saas'] = $estadoSaas;
        }
        if ($susHasta !== '') {
            $cambios['suscripcion_hasta'] = $susHasta;
        }
        if ($trialHasta !== '') {
            $cambios['trial_ends_at'] = $trialHasta;
        }

        if ($cambios !== []) {
            (new Clinica())->update($id, $cambios);
        }

        flash('success', 'Clínica actualizada.');
        redirect('superadmin');
    }

    // ── internos ──────────────────────────────────────────────────────────

    private function clinicaOr404(int $id): array
    {
        $c = (new Clinica())->find($id);
        if ($c === null) {
            http_response_code(404);
            die('Clínica no encontrada.');
        }
        return $c;
    }

    private function recogerPost(): array
    {
        return [
            'clinica_nombre'   => trim((string) ($_POST['clinica_nombre']   ?? '')),
            'nombre'           => trim((string) ($_POST['nombre']           ?? '')),
            'email'            => strtolower(trim((string) ($_POST['email'] ?? ''))),
            'telefono'         => trim((string) ($_POST['telefono']         ?? '')),
            'especialidad'     => trim((string) ($_POST['especialidad']     ?? '')),
            'cedula'           => trim((string) ($_POST['cedula']           ?? '')),
            'password'         => (string) ($_POST['password']              ?? ''),
            'precio_mensual'   => trim((string) ($_POST['precio_mensual']   ?? '')),
            'activacion'       => trim((string) ($_POST['activacion']       ?? 'trial')),
            'suscripcion_hasta'=> trim((string) ($_POST['suscripcion_hasta']?? '')),
        ];
    }

    private function validar(array $d): array
    {
        $e = [];
        if ($d['clinica_nombre'] === '') {
            $e['clinica_nombre'] = 'El nombre del consultorio es obligatorio.';
        }
        if ($d['nombre'] === '') {
            $e['nombre'] = 'El nombre del propietario es obligatorio.';
        }
        if ($d['email'] === '') {
            $e['email'] = 'El email es obligatorio.';
        } elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Formato de email no válido.';
        } else {
            $db   = Database::conn();
            $stmt = $db->prepare("SELECT 1 FROM usuarios WHERE email = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$d['email']]);
            if ($stmt->fetchColumn()) {
                $e['email'] = 'Este email ya tiene una cuenta registrada.';
            }
        }
        if (strlen($d['password']) < 8) {
            $e['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($d['precio_mensual'] !== '' && (!is_numeric($d['precio_mensual']) || (float) $d['precio_mensual'] <= 0)) {
            $e['precio_mensual'] = 'El precio debe ser un número positivo.';
        }
        if ($d['activacion'] === 'activo' && $d['suscripcion_hasta'] === '') {
            $e['suscripcion_hasta'] = 'Indica la fecha de vencimiento de la suscripción.';
        }
        return $e;
    }
}
