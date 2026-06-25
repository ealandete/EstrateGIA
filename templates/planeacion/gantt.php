<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="/planeacion/<?= $id ?>" class="btn btn-sm btn-light"><i class="fas fa-arrow-left me-1"></i>Volver al Plan</a>
    </div>
    <div>
        <span class="badge-status badge-<?= $plan['plan_estado'] ?>"><?= $plan['plan_estado'] ?></span>
    </div>
</div>

<h4 class="mb-3"><i class="fas fa-chart-gantt me-2" style="color:#1a73e8"></i>Diagrama de Gantt — <?= htmlspecialchars($plan['plan_nombre']) ?></h4>

<div class="card-box">
    <div class="card-box-header d-flex justify-content-between">
        <span>Fases y Cronograma</span>
        <small class="text-muted"><?= count($arbol) ?> fases</small>
    </div>
    <div class="card-box-body p-0">
        <div class="gantt-container" style="overflow-x:auto;padding:0">
            <?php
            $fechaInicio = strtotime($plan['plan_fecha_inicio'] ?? date('Y-m-d'));
            $fechaFin = strtotime($plan['plan_fecha_fin'] ?? date('Y-m-d', strtotime('+1 year')));
            $duracionTotal = max(1, ($fechaFin - $fechaInicio) / 86400);
            $meses = [];
            $cursor = $fechaInicio;
            while ($cursor <= $fechaFin) {
                $meses[] = date('M Y', $cursor);
                $cursor = strtotime('+1 month', $cursor);
            }
            $colW = max(60, floor((860 - 200) / count($meses)));
            ?>
            <table class="table-box small mb-0" style="min-width:<?= 200 + count($meses)*$colW ?>px">
                <thead><tr>
                    <th style="width:200px;position:sticky;left:0;background:#f8fafc;z-index:1">Fase</th>
                    <th style="width:80px">Estado</th>
                    <?php foreach ($meses as $m): ?>
                    <th style="width:<?=$colW?>px;text-align:center;font-size:0.6rem"><?= $m ?></th>
                    <?php endforeach; ?>
                </tr></thead>
                <tbody>
                <?php
                $colors = ['#1a73e8','#28a745','#ffc107','#ff9800','#6f42c1','#dc3545','#17a2b8','#fd7e14'];
                $ci = 0;
                foreach ($arbol as $fase):
                    $color = $colors[$ci++ % count($colors)];
                    $estado = $fase['fase_estado'] ?? 'pendiente';
                    $faseInicio = !empty($fase['fase_fecha_inicio']) ? strtotime($fase['fase_fecha_inicio']) : $fechaInicio;
                    $faseFin = !empty($fase['fase_fecha_fin']) ? strtotime($fase['fase_fecha_fin']) : strtotime('+1 month', $faseInicio);
                    $offsetPct = max(0, ($faseInicio - $fechaInicio) / 86400 / $duracionTotal * 100);
                    $widthPct = max(3, ($faseFin - $faseInicio) / 86400 / $duracionTotal * 100);
                    $avancePct = $fase['fase_avance_porcentaje'] ?? ($estado === 'completada' ? 100 : 0);
                ?>
                <tr>
                    <td style="position:sticky;left:0;background:white;z-index:1">
                        <strong><?= htmlspecialchars($fase['fase_nombre']) ?></strong>
                        <div class="text-muted" style="font-size:0.6rem">
                            <?= $fase['fase_fecha_inicio'] ? date('d/m/Y', strtotime($fase['fase_fecha_inicio'])) : '—' ?>
                            → <?= $fase['fase_fecha_fin'] ? date('d/m/Y', strtotime($fase['fase_fecha_fin'])) : '—' ?>
                        </div>
                    </td>
                    <td><span class="badge-status badge-<?= $estado ?>"><?= $estado ?></span></td>
                    <?php foreach ($meses as $mi => $m): ?>
                    <td style="position:relative;text-align:center;padding:2px">
                        <?php
                        $mesInicio = strtotime("first day of $m", $fechaInicio);
                        $mesFin = strtotime("last day of $m", $fechaInicio);
                        $solapado = ($faseInicio <= $mesFin && $faseFin >= $mesInicio);
                        if ($solapado): ?>
                        <div style="background:<?=$color?>;height:20px;border-radius:3px;opacity:0.8;position:relative" title="<?= htmlspecialchars($fase['fase_nombre']) ?>">
                            <?php if ($avancePct > 0): ?>
                            <div style="background:<?=$color?>;height:100%;width:<?=$avancePct?>%;border-radius:3px 0 0 3px;opacity:0.5;position:absolute;top:0;left:0"></div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3 mt-3">
    <div class="col-md-4">
        <div class="card-box">
            <div class="card-box-header">Resumen</div>
            <div class="card-box-body">
                <?php $completadas = 0; foreach ($arbol as $f) if (in_array($f['fase_estado']??'', ['completada','aprobada'])) $completadas++; ?>
                <div class="d-flex justify-content-between mb-2"><span>Fases completadas</span><strong><?= $completadas ?>/<?= count($arbol) ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Periodo</span><strong><?= $plan['plan_fecha_inicio'] ?? '—' ?> → <?= $plan['plan_fecha_fin'] ?? '—' ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Avance general</span><strong><?= $avanceReal ?>%</strong></div>
                <div class="progress progress-thin"><div class="progress-bar bg-primary" style="width:<?= $avanceReal ?>%"></div></div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card-box">
            <div class="card-box-header">Leyenda</div>
            <div class="card-box-body">
                <div class="d-flex flex-wrap gap-3">
                    <?php $ci = 0; foreach ($arbol as $fase): $color = $colors[$ci++ % count($colors)]; ?>
                    <div class="d-flex align-items-center gap-1">
                        <div style="width:16px;height:14px;background:<?=$color?>;border-radius:2px;opacity:0.8"></div>
                        <small><?= htmlspecialchars($fase['fase_nombre']) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge-status{padding:4px 10px;border-radius:12px;font-size:0.65rem;font-weight:600;text-transform:uppercase}
.badge-borrador{background:#e2e8f0;color:#475569}
.badge-en_proceso,.badge-ejecucion{background:#dbeafe;color:#1d4ed8}
.badge-completada,.badge-aprobado,.badge-completado{background:#dcfce7;color:#15803d}
.badge-pendiente{background:#fef3c7;color:#a16207}
.badge-cancelado,.badge-cancelada{background:#fee2e2;color:#b91c1c}
.gantt-container .table-box td, .gantt-container .table-box th { padding: 6px 8px; vertical-align: middle; }
</style>
