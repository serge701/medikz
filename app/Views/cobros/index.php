<?php
/** @var array<int,array<string,mixed>> $cobros */
/** @var array{total:float,por_metodo:array<string,float>}|null $totales */
/** @var bool $modoPaciente */
/** @var string|null $fecha */
/** @var \DateTime|null $fechaObj */
/** @var string|null $anterior */
/** @var string|null $siguiente */
/** @var bool $esHoy */
/** @var array<string,mixed>|null $paciente */
?>
<div class="container-fluid">

<?php if ($modoPaciente && $paciente): ?>
<!-- ====== Modo historial de paciente ====== -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Cobros</h4>
        <small class="text-muted">
            Historial de cobros de
            <a href="<?= url('pacientes/' . $paciente['id']) ?>"><?= e($paciente['nombre'] . ' ' . $paciente['apellido_paterno']) ?></a>
        </small>
    </div>
    <a href="<?= url('cobros/nuevo?paciente_id=' . $paciente['id']) ?>" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Nuevo cobro
    </a>
</div>

<?php if (empty($cobros)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-receipt fs-1 d-block mb-2"></i>
    No hay cobros registrados para este paciente.
</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Concepto</th>
                    <th>Método</th>
                    <th class="text-end">Monto</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cobros as $c): ?>
                <tr>
                    <td class="text-nowrap"><?= e(fecha_legible($c['fecha_cobro'])) ?></td>
                    <td><?= e($c['concepto']) ?></td>
                    <td>
                        <i class="bi <?= e(metodo_pago_icon($c['metodo_pago'])) ?>"></i>
                        <?= e(metodo_pago_label($c['metodo_pago'])) ?>
                    </td>
                    <td class="text-end fw-semibold"><?= e(formato_moneda($c['monto'])) ?></td>
                    <td><?= estado_cobro_badge($c['estado']) ?></td>
                    <td class="text-end">
                        <a href="<?= url('cobros/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<?php else: ?>
<!-- ====== Modo vista diaria ====== -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h4 class="mb-0">Cobros</h4>
    <a href="<?= url('cobros/nuevo') ?>" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Nuevo cobro
    </a>
</div>

<!-- Navegación de fecha -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= url('cobros?fecha=' . $anterior) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-chevron-left"></i>
            </a>

            <form method="GET" action="<?= url('cobros') ?>" class="d-flex align-items-center gap-1">
                <input type="date" name="fecha" value="<?= e($fecha) ?>"
                       class="form-control form-control-sm" style="width:auto"
                       onchange="this.form.submit()">
            </form>

            <a href="<?= url('cobros?fecha=' . $siguiente) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-chevron-right"></i>
            </a>

            <?php if ($fechaObj): ?>
            <span class="fw-semibold"><?= e(fecha_dia_es($fechaObj)) ?></span>
            <?php endif ?>

            <?php if (!$esHoy): ?>
            <a href="<?= url('cobros') ?>" class="btn btn-outline-primary btn-sm ms-auto">Hoy</a>
            <?php endif ?>
        </div>
    </div>
</div>

<!-- Totales del día -->
<?php if ($totales !== null): ?>
<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 bg-success bg-opacity-10">
            <div class="card-body text-center">
                <div class="text-muted small">Total del día</div>
                <div class="fs-4 fw-bold text-success"><?= e(formato_moneda($totales['total'])) ?></div>
                <div class="text-muted" style="font-size:0.75rem">(solo cobros pagados)</div>
            </div>
        </div>
    </div>
    <?php foreach ($totales['por_metodo'] as $metodo => $subtotal): ?>
    <div class="col-6 col-md-2">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-2">
                <div class="text-muted" style="font-size:0.75rem"><?= e(metodo_pago_label($metodo)) ?></div>
                <div class="fw-semibold"><?= e(formato_moneda($subtotal)) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach ?>
</div>
<?php endif ?>

<!-- Lista de cobros -->
<?php if (empty($cobros)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-receipt fs-1 d-block mb-2"></i>
    No hay cobros registrados para este día.
    <div class="mt-2">
        <a href="<?= url('cobros/nuevo') ?>" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle"></i> Registrar primer cobro
        </a>
    </div>
</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Paciente</th>
                    <th>Concepto</th>
                    <th>Método</th>
                    <th class="text-end">Monto</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cobros as $c): ?>
                <?php
                    $nombre = e($c['pac_nombre'] . ' ' . $c['pac_ap'] . ' ' . ($c['pac_am'] ?? ''));
                    $pid    = (int) $c['paciente_id'];
                ?>
                <tr>
                    <td>
                        <a href="<?= url('pacientes/' . $pid) ?>"><?= $nombre ?></a>
                    </td>
                    <td><?= e($c['concepto']) ?></td>
                    <td class="text-nowrap">
                        <i class="bi <?= e(metodo_pago_icon($c['metodo_pago'])) ?>"></i>
                        <?= e(metodo_pago_label($c['metodo_pago'])) ?>
                    </td>
                    <td class="text-end fw-semibold"><?= e(formato_moneda($c['monto'])) ?></td>
                    <td><?= estado_cobro_badge($c['estado']) ?></td>
                    <td class="text-end">
                        <a href="<?= url('cobros/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>
<?php endif ?>

</div>
