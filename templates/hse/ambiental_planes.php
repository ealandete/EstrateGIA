<!-- ============================================================ -->
<!-- PLANES DE TRABAJO AMBIENTAL                                    -->
<!-- ============================================================ -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">Planes de trabajo con actividades, responsables y seguimiento de avance</div>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalPlanTrabajo"><i class="fas fa-plus me-1"></i>Nuevo Plan</button>
</div>

<?php foreach ($planesTrabajo as $plan): $planId = $plan['plan_id'] ?? 0; ?>
<div class="card-box mb-3">
    <div class="card-box-header d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-tasks me-2"></i>
            <strong><?= htmlspecialchars($plan['plan_nombre'] ?? '') ?></strong>
            <span class="badge bg-<?= ($plan['plan_estado'] ?? '') === 'completado' ? 'success' : (($plan['plan_estado'] ?? '') === 'en_progreso' ? 'warning' : 'secondary') ?> ms-2"><?= htmlspecialchars($plan['plan_estado'] ?? '') ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="progress" style="width:120px;height:8px"><div class="progress-bar bg-success" style="width:<?= $plan['plan_porcentaje_avance'] ?? 0 ?>%"></div></div>
            <small class="text-muted"><?= number_format($plan['plan_porcentaje_avance'] ?? 0, 0) ?>%</small>
            <button class="btn btn-sm btn-outline-secondary" onclick="editarPlanTrabajo(<?= htmlspecialchars(json_encode($plan)) ?>)"><i class="fas fa-edit"></i></button>
        </div>
    </div>
    <div class="card-box-body p-0">
        <?php $actividades = $actividadesPorPlan[$planId] ?? []; ?>
        <?php if (!empty($actividades)): ?>
        <table class="table-box small mb-0">
            <thead><tr><th>Actividad</th><th>Inicio</th><th>Fin</th><th>Responsable</th><th>Avance</th><th>Estado</th></tr></thead>
            <tbody>
                <?php foreach ($actividades as $act): ?>
                <tr>
                    <td><?= htmlspecialchars($act['actividad_nombre'] ?? '') ?></td>
                    <td><?= htmlspecialchars($act['actividad_fecha_inicio'] ?? '') ?></td>
                    <td><?= htmlspecialchars($act['actividad_fecha_fin'] ?? '—') ?></td>
                    <td><small class="text-muted">ID: <?= htmlspecialchars($act['actividad_responsable_id'] ?? '—') ?></small></td>
                    <td>
                        <div class="progress" style="width:80px;height:5px"><div class="progress-bar bg-<?= ($act['actividad_porcentaje'] ?? 0) >= 100 ? 'success' : 'info' ?>" style="width:<?= $act['actividad_porcentaje'] ?? 0 ?>%"></div></div>
                        <small><?= number_format($act['actividad_porcentaje'] ?? 0, 0) ?>%</small>
                    </td>
                    <td><span class="badge bg-<?= ($act['actividad_estado'] ?? '') === 'completada' ? 'success' : (($act['actividad_estado'] ?? '') === 'en_progreso' ? 'warning' : 'secondary') ?>"><?= $act['actividad_estado'] ?? '' ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-3 text-muted"><small>Sin actividades. <a href="#" onclick="abrirModalActividad(<?= $planId ?>);return false">Agregar actividad</a></small></div>
        <?php endif; ?>
    </div>
    <div class="card-box-footer text-end">
        <button class="btn btn-sm btn-outline-success" onclick="abrirModalActividad(<?= $planId ?>)"><i class="fas fa-plus me-1"></i>Actividad</button>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($planesTrabajo)): ?>
<div class="text-center py-5 text-muted"><i class="fas fa-tasks" style="font-size:3rem;color:#ddd;display:block;margin-bottom:10px"></i>No hay planes de trabajo.<br><small>Cree un plan para organizar las actividades ambientales.</small></div>
<?php endif; ?>

<!-- Modal Plan Trabajo -->
<div class="modal fade" id="modalPlanTrabajo"><div class="modal-dialog"><form method="POST" action="/ambiental/plan-trabajo/crear" class="modal-content" id="formPlanTrabajo">
    <input type="hidden" name="id" id="pt_id">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? '' ?>">
    <div class="modal-header"><h5 id="modalPTTitle">Nuevo Plan de Trabajo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="small fw-bold mb-1">Nombre del Plan</label>
        <input type="text" name="nombre" id="pt_nombre" class="form-control form-control-sm mb-2" required>
        <label class="small fw-bold mb-1">Objetivo</label>
        <textarea name="objetivo" id="pt_objetivo" class="form-control form-control-sm mb-2" rows="2"></textarea>
        <div class="row g-2 mb-2">
            <div class="col-4"><label class="small fw-bold mb-1">A&ntilde;o</label><input type="number" name="anio" id="pt_anio" class="form-control form-control-sm" value="<?= $anio ?>" required></div>
            <div class="col-4"><label class="small fw-bold mb-1">Inicio</label><input type="date" name="fecha_inicio" id="pt_inicio" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-4"><label class="small fw-bold mb-1">Fin</label><input type="date" name="fecha_fin" id="pt_fin" class="form-control form-control-sm"></div>
        </div>
        <div class="row g-2">
            <div class="col-6"><label class="small fw-bold mb-1">Presupuesto</label><input type="number" step="0.01" name="presupuesto" id="pt_presupuesto" class="form-control form-control-sm" value="0"></div>
            <div class="col-6"><label class="small fw-bold mb-1">Estado</label><select name="estado" id="pt_estado" class="form-select form-select-sm"><option value="planificado">Planificado</option><option value="en_progreso">En Progreso</option><option value="completado">Completado</option></select></div>
        </div>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Guardar</button></div>
</form></div></div>

<!-- Modal Actividad -->
<div class="modal fade" id="modalActividad"><div class="modal-dialog"><form method="POST" action="/ambiental/plan-trabajo/actividad/crear" class="modal-content" id="formActividad">
    <input type="hidden" name="id" id="act_id">
    <input type="hidden" name="plan_id" id="act_plan_id">
    <div class="modal-header"><h5 id="modalActTitle">Nueva Actividad</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="small fw-bold mb-1">Nombre Actividad</label>
        <input type="text" name="nombre" id="act_nombre" class="form-control form-control-sm mb-2" required>
        <label class="small fw-bold mb-1">Descripci&oacute;n</label>
        <textarea name="descripcion" id="act_descripcion" class="form-control form-control-sm mb-2" rows="2"></textarea>
        <div class="row g-2 mb-2">
            <div class="col-4"><label class="small fw-bold mb-1">Inicio</label><input type="date" name="fecha_inicio" id="act_inicio" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-4"><label class="small fw-bold mb-1">Fin</label><input type="date" name="fecha_fin" id="act_fin" class="form-control form-control-sm"></div>
            <div class="col-4"><label class="small fw-bold mb-1">Avance %</label><input type="number" step="0.1" name="porcentaje" id="act_porcentaje" class="form-control form-control-sm" value="0"></div>
        </div>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Guardar</button></div>
</form></div></div>

<script>
function editarPlanTrabajo(p) {
    document.getElementById('modalPTTitle').textContent = 'Editar Plan de Trabajo';
    document.getElementById('formPlanTrabajo').action = '/ambiental/plan-trabajo/editar';
    document.getElementById('pt_id').value = p.plan_id || '';
    document.getElementById('pt_nombre').value = p.plan_nombre || '';
    document.getElementById('pt_objetivo').value = p.plan_objetivo || '';
    document.getElementById('pt_anio').value = p.plan_anio || '<?= $anio ?>';
    document.getElementById('pt_inicio').value = p.plan_fecha_inicio || '';
    document.getElementById('pt_fin').value = p.plan_fecha_fin || '';
    document.getElementById('pt_presupuesto').value = p.plan_presupuesto || 0;
    document.getElementById('pt_estado').value = p.plan_estado || 'planificado';
    new bootstrap.Modal(document.getElementById('modalPlanTrabajo')).show();
}
document.getElementById('modalPlanTrabajo').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalPTTitle').textContent = 'Nuevo Plan de Trabajo';
    document.getElementById('formPlanTrabajo').action = '/ambiental/plan-trabajo/crear';
    document.getElementById('pt_id').value = '';
    document.getElementById('formPlanTrabajo').reset();
});
function abrirModalActividad(planId) {
    document.getElementById('modalActTitle').textContent = 'Nueva Actividad';
    document.getElementById('formActividad').action = '/ambiental/plan-trabajo/actividad/crear';
    document.getElementById('act_id').value = '';
    document.getElementById('act_plan_id').value = planId;
    document.getElementById('formActividad').reset();
    new bootstrap.Modal(document.getElementById('modalActividad')).show();
}
</script>
