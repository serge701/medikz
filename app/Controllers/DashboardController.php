<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Clinica;
use App\Models\Paciente;
use App\Models\Cita;
use App\Models\Cobro;
use App\Models\Consulta;

final class DashboardController extends Controller
{
    /** GET / y GET /dashboard */
    public function index(): void
    {
        Auth::require();

        $hoy       = date('Y-m-d');
        $mesInicio = date('Y-m-01');
        $puedeClinico = Auth::puedeVerClinico();

        $cobros = new Cobro();

        // KPIs
        $totalPacientes = (new Paciente())->contar();
        $citasHoyLista  = (new Cita())->porFecha($hoy);
        $ingresosHoy    = $cobros->totalesPorFecha($hoy);
        $ingresosMes    = $cobros->totalEnPeriodo($mesInicio, $hoy);

        // Breakdown de citas de hoy por estado
        $citasHoyTotal    = count($citasHoyLista);
        $citasAtendidas   = 0;
        $citasPendientes  = 0;
        foreach ($citasHoyLista as $c) {
            if ($c['estado'] === 'atendida') {
                $citasAtendidas++;
            } elseif (in_array($c['estado'], ['programada', 'confirmada'], true)) {
                $citasPendientes++;
            }
        }

        // Consultas del mes (solo para roles clínicos)
        $consultasMes = $puedeClinico
            ? (new Consulta())->contarEnPeriodo($mesInicio, $hoy)
            : null;

        $this->render('dashboard/index', [
            'clinica'          => (new Clinica())->findActual(),
            'puedeClinico'     => $puedeClinico,
            'hoy'              => $hoy,
            'mesInicio'        => $mesInicio,
            // KPIs
            'totalPacientes'   => $totalPacientes,
            'citasHoyLista'    => $citasHoyLista,
            'citasHoyTotal'    => $citasHoyTotal,
            'citasAtendidas'   => $citasAtendidas,
            'citasPendientes'  => $citasPendientes,
            'ingresosHoy'      => $ingresosHoy,
            'ingresosMes'      => $ingresosMes,
            'consultasMes'     => $consultasMes,
        ]);
    }
}
