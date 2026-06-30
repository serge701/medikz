<style>
    .guest-form-inner h2 { font-size: 1.5rem; font-weight: 700; color: #0f1724; }
    .guest-form-inner .form-label { font-weight: 500; font-size: .875rem; color: #374151; }
    .guest-form-inner .form-control {
        border-radius: 8px;
        border-color: #d1d5db;
        font-size: .9rem;
        padding: .55rem .85rem;
    }
    .guest-form-inner .form-control:focus {
        border-color: #4e9af1;
        box-shadow: 0 0 0 3px rgba(78,154,241,.15);
    }
    .guest-form-inner .input-group-text {
        border-radius: 8px 0 0 8px !important;
        border-color: #d1d5db;
        background: #f9fafb;
        color: #6b7280;
    }
    .guest-form-inner .input-group .form-control { border-radius: 0 8px 8px 0 !important; }
    .btn-login {
        background: #0f1724;
        border: none;
        border-radius: 8px;
        padding: .65rem;
        font-weight: 600;
        font-size: .95rem;
        letter-spacing: .01em;
        transition: background .2s;
    }
    .btn-login:hover { background: #1a2e52; }
    .divider-text {
        display: flex; align-items: center; gap: .75rem;
        color: #9ca3af; font-size: .8rem; margin: 1.25rem 0;
    }
    .divider-text::before, .divider-text::after {
        content: ''; flex: 1; height: 1px; background: #e5e7eb;
    }
    .btn-registro {
        border: 1.5px solid #4e9af1;
        color: #4e9af1;
        border-radius: 8px;
        padding: .6rem;
        font-weight: 600;
        font-size: .9rem;
        transition: all .2s;
    }
    .btn-registro:hover { background: #4e9af1; color: #fff; }
</style>

<!-- Logo visible solo en móvil (el panel oscuro con logo blanco se oculta en pantallas pequeñas) -->
<div class="text-center mb-4 d-md-none">
    <img src="<?= url('assets/img/medikz_logo.png') ?>" alt="Medikz"
         style="height:32px;width:auto;max-width:160px;object-fit:contain;">
</div>

<h2 class="mb-1">Bienvenido</h2>
<p class="text-muted mb-4" style="font-size:.875rem">Ingresa tus credenciales para continuar</p>

<?php if ($err = get_flash('error')): ?>
    <div class="alert alert-danger py-2 mb-3" style="border-radius:8px;font-size:.875rem">
        <i class="bi bi-exclamation-circle me-1"></i><?= e($err) ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('login') ?>">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label">Correo electrónico</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control"
                   value="<?= old('email') ?>" autofocus required
                   placeholder="doctor@ejemplo.com">
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">Contraseña</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control"
                   required placeholder="••••••••">
        </div>
    </div>

    <button type="submit" class="btn btn-login btn-primary w-100">
        Iniciar sesión &rarr;
    </button>
</form>

<div class="divider-text">¿No tienes cuenta?</div>

<a href="<?= url('registro') ?>" class="btn btn-registro w-100">
    <i class="bi bi-rocket-takeoff me-1"></i> Crear cuenta gratis · 14 días de prueba
</a>

<p class="text-center mt-4" style="font-size:.75rem;color:#9ca3af">
    &copy; <?= date('Y') ?> <?= e(\App\Core\Config::get('app')['name']) ?> &nbsp;&middot;&nbsp; Todos los derechos reservados
</p>
