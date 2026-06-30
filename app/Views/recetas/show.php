<?php
// $receta con pac_*, med_* y codigo_verificacion
$nombrePac    = trim($receta['pac_nombre'] . ' ' . $receta['pac_ap'] . ' ' . ($receta['pac_am'] ?? ''));
$medicamentos = json_decode((string) ($receta['medicamentos'] ?? '[]'), true) ?: [];
?>

<style>
/* ── layout ── */
.rx-show-wrap { max-width: 900px; }

/* ── header de receta ── */
.rx-hero {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #bbf7d0;
    border-radius: 14px;
    padding: 1.25rem 1.4rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.rx-hero-left .rx-pac-name {
    font-size: 1.1rem; font-weight: 800; color: #14532d;
    text-decoration: none;
}
.rx-hero-left .rx-pac-name:hover { text-decoration: underline; }
.rx-hero-left .rx-pac-meta { font-size: .8rem; color: #4ade80; margin-top: 3px; }
.rx-hero-left .rx-doc {
    font-size: .82rem; color: #15803d; margin-top: 6px;
    display: flex; align-items: center; gap: 5px;
}
.rx-hero-right { text-align: right; flex-shrink: 0; }
.rx-hero-right .rx-fecha { font-size: .9rem; font-weight: 700; color: #15803d; }
.rx-hero-right .rx-folio {
    font-size: .72rem; color: #4ade80; margin-top: 4px;
    font-family: monospace; letter-spacing: .04em;
}

/* ── cards de sección ── */
.rx-section {
    background: #fff;
    border: 1px solid #e9eef5;
    border-radius: 12px;
    margin-bottom: 1rem;
    overflow: hidden;
}
.rx-section-header {
    padding: .6rem 1.1rem;
    background: #f8faff;
    border-bottom: 1px solid #edf1f9;
    font-size: .75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.rx-section-body { padding: 1rem 1.1rem; }

/* ── medicamentos ── */
.rx-med-item {
    display: flex;
    gap: .85rem;
    padding: .85rem 0;
    border-bottom: 1px solid #f1f5f9;
    align-items: flex-start;
}
.rx-med-item:last-child { border-bottom: none; padding-bottom: 0; }
.rx-med-num {
    width: 28px; height: 28px; border-radius: 50%;
    background: #eff6ff; color: #2563eb;
    font-size: .78rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-top: 1px;
}
.rx-med-body { flex-grow: 1; }
.rx-med-nombre { font-weight: 700; font-size: .9rem; color: #1e293b; }
.rx-med-dosis  { font-size: .82rem; color: #64748b; margin-left: 6px; }
.rx-med-posologia { font-size: .8rem; color: #64748b; margin-top: 3px; }
.rx-med-indicaciones { font-size: .78rem; color: #94a3b8; font-style: italic; margin-top: 2px; }

/* ── panel de acciones ── */
.rx-actions-card {
    background: #fff;
    border: 1px solid #e9eef5;
    border-radius: 12px;
    overflow: hidden;
    position: sticky;
    top: 1rem;
}
.rx-actions-header {
    padding: .65rem 1.1rem;
    background: #f8faff;
    border-bottom: 1px solid #edf1f9;
    font-size: .75rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .07em; color: #64748b;
}
.rx-actions-body { padding: .9rem; display: grid; gap: .5rem; }
.rx-actions-body .btn { border-radius: 9px; font-size: .84rem; }

/* ── código de verificación ── */
.rx-verify-box {
    background: #fafafa;
    border: 1px dashed #d1d5db;
    border-radius: 10px;
    padding: .85rem;
    text-align: center;
    margin-top: .5rem;
}
.rx-verify-code {
    font-family: monospace;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: .08em;
    color: #1e293b;
    background: #f1f5f9;
    padding: 4px 10px;
    border-radius: 6px;
    display: inline-block;
    margin: .4rem 0;
}
.rx-verify-label { font-size: .72rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
</style>

<!-- Breadcrumb -->
<div class="mb-3">
    <a href="<?= url('recetas?paciente_id=' . $receta['paciente_id']) ?>"
       class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Recetas de <?= e($nombrePac) ?>
    </a>
</div>

<div class="rx-show-wrap">
<div class="row g-3">

    <!-- Columna principal -->
    <div class="col-lg-8">

        <!-- Hero header -->
        <div class="rx-hero">
            <div class="rx-hero-left">
                <a href="<?= url('pacientes/' . $receta['paciente_id']) ?>" class="rx-pac-name">
                    <?= e($nombrePac) ?>
                </a>
                <div class="rx-pac-meta">
                    <?php if (!empty($receta['pac_nacimiento'])): ?>
                        <?= edad_anios($receta['pac_nacimiento']) ?> años
                        <?php if (!empty($receta['med_nombre'])): ?> &nbsp;·&nbsp; <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($receta['med_nombre'])): ?>
                <div class="rx-doc">
                    <i class="bi bi-person-badge"></i>
                    <?= e($receta['med_nombre']) ?>
                    <?php if (!empty($receta['med_cedula'])): ?>
                        <span class="text-muted">· Céd. <?= e($receta['med_cedula']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="rx-hero-right">
                <div class="rx-fecha"><i class="bi bi-calendar3 me-1"></i><?= e(fecha_legible($receta['fecha_receta'])) ?></div>
                <div class="rx-folio">Folio: <?= e($receta['codigo_verificacion']) ?></div>
            </div>
        </div>

        <!-- Diagnóstico -->
        <?php if (!empty($receta['diagnostico'])): ?>
        <div class="rx-section">
            <div class="rx-section-header">
                <i class="bi bi-clipboard2-check"></i> Diagnóstico
            </div>
            <div class="rx-section-body">
                <p class="mb-0" style="color:#334155"><?= e($receta['diagnostico']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Medicamentos -->
        <div class="rx-section">
            <div class="rx-section-header">
                <i class="bi bi-capsule"></i> &#8478; Medicamentos
                <span class="ms-auto fw-normal text-muted" style="text-transform:none;letter-spacing:0">
                    <?= count($medicamentos) ?> medicamento<?= count($medicamentos) !== 1 ? 's' : '' ?>
                </span>
            </div>
            <div class="rx-section-body">
                <?php if (empty($medicamentos)): ?>
                    <p class="text-muted mb-0 small">Sin medicamentos registrados.</p>
                <?php else: ?>
                    <?php foreach ($medicamentos as $i => $med): ?>
                    <div class="rx-med-item">
                        <div class="rx-med-num"><?= $i + 1 ?></div>
                        <div class="rx-med-body">
                            <div class="rx-med-nombre">
                                <?= e($med['nombre']) ?>
                                <?php if (!empty($med['dosis'])): ?>
                                    <span class="rx-med-dosis"><?= e($med['dosis']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php
                            $posologia = trim(
                                ($med['frecuencia'] ?? '') .
                                (!empty($med['duracion']) ? ' · por ' . $med['duracion'] : '')
                            );
                            ?>
                            <?php if ($posologia): ?>
                                <div class="rx-med-posologia">
                                    <i class="bi bi-clock me-1 opacity-50"></i><?= e($posologia) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($med['indicaciones'])): ?>
                                <div class="rx-med-indicaciones"><?= e($med['indicaciones']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Indicaciones generales -->
        <?php if (!empty($receta['indicaciones_generales'])): ?>
        <div class="rx-section">
            <div class="rx-section-header">
                <i class="bi bi-card-text"></i> Indicaciones generales
            </div>
            <div class="rx-section-body">
                <p class="mb-0" style="white-space:pre-line;color:#334155"><?= e($receta['indicaciones_generales']) ?></p>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Panel de acciones -->
    <div class="col-lg-4">
        <div class="rx-actions-card">
            <div class="rx-actions-header"><i class="bi bi-lightning me-1"></i>Acciones</div>
            <div class="rx-actions-body">

                <a href="<?= url('recetas/' . $receta['id'] . '/pdf') ?>"
                   class="btn btn-danger" target="_blank">
                    <i class="bi bi-file-pdf me-1"></i> Ver / Imprimir PDF
                </a>
                <a href="<?= url('recetas/' . $receta['id'] . '/editar') ?>"
                   class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i> Editar receta
                </a>
                <a href="<?= url('recetas/nueva?paciente_id=' . $receta['paciente_id']) ?>"
                   class="btn btn-outline-success">
                    <i class="bi bi-plus-lg me-1"></i> Nueva receta (mismo paciente)
                </a>
                <a href="<?= url('pacientes/' . $receta['paciente_id']) ?>"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-person me-1"></i> Ficha del paciente
                </a>

                <!-- Código de verificación -->
                <div class="rx-verify-box">
                    <div class="rx-verify-label">Código de verificación</div>
                    <div class="rx-verify-code"><?= e($receta['codigo_verificacion']) ?></div>
                    <a href="<?= url('recetas/verificar/' . $receta['codigo_verificacion']) ?>"
                       class="btn btn-outline-secondary btn-sm w-100" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Ver página pública
                    </a>
                </div>

                <button type="button" class="btn btn-outline-danger btn-sm"
                        data-bs-toggle="modal" data-bs-target="#modalEliminar">
                    <i class="bi bi-trash me-1"></i> Eliminar receta
                </button>

            </div>
        </div>
    </div>

</div>
</div>

<!-- Modal eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">¿Eliminar esta receta?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                Estás a punto de eliminar la receta del
                <strong><?= e(fecha_legible($receta['fecha_receta'])) ?></strong>
                de <strong><?= e($nombrePac) ?></strong>.
                <div class="text-muted small mt-2">Esta acción no se puede deshacer desde la interfaz.</div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="post" action="<?= url('recetas/' . $receta['id'] . '/eliminar') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Sí, eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
