<?php
// $clinicas, $precioDefault
$estados = [
    'trial'      => ['label' => 'Trial',      'badge' => 'text-bg-warning text-dark'],
    'activo'     => ['label' => 'Activo',      'badge' => 'text-bg-success'],
    'suspendido' => ['label' => 'Suspendido',  'badge' => 'text-bg-danger'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <span class="badge text-bg-dark me-2">SUPER ADMIN</span>
        <span class="text-muted small"><?= count($clinicas) ?> clínicas registradas</span>
    </div>
    <a href="<?= url('superadmin/clinicas/nueva') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Nueva clínica
    </a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3">#</th>
                    <th>Clínica</th>
                    <th>Propietario</th>
                    <th>Estado</th>
                    <th>Vence</th>
                    <th class="text-end">Precio/mes</th>
                    <th class="pe-3"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clinicas as $c): ?>
            <?php
                $estado   = $c['estado_saas'] ?? 'trial';
                $badge    = $estados[$estado] ?? $estados['trial'];
                $hasta    = $estado === 'activo'
                    ? ($c['suscripcion_hasta'] ?? null)
                    : ($c['trial_ends_at'] ?? null);
                $precio   = $c['precio_mensual'] !== null
                    ? '$' . number_format($c['precio_mensual'] / 100, 2) . ' MXN'
                    : '<span class="text-muted small">Estándar ($' . number_format($precioDefault / 100, 2) . ')</span>';
            ?>
            <tr>
                <td class="ps-3 text-muted small"><?= (int) $c['id'] ?></td>
                <td>
                    <div class="fw-semibold"><?= e($c['nombre']) ?></div>
                    <div class="text-muted small"><?= e($c['email'] ?? '') ?></div>
                </td>
                <td class="small">
                    <div><?= e($c['owner_nombre'] ?? '—') ?></div>
                    <div class="text-muted"><?= e($c['owner_email'] ?? '') ?></div>
                </td>
                <td><span class="badge <?= $badge['badge'] ?>"><?= $badge['label'] ?></span></td>
                <td class="small">
                    <?= $hasta ? date('d/m/Y', strtotime($hasta)) : '—' ?>
                </td>
                <td class="text-end small"><?= $precio ?></td>
                <td class="pe-3 text-end">
                    <a href="<?= url('superadmin/clinicas/' . (int) $c['id'] . '/editar') ?>"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-pencil"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach ?>
            <?php if (empty($clinicas)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Sin clínicas registradas.</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>
