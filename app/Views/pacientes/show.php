<?php
$pageTitle = 'Ficha del paciente';
$edad = edad_anios($p['fecha_nacimiento']);
?>

<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h3 class="mb-1"><?= e(nombre_completo($p)) ?></h3>
        <span class="text-secondary">
            <?= e(sexo_label($p['sexo'])) ?>
            <?php if ($edad !== null): ?> · <?= $edad ?> años<?php endif; ?>
            <?php if (!empty($p['fecha_nacimiento'])): ?> · <?= e(fecha_legible($p['fecha_nacimiento'])) ?><?php endif; ?>
        </span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('pacientes') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
        <a href="<?= url('pacientes/' . $p['id'] . '/editar') ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Editar</a>
        <?php if ($puedeClinico): ?>
            <a href="<?= url('consultas/nueva?paciente_id=' . $p['id']) ?>" class="btn btn-success"><i class="bi bi-clipboard2-plus"></i> Nueva consulta</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-person-vcard"></i> Contacto e identificación</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-secondary">Teléfono</dt>
                    <dd class="col-sm-8"><?= e($p['telefono'] ?? '') ?: '—' ?></dd>

                    <dt class="col-sm-4 text-secondary">Correo</dt>
                    <dd class="col-sm-8"><?= e($p['email'] ?? '') ?: '—' ?></dd>

                    <dt class="col-sm-4 text-secondary">CURP</dt>
                    <dd class="col-sm-8"><?= e($p['curp'] ?? '') ?: '—' ?></dd>

                    <dt class="col-sm-4 text-secondary">Dirección</dt>
                    <dd class="col-sm-8">
                        <?php
                        $dir = array_filter([$p['direccion'] ?? '', $p['ciudad'] ?? '', $p['estado'] ?? '', $p['cp'] ?? '']);
                        echo $dir ? e(implode(', ', $dir)) : '—';
                        ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-shield-plus"></i> Datos de seguridad</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-secondary">Tipo de sangre</dt>
                    <dd class="col-sm-8"><?= e($p['tipo_sangre'] ?? '') ?: '—' ?></dd>

                    <dt class="col-sm-4 text-secondary">Alergias</dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($p['alergias'])): ?>
                            <span class="text-danger fw-semibold"><?= e($p['alergias']) ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </dd>

                    <dt class="col-sm-4 text-secondary">Contacto emergencia</dt>
                    <dd class="col-sm-8"><?= e($p['contacto_emergencia'] ?? '') ?: '—' ?></dd>

                    <dt class="col-sm-4 text-secondary">Tel. emergencia</dt>
                    <dd class="col-sm-8"><?= e($p['tel_emergencia'] ?? '') ?: '—' ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <?php if ($puedeClinico): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="bi bi-clipboard2-pulse"></i> Antecedentes médicos</div>
            <div class="card-body">
                <?php if (!empty($p['antecedentes'])): ?>
                    <p class="mb-0" style="white-space: pre-line;"><?= e($p['antecedentes']) ?></p>
                <?php else: ?>
                    <p class="text-secondary mb-0">Sin antecedentes registrados.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-clock-history"></i> Últimas consultas</span>
                <a href="<?= url('consultas?paciente_id=' . $p['id']) ?>"
                   class="btn btn-outline-secondary btn-sm">Ver todas</a>
            </div>
            <?php if (empty($consultas)): ?>
                <div class="card-body text-center text-secondary py-4">
                    <i class="bi bi-clipboard2-x fs-3 d-block mb-2 opacity-50"></i>
                    Sin consultas registradas.
                    <div class="mt-2">
                        <a href="<?= url('consultas/nueva?paciente_id=' . $p['id']) ?>"
                           class="btn btn-outline-success btn-sm">
                            <i class="bi bi-plus-lg"></i> Registrar primera consulta
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($consultas as $c): ?>
                        <a href="<?= url('consultas/' . $c['id']) ?>"
                           class="list-group-item list-group-item-action d-flex align-items-start gap-3 px-3 py-2">
                            <div class="text-muted small text-center" style="min-width:44px">
                                <div class="fw-bold"><?= e(date('d/m', strtotime($c['fecha_consulta']))) ?></div>
                                <div><?= e(date('Y', strtotime($c['fecha_consulta']))) ?></div>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <?php if (!empty($c['motivo_consulta'])): ?>
                                    <div class="small text-truncate"><?= e($c['motivo_consulta']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($c['diagnostico'])): ?>
                                    <div class="small text-muted text-truncate"><?= e($c['diagnostico']) ?></div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($puedeEliminar): ?>
<div class="card border-danger mt-3">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong class="text-danger">Dar de baja</strong>
            <div class="small text-secondary">El paciente se oculta pero su información se conserva (baja lógica).</div>
        </div>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminar">
            <i class="bi bi-trash"></i> Dar de baja
        </button>
    </div>
</div>

<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar baja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Dar de baja a <strong><?= e(nombre_completo($p)) ?></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="post" action="<?= url('pacientes/' . $p['id'] . '/eliminar') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Sí, dar de baja</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
