<?php
/** @var array<string,mixed>|null $usuario */
/** @var array<string,mixed>|null $medico */
/** @var array<string,string> $errores */
/** @var array<string,string> $erroresPassword */
/** @var array<int,string> $rolesPermitidos */

$esEditar  = $usuario !== null;
$esProp    = $esEditar && (int) $usuario['es_propietario'];
// Si solo hay un rol disponible (recepcion), usar ese como default
$rolDefault = count($rolesPermitidos) === 1 ? $rolesPermitidos[0] : 'medico';
$rolActual  = $esEditar ? $usuario['rol'] : old('rol', $rolDefault);

$rolLabels = [
    'medico'        => 'Médico',
    'admin_clinica' => 'Admin clínica',
    'recepcion'     => 'Recepción',
];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('usuarios') ?>">Usuarios</a></li>
        <li class="breadcrumb-item active">
            <?= $esEditar ? e($usuario['nombre']) : 'Nuevo usuario' ?>
        </li>
    </ol>
</nav>

<div class="row g-3">
<div class="col-lg-8">

<!-- ── Datos de la cuenta ─────────────────────────────────────────────── -->
<form method="POST"
      action="<?= $esEditar ? url('usuarios/' . (int)$usuario['id']) : url('usuarios') ?>">
    <?= csrf_field() ?>

    <div class="card shadow-sm mb-3">
        <div class="card-header fw-semibold">
            <i class="bi bi-person-circle me-1"></i> Datos de la cuenta
        </div>
        <div class="card-body row g-3">

            <div class="col-12">
                <label class="form-label fw-medium">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre"
                       class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>"
                       value="<?= $esEditar ? e($usuario['nombre']) : old('nombre') ?>"
                       placeholder="Dr. Juan García López" required>
                <?php if (isset($errores['nombre'])): ?>
                <div class="invalid-feedback"><?= e($errores['nombre']) ?></div>
                <?php endif ?>
            </div>

            <div class="col-md-7">
                <label class="form-label fw-medium">Email <span class="text-danger">*</span></label>
                <input type="email" name="email"
                       class="form-control <?= isset($errores['email']) ? 'is-invalid' : '' ?>"
                       value="<?= $esEditar ? e($usuario['email']) : old('email') ?>"
                       placeholder="doctor@clinica.com" required>
                <?php if (isset($errores['email'])): ?>
                <div class="invalid-feedback"><?= e($errores['email']) ?></div>
                <?php endif ?>
            </div>

            <div class="col-md-5">
                <label class="form-label fw-medium">Rol <span class="text-danger">*</span></label>
                <?php if ($esProp): ?>
                <input type="text" class="form-control" value="Admin (Propietario)" disabled>
                <input type="hidden" name="rol" value="<?= e($usuario['rol']) ?>">
                <?php elseif (count($rolesPermitidos) === 1): ?>
                <?php /* Doctor-propietario: solo puede crear recepcionistas */ ?>
                <input type="text" class="form-control"
                       value="<?= e($rolLabels[$rolesPermitidos[0]] ?? $rolesPermitidos[0]) ?>" disabled>
                <input type="hidden" name="rol" value="<?= e($rolesPermitidos[0]) ?>">
                <?php else: ?>
                <select name="rol" id="rol-select"
                        class="form-select <?= isset($errores['rol']) ? 'is-invalid' : '' ?>">
                    <?php foreach ($rolesPermitidos as $r): ?>
                    <option value="<?= e($r) ?>" <?= $rolActual === $r ? 'selected' : '' ?>>
                        <?= e($rolLabels[$r] ?? $r) ?>
                    </option>
                    <?php endforeach ?>
                </select>
                <?php if (isset($errores['rol'])): ?>
                <div class="invalid-feedback"><?= e($errores['rol']) ?></div>
                <?php endif ?>
                <?php endif ?>
            </div>

            <?php if ($esEditar && !$esProp): ?>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="activo"
                           id="activo" value="1"
                           <?= (int) $usuario['activo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo">
                        Cuenta activa (puede iniciar sesión)
                    </label>
                </div>
            </div>
            <?php endif ?>

            <?php if (!$esEditar): ?>
            <div class="col-md-6">
                <label class="form-label fw-medium">Contraseña <span class="text-danger">*</span></label>
                <input type="password" name="password"
                       class="form-control <?= isset($errores['password']) ? 'is-invalid' : '' ?>"
                       minlength="8" required autocomplete="new-password"
                       placeholder="Mínimo 8 caracteres">
                <?php if (isset($errores['password'])): ?>
                <div class="invalid-feedback"><?= e($errores['password']) ?></div>
                <?php endif ?>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-medium">Confirmar contraseña <span class="text-danger">*</span></label>
                <input type="password" name="password_confirm"
                       class="form-control <?= isset($errores['password_confirm']) ? 'is-invalid' : '' ?>"
                       minlength="8" required autocomplete="new-password"
                       placeholder="Repite la contraseña">
                <?php if (isset($errores['password_confirm'])): ?>
                <div class="invalid-feedback"><?= e($errores['password_confirm']) ?></div>
                <?php endif ?>
            </div>
            <?php endif ?>

        </div>
    </div>

    <!-- ── Perfil médico ─────────────────────────────────────────────────── -->
    <div id="medico-section" class="card shadow-sm mb-3"
         style="<?= $rolActual !== 'medico' ? 'display:none' : '' ?>">
        <div class="card-header fw-semibold">
            <i class="bi bi-person-badge me-1"></i> Perfil médico
            <small class="text-muted fw-normal ms-2">Aparece en recetas e historial</small>
        </div>
        <div class="card-body row g-3">

            <div class="col-12">
                <label class="form-label fw-medium">Nombre completo en receta</label>
                <input type="text" name="med_nombre" class="form-control"
                       value="<?= $medico ? e($medico['nombre']) : old('med_nombre') ?>"
                       placeholder="Con título: Dr. Juan García López">
                <div class="form-text">Si se deja en blanco se usará el nombre de la cuenta.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">Cédula profesional</label>
                <input type="text" name="med_cedula_profesional" class="form-control"
                       value="<?= $medico ? e($medico['cedula_profesional'] ?? '') : old('med_cedula_profesional') ?>"
                       placeholder="Ej. 12345678">
                <div class="form-text">Obligatoria para emitir recetas válidas.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">Especialidad</label>
                <input type="text" name="med_especialidad" class="form-control"
                       value="<?= $medico ? e($medico['especialidad'] ?? '') : old('med_especialidad') ?>"
                       placeholder="Ej. Medicina General">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">Cédula de especialidad</label>
                <input type="text" name="med_cedula_especialidad" class="form-control"
                       value="<?= $medico ? e($medico['cedula_especialidad'] ?? '') : old('med_cedula_especialidad') ?>"
                       placeholder="Si aplica">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">Teléfono directo</label>
                <input type="tel" name="med_telefono" class="form-control"
                       value="<?= $medico ? e($medico['telefono'] ?? '') : old('med_telefono') ?>"
                       placeholder="Para contacto profesional">
            </div>

            <div class="col-12">
                <label class="form-label fw-medium">Universidad</label>
                <input type="text" name="med_universidad" class="form-control"
                       value="<?= $medico ? e($medico['universidad'] ?? '') : old('med_universidad') ?>"
                       placeholder="Institución donde se graduó">
            </div>

        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i>
            <?= $esEditar ? 'Guardar cambios' : 'Crear usuario' ?>
        </button>
        <a href="<?= url('usuarios') ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<?php if ($esEditar): ?>
<!-- ── Cambiar contraseña ─────────────────────────────────────────────── -->
<form method="POST" action="<?= url('usuarios/' . (int)$usuario['id'] . '/password') ?>"
      class="mt-3">
    <?= csrf_field() ?>
    <div class="card shadow-sm border-warning">
        <div class="card-header fw-semibold text-warning-emphasis bg-warning bg-opacity-10">
            <i class="bi bi-key me-1"></i> Cambiar contraseña
        </div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label fw-medium">Nueva contraseña</label>
                <input type="password" name="password_nuevo"
                       class="form-control <?= isset($erroresPassword['password_nuevo']) ? 'is-invalid' : '' ?>"
                       minlength="8" autocomplete="new-password"
                       placeholder="Mínimo 8 caracteres">
                <?php if (isset($erroresPassword['password_nuevo'])): ?>
                <div class="invalid-feedback"><?= e($erroresPassword['password_nuevo']) ?></div>
                <?php endif ?>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-medium">Confirmar contraseña</label>
                <input type="password" name="password_confirm"
                       class="form-control <?= isset($erroresPassword['password_confirm']) ? 'is-invalid' : '' ?>"
                       minlength="8" autocomplete="new-password"
                       placeholder="Repite la contraseña">
                <?php if (isset($erroresPassword['password_confirm'])): ?>
                <div class="invalid-feedback"><?= e($erroresPassword['password_confirm']) ?></div>
                <?php endif ?>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-warning btn-sm">
                <i class="bi bi-key me-1"></i> Actualizar contraseña
            </button>
        </div>
    </div>
</form>
<?php endif ?>

</div><!-- col-lg-8 -->

<!-- ── Panel lateral ─────────────────────────────────────────────────── -->
<div class="col-lg-4">
    <div class="card shadow-sm mb-3">
        <div class="card-header fw-semibold">
            <i class="bi bi-info-circle me-1"></i> Roles disponibles
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tbody>
                    <?php if (in_array('medico', $rolesPermitidos, true)): ?>
                    <tr>
                        <td class="ps-3"><span class="badge text-bg-success">Médico</span></td>
                        <td class="pe-3 small text-muted">
                            Citas, historial clínico, recetas y cobros.
                        </td>
                    </tr>
                    <?php endif ?>
                    <?php if (in_array('admin_clinica', $rolesPermitidos, true)): ?>
                    <tr>
                        <td class="ps-3"><span class="badge text-bg-primary">Admin</span></td>
                        <td class="pe-3 small text-muted">
                            Todo lo del médico + usuarios y métricas.
                        </td>
                    </tr>
                    <?php endif ?>
                    <?php if (in_array('recepcion', $rolesPermitidos, true)): ?>
                    <tr>
                        <td class="ps-3"><span class="badge text-bg-secondary">Recepción</span></td>
                        <td class="pe-3 small text-muted">
                            Pacientes, agenda y cobros. Sin historial clínico.
                        </td>
                    </tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($esEditar): ?>
    <div class="card shadow-sm">
        <div class="card-body p-3">
            <div class="text-muted small mb-1">
                <i class="bi bi-calendar-event me-1"></i>
                Cuenta creada: <?= e(fecha_legible(substr($usuario['created_at'], 0, 10))) ?>
            </div>
            <?php if ($usuario['last_login']): ?>
            <div class="text-muted small">
                <i class="bi bi-box-arrow-in-right me-1"></i>
                Último acceso: <?= e(fecha_legible(substr($usuario['last_login'], 0, 10))) ?>
            </div>
            <?php else: ?>
            <div class="text-muted small opacity-50">
                <i class="bi bi-dash-circle me-1"></i> Sin acceso registrado aún
            </div>
            <?php endif ?>
        </div>
    </div>
    <?php endif ?>
</div>

</div><!-- row -->

<script>
(function () {
    const select  = document.getElementById('rol-select');
    const section = document.getElementById('medico-section');
    if (!select || !section) return;
    select.addEventListener('change', function () {
        section.style.display = this.value === 'medico' ? '' : 'none';
    });
})();
</script>
