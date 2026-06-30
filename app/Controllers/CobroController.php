<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Auditoria;
use App\Models\Cobro;
use App\Models\Paciente;

final class CobroController extends Controller
{
    private Cobro    $cobros;
    private Paciente $pacientes;

    public function __construct()
    {
        $this->cobros    = new Cobro();
        $this->pacientes = new Paciente();
    }

    /**
     * GET /cobros[?fecha=YYYY-MM-DD | ?paciente_id=X]
     * Modo fecha (default): muestra cobros del día + totales.
     * Modo paciente: historial de cobros de ese paciente.
     */
    public function index(): void
    {
        Auth::require();

        $pacienteId = (int) ($_GET['paciente_id'] ?? 0);

        if ($pacienteId > 0) {
            $paciente = $this->pacientes->find($pacienteId);
            $cobros   = $this->cobros->porPaciente($pacienteId);
            $this->render('cobros/index', [
                'pageTitle' => 'Cobros',
                'cobros'    => $cobros,
                'paciente'  => $paciente,
                'modoPaciente' => true,
                'fecha'     => null,
                'totales'   => null,
            ]);
            return;
        }

        $fecha   = $this->fechaValida($this->input('fecha', ''));
        $cobros  = $this->cobros->porFecha($fecha);
        $totales = $this->cobros->totalesPorFecha($fecha);
        $fechaObj  = new \DateTime($fecha);

        $this->render('cobros/index', [
            'pageTitle'    => 'Cobros',
            'cobros'       => $cobros,
            'paciente'     => null,
            'modoPaciente' => false,
            'fecha'        => $fecha,
            'fechaObj'     => $fechaObj,
            'anterior'     => (clone $fechaObj)->modify('-1 day')->format('Y-m-d'),
            'siguiente'    => (clone $fechaObj)->modify('+1 day')->format('Y-m-d'),
            'esHoy'        => $fecha === date('Y-m-d'),
            'totales'      => $totales,
        ]);
    }

    /** GET /cobros/nuevo[?paciente_id=X&cita_id=Y&consulta_id=Z] */
    public function create(): void
    {
        Auth::require();
        clear_old();

        $pacienteId = (int) ($_GET['paciente_id'] ?? 0);
        $citaId     = (int) ($_GET['cita_id']     ?? 0);
        $consultaId = (int) ($_GET['consulta_id'] ?? 0);
        $paciente   = $pacienteId > 0 ? $this->pacientes->find($pacienteId) : null;

        $this->render('cobros/form', [
            'pageTitle'  => 'Nuevo cobro',
            'cobro'      => null,
            'paciente'   => $paciente,
            'citaId'     => $citaId     ?: null,
            'consultaId' => $consultaId ?: null,
            'errores'    => [],
        ]);
    }

    /** POST /cobros */
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
            $this->render('cobros/form', [
                'pageTitle'  => 'Nuevo cobro',
                'cobro'      => null,
                'paciente'   => $paciente,
                'citaId'     => $data['cita_id'],
                'consultaId' => $data['consulta_id'],
                'errores'    => $errores,
            ]);
            return;
        }

        $data['creado_por'] = Auth::id();
        $id = $this->cobros->create($data);

        Auditoria::log('cobro.crear', 'cobro', $id, [
            'monto'   => $data['monto'],
            'concepto'=> $data['concepto'],
        ]);
        clear_old();
        flash('success', 'Cobro registrado correctamente.');
        redirect('cobros/' . $id);
    }

    /** GET /cobros/{id} */
    public function show(array $params): void
    {
        Auth::require();
        $cobro = $this->cobroOr404((int) $params['id']);

        $this->render('cobros/show', [
            'pageTitle'     => 'Detalle de cobro',
            'cobro'         => $cobro,
            'puedeEliminar' => Auth::is('medico', 'admin_clinica') || Auth::esPropietario(),
        ]);
    }

    /** GET /cobros/{id}/editar */
    public function edit(array $params): void
    {
        Auth::require();
        $cobro = $this->cobroOr404((int) $params['id']);

        if ($cobro['estado'] === 'cancelado') {
            flash('error', 'Un cobro cancelado no se puede editar.');
            redirect('cobros/' . $cobro['id']);
            return;
        }

        clear_old();
        $paciente = $this->pacientes->find((int) $cobro['paciente_id']);

        $this->render('cobros/form', [
            'pageTitle'  => 'Editar cobro',
            'cobro'      => $cobro,
            'paciente'   => $paciente,
            'citaId'     => $cobro['cita_id']     ? (int) $cobro['cita_id']     : null,
            'consultaId' => $cobro['consulta_id'] ? (int) $cobro['consulta_id'] : null,
            'errores'    => [],
        ]);
    }

    /** POST /cobros/{id} */
    public function update(array $params): void
    {
        Auth::require();
        Csrf::verify();

        $id    = (int) $params['id'];
        $cobro = $this->cobroOr404($id);
        $data  = $this->datosDesdePost();
        $errores = $this->validar($data);

        if ($errores !== []) {
            set_old($_POST);
            $paciente = $this->pacientes->find((int) $cobro['paciente_id']);
            $this->render('cobros/form', [
                'pageTitle'  => 'Editar cobro',
                'cobro'      => $cobro,
                'paciente'   => $paciente,
                'citaId'     => $data['cita_id'],
                'consultaId' => $data['consulta_id'],
                'errores'    => $errores,
            ]);
            return;
        }

        $this->cobros->update($id, $data);
        Auditoria::log('cobro.editar', 'cobro', $id);
        clear_old();
        flash('success', 'Cobro actualizado.');
        redirect('cobros/' . $id);
    }

    /** POST /cobros/{id}/cancelar */
    public function cancelar(array $params): void
    {
        Auth::require();
        Csrf::verify();

        $id    = (int) $params['id'];
        $cobro = $this->cobroOr404($id);

        if ($cobro['estado'] === 'cancelado') {
            flash('error', 'Este cobro ya está cancelado.');
            redirect('cobros/' . $id);
            return;
        }

        $this->cobros->update($id, ['estado' => 'cancelado']);
        Auditoria::log('cobro.cancelar', 'cobro', $id);
        flash('success', 'Cobro cancelado.');
        redirect('cobros/' . $id);
    }

    /** POST /cobros/{id}/eliminar — solo médico / admin */
    public function destroy(array $params): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        Csrf::verify();

        $id    = (int) $params['id'];
        $cobro = $this->cobroOr404($id);

        $this->cobros->delete($id);
        Auditoria::log('cobro.eliminar', 'cobro', $id);
        flash('success', 'Cobro eliminado.');
        redirect('cobros?fecha=' . $cobro['fecha_cobro']);
    }

    // ---- internos ----

    private function cobroOr404(int $id): array
    {
        $c = $this->cobros->conDetalle($id);
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
            'paciente_id', 'cita_id', 'consulta_id', 'fecha_cobro',
            'concepto', 'monto', 'metodo_pago', 'estado', 'notas',
        ];
        $data = [];
        foreach ($campos as $c) {
            $val      = trim((string) ($_POST[$c] ?? ''));
            $data[$c] = ($val === '') ? null : $val;
        }
        $data['paciente_id'] = !empty($data['paciente_id']) ? (int) $data['paciente_id'] : null;
        $data['cita_id']     = !empty($data['cita_id'])     ? (int) $data['cita_id']     : null;
        $data['consulta_id'] = !empty($data['consulta_id']) ? (int) $data['consulta_id'] : null;
        $data['monto']       = isset($data['monto']) ? (float) str_replace(',', '', $data['monto']) : 0.0;
        $data['estado']      = $data['estado'] ?? 'pagado';
        $data['metodo_pago'] = $data['metodo_pago'] ?? 'efectivo';
        return $data;
    }

    private function validar(array $d): array
    {
        $e = [];
        if (empty($d['paciente_id'])) {
            $e['paciente_id'] = 'Selecciona un paciente.';
        } elseif ($this->pacientes->find((int) $d['paciente_id']) === null) {
            $e['paciente_id'] = 'El paciente seleccionado no existe.';
        }
        if (empty($d['concepto'])) {
            $e['concepto'] = 'El concepto es obligatorio.';
        }
        if (empty($d['fecha_cobro'])) {
            $e['fecha_cobro'] = 'La fecha es obligatoria.';
        } elseif (!\DateTime::createFromFormat('Y-m-d', $d['fecha_cobro'])) {
            $e['fecha_cobro'] = 'Fecha no válida.';
        }
        if (!isset($d['monto']) || $d['monto'] < 0) {
            $e['monto'] = 'El monto debe ser mayor o igual a cero.';
        }
        return $e;
    }

    private function fechaValida(string $fecha): string
    {
        if ($fecha !== '' && \DateTime::createFromFormat('Y-m-d', $fecha)) {
            return $fecha;
        }
        return date('Y-m-d');
    }
}
