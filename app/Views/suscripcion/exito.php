<?php
$susHasta = $clinica['suscripcion_hasta'] ?? null;
?>

<div class="row justify-content-center">
<div class="col-md-7 col-lg-6">
    <div class="card shadow-sm text-center">
        <div class="card-body p-5">
            <div class="mb-3">
                <span class="display-1">
                    <i class="bi bi-check-circle-fill text-success"></i>
                </span>
            </div>
            <h2 class="h3 mb-2">¡Pago exitoso!</h2>
            <p class="text-muted mb-3">
                Tu suscripción a MedApp está activa.
                <?php if ($susHasta): ?>
                    Tienes acceso hasta el
                    <?php
                    $meses = ['','enero','febrero','marzo','abril','mayo','junio',
                              'julio','agosto','septiembre','octubre','noviembre','diciembre'];
                    $ts = strtotime($susHasta);
                    ?>
                    <strong><?= (int)date('j',$ts) ?> de <?= $meses[(int)date('n',$ts)] ?> de <?= date('Y',$ts) ?></strong>.
                <?php endif ?>
            </p>

            <hr>

            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-3">
                <a href="<?= url('') ?>" class="btn btn-primary btn-lg px-4">
                    <i class="bi bi-house me-1"></i> Ir al inicio
                </a>
                <a href="<?= url('suscripcion') ?>" class="btn btn-outline-secondary btn-lg px-4">
                    <i class="bi bi-receipt me-1"></i> Ver suscripción
                </a>
            </div>
        </div>
    </div>
</div>
</div>
