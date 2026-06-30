<?php
// Vista pública — usa layout 'guest'. Sin $pageTitle.
// $receta (null si no existe), $codigo
$valida     = $receta !== null;
$nombrePac  = $valida ? trim($receta['pac_nombre'] . ' ' . $receta['pac_ap'] . ' ' . ($receta['pac_am'] ?? '')) : '';
$medicamentos = $valida ? (json_decode((string) ($receta['medicamentos'] ?? '[]'), true) ?: []) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verificación de receta · MedApp</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>body{background:#f5f7fa;}</style>
</head>
<body>
<div class="container py-5" style="max-width:640px">

    <?php if (!$valida): ?>
        <div class="card border-danger text-center py-5">
            <div class="card-body">
                <i class="bi bi-x-circle-fill text-danger fs-1 d-block mb-3"></i>
                <h4 class="text-danger">Receta no encontrada</h4>
                <p class="text-muted mt-2">
                    El código <strong><?= e($codigo) ?></strong> no corresponde
                    a ninguna receta válida en este sistema.
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-success mb-3">
            <div class="card-header bg-success text-white d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <strong>Receta válida</strong>
                <span class="ms-auto small opacity-75">Folio: <?= e($receta['codigo_verificacion']) ?></span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Paciente</dt>
                    <dd class="col-sm-8"><?= e($nombrePac) ?></dd>

                    <dt class="col-sm-4">Fecha</dt>
                    <dd class="col-sm-8"><?= e(fecha_legible($receta['fecha_receta'])) ?></dd>

                    <?php if (!empty($receta['med_nombre'])): ?>
                    <dt class="col-sm-4">Médico</dt>
                    <dd class="col-sm-8">
                        <?= e($receta['med_nombre']) ?>
                        <?php if (!empty($receta['med_cedula'])): ?>
                            · Céd. <?= e($receta['med_cedula']) ?>
                        <?php endif; ?>
                        <?php if (!empty($receta['med_especialidad'])): ?>
                            <br><span class="text-muted small"><?= e($receta['med_especialidad']) ?></span>
                        <?php endif; ?>
                    </dd>
                    <?php endif; ?>

                    <?php if (!empty($receta['clinica_nombre'])): ?>
                    <dt class="col-sm-4">Clínica</dt>
                    <dd class="col-sm-8"><?= e($receta['clinica_nombre']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <?php if (!empty($medicamentos)): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-capsule me-1"></i>Medicamentos prescritos</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($medicamentos as $i => $med): ?>
                    <li class="list-group-item">
                        <div class="fw-semibold">
                            <?= $i + 1 ?>. <?= e($med['nombre']) ?>
                            <?php if (!empty($med['dosis'])): ?>
                                <span class="fw-normal text-muted">– <?= e($med['dosis']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php $l2 = trim(($med['frecuencia'] ?? '') . (!empty($med['duracion']) ? ' por ' . $med['duracion'] : '')); ?>
                        <?php if ($l2): ?><div class="small text-muted">Tomar: <?= e($l2) ?></div><?php endif; ?>
                        <?php if (!empty($med['indicaciones'])): ?><div class="small fst-italic text-muted"><?= e($med['indicaciones']) ?></div><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="text-center text-muted small">
            <i class="bi bi-shield-check me-1"></i>
            Receta verificada por MedApp · NOM-004-SSA3-2012
        </div>
    <?php endif; ?>

</div>
</body>
</html>
<?php
// Esta vista genera su propio HTML completo; el layout 'guest' de view()
// puede sobreescribir esto si se usa el helper view(). Para evitarlo,
// el controlador llama view() con layout '' (sin layout).
