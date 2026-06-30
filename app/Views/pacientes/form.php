<?php
$esEdicion = !empty($paciente['id']);
$pageTitle = $esEdicion ? 'Editar paciente' : 'Nuevo paciente';
$accion    = $esEdicion ? url('pacientes/' . $paciente['id']) : url('pacientes');
$puedeClinico = \App\Core\Auth::puedeVerClinico();

/** Devuelve el valor a mostrar: primero lo reenviado (old), si no, el del paciente. */
$val = function (string $campo) use ($paciente) {
    return old($campo, $paciente[$campo] ?? '');
};
$err = fn(string $c) => $errores[$c] ?? null;
?>

<?php if (!empty($duplicados)): ?>
<div class="alert alert-warning">
    <h6 class="mb-2"><i class="bi bi-exclamation-triangle"></i> Posible paciente duplicado</h6>
    <p class="mb-2 small">Ya existe(n) paciente(s) con ese mismo nombre:</p>
    <ul class="mb-2">
        <?php foreach ($duplicados as $d): ?>
            <li>
                <a href="<?= url('pacientes/' . $d['id']) ?>" target="_blank">
                    <?= e(nombre_completo($d)) ?></a>
                <?php if (!empty($d['telefono'])): ?>
                    · Tel: <?= e($d['telefono']) ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <p class="mb-0 small text-secondary">
        Si es la misma persona, abre su ficha. Si es alguien distinto, confirma abajo para crearlo.
    </p>
</div>
<?php endif; ?>

<form method="post" action="<?= $accion ?>">
    <?= csrf_field() ?>
    <?php if (!empty($duplicados)): ?>
        <input type="hidden" name="confirmar_duplicado" value="1">
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person-vcard"></i> Datos básicos</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nombre(s) <span class="text-danger">*</span></label>
                    <input type="text" name="nombre"
                           class="form-control <?= $err('nombre') ? 'is-invalid' : '' ?>"
                           value="<?= $val('nombre') ?>" autofocus required>
                    <?php if ($err('nombre')): ?><div class="invalid-feedback"><?= e($err('nombre')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Apellido paterno <span class="text-danger">*</span></label>
                    <input type="text" name="apellido_paterno"
                           class="form-control <?= $err('apellido_paterno') ? 'is-invalid' : '' ?>"
                           value="<?= $val('apellido_paterno') ?>" required>
                    <?php if ($err('apellido_paterno')): ?><div class="invalid-feedback"><?= e($err('apellido_paterno')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Apellido materno</label>
                    <input type="text" name="apellido_materno" class="form-control" value="<?= $val('apellido_materno') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Sexo</label>
                    <select name="sexo" class="form-select">
                        <?php $sx = $val('sexo'); ?>
                        <option value="">—</option>
                        <option value="M" <?= $sx === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= $sx === 'F' ? 'selected' : '' ?>>Femenino</option>
                        <option value="O" <?= $sx === 'O' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento"
                           class="form-control <?= $err('fecha_nacimiento') ? 'is-invalid' : '' ?>"
                           value="<?= $val('fecha_nacimiento') ?>">
                    <?php if ($err('fecha_nacimiento')): ?><div class="invalid-feedback"><?= e($err('fecha_nacimiento')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" name="telefono" class="form-control" value="<?= $val('telefono') ?>">
                </div>
            </div>
        </div>
    </div>

    <?php
    // Detectar si estas secciones ya tienen datos (para auto-expandirlas)
    $tieneExtraDatos = $val('curp') || $val('email') || $val('tipo_sangre') || $val('direccion')
                    || $val('ciudad') || $val('estado') || $val('cp')
                    || $val('alergias') || $val('contacto_emergencia') || $val('tel_emergencia');
    $tieneAntecedentes = $val('antecedentes');
    ?>

    <!-- ── Más datos ── -->
    <div class="card mb-3 border-0 shadow-sm overflow-hidden">
        <div class="card-header d-flex align-items-center justify-content-between py-3 px-4"
             style="background:linear-gradient(90deg,#f0f6ff 0%,#f8faff 100%);cursor:pointer;border-bottom:1px solid #dce8fb;"
             onclick="toggleSeccion('masDatos', this)">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                     style="width:36px;height:36px;background:#dce8fb;flex-shrink:0">
                    <i class="bi bi-person-lines-fill" style="color:#2563eb;font-size:1rem"></i>
                </div>
                <div>
                    <div class="fw-semibold" style="color:#1e3a5f">Más datos del paciente</div>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        <?php foreach (['CURP','Email','Tipo de sangre','Dirección','Ciudad','Estado','C.P.','Alergias','Emergencia'] as $chip): ?>
                        <span class="badge rounded-pill fw-normal"
                              style="background:#e0ebff;color:#2563eb;font-size:.7rem"><?= $chip ?></span>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($tieneExtraDatos): ?>
                <span class="badge text-bg-success" style="font-size:.72rem">Con datos</span>
                <?php else: ?>
                <span class="text-muted small">Opcional</span>
                <?php endif ?>
                <i class="bi bi-chevron-down toggle-chevron ms-1" style="color:#2563eb;transition:transform .2s;<?= $tieneExtraDatos ? 'transform:rotate(180deg)' : '' ?>"></i>
            </div>
        </div>
        <div id="masDatos" <?= $tieneExtraDatos ? '' : 'style="display:none"' ?>>
            <div class="card-body px-4 pt-4 pb-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">CURP</label>
                        <input type="text" name="curp" maxlength="18"
                               class="form-control text-uppercase <?= $err('curp') ? 'is-invalid' : '' ?>"
                               value="<?= $val('curp') ?>">
                        <?php if ($err('curp')): ?><div class="invalid-feedback"><?= e($err('curp')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email"
                               class="form-control <?= $err('email') ? 'is-invalid' : '' ?>"
                               value="<?= $val('email') ?>">
                        <?php if ($err('email')): ?><div class="invalid-feedback"><?= e($err('email')) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo de sangre</label>
                        <select name="tipo_sangre" class="form-select">
                            <?php $ts = $val('tipo_sangre');
                            foreach (['', 'O+','O-','A+','A-','B+','B-','AB+','AB-'] as $opt): ?>
                                <option value="<?= $opt ?>" <?= $ts === $opt ? 'selected' : '' ?>>
                                    <?= $opt === '' ? '—' : $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control" value="<?= $val('direccion') ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Ciudad</label>
                        <input type="text" name="ciudad" class="form-control" value="<?= $val('ciudad') ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Estado</label>
                        <input type="text" name="estado" class="form-control" value="<?= $val('estado') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">C.P.</label>
                        <input type="text" name="cp" class="form-control" value="<?= $val('cp') ?>">
                    </div>
                    <div class="col-12"><hr class="my-1"></div>
                    <div class="col-12">
                        <label class="form-label">Alergias</label>
                        <textarea name="alergias" class="form-control" rows="2"
                                  placeholder="Penicilina, AINEs, látex…"><?= $val('alergias') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contacto de emergencia</label>
                        <input type="text" name="contacto_emergencia" class="form-control" value="<?= $val('contacto_emergencia') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Teléfono de emergencia</label>
                        <input type="tel" name="tel_emergencia" class="form-control" value="<?= $val('tel_emergencia') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($puedeClinico): ?>
    <!-- ── Antecedentes médicos ── -->
    <div class="card mb-3 border-0 shadow-sm overflow-hidden">
        <div class="card-header d-flex align-items-center justify-content-between py-3 px-4"
             style="background:linear-gradient(90deg,#f0fff4 0%,#f7fffe 100%);cursor:pointer;border-bottom:1px solid #bbf0d5;"
             onclick="toggleSeccion('antecedentes', this)">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                     style="width:36px;height:36px;background:#d1fadf;flex-shrink:0">
                    <i class="bi bi-clipboard2-pulse" style="color:#16a34a;font-size:1rem"></i>
                </div>
                <div>
                    <div class="fw-semibold" style="color:#14532d">Antecedentes médicos</div>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        <?php foreach (['Heredofamiliares','Patológicos','Quirúrgicos','Alergias','Medicamentos actuales','Otros'] as $chip): ?>
                        <span class="badge rounded-pill fw-normal"
                              style="background:#d1fadf;color:#16a34a;font-size:.7rem"><?= $chip ?></span>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($tieneAntecedentes): ?>
                <span class="badge text-bg-success" style="font-size:.72rem">Con datos</span>
                <?php else: ?>
                <span class="text-muted small">Solo personal clínico</span>
                <?php endif ?>
                <i class="bi bi-chevron-down toggle-chevron ms-1" style="color:#16a34a;transition:transform .2s;<?= $tieneAntecedentes ? 'transform:rotate(180deg)' : '' ?>"></i>
            </div>
        </div>
        <div id="antecedentes" <?= $tieneAntecedentes ? '' : 'style="display:none"' ?>>
            <div class="card-body px-4 pt-4 pb-3">
                <label class="form-label">Antecedentes</label>
                <textarea name="antecedentes" class="form-control" rows="4"
                          placeholder="Antecedentes heredofamiliares, patológicos, quirúrgicos, traumáticos, medicamentos actuales…"><?= $val('antecedentes') ?></textarea>
                <div class="form-text mt-1"><i class="bi bi-shield-lock me-1"></i>Visible solo para personal médico.</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

<script>
function toggleSeccion(id, header) {
    const panel   = document.getElementById(id);
    const chevron = header.querySelector('.toggle-chevron');
    const visible = panel.style.display !== 'none';
    panel.style.display  = visible ? 'none' : '';
    chevron.style.transform = visible ? '' : 'rotate(180deg)';
}
</script>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> <?= $esEdicion ? 'Guardar cambios' : 'Registrar paciente' ?>
        </button>
        <a href="<?= $esEdicion ? url('pacientes/' . $paciente['id']) : url('pacientes') ?>" class="btn btn-outline-secondary">
            Cancelar
        </a>
    </div>
</form>
