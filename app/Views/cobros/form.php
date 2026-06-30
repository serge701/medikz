<?php
/** @var array<string,mixed>|null $cobro  null = nuevo */
/** @var array<string,mixed>|null $paciente */
/** @var int|null $citaId */
/** @var int|null $consultaId */
/** @var array<string,string> $errores */

$esEditar   = $cobro !== null;
$action     = $esEditar ? url('cobros/' . $cobro['id']) : url('cobros');
$pacienteId = $cobro['paciente_id'] ?? ($paciente['id'] ?? '');
$hoy        = date('Y-m-d');
$fechaCobro = old('fecha_cobro', $cobro['fecha_cobro'] ?? $hoy);
$concepto   = old('concepto',   $cobro['concepto']    ?? '');
$monto      = old('monto',      (string)($cobro['monto'] ?? ''));
$metodoPago = old('metodo_pago',$cobro['metodo_pago'] ?? 'efectivo');
$estado     = old('estado',     $cobro['estado']      ?? 'pagado');
$notas      = old('notas',      $cobro['notas']       ?? '');
$citaIdVal  = $citaId     ? (string)$citaId     : '';
$consIdVal  = $consultaId ? (string)$consultaId : '';

// Nombre completo del paciente pre-cargado
$pacNombre  = $paciente ? nombre_completo($paciente) : '';
$pacEdad    = $paciente && !empty($paciente['fecha_nacimiento']) ? edad_anios($paciente['fecha_nacimiento']) . ' años' : '';
$pacTel     = $paciente['telefono'] ?? '';

$metodos = [
    'efectivo'      => ['label' => 'Efectivo',      'icon' => 'bi-cash-stack'],
    'tarjeta'       => ['label' => 'Tarjeta',        'icon' => 'bi-credit-card'],
    'transferencia' => ['label' => 'Transferencia',  'icon' => 'bi-bank'],
    'cheque'        => ['label' => 'Cheque',          'icon' => 'bi-file-earmark-text'],
];

$conceptosOpciones = [
    // Consultas
    'Consulta general',
    'Consulta de seguimiento',
    'Consulta de urgencia',
    'Primera consulta',
    'Consulta de nutrición',
    'Psicología',
    // Estudios
    'Laboratorio',
    'Radiografía',
    'Ultrasonido',
    'Electrocardiograma',
    'Tomografía',
    // Procedimientos
    'Procedimiento menor',
    'Curación',
    'Sutura',
    'Inyección / aplicación',
    'Vacuna',
    // Productos
    'Medicamento',
    'Material de curación',
    // Otros
    'Otro',
];
?>

<style>
/* ── contenedor ── */
.cobro-form-wrap { max-width: 680px; margin: 0 auto; }

/* ── cards ── */
.cobro-card {
    background: #fff;
    border: 1px solid #e9eef5;
    border-radius: 14px;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.cobro-card-header {
    border-radius: 14px 14px 0 0;
}
.cobro-card-header {
    padding: .7rem 1.2rem;
    background: #f8faff;
    border-bottom: 1px solid #edf1f9;
    font-size: .8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.cobro-card-body { padding: 1.2rem; }

/* ── buscador de paciente ── */
#pac-search-wrap { position: relative; }
#pac_buscar {
    border-radius: 10px;
    border: 1.5px solid #cbd5e1;
    padding: .6rem 1rem;
    font-size: .9rem;
    transition: border-color .15s, box-shadow .15s;
}
#pac_buscar:focus {
    border-color: #4e9af1;
    box-shadow: 0 0 0 3px rgba(78,154,241,.15);
    outline: none;
}
#pac_resultados {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 4px 16px rgba(0,0,0,.10);
    overflow: hidden;
    position: absolute;
    width: 100%;
    z-index: 99;
    background: #fff;
    margin-top: 4px;
    display: none;
}
#pac_resultados .pac-item {
    padding: .65rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    transition: background .1s;
}
#pac_resultados .pac-item:last-child { border-bottom: none; }
#pac_resultados .pac-item:hover { background: #f0f6ff; }
#pac_resultados .pac-item .pac-name { font-weight: 600; font-size: .88rem; color: #1e293b; }
#pac_resultados .pac-item .pac-meta { font-size: .75rem; color: #94a3b8; margin-top: 1px; }
#pac_resultados .pac-empty { padding: .7rem 1rem; font-size: .83rem; color: #94a3b8; text-align: center; }

/* ── chip paciente seleccionado ── */
#pac-chip {
    display: none;
    align-items: center;
    gap: .9rem;
    background: #f0fdf4;
    border: 1.5px solid #86efac;
    border-radius: 10px;
    padding: .75rem 1rem;
}
#pac-chip.show { display: flex; }
#pac-chip .pac-chip-icon {
    width: 36px; height: 36px;
    background: #22c55e;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #fff; font-size: 1rem;
}
#pac-chip .pac-chip-body { flex-grow: 1; min-width: 0; }
#pac-chip .pac-chip-name { font-weight: 700; font-size: .9rem; color: #15803d; }
#pac-chip .pac-chip-meta { font-size: .77rem; color: #4ade80; margin-top: 1px; }
#pac-chip .pac-chip-change {
    font-size: .75rem; color: #64748b; cursor: pointer;
    background: none; border: 1px solid #cbd5e1;
    border-radius: 6px; padding: 3px 10px;
    white-space: nowrap; flex-shrink: 0;
    transition: background .12s;
}
#pac-chip .pac-chip-change:hover { background: #f1f5f9; }

/* ── monto ── */
.monto-wrap .input-group-text {
    background: #f8faff;
    border-color: #cbd5e1;
    color: #64748b;
    font-weight: 700;
    font-size: 1rem;
    border-radius: 10px 0 0 10px;
}
.monto-wrap input {
    font-size: 1.4rem;
    font-weight: 700;
    color: #0f1724;
    border-color: #cbd5e1;
    border-radius: 0 10px 10px 0;
    padding: .55rem .9rem;
}
.monto-wrap input:focus {
    border-color: #4e9af1;
    box-shadow: 0 0 0 3px rgba(78,154,241,.15);
}

/* ── método de pago: radio buttons estilizados ── */
.metodo-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .5rem;
}
@media (max-width: 540px) { .metodo-grid { grid-template-columns: repeat(2, 1fr); } }
.metodo-btn input[type=radio] { display: none; }
.metodo-btn label {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 4px;
    padding: .6rem .3rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    font-size: .75rem;
    font-weight: 600;
    color: #64748b;
    background: #fff;
    transition: all .14s;
    text-align: center;
    line-height: 1.2;
}
.metodo-btn label i { font-size: 1.1rem; }
.metodo-btn input:checked + label {
    border-color: #4e9af1;
    background: #eff6ff;
    color: #2563eb;
}

/* ── estado chips ── */
.estado-chips { display: flex; gap: .5rem; flex-wrap: wrap; }
.estado-chip input[type=radio] { display: none; }
.estado-chip label {
    padding: .35rem .9rem;
    border-radius: 20px;
    border: 1.5px solid #e2e8f0;
    font-size: .8rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all .12s;
}
.estado-chip.pagado    input:checked + label { background: #dcfce7; border-color: #86efac; color: #15803d; }
.estado-chip.pendiente input:checked + label { background: #fef9c3; border-color: #fde047; color: #854d0e; }
.estado-chip.cancelado input:checked + label { background: #fee2e2; border-color: #fca5a5; color: #b91c1c; }

/* ── inputs genéricos ── */
.cobro-card-body .form-control,
.cobro-card-body .form-select {
    border-radius: 10px;
    border-color: #cbd5e1;
    font-size: .88rem;
    transition: border-color .15s, box-shadow .15s;
}
.cobro-card-body .form-control:focus,
.cobro-card-body .form-select:focus {
    border-color: #4e9af1;
    box-shadow: 0 0 0 3px rgba(78,154,241,.15);
}
.cobro-form-label {
    font-size: .78rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: .4rem;
    display: block;
}

/* ── botones de acción ── */
.cobro-actions {
    display: flex; gap: .75rem; align-items: center;
    padding-top: .25rem;
}
.btn-cobro-submit {
    background: linear-gradient(135deg, #2563eb, #4e9af1);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-weight: 700;
    padding: .65rem 1.6rem;
    font-size: .9rem;
    box-shadow: 0 2px 8px rgba(37,99,235,.25);
    transition: opacity .15s;
}
.btn-cobro-submit:hover { opacity: .9; color: #fff; }
</style>

<div class="cobro-form-wrap">

    <!-- Encabezado -->
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="<?= url('cobros') ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
            <i class="bi bi-arrow-left"></i>
        </a>
        <?php if ($esEditar && !empty($cobro['pac_nombre'])): ?>
            <span class="fw-semibold">
                <?= e(trim($cobro['pac_nombre'] . ' ' . ($cobro['pac_ap'] ?? ''))) ?>
            </span>
        <?php endif ?>
    </div>

    <?php if ($err = get_flash('error')): ?>
        <div class="alert alert-danger rounded-3"><?= e($err) ?></div>
    <?php endif ?>

    <?php if ($errores !== []): ?>
        <div class="alert alert-danger rounded-3">
            <ul class="mb-0 ps-3">
                <?php foreach ($errores as $msg): ?>
                    <li><?= e($msg) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <form method="POST" action="<?= $action ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="cita_id"     value="<?= e($citaIdVal) ?>">
        <input type="hidden" name="consulta_id" value="<?= e($consIdVal) ?>">
        <input type="hidden" id="paciente_id"   name="paciente_id" value="<?= e((string)$pacienteId) ?>">

        <!-- ── Paciente ── -->
        <div class="cobro-card">
            <div class="cobro-card-header">
                <i class="bi bi-person-circle"></i> Paciente
            </div>
            <div class="cobro-card-body">

                <!-- Chip: paciente ya seleccionado -->
                <div id="pac-chip" class="<?= ($paciente || $esEditar) ? 'show' : '' ?>">
                    <div class="pac-chip-icon"><i class="bi bi-check-lg"></i></div>
                    <div class="pac-chip-body">
                        <div class="pac-chip-name" id="pac-chip-name"><?= e($pacNombre) ?></div>
                        <div class="pac-chip-meta" id="pac-chip-meta">
                            <?php
                            $metaPartes = array_filter([$pacEdad, $pacTel ? '📞 ' . $pacTel : '']);
                            echo e(implode('  ·  ', $metaPartes));
                            ?>
                        </div>
                    </div>
                    <?php if (!$esEditar): ?>
                        <button type="button" class="pac-chip-change" id="pac-chip-change">
                            <i class="bi bi-pencil me-1"></i>Cambiar
                        </button>
                    <?php endif ?>
                </div>

                <!-- Buscador -->
                <div id="pac-search-wrap" <?= ($paciente || $esEditar) ? 'style="display:none"' : '' ?>>
                    <input type="text" id="pac_buscar" class="form-control w-100"
                           placeholder="Buscar por nombre o teléfono..."
                           autocomplete="off">
                    <div id="pac_resultados"></div>
                </div>

                <?php if (isset($errores['paciente_id'])): ?>
                    <div class="text-danger mt-1" style="font-size:.82rem">
                        <i class="bi bi-exclamation-circle me-1"></i><?= e($errores['paciente_id']) ?>
                    </div>
                <?php endif ?>
            </div>
        </div>

        <!-- ── Detalle del cobro ── -->
        <div class="cobro-card">
            <div class="cobro-card-header">
                <i class="bi bi-receipt"></i> Detalle del cobro
            </div>
            <div class="cobro-card-body">

                <div class="row g-3 mb-3">
                    <div class="col-sm-5">
                        <label class="cobro-form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_cobro"
                               class="form-control <?= isset($errores['fecha_cobro']) ? 'is-invalid' : '' ?>"
                               value="<?= e($fechaCobro) ?>" required>
                        <?php if (isset($errores['fecha_cobro'])): ?>
                            <div class="invalid-feedback"><?= e($errores['fecha_cobro']) ?></div>
                        <?php endif ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="cobro-form-label">Concepto <span class="text-danger">*</span></label>
                    <select name="concepto"
                            class="form-select <?= isset($errores['concepto']) ? 'is-invalid' : '' ?>"
                            required>
                        <option value="">-- Selecciona un concepto --</option>
                        <?php foreach ($conceptosOpciones as $op): ?>
                            <option value="<?= e($op) ?>" <?= $concepto === $op ? 'selected' : '' ?>>
                                <?= e($op) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <?php if (isset($errores['concepto'])): ?>
                        <div class="invalid-feedback"><?= e($errores['concepto']) ?></div>
                    <?php endif ?>
                </div>

                <div class="mb-4">
                    <label class="cobro-form-label">Monto <span class="text-danger">*</span></label>
                    <div class="input-group monto-wrap">
                        <span class="input-group-text">$</span>
                        <input type="number" name="monto"
                               class="form-control <?= isset($errores['monto']) ? 'is-invalid' : '' ?>"
                               value="<?= e($monto) ?>"
                               min="0" step="0.01" placeholder="0.00" required>
                        <?php if (isset($errores['monto'])): ?>
                            <div class="invalid-feedback"><?= e($errores['monto']) ?></div>
                        <?php endif ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="cobro-form-label">Método de pago</label>
                    <div class="metodo-grid">
                        <?php foreach ($metodos as $val => $info): ?>
                            <div class="metodo-btn">
                                <input type="radio" name="metodo_pago"
                                       id="mp_<?= $val ?>" value="<?= $val ?>"
                                       <?= $metodoPago === $val ? 'checked' : '' ?>>
                                <label for="mp_<?= $val ?>">
                                    <i class="bi <?= $info['icon'] ?>"></i>
                                    <?= $info['label'] ?>
                                </label>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="cobro-form-label">Estado</label>
                    <div class="estado-chips">
                        <?php foreach (['pagado' => 'Pagado', 'pendiente' => 'Pendiente', 'cancelado' => 'Cancelado'] as $v => $l): ?>
                            <div class="estado-chip <?= $v ?>">
                                <input type="radio" name="estado" id="est_<?= $v ?>" value="<?= $v ?>"
                                       <?= $estado === $v ? 'checked' : '' ?>>
                                <label for="est_<?= $v ?>"><?= $l ?></label>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>

                <div>
                    <label class="cobro-form-label">Notas <span class="text-muted fw-normal">(opcional)</span></label>
                    <textarea name="notas" class="form-control" rows="2"
                              placeholder="Observaciones internas..."><?= e($notas) ?></textarea>
                </div>

            </div>
        </div>

        <div class="cobro-actions">
            <button type="submit" class="btn btn-cobro-submit">
                <i class="bi bi-check-lg me-1"></i>
                <?= $esEditar ? 'Guardar cambios' : 'Registrar cobro' ?>
            </button>
            <a href="<?= url('cobros') ?>" class="btn btn-outline-secondary" style="border-radius:10px">
                Cancelar
            </a>
        </div>

    </form>
</div>

<script>
(function () {
    const hiddenId    = document.getElementById('paciente_id');
    const searchWrap  = document.getElementById('pac-search-wrap');
    const searchInput = document.getElementById('pac_buscar');
    const results     = document.getElementById('pac_resultados');
    const chip        = document.getElementById('pac-chip');
    const chipName    = document.getElementById('pac-chip-name');
    const chipMeta    = document.getElementById('pac-chip-meta');
    const chipChange  = document.getElementById('pac-chip-change');

    if (!searchInput) return; // modo editar: solo chip, sin buscador

    function mostrarChip(id, nombre, meta) {
        hiddenId.value   = id;
        chipName.textContent = nombre;
        chipMeta.textContent = meta;
        chip.classList.add('show');
        searchWrap.style.display = 'none';
        results.style.display    = 'none';
        searchInput.value        = '';
    }

    function mostrarBuscador() {
        hiddenId.value = '';
        chip.classList.remove('show');
        searchWrap.style.display = '';
        searchInput.value = '';
        searchInput.focus();
    }

    if (chipChange) {
        chipChange.addEventListener('click', mostrarBuscador);
    }

    let timer;
    searchInput.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { results.style.display = 'none'; return; }

        timer = setTimeout(() => {
            fetch('<?= url('pacientes/buscar') ?>?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    results.innerHTML = '';
                    if (!data.length) {
                        results.innerHTML = '<div class="pac-empty"><i class="bi bi-search me-1"></i>Sin resultados</div>';
                        results.style.display = 'block';
                        return;
                    }
                    data.forEach(p => {
                        const meta = [
                            p.edad   ? p.edad : '',
                            p.telefono ? '📞 ' + p.telefono : '',
                        ].filter(Boolean).join('  ·  ');

                        const item = document.createElement('div');
                        item.className = 'pac-item';
                        item.innerHTML =
                            '<div class="pac-name">' + escHtml(p.nombre) + '</div>' +
                            (meta ? '<div class="pac-meta">' + escHtml(meta) + '</div>' : '');

                        item.addEventListener('click', () => {
                            mostrarChip(p.id, p.nombre, meta);
                        });
                        results.appendChild(item);
                    });
                    results.style.display = 'block';
                })
                .catch(() => { results.style.display = 'none'; });
        }, 250);
    });

    document.addEventListener('click', e => {
        if (!results.contains(e.target) && e.target !== searchInput) {
            results.style.display = 'none';
        }
    });

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g,
            c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
})();
</script>
