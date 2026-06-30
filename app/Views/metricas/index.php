<?php
/** @var string $desde */
/** @var string $hasta */
/** @var int    $totalPacientes */
/** @var int    $totalCitas */
/** @var int    $totalConsultas */
/** @var float  $totalIngresos */
/** @var array<int,array<string,mixed>> $ingresosMes */
/** @var array<int,array<string,mixed>> $citasEstado */
/** @var array<int,array<string,mixed>> $pacientesMes */
/** @var array<int,array<string,mixed>> $topConceptos */
/** @var array<int,array<string,mixed>> $porMetodo */

// ── Preparar datos para las gráficas ────────────────────────────────────
$mesesNombres = [
    '01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr',
    '05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago',
    '09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic',
];
$mesLabel = static function (string $ym) use ($mesesNombres): string {
    [$y, $m] = explode('-', $ym);
    return ($mesesNombres[$m] ?? $m) . ' ' . substr($y, 2);
};

$ingresosLabels = array_map(fn($r) => $mesLabel($r['mes']), $ingresosMes);
$ingresosValues = array_map(fn($r) => (float) $r['total'],  $ingresosMes);

$pacientesLabels = array_map(fn($r) => $mesLabel($r['mes']), $pacientesMes);
$pacientesValues = array_map(fn($r) => (int)   $r['total'],  $pacientesMes);

$estadoColorMap = [
    'programada' => '#6c757d',
    'confirmada' => '#0d6efd',
    'atendida'   => '#198754',
    'cancelada'  => '#dc3545',
    'no_asistio' => '#ffc107',
];
$citasLabels = [];
$citasValues = [];
$citasColors = [];
foreach ($citasEstado as $row) {
    $citasLabels[] = estado_cita_label($row['estado']);
    $citasValues[] = (int) $row['total'];
    $citasColors[] = $estadoColorMap[$row['estado']] ?? '#adb5bd';
}

$jFlags = JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
?>

<!-- ── Filtro de periodo ─────────────────────────────────────────────── -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= url('metricas') ?>"
              class="d-flex align-items-center gap-2 flex-wrap">
            <span class="text-muted small fw-semibold">Periodo:</span>
            <input type="date" name="desde" value="<?= e($desde) ?>"
                   class="form-control form-control-sm" style="width:auto">
            <span class="text-muted">al</span>
            <input type="date" name="hasta" value="<?= e($hasta) ?>"
                   class="form-control form-control-sm" style="width:auto">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
            <!-- Accesos rápidos -->
            <?php
            $hoy      = date('Y-m-d');
            $mesIni   = date('Y-m-01');
            $mesAntIni = date('Y-m-01', strtotime('first day of last month'));
            $mesAntFin = date('Y-m-t',  strtotime('last day of last month'));
            $anioIni  = date('Y-01-01');
            ?>
            <div class="ms-auto d-flex gap-1 flex-wrap">
                <a href="<?= url("metricas?desde={$mesIni}&hasta={$hoy}") ?>"
                   class="btn btn-outline-secondary btn-sm">Este mes</a>
                <a href="<?= url("metricas?desde={$mesAntIni}&hasta={$mesAntFin}") ?>"
                   class="btn btn-outline-secondary btn-sm">Mes anterior</a>
                <a href="<?= url("metricas?desde={$anioIni}&hasta={$hoy}") ?>"
                   class="btn btn-outline-secondary btn-sm">Este año</a>
            </div>
        </form>
    </div>
</div>

<!-- ── KPI cards ─────────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3><?= e((string) $totalPacientes) ?></h3>
                <p>Pacientes activos</p>
            </div>
            <i class="small-box-icon bi bi-people"></i>
            <a href="<?= url('pacientes') ?>" class="small-box-footer">
                Ver pacientes <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?= e((string) $totalCitas) ?></h3>
                <p>Citas en el periodo</p>
            </div>
            <i class="small-box-icon bi bi-calendar-check"></i>
            <a href="<?= url('agenda') ?>" class="small-box-footer">
                Ver agenda <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?= e((string) $totalConsultas) ?></h3>
                <p>Consultas en el periodo</p>
            </div>
            <i class="small-box-icon bi bi-clipboard2-pulse"></i>
            <a href="<?= url('consultas') ?>" class="small-box-footer">
                Ver consultas <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3><?= e(formato_moneda($totalIngresos)) ?></h3>
                <p>Ingresos en el periodo</p>
            </div>
            <i class="small-box-icon bi bi-cash-coin"></i>
            <a href="<?= url('cobros') ?>" class="small-box-footer">
                Ver cobros <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>
    </div>
</div>

<!-- ── Gráficas fila 1: Ingresos + Citas por estado ─────────────────── -->
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <i class="bi bi-bar-chart-line me-1"></i>
                Ingresos por mes <small class="text-muted">(últimos 12 meses · solo pagados)</small>
            </div>
            <div class="card-body">
                <canvas id="chartIngresos" style="max-height:280px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <i class="bi bi-pie-chart me-1"></i>
                Citas por estado
                <small class="text-muted">(periodo seleccionado)</small>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <?php if (empty($citasEstado)): ?>
                <p class="text-muted text-center mb-0">Sin datos en el periodo.</p>
                <?php else: ?>
                <canvas id="chartCitasEstado" style="max-height:260px"></canvas>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Gráficas fila 2: Pacientes nuevos + Métodos de pago ───────────── -->
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <i class="bi bi-graph-up-arrow me-1"></i>
                Pacientes nuevos por mes <small class="text-muted">(últimos 12 meses)</small>
            </div>
            <div class="card-body">
                <canvas id="chartPacientes" style="max-height:260px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <i class="bi bi-credit-card me-1"></i>
                Métodos de pago
                <small class="text-muted">(periodo seleccionado)</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($porMetodo)): ?>
                <p class="text-muted text-center py-5 mb-0">Sin cobros en el periodo.</p>
                <?php else: ?>
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Método</th>
                            <th class="text-center">Veces</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($porMetodo as $pm): ?>
                        <tr>
                            <td>
                                <i class="bi <?= e(metodo_pago_icon($pm['metodo_pago'])) ?> me-1"></i>
                                <?= e(metodo_pago_label($pm['metodo_pago'])) ?>
                            </td>
                            <td class="text-center"><?= e((string) $pm['veces']) ?></td>
                            <td class="text-end fw-semibold"><?= e(formato_moneda((float) $pm['subtotal'])) ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Top conceptos de cobro ─────────────────────────────────────────── -->
<div class="card shadow-sm mb-3">
    <div class="card-header">
        <i class="bi bi-trophy me-1"></i>
        Top conceptos de cobro
        <small class="text-muted">(periodo seleccionado · por monto total pagado)</small>
    </div>
    <?php if (empty($topConceptos)): ?>
    <div class="card-body text-muted text-center py-4">
        No hay cobros pagados en el periodo seleccionado.
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Concepto</th>
                    <th class="text-center">Veces</th>
                    <th class="text-end">Monto total</th>
                    <th class="text-end">Ticket promedio</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($topConceptos as $i => $tc): ?>
                <?php $promedio = (int) $tc['veces'] > 0
                    ? (float) $tc['monto_total'] / (int) $tc['veces']
                    : 0.0; ?>
                <tr>
                    <td class="text-muted"><?= e((string) ($i + 1)) ?></td>
                    <td><?= e($tc['concepto']) ?></td>
                    <td class="text-center"><?= e((string) $tc['veces']) ?></td>
                    <td class="text-end fw-semibold"><?= e(formato_moneda((float) $tc['monto_total'])) ?></td>
                    <td class="text-end text-muted"><?= e(formato_moneda($promedio)) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>
</div>

<!-- ── Chart.js ────────────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    // ── Datos desde PHP ──────────────────────────────────────────────────
    const ingresosLabels  = <?= json_encode($ingresosLabels,  $jFlags) ?>;
    const ingresosValues  = <?= json_encode($ingresosValues,  $jFlags) ?>;
    const pacientesLabels = <?= json_encode($pacientesLabels, $jFlags) ?>;
    const pacientesValues = <?= json_encode($pacientesValues, $jFlags) ?>;
    const citasLabels     = <?= json_encode($citasLabels,     $jFlags) ?>;
    const citasValues     = <?= json_encode($citasValues,     $jFlags) ?>;
    const citasColors     = <?= json_encode($citasColors,     $jFlags) ?>;

    const gridColor = 'rgba(0,0,0,0.05)';

    // ── Gráfica 1: Ingresos por mes ─────────────────────────────────────
    new Chart(document.getElementById('chartIngresos'), {
        type: 'bar',
        data: {
            labels: ingresosLabels,
            datasets: [{
                label: 'Ingresos ($)',
                data: ingresosValues,
                backgroundColor: 'rgba(13,110,253,0.7)',
                borderColor: 'rgba(13,110,253,1)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' $' + ctx.parsed.y.toLocaleString('es-MX', {minimumFractionDigits:2})
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: {
                        callback: v => '$' + Number(v).toLocaleString('es-MX')
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });

    // ── Gráfica 2: Citas por estado (doughnut) ───────────────────────────
    const ctxEstado = document.getElementById('chartCitasEstado');
    if (ctxEstado) {
        new Chart(ctxEstado, {
            type: 'doughnut',
            data: {
                labels: citasLabels,
                datasets: [{
                    data: citasValues,
                    backgroundColor: citasColors,
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 12 } }
                },
                cutout: '60%'
            }
        });
    }

    // ── Gráfica 3: Pacientes nuevos por mes (línea) ───────────────────────
    new Chart(document.getElementById('chartPacientes'), {
        type: 'line',
        data: {
            labels: pacientesLabels,
            datasets: [{
                label: 'Pacientes nuevos',
                data: pacientesValues,
                borderColor: '#20c997',
                backgroundColor: 'rgba(32,201,151,0.12)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#20c997',
                fill: true,
                tension: 0.3,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { precision: 0 }
                },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
