<?php // $recetas, $paciente ?>

<style>
/* ── tarjetas de receta ── */
.rx-card {
    background: #fff;
    border: 1px solid #e9eef5;
    border-radius: 12px;
    padding: .85rem 1.1rem;
    margin-bottom: .6rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    transition: border-color .15s, box-shadow .15s;
    text-decoration: none;
    color: inherit;
}
.rx-card:hover {
    border-color: #4e9af1;
    box-shadow: 0 2px 10px rgba(78,154,241,.13);
    color: inherit;
}

/* fecha */
.rx-date {
    flex-shrink: 0;
    text-align: center;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: .45rem .6rem;
    min-width: 52px;
}
.rx-date-day  { font-size: 1.1rem; font-weight: 800; color: #15803d; line-height: 1; }
.rx-date-mon  { font-size: .65rem; font-weight: 700; text-transform: uppercase;
                 letter-spacing: .05em; color: #4ade80; margin-top: 2px; }

/* cuerpo */
.rx-body { flex-grow: 1; min-width: 0; }
.rx-patient {
    font-weight: 700; font-size: .9rem; color: #1e293b;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rx-meds {
    display: flex; flex-wrap: wrap; gap: 4px; margin-top: 5px;
}
.rx-med-chip {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: .71rem; font-weight: 600;
    background: #f8faff; border: 1px solid #dbeafe; color: #3b82f6;
    border-radius: 5px; padding: 2px 7px;
}
.rx-med-more {
    font-size: .71rem; color: #94a3b8; align-self: center; padding: 2px 4px;
}
.rx-doctor {
    font-size: .75rem; color: #94a3b8; margin-top: 5px;
    display: flex; align-items: center; gap: 4px;
}

/* acciones */
.rx-actions { flex-shrink: 0; display: flex; gap: .4rem; align-items: center; }
.rx-btn {
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 8px; border: 1px solid #e2e8f0;
    background: #fff; color: #64748b; font-size: .85rem;
    text-decoration: none; transition: all .12s;
}
.rx-btn:hover { background: #f1f5f9; color: #0f1724; }
.rx-btn.rx-pdf { border-color: #fca5a5; color: #dc2626; }
.rx-btn.rx-pdf:hover { background: #fee2e2; }

/* encabezado de sección */
.rx-header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: .75rem; margin-bottom: 1.25rem;
}
.rx-count {
    font-size: .78rem; color: #94a3b8; font-weight: 600;
    background: #f8faff; border: 1px solid #e2e8f0;
    border-radius: 20px; padding: 3px 12px;
}

/* símbolo Rx decorativo */
.rx-symbol {
    font-size: .7rem; font-weight: 900; color: #15803d;
    background: #dcfce7; border-radius: 50%;
    width: 18px; height: 18px;
    display: inline-flex; align-items: center; justify-content: center;
    margin-right: 4px; flex-shrink: 0;
}
</style>

<div class="rx-header">
    <div>
        <?php if ($paciente): ?>
            <a href="<?= url('recetas') ?>" class="text-decoration-none text-muted small d-block mb-1">
                <i class="bi bi-arrow-left"></i> Todas las recetas
            </a>
            <span class="fw-bold">
                <i class="bi bi-person me-1 text-muted"></i><?= e(nombre_completo($paciente)) ?>
            </span>
        <?php else: ?>
            <h6 class="mb-0 fw-bold text-body">Recetas médicas</h6>
        <?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if (!empty($recetas)): ?>
            <span class="rx-count"><?= count($recetas) ?> receta<?= count($recetas) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
        <a href="<?= url('recetas/nueva' . ($paciente ? '?paciente_id=' . $paciente['id'] : '')) ?>"
           class="btn btn-primary btn-sm" style="border-radius:8px">
            <i class="bi bi-plus-lg me-1"></i>Nueva receta
        </a>
    </div>
</div>

<?php if (empty($recetas)): ?>
    <div class="text-center py-5" style="background:#fff;border:1px solid #e9eef5;border-radius:14px">
        <div style="font-size:2.5rem;margin-bottom:.5rem;opacity:.3">&#8478;</div>
        <p class="text-muted mb-3">No hay recetas registradas<?= $paciente ? ' para este paciente' : '' ?>.</p>
        <a href="<?= url('recetas/nueva' . ($paciente ? '?paciente_id=' . $paciente['id'] : '')) ?>"
           class="btn btn-primary btn-sm" style="border-radius:8px">
            <i class="bi bi-plus-lg me-1"></i>Crear primera receta
        </a>
    </div>
<?php else: ?>
    <?php
    $meses = ['','ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    foreach ($recetas as $r):
        $nombrePac = trim($r['pac_nombre'] . ' ' . $r['pac_ap'] . ' ' . ($r['pac_am'] ?? ''));
        $meds      = json_decode($r['medicamentos'] ?? '[]', true) ?: [];
        $medSlice  = array_slice($meds, 0, 3);
        $medExtra  = count($meds) > 3 ? count($meds) - 3 : 0;
        $ts        = strtotime($r['fecha_receta']);
        $dia       = date('j', $ts);
        $mes       = $meses[(int) date('n', $ts)];
    ?>
    <div class="rx-card">

        <!-- Fecha -->
        <div class="rx-date">
            <div class="rx-date-day"><?= $dia ?></div>
            <div class="rx-date-mon"><?= $mes ?></div>
        </div>

        <!-- Cuerpo -->
        <div class="rx-body">
            <div class="rx-patient"><?= e($nombrePac) ?></div>

            <?php if (!empty($medSlice)): ?>
            <div class="rx-meds">
                <?php foreach ($medSlice as $m): ?>
                    <span class="rx-med-chip">
                        <span class="rx-symbol">&#8478;</span>
                        <?= e($m['nombre']) ?>
                    </span>
                <?php endforeach; ?>
                <?php if ($medExtra): ?>
                    <span class="rx-med-more">+<?= $medExtra ?> más</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($r['med_nombre'])): ?>
            <div class="rx-doctor">
                <i class="bi bi-person-badge"></i>
                <?= e($r['med_nombre']) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Acciones -->
        <div class="rx-actions">
            <a href="<?= url('recetas/' . $r['id']) ?>" class="rx-btn" title="Ver detalle">
                <i class="bi bi-eye"></i>
            </a>
            <a href="<?= url('recetas/' . $r['id'] . '/pdf') ?>" class="rx-btn rx-pdf"
               title="Ver PDF" target="_blank">
                <i class="bi bi-file-pdf"></i>
            </a>
        </div>

    </div>
    <?php endforeach; ?>
<?php endif; ?>
