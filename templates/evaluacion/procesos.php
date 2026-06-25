<?php
$macro = $core->fetchOne("SELECT * FROM proc_macroprocesos WHERE macro_id=:id", ['id'=>$procesoId]);
$procesosDelMacro = $core->fetchAll(
    "SELECT p.*, 
            (SELECT AVG(e.evaluacion_puntaje_total) FROM ind_evaluaciones_desempeno e 
             JOIN sys_usuarios u ON e.evaluacion_usuario_id=u.usuario_id
             JOIN plan_mapa_actividades ma ON ma.mapa_usuario_id=u.usuario_id
             JOIN plan_actividades a ON ma.mapa_actividad_id=a.actividad_id
             WHERE a.actividad_proceso_id=p.proceso_id AND e.evaluacion_periodo=:per) as puntaje_promedio
     FROM proc_procesos p WHERE p.proceso_macro_id=:mid AND p.proceso_activo=1 ORDER BY p.proceso_nombre",
    ['mid'=>$procesoId, 'per'=>$periodo]
);

// Tareas con tiempos promedio
$tareas = $core->fetchAll(
    "SELECT t.*, t.tarea_tiempo_real_promedio_minutos, t.tarea_tiempo_estimado_minutos,
            CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as responsable_nombre
     FROM proc_tareas t 
     LEFT JOIN sys_usuarios u ON t.tarea_responsable_id=u.usuario_id
     WHERE t.tarea_proceso_id IN (SELECT proceso_id FROM proc_procesos WHERE proceso_macro_id=:mid) AND t.tarea_activo=1
     ORDER BY t.tarea_tiempo_real_promedio_minutos DESC LIMIT 20",
    ['mid'=>$procesoId]
);
?>

<nav class="mb-3"><ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="/evaluacion">Evaluación</a></li>
    <li class="breadcrumb-item active"><?= htmlspecialchars($macro['macro_nombre']) ?></li>
</ol></nav>

<h5 class="mb-3"><?= htmlspecialchars($macro['macro_nombre']) ?> · <span class="badge bg-light text-dark"><?= $macro['macro_tipo'] ?></span></h5>

<!-- Procesos del macroproceso -->
<div class="row g-4 mb-4">
    <?php foreach ($procesosDelMacro as $proc): 
        $pct = round(floatval($proc['puntaje_promedio'] ?? 0), 1);
    ?>
    <div class="col-md-6">
        <div class="card p-3" style="border-left:4px solid #1a73e8;border-radius:12px">
            <div class="d-flex justify-content-between mb-2">
                <strong><?= htmlspecialchars($proc['proceso_nombre']) ?></strong>
                <span class="badge bg-<?= $pct>=90?'success':($pct>=70?'warning':'danger') ?>"><?= $pct ?>%</span>
            </div>
            <div class="progress mb-2" style="height:6px;border-radius:3px"><div class="progress-bar bg-primary" style="width:<?= min($pct,100) ?>%"></div></div>

            <!-- Colaboradores de este proceso -->
            <?php 
            $colabsProceso = EstrateGiaCore::getInstance()->fetchAll(
                "SELECT u.usuario_id, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as nombre, u.usuario_cargo,
                        e.evaluacion_puntaje_total, e.evaluacion_puntaje_cumplimiento
                 FROM ind_evaluaciones_desempeno e
                 JOIN sys_usuarios u ON e.evaluacion_usuario_id=u.usuario_id
                 WHERE e.evaluacion_periodo=:per AND u.usuario_id IN (
                     SELECT DISTINCT ma.mapa_usuario_id FROM plan_mapa_actividades ma
                     JOIN plan_actividades a ON ma.mapa_actividad_id=a.actividad_id
                     WHERE a.actividad_proceso_id=:pid
                 ) ORDER BY e.evaluacion_puntaje_total DESC LIMIT 5",
                ['per'=>$periodo, 'pid'=>$proc['proceso_id']]
            );
            ?>
            <?php if (!empty($colabsProceso)): ?>
            <div class="small mt-2">
                <?php foreach ($colabsProceso as $c): ?>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span style="width:6px;height:6px;border-radius:50%;background:<?= ($c['evaluacion_puntaje_total']??0)>=90?'#28a745':($c['evaluacion_puntaje_total']??0>=70?'#ffc107':'#dc3545') ?>;flex-shrink:0"></span>
                    <span style="flex:1"><?= htmlspecialchars($c['nombre']) ?></span>
                    <strong class="small"><?= number_format($c['evaluacion_puntaje_total']??0,1) ?>%</strong>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tareas con tiempos promedio -->
<h5 class="mb-3"><i class="fas fa-clock me-2"></i>Benchmark de Tiempos por Tarea</h5>
<div class="card-box"><div class="card-box-body p-0">
    <table class="table-box small">
        <thead><tr><th>Tarea</th><th>Responsable</th><th>Tiempo Est.</th><th>Tiempo Real Prom.</th><th>Diferencia</th></tr></thead>
        <tbody>
        <?php foreach ($tareas as $t): 
            $est = $t['tarea_tiempo_estimado_minutos'] ?? 0;
            $real = $t['tarea_tiempo_real_promedio_minutos'] ?? 0;
            $diff = $real - $est;
        ?>
        <tr>
            <td><?= htmlspecialchars($t['tarea_nombre']) ?></td>
            <td><?= htmlspecialchars($t['responsable_nombre']??'-') ?></td>
            <td><?= $est ? round($est/60,1).'h' : '-' ?></td>
            <td><?= $real ? round($real/60,1).'h' : '-' ?></td>
            <td class="<?= $diff>0?'text-danger':'text-success' ?>"><?= $real ? ($diff>0?'+':'') . round($diff/60,1).'h' : '-' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
