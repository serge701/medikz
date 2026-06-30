<?php
$estado    = $clinica['estado_saas'] ?? 'trial';
$trialFin  = $clinica['trial_ends_at'] ?? null;
$susHasta  = $clinica['suscripcion_hasta'] ?? null;

$diasTrial = 0;
if ($estado === 'trial' && $trialFin) {
    $hoy       = new DateTimeImmutable(date('Y-m-d'));
    $finDt     = new DateTimeImmutable($trialFin);
    $diasTrial = $finDt >= $hoy ? (int) $hoy->diff($finDt)->days + 1 : 0;
}

// Precios (en pesos)
$cfg         = \App\Core\Config::get('stripe');
$precioMes   = ($clinica['precio_mensual'] ?? null) !== null
    ? $clinica['precio_mensual'] / 100
    : $cfg['precio_mxn'] / 100;
$precioAnual = ($clinica['precio_anual'] ?? null) !== null
    ? $clinica['precio_anual'] / 100
    : $cfg['precio_anual'] / 100;
$ahorro      = ($precioMes * 12) - $precioAnual;

// Fechas proyectadas si se contrata hoy (o se extiende desde susHasta si es futura)
$baseExtension  = ($susHasta && $susHasta > date('Y-m-d'))
    ? new DateTimeImmutable($susHasta)
    : new DateTimeImmutable();
$proyecMensual  = $baseExtension->modify('+1 month')->format('d/m/Y');
$proyecAnual    = $baseExtension->modify('+1 year')->format('d/m/Y');
?>

<style>
.plan-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.5rem; }
@media (max-width: 640px) { .plan-cards { grid-template-columns: 1fr; } }

.plan-card {
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.5rem;
    background: #fff;
    position: relative;
    transition: border-color .15s, box-shadow .15s;
}
.plan-card.plan-anual {
    border-color: #2563eb;
    box-shadow: 0 4px 20px rgba(37,99,235,.12);
}

.plan-badge {
    position: absolute;
    top: -13px; left: 50%; transform: translateX(-50%);
    background: linear-gradient(135deg,#2563eb,#4e9af1);
    color: #fff; font-size: .7rem; font-weight: 800;
    letter-spacing: .06em; text-transform: uppercase;
    padding: 3px 14px; border-radius: 20px;
    white-space: nowrap;
}

.plan-name   { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #64748b; margin-bottom: .5rem; }
.plan-price  { font-size: 2.4rem; font-weight: 900; color: #0f1724; line-height: 1; }
.plan-price sup { font-size: 1rem; font-weight: 700; vertical-align: super; }
.plan-period { font-size: .8rem; color: #94a3b8; margin-top: 3px; }
.plan-equiv  { font-size: .78rem; color: #4ade80; font-weight: 700; margin-top: 4px; }

.plan-divider { border: none; border-top: 1px solid #f1f5f9; margin: 1.1rem 0; }

.plan-feature {
    display: flex; align-items: center; gap: 7px;
    font-size: .82rem; color: #475569; margin-bottom: .45rem;
}
.plan-feature i { color: #22c55e; font-size: .85rem; flex-shrink: 0; }

.plan-btn {
    display: block; width: 100%; text-align: center;
    padding: .65rem; border-radius: 10px;
    font-weight: 700; font-size: .88rem;
    margin-top: 1.1rem; border: none; cursor: pointer;
    transition: opacity .15s, transform .12s;
}
.plan-btn:hover { opacity: .9; transform: translateY(-1px); }
.plan-btn-mensual { background: #f1f5f9; color: #334155; }
.plan-btn-anual   { background: linear-gradient(135deg,#2563eb,#4e9af1); color: #fff;
                    box-shadow: 0 3px 10px rgba(37,99,235,.3); }
.plan-btn-active  { background: #dcfce7; color: #15803d; cursor: default; }

.ahorro-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: #fefce8; border: 1px solid #fde047;
    color: #854d0e; border-radius: 6px;
    font-size: .73rem; font-weight: 700;
    padding: 2px 8px; margin-top: 6px;
}
</style>

<div class="row justify-content-center">
<div class="col-lg-8">

<!-- Banner de estado -->
<?php if ($estado === 'suspendido'): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-octagon-fill fs-4"></i>
    <div>
        <strong>Tu cuenta está suspendida.</strong>
        Suscríbete para volver a usar Medikz con todos tus datos intactos.
    </div>
</div>
<?php elseif ($estado === 'trial' && $diasTrial <= 3 && $diasTrial > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-clock-history fs-4"></i>
    <div>
        <strong>Tu período de prueba termina en <?= $diasTrial ?> día<?= $diasTrial !== 1 ? 's' : '' ?>.</strong>
        Suscríbete ahora para no perder el acceso.
    </div>
</div>
<?php elseif ($estado === 'activo'): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-check-circle-fill fs-4"></i>
    <div>
        <strong>Suscripción activa</strong> hasta
        <?= $susHasta ? date('d/m/Y', strtotime($susHasta)) : '—' ?>.
    </div>
</div>
<?php endif ?>

<!-- Encabezado -->
<div class="text-center mb-4">
    <h5 class="fw-bold mb-1">Elige tu plan</h5>
    <p class="text-muted small mb-0">Sin contratos. Sin sorpresas. Cancela cuando quieras.</p>
</div>

<!-- Tarjetas de planes -->
<div class="plan-cards">

    <!-- Plan Mensual -->
    <div class="plan-card">
        <div class="plan-name">Plan Mensual</div>
        <div class="plan-price"><sup>$</sup><?= number_format($precioMes, 0, '.', ',') ?></div>
        <div class="plan-period">MXN / mes · IVA incluido</div>
        <hr class="plan-divider">
        <div class="plan-feature"><i class="bi bi-check-circle-fill"></i> Acceso completo a todas las funciones</div>
        <div class="plan-feature"><i class="bi bi-check-circle-fill"></i> Sin límite de pacientes ni consultas</div>
        <div class="plan-feature"><i class="bi bi-check-circle-fill"></i> Soporte incluido</div>
        <div class="plan-feature"><i class="bi bi-check-circle-fill"></i> Cancela en cualquier momento</div>

        <form method="post" action="<?= url('suscripcion/checkout') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="plan" value="mensual">
            <button type="submit" class="plan-btn plan-btn-mensual">
                <i class="bi bi-stripe me-1"></i>
                <?= $estado === 'activo' ? 'Renovar mensual' : 'Suscribirme mensual' ?>
            </button>
        </form>
        <div class="text-center text-muted mt-2" style="font-size:.75rem">
            Acceso hasta el <strong><?= $proyecMensual ?></strong>
        </div>
    </div>

    <!-- Plan Anual -->
    <div class="plan-card plan-anual">
        <div class="plan-badge">⭐ Más popular · Ahorra <?= number_format($ahorro, 0, '.', ',') ?></div>
        <div class="plan-name" style="color:#2563eb">Plan Anual</div>
        <div class="plan-price" style="color:#1d4ed8"><sup>$</sup><?= number_format($precioAnual, 0, '.', ',') ?></div>
        <div class="plan-period">MXN / año · IVA incluido</div>
        <div class="ahorro-chip">
            <i class="bi bi-tag-fill"></i>
            Equivale a $<?= number_format($precioAnual / 12, 0, '.', ',') ?>/mes
            · Ahorras $<?= number_format($ahorro, 0, '.', ',') ?> al año
        </div>
        <hr class="plan-divider">
        <div class="plan-feature"><i class="bi bi-check-circle-fill"></i> Todo lo del plan mensual</div>
        <div class="plan-feature"><i class="bi bi-check-circle-fill"></i> Sin cobros durante 12 meses</div>
        <div class="plan-feature"><i class="bi bi-check-circle-fill"></i> Precio congelado por un año</div>
        <div class="plan-feature"><i class="bi bi-check-circle-fill" style="color:#2563eb"></i>
            <strong>2 meses gratis vs plan mensual</strong>
        </div>

        <form method="post" action="<?= url('suscripcion/checkout') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="plan" value="anual">
            <button type="submit" class="plan-btn plan-btn-anual">
                <i class="bi bi-stripe me-1"></i>
                <?= $estado === 'activo' ? 'Cambiar a anual' : 'Suscribirme anual' ?>
            </button>
        </form>
        <div class="text-center text-muted mt-2" style="font-size:.75rem">
            Acceso hasta el <strong><?= $proyecAnual ?></strong>
        </div>
    </div>

</div>

<!-- Seguridad -->
<div class="text-center text-muted small mb-4">
    <i class="bi bi-lock me-1"></i> Pago 100% seguro procesado por Stripe
    &nbsp;·&nbsp; <i class="bi bi-shield-check me-1"></i> Sin guardar datos de tarjeta
</div>

<!-- Beneficios -->
<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold">
        <i class="bi bi-grid me-1"></i> Todo incluido en ambos planes
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php
            $beneficios = [
                ['bi-calendar2-check',    'primary',   'Agenda inteligente',         'Sin doble reservación: detecta conflictos de horario en tiempo real.'],
                ['bi-folder2-open',       'success',   'Expediente clínico digital', 'Historial completo de consultas, diagnósticos, signos vitales y notas por paciente.'],
                ['bi-file-earmark-text',  'info',      'Recetas con QR y PDF',       'Cumple NOM-004-SSA3-2012. Código QR de verificación de autenticidad.'],
                ['bi-whatsapp',           'success',   'Recordatorios por WhatsApp', 'Confirmación automática al crear la cita y aviso 24 h antes vía WhatsApp.'],
                ['bi-capsule',            'warning',   'Catálogo de medicamentos',   'Más de 160 medicamentos con autocompletado y dosis prellenada al prescribir.'],
                ['bi-graph-up-arrow',     'danger',    'Métricas del consultorio',   'Dashboard con pacientes nuevos, ingresos, ocupación de agenda y más.'],
                ['bi-people',             'secondary', 'Multi-usuario y roles',      'Médicos, recepcionistas y administradores con permisos diferenciados.'],
                ['bi-shield-check',       'dark',      'Auditoría completa',         'Cada acción queda registrada: quién hizo qué y cuándo, para mayor seguridad.'],
            ];
            foreach ($beneficios as [$icon, $color, $titulo, $desc]):
            ?>
            <div class="col-sm-6">
                <div class="d-flex gap-3 align-items-start">
                    <div class="flex-shrink-0 rounded-2 d-flex align-items-center justify-content-center text-<?= $color ?> bg-<?= $color ?>-subtle"
                         style="width:38px;height:38px;">
                        <i class="bi <?= $icon ?> fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-semibold small"><?= $titulo ?></div>
                        <div class="text-muted" style="font-size:.8rem"><?= $desc ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</div>

<!-- Historial de pagos -->
<?php if ($historial): ?>
<div class="card shadow-sm">
    <div class="card-header fw-semibold">
        <i class="bi bi-receipt me-1"></i> Historial de pagos
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Fecha</th>
                    <th>Concepto</th>
                    <th>Período</th>
                    <th class="text-end">Monto</th>
                    <th class="pe-3">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $p): ?>
                <tr>
                    <td class="ps-3"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                    <td class="small"><?= e($p['concepto'] ?: 'Suscripción Medikz') ?></td>
                    <td class="small">
                        <?php if ($p['periodo_inicio']): ?>
                            <?= date('d/m/Y', strtotime($p['periodo_inicio'])) ?>
                            – <?= date('d/m/Y', strtotime($p['periodo_fin'])) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif ?>
                    </td>
                    <td class="text-end">$<?= number_format((float)$p['monto'], 2) ?></td>
                    <td class="pe-3">
                        <?php if ($p['estado'] === 'completado'): ?>
                            <span class="badge text-bg-success">Pagado</span>
                        <?php elseif ($p['estado'] === 'pendiente'): ?>
                            <span class="badge text-bg-warning text-dark">Pendiente</span>
                        <?php else: ?>
                            <span class="badge text-bg-danger">Fallido</span>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

</div><!-- col -->
</div><!-- row -->
