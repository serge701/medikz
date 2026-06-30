<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Auditoria;
use App\Models\Consulta;
use App\Models\Medico;
use App\Models\Paciente;

final class ConsultaController extends Controller
{
    private Consulta $consultas;
    private Medico   $medicos;
    private Paciente $pacientes;

    public function __construct()
    {
        $this->consultas = new Consulta();
        $this->medicos   = new Medico();
        $this->pacientes = new Paciente();
    }

    /** GET /consultas[?paciente_id=X&desde=Y&hasta=Z] */
    public function index(): void
    {
        Auth::requireRole('medico', 'admin_clinica');

        $pacienteId = (int) ($_GET['paciente_id'] ?? 0);
        $paciente   = $pacienteId > 0 ? $this->pacientes->find($pacienteId) : null;

        $desde = trim((string) ($_GET['desde'] ?? ''));
        $hasta = trim((string) ($_GET['hasta'] ?? ''));

        $filtroActivo = $desde !== '' || $hasta !== '';

        // Sin filtro explícito y sin paciente: mostrar últimos 30 días
        if (!$filtroActivo && $pacienteId === 0) {
            $desde = date('Y-m-d', strtotime('-30 days'));
            $hasta = date('Y-m-d');
        }

        $consultas = $this->consultas->recientes(
            200,
            $pacienteId ?: null,
            $desde !== '' ? $desde : null,
            $hasta !== '' ? $hasta : null
        );

        $this->render('consultas/index', [
            'pageTitle'    => 'Historial clínico',
            'consultas'    => $consultas,
            'paciente'     => $paciente,
            'desde'        => $desde,
            'hasta'        => $hasta,
            'filtroActivo' => $filtroActivo,
        ]);
    }

    /** GET /consultas/nueva[?paciente_id=X&cita_id=Y] */
    public function create(): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        clear_old();

        $pacienteId = (int) ($_GET['paciente_id'] ?? 0);
        $citaId     = (int) ($_GET['cita_id']     ?? 0);
        $paciente   = $pacienteId > 0 ? $this->pacientes->find($pacienteId) : null;

        $this->render('consultas/form', [
            'pageTitle'  => 'Nueva consulta',
            'consulta'   => null,
            'paciente'   => $paciente,
            'medicos'    => $this->medicos->activos(),
            'citaId'     => $citaId ?: null,
            'errores'    => [],
        ]);
    }

    /** POST /consultas */
    public function store(): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        Csrf::verify();

        $data    = $this->datosDesdePost();
        $errores = $this->validar($data);

        if ($errores !== []) {
            set_old($_POST);
            $paciente = !empty($data['paciente_id'])
                ? $this->pacientes->find((int) $data['paciente_id'])
                : null;
            $this->render('consultas/form', [
                'pageTitle' => 'Nueva consulta',
                'consulta'  => null,
                'paciente'  => $paciente,
                'medicos'   => $this->medicos->activos(),
                'citaId'    => $data['cita_id'],
                'errores'   => $errores,
            ]);
            return;
        }

        $data['creado_por'] = Auth::id();
        $id = $this->consultas->create($data);

        Auditoria::log('consulta.crear', 'consulta', $id, [
            'paciente_id' => $data['paciente_id'],
            'fecha'       => $data['fecha_consulta'],
        ]);
        clear_old();
        flash('success', 'Consulta registrada correctamente.');
        redirect('consultas/' . $id);
    }

    /** GET /consultas/{id} */
    public function show(array $params): void
    {
        Auth::requireRole('medico', 'admin_clinica');

        $consulta = $this->consultaOr404((int) $params['id']);
        Auditoria::log('consulta.ver', 'consulta', (int) $consulta['id']);

        $this->render('consultas/show', [
            'pageTitle' => 'Consulta clínica',
            'consulta'  => $consulta,
        ]);
    }

    /** GET /consultas/{id}/editar */
    public function edit(array $params): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        $consulta = $this->consultaOr404((int) $params['id']);
        clear_old();

        $paciente = $this->pacientes->find((int) $consulta['paciente_id']);

        $this->render('consultas/form', [
            'pageTitle' => 'Editar consulta',
            'consulta'  => $consulta,
            'paciente'  => $paciente,
            'medicos'   => $this->medicos->activos(),
            'citaId'    => $consulta['cita_id'] ? (int) $consulta['cita_id'] : null,
            'errores'   => [],
        ]);
    }

    /** POST /consultas/{id} */
    public function update(array $params): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        Csrf::verify();

        $id       = (int) $params['id'];
        $consulta = $this->consultaOr404($id);
        $data     = $this->datosDesdePost();
        $errores  = $this->validar($data);

        if ($errores !== []) {
            set_old($_POST);
            $paciente = $this->pacientes->find((int) $consulta['paciente_id']);
            $this->render('consultas/form', [
                'pageTitle' => 'Editar consulta',
                'consulta'  => $consulta,
                'paciente'  => $paciente,
                'medicos'   => $this->medicos->activos(),
                'citaId'    => $data['cita_id'],
                'errores'   => $errores,
            ]);
            return;
        }

        $this->consultas->update($id, $data);
        Auditoria::log('consulta.editar', 'consulta', $id);
        clear_old();
        flash('success', 'Consulta actualizada.');
        redirect('consultas/' . $id);
    }

    /** POST /consultas/{id}/eliminar */
    public function destroy(array $params): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        Csrf::verify();

        $id       = (int) $params['id'];
        $consulta = $this->consultaOr404($id);

        $this->consultas->delete($id);
        Auditoria::log('consulta.eliminar', 'consulta', $id);
        flash('success', 'Consulta eliminada.');
        redirect('consultas?paciente_id=' . $consulta['paciente_id']);
    }

    // ---- internos ----

    private function consultaOr404(int $id): array
    {
        $c = $this->consultas->conDetalle($id);
        if ($c === null) {
            http_response_code(404);
            view('errors/404');
            exit;
        }
        return $c;
    }

    private function datosDesdePost(): array
    {
        $campos = [
            'paciente_id', 'medico_id', 'cita_id', 'fecha_consulta',
            'motivo_consulta', 'exploracion_fisica', 'diagnostico',
            'tratamiento', 'observaciones', 'proximo_control',
        ];
        $data = [];
        foreach ($campos as $c) {
            $val      = trim((string) ($_POST[$c] ?? ''));
            $data[$c] = ($val === '') ? null : $val;
        }
        $data['paciente_id'] = !empty($data['paciente_id']) ? (int) $data['paciente_id'] : null;
        $data['medico_id']   = !empty($data['medico_id'])   ? (int) $data['medico_id']   : null;
        $data['cita_id']     = !empty($data['cita_id'])     ? (int) $data['cita_id']     : null;
        return $data;
    }

    private function validar(array $d): array
    {
        $e = [];
        if (empty($d['paciente_id'])) {
            $e['paciente_id'] = 'Selecciona un paciente.';
        }
        if (empty($d['fecha_consulta'])) {
            $e['fecha_consulta'] = 'La fecha es obligatoria.';
        } elseif (!\DateTime::createFromFormat('Y-m-d', $d['fecha_consulta'])) {
            $e['fecha_consulta'] = 'Fecha no válida.';
        }
        return $e;
    }
}
