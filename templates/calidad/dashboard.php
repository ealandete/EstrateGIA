<?php
$faseSteps = ['diagnostico'=>'1. Diagnóstico','planificacion'=>'2. Planificación','ejecucion'=>'3. Ejecución','verificacion'=>'4. Verificación','acreditado'=>'5. Acreditado'];
$faseColors = ['diagnostico'=>'secondary','planificacion'=>'info','ejecucion'=>'primary','verificacion'=>'warning','acreditado'=>'success'];
$tipoLabels = ['SUA'=>'Sist. Único Acreditación','ISO7101'=>'ISO 7101:2023','Habilitacion'=>'Habilitación'];

// Determinar fase activa del primer ciclo
$cicloActivo = !empty($ciclos) ? $ciclos[0] : null;
?>

<div class="d-flex justify-content-between mb-3">
    <div>
        <h5><i class="fas fa-certificate me-2" style="color:#ffc107"></i>Acreditación · <?= htmlspecialchars($empresa['empresa_nombre']) ?></h5>
        <small class="text-muted">Sistema de gestión de acreditación en salud</small>
    </div>
    <a href="/calidad/reporte?empresa_id=<?= $empresaId ?>" class="btn btn-outline-primary btn-sm" target="_blank"><i class="fas fa-file-pdf me-1"></i>Informe</a>
</div>

<!-- Proceso de acreditación paso a paso -->
<div class="card-box mb-4">
    <div class="card-box-header"><i class="fas fa-route me-2"></i>Proceso de Acreditación</div>
    <div class="card-box-body">
        <div class="d-flex align-items-center">
            <?php foreach ($faseSteps as $fk => $fl): $activo = $cicloActivo && $cicloActivo['nivel_fase'] === $fk; ?>
            <div class="text-center" style="flex:1">
                <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px;background:<?= $activo?'#1a73e8':'#e9ecef' ?>;color:<?= $activo?'#fff':'#888' ?>;font-size:0.8rem"><?= explode('.',$fl)[0] ?></div>
                <div class="small mt-1" style="color:<?= $activo?'#1a73e8':'#888' ?>;font-weight:<?= $activo?'700':'400' ?>"><?= explode('. ',$fl)[1] ?></div>
            </div>
            <?php if ($fk !== 'acreditado'): ?><div style="flex:0.5;height:3px;background:<?= array_search($fk, array_keys($faseSteps)) < array_search($cicloActivo['nivel_fase']??'diagnostico', array_keys($faseSteps)) ? '#28a745':'#e9ecef' ?>"></div><?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Ciclos de acreditación activos -->
<div class="row g-4 mb-4">
    <?php foreach ($ciclos as $ciclo): $pct = $ciclo['nivel_puntaje_actual']; ?>
    <div class="col-md-4">
        <div class="card p-3" style="border-left:4px solid <?= $ciclo['nivel_estandar_tipo']==='SUA'?'#28a745':($ciclo['nivel_estandar_tipo']==='ISO7101'?'#007bff':'#ffc107') ?>">
            <div class="d-flex justify-content-between mb-2">
                <strong><?= $tipoLabels[$ciclo['nivel_estandar_tipo']]??$ciclo['nivel_estandar_tipo'] ?></strong>
                <span class="badge bg-<?= $pct>=90?'success':($pct>=60?'warning':'danger') ?>"><?= $pct ?>%</span>
            </div>
            <div class="progress mb-2" style="height:10px"><div class="progress-bar bg-<?= $pct>=90?'success':($pct>=60?'primary':'warning') ?>" style="width:<?= $pct ?>%"></div></div>
            <div class="d-flex justify-content-between small text-muted mb-2">
                <span>Meta: <?= $ciclo['nivel_puntaje_objetivo'] ?>%</span>
                <span>Fase: <span class="badge bg-<?= $faseColors[$ciclo['nivel_fase']] ?>"><?= $ciclo['nivel_fase'] ?></span></span>
            </div>
            <div class="d-flex gap-2">
                <a href="/calidad/autoevaluacion?empresa_id=<?= $empresaId ?>" class="btn btn-sm btn-outline-primary w-100">Autoevaluación</a>
                <a href="/calidad/estandares" class="btn btn-sm btn-outline-secondary w-100">Estándares</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Acciones rápidas -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="/calidad/autoevaluacion?empresa_id=<?= $empresaId ?>" class="card p-3 text-center text-decoration-none h-100" style="border-left:4px solid #1a73e8">
            <i class="fas fa-clipboard-check fs-2 mb-2" style="color:#1a73e8"></i>
            <strong>Autoevaluación</strong>
            <small class="text-muted">Evalúa el cumplimiento de cada estándar</small>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/calidad/pamec" class="card p-3 text-center text-decoration-none h-100" style="border-left:4px solid #6f42c1">
            <i class="fas fa-search fs-2 mb-2" style="color:#6f42c1"></i>
            <strong>PAMEC</strong>
            <small class="text-muted">Programa de auditoría para mejoramiento</small>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/calidad/riesgos" class="card p-3 text-center text-decoration-none h-100" style="border-left:4px solid #dc3545">
            <i class="fas fa-bolt fs-2 mb-2" style="color:#dc3545"></i>
            <strong>Riesgos</strong>
            <small class="text-muted">Matriz de riesgos en seguridad</small>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/nc" class="card p-3 text-center text-decoration-none h-100" style="border-left:4px solid #ffc107">
            <i class="fas fa-triangle-exclamation fs-2 mb-2" style="color:#ffc107"></i>
            <strong>No Conformidades</strong>
            <small class="text-muted">Gestión de hallazgos y mejora</small>
        </a>
    </div>
</div>

<!-- Resumen de estándares por estado -->
<?php if (!empty($evidencias)): ?>
<div class="card-box">
    <div class="card-box-header"><i class="fas fa-table me-2"></i>Estado de Evidencias</div>
    <div class="card-box-body p-0" style="max-height:300px;overflow-y:auto">
        <table class="table-box small">
            <thead><tr><th>Estándar</th><th>Puntaje</th><th>Cumplimiento</th><th>Estado</th><th>Plan Mejora</th></tr></thead>
            <tbody>
            <?php 
            $evidencias = EstrateGiaCore::getInstance()->fetchAll(
                "SELECT ev.*, e.estandar_codigo, e.estandar_nombre FROM cal_evidencias_acreditacion ev JOIN cal_estandares_acreditacion e ON ev.evidencia_estandar_id=e.estandar_id WHERE ev.evidencia_empresa_id=:eid ORDER BY e.estandar_tipo, ev.evidencia_fecha_evaluacion DESC",
                ['eid'=>$empresaId]
            );
            foreach ($evidencias as $ev): 
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($ev['estandar_codigo']) ?></strong><br><small><?= htmlspecialchars(substr($ev['estandar_nombre'],0,50)) ?></small></td>
                <td><span class="badge bg-<?= ($ev['evidencia_puntaje']??0)>=90?'success':(($ev['evidencia_puntaje']??0)>=70?'warning':'danger') ?>"><?= $ev['evidencia_puntaje']??0 ?>%</span></td>
                <td><?= str_replace('_',' ',$ev['evidencia_cumplimiento']) ?></td>
                <td><span class="badge bg-<?= $ev['evidencia_estado']==='verificado'?'success':($ev['evidencia_estado']==='completado'?'primary':'warning') ?>"><?= $ev['evidencia_estado'] ?></span></td>
                <td><?= $ev['evidencia_plan_mejora']?'<i class="fas fa-check text-success"></i>':'-' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Actividades de Acreditación con Seguimiento -->
<h5 class="mb-3 mt-4"><i class="fas fa-tasks me-2"></i>Actividades Programadas</h5>
<div class="row g-4">
    <div class="col-md-7">
        <div class="card-box"><div class="card-box-header d-flex justify-content-between">
            <span><i class="fas fa-calendar-check me-2"></i>Seguimiento</span>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalActividad"><i class="fas fa-plus"></i></button>
        </div>
        <div class="card-box-body p-0" style="max-height:350px;overflow-y:auto"><table class="table-box small">
            <thead><tr><th>Actividad</th><th>Estándar</th><th>Responsable</th><th>Fin</th><th>Avance</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($actividades as $act): ?>
            <tr>
                <td><?= htmlspecialchars($act['act_descripcion']) ?></td>
                <td><span class="badge bg-light text-dark"><?= $act['act_estandar_tipo'] ?></span></td>
                <td><small><?= htmlspecialchars($act['responsable_nombre']??'-') ?></small></td>
                <td><?= $act['act_fecha_fin'] ?></td>
                <td><div class="progress" style="height:6px;width:60px"><div class="progress-bar bg-<?= $act['act_avance']>=80?'success':'primary' ?>" style="width:<?= $act['act_avance'] ?>%"></div></div><small><?= $act['act_avance'] ?>%</small></td>
                <td><span class="badge bg-<?= $act['act_estado']==='completada'?'success':($act['act_estado']==='en_proceso'?'primary':($act['act_estado']==='vencida'?'danger':'warning')) ?>"><?= $act['act_estado'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div></div>
    </div>
    <div class="col-md-5">
        <div class="card-box"><div class="card-box-header d-flex justify-content-between">
            <span><i class="fas fa-file-signature me-2"></i>Reportes a Entes de Control</span>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalReporte"><i class="fas fa-plus"></i></button>
        </div>
        <div class="card-box-body p-0" style="max-height:350px;overflow-y:auto">
            <?php $entes = []; foreach ($reportes as $r) $entes[$r['rep_ente_control']][] = $r; ?>
            <?php foreach ($entes as $ente => $reps): ?>
            <div class="p-2 px-3 bg-light small fw-bold"><?= htmlspecialchars($ente) ?></div>
            <?php foreach ($reps as $rep): ?>
            <div class="p-2 px-3 border-bottom small">
                <div class="d-flex justify-content-between">
                    <strong><?= htmlspecialchars($rep['rep_nombre']) ?></strong>
                    <span class="badge bg-<?= $rep['rep_estado']==='enviado'?'success':($rep['rep_estado']==='elaboracion'?'warning':'secondary') ?>"><?= $rep['rep_estado'] ?></span>
                </div>
                <div class="text-muted">Límite: <?= date('d/m/Y', strtotime($rep['rep_fecha_limite'])) ?> · <?= $rep['rep_periodicidad'] ?></div>
                <?php if ($rep['rep_norma']): ?><small class="text-muted"><?= htmlspecialchars($rep['rep_norma']) ?></small><?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>

<!-- MODAL Actividad -->
<div class="modal fade" id="modalActividad"><div class="modal-dialog"><form method="POST" action="/calidad/actividad/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5>Programar Actividad</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="text" name="descripcion" class="form-control form-control-sm mb-2" placeholder="Descripción *" required>
        <div class="row g-2 mb-2"><div class="col-6"><select name="estandar_tipo" class="form-select form-select-sm"><option value="SUA">SUA</option><option value="ISO7101">ISO 7101</option><option value="Habilitacion">Habilitación</option></select></div><div class="col-6"><input type="date" name="fecha_fin" class="form-control form-control-sm"></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Programar</button></div>
</form></div></div>

<!-- MODAL Reporte -->
<div class="modal fade" id="modalReporte"><div class="modal-dialog"><form method="POST" action="/calidad/reporte/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5>Registrar Reporte Regulatorio</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="Nombre del reporte *" required>
        <div class="row g-2 mb-2"><div class="col-6"><input type="text" name="ente_control" class="form-control form-control-sm" placeholder="Ente de control *" required></div><div class="col-6"><input type="text" name="norma" class="form-control form-control-sm" placeholder="Norma / Resolución"></div></div>
        <div class="row g-2"><div class="col-4"><select name="periodicidad" class="form-select form-select-sm"><option value="mensual">Mensual</option><option value="trimestral">Trimestral</option><option value="semestral">Semestral</option><option value="anual">Anual</option></select></div><div class="col-4"><input type="date" name="fecha_limite" class="form-control form-control-sm" required></div><div class="col-4"><select name="estado" class="form-select form-select-sm"><option value="pendiente">Pendiente</option><option value="elaboracion">Elaboración</option><option value="revision">Revisión</option><option value="enviado">Enviado</option></select></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Registrar</button></div>
</form></div></div>

<?php $moduloContexto = 'acreditación en salud'; require BASE_PATH . '/templates/hse/ia_panel.php'; ?>
