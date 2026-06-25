<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/planeacion">Planes</a></li>
        <li class="breadcrumb-item"><a href="/planeacion/<?= $plan['plan_id'] ?>"><?= htmlspecialchars($plan['plan_nombre']) ?></a></li>
        <li class="breadcrumb-item active">PESTEL</li>
    </ol>
</nav>

<div class="card-box">
    <div class="card-box-header"><i class="fas fa-globe me-2"></i>Análisis PESTEL — <?= htmlspecialchars($plan['plan_nombre']) ?></div>
    <div class="card-box-body">
        <?php if (empty($pestel)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-globe" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
            <p>No hay análisis PESTEL registrado para este plan.</p>
            <a href="/planeacion/<?= $plan['plan_id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-arrow-left me-1"></i>Volver al Plan</a>
        </div>
        <?php else: ?>
        <?php 
        $contenido = $pestel['analisis_contenido'];
        if (is_string($contenido)) $contenido = json_decode($contenido, true);
        $dimensiones = ['politico'=>'🏛 Político','economico'=>'💰 Económico','social'=>'👥 Social','tecnologico'=>'🔬 Tecnológico','ecologico'=>'🌱 Ecológico','legal'=>'⚖️ Legal'];
        ?>
        <div class="row g-4">
            <?php foreach ($dimensiones as $key => $label): $items = $contenido[$key] ?? []; ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header fw-bold"><?= $label ?></div>
                    <ul class="list-group list-group-flush">
                        <?php if (empty($items)): ?>
                        <li class="list-group-item text-muted small">Sin factores registrados</li>
                        <?php else: foreach ($items as $item): ?>
                        <li class="list-group-item small"><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-muted small mt-3">Análisis creado: <?= date('d/m/Y', strtotime($pestel['analisis_fecha'] ?? $pestel['created_at'])) ?></div>
        <?php endif; ?>
    </div>
</div>
