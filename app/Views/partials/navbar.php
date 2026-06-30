<?php
$u         = auth();
$clinicaId = (int) ($u['clinica_id'] ?? 0);
$rolActual = $u['rol'] ?? '';
$puedeClinico = in_array($rolActual, ['medico', 'admin_clinica'], true);

// Trial banner
$diasRestantes = 0;
if ($clinicaId > 0) {
    $clinicaModel = new \App\Models\Clinica();
    $clinica      = $clinicaModel->find($clinicaId);
    if ($clinica && ($clinica['estado_saas'] ?? '') === 'trial') {
        $diasRestantes = \App\Core\Suscripcion::diasTrialRestantes($clinica);
    }
}

// Atajos rápidos
$atajos = [
    [
        'href'  => url('pacientes/nuevo'),
        'icon'  => 'bi-person-plus',
        'label' => 'Paciente',
        'color' => '#0d6efd',
        'text'  => '#fff',
        'show'  => true,
    ],
    [
        'href'  => url('agenda/nueva'),
        'icon'  => 'bi-calendar-plus',
        'label' => 'Cita',
        'color' => '#198754',
        'text'  => '#fff',
        'show'  => true,
    ],
    [
        'href'  => url('consultas/nueva'),
        'icon'  => 'bi-clipboard2-plus',
        'label' => 'Consulta',
        'color' => '#6f42c1',
        'text'  => '#fff',
        'show'  => $puedeClinico,
    ],
    [
        'href'  => url('recetas/nueva'),
        'icon'  => 'bi-prescription2',
        'label' => 'Receta',
        'color' => '#0d9488',
        'text'  => '#fff',
        'show'  => $puedeClinico,
    ],
    [
        'href'  => url('cobros/nuevo'),
        'icon'  => 'bi-cash-coin',
        'label' => 'Cobro',
        'color' => '#d97706',
        'text'  => '#fff',
        'show'  => true,
    ],
];
?>

<style>
.navbar-shortcut {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 11px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    transition: opacity .15s, transform .12s, box-shadow .12s;
    line-height: 1.4;
}
.navbar-shortcut:hover {
    opacity: .88;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0,0,0,.18);
}
.navbar-shortcut i { font-size: .82rem; }
</style>

<nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">

        <!-- Izquierda: hamburger + reloj -->
        <ul class="navbar-nav flex-shrink-0">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="bi bi-list"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-flex align-items-center ms-2 gap-2">
                <span id="navbar-fecha" class="text-muted small"></span>
                <span class="text-muted small opacity-50">|</span>
                <span id="navbar-reloj" class="fw-semibold small font-monospace"></span>
            </li>
        </ul>

        <script>
        (function () {
            var dias   = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
            var meses  = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
            var elFecha = document.getElementById('navbar-fecha');
            var elReloj = document.getElementById('navbar-reloj');
            function tick() {
                var d = new Date();
                elFecha.textContent = dias[d.getDay()] + ' ' + d.getDate() + ' ' + meses[d.getMonth()];
                elReloj.textContent = String(d.getHours()).padStart(2,'0') + ':'
                                    + String(d.getMinutes()).padStart(2,'0') + ':'
                                    + String(d.getSeconds()).padStart(2,'0');
            }
            tick();
            setInterval(tick, 1000);
        })();
        </script>

        <!-- Centro: atajos rápidos -->
        <ul class="navbar-nav mx-auto d-none d-lg-flex align-items-center gap-1">
            <?php foreach ($atajos as $a): ?>
                <?php if (!$a['show']) continue; ?>
                <li class="nav-item">
                    <a href="<?= $a['href'] ?>"
                       class="navbar-shortcut"
                       style="background:<?= $a['color'] ?>;color:<?= $a['text'] ?>"
                       title="<?= e($a['label']) ?>">
                        <i class="bi <?= $a['icon'] ?>"></i>
                        <span><?= e($a['label']) ?></span>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>

        <!-- Derecha: trial badge + usuario -->
        <ul class="navbar-nav flex-shrink-0">
            <?php if ($diasRestantes > 0): ?>
            <li class="nav-item d-none d-md-flex align-items-center me-2">
                <a href="<?= url('suscripcion') ?>"
                   class="btn btn-sm btn-warning px-2 py-1 text-dark fw-semibold">
                    <i class="bi bi-credit-card me-1"></i>
                    <?= $diasRestantes ?> día<?= $diasRestantes !== 1 ? 's' : '' ?> de prueba
                </a>
            </li>
            <?php endif ?>

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">
                    <i class="bi bi-person-circle"></i>
                    <span class="d-none d-sm-inline ms-1"><?= e($u['nombre'] ?? '') ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-secondary">
                        <?= e(ucfirst($u['rol'] ?? '')) ?>
                    </span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="<?= url('suscripcion') ?>">
                            <i class="bi bi-credit-card me-1"></i> Mi suscripción
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="<?= url('logout') ?>" class="px-1">
                            <?= csrf_field() ?>
                            <button class="dropdown-item text-danger" type="submit">
                                <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>

    </div>
</nav>
