<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Metrica;

final class MetricasController extends Controller
{
    private Metrica $m;

    public function __construct()
    {
        $this->m = new Metrica();
    }

    /** GET /metricas[?desde=YYYY-MM-DD&hasta=YYYY-MM-DD] */
    public function index(): void
    {
        Auth::require();
        if (!Auth::is('admin_clinica') && !Auth::esPropietario()) {
            http_response_code(403);
            view('errors/403');
            return;
        }

        $desde = $this->fechaValida($this->input('desde', ''), date('Y-m-01'));
        $hasta = $this->fechaValida($this->input('hasta', ''), date('Y-m-d'));

        // Garantiza desde <= hasta
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        // Series de 12 meses (rellenas con ceros para que no falten columnas)
        $ingresosMes  = $this->rellenar12Meses($this->m->ingresosPorMes(12));
        $pacientesMes = $this->rellenar12Meses($this->m->pacientesNuevosPorMes(12));

        $this->render('metricas/index', [
            'pageTitle'      => 'Métricas',
            'desde'          => $desde,
            'hasta'          => $hasta,
            // KPIs
            'totalPacientes' => $this->m->totalPacientes(),
            'totalCitas'     => $this->m->totalCitas($desde, $hasta),
            'totalConsultas' => $this->m->totalConsultas($desde, $hasta),
            'totalIngresos'  => $this->m->totalIngresos($desde, $hasta),
            // Charts
            'ingresosMes'    => $ingresosMes,
            'citasEstado'    => $this->m->citasPorEstado($desde, $hasta),
            'pacientesMes'   => $pacientesMes,
            // Tablas
            'topConceptos'   => $this->m->topConceptos($desde, $hasta),
            'porMetodo'      => $this->m->cobrosPorMetodo($desde, $hasta),
        ]);
    }

    // ── internos ──────────────────────────────────────────────────────────

    private function fechaValida(string $fecha, string $default): string
    {
        if ($fecha !== '' && \DateTime::createFromFormat('Y-m-d', $fecha) !== false) {
            return $fecha;
        }
        return $default;
    }

    /**
     * Garantiza que haya una entrada por cada uno de los últimos 12 meses.
     * Los meses sin datos quedan con total = 0.
     *
     * @param  array<int,array<string,mixed>> $rows  Filas con clave 'mes' (YYYY-MM) y 'total'
     * @return array<int,array<string,mixed>>
     */
    private function rellenar12Meses(array $rows): array
    {
        $mapa = [];
        foreach ($rows as $row) {
            $mapa[$row['mes']] = $row['total'];
        }

        $resultado = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = (new \DateTime("first day of -{$i} month"))->format('Y-m');
            $resultado[] = ['mes' => $key, 'total' => $mapa[$key] ?? '0'];
        }
        return $resultado;
    }
}
