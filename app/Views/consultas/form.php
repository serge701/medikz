<?php
// $consulta (null = nueva), $paciente, $medicos, $citaId, $errores
$esEdicion = !empty($consulta['id']);
$accion    = $esEdicion ? url('consultas/' . $consulta['id']) : url('consultas');

$err = fn(string $c) => $errores[$c] ?? null;
$val = fn(string $c, mixed $def = '') => old($c, (string) ($consulta[$c] ?? $def));

$pacId     = (int) ($_SESSION['_old']['paciente_id'] ?? $consulta['paciente_id'] ?? $paciente['id'] ?? 0);
$pacNombre = '';
if ($paciente) {
    $pacNombre = nombre_completo($paciente);
}
?>

<form method="post" action="<?= $accion ?>">
    <?= csrf_field() ?>
    <?php if ($citaId): ?>
        <input type="hidden" name="cita_id" value="<?= (int) $citaId ?>">
    <?php endif; ?>

    <!-- Paciente + Fecha -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person-vcard"></i> Paciente y fecha</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Paciente <span class="text-danger">*</span></label>
                    <input type="hidden" name="paciente_id" id="paciente_id" value="<?= $pacId ?: '' ?>">
                    <div class="position-relative">
                        <input type="text" id="pac_buscar"
                               class="form-control <?= $err('paciente_id') ? 'is-invalid' : '' ?>"
                               value="<?= e($pacNombre) ?>"
                               placeholder="Buscar por nombre, teléfono o CURP…"
                               autocomplete="off"
                               <?= ($esEdicion || $pacNombre) ? 'readonly' : '' ?>>
                        <?php if ($err('paciente_id')): ?>
                            <div class="invalid-feedback"><?= e($err('paciente_id')) ?></div>
                        <?php endif; ?>
                        <div id="pac_resultados"
                             class="list-group shadow position-absolute w-100"
                             style="z-index:1000; display:none; max-height:220px; overflow-y:auto;"></div>
                    </div>
                    <?php if (!$esEdicion && $pacNombre): ?>
                        <div class="mt-1 small text-success">
                            <i class="bi bi-check-circle"></i> <?= e($pacNombre) ?>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-2 text-danger"
                                    onclick="limpiarPaciente()">cambiar</button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha de consulta <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_consulta"
                           class="form-control <?= $err('fecha_consulta') ? 'is-invalid' : '' ?>"
                           value="<?= $val('fecha_consulta', date('Y-m-d')) ?>">
                    <?php if ($err('fecha_consulta')): ?>
                        <div class="invalid-feedback"><?= e($err('fecha_consulta')) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Nota clínica -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-clipboard2-pulse"></i> Nota clínica</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-semibold">Motivo de consulta</label>
                <textarea name="motivo_consulta" class="form-control" rows="3"
                          placeholder="¿Por qué acude el paciente?"><?= $val('motivo_consulta') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Exploración física</label>
                <textarea name="exploracion_fisica" class="form-control" rows="4"
                          placeholder="Signos vitales, hallazgos relevantes…"><?= $val('exploracion_fisica') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Diagnóstico</label>
                <textarea name="diagnostico" class="form-control" rows="3"
                          placeholder="CIE-10 o diagnóstico descriptivo…"><?= $val('diagnostico') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Tratamiento / Plan</label>
                <textarea name="tratamiento" class="form-control" rows="4"
                          placeholder="Medicamentos, dosis, indicaciones…"><?= $val('tratamiento') ?></textarea>
            </div>
            <div class="mb-0">
                <label class="form-label fw-semibold">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="3"
                          placeholder="Notas adicionales, evolución esperada…"><?= $val('observaciones') ?></textarea>
            </div>
        </div>
    </div>

    <!-- Seguimiento + Médico -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-calendar-check"></i> Seguimiento</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Próximo control</label>
                    <input type="date" name="proximo_control" class="form-control"
                           value="<?= $val('proximo_control') ?>">
                    <div class="form-text">Dejar en blanco si no aplica.</div>
                </div>
                <?php if (count($medicos) > 0): ?>
                <div class="col-md-5">
                    <label class="form-label">Médico que atiende</label>
                    <select name="medico_id" class="form-select">
                        <option value="">— Sin asignar —</option>
                        <?php
                        $medId = (int) ($consulta['medico_id'] ?? (count($medicos) === 1 ? $medicos[0]['id'] : 0));
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
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i>
            <?= $esEdicion ? 'Guardar cambios' : 'Guardar consulta' ?>
        </button>
        <?php if ($esEdicion): ?>
            <a href="<?= url('consultas/' . $consulta['id']) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <?php else: ?>
            <a href="<?= url('consultas' . ($pacId ? '?paciente_id=' . $pacId : '')) ?>"
               class="btn btn-outline-secondary">Cancelar</a>
        <?php endif; ?>
    </div>
</form>

<script>
(function () {
    const input  = document.getElementById('pac_buscar');
    const hidden = document.getElementById('paciente_id');
    const lista  = document.getElementById('pac_resultados');

    if (!input || input.readOnly) return;

    let timer = null;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { lista.style.display = 'none'; return; }
        timer = setTimeout(() => buscar(q), 280);
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
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'list-group-item list-group-item-action py-2';
                        btn.innerHTML = `<strong>${esc(p.nombre)}</strong>
                            <span class="text-muted small ms-2">
                                ${p.edad ? p.edad + ' años' : ''}
                                ${p.telefono ? '· ' + esc(p.telefono) : ''}
                            </span>`;
                        btn.addEventListener('click', () => {
                            hidden.value = p.id;
                            input.value  = p.nombre;
                            lista.style.display = 'none';
                        });
                        lista.appendChild(btn);
                    });
                }
                lista.style.display = 'block';
            });
    }

    window.limpiarPaciente = function () {
        hidden.value = '';
        input.value  = '';
        input.readOnly = false;
        lista.style.display = 'none';
        input.focus();
    };

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
})();
</script>
