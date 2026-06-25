<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="/planeacion/crear" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Nuevo Plan Estratégico</a>
    </div>
</div>

<?php if (empty($planes)): ?>
<div class="card-box">
    <div class="card-box-body text-center py-5">
        <i class="fas fa-bullseye" style="font-size:4rem;color:#ccc;margin-bottom:16px;display:block;"></i>
        <h5>No hay planes estratégicos aún</h5>
        <p class="text-muted">Crea tu primer plan para comenzar a gestionar la estrategia de tu organización.</p>
        <a href="/planeacion/crear" class="btn btn-primary btn-lg mt-3"><i class="fas fa-plus me-2"></i>Crear Plan</a>
    </div>
</div>
<?php else: ?>
<div class="card-box">
    <div class="card-box-body">
        <table class="table-box">
            <thead><tr><th>Plan</th><th>Metodología</th><th>Período</th><th>Avance</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($planes as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p['plan_nombre']) ?></strong></td>
                <td><i class="fas <?= htmlspecialchars($p['metodologia_icono']??'fa-circle') ?> me-1"></i><?= htmlspecialchars($p['metodologia_nombre']) ?></td>
                <td><?= htmlspecialchars($p['plan_periodo'] ?? '-') ?></td>
                <td style="width:150px">
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress progress-thin flex-grow-1"><div class="progress-bar bg-primary" style="width:<?= $p['plan_avance_porcentaje'] ?>%"></div></div>
                        <small><?= $p['plan_avance_porcentaje'] ?>%</small>
                    </div>
                </td>
                <td><span class="badge-status badge-<?= $p['plan_estado'] ?>"><?= $p['plan_estado'] ?></span></td>
                <td><a href="/planeacion/<?= $p['plan_id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                <form method="POST" action="/planeacion/<?= $p['plan_id'] ?>/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar este plan?')"><button class="btn btn-sm btn-outline-danger ms-1"><i class="fas fa-trash"></i></button></form></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
