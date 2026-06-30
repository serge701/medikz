<?php
// $consulta (con campos pac_* y med_*), tiene: id, paciente_id, cita_id, fecha_consulta,
// motivo_consulta, exploracion_fisica, diagnostico, tratamiento, observaciones, proximo_control
$nombrePac = trim($consulta['pac_nombre'] . ' ' . $consulta['pac_ap'] . ' ' . ($consulta['pac_am'] ?? ''));
?>

<div class="mb-3">
    <a href="<?= url('consultas?paciente_id=' . $consulta['paciente_id']) ?>"
       class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Historial de <?= e($nombrePac) ?>
    </a>
</div>

<div class="row g-3">

    <!-- Columna principal -->
    <div class="col-lg-8">

        <!-- Encabezado de la nota -->
        <div class="card mb-3">
            <div class="card-body d-flex align-items-start gap-3 flex-wrap">
                <div class="flex-grow-1">
                    <a href="<?= url('pacientes/' . $consulta['paciente_id']) ?>"
                       class="fw-semibold fs-6 text-decoration-none">
                        <?= e($nombrePac) ?>
                    </a>
                    <div class="text-muted small mt-1">
                        <?php if (!empty($consulta['pac_nacimiento'])): ?>
                            <?= edad_anios($consulta['pac_nacimiento']) ?> años &nbsp;·&nbsp;
                        <?php endif; ?>
                        <?php if (!empty($consulta['pac_tipo_sangre'])): ?>
                            <span class="text-danger fw-semibold"><?= e($consulta['pac_tipo_sangre']) ?></span>
                            &nbsp;·&nbsp;
                        <?php endif; ?>
                        <?php if (!empty($consulta['pac_alergias'])): ?>
                            <span class="text-danger">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Alergias: <?= e($consulta['pac_alergias']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-semibold"><?= e(fecha_legible($consulta['fecha_consulta'])) ?></div>
                    <?php if (!empty($consulta['med_nombre'])): ?>
                        <div class="small text-muted">
                            <i class="bi bi-person-badge me-1"></i><?= e($consulta['med_nombre']) ?>
                            <?php if (!empty($consulta['med_cedula'])): ?>
                                · Céd. <?= e($consulta['med_cedula']) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($consulta['cita_id'])): ?>
                        <div class="small mt-1">
                            <a href="<?= url('agenda/' . $consulta['cita_id']) ?>" class="text-decoration-none text-muted">
                                <i class="bi bi-calendar2-check me-1"></i>Ver cita
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Secciones de la nota clínica -->
        <?php
        $secciones = [
            ['motivo_consulta',    'bi-chat-left-text',      'Motivo de consulta'],
            ['exploracion_fisica', 'bi-activity',            'Exploración física'],
            ['diagnostico',        'bi-clipboard2-check',    'Diagnóstico'],
            ['tratamiento',        'bi-capsule',             'Tratamiento / Plan'],
            ['observaciones',      'bi-sticky',              'Observaciones'],
        ];
        foreach ($secciones as [$campo, $icono, $titulo]):
            if (empty($consulta[$campo])) continue;
        ?>
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi <?= $icono ?> me-1"></i> <?= $titulo ?>
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space:pre-line"><?= e($consulta[$campo]) ?></p>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($consulta['proximo_control'])): ?>
        <div class="alert alert-info d-flex align-items-center gap-2">
            <i class="bi bi-calendar-event fs-5"></i>
            <div>
                <strong>Próximo control:</strong> <?= e(fecha_legible($consulta['proximo_control'])) ?>
            </div>
            <a href="<?= url('agenda/nueva?paciente_id=' . $consulta['paciente_id'] . '&fecha=' . $consulta['proximo_control']) ?>"
               class="btn btn-sm btn-outline-primary ms-auto">
                <i class="bi bi-plus-lg"></i> Agendar
            </a>
        </div>
        <?php endif; ?>

    </div>

    <!-- Columna acciones -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Acciones</div>
            <div class="card-body d-grid gap-2">
                <a href="<?= url('consultas/' . $consulta['id'] . '/editar') ?>"
                   class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i> Editar nota
                </a>
                <a href="<?= url('recetas/nueva?paciente_id=' . $consulta['paciente_id'] . '&consulta_id=' . $consulta['id']) ?>"
                   class="btn btn-outline-danger">
                    <i class="bi bi-prescription2 me-1"></i> Generar receta
                </a>
                <a href="<?= url('cobros/nuevo?paciente_id=' . $consulta['paciente_id'] . '&consulta_id=' . $consulta['id']) ?>"
                   class="btn btn-outline-success">
                    <i class="bi bi-cash-coin me-1"></i> Registrar cobro
                </a>
                <a href="<?= url('consultas/nueva?paciente_id=' . $consulta['paciente_id']) ?>"
                   class="btn btn-outline-success">
                    <i class="bi bi-plus-lg me-1"></i> Nueva consulta (mismo paciente)
                </a>
                <a href="<?= url('pacientes/' . $consulta['paciente_id']) ?>"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-person me-1"></i> Ficha del paciente
                </a>
                <hr class="my-1">
                <button type="button" class="btn btn-outline-danger btn-sm"
                        data-bs-toggle="modal" data-bs-target="#modalEliminar">
                    <i class="bi bi-trash me-1"></i> Eliminar nota
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Modal eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar nota clínica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de eliminar la consulta del
                    <strong><?= e(fecha_legible($consulta['fecha_consulta'])) ?></strong>
                    de <strong><?= e($nombrePac) ?></strong>?
                </p>
                <p class="text-muted small mb-0">La nota se eliminará de forma lógica y quedará en la bitácora de auditoría.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="post" action="<?= url('consultas/' . $consulta['id'] . '/eliminar') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
