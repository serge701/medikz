<?php
$u = auth();
$puedeClinico = \App\Core\Auth::puedeVerClinico();
?>
<aside class="app-sidebar shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?= url('dashboard') ?>" class="brand-link d-flex align-items-center">
            <img src="<?= asset('img/medikz_logo_w.png') ?>"
                 alt="Medikz"
                 style="height:30px;width:auto;max-width:140px;object-fit:contain;">
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation">

                <li class="nav-item">
                    <a href="<?= url('dashboard') ?>" class="nav-link <?= active('dashboard') ?>">
                        <i class="nav-icon bi bi-speedometer2"></i>
                        <p>Inicio</p>
                    </a>
                </li>

                <li class="nav-header">GESTIÓN</li>

                <li class="nav-item">
                    <a href="<?= url('pacientes') ?>" class="nav-link <?= active('pacientes') ?>">
                        <i class="nav-icon bi bi-people"></i>
                        <p>Pacientes</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= url('agenda') ?>" class="nav-link <?= active('agenda') ?>">
                        <i class="nav-icon bi bi-calendar-week"></i>
                        <p>Agenda y citas</p>
                    </a>
                </li>

                <?php if ($puedeClinico): ?>
                <li class="nav-header">CLÍNICO</li>

                <li class="nav-item">
                    <a href="<?= url('consultas') ?>" class="nav-link <?= active('consultas') ?>">
                        <i class="nav-icon bi bi-clipboard2-pulse"></i>
                        <p>Historial clínico</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= url('recetas') ?>" class="nav-link <?= active('recetas') ?>">
                        <i class="nav-icon bi bi-prescription2"></i>
                        <p>Recetas</p>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-header">ADMINISTRACIÓN</li>

                <li class="nav-item">
                    <a href="<?= url('cobros') ?>" class="nav-link <?= active('cobros') ?>">
                        <i class="nav-icon bi bi-cash-coin"></i>
                        <p>Cobros</p>
                    </a>
                </li>

                <?php if (\App\Core\Auth::is('admin_clinica') || \App\Core\Auth::esPropietario()): ?>
                <li class="nav-item">
                    <a href="<?= url('metricas') ?>" class="nav-link <?= active('metricas') ?>">
                        <i class="nav-icon bi bi-graph-up"></i>
                        <p>Métricas</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('usuarios') ?>" class="nav-link <?= active('usuarios') ?>">
                        <i class="nav-icon bi bi-person-gear"></i>
                        <p>Usuarios</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (\App\Core\Auth::esSuperAdmin()): ?>
                <li class="nav-header">SUPER ADMIN</li>
                <li class="nav-item">
                    <a href="<?= url('superadmin') ?>" class="nav-link <?= active('superadmin') ?>">
                        <i class="nav-icon bi bi-buildings"></i>
                        <p>Clínicas</p>
                    </a>
                </li>
                <?php endif; ?>

            </ul>
        </nav>
    </div>
</aside>
