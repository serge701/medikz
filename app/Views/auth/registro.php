<div class="card shadow-sm" style="max-width:520px;margin:0 auto;">
    <div class="card-body p-4">
        <div class="text-center mb-3">
            <img src="<?= url('assets/img/medikz_logo.png') ?>" alt="Medikz"
                 style="height:30px;width:auto;max-width:160px;object-fit:contain;margin-bottom:.6rem;">
            <p class="text-secondary small mb-0">Crea tu cuenta gratuita</p>
        </div>

        <?php if ($err = get_flash('error')): ?>
            <div class="alert alert-danger py-2"><?= e($err) ?></div>
        <?php endif; ?>
        <?php if (!empty($errores['_global'])): ?>
            <div class="alert alert-danger py-2"><?= e($errores['_global']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= url('registro') ?>" novalidate>
            <?= csrf_field() ?>

            <!-- Consultorio -->
            <div class="mb-3">
                <label class="form-label fw-medium">Nombre del consultorio <span class="text-danger">*</span></label>
                <input type="text" name="clinica_nombre"
                       class="form-control <?= isset($errores['clinica_nombre']) ? 'is-invalid' : '' ?>"
                       value="<?= old('clinica_nombre') ?>"
                       placeholder="Ej. Consultorio Dra. García"
                       autofocus required>
                <?php if (isset($errores['clinica_nombre'])): ?>
                    <div class="invalid-feedback"><?= e($errores['clinica_nombre']) ?></div>
                <?php endif ?>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-7">
                    <label class="form-label fw-medium">Tu nombre completo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre"
                           class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>"
                           value="<?= old('nombre') ?>"
                           placeholder="Dr. / Dra. …" required>
                    <?php if (isset($errores['nombre'])): ?>
                        <div class="invalid-feedback"><?= e($errores['nombre']) ?></div>
                    <?php endif ?>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-medium">Teléfono</label>
                    <input type="tel" name="telefono"
                           class="form-control"
                           value="<?= old('telefono') ?>"
                           placeholder="55 1234 5678">
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-medium">Especialidad</label>
                    <input type="text" name="especialidad"
                           class="form-control"
                           value="<?= old('especialidad') ?>"
                           placeholder="Medicina General">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Cédula profesional</label>
                    <input type="text" name="cedula"
                           class="form-control"
                           value="<?= old('cedula') ?>"
                           placeholder="Opcional">
                </div>
            </div>

            <hr class="my-3">

            <div class="mb-3">
                <label class="form-label fw-medium">Correo electrónico <span class="text-danger">*</span></label>
                <input type="email" name="email"
                       class="form-control <?= isset($errores['email']) ? 'is-invalid' : '' ?>"
                       value="<?= old('email') ?>"
                       placeholder="doctor@clinica.com" required>
                <?php if (isset($errores['email'])): ?>
                    <div class="invalid-feedback"><?= e($errores['email']) ?></div>
                <?php endif ?>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-medium">Contraseña <span class="text-danger">*</span></label>
                    <input type="password" name="password"
                           class="form-control <?= isset($errores['password']) ? 'is-invalid' : '' ?>"
                           minlength="8" required autocomplete="new-password"
                           placeholder="Mín. 8 caracteres">
                    <?php if (isset($errores['password'])): ?>
                        <div class="invalid-feedback"><?= e($errores['password']) ?></div>
                    <?php endif ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Confirmar contraseña <span class="text-danger">*</span></label>
                    <input type="password" name="password2"
                           class="form-control <?= isset($errores['password2']) ? 'is-invalid' : '' ?>"
                           minlength="8" required autocomplete="new-password"
                           placeholder="Repite la contraseña">
                    <?php if (isset($errores['password2'])): ?>
                        <div class="invalid-feedback"><?= e($errores['password2']) ?></div>
                    <?php endif ?>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check <?= isset($errores['terminos']) ? 'is-invalid' : '' ?>">
                    <input class="form-check-input <?= isset($errores['terminos']) ? 'is-invalid' : '' ?>"
                           type="checkbox" name="terminos" id="terminos"
                           <?= old('terminos') ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="terminos">
                        Acepto los <a href="#" target="_blank">términos de uso</a>
                        y la <a href="#" target="_blank">política de privacidad</a>
                    </label>
                </div>
                <?php if (isset($errores['terminos'])): ?>
                    <div class="text-danger small mt-1"><?= e($errores['terminos']) ?></div>
                <?php endif ?>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">
                <i class="bi bi-rocket-takeoff me-1"></i>
                Crear cuenta gratis · 14 días de prueba
            </button>
        </form>

        <div class="text-center mt-3 small text-secondary">
            ¿Ya tienes cuenta?
            <a href="<?= url('login') ?>">Inicia sesión</a>
        </div>

        <div class="mt-3 p-2 bg-light rounded text-center small text-muted">
            <i class="bi bi-shield-check text-success"></i>
            14 días gratis · Sin tarjeta de crédito · Cancela cuando quieras
        </div>
    </div>
</div>
