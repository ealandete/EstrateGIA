<!-- ============================================================ -->
<!-- CONTROLES AMBIENTALES                                          -->
<!-- ============================================================ -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">Controles vinculados a aspectos ambientales con c&aacute;lculo de impacto residual</div>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalControl"><i class="fas fa-plus me-1"></i>Nuevo Control</button>
</div>

<div class="card-box">
    <div class="card-box-header"><i class="fas fa-shield-haltered me-2"></i>Controles Ambientales (<?= count($controles ?? []) ?>)</div>
    <div class="card-box-body p-0">
        <?php if (!empty($controles)): ?>
        <table class="table-box small mb-0">
            <thead><tr><th>Control</th><th>Aspecto Vinculado</th><th>Criticidad</th><th>Efectividad</th><th>Estado</th><th class="text-end">Acci&oacute;n</th></tr></thead>
            <tbody>
                <?php foreach ($controles as $c): ?>
                <tr>
                    <td><?= htmlspecialchars(mb_strimwidth($c['control_descripcion'] ?? '', 0, 50, '...')) ?></td>
                    <td><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($c['asp_descripcion'] ?? 'Sin vincular', 0, 40, '...')) ?></small></td>
                    <td><span class="badge bg-<?= ($c['control_criticidad'] ?? '') === 'alta' ? 'danger' : (($c['control_criticidad'] ?? '') === 'media' ? 'warning' : 'info') ?>"><?= htmlspecialchars($c['control_criticidad'] ?? '') ?></span></td>
                    <td><span class="badge bg-<?= ($c['control_efectividad'] ?? '') === 'alta' ? 'success' : (($c['control_efectividad'] ?? '') === 'media' ? 'warning' : 'danger') ?>"><?= htmlspecialchars($c['control_efectividad'] ?? '') ?></span></td>
                    <td>
                        <?php if (($c['control_efectivo'] ?? 0) == 1): ?>
                        <span class="badge bg-success">Efectivo</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary" onclick="editarControl(<?= htmlspecialchars(json_encode($c)) ?>)"><i class="fas fa-edit"></i></button>
                        <form method="POST" action="/ambiental/control/eliminar" class="d-inline" onsubmit="return confirm('Eliminar este control?')"><input type="hidden" name="id" value="<?= $c['control_id'] ?? '' ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-5 text-muted"><i class="fas fa-shield-haltered" style="font-size:3rem;color:#ddd;display:block;margin-bottom:10px"></i>No hay controles registrados.<br><small>Agregue controles y vinc&uacute;lelos a aspectos para calcular el impacto residual.</small></div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Control -->
<div class="modal fade" id="modalControl"><div class="modal-dialog"><form method="POST" action="/ambiental/control/crear" class="modal-content" id="formControl">
    <input type="hidden" name="id" id="ctrl_id">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? '' ?>">
    <div class="modal-header"><h5 id="modalControlTitle">Nuevo Control Ambiental</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="small fw-bold mb-1">Aspecto Ambiental Vinculado</label>
        <select name="asp_id" id="ctrl_asp_id" class="form-select form-select-sm mb-2">
            <option value="">-- Sin vincular --</option>
            <?php foreach ($aspectos as $a): ?>
            <option value="<?= $a['asp_id'] ?? '' ?>"><?= htmlspecialchars(($a['asp_codigo'] ?? '') . ' - ' . mb_strimwidth($a['asp_descripcion'] ?? '', 0, 50, '...')) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="small fw-bold mb-1">Descripci&oacute;n del Control</label>
        <textarea name="descripcion" id="ctrl_descripcion" class="form-control form-control-sm mb-2" rows="3" placeholder="Describa el control implementado" required></textarea>
        <div class="row g-2 mb-2">
            <div class="col-4">
                <label class="small fw-bold mb-1">Criticidad</label>
                <select name="criticidad" id="ctrl_criticidad" class="form-select form-select-sm">
                    <option value="alta">Alta</option>
                    <option value="media" selected>Media</option>
                    <option value="baja">Baja</option>
                </select>
            </div>
            <div class="col-4">
                <label class="small fw-bold mb-1">Efectividad</label>
                <select name="efectividad" id="ctrl_efectividad" class="form-select form-select-sm">
                    <option value="alta">Alta</option>
                    <option value="media" selected>Media</option>
                    <option value="baja">Baja</option>
                </select>
            </div>
            <div class="col-4">
                <label class="small fw-bold mb-1">Efectivo</label>
                <select name="efectivo" id="ctrl_efectivo" class="form-select form-select-sm">
                    <option value="0">No</option>
                    <option value="1">S&iacute;</option>
                </select>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-6">
                <label class="small fw-bold mb-1">Estado</label>
                <select name="estado" id="ctrl_estado" class="form-select form-select-sm">
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                    <option value="pendiente">Pendiente</option>
                </select>
            </div>
            <div class="col-6">
                <label class="small fw-bold mb-1">Fecha Implantaci&oacute;n</label>
                <input type="date" name="fecha_implantacion" id="ctrl_fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Guardar</button>
    </div>
</form></div></div>

<script>
function editarControl(c) {
    document.getElementById('modalControlTitle').textContent = 'Editar Control';
    document.getElementById('formControl').action = '/ambiental/control/editar';
    document.getElementById('ctrl_id').value = c.control_id || '';
    document.getElementById('ctrl_asp_id').value = c.asp_id || '';
    document.getElementById('ctrl_descripcion').value = c.control_descripcion || '';
    document.getElementById('ctrl_criticidad').value = c.control_criticidad || 'media';
    document.getElementById('ctrl_efectividad').value = c.control_efectividad || 'media';
    document.getElementById('ctrl_efectivo').value = c.control_efectivo || 0;
    document.getElementById('ctrl_estado').value = c.control_estado || 'activo';
    document.getElementById('ctrl_fecha').value = c.control_fecha_implantacion || '';
    new bootstrap.Modal(document.getElementById('modalControl')).show();
}
document.getElementById('modalControl').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalControlTitle').textContent = 'Nuevo Control Ambiental';
    document.getElementById('formControl').action = '/ambiental/control/crear';
    document.getElementById('ctrl_id').value = '';
    document.getElementById('formControl').reset();
});
</script>
