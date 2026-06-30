<?php
/** @var array<int,array<string,mixed>> $lista */
?>
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0">Usuarios</h4>
        <small class="text-muted">Cuentas de acceso de tu clínica</small>
    </div>
    <a href="<?= url('usuarios/nuevo') ?>" class="btn btn-success">
        <i class="bi bi-person-plus"></i> Nuevo usuario
    </a>
</div>

<?php if (empty($lista)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-people fs-1 d-block mb-2 opacity-40"></i>
    No hay usuarios registrados.
</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th class="d-none d-md-table-cell">Último acceso</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $u): ?>
                <?php
                [$rolClass, $rolLabel] = match ($u['rol']) {
                    'admin_clinica' => ['primary',   'Admin'],
                    'medico'        => ['success',   'Médico'],
                    'recepcion'     => ['secondary', 'Recepción'],
                    default         => ['light',      $u['rol']],
                };
                $esProp = (int) $u['es_propietario'];
                $esYo   = \App\Core\Auth::id() === (int) $u['id'];
                $icono  = match ($u['rol']) {
                    'medico'        => 'person-badge',
                    'admin_clinica' => 'shield-check',
                    default         => 'headset',
                };
                ?>
                <tr class="<?= !(int) $u['activo'] ? 'opacity-50' : '' ?>">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center
                                        bg-<?= $rolClass ?> bg-opacity-10 text-<?= $rolClass ?>"
                                 style="width:36px;height:36px;font-size:.85rem;flex-shrink:0">
                                <i class="bi bi-<?= $icono ?>"></i>
                            </div>
                            <div>
                                <div class="fw-medium">
                                    <?= e($u['nombre']) ?>
                                    <?php if ($esProp): ?>
                                    <span class="badge text-bg-dark ms-1" style="font-size:.65rem">Propietario</span>
                                    <?php endif ?>
                                    <?php if ($esYo): ?>
                                    <span class="badge text-bg-info ms-1" style="font-size:.65rem">Tú</span>
                                    <?php endif ?>
                                </div>
                                <?php if (!empty($u['especialidad'])): ?>
                                <div class="text-muted" style="font-size:.75rem"><?= e($u['especialidad']) ?></div>
                                <?php endif ?>
                            </div>
                        </div>
                    </td>
                    <td class="text-muted"><?= e($u['email']) ?></td>
                    <td><span class="badge text-bg-<?= $rolClass ?>"><?= e($rolLabel) ?></span></td>
                    <td>
                        <?php if ((int) $u['activo']): ?>
                        <span class="badge text-bg-success">Activo</span>
                        <?php else: ?>
                        <span class="badge text-bg-danger">Inactivo</span>
                        <?php endif ?>
                    </td>
                    <td class="d-none d-md-table-cell text-muted small text-nowrap">
                        <?php if ($u['last_login']): ?>
                            <?= e(fecha_legible(substr($u['last_login'], 0, 10))) ?>
                        <?php else: ?>
                            <span class="opacity-40">Sin acceso aún</span>
                        <?php endif ?>
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                            <a href="<?= url('usuarios/' . (int)$u['id'] . '/editar') ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <?php if (!$esProp && !$esYo): ?>
                            <form method="POST"
                                  action="<?= url('usuarios/' . (int)$u['id'] . '/activar') ?>"
                                  onsubmit="return confirm('¿Confirmar cambio de estado?')">
                                <?= csrf_field() ?>
                                <button type="submit"
                                        class="btn btn-sm <?= (int)$u['activo'] ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                    <i class="bi <?= (int)$u['activo'] ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                    <?= (int)$u['activo'] ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>
                            <?php endif ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>
