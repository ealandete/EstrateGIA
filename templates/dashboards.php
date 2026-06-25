<?php
$tipoIcons = ['planeacion'=>'chess-board','procesos'=>'sitemap','indicadores'=>'chart-line','individual'=>'user','ejecutivo'=>'briefcase','operativo'=>'cogs','personalizado'=>'palette'];
$tipoLabels = ['planeacion'=>'Planeación','procesos'=>'Procesos','indicadores'=>'Indicadores','individual'=>'Individual','ejecutivo'=>'Ejecutivo','operativo'=>'Operativo','personalizado'=>'Personalizado'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="fw-bold mb-0" style="font-size:1.15rem"><i class="fas fa-th-large me-2"></i>Dashboards Configurables</div>
</div>

<?php if (empty($tableros)): ?>
<div class="card-box"><div class="card-box-body text-center py-5">
    <i class="fas fa-th-large" style="font-size:4rem;color:#ccc;display:block;margin-bottom:16px"></i>
    <h5>No hay dashboards configurados</h5>
    <p class="text-muted">Cree tableros desde el panel de administración.</p>
</div></div>
<?php else: ?>
<div class="row g-4">
    <?php foreach ($tableros as $t): ?>
    <div class="col-md-4">
        <div class="card-box h-100">
            <div class="card-box-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-<?= $tipoIcons[$t['tablero_tipo']] ?? 'table' ?> me-2"></i><?= htmlspecialchars($t['tablero_nombre']) ?></span>
                <span class="badge bg-light text-dark"><?= $tipoLabels[$t['tablero_tipo']] ?? $t['tablero_tipo'] ?></span>
            </div>
            <div class="card-box-body">
                <p class="small text-muted mb-2"><?= htmlspecialchars(mb_substr($t['tablero_descripcion'] ?? 'Sin descripción', 0, 120)) ?></p>
                <div class="d-flex justify-content-between small text-muted">
                    <span><i class="fas fa-cubes me-1"></i><?= $t['total_widgets'] ?> widgets</span>
                    <?php if ($t['tablero_es_plantilla']): ?><span class="badge bg-info">Plantilla</span><?php endif; ?>
                </div>
                <a href="/dashboard?empresa_id=<?= $t['tablero_empresa_id'] ?? '' ?>" class="btn btn-sm btn-outline-primary mt-2 w-100">Abrir</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
