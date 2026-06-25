<?php
$variantColors = ['cumplimiento'=>'#28a745','oportunidad'=>'#ffc107','calidad'=>'#007bff','productividad'=>'#6f42c1'];
$variantIcons = ['cumplimiento'=>'check-circle','oportunidad'=>'clock','calidad'=>'star','productividad'=>'chart-line'];
?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Avance General</div>
            <div class="stat-value"><?= $plan['avance'] ?? 0 ?>%</div>
            <div class="progress progress-thin mt-2"><div class="progress-bar bg-primary" style="width:<?= $plan['avance']??0 ?>%"></div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Objetivos</div>
            <div class="stat-value"><?= ($dashboard['resumen_planeacion']['progreso']['objetivos_cumplidos']??0) ?>/<?= ($dashboard['resumen_planeacion']['progreso']['total_objetivos']??0) ?></div>
            <small class="text-muted">cumplidos</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Indicadores en Verde</div>
            <div class="stat-value text-success"><?= $dashboard['resumen_planeacion']['progreso']['indicadores_verdes']??0 ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Alertas Activas</div>
            <div class="stat-value text-danger"><?= count($alertas) ?></div>
        </div>
    </div>
</div>

<!-- Plan activo -->
<?php if ($plan): ?>
<div class="card-box">
    <div class="card-box-header">
        <span><i class="fas fa-bullseye me-2" style="color:var(--primary)"></i><?= htmlspecialchars($plan['nombre']) ?></span>
        <span class="badge-status badge-<?= $plan['estado'] ?>"><?= $plan['estado'] ?></span>
    </div>
    <div class="card-box-body">
        <div class="row">
            <div class="col-md-6">
                <strong>Metodología:</strong> <?= htmlspecialchars($plan['metodologia']) ?><br>
                <strong>Período:</strong> <?= htmlspecialchars($plan['periodo'] ?? '') ?>
                <?php if (($plan['dias_restantes']??null) !== null): ?>
                | <strong><?= $plan['dias_restantes'] ?> días restantes</strong>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-end">
                <a href="/planeacion/<?= $plan['id'] ?>" class="btn btn-primary btn-sm">Ver Plan <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 4 Variantes -->
<h6 class="mb-3"><i class="fas fa-gauge-high me-2"></i>Las 4 Variantes</h6>
<div class="row g-3 mb-4">
    <?php foreach (['cumplimiento','oportunidad','calidad','productividad'] as $tipo): $v = $variantes[$tipo] ?? null; ?>
    <div class="col-md-3">
        <div class="variant-card" style="border-left-color:<?= $variantColors[$tipo] ?>">
            <div class="v-icon" style="color:<?= $variantColors[$tipo] ?>"><i class="fas fa-<?= $variantIcons[$tipo] ?>"></i></div>
            <div class="v-label"><?= $v['categoria_nombre'] ?? $tipo ?></div>
            <div class="v-value" style="color:<?= $variantColors[$tipo] ?>"><?= number_format($v['promedio_cumplimiento']??0,1) ?>%</div>
            <div class="semaforo-dots">
                <span class="semaforo-dot" style="background:#28a745"><?= $v['conteo_verde']??0 ?></span>
                <span class="semaforo-dot" style="background:#ffc107;color:#333"><?= $v['conteo_amarillo']??0 ?></span>
                <span class="semaforo-dot" style="background:#dc3545"><?= $v['conteo_rojo']??0 ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Semáforo KPI -->
    <div class="col-md-8">
        <div class="card-box">
            <div class="card-box-header"><span><i class="fas fa-table me-2"></i>Semáforo de Indicadores</span></div>
            <div class="card-box-body">
                <table class="table-box">
                    <thead><tr><th>Categoría</th><th>Total</th><th class="text-success">Verde</th><th class="text-warning">Amarillo</th><th class="text-danger">Rojo</th><th>% Salud</th></tr></thead>
                    <tbody>
                    <?php foreach ($semaforo as $sem): $total = ($sem['total']?:1); ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($sem['categoria_nombre']) ?></strong></td>
                        <td><?= $sem['total'] ?></td>
                        <td class="text-success fw-bold"><?= $sem['verde'] ?></td>
                        <td class="text-warning fw-bold"><?= $sem['amarillo'] ?></td>
                        <td class="text-danger fw-bold"><?= $sem['rojo'] ?></td>
                        <td>
                            <div class="progress progress-thin" style="width:100px"><div class="progress-bar bg-success" style="width:<?= round(($sem['verde']/$total)*100) ?>%"></div></div>
                            <small><?= round(($sem['verde']/$total)*100) ?>%</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Procesos -->
    <div class="col-md-4">
        <div class="card-box">
            <div class="card-box-header"><span><i class="fas fa-diagram-project me-2"></i>Procesos</span></div>
            <div class="card-box-body">
                <div class="mb-2"><strong><?= $procesos['total_macroprocesos'] ?? 0 ?></strong> Macroprocesos</div>
                <div class="mb-2"><strong><?= $procesos['total_procesos'] ?? 0 ?></strong> Procesos</div>
                <div class="mb-2"><strong><?= $procesos['total_procedimientos'] ?? 0 ?></strong> Procedimientos</div>
                <div class="mb-2"><strong><?= $procesos['total_tareas'] ?? 0 ?></strong> Tareas</div>
                <div><strong><?= $procesos['total_documentos'] ?? 0 ?></strong> Documentos</div>
                <hr>
                <small class="text-muted">
                <?php foreach (($procesos['distribucion_tipos']??[]) as $t=>$c): ?>
                    <?= ucfirst($t) ?>: <?= $c ?><br>
                <?php endforeach; ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Ranking y Alertas -->
<div class="row g-4 mt-1">
    <div class="col-md-6">
        <div class="card-box">
            <div class="card-box-header">
                <span><i class="fas fa-trophy me-2"></i>Top Colaboradores</span>
                <a href="/evaluacion/ranking" class="btn btn-sm btn-outline-primary">Ver ranking</a>
            </div>
            <div class="card-box-body">
                <?php foreach (array_slice($ranking, 0, 5) as $i => $col): ?>
                <div class="ranking-item">
                    <span class="rank-pos">#<?= $i+1 ?></span>
                    <div class="user-avatar me-2" style="width:32px;height:32px;font-size:0.7rem"><?= strtoupper(substr($col['nombre']??'U',0,2)) ?></div>
                    <div class="rank-info">
                        <div class="rank-name"><?= htmlspecialchars($col['nombre']??'') ?></div>
                        <div class="rank-dept"><?= htmlspecialchars($col['usuario_departamento']??'') ?></div>
                    </div>
                    <span class="rank-score text-success"><?= number_format($col['evaluacion_puntaje_total']??0,1) ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-box">
            <div class="card-box-header"><span><i class="fas fa-exclamation-triangle me-2"></i>Alertas</span></div>
            <div class="card-box-body">
                <?php if (empty($alertas)): ?>
                    <div class="text-center text-muted py-3">Sin alertas activas</div>
                <?php else: ?>
                <?php foreach (array_slice($alertas,0,5) as $a): ?>
                <div class="alert-line" style="border-left-color:<?= $a['prioridad']==='alta'?'#dc3545':'#ffc107' ?>">
                    <i class="fas <?= $a['prioridad']==='alta'?'fa-circle-exclamation text-danger':'fa-triangle-exclamation text-warning' ?> me-2"></i>
                    <?= htmlspecialchars($a['mensaje']) ?>
                    <?php if (!empty($a['responsable'])): ?>
                    <div class="text-muted small ms-4"><?= htmlspecialchars($a['responsable']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
