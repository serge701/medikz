<?php
// $consultas, $paciente, $desde, $hasta, $filtroActivo

// Agrupar por fecha_consulta
$agrupadas = [];
foreach ($consultas as $c) {
    $agrupadas[$c['fecha_consulta']][] = $c;
}

// Etiqueta de fecha para encabezado de sección
function etiquetaFecha(string $fecha): string {
    $hoy  = date('Y-m-d');
    $ayer = date('Y-m-d', strtotime('-1 day'));
    $dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $meses = ['','enero','febrero','marzo','abril','mayo','junio','julio',
              'agosto','septiembre','octubre','noviembre','diciembre'];
    $ts  = strtotime($fecha);
    $dow = $dias[(int) date('w', $ts)];
    $dia = (int) date('j', $ts);
    $mes = $meses[(int) date('n', $ts)];
    $anio = (int) date('Y', $ts);
    $anioActual = (int) date('Y');
    $sufijo = ($anio !== $anioActual) ? " de $anio" : '';

    if ($fecha === $hoy)  return "Hoy · " . ucfirst($dow) . " $dia de $mes$sufijo";
    if ($fecha === $ayer) return "Ayer · " . ucfirst($dow) . " $dia de $mes$sufijo";
    return ucfirst($dow) . " $dia de $mes$sufijo";
}

function horaCorta(?string $hora): string {
    if (!$hora) return '';
    $partes = explode(':', $hora);
    $h = (int) $partes[0];
    $m = $partes[1] ?? '00';
    return $m === '00' ? "{$h}:00" : "{$h}:{$m}";
}
?>

<style>
.tl-filter-bar {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: .9rem 1.2rem;
    margin-bottom: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: .75rem;
}
.tl-filter-bar label { font-size: .78rem; font-weight: 600; color: #64748b; margin-bottom: 3px; display: block; }
.tl-filter-bar .form-control { font-size: .85rem; border-radius: 8px; border-color: #cbd5e1; max-width: 150px; }
.tl-filter-count { font-size: .8rem; color: #64748b; margin-left: auto; align-self: center; }

.tl-date-header {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #94a3b8;
    padding: .25rem 0;
    margin: 1.5rem 0 .6rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.tl-date-header:first-of-type { margin-top: 0; }
.tl-date-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #cbd5e1; flex-shrink: 0;
}

.tl-card {
    background: #fff;
    border: 1px solid #e9eef5;
    border-radius: 10px;
    padding: .8rem 1rem;
    margin-bottom: .5rem;
    display: flex;
    align-items: flex-start;
    gap: .9rem;
    transition: border-color .15s, box-shadow .15s;
    text-decoration: none;
    color: inherit;
}
.tl-card:hover {
    border-color: #4e9af1;
    box-shadow: 0 2px 8px rgba(78,154,241,.12);
    color: inherit;
}

.tl-time {
    min-width: 52px;
    text-align: center;
    flex-shrink: 0;
    padding-top: 2px;
}
.tl-time-badge {
    display: inline-block;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 6px;
    font-size: .72rem;
    font-weight: 700;
    padding: 3px 7px;
    white-space: nowrap;
}
.tl-time-icon {
    color: #cbd5e1;
    font-size: 1rem;
}

.tl-body { flex-grow: 1; min-width: 0; }
.tl-name {
    font-weight: 600;
    font-size: .9rem;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tl-motivo {
    font-size: .8rem;
    color: #64748b;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tl-dx {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 5px;
    font-size: .73rem;
    background: #f8faff;
    border: 1px solid #dbeafe;
    color: #3b82f6;
    border-radius: 5px;
    padding: 2px 7px;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.tl-meta {
    flex-shrink: 0;
    text-align: right;
    min-width: 130px;
    font-size: .77rem;
    color: #94a3b8;
}
.tl-meta-doc { display: flex; align-items: center; justify-content: flex-end; gap: 4px; margin-bottom: 6px; }
.tl-arrow { color: #cbd5e1; font-size: .9rem; }
</style>

<!-- Cabecera + botón nueva consulta -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <?php if ($paciente): ?>
            <a href="<?= url('consultas') ?>" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left"></i> Todas las consultas
            </a>
            <div class="mt-1 fw-semibold">
                <i class="bi bi-person me-1"></i><?= e(nombre_completo($paciente)) ?>
            </div>
        <?php else: ?>
            <h6 class="mb-0 fw-bold text-body">Historial clínico</h6>
        <?php endif; ?>
    </div>
    <a href="<?= url('consultas/nueva' . ($paciente ? '?paciente_id=' . $paciente['id'] : '')) ?>"
       class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Nueva consulta
    </a>
</div>

<!-- Barra de filtros por rango de fechas (solo si no hay filtro de paciente) -->
<?php if (!$paciente): ?>
<form method="get" action="<?= url('consultas') ?>" class="tl-filter-bar">
    <div>
        <label for="tl-desde">Desde</label>
        <input type="date" id="tl-desde" name="desde" class="form-control form-control-sm"
               value="<?= e($desde) ?>">
    </div>
    <div>
        <label for="tl-hasta">Hasta</label>
        <input type="date" id="tl-hasta" name="hasta" class="form-control form-control-sm"
               value="<?= e($hasta) ?>">
    </div>
    <div class="d-flex gap-2 align-self-end">
        <button type="submit" class="btn btn-sm btn-primary" style="border-radius:8px">
            <i class="bi bi-search me-1"></i>Filtrar
        </button>
        <?php if ($filtroActivo): ?>
            <a href="<?= url('consultas') ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
                Limpiar
            </a>
        <?php endif; ?>
    </div>
    <div class="tl-filter-count">
        <?= count($consultas) ?> consulta<?= count($consultas) !== 1 ? 's' : '' ?>
    </div>
</form>
<?php endif; ?>

<!-- Timeline -->
<?php if (empty($agrupadas)): ?>
    <div class="card text-center py-5">
        <div class="card-body text-muted">
            <i class="bi bi-clipboard2-x fs-1 d-block mb-2 opacity-50"></i>
            <p class="mb-3">
                <?php if ($filtroActivo): ?>
                    No hay consultas en el rango seleccionado.
                <?php else: ?>
                    No hay consultas registradas<?= $paciente ? ' para este paciente' : '' ?>.
                <?php endif; ?>
            </p>
            <a href="<?= url('consultas/nueva' . ($paciente ? '?paciente_id=' . $paciente['id'] : '')) ?>"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Registrar primera consulta
            </a>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($agrupadas as $fecha => $grupo): ?>

        <div class="tl-date-header">
            <span class="tl-date-dot"></span>
            <?= e(etiquetaFecha($fecha)) ?>
            <span class="ms-auto" style="font-size:.7rem;font-weight:600;color:#cbd5e1">
                <?= count($grupo) ?> consulta<?= count($grupo) !== 1 ? 's' : '' ?>
            </span>
        </div>

        <?php foreach ($grupo as $c): ?>
            <?php
            $nombrePac = trim($c['pac_nombre'] . ' ' . $c['pac_ap'] . ' ' . ($c['pac_am'] ?? ''));
            $horaStr   = $c['cita_hora'] ? horaCorta($c['cita_hora']) : null;
            $dx        = !empty($c['diagnostico']) ? mb_strimwidth($c['diagnostico'], 0, 90, '…') : null;
            ?>
            <a href="<?= url('consultas/' . $c['id']) ?>" class="tl-card">

                <!-- Hora -->
                <div class="tl-time">
                    <?php if ($horaStr): ?>
                        <span class="tl-time-badge"><?= e($horaStr) ?></span>
                    <?php else: ?>
                        <i class="bi bi-calendar3 tl-time-icon"></i>
                    <?php endif; ?>
                </div>

                <!-- Cuerpo -->
                <div class="tl-body">
                    <div class="tl-name"><?= e($nombrePac) ?></div>
                    <?php if (!empty($c['motivo_consulta'])): ?>
                        <div class="tl-motivo"><?= e($c['motivo_consulta']) ?></div>
                    <?php endif; ?>
                    <?php if ($dx): ?>
                        <div class="tl-dx">
                            <i class="bi bi-clipboard2-check"></i>
                            <?= e($dx) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Meta derecha -->
                <div class="tl-meta d-none d-md-block">
                    <?php if (!empty($c['med_nombre'])): ?>
                        <div class="tl-meta-doc">
                            <i class="bi bi-person-badge"></i>
                            <?= e($c['med_nombre']) ?>
                        </div>
                    <?php endif; ?>
                    <i class="bi bi-arrow-right tl-arrow"></i>
                </div>

            </a>
        <?php endforeach; ?>

    <?php endforeach; ?>
<?php endif; ?>
