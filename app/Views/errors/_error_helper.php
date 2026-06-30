<?php
// Helper de presentación de errores, sin layout, autocontenido.
function view_error(string $code, string $title, string $msg): void {
    ?>
    <!DOCTYPE html>
    <html lang="es" data-bs-theme="light">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($code) ?> · <?= e($title) ?></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4/dist/css/adminlte.min.css">
    </head>
    <body class="bg-body-tertiary">
        <div class="container py-5 text-center">
            <h1 class="display-1 fw-bold text-primary"><?= e($code) ?></h1>
            <h2 class="h4"><?= e($title) ?></h2>
            <p class="text-secondary"><?= e($msg) ?></p>
            <a href="<?= url('dashboard') ?>" class="btn btn-primary">Volver al inicio</a>
        </div>
    </body>
    </html>
    <?php
}
