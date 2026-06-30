<?php
/** @var array<string,mixed> $cobro */
/** @var bool $puedeEliminar */
$pid = (int) $cobro['paciente_id'];
$nombrePac = e(trim($cobro['pac_nombre'] . ' ' . $cobro['pac_ap'] . ' ' . ($cobro['pac_am'] ?? '')));
$cancelado = $cobro['estado'] === 'cancelado';
?>
<div class="container-fluid" style="max-width:720px">

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= url('cobros?fecha=' . $cobro['fecha_cobro']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <span class="fw-semibold"><?= $nombrePac ?></span>
    <div class="ms-auto d-flex gap-2">
        <?php if (!$cancelado): ?>
            <a href="<?= url('cobros/' . $cobro['id'] . '/editar') ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
        <?php endif ?>
        <?php if ($puedeEliminar): ?>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminar">
                <i class="bi bi-trash"></i>
            </button>
        <?php endif ?>
    </div>
</div>

<?php if ($ok = get_flash('success')): ?>
    <div class="alert alert-success"><?= e($ok) ?></div>
<?php endif ?>
<?php if ($err = get_flash('error')): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
<?php endif ?>

<!-- Tarjeta principal -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-6">
                <div class="text-muted small">Paciente</div>
                <a href="<?= url('pacientes/' . $pid) ?>" class="fw-semibold fs-6"><?= $nombrePac ?></a>
            </div>
            <div class="col-sm-3">
                <div class="text-muted small">Fecha</div>
                <div><?= e(fecha_legible($cobro['fecha_cobro'])) ?></div>
            </div>
            <div class="col-sm-3">
                <div class="text-muted small">Estado</div>
                <div><?= estado_cobro_badge($cobro['estado']) ?></div>
            </div>

            <div class="col-12">
                <div class="text-muted small">Concepto</div>
                <div class="fw-semibold"><?= e($cobro['concepto']) ?></div>
            </div>

            <div class="col-sm-4">
                <div class="text-muted small">Monto</div>
                <div class="fs-5 fw-bold text-success"><?= e(formato_moneda($cobro['monto'])) ?></div>
            </div>
            <div class="col-sm-4">
                <div class="text-muted small">Método de pago</div>
                <div>
                    <i class="bi <?= e(metodo_pago_icon($cobro['metodo_pago'])) ?>"></i>
                    <?= e(metodo_pago_label($cobro['metodo_pago'])) ?>
                </div>
            </div>

            <?php if ($cobro['notas']): ?>
            <div class="col-12">
                <div class="text-muted small">Notas</div>
                <div class="text-muted" style="white-space:pre-wrap"><?= e($cobro['notas']) ?></div>
            </div>
            <?php endif ?>

            <?php if ($cobro['cita_id']): ?>
            <div class="col-sm-6">
                <div class="text-muted small">Cita vinculada</div>
                <a href="<?= url('agenda/' . $cobro['cita_id']) ?>">Ver cita #<?= (int)$cobro['cita_id'] ?></a>
            </div>
            <?php endif ?>
            <?php if ($cobro['consulta_id']): ?>
            <div class="col-sm-6">
                <div class="text-muted small">Consulta vinculada</div>
                <a href="<?= url('consultas/' . $cobro['consulta_id']) ?>">Ver consulta #<?= (int)$cobro['consulta_id'] ?></a>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>

<!-- Acciones -->
<?php if (!$cancelado): ?>
<div class="d-flex gap-2 mb-3">
    <form method="POST" action="<?= url('cobros/' . $cobro['id'] . '/cancelar') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-warning"
                onclick="return confirm('¿Cancelar este cobro?')">
            <i class="bi bi-x-circle"></i> Cancelar cobro
        </button>
    </form>
    <a href="<?= url('cobros/nuevo?paciente_id=' . $pid) ?>" class="btn btn-outline-success">
        <i class="bi bi-plus-circle"></i> Nuevo cobro (mismo paciente)
    </a>
</div>
<?php endif ?>

<!-- Links contextuales -->
<div class="d-flex gap-2 text-muted flex-wrap" style="font-size:0.85rem">
    <a href="<?= url('cobros?paciente_id=' . $pid) ?>">Ver todos los cobros del paciente</a>
    <span>·</span>
    <a href="<?= url('pacientes/' . $pid) ?>">Ficha del paciente</a>
    <?php if (\App\Core\Auth::puedeVerClinico()): ?>
    <span>·</span>
    <a href="<?= url('consultas?paciente_id=' . $pid) ?>">Historial clínico</a>
    <?php endif ?>
</div>

</div>

<?php if ($puedeEliminar): ?>
<!-- Modal eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Eliminar cobro</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Eliminar permanentemente el cobro <strong><?= e($cobro['concepto']) ?></strong>
                de <?= e(formato_moneda($cobro['monto'])) ?>?</p>
                <p class="text-danger mb-0">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="<?= url('cobros/' . $cobro['id'] . '/eliminar') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif ?>
