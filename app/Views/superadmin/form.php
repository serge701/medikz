<?php
// $clinica (null = nueva), $errores
$esEdicion   = $clinica !== null;
$accion      = $esEdicion
    ? url('superadmin/clinicas/' . (int) $clinica['id'])
    : url('superadmin/clinicas');
$err         = fn(string $c) => $errores[$c] ?? null;
$val         = fn(string $c, mixed $def = '') => old($c, $esEdicion ? (string) ($clinica[$c] ?? $def) : (string) $def);

// Para edición: precios en pesos (no centavos)
$precioActual       = '';
$precioAnualActual  = '';
if ($esEdicion) {
    if ($clinica['precio_mensual'] !== null) {
        $precioActual = number_format($clinica['precio_mensual'] / 100, 2, '.', '');
    }
    if ($clinica['precio_anual'] !== null) {
        $precioAnualActual = number_format($clinica['precio_anual'] / 100, 2, '.', '');
    }
}
?>

<div class="mb-3">
    <a href="<?= url('superadmin') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow-sm" style="max-width:680px">
    <div class="card-header fw-semibold">
        <span class="badge text-bg-dark me-2">SUPER ADMIN</span>
        <?= $esEdicion ? 'Editar clínica' : 'Nueva clínica' ?>
    </div>
    <div class="card-body">

        <?php if (!empty($errores['_global'])): ?>
            <div class="alert alert-danger"><?= e($errores['_global']) ?></div>
        <?php endif ?>

        <form method="post" action="<?= $accion ?>">
            <?= csrf_field() ?>

            <?php if (!$esEdicion): ?>
            <!-- ── Datos del consultorio ── -->
            <h6 class="text-uppercase text-muted small fw-bold mb-3 mt-1">Consultorio</h6>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label">Nombre del consultorio <span class="text-danger">*</span></label>
                    <input type="text" name="clinica_nombre"
                           class="form-control <?= $err('clinica_nombre') ? 'is-invalid' : '' ?>"
                           value="<?= e(old('clinica_nombre', '')) ?>" required>
                    <?php if ($err('clinica_nombre')): ?>
                        <div class="invalid-feedback"><?= e($err('clinica_nombre')) ?></div>
                    <?php endif ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control"
                           value="<?= e(old('telefono', '')) ?>">
                </div>
            </div>

            <!-- ── Propietario ── -->
            <h6 class="text-uppercase text-muted small fw-bold mb-3">Propietario (admin_clinica)</h6>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre"
                           class="form-control <?= $err('nombre') ? 'is-invalid' : '' ?>"
                           value="<?= e(old('nombre', '')) ?>" required>
                    <?php if ($err('nombre')): ?>
                        <div class="invalid-feedback"><?= e($err('nombre')) ?></div>
                    <?php endif ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email"
                           class="form-control <?= $err('email') ? 'is-invalid' : '' ?>"
                           value="<?= e(old('email', '')) ?>" required>
                    <?php if ($err('email')): ?>
                        <div class="invalid-feedback"><?= e($err('email')) ?></div>
                    <?php endif ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contraseña inicial <span class="text-danger">*</span></label>
                    <input type="text" name="password"
                           class="form-control <?= $err('password') ? 'is-invalid' : '' ?>"
                           value="<?= e(old('password', '')) ?>"
                           placeholder="Mínimo 8 caracteres">
                    <?php if ($err('password')): ?>
                        <div class="invalid-feedback"><?= e($err('password')) ?></div>
                    <?php endif ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Especialidad</label>
                    <input type="text" name="especialidad" class="form-control"
                           value="<?= e(old('especialidad', '')) ?>" placeholder="Medicina general…">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cédula profesional</label>
                    <input type="text" name="cedula" class="form-control"
                           value="<?= e(old('cedula', '')) ?>">
                </div>
            </div>
            <?php endif /* !esEdicion */ ?>

            <!-- ── Precio negociado ── -->
            <h6 class="text-uppercase text-muted small fw-bold mb-3">Precio negociado</h6>
            <p class="text-muted small mb-3">Deja vacío para usar el precio estándar ($389/mes · $3,900/año).</p>
            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label class="form-label">Precio mensual (MXN)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="precio_mensual" step="0.01" min="0"
                               class="form-control <?= $err('precio_mensual') ? 'is-invalid' : '' ?>"
                               value="<?= $esEdicion ? $precioActual : e(old('precio_mensual', '')) ?>"
                               placeholder="389.00">
                        <span class="input-group-text">/mes</span>
                    </div>
                    <?php if ($err('precio_mensual')): ?>
                        <div class="text-danger small mt-1"><?= e($err('precio_mensual')) ?></div>
                    <?php endif ?>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Precio anual (MXN)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="precio_anual" step="0.01" min="0"
                               class="form-control <?= $err('precio_anual') ? 'is-invalid' : '' ?>"
                               value="<?= $esEdicion ? $precioAnualActual : e(old('precio_anual', '')) ?>"
                               placeholder="3900.00">
                        <span class="input-group-text">/año</span>
                    </div>
                    <?php if ($err('precio_anual')): ?>
                        <div class="text-danger small mt-1"><?= e($err('precio_anual')) ?></div>
                    <?php endif ?>
                </div>
            </div>

            <!-- ── Activación / suscripción ── -->
            <h6 class="text-uppercase text-muted small fw-bold mb-3">Acceso</h6>
            <div class="row g-3 mb-4">
                <?php if (!$esEdicion): ?>
                <div class="col-md-5">
                    <label class="form-label">Activación inicial</label>
                    <select name="activacion" class="form-select" id="sel-activacion"
                            onchange="toggleSusHasta(this.value)">
                        <option value="trial" <?= old('activacion', 'trial') === 'trial' ? 'selected' : '' ?>>
                            Trial (<?= (int)(\App\Core\Config::get('stripe')['trial_dias'] ?? 14) ?> días)
                        </option>
                        <option value="activo" <?= old('activacion') === 'activo' ? 'selected' : '' ?>>
                            Activo hasta fecha
                        </option>
                        <option value="suspendido" <?= old('activacion') === 'suspendido' ? 'selected' : '' ?>>
                            Suspendido
                        </option>
                    </select>
                </div>
                <div class="col-md-5" id="div-sus-hasta"
                     style="display:<?= old('activacion') === 'activo' ? '' : 'none' ?>">
                    <label class="form-label">Activo hasta <span class="text-danger">*</span></label>
                    <input type="date" name="suscripcion_hasta"
                           class="form-control <?= $err('suscripcion_hasta') ? 'is-invalid' : '' ?>"
                           value="<?= e(old('suscripcion_hasta', '')) ?>">
                    <?php if ($err('suscripcion_hasta')): ?>
                        <div class="invalid-feedback"><?= e($err('suscripcion_hasta')) ?></div>
                    <?php endif ?>
                </div>
                <?php else: ?>
                <div class="col-md-4">
                    <label class="form-label">Estado SaaS</label>
                    <select name="estado_saas" class="form-select">
                        <?php foreach (['trial','activo','suspendido'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($clinica['estado_saas'] ?? '') === $opt ? 'selected' : '' ?>>
                            <?= ucfirst($opt) ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Suscripción hasta</label>
                    <input type="date" name="suscripcion_hasta" class="form-control"
                           value="<?= e($clinica['suscripcion_hasta'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Trial hasta</label>
                    <input type="date" name="trial_ends_at" class="form-control"
                           value="<?= e($clinica['trial_ends_at'] ?? '') ?>">
                </div>
                <?php endif ?>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>
                    <?= $esEdicion ? 'Guardar cambios' : 'Crear clínica' ?>
                </button>
                <a href="<?= url('superadmin') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php if (!$esEdicion): ?>
<script>
function toggleSusHasta(val) {
    document.getElementById('div-sus-hasta').style.display = val === 'activo' ? '' : 'none';
}
</script>
<?php endif ?>
