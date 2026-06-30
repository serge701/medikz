<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(\App\Core\Config::get('app')['name']) ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4/dist/css/adminlte.min.css">
    <style>
        /* ── Sidebar custom ─────────────────────────────── */
        .app-sidebar { background: #0f1724 !important; }

        /* Brand */
        .sidebar-brand .brand-link {
            border-bottom: 1px solid rgba(255,255,255,.07) !important;
            padding: 1rem 1.2rem !important;
        }
        .sidebar-brand .brand-text {
            font-weight: 700 !important;
            font-size: 1.15rem;
            letter-spacing: .02em;
            color: #fff !important;
        }
        .sidebar-brand .brand-image { color: #4e9af1; font-size: 1.4rem; }

        /* Sección headers */
        .sidebar-menu .nav-header {
            font-weight: 800 !important;
            font-size: .68rem;
            letter-spacing: .1em;
            color: rgba(255,255,255,.35) !important;
            padding: 1.1rem 1rem .3rem 1.2rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .sidebar-menu .nav-header::before {
            content: '';
            display: inline-block;
            width: 14px;
            height: 2px;
            background: #4e9af1;
            border-radius: 2px;
            flex-shrink: 0;
        }

        /* Nav links */
        .sidebar-menu .nav-link {
            border-radius: 8px !important;
            margin: 1px 8px !important;
            padding: .48rem .9rem !important;
            transition: background .15s, color .15s;
            color: rgba(255,255,255,.65) !important;
        }
        .sidebar-menu .nav-link:hover {
            background: rgba(78,154,241,.12) !important;
            color: #fff !important;
        }
        .sidebar-menu .nav-link.active {
            background: #4e9af1 !important;
            color: #fff !important;
            box-shadow: 0 2px 8px rgba(78,154,241,.35);
        }
        .sidebar-menu .nav-link.active .nav-icon { color: #fff !important; }

        /* Íconos */
        .sidebar-menu .nav-icon {
            color: #4e9af1 !important;
            font-size: 1rem;
            margin-right: .55rem !important;
            width: 1.2rem;
            text-align: center;
        }
        .sidebar-menu .nav-link:hover .nav-icon { color: #7dbcff !important; }

        /* Texto de los items */
        .sidebar-menu .nav-link p {
            font-size: .875rem;
            font-weight: 500;
            letter-spacing: .01em;
        }
        .sidebar-menu .nav-link.active p { font-weight: 600; }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

    <?php require dirname(__DIR__) . '/partials/navbar.php'; ?>
    <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>

    <main class="app-main">

        <?php
        // Banner de trial / suscripción vencida
        $_cid = (int) ($_SESSION['user']['clinica_id'] ?? 0);
        if ($_cid > 0) {
            $_cl  = (new \App\Models\Clinica())->find($_cid);
            $_est = $_cl['estado_saas'] ?? '';
            $_dias = 0;
            if ($_est === 'trial' && !empty($_cl['trial_ends_at'])) {
                $_fin  = new DateTimeImmutable($_cl['trial_ends_at']);
                $_hoy2 = new DateTimeImmutable(date('Y-m-d'));
                $_dias = $_fin >= $_hoy2 ? (int) $_hoy2->diff($_fin)->days + 1 : 0;
            }
            $_diasSus = 0;
            if ($_est === 'activo' && !empty($_cl['suscripcion_hasta'])) {
                $_finSus  = new DateTimeImmutable($_cl['suscripcion_hasta']);
                $_hoy3    = new DateTimeImmutable(date('Y-m-d'));
                $_diasSus = $_finSus >= $_hoy3 ? (int) $_hoy3->diff($_finSus)->days + 1 : 0;
            }

            if ($_est === 'trial' && $_dias > 0): ?>
        <div class="alert rounded-0 mb-0 py-2 text-center small border-0 border-bottom <?= $_dias <= 3 ? 'alert-danger' : 'alert-warning' ?>">
            <i class="bi bi-clock me-1"></i>
            <strong>Período de prueba:</strong>
            te quedan <strong><?= $_dias ?> día<?= $_dias !== 1 ? 's' : '' ?></strong>.
            <a href="<?= url('suscripcion') ?>" class="alert-link fw-semibold ms-2">Suscribirme →</a>
        </div>
        <?php elseif ($_est === 'activo' && $_diasSus > 0 && $_diasSus <= 7): ?>
        <div class="alert rounded-0 mb-0 py-2 text-center small border-0 border-bottom <?= $_diasSus <= 3 ? 'alert-danger' : 'alert-warning' ?>">
            <i class="bi bi-calendar-x me-1"></i>
            <strong>Tu suscripción vence el <?= date('d/m/Y', strtotime($_cl['suscripcion_hasta'])) ?>.</strong>
            Te quedan <strong><?= $_diasSus ?> día<?= $_diasSus !== 1 ? 's' : '' ?></strong> — renueva para no perder el acceso.
            <a href="<?= url('suscripcion') ?>" class="alert-link fw-semibold ms-2">Renovar →</a>
        </div>
        <?php elseif ($_est === 'suspendido'): ?>
        <div class="alert alert-danger rounded-0 mb-0 py-2 text-center small border-0">
            <i class="bi bi-exclamation-octagon me-1"></i>
            Tu cuenta está <strong>suspendida</strong>.
            <a href="<?= url('suscripcion') ?>" class="alert-link fw-semibold ms-2">Renovar suscripción →</a>
        </div>
        <?php endif;
        } ?>

        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0"><?= e($pageTitle ?? 'Inicio') ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">

                <?php if ($ok = get_flash('success')): ?>
                    <div class="alert alert-success"><?= e($ok) ?></div>
                <?php endif; ?>
                <?php if ($err = get_flash('error')): ?>
                    <div class="alert alert-danger"><?= e($err) ?></div>
                <?php endif; ?>

                <?= $content ?>
            </div>
        </div>
    </main>

    <footer class="app-footer d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <a href="https://Medikz.App" target="_blank" rel="noopener" class="d-flex align-items-center gap-2 text-decoration-none">
            <img src="<?= url('assets/img/medikz_logo.png') ?>"
                 alt="Medikz.App"
                 style="height:22px;width:auto;object-fit:contain;">
            <span class="text-muted small">&copy; <?= date('Y') ?></span>
        </a>
        <span class="text-muted small d-none d-sm-inline">
            Desarrollado por <a href="https://ninubo.com" target="_blank" rel="noopener">Ninubo</a>
            &nbsp;&middot;&nbsp; v0.95 · Beta
        </span>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4/dist/js/adminlte.min.js"></script>
</body>
</html>
