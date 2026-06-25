<div class="d-flex justify-content-between align-items-center mb-2">
    <div><h6 class="mb-0"><i class="fas fa-calendar-alt me-2" style="color:#0d6efd"></i>Plan de Trabajo Anual <?= $anio ?></h6></div>
    <?php if (!empty($planTrabajo)): ?>
    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditarPlan"><i class="fas fa-pen me-1"></i>Editar Plan</button>
    <?php endif; ?>
</div>

<?php if (empty($planTrabajo)): ?>
<div class="card-box">
    <div class="card-box-body text-center py-5">
        <i class="fas fa-calendar-alt" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
        <p class="text-muted mb-3">No hay plan de trabajo para <?= $anio ?></p>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearPlan"><i class="fas fa-plus me-1"></i>Crear Plan de Trabajo <?= $anio ?></button>
    </div>
</div>
<?php else: ?>

<?php
$totalAct = count($actividades);
$completadas = count(array_filter($actividades, fn($a) => ($a['sst_act_estado'] ?? '') === 'completada'));
$enProgreso = count(array_filter($actividades, fn($a) => ($a['sst_act_estado'] ?? '') === 'en_proceso'));
$avance = $totalAct > 0 ? round(array_sum(array_column($actividades, 'sst_act_avance') ?: [0]) / $totalAct) : 0;
?>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Estado</div><div class="stat-value"><?= htmlspecialchars($planTrabajo['plan_estado'] ?? 'pendiente') ?></div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Actividades Totales</div><div class="stat-value"><?= $totalAct ?></div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Completadas</div><div class="stat-value"><?= $completadas ?></div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Avance %</div><div class="stat-value"><?= $avance ?>%</div></div></div>
</div>

<?php if (!empty($actividades) && count(array_filter($actividades, fn($a)=>$a['sst_act_fecha_inicio']&&$a['sst_act_fecha_fin']))>0): ?>
<?php 
$minDate=null;$maxDate=null;
foreach($actividades as $a){if($a['sst_act_fecha_inicio']&&(!$minDate||$a['sst_act_fecha_inicio']<$minDate))$minDate=$a['sst_act_fecha_inicio'];if($a['sst_act_fecha_fin']&&(!$maxDate||$a['sst_act_fecha_fin']>$maxDate))$maxDate=$a['sst_act_fecha_fin'];}
$minDate=$minDate??date('Y-m-d');$maxDate=$maxDate??date('Y-m-d',strtotime('+12m'));
$totalDays=max(1,(strtotime($maxDate)-strtotime($minDate))/86400);
$mesesAbr=['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
?>
<div class="card-box mt-3"><div class="card-box-header"><i class="fas fa-chart-gantt me-2"></i>Cronograma (Gantt) <?= substr($minDate,0,4) ?></div>
<div class="card-box-body p-3">
<?php foreach($actividades as $a):
    if(!$a['sst_act_fecha_inicio']||!$a['sst_act_fecha_fin'])continue;
    $sp=max(0,(strtotime($a['sst_act_fecha_inicio'])-strtotime($minDate))/86400/$totalDays*100);
    $wp=max(2,(strtotime($a['sst_act_fecha_fin'])-strtotime($a['sst_act_fecha_inicio']))/86400/$totalDays*100);
    $c=$a['sst_act_estado']==='completada'?'#28a745':($a['sst_act_estado']==='en_proceso'?'#007bff':'#adb5bd');
?>
<div style="display:flex;align-items:center;gap:8px;padding:3px 0;font-size:0.75rem">
    <div style="width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($a['sst_act_nombre']) ?></div>
    <div style="flex:1;height:20px;background:#f0f0f0;border-radius:3px;position:relative">
        <div style="position:absolute;left:<?=$sp?>%;width:<?=$wp?>%;height:100%;background:<?=$c?>;border-radius:3px;min-width:4px" title="<?= $a['sst_act_fecha_inicio'].' → '.$a['sst_act_fecha_fin'] ?>"></div>
    </div>
    <div style="width:90px"><?= date('d/m',strtotime($a['sst_act_fecha_inicio'])).'-'.date('d/m',strtotime($a['sst_act_fecha_fin'])) ?></div>
</div>
<?php endforeach; ?>
<div style="display:flex;justify-content:space-between;font-size:0.65rem;color:#999;margin-top:4px;padding-left:188px">
    <span><?= $mesesAbr[(int)date('n',strtotime($minDate))] ?> <?= date('Y',strtotime($minDate)) ?></span>
    <span><?= $mesesAbr[(int)date('n',strtotime($maxDate))] ?> <?= date('Y',strtotime($maxDate)) ?></span>
</div></div></div>
<?php endif; ?>
<?php endif; ?>

<!-- Modal Crear Plan -->
<div class="modal fade" id="modalCrearPlan">
    <div class="modal-dialog">
        <form method="POST" action="/sst/plan/crear" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <input type="hidden" name="anio" value="<?= $anio ?>">
            <div class="modal-header"><h5>Crear Plan de Trabajo <?= $anio ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label small">Objetivo</label><textarea name="objetivo" class="form-control form-control-sm" rows="2" required></textarea></div>
                <div class="mb-2"><label class="form-label small">Alcance</label><textarea name="alcance" class="form-control form-control-sm" rows="2"></textarea></div>
                <div class="mb-2"><label class="form-label small">Presupuesto</label><input type="number" name="presupuesto" class="form-control form-control-sm" step="0.01" min="0"></div>
                <div class="mb-2"><label class="form-label small">Fecha Aprobaci&oacute;n</label><input type="date" name="fecha_aprobacion" class="form-control form-control-sm"></div>
                <div class="mb-2"><label class="form-label small">Plan Estrat&eacute;gico</label>
                    <select name="plan_estrategico_id" class="form-select form-select-sm">
                        <option value="">-- Seleccionar --</option>
                        <?php if (!empty($planesEstrategicos)): foreach ($planesEstrategicos as $pe): ?>
                        <option value="<?= $pe['plan_id'] ?>"><?= htmlspecialchars($pe['plan_nombre'] ?? '') ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Crear Plan</button></div>
        </form>
    </div>
</div>

<!-- Modal Editar Plan -->
<?php if (!empty($planTrabajo)): ?>
<div class="modal fade" id="modalEditarPlan">
    <div class="modal-dialog">
        <form method="POST" action="/sst/plan/actualizar" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <input type="hidden" name="id" value="<?= $planTrabajo['plan_id'] ?>">
            <div class="modal-header"><h5>Editar Plan de Trabajo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label small">Objetivo</label><textarea name="objetivo" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($planTrabajo['plan_objetivo'] ?? '') ?></textarea></div>
                <div class="mb-2"><label class="form-label small">Alcance</label><textarea name="alcance" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($planTrabajo['plan_alcance'] ?? '') ?></textarea></div>
                <div class="mb-2"><label class="form-label small">Presupuesto</label><input type="number" name="presupuesto" class="form-control form-control-sm" value="<?= $planTrabajo['plan_presupuesto'] ?? 0 ?>" step="0.01" min="0"></div>
                <div class="mb-2"><label class="form-label small">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="pendiente" <?= ($planTrabajo['plan_estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="en_progreso" <?= ($planTrabajo['plan_estado'] ?? '') === 'en_progreso' ? 'selected' : '' ?>>En Progreso</option>
                        <option value="completado" <?= ($planTrabajo['plan_estado'] ?? '') === 'completado' ? 'selected' : '' ?>>Completado</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Actualizar</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal Actividad -->
<div class="modal fade" id="modalActividad">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="/sst/plan/actividad/guardar" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <input type="hidden" name="plan_id" value="<?= $planTrabajo['plan_id'] ?? '' ?>">
            <div class="modal-header"><h5>A&ntilde;adir Actividad</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-8">
                        <label class="form-label small">Nombre de la Actividad *</label>
                        <input type="text" name="nombre" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Tipo</label>
                        <select name="tipo" class="form-select form-select-sm">
                            <option value="capacitacion">Capacitaci&oacute;n</option><option value="inspeccion">Inspecci&oacute;n</option><option value="examen">Examen</option><option value="simulacro">Simulacro</option><option value="auditoria">Auditor&iacute;a</option><option value="otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Fecha Inicio *</label>
                        <input type="date" name="fecha_inicio" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Fecha Fin *</label>
                        <input type="date" name="fecha_fin" class="form-control form-control-sm" required>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Responsable</label>
                        <select name="responsable_id" class="form-select form-select-sm">
                            <option value="">-- Seleccionar --</option>
                            <?php if (!empty($usuarios)): foreach ($usuarios as $u): ?>
                            <option value="<?= $u['usuario_id'] ?>"><?= htmlspecialchars($u['usuario_nombre'] ?? '') ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Estado</label>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="pendiente">Pendiente</option><option value="en_progreso">En Progreso</option><option value="completada">Completada</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
        </form>
    </div>
</div>

<!-- Modal Avance -->
<div class="modal fade" id="modalAvance">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="/sst/plan/actividad/avance" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <input type="hidden" name="id" id="avanceActividadId">
            <div class="modal-header"><h5>Actualizar Avance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small" id="avanceActividadNombre"></p>
                <label class="form-label small">Porcentaje de Avance (%)</label>
                <input type="range" name="avance" id="avanceRango" class="form-range" min="0" max="100" value="0" oninput="document.getElementById('avanceValor').value=this.value+'%'">
                <div class="input-group input-group-sm"><input type="text" id="avanceValor" class="form-control text-center" value="0%" readonly><span class="input-group-text">%</span></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Guardar Avance</button></div>
        </form>
    </div>
</div>

<script>
function abrirAvance(act) {
    document.getElementById('avanceActividadId').value = act.act_id || '';
    document.getElementById('avanceActividadNombre').innerText = act.act_nombre || '';
    document.getElementById('avanceRango').value = act.act_avance || 0;
    document.getElementById('avanceValor').value = (act.act_avance || 0) + '%';
    new bootstrap.Modal(document.getElementById('modalAvance')).show();
}
</script>
