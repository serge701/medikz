<?php
// $cita (null = nueva), $paciente, $medicos, $errores, $fechaDefault
$esEdicion  = !empty($cita['id']);
$accion     = $esEdicion ? url('agenda/' . $cita['id']) : url('agenda');
$puedeClinico = \App\Core\Auth::puedeVerClinico();

$err = fn(string $c) => $errores[$c] ?? null;
$val = fn(string $c, mixed $def = '') => old($c, (string) ($cita[$c] ?? $def));

// Paciente pre-seleccionado (desde old() o desde el objeto $paciente pasado por el controlador)
$pacId     = (int) ($_SESSION['_old']['paciente_id'] ?? $cita['paciente_id'] ?? $paciente['id'] ?? 0);
$pacNombre = '';
if ($paciente) {
    $pacNombre = trim($paciente['nombre'] . ' ' . $paciente['apellido_paterno'] . ' ' . ($paciente['apellido_materno'] ?? ''));
}
?>

<form method="post" action="<?= $accion ?>">
    <?= csrf_field() ?>

    <!-- Paciente -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person"></i> Paciente</div>
        <div class="card-body">
            <label class="form-label">Paciente <span class="text-danger">*</span></label>
            <input type="hidden" name="paciente_id" id="paciente_id" value="<?= $pacId ?: '' ?>">

            <div class="position-relative">
                <input type="text" id="pac_buscar"
                       class="form-control <?= $err('paciente_id') ? 'is-invalid' : '' ?>"
                       value="<?= e($pacNombre) ?>"
                       placeholder="Buscar por nombre, teléfono o CURP…"
                       autocomplete="off">
                <?php if ($err('paciente_id')): ?>
                    <div class="invalid-feedback"><?= e($err('paciente_id')) ?></div>
                <?php endif; ?>
                <div id="pac_resultados"
                     class="list-group shadow position-absolute w-100"
                     style="z-index:1000; display:none; max-height:220px; overflow-y:auto;"></div>
            </div>

            <?php if ($pacNombre): ?>
                <div id="pac_seleccionado" class="mt-2 small text-success">
                    <i class="bi bi-check-circle"></i> <?= e($pacNombre) ?>
                    <button type="button" class="btn btn-link btn-sm p-0 ms-2 text-danger"
                            onclick="limpiarPaciente()">cambiar</button>
                </div>
            <?php else: ?>
                <div id="pac_seleccionado" class="mt-2 small text-success" style="display:none"></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Horario -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-clock"></i> Horario</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Fecha <span class="text-danger">*</span></label>
                    <input type="date" name="fecha"
                           class="form-control <?= $err('fecha') ? 'is-invalid' : '' ?>"
                           value="<?= $val('fecha', $fechaDefault) ?>">
                    <?php if ($err('fecha')): ?>
                        <div class="invalid-feedback"><?= e($err('fecha')) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hora inicio <span class="text-danger">*</span></label>
                    <input type="time" name="hora_inicio"
                           class="form-control <?= $err('hora_inicio') ? 'is-invalid' : '' ?>"
                           value="<?= $val('hora_inicio', $horaInicioDefault ?? '09:00') ?>">
                    <?php if ($err('hora_inicio')): ?>
                        <div class="invalid-feedback"><?= e($err('hora_inicio')) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hora fin <span class="text-danger">*</span></label>
                    <input type="time" name="hora_fin"
                           class="form-control <?= $err('hora_fin') ? 'is-invalid' : '' ?>"
                           value="<?= $val('hora_fin', $horaFinDefault ?? '09:30') ?>">
                    <?php if ($err('hora_fin')): ?>
                        <div class="invalid-feedback"><?= e($err('hora_fin')) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalles -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-card-text"></i> Detalles</div>
        <div class="card-body">
            <div class="row g-3">
                <?php if (count($medicos) > 0): ?>
                <div class="col-md-6">
                    <label class="form-label">Médico</label>
                    <select name="medico_id" class="form-select">
                        <option value="">— Sin asignar —</option>
                        <?php
                        $medId = (int) ($cita['medico_id'] ?? (count($medicos) === 1 ? $medicos[0]['id'] : 0));
                        foreach ($medicos as $m):
                        ?>
                            <option value="<?= $m['id'] ?>" <?= (int)$m['id'] === $medId ? 'selected' : '' ?>>
                                <?= e($m['nombre']) ?>
                                <?php if (!empty($m['especialidad'])): ?>
                                    – <?= e($m['especialidad']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-6">
                    <label class="form-label">Motivo de consulta</label>
                    <input type="text" name="motivo" class="form-control"
                           value="<?= $val('motivo') ?>"
                           placeholder="Revisión general, dolor de cabeza…">
                </div>

                <?php if ($esEdicion): ?>
                <div class="col-md-6">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <?php
                        $estados = ['programada','confirmada','atendida','cancelada','no_asistio'];
                        $estadoActual = $val('estado', 'programada');
                        foreach ($estados as $est):
                        ?>
                            <option value="<?= $est ?>" <?= $estadoActual === $est ? 'selected' : '' ?>>
                                <?= estado_cita_label($est) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($puedeClinico): ?>
            <div class="mt-3">
                <label class="form-label">Notas clínicas</label>
                <textarea name="notas" class="form-control" rows="3"
                          placeholder="Observaciones, indicaciones previas…"><?= $val('notas') ?></textarea>
                <div class="form-text">Visible solo para personal médico.</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i>
            <?= $esEdicion ? 'Guardar cambios' : 'Registrar cita' ?>
        </button>
        <?php if ($esEdicion): ?>
            <a href="<?= url('agenda/' . $cita['id']) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <?php else: ?>
            <a href="<?= url('agenda?fecha=' . $fechaDefault) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <?php endif; ?>
    </div>
</form>

<script>
(function () {
    const input    = document.getElementById('pac_buscar');
    const hidden   = document.getElementById('paciente_id');
    const lista    = document.getElementById('pac_resultados');
    const info     = document.getElementById('pac_seleccionado');
    let   timer    = null;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { lista.style.display = 'none'; return; }
        timer = setTimeout(() => buscar(q), 280);
    });

    input.addEventListener('focus', function () {
        if (this.value.trim().length >= 2 && lista.childElementCount > 0) {
            lista.style.display = 'block';
        }
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !lista.contains(e.target)) {
            lista.style.display = 'none';
        }
    });

    function buscar(q) {
        fetch('<?= url('pacientes/buscar') ?>?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                lista.innerHTML = '';
                if (data.length === 0) {
                    lista.innerHTML = '<div class="list-group-item text-muted small">Sin resultados</div>';
                } else {
                    data.forEach(p => {
                        const a = document.createElement('button');
                        a.type = 'button';
                        a.className = 'list-group-item list-group-item-action py-2';
                        a.innerHTML = `<strong>${escHtml(p.nombre)}</strong>
                            <span class="text-muted small ms-2">${escHtml(p.edad ? p.edad + ' años' : '')}
                            ${p.telefono ? '· ' + escHtml(p.telefono) : ''}</span>`;
                        a.addEventListener('click', () => seleccionar(p));
                        lista.appendChild(a);
                    });
                }
                lista.style.display = 'block';
            })
            .catch(() => { lista.style.display = 'none'; });
    }

    function seleccionar(p) {
        hidden.value    = p.id;
        input.value     = p.nombre;
        lista.style.display = 'none';
        info.style.display  = '';
        info.innerHTML = `<i class="bi bi-check-circle text-success"></i> ${escHtml(p.nombre)}
            <button type="button" class="btn btn-link btn-sm p-0 ms-2 text-danger"
                    onclick="limpiarPaciente()">cambiar</button>`;
    }

    window.limpiarPaciente = function () {
        hidden.value        = '';
        input.value         = '';
        info.style.display  = 'none';
        input.focus();
    };

    function escHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
})();
</script>
