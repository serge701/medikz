<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(\App\Core\Config::get('app')['name']) ?> · Acceso</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4/dist/css/adminlte.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            background: #0f1724;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        /* ── Panel izquierdo (branding) ── */
        .guest-brand {
            width: 420px;
            flex-shrink: 0;
            background: linear-gradient(160deg, #0f1724 0%, #162340 60%, #1a2e52 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 2.8rem;
            position: relative;
            overflow: hidden;
        }
        .guest-brand::before {
            content: '';
            position: absolute;
            width: 340px; height: 340px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(78,154,241,.18) 0%, transparent 70%);
            top: -80px; right: -100px;
        }
        .guest-brand::after {
            content: '';
            position: absolute;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(78,154,241,.1) 0%, transparent 70%);
            bottom: 40px; left: -60px;
        }
        .brand-logo {
            display: flex;
            align-items: center;
            margin-bottom: 2.5rem;
        }
        .brand-logo img {
            height: 38px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
        }
        .brand-tagline {
            font-size: 1.35rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.35;
            margin-bottom: .75rem;
        }
        .brand-sub {
            font-size: .92rem;
            color: rgba(255,255,255,.5);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }
        .brand-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }
        .brand-features li {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: rgba(255,255,255,.75);
            font-size: .88rem;
        }
        .brand-features li i {
            color: #4e9af1;
            font-size: 1rem;
            flex-shrink: 0;
        }

        /* ── Panel derecho (formulario) ── */
        .guest-form {
            flex: 1;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 2rem;
        }
        .guest-form-inner {
            width: 100%;
            max-width: 380px;
        }

        /* Responsive: en móvil solo el panel del formulario */
        @media (max-width: 768px) {
            body { background: #fff; }
            .guest-brand { display: none; }
            .guest-form { padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="guest-brand">
        <div class="brand-logo">
            <img src="<?= url('assets/img/medikz_logo_w.png') ?>" alt="Medikz">
        </div>
        <div class="brand-tagline">Gestión integral de tu consultorio médico</div>
        <p class="brand-sub">Todo lo que necesitas para administrar tu práctica clínica en un solo lugar.</p>
        <ul class="brand-features">
            <li><i class="bi bi-calendar2-check"></i> Agenda con detección de conflictos</li>
            <li><i class="bi bi-folder2-open"></i> Expediente clínico digital</li>
            <li><i class="bi bi-file-earmark-text"></i> Recetas con QR · NOM-004</li>
            <li><i class="bi bi-whatsapp"></i> Recordatorios automáticos por WhatsApp</li>
            <li><i class="bi bi-graph-up-arrow"></i> Métricas e ingresos en tiempo real</li>
        </ul>
    </div>

    <div class="guest-form">
        <div class="guest-form-inner">
            <?= $content ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4/dist/js/adminlte.min.js"></script>
</body>
</html>
