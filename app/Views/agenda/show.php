<?php
// $cita, $paciente, $puedeClinico, $puedeEditar
$nombrePac = nombre_completo($paciente ?? []);
$pendiente = in_array($cita['estado'], ['programada', 'confirmada'], true);
$cancelable = !in_array($cita['estado'], ['atendida', 'cancelada'], true);

// Calcular duración
$duracion = '';
if (!empty($cita['hora_inicio']) && !empty($cita['hora_fin'])) {
    $ini = new DateTime('1970-01-01 ' . $cita['hora_inicio']);
    $fin = new DateTime('1970-01-01 ' . $cita['hora_fin']);
    $min = ($fin->getTimestamp() - $ini->getTimestamp()) / 60;
    $duracion = $min >= 60
        ? floor($min / 60) . 'h ' . ($min % 60 > 0 ? ($min % 60) . 'min' : '')
        : $min . ' min';
}
?>

<div class="mb-3">
    <a href="<?= url('agenda?fecha=' . $cita['fecha']) ?>" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Agenda del <?= e(fecha_legible($cita['fecha'])) ?>
    </a>
</div>

<div class="row g-3">

    <!-- Columna principal -->
    <div class="col-lg-8">

        <!-- Paciente -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person"></i> Paciente</div>
            <div class="card-body d-flex align-items-center gap-3">
                <div class="flex-grow-1">
                    <a href="<?= url('pacientes/' . ($paciente['id'] ?? '')) ?>"
                       class="fw-semibold fs-6 text-decoration-none">
                        <?= e($nombrePac) ?>
                    </a>
                    <div class="text-muted small mt-1">
                        <?php if (!empty($paciente['fecha_nacimiento'])): ?>
                            <?= edad_anios($paciente['fecha_nacimiento']) ?> años
                            &nbsp;·&nbsp;
                        <?php endif; ?>
                        <?php if (!empty($paciente['sexo'])): ?>
                            <?= sexo_label($paciente['sexo']) ?>
                            &nbsp;·&nbsp;
                        <?php endif; ?>
                        <?php if (!empty($paciente['telefono'])): ?>
                            <i class="bi bi-telephone me-1"></i><?= e($paciente['telefono']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="<?= url('pacientes/' . ($paciente['id'] ?? '')) ?>"
                   class="btn btn-outline-secondary btn-sm">
                    Ver ficha
                </a>
            </div>
        </div>

        <!-- Detalles de la cita -->
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-calendar-check"></i> Cita
                <?= estado_cita_badge($cita['estado']) ?>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Fecha</dt>
                    <dd class="col-sm-8"><?= e(fecha_legible($cita['fecha'])) ?></dd>

                    <dt class="col-sm-4">Horario</dt>
                    <dd class="col-sm-8">
                        <?= e(hora_legible($cita['hora_inicio'])) ?>
                        &ndash; <?= e(hora_legible($cita['hora_fin'])) ?>
                        <?php if ($duracion): ?>
                            <span class="text-muted small">(<?= e(trim($duracion)) ?>)</span>
                        <?php endif; ?>
                    </dd>

                    <?php if (!empty($cita['med_nombre'])): ?>
                    <dt class="col-sm-4">Médico</dt>
                    <dd class="col-sm-8"><?= e($cita['med_nombre']) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($cita['motivo'])): ?>
                    <dt class="col-sm-4">Motivo</dt>
                    <dd class="col-sm-8"><?= e($cita['motivo']) ?></dd>
                    <?php endif; ?>

                    <?php if ($puedeClinico && !empty($cita['notas'])): ?>
                    <dt class="col-sm-4">Notas clínicas</dt>
                    <dd class="col-sm-8"><?= nl2br(e($cita['notas'])) ?></dd>
                    <?php endif; ?>

                    <?php if ($cita['estado'] === 'cancelada' && !empty($cita['motivo_cancelacion'])): ?>
                    <dt class="col-sm-4 text-danger">Motivo cancelación</dt>
                    <dd class="col-sm-8 text-danger"><?= e($cita['motivo_cancelacion']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

    </div>

    <!-- Columna de acciones -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Acciones</div>
            <div class="card-body d-grid gap-2">

                <?php if ($puedeEditar): ?>
                    <a href="<?= url('agenda/' . $cita['id'] . '/editar') ?>"
                       class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Editar cita
                    </a>
                <?php endif; ?>

                <?php if ($pendiente): ?>
                    <!-- Marcar como atendida -->
                    <form method="post" action="<?= url('agenda/' . $cita['id'] . '/atender') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle me-1"></i> Marcar como atendida
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($cancelable): ?>
                    <!-- Cancelar (modal) -->
                    <button type="button" class="btn btn-outline-danger"
                            data-bs-toggle="modal" data-bs-target="#modalCancelar">
                        <i class="bi bi-x-circle me-1"></i> Cancelar cita
                    </button>
                <?php endif; ?>

                <?php if (\App\Core\Auth::puedeVerClinico()): ?>
                    <?php if ($consultaExistente): ?>
                        <a href="<?= url('consultas/' . $consultaExistente['id']) ?>"
                           class="btn btn-success">
                            <i class="bi bi-clipboard2-check me-1"></i> Ver consulta registrada
                        </a>
                    <?php else: ?>
                        <a href="<?= url('consultas/nueva?paciente_id=' . ($paciente['id'] ?? '') . '&cita_id=' . $cita['id']) ?>"
                           class="btn btn-outline-success">
                            <i class="bi bi-clipboard2-plus me-1"></i> Registrar consulta
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="<?= url('cobros/nuevo?paciente_id=' . ($paciente['id'] ?? '') . '&cita_id=' . $cita['id']) ?>"
                   class="btn btn-outline-success">
                    <i class="bi bi-cash-coin me-1"></i> Registrar cobro
                </a>
                <a href="<?= url('agenda/nueva?paciente_id=' . ($paciente['id'] ?? '') . '&fecha=' . date('Y-m-d')) ?>"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-plus-lg me-1"></i> Nueva cita para este paciente
                </a>

            </div>
        </div>
    </div>

</div>

<?php if ($cancelable): ?>
<!-- Modal cancelación -->
<div class="modal fade" id="modalCancelar" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= url('agenda/' . $cita['id'] . '/cancelar') ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancelar cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Motivo de cancelación <span class="text-muted small">(opcional)</span></label>
                    <textarea name="motivo_cancelacion" class="form-control" rows="3"
                              placeholder="Paciente avisó con anticipación, etc."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn btn-danger">Confirmar cancelación</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
