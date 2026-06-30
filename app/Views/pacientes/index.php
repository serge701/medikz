<?php $pageTitle = 'Pacientes'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-secondary"><i class="bi bi-people"></i> <?= (int) $total ?> paciente(s)</span>
    <a href="<?= url('pacientes/nuevo') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo paciente
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="input-group input-group-lg mb-3">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="busqueda" class="form-control"
                   placeholder="Buscar por nombre, teléfono o CURP…" autofocus autocomplete="off">
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th class="text-center">Edad</th>
                        <th>Sexo</th>
                        <th>Teléfono</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="resultados">
                    <?php if (empty($recientes)): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4">
                            Aún no hay pacientes. Crea el primero con “Nuevo paciente”.
                        </td></tr>
                    <?php else: foreach ($recientes as $p): ?>
                        <tr>
                            <td><a href="<?= url('pacientes/' . $p['id']) ?>" class="fw-semibold text-decoration-none">
                                <?= e(nombre_completo($p)) ?></a></td>
                            <td class="text-center"><?= edad_anios($p['fecha_nacimiento']) ?? '—' ?></td>
                            <td><?= e(sexo_label($p['sexo'])) ?></td>
                            <td><?= e($p['telefono'] ?? '') ?: '—' ?></td>
                            <td class="text-end">
                                <a href="<?= url('pacientes/' . $p['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Ver">
                                    <i class="bi bi-eye"></i></a>
                                <a href="<?= url('pacientes/' . $p['id'] . '/editar') ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    const base = <?= json_encode(url('pacientes')) ?>;
    const input = document.getElementById('busqueda');
    const tbody = document.getElementById('resultados');
    let timer = null;

    function el(tag, attrs, text) {
        const n = document.createElement(tag);
        if (attrs) for (const k in attrs) n.setAttribute(k, attrs[k]);
        if (text != null) n.textContent = text;
        return n;
    }

    function pintar(items) {
        tbody.innerHTML = '';
        if (!items.length) {
            const tr = el('tr');
            const td = el('td', { colspan: '5', class: 'text-center text-secondary py-4' }, 'Sin resultados.');
            tr.appendChild(td); tbody.appendChild(tr);
            return;
        }
        for (const p of items) {
            const tr = el('tr');

            const tdNom = el('td');
            const a = el('a', { href: base + '/' + p.id, class: 'fw-semibold text-decoration-none' }, p.nombre);
            tdNom.appendChild(a);

            const tdEdad = el('td', { class: 'text-center' }, p.edad != null ? String(p.edad) : '—');
            const tdSexo = el('td', null, p.sexo);
            const tdTel = el('td', null, p.telefono || '—');

            const tdAcc = el('td', { class: 'text-end' });
            const ver = el('a', { href: base + '/' + p.id, class: 'btn btn-sm btn-outline-secondary', title: 'Ver' });
            ver.appendChild(el('i', { class: 'bi bi-eye' }));
            const edi = el('a', { href: base + '/' + p.id + '/editar', class: 'btn btn-sm btn-outline-primary', title: 'Editar' });
            edi.appendChild(el('i', { class: 'bi bi-pencil' }));
            tdAcc.append(ver, document.createTextNode(' '), edi);

            tr.append(tdNom, tdEdad, tdSexo, tdTel, tdAcc);
            tbody.appendChild(tr);
        }
    }

    function buscar(q) {
        fetch(base + '/buscar?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(pintar)
            .catch(() => {});
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(() => buscar(input.value.trim()), 180);
    });
})();
</script>
