<?php $faseIcons = ['P' => 'lightbulb', 'H' => 'cogs', 'V' => 'search', 'A' => 'wrench']; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="fas fa-sync-alt me-2"></i>Ciclo PHVA — Mejora Continua</h4>
    <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill" style="background:<?= $porcentajeGeneral >= 75 ? '#28a745' : ($porcentajeGeneral >= 50 ? '#ffc107' : '#dc3545') ?>;font-size:1.1rem;padding:8px 16px">
                <?= $porcentajeGeneral ?>% cumplimiento
            </span>
        </div>
    </div>
</div>

<!-- Ciclo visual PHVA -->
<div class="row g-4 mb-4">
    <?php foreach ($resumen as $key => $fase): ?>
    <div class="col-md-3">
        <div class="card-box" style="border-top:4px solid <?= $fase['color'] ?>">
            <div class="card-box-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-<?= $fase['icon'] ?> me-2" style="color:<?= $fase['color'] ?>"></i>
                    <strong><?= $fase['nombre'] ?></strong>
                </div>
                <span class="badge" style="background:<?= $fase['color'] ?>"><?= $fase['porcentaje'] ?>%</span>
            </div>
            <div class="card-box-body">
                <div class="progress mb-3" style="height:8px;border-radius:4px">
                    <div class="progress-bar" style="width:<?= $fase['porcentaje'] ?>%;background:<?= $fase['color'] ?>;border-radius:4px"></div>
                </div>
                <?php foreach ($fase['modulos'] as $mod): ?>
                <a href="<?= $mod['url'] ?>" class="d-flex align-items-center justify-content-between py-1 text-decoration-none" style="font-size:0.85rem">
                    <span class="text-muted">
                        <?= $mod['tiene_datos'] ? '<i class="fas fa-check-circle text-success me-1"></i>' : '<i class="fas fa-circle text-muted me-1" style="font-size:0.5rem;vertical-align:middle"></i>' ?>
                        <?= $mod['nombre'] ?>
                    </span>
                    <small class="text-muted"><?= $mod['registros'] ?></small>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Diagrama circular PHVA -->
<div class="card-box mb-4">
    <div class="card-box-header"><i class="fas fa-chart-pie me-2"></i>Resumen por Fase</div>
    <div class="card-box-body">
        <div class="row align-items-center">
            <div class="col-md-4">
                <canvas id="phvaChart" width="250" height="250"></canvas>
            </div>
            <div class="col-md-8">
                <table class="table table-sm">
                    <thead><tr><th>Fase</th><th>Progreso</th><th>Módulos OK</th><th>Registros</th></tr></thead>
                    <tbody>
                    <?php foreach ($resumen as $key => $fase): 
                        $okCount = count(array_filter($fase['modulos'], fn($m) => $m['tiene_datos']));
                        $totalReg = array_sum(array_column($fase['modulos'], 'registros'));
                    ?>
                    <tr>
                        <td><span class="badge" style="background:<?= $fase['color'] ?>"><?= $key ?> — <?= $fase['nombre'] ?></span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px"><div class="progress-bar" style="width:<?= $fase['porcentaje'] ?>%;background:<?= $fase['color'] ?>"></div></div>
                                <small class="fw-bold"><?= $fase['porcentaje'] ?>%</small>
                            </div>
                        </td>
                        <td><?= $okCount ?>/<?= count($fase['modulos']) ?></td>
                        <td><?= number_format($totalReg) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Brechas y acciones -->
<?php if (!empty($brechas)): ?>
<div class="card-box">
    <div class="card-box-header"><i class="fas fa-exclamation-triangle me-2"></i>Brechas y Acciones Recomendadas (<?= count($brechas) ?>)</div>
    <div class="card-box-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>Fase</th><th>Módulo</th><th>Acción Recomendada</th><th>Prioridad</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($brechas as $b): ?>
            <tr>
                <td><span class="badge" style="background:<?= $b['color'] ?>"><?= $b['fase'] ?></span></td>
                <td><strong><?= $b['modulo'] ?></strong></td>
                <td class="text-muted small"><?= $b['accion'] ?></td>
                <td>
                    <?php $pColor = ['ALTA'=>'#dc3545','MEDIA'=>'#ffc107','BAJA'=>'#28a745'][$b['prioridad']] ?? '#888'; ?>
                    <span class="badge" style="background:<?= $pColor ?>"><?= $b['prioridad'] ?></span>
                </td>
                <td><a href="<?= $b['url'] ?>" class="btn btn-sm btn-outline-primary">Ir</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script src="/assets/js/chart.min.js"></script>
<script>
new Chart(document.getElementById('phvaChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(fn($f) => $f['nombre'], $resumen)) ?>,
        datasets: [{
            data: <?= json_encode(array_map(fn($f) => max($f['porcentaje'], 1), $resumen)) ?>,
            backgroundColor: <?= json_encode(array_map(fn($f) => $f['color'], $resumen)) ?>,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } }
        },
        cutout: '55%'
    }
});
</script>
