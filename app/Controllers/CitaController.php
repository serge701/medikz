<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Auditoria;
use App\Core\Tenant;
use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Clinica;
use App\Services\WhatsappService;

final class CitaController extends Controller
{
    private Cita     $citas;
    private Medico   $medicos;
    private Paciente $pacientes;

    public function __construct()
    {
        $this->citas     = new Cita();
        $this->medicos   = new Medico();
        $this->pacientes = new Paciente();
    }

    /** GET /agenda */
    public function index(): void
    {
        Auth::require();

        $this->render('agenda/index', [
            'pageTitle'    => 'Agenda',
            'fechaInicial' => $this->fechaValida($this->input('fecha', '')),
        ]);
    }

    /** GET /agenda/eventos?start=...&end=... — JSON para FullCalendar */
    public function eventos(): void
    {
        Auth::require();

        $inicio = substr(trim((string) ($_GET['start'] ?? date('Y-m-d'))), 0, 10);
        $fin    = substr(trim((string) ($_GET['end']   ?? date('Y-m-d'))), 0, 10);

        $colores = [
            'programada'  => '#3b82f6',
            'confirmada'  => '#16a34a',
            'cancelada'   => '#dc2626',
            'atendida'    => '#6b7280',
            'no_asistio'  => '#d97706',
        ];
        $noEditables = ['cancelada', 'atendida', 'no_asistio'];

        $eventos = [];
        foreach ($this->citas->porRango($inicio, $fin) as $c) {
            $nombre    = trim($c['pac_nombre'] . ' ' . $c['pac_ap'] . ' ' . ($c['pac_am'] ?? ''));
            $estado    = $c['estado'] ?? 'programada';
            $horaFin   = $c['hora_fin'] ?? $c['hora_inicio'];
            $eventos[] = [
                'id'        => (string) $c['id'],
                'title'     => $nombre,
                'start'     => $c['fecha'] . 'T' . $c['hora_inicio'],
                'end'       => $c['fecha'] . 'T' . $horaFin,
                'color'     => $colores[$estado] ?? '#3b82f6',
                'editable'  => !in_array($estado, $noEditables, true),
                'extendedProps' => [
                    'estado'  => $estado,
                    'motivo'  => $c['motivo'] ?? '',
                    'medico'  => $c['med_nombre'] ?? '',
                    'url'     => url('agenda/' . $c['id']),
                ],
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($eventos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /agenda/{id}/mover — drag & drop / resize */
    public function mover(array $params): void
    {
        Auth::require();
        Csrf::verify();

        $id   = (int) $params['id'];
        $cita = $this->citas->find($id);

        if ($cita === null) {
            $this->jsonResponse(false, 'Cita no encontrada.');
        }
        if (!in_array($cita['estado'], ['programada', 'confirmada'], true)) {
            $this->jsonResponse(false, 'Esta cita ya no se puede modificar.');
        }

        $fecha      = trim((string) ($_POST['fecha']       ?? ''));
        $horaInicio = trim((string) ($_POST['hora_inicio'] ?? ''));
        $horaFin    = trim((string) ($_POST['hora_fin']    ?? ''));

        if (!$fecha || !$horaInicio || !$horaFin) {
            $this->jsonResponse(false, 'Datos incompletos.');
        }
        if ($horaFin <= $horaInicio) {
            $this->jsonResponse(false, 'La hora de fin debe ser mayor que la de inicio.');
        }
        if ($this->citas->hayConflicto($fecha, $horaInicio, $horaFin, (int)($cita['medico_id'] ?? 0) ?: null, $id)) {
            $this->jsonResponse(false, 'Ya existe una cita en ese horario.');
        }

        $this->citas->update($id, [
            'fecha'       => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin'    => $horaFin,
        ]);
        Auditoria::log('cita.mover', 'cita', $id, ['fecha' => $fecha, 'inicio' => $horaInicio]);

        $this->jsonResponse(true);
    }

    /** GET /agenda/nueva[?paciente_id=X&fecha=Y&hora_inicio=H&hora_fin=H] */
    public function create(): void
    {
        Auth::require();
        clear_old();

        $pacienteId = (int) ($_GET['paciente_id'] ?? 0);
        $paciente   = $pacienteId > 0 ? $this->pacientes->find($pacienteId) : null;
        $fecha      = $this->fechaValida($_GET['fecha'] ?? '');
        $horaInicio = trim((string) ($_GET['hora_inicio'] ?? '09:00'));
        $horaFin    = trim((string) ($_GET['hora_fin']    ?? '09:30'));

        $this->render('agenda/form', [
            'pageTitle'         => 'Nueva cita',
            'cita'              => null,
            'paciente'          => $paciente,
            'medicos'           => $this->medicos->activos(),
            'errores'           => [],
            'fechaDefault'      => $fecha,
            'horaInicioDefault' => $horaInicio,
            'horaFinDefault'    => $horaFin,
        ]);
    }

    /** POST /agenda */
    public function store(): void
    {
        Auth::require();
        Csrf::verify();

        $data    = $this->datosDesdePost();
        $errores = $this->validar($data);

        if ($errores !== []) {
            set_old($_POST);
            $paciente = !empty($data['paciente_id'])
                ? $this->pacientes->find((int) $data['paciente_id'])
                : null;
            $this->render('agenda/form', [
                'pageTitle'    => 'Nueva cita',
                'cita'         => null,
                'paciente'     => $paciente,
                'medicos'      => $this->medicos->activos(),
                'errores'      => $errores,
                'fechaDefault' => $data['fecha'] ?? date('Y-m-d'),
            ]);
            return;
        }


        $data['creado_por'] = Auth::id();
        $id = $this->citas->create($data);

        Auditoria::log('cita.crear', 'cita', $id, ['fecha' => $data['fecha']]);
        $this->enviarConfirmacion($id, (int) $data['paciente_id']);
        clear_old();
        flash('success', 'Cita registrada correctamente.');
        redirect('agenda/' . $id);
    }

    /** GET /agenda/{id} */
    public function show(array $params): void
    {
        Auth::require();
        $cita     = $this->citaOr404((int) $params['id']);
        $paciente = $this->pacientes->find((int) $cita['paciente_id']);

        Auditoria::log('cita.ver', 'cita', (int) $cita['id']);

        $this->render('agenda/show', [
            'pageTitle'         => 'Detalle de cita',
            'cita'              => $cita,
            'paciente'          => $paciente,
            'puedeClinico'      => Auth::puedeVerClinico(),
            'puedeEditar'       => $this->puedeEditar($cita),
            'consultaExistente' => (new \App\Models\Consulta())->porCita((int) $cita['id']),
        ]);
    }

    /** GET /agenda/{id}/editar */
    public function edit(array $params): void
    {
        Auth::require();
        $cita = $this->citaOr404((int) $params['id']);

        if (!$this->puedeEditar($cita)) {
            flash('error', 'Esta cita ya no se puede editar.');
            redirect('agenda/' . $cita['id']);
            return;
        }

        clear_old();
        $paciente = $this->pacientes->find((int) $cita['paciente_id']);

        $this->render('agenda/form', [
            'pageTitle'    => 'Editar cita',
            'cita'         => $cita,
            'paciente'     => $paciente,
            'medicos'      => $this->medicos->activos(),
            'errores'      => [],
            'fechaDefault' => $cita['fecha'],
        ]);
    }

    /** POST /agenda/{id} */
    public function update(array $params): void
    {
        Auth::require();
        Csrf::verify();

        $id   = (int) $params['id'];
        $cita = $this->citaOr404($id);

        if (!$this->puedeEditar($cita)) {
            flash('error', 'Esta cita ya no se puede editar.');
            redirect('agenda/' . $id);
            return;
        }

        $data    = $this->datosDesdePost();
        $errores = $this->validar($data, $id);

        if ($errores !== []) {
            set_old($_POST);
            $paciente = $this->pacientes->find((int) $cita['paciente_id']);
            $this->render('agenda/form', [
                'pageTitle'    => 'Editar cita',
                'cita'         => $cita,
                'paciente'     => $paciente,
                'medicos'      => $this->medicos->activos(),
                'errores'      => $errores,
                'fechaDefault' => $cita['fecha'],
            ]);
            return;
        }

        $this->citas->update($id, $data);
        Auditoria::log('cita.editar', 'cita', $id);
        clear_old();
        flash('success', 'Cita actualizada.');
        redirect('agenda/' . $id);
    }

    /** POST /agenda/{id}/cancelar */
    public function cancelar(array $params): void
    {
        Auth::require();
        Csrf::verify();

        $id    = (int) $params['id'];
        $cita  = $this->citaOr404($id);
        $motivo = trim((string) ($_POST['motivo_cancelacion'] ?? ''));

        if (in_array($cita['estado'], ['atendida', 'cancelada'], true)) {
            flash('error', 'Esta cita no se puede cancelar.');
            redirect('agenda/' . $id);
            return;
        }

        $this->citas->cambiarEstado($id, 'cancelada', $motivo ?: null);
        Auditoria::log('cita.cancelar', 'cita', $id);
        flash('success', 'Cita cancelada.');
        redirect('agenda/' . $id);
    }

    /** POST /agenda/{id}/atender — marca la cita como atendida */
    public function atender(array $params): void
    {
        Auth::require();
        Csrf::verify();

        $id   = (int) $params['id'];
        $cita = $this->citaOr404($id);

        if (!in_array($cita['estado'], ['programada', 'confirmada'], true)) {
            flash('error', 'Esta cita no se puede marcar como atendida.');
            redirect('agenda/' . $id);
            return;
        }

        $this->citas->cambiarEstado($id, 'atendida');
        Auditoria::log('cita.atender', 'cita', $id);
        flash('success', 'Cita marcada como atendida.');
        redirect('agenda/' . $id);
    }

    // ---- internos ----

    private function citaOr404(int $id): array
    {
        $cita = $this->citas->find($id);
        if ($cita === null) {
            http_response_code(404);
            view('errors/404');
            exit;
        }
        return $cita;
    }

    private function puedeEditar(array $cita): bool
    {
        return in_array($cita['estado'], ['programada', 'confirmada'], true);
    }

    private function datosDesdePost(): array
    {
        $campos = [
            'paciente_id', 'medico_id', 'fecha', 'hora_inicio', 'hora_fin',
            'motivo', 'estado', 'notas',
        ];
        $data = [];
        foreach ($campos as $c) {
            $val      = trim((string) ($_POST[$c] ?? ''));
            $data[$c] = ($val === '') ? null : $val;
        }
        $data['paciente_id'] = !empty($data['paciente_id']) ? (int) $data['paciente_id'] : null;
        $data['medico_id']   = !empty($data['medico_id'])   ? (int) $data['medico_id']   : null;
        $data['estado']      = $data['estado'] ?? 'programada';
        return $data;
    }

    private function validar(array $d, ?int $excluirId = null): array
    {
        $e = [];

        if (empty($d['paciente_id'])) {
            $e['paciente_id'] = 'Selecciona un paciente.';
        }
        if (empty($d['fecha'])) {
            $e['fecha'] = 'La fecha es obligatoria.';
        } elseif (!\DateTime::createFromFormat('Y-m-d', $d['fecha'])) {
            $e['fecha'] = 'Fecha no válida.';
        }
        if (empty($d['hora_inicio'])) {
            $e['hora_inicio'] = 'La hora de inicio es obligatoria.';
        }
        if (empty($d['hora_fin'])) {
            $e['hora_fin'] = 'La hora de fin es obligatoria.';
        }
        if (!empty($d['hora_inicio']) && !empty($d['hora_fin']) && $d['hora_fin'] <= $d['hora_inicio']) {
            $e['hora_fin'] = 'La hora de fin debe ser mayor que la de inicio.';
        }

        // Verificar traslape de horario solo si los campos básicos son válidos
        if (empty($e['fecha']) && empty($e['hora_inicio']) && empty($e['hora_fin'])) {
            $medicoId = !empty($d['medico_id']) ? (int) $d['medico_id'] : null;
            if ($this->citas->hayConflicto($d['fecha'], $d['hora_inicio'], $d['hora_fin'], $medicoId, $excluirId)) {
                $quien = $medicoId !== null ? 'El médico seleccionado ya tiene' : 'Ya existe';
                $e['hora_inicio'] = $quien . ' una cita en ese horario. Elige otra hora.';
            }
        }

        return $e;
    }

    private function enviarConfirmacion(int $citaId, int $pacienteId): void
    {
        $paciente = $this->pacientes->find($pacienteId);
        $telefono = trim((string) ($paciente['telefono'] ?? ''));
        if ($telefono === '') {
            return;
        }

        $cita    = $this->citas->find($citaId);
        $clinica = (new Clinica())->find(Tenant::clinicaId());

        $dias  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
        $meses = ['','enero','febrero','marzo','abril','mayo','junio','julio',
                  'agosto','septiembre','octubre','noviembre','diciembre'];

        $fechaStr = '';
        if (!empty($cita['fecha'])) {
            $ts       = strtotime($cita['fecha']);
            $fechaStr = $dias[date('w', $ts)] . ' '
                      . (int)date('j', $ts) . ' de '
                      . $meses[(int)date('n', $ts)];
        }

        $nombrePac = trim(
            ($paciente['nombre'] ?? '') . ' ' .
            ($paciente['apellido_paterno'] ?? '')
        );
        $clinicaNombre = $clinica['nombre'] ?? 'la clínica';
        $hora          = !empty($cita['hora_inicio'])
            ? substr((string)$cita['hora_inicio'], 0, 5)
            : '';

        $mensaje = "Hola {$nombrePac}, tu cita en {$clinicaNombre} quedó programada para el "
                 . "{$fechaStr}" . ($hora ? " a las {$hora}" : '') . ". "
                 . "Si necesitas cancelar o cambiar la hora, comunícate con nosotros.";

        (new WhatsappService())->enviar(
            Tenant::clinicaId(),
            $citaId,
            'confirmacion',
            $telefono,
            $mensaje
        );
    }

    private function fechaValida(string $fecha): string
    {
        if ($fecha !== '' && \DateTime::createFromFormat('Y-m-d', $fecha)) {
            return $fecha;
        }
        return date('Y-m-d');
    }

    private function jsonResponse(bool $ok, string $error = ''): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $error]);
        exit;
    }
}
