<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Auditoria;
use App\Models\Paciente;
use App\Models\Consulta;

final class PacienteController extends Controller
{
    private Paciente $pacientes;

    public function __construct()
    {
        $this->pacientes = new Paciente();
    }

    /** GET /pacientes — lista con búsqueda instantánea. */
    public function index(): void
    {
        Auth::require();
        $recientes = $this->pacientes->buscar('', 25);
        $this->render('pacientes/index', [
            'recientes' => $recientes,
            'total'     => $this->pacientes->contar(),
        ], 'app');
    }

    /** GET /pacientes/buscar?q= — devuelve JSON para la búsqueda en vivo. */
    public function buscar(): void
    {
        Auth::require();
        $q = (string) $this->input('q', '');
        $rows = $this->pacientes->buscar($q, 25);

        $out = array_map(static function (array $p): array {
            return [
                'id'       => (int) $p['id'],
                'nombre'   => nombre_completo($p),
                'edad'     => edad_anios($p['fecha_nacimiento'] ?? null),
                'sexo'     => sexo_label($p['sexo'] ?? null),
                'telefono' => $p['telefono'] ?? '',
            ];
        }, $rows);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
    }

    /** GET /pacientes/nuevo — formulario de alta. */
    public function create(): void
    {
        Auth::require();
        clear_old();
        $this->render('pacientes/form', [
            'paciente'   => null,
            'errores'    => [],
            'duplicados' => [],
        ], 'app');
    }

    /** POST /pacientes — guarda un paciente nuevo. */
    public function store(): void
    {
        Auth::require();
        Csrf::verify();

        $data = $this->datosDesdePost();
        $errores = $this->validar($data);

        if ($errores !== []) {
            set_old($_POST);
            $this->render('pacientes/form', [
                'paciente' => null, 'errores' => $errores, 'duplicados' => [],
            ], 'app');
            return;
        }

        // Aviso de posible duplicado (a menos que el usuario confirme crearlo igual).
        if (empty($_POST['confirmar_duplicado'])) {
            $dups = $this->pacientes->duplicados(
                $data['nombre'], $data['apellido_paterno'], $data['apellido_materno']
            );
            if ($dups !== []) {
                set_old($_POST);
                $this->render('pacientes/form', [
                    'paciente' => null, 'errores' => [], 'duplicados' => $dups,
                ], 'app');
                return;
            }
        }

        $data['creado_por'] = Auth::id();
        $id = $this->pacientes->create($data);

        Auditoria::log('paciente.crear', 'paciente', $id, ['nombre' => $data['nombre']]);
        clear_old();
        flash('success', 'Paciente registrado correctamente.');
        redirect('pacientes/' . $id);
    }

    /** GET /pacientes/{id} — detalle. */
    public function show(array $params): void
    {
        Auth::require();
        $id = (int) $params['id'];
        $paciente = $this->pacientes->find($id);

        if ($paciente === null) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        // Acceso a un expediente: se audita (LFPDPPP).
        Auditoria::log('paciente.ver', 'paciente', $id);

        $consultas = Auth::puedeVerClinico()
            ? (new Consulta())->porPaciente($id, 5)
            : [];

        $this->render('pacientes/show', [
            'p'             => $paciente,
            'puedeClinico'  => Auth::puedeVerClinico(),
            'puedeEliminar' => $this->puedeEliminar(),
            'consultas'     => $consultas,
        ], 'app');
    }

    /** GET /pacientes/{id}/editar — formulario de edición. */
    public function edit(array $params): void
    {
        Auth::require();
        clear_old();
        $id = (int) $params['id'];
        $paciente = $this->pacientes->find($id);

        if ($paciente === null) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        $this->render('pacientes/form', [
            'paciente' => $paciente, 'errores' => [], 'duplicados' => [],
        ], 'app');
    }

    /** POST /pacientes/{id} — actualiza. */
    public function update(array $params): void
    {
        Auth::require();
        Csrf::verify();

        $id = (int) $params['id'];
        $paciente = $this->pacientes->find($id);
        if ($paciente === null) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        $data = $this->datosDesdePost();
        $errores = $this->validar($data);

        if ($errores !== []) {
            set_old($_POST);
            $this->render('pacientes/form', [
                'paciente' => $paciente, 'errores' => $errores, 'duplicados' => [],
            ], 'app');
            return;
        }

        // Si recepción edita, no puede modificar antecedentes (campo clínico):
        // se conserva el valor existente.
        if (!Auth::puedeVerClinico()) {
            unset($data['antecedentes']);
        }

        $this->pacientes->update($id, $data);
        Auditoria::log('paciente.editar', 'paciente', $id);
        clear_old();
        flash('success', 'Datos del paciente actualizados.');
        redirect('pacientes/' . $id);
    }

    /** POST /pacientes/{id}/eliminar — baja lógica. */
    public function destroy(array $params): void
    {
        Auth::require();
        Csrf::verify();

        if (!$this->puedeEliminar()) {
            http_response_code(403);
            view('errors/403');
            return;
        }

        $id = (int) $params['id'];
        $paciente = $this->pacientes->find($id);
        if ($paciente === null) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        $this->pacientes->delete($id); // soft delete
        Auditoria::log('paciente.eliminar', 'paciente', $id);
        flash('success', 'Paciente dado de baja.');
        redirect('pacientes');
    }

    // ---------------- internos ----------------

    /** Solo médico, admin de clínica o propietario pueden eliminar pacientes. */
    private function puedeEliminar(): bool
    {
        return Auth::is('medico', 'admin_clinica') || Auth::esPropietario();
    }

    /** Extrae y normaliza los datos del formulario. */
    private function datosDesdePost(): array
    {
        $campos = [
            'nombre', 'apellido_paterno', 'apellido_materno', 'sexo', 'fecha_nacimiento',
            'curp', 'telefono', 'email', 'direccion', 'ciudad', 'estado', 'cp',
            'tipo_sangre', 'alergias', 'antecedentes', 'contacto_emergencia', 'tel_emergencia',
        ];
        $data = [];
        foreach ($campos as $c) {
            $val = trim((string) ($_POST[$c] ?? ''));
            $data[$c] = ($val === '') ? null : $val;
        }
        // CURP siempre en mayúsculas.
        if ($data['curp'] !== null) {
            $data['curp'] = strtoupper($data['curp']);
        }
        // El nombre y apellido paterno no pueden ser null (son obligatorios).
        $data['nombre'] = $data['nombre'] ?? '';
        $data['apellido_paterno'] = $data['apellido_paterno'] ?? '';
        return $data;
    }

    /** Validación mínima: solo nombre y apellido paterno son obligatorios. */
    private function validar(array $d): array
    {
        $e = [];

        if (($d['nombre'] ?? '') === '') {
            $e['nombre'] = 'El nombre es obligatorio.';
        }
        if (($d['apellido_paterno'] ?? '') === '') {
            $e['apellido_paterno'] = 'El apellido paterno es obligatorio.';
        }
        if (!empty($d['sexo']) && !in_array($d['sexo'], ['M', 'F', 'O'], true)) {
            $e['sexo'] = 'Sexo no válido.';
        }
        if (!empty($d['fecha_nacimiento'])) {
            $f = \DateTime::createFromFormat('Y-m-d', $d['fecha_nacimiento']);
            if (!$f) {
                $e['fecha_nacimiento'] = 'Fecha no válida.';
            } elseif ($f > new \DateTime('today')) {
                $e['fecha_nacimiento'] = 'La fecha no puede ser futura.';
            }
        }
        if (!empty($d['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Correo no válido.';
        }
        if (!empty($d['curp']) && !preg_match('/^[A-Z0-9]{18}$/', $d['curp'])) {
            $e['curp'] = 'La CURP debe tener 18 caracteres.';
        }

        return $e;
    }
}
