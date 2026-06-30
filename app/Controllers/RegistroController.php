<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Auditoria;
use App\Core\Config;
use App\Models\Clinica;
use App\Models\Usuario;
use App\Models\Medico;

/**
 * Registro público de nuevos doctores (SaaS onboarding).
 * Crea la clínica, el usuario propietario y activa el trial de 14 días.
 */
final class RegistroController extends Controller
{
    /** GET /registro */
    public function show(): void
    {
        if (Auth::check()) {
            redirect('');
            return;
        }

        $this->render('auth/registro', [
            'pageTitle' => 'Crear cuenta',
            'errores'   => [],
        ], 'guest');
    }

    /** POST /registro */
    public function store(): void
    {
        if (Auth::check()) {
            redirect('');
            return;
        }

        Csrf::verify();

        $data    = $this->recogerPost();
        $errores = $this->validar($data);

        if ($errores !== []) {
            set_old($_POST);
            $this->render('auth/registro', [
                'pageTitle' => 'Crear cuenta',
                'errores'   => $errores,
            ], 'guest');
            return;
        }

        // 1. Crear la clínica (guardamos la IP para anti-abuso)
        $clinicaModel = new Clinica();
        $clinicaId    = $clinicaModel->create([
            'nombre'      => $data['clinica_nombre'],
            'tipo_plan'   => 'individual',
            'email'       => $data['email'],
            'telefono'    => $data['telefono'],
            'activo'      => 1,
            'registro_ip' => $data['ip'],
        ]);

        // 2. Activar trial
        $diasTrial = (int) (Config::get('stripe')['trial_dias'] ?? 14);
        $clinicaModel->activarTrial($clinicaId, $diasTrial);

        // 3. Crear usuario propietario con PDO directo
        // (sin sesión aún — BaseModel::create requiere tenant en sesión,
        //  así que usamos una inserción raw para el primer usuario)
        $db   = \App\Core\Database::conn();
        $stmt = $db->prepare(
            "INSERT INTO usuarios
             (clinica_id, nombre, email, password_hash, rol, es_propietario, activo)
             VALUES (:cid, :nombre, :email, :hash, 'medico', 1, 1)"
        );
        $stmt->execute([
            'cid'    => $clinicaId,
            'nombre' => $data['nombre'],
            'email'  => $data['email'],
            'hash'   => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);
        $usuarioId = (int) $db->lastInsertId();

        // 4. Iniciar sesión ANTES de usar modelos tenant-scoped
        Auth::loginManual($clinicaId, $usuarioId, $data['nombre'], $data['email'], 'medico', 1);

        // 5. Crear perfil de médico (ya hay sesión con clinica_id)
        $medicoModel = new Medico();
        $medicoModel->create([
            'usuario_id'         => $usuarioId,
            'nombre'             => $data['nombre'],
            'especialidad'       => $data['especialidad'] ?: null,
            'cedula_profesional' => $data['cedula'] ?: null,
            'activo'             => 1,
        ]);

        Auditoria::log('clinica.registro', 'clinica', $clinicaId, [
            'email' => $data['email'],
        ]);

        clear_old();
        flash('success', "¡Bienvenido/a {$data['nombre']}! Tienes {$diasTrial} días de prueba gratuita.");
        redirect('');
    }

    // ── internos ──────────────────────────────────────────────────────────

    private function recogerPost(): array
    {
        return [
            'clinica_nombre' => trim((string) ($_POST['clinica_nombre'] ?? '')),
            'nombre'         => trim((string) ($_POST['nombre']         ?? '')),
            'email'          => strtolower(trim((string) ($_POST['email']    ?? ''))),
            'telefono'       => trim((string) ($_POST['telefono']       ?? '')),
            'especialidad'   => trim((string) ($_POST['especialidad']   ?? '')),
            'cedula'         => trim((string) ($_POST['cedula']         ?? '')),
            'password'       => (string) ($_POST['password']            ?? ''),
            'password2'      => (string) ($_POST['password2']           ?? ''),
            'terminos'       => isset($_POST['terminos']),
            'ip'             => $this->obtenerIp(),
        ];
    }

    private function obtenerIp(): string
    {
        // Respeta proxies reales (Cloudflare, load balancers), pero no confía ciegamente en headers
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $h) {
            $val = $_SERVER[$h] ?? '';
            if ($val === '') {
                continue;
            }
            // X-Forwarded-For puede ser una lista; tomamos la primera IP
            $ip = trim(explode(',', $val)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '0.0.0.0';
    }

    private function validar(array $d): array
    {
        $e = [];

        if ($d['clinica_nombre'] === '') {
            $e['clinica_nombre'] = 'El nombre del consultorio es obligatorio.';
        }
        if ($d['nombre'] === '') {
            $e['nombre'] = 'Tu nombre es obligatorio.';
        }
        if ($d['email'] === '') {
            $e['email'] = 'El email es obligatorio.';
        } elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Formato de email no válido.';
        } else {
            $usuarioModel = new Usuario();
            if ($usuarioModel->existeEmailGlobal($d['email'])) {
                $e['email'] = 'Este email ya tiene una cuenta registrada.';
            }
        }
        $clinicaModel = new Clinica();
        if (!empty($d['telefono']) && $clinicaModel->existeTelefonoGlobal($d['telefono'])) {
            $e['telefono'] = 'Este teléfono ya está asociado a una cuenta existente.';
        }
        if ($clinicaModel->ipBloqueada($d['ip'])) {
            $e['_global'] = 'Se ha alcanzado el límite de cuentas de prueba desde esta red. Contáctanos si necesitas ayuda.';
        }
        if (strlen($d['password']) < 8) {
            $e['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($d['password'] !== $d['password2']) {
            $e['password2'] = 'Las contraseñas no coinciden.';
        }
        if (!$d['terminos']) {
            $e['terminos'] = 'Debes aceptar los términos de uso.';
        }

        return $e;
    }
}
