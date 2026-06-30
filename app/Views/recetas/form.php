<?php
// $receta, $paciente, $medicos, $consultaId, $errores
$esEdicion  = !empty($receta['id']);
$accion     = $esEdicion ? url('recetas/' . $receta['id']) : url('recetas');
$err        = fn(string $c) => $errores[$c] ?? null;
$val        = fn(string $c, mixed $def = '') => old($c, (string) ($receta[$c] ?? $def));

$pacId     = (int) ($_SESSION['_old']['paciente_id'] ?? $receta['paciente_id'] ?? $paciente['id'] ?? 0);
$pacNombre = $paciente ? nombre_completo($paciente) : '';

// Medicamentos pre-existentes (edición o error de validación)
$medsJson = old('medicamentos_json', $receta['medicamentos'] ?? '[]');
$medsInit = json_decode($medsJson !== '' ? $medsJson : '[]', true) ?: [];
?>

<form method="post" action="<?= $accion ?>" id="forma-receta">
    <?= csrf_field() ?>
    <?php if ($consultaId): ?>
        <input type="hidden" name="consulta_id" value="<?= (int) $consultaId ?>">
    <?php endif; ?>
    <input type="hidden" name="medicamentos_json" id="medicamentos_json">

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
                        <div id="pac_resultados" class="list-group shadow position-absolute w-100"
                             style="z-index:1000;display:none;max-height:220px;overflow-y:auto;"></div>
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
                    <label class="form-label">Fecha <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_receta"
                           class="form-control <?= $err('fecha_receta') ? 'is-invalid' : '' ?>"
                           value="<?= $val('fecha_receta', date('Y-m-d')) ?>">
                    <?php if ($err('fecha_receta')): ?>
                        <div class="invalid-feedback"><?= e($err('fecha_receta')) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagnóstico -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-clipboard2-check"></i> Diagnóstico</div>
        <div class="card-body">
            <textarea name="diagnostico" class="form-control" rows="2"
                      placeholder="CIE-10 o descripción del diagnóstico…"><?= $val('diagnostico') ?></textarea>
        </div>
    </div>

    <!-- Medicamentos -->
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="bi bi-capsule"></i> Medicamentos <span class="text-danger">*</span></span>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarMed()">
                <i class="bi bi-plus-lg"></i> Agregar
            </button>
        </div>
        <?php if ($err('medicamentos')): ?>
            <div class="alert alert-danger mb-0 rounded-0 border-0 border-bottom py-2 px-3">
                <?= e($err('medicamentos')) ?>
            </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="meds-table">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:180px">Medicamento <span class="text-danger">*</span></th>
                        <th style="min-width:100px">Dosis</th>
                        <th style="min-width:140px">Frecuencia</th>
                        <th style="min-width:100px">Duración</th>
                        <th style="min-width:140px">Indicaciones</th>
                        <th style="width:50px"></th>
                    </tr>
                </thead>
                <tbody id="meds-body"></tbody>
            </table>
        </div>
        <div id="meds-vacio" class="card-body text-center text-muted py-3" style="display:none">
            <i class="bi bi-capsule opacity-50"></i> Sin medicamentos. Usa el botón "Agregar".
        </div>
    </div>

    <!-- Indicaciones generales -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-card-text"></i> Indicaciones generales</div>
        <div class="card-body">
            <textarea name="indicaciones_generales" class="form-control" rows="3"
                      placeholder="Reposo, hidratación, dieta, próxima cita…"><?= $val('indicaciones_generales') ?></textarea>
        </div>
    </div>

    <!-- Médico -->
    <?php if (count($medicos) > 0): ?>
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person-badge"></i> Médico que prescribe</div>
        <div class="card-body">
            <select name="medico_id" class="form-select" style="max-width:380px">
                <option value="">— Sin asignar —</option>
                <?php
                $medId = (int) ($receta['medico_id'] ?? (count($medicos) === 1 ? $medicos[0]['id'] : 0));
                foreach ($medicos as $m):
                ?>
                    <option value="<?= $m['id'] ?>" <?= (int)$m['id'] === $medId ? 'selected' : '' ?>>
                        <?= e($m['nombre']) ?>
                        <?php if (!empty($m['especialidad'])): ?>– <?= e($m['especialidad']) ?><?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> <?= $esEdicion ? 'Guardar cambios' : 'Guardar receta' ?>
        </button>
        <a href="<?= $esEdicion ? url('recetas/' . $receta['id']) : url('recetas' . ($pacId ? '?paciente_id=' . $pacId : '')) ?>"
           class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<script>
(function () {
    // ---- Medicamentos ----
    const tbody  = document.getElementById('meds-body');
    const vacio  = document.getElementById('meds-vacio');
    const tabla  = document.getElementById('meds-table');
    const forma  = document.getElementById('forma-receta');
    const jsonIn = document.getElementById('medicamentos_json');

    const medsIniciales = <?= json_encode($medsInit, JSON_UNESCAPED_UNICODE) ?>;

    function actualizarVacio() {
        const vac = tbody.children.length === 0;
        vacio.style.display = vac ? '' : 'none';
        tabla.style.display = vac ? 'none' : '';
    }

    function iniciarAutocompleteMed(input) {
        let drop = document.getElementById('drop_' + input.id);
        if (!drop) return;

        // Mover el dropdown al <body> para escapar del overflow:auto de table-responsive
        document.body.appendChild(drop);
        drop.style.position = 'fixed';
        drop.style.zIndex   = '9999';

        function reposicionar() {
            const r = input.getBoundingClientRect();
            drop.style.top   = r.bottom + 'px';
            drop.style.left  = r.left   + 'px';
            drop.style.width = r.width  + 'px';
        }

        let timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            const q = this.value.trim();
            if (q.length < 2) { drop.style.display = 'none'; return; }
            timer = setTimeout(() => {
                fetch('<?= url('medicamentos/buscar') ?>?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(lista => {
                        drop.innerHTML = '';
                        if (!lista.length) { drop.style.display = 'none'; return; }
                        lista.forEach(m => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action py-1 px-2';
                            btn.innerHTML = `<span class="fw-medium">${esc(m.nombre)}</span>`
                                + (m.presentacion ? ` <span class="text-muted small">— ${esc(m.presentacion)}</span>` : '')
                                + (m.categoria ? ` <span class="badge text-bg-light text-secondary ms-1" style="font-size:.7rem">${esc(m.categoria)}</span>` : '');
                            btn.addEventListener('mousedown', function (e) {
                                e.preventDefault();
                                input.value = m.nombre;
                                // Rellenar dosis con la concentración si el campo está vacío
                                const row = input.closest('tr');
                                if (row && m.concentracion) {
                                    const dosisInput = row.querySelectorAll('input')[1];
                                    if (dosisInput && !dosisInput.value.trim()) {
                                        dosisInput.value = m.concentracion;
                                    }
                                }
                                drop.style.display = 'none';
                            });
                            drop.appendChild(btn);
                        });
                        reposicionar();
                        drop.style.display = 'block';
                    })
                    .catch(() => { drop.style.display = 'none'; });
            }, 250);
        });

        input.addEventListener('focus', reposicionar);
        input.addEventListener('blur', function () {
            setTimeout(() => { drop.style.display = 'none'; }, 150);
        });
    }

    window.agregarMed = function (data = {}) {
        const uid = 'med_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6);
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="position:relative">
                <input type="text" id="${uid}" class="form-control form-control-sm med-nombre"
                       placeholder="Escribe para buscar…" value="${esc(data.nombre||'')}"
                       autocomplete="off">
                <div id="drop_${uid}" class="list-group shadow"
                     style="position:absolute;top:100%;left:0;right:0;z-index:1050;display:none;max-height:200px;overflow-y:auto;font-size:.85rem"></div>
            </td>
            <td><input type="text" class="form-control form-control-sm" placeholder="500mg" value="${esc(data.dosis||'')}"></td>
            <td><input type="text" class="form-control form-control-sm" placeholder="Cada 8 horas" value="${esc(data.frecuencia||'')}"></td>
            <td><input type="text" class="form-control form-control-sm" placeholder="7 días" value="${esc(data.duracion||'')}"></td>
            <td><input type="text" class="form-control form-control-sm" placeholder="Con alimentos" value="${esc(data.indicaciones||'')}"></td>
            <td>
                <button type="button" class="btn btn-outline-danger btn-sm px-2"
                        onclick="this.closest('tr').remove(); actualizarVacio()">
                    <i class="bi bi-trash"></i>
                </button>
            </td>`;
        tbody.appendChild(tr);
        actualizarVacio();
        const inp = document.getElementById(uid);
        iniciarAutocompleteMed(inp);
        if (!data.nombre) inp.focus();
    };

    // Pre-cargar medicamentos existentes
    if (medsIniciales.length > 0) {
        medsIniciales.forEach(m => agregarMed(m));
    } else {
        agregarMed(); // fila vacía inicial
    }
    actualizarVacio();

    // Serializar antes de enviar
    forma.addEventListener('submit', function () {
        const meds = [];
        tbody.querySelectorAll('tr').forEach(tr => {
            const inp = tr.querySelectorAll('input');
            if (inp[0].value.trim()) {
                meds.push({
                    nombre:       inp[0].value.trim(),
                    dosis:        inp[1].value.trim(),
                    frecuencia:   inp[2].value.trim(),
                    duracion:     inp[3].value.trim(),
                    indicaciones: inp[4].value.trim(),
                });
            }
        });
        jsonIn.value = JSON.stringify(meds);
    });

    // ---- Autocomplete paciente ----
    const input  = document.getElementById('pac_buscar');
    const hidden = document.getElementById('paciente_id');
    const lista  = document.getElementById('pac_resultados');

    if (input && !input.readOnly) {
        let timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            const q = this.value.trim();
            if (q.length < 2) { lista.style.display = 'none'; return; }
            timer = setTimeout(() => buscarPac(q), 280);
        });
        document.addEventListener('click', e => {
            if (!input.contains(e.target) && !lista.contains(e.target)) lista.style.display = 'none';
        });
    }

    function buscarPac(q) {
        fetch('<?= url('pacientes/buscar') ?>?q=' + encodeURIComponent(q))
            .then(r => r.json()).then(data => {
                lista.innerHTML = '';
                (data.length ? data : [{nombre:'Sin resultados',id:null}]).forEach(p => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'list-group-item list-group-item-action py-2';
                    btn.innerHTML = p.id
                        ? `<strong>${esc(p.nombre)}</strong> <span class="text-muted small ms-1">${p.edad ? p.edad+' años' : ''} ${p.telefono ? '· '+esc(p.telefono) : ''}</span>`
                        : `<span class="text-muted small">${esc(p.nombre)}</span>`;
                    if (p.id) btn.addEventListener('click', () => {
                        hidden.value = p.id; input.value = p.nombre; lista.style.display = 'none';
                    });
                    lista.appendChild(btn);
                });
                lista.style.display = 'block';
            });
    }

    window.limpiarPaciente = function () {
        hidden.value = ''; input.value = ''; input.readOnly = false; input.focus();
    };

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    window.actualizarVacio = actualizarVacio;
})();
</script>
