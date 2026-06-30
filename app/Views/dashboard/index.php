<?php
/** @var array<string,mixed>|null $clinica */
/** @var bool   $puedeClinico */
/** @var string $hoy */
/** @var string $mesInicio */
/** @var int    $totalPacientes */
/** @var array<int,array<string,mixed>> $citasHoyLista */
/** @var int    $citasHoyTotal */
/** @var int    $citasAtendidas */
/** @var int    $citasPendientes */
/** @var array{total:float,por_metodo:array<string,float>} $ingresosHoy */
/** @var float  $ingresosMes */
/** @var int|null $consultasMes */

$pageTitle = 'Inicio';

// Fecha bonita para el encabezado
$fechaObj = new DateTime($hoy);
$dias   = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$meses  = ['','enero','febrero','marzo','abril','mayo','junio',
           'julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fechaTexto = ucfirst($dias[(int)$fechaObj->format('w')]) . ' '
            . (int)$fechaObj->format('j') . ' de '
            . $meses[(int)$fechaObj->format('n')];

$nombreMes = $meses[(int)$fechaObj->format('n')];
?>

<!-- ── Bienvenida ─────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0 fw-semibold">
            Hola, <?= e(auth()['nombre']) ?>
        </h5>
        <small class="text-muted">
            <?= e($fechaTexto) ?>
            &nbsp;·&nbsp;<?= e($clinica['nombre'] ?? 'Mi clínica') ?>
        </small>
    </div>
    <span class="badge text-bg-primary fs-6 px-3 py-2"><?= e(auth()['rol']) ?></span>
</div>

<!-- ── KPIs ───────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">

    <!-- Pacientes -->
    <div class="col-6 col-lg-3">
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

    <!-- Citas hoy -->
    <div class="col-6 col-lg-3">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?= e((string) $citasHoyTotal) ?></h3>
                <p>Citas hoy</p>
                <?php if ($citasHoyTotal > 0): ?>
                <small class="opacity-75">
                    <?= e((string) $citasAtendidas) ?> atendidas
                    · <?= e((string) $citasPendientes) ?> pendientes
                </small>
                <?php endif ?>
            </div>
            <i class="small-box-icon bi bi-calendar-check"></i>
            <a href="<?= url('agenda?fecha=' . $hoy) ?>" class="small-box-footer">
                Ver agenda <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>
    </div>

    <!-- Ingresos hoy -->
    <div class="col-6 col-lg-3">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?= e(formato_moneda($ingresosHoy['total'])) ?></h3>
                <p>Ingresos hoy</p>
                <?php if (!empty($ingresosHoy['por_metodo'])): ?>
                <small class="opacity-75">
                    <?= e(implode(' · ', array_map(
                        fn($m, $v) => metodo_pago_label($m) . ' ' . formato_moneda($v),
                        array_keys($ingresosHoy['por_metodo']),
                        $ingresosHoy['por_metodo']
                    ))) ?>
                </small>
                <?php endif ?>
            </div>
            <i class="small-box-icon bi bi-cash-coin"></i>
            <a href="<?= url('cobros?fecha=' . $hoy) ?>" class="small-box-footer">
                Ver cobros <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>
    </div>

    <!-- Ingresos del mes -->
    <div class="col-6 col-lg-3">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3><?= e(formato_moneda($ingresosMes)) ?></h3>
                <p>Ingresos de <?= e($nombreMes) ?></p>
                <?php if ($consultasMes !== null): ?>
                <small class="opacity-75">
                    <?= e((string) $consultasMes) ?> consulta<?= $consultasMes !== 1 ? 's' : '' ?> este mes
                </small>
                <?php endif ?>
            </div>
            <i class="small-box-icon bi bi-graph-up"></i>
            <a href="<?= url('cobros?desde=' . $mesInicio . '&hasta=' . $hoy) ?>" class="small-box-footer">
                Ver detalle <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>
    </div>

</div>

<!-- ── Contenido principal ────────────────────────────────────────────── -->
<div class="row g-3">

    <!-- Agenda de hoy -->
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>
                    <i class="bi bi-calendar-week me-1"></i>
                    Agenda de hoy
                </span>
                <div class="d-flex gap-1">
                    <a href="<?= url('agenda/nueva') ?>" class="btn btn-sm btn-success">
                        <i class="bi bi-plus-circle"></i> Nueva cita
                    </a>
                    <a href="<?= url('agenda?fecha=' . $hoy) ?>" class="btn btn-sm btn-outline-secondary">
                        Ver todo
                    </a>
                </div>
            </div>

            <?php if (empty($citasHoyLista)): ?>
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-50"></i>
                No hay citas programadas para hoy.
                <div class="mt-3">
                    <a href="<?= url('agenda/nueva') ?>" class="btn btn-sm btn-success">
                        <i class="bi bi-plus-circle"></i> Agregar cita
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:70px">Hora</th>
                            <th>Paciente</th>
                            <?php if (\App\Core\Auth::is('admin_clinica') || \App\Core\Auth::esPropietario()): ?>
                            <th class="d-none d-md-table-cell">Médico</th>
                            <?php endif ?>
                            <th>Estado</th>
                            <th style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($citasHoyLista as $cita): ?>
                        <?php
                        $esProxima = in_array($cita['estado'], ['programada','confirmada'], true);
                        $rowClass  = $cita['estado'] === 'atendida' ? 'text-muted' : '';
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="fw-semibold text-nowrap">
                                <?= e(hora_legible($cita['hora_inicio'])) ?>
                            </td>
                            <td>
                                <a href="<?= url('pacientes/' . (int)$cita['paciente_id']) ?>"
                                   class="text-decoration-none fw-medium">
                                    <?= e($cita['pac_nombre'] . ' ' . $cita['pac_ap'] . ' ' . ($cita['pac_am'] ?? '')) ?>
                                </a>
                                <?php if (!empty($cita['motivo'])): ?>
                                <div class="text-muted" style="font-size:.75rem">
                                    <?= e($cita['motivo']) ?>
                                </div>
                                <?php endif ?>
                            </td>
                            <?php if (\App\Core\Auth::is('admin_clinica') || \App\Core\Auth::esPropietario()): ?>
                            <td class="d-none d-md-table-cell text-muted small">
                                <?= e($cita['med_nombre'] ?? '—') ?>
                            </td>
                            <?php endif ?>
                            <td><?= estado_cita_badge($cita['estado']) ?></td>
                            <td class="text-end">
                                <a href="<?= url('agenda/' . (int)$cita['id']) ?>"
                                   class="btn btn-sm btn-outline-primary py-0 px-2">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Panel lateral: cobros + accesos rápidos -->
    <div class="col-lg-5 d-flex flex-column gap-3">

        <!-- Resumen de cobros de hoy -->
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-receipt me-1"></i> Cobros de hoy</span>
                <a href="<?= url('cobros/nuevo') ?>" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-circle"></i> Nuevo cobro
                </a>
            </div>
            <?php if ($ingresosHoy['total'] <= 0 && empty($ingresosHoy['por_metodo'])): ?>
            <div class="card-body text-center py-4 text-muted">
                <i class="bi bi-receipt fs-2 d-block mb-2 opacity-40"></i>
                Sin cobros registrados hoy.
            </div>
            <?php else: ?>
            <div class="card-body pb-0">
                <div class="text-center mb-3">
                    <div class="text-muted small">Total del día</div>
                    <div class="display-6 fw-bold text-success">
                        <?= e(formato_moneda($ingresosHoy['total'])) ?>
                    </div>
                    <div class="text-muted" style="font-size:.72rem">solo cobros pagados</div>
                </div>
            </div>
            <?php if (!empty($ingresosHoy['por_metodo'])): ?>
            <table class="table table-sm mb-0 border-top">
                <tbody>
                <?php foreach ($ingresosHoy['por_metodo'] as $metodo => $subtotal): ?>
                    <tr>
                        <td class="ps-3 text-muted">
                            <i class="bi <?= e(metodo_pago_icon($metodo)) ?> me-1"></i>
                            <?= e(metodo_pago_label($metodo)) ?>
                        </td>
                        <td class="text-end pe-3 fw-semibold">
                            <?= e(formato_moneda($subtotal)) ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
            <?php endif ?>
            <div class="card-footer text-end py-2">
                <a href="<?= url('cobros?fecha=' . $hoy) ?>" class="btn btn-sm btn-outline-primary">
                    Ver todos los cobros de hoy
                </a>
            </div>
            <?php endif ?>
        </div>

        <!-- Accesos rápidos -->
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="bi bi-lightning-charge me-1"></i> Accesos rápidos
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-2">
                    <a href="<?= url('pacientes/nuevo') ?>"
                       class="btn btn-outline-primary text-start">
                        <i class="bi bi-person-plus me-2"></i> Registrar nuevo paciente
                    </a>
                    <a href="<?= url('agenda/nueva') ?>"
                       class="btn btn-outline-success text-start">
                        <i class="bi bi-calendar-plus me-2"></i> Agendar cita
                    </a>
                    <a href="<?= url('cobros/nuevo') ?>"
                       class="btn btn-outline-warning text-start">
                        <i class="bi bi-cash me-2"></i> Registrar cobro
                    </a>
                    <?php if ($puedeClinico): ?>
                    <a href="<?= url('consultas/nueva') ?>"
                       class="btn btn-outline-info text-start">
                        <i class="bi bi-clipboard2-plus me-2"></i> Nueva consulta
                    </a>
                    <?php endif ?>
                    <?php if (\App\Core\Auth::is('admin_clinica') || \App\Core\Auth::esPropietario()): ?>
                    <a href="<?= url('metricas') ?>"
                       class="btn btn-outline-secondary text-start">
                        <i class="bi bi-graph-up me-2"></i> Ver métricas del negocio
                    </a>
                    <?php endif ?>
                </div>
            </div>
        </div>

    </div>
</div>
