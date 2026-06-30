<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Auditoria;
use App\Models\Receta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Clinica;
use App\Services\RecetaPdf;

final class RecetaController extends Controller
{
    private Receta   $recetas;
    private Medico   $medicos;
    private Paciente $pacientes;

    public function __construct()
    {
        $this->recetas   = new Receta();
        $this->medicos   = new Medico();
        $this->pacientes = new Paciente();
    }

    /** GET /recetas[?paciente_id=X] */
    public function index(): void
    {
        Auth::requireRole('medico', 'admin_clinica');

        $pacienteId = (int) ($_GET['paciente_id'] ?? 0);
        $paciente   = $pacienteId > 0 ? $this->pacientes->find($pacienteId) : null;
        $recetas    = $this->recetas->recientes(60, $pacienteId ?: null);

        $this->render('recetas/index', [
            'pageTitle' => 'Recetas',
            'recetas'   => $recetas,
            'paciente'  => $paciente,
        ]);
    }

    /** GET /recetas/nueva[?paciente_id=X&consulta_id=Y] */
    public function create(): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        clear_old();

        $pacienteId = (int) ($_GET['paciente_id'] ?? 0);
        $consultaId = (int) ($_GET['consulta_id'] ?? 0);
        $paciente   = $pacienteId > 0 ? $this->pacientes->find($pacienteId) : null;

        $this->render('recetas/form', [
            'pageTitle'  => 'Nueva receta',
            'receta'     => null,
            'paciente'   => $paciente,
            'medicos'    => $this->medicos->activos(),
            'consultaId' => $consultaId ?: null,
            'errores'    => [],
        ]);
    }

    /** POST /recetas */
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
            $this->render('recetas/form', [
                'pageTitle'  => 'Nueva receta',
                'receta'     => null,
                'paciente'   => $paciente,
                'medicos'    => $this->medicos->activos(),
                'consultaId' => $data['consulta_id'],
                'errores'    => $errores,
            ]);
            return;
        }

        $data['codigo_verificacion'] = $this->recetas->generarCodigo();
        $data['creado_por']          = Auth::id();
        $id = $this->recetas->create($data);

        Auditoria::log('receta.crear', 'receta', $id, ['paciente_id' => $data['paciente_id']]);
        clear_old();
        flash('success', 'Receta generada correctamente.');
        redirect('recetas/' . $id);
    }

    /** GET /recetas/{id} */
    public function show(array $params): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        $receta = $this->recetaOr404((int) $params['id']);

        Auditoria::log('receta.ver', 'receta', (int) $receta['id']);

        $this->render('recetas/show', [
            'pageTitle' => 'Receta médica',
            'receta'    => $receta,
        ]);
    }

    /** GET /recetas/{id}/editar */
    public function edit(array $params): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        $receta   = $this->recetaOr404((int) $params['id']);
        $paciente = $this->pacientes->find((int) $receta['paciente_id']);
        clear_old();

        $this->render('recetas/form', [
            'pageTitle'  => 'Editar receta',
            'receta'     => $receta,
            'paciente'   => $paciente,
            'medicos'    => $this->medicos->activos(),
            'consultaId' => $receta['consulta_id'] ? (int) $receta['consulta_id'] : null,
            'errores'    => [],
        ]);
    }

    /** POST /recetas/{id} */
    public function update(array $params): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        Csrf::verify();

        $id     = (int) $params['id'];
        $receta = $this->recetaOr404($id);
        $data   = $this->datosDesdePost();
        $errores = $this->validar($data);

        if ($errores !== []) {
            set_old($_POST);
            $paciente = $this->pacientes->find((int) $receta['paciente_id']);
            $this->render('recetas/form', [
                'pageTitle'  => 'Editar receta',
                'receta'     => $receta,
                'paciente'   => $paciente,
                'medicos'    => $this->medicos->activos(),
                'consultaId' => $data['consulta_id'],
                'errores'    => $errores,
            ]);
            return;
        }

        $this->recetas->update($id, $data);
        Auditoria::log('receta.editar', 'receta', $id);
        clear_old();
        flash('success', 'Receta actualizada.');
        redirect('recetas/' . $id);
    }

    /** POST /recetas/{id}/eliminar */
    public function destroy(array $params): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        Csrf::verify();

        $id     = (int) $params['id'];
        $receta = $this->recetaOr404($id);

        $this->recetas->delete($id);
        Auditoria::log('receta.eliminar', 'receta', $id);
        flash('success', 'Receta eliminada.');
        redirect('recetas?paciente_id=' . $receta['paciente_id']);
    }

    /** GET /recetas/{id}/pdf — genera y sirve el PDF */
    public function pdf(array $params): void
    {
        Auth::requireRole('medico', 'admin_clinica');

        $receta  = $this->recetaOr404((int) $params['id']);
        $clinica = (new Clinica())->findActual() ?? [];

        $servicio = new RecetaPdf();
        $bytes    = $servicio->generar($receta, $clinica);
        $nombre   = 'receta-' . $receta['codigo_verificacion'] . '.pdf';

        Auditoria::log('receta.pdf', 'receta', (int) $receta['id']);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nombre . '"');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
    }

    /** GET /recetas/verificar/{codigo} — página pública de verificación */
    public function verificar(array $params): void
    {
        $codigo = trim($params['codigo'] ?? '');
        $receta = ($codigo !== '') ? $this->recetas->porCodigo($codigo) : null;

        view('recetas/verificar', [
            'receta' => $receta,
            'codigo' => $codigo,
        ]);
    }

    // ---- internos ----

    private function recetaOr404(int $id): array
    {
        $r = $this->recetas->conDetalle($id);
        if ($r === null) {
            http_response_code(404);
            view('errors/404');
            exit;
        }
        return $r;
    }

    private function datosDesdePost(): array
    {
        $campos = [
            'paciente_id', 'medico_id', 'consulta_id', 'fecha_receta',
            'diagnostico', 'indicaciones_generales',
        ];
        $data = [];
        foreach ($campos as $c) {
            $val      = trim((string) ($_POST[$c] ?? ''));
            $data[$c] = ($val === '') ? null : $val;
        }
        $data['paciente_id'] = !empty($data['paciente_id']) ? (int) $data['paciente_id'] : null;
        $data['medico_id']   = !empty($data['medico_id'])   ? (int) $data['medico_id']   : null;
        $data['consulta_id'] = !empty($data['consulta_id']) ? (int) $data['consulta_id'] : null;

        // Medicamentos vienen como JSON del campo oculto generado por JS.
        $jsonRaw = trim((string) ($_POST['medicamentos_json'] ?? ''));
        $meds    = [];
        if ($jsonRaw !== '') {
            $decoded = json_decode($jsonRaw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $m) {
                    if (!empty($m['nombre'])) {
                        $meds[] = [
                            'nombre'       => trim($m['nombre']),
                            'dosis'        => trim($m['dosis'] ?? ''),
                            'frecuencia'   => trim($m['frecuencia'] ?? ''),
                            'duracion'     => trim($m['duracion'] ?? ''),
                            'indicaciones' => trim($m['indicaciones'] ?? ''),
                        ];
                    }
                }
            }
        }
        $data['medicamentos'] = json_encode($meds, JSON_UNESCAPED_UNICODE);

        return $data;
    }

    private function validar(array $d): array
    {
        $e = [];
        if (empty($d['paciente_id'])) {
            $e['paciente_id'] = 'Selecciona un paciente.';
        }
        if (empty($d['fecha_receta'])) {
            $e['fecha_receta'] = 'La fecha es obligatoria.';
        } elseif (!\DateTime::createFromFormat('Y-m-d', $d['fecha_receta'])) {
            $e['fecha_receta'] = 'Fecha no válida.';
        }
        $meds = json_decode($d['medicamentos'] ?? '[]', true) ?: [];
        if (empty($meds)) {
            $e['medicamentos'] = 'Agrega al menos un medicamento.';
        }
        return $e;
    }
}
