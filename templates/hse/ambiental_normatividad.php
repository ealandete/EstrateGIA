<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">Requisitos legales y normativos ambientales aplicables</div>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalNorma"><i class="fas fa-plus me-1"></i>Nuevo Requisito</button>
</div>

<div class="card-box">
    <div class="card-box-header"><i class="fas fa-scale-balanced me-2"></i>Normatividad Ambiental (<?= count($reqLegales ?? []) ?>)</div>
    <div class="card-box-body p-0">
        <?php if (!empty($reqLegales)): ?>
        <table class="table-box small mb-0">
            <thead><tr><th>Norma</th><th>Descripci&oacute;n</th><th>Entidad</th><th>Periodicidad</th><th>Cumplimiento</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($reqLegales as $rl): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($rl['norma'] ?? '') ?></strong></td>
                    <td><?= htmlspecialchars(mb_strimwidth($rl['descripcion'] ?? '', 0, 50, '...')) ?></td>
                    <td><?= htmlspecialchars($rl['entidad'] ?? '') ?></td>
                    <td><?= htmlspecialchars($rl['periodicidad'] ?? '') ?></td>
                    <td><span class="badge bg-<?= ($rl['cumplimiento'] ?? '') === 'cumple' ? 'success' : (($rl['cumplimiento'] ?? '') === 'parcial' ? 'warning' : 'danger') ?>"><?= htmlspecialchars($rl['cumplimiento'] ?? '') ?></span></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary" onclick="editarReqLegal(<?= htmlspecialchars(json_encode($rl)) ?>)"><i class="fas fa-edit"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-5 text-muted"><i class="fas fa-scale-balanced" style="font-size:3rem;color:#ddd;display:block;margin-bottom:10px"></i>No hay requisitos legales registrados.<br><small>Agregue las normas ambientales aplicables a la organizaci&oacute;n.</small></div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalNorma"><div class="modal-dialog"><form method="POST" action="/ambiental/norma/guardar" class="modal-content" id="formNorma">
    <input type="hidden" name="id" id="rl_id">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? '' ?>">
    <input type="hidden" name="anio" value="<?= $anio ?>">
    <div class="modal-header"><h5 id="modalNormaTitle">Nuevo Requisito Legal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="small fw-bold mb-1">Norma / Regulaci&oacute;n</label>
        <input type="text" name="norma" id="rl_norma" class="form-control form-control-sm mb-2" placeholder="Ej: Decreto 1076/2015, ISO 14001" required>
        <label class="small fw-bold mb-1">Descripci&oacute;n del Requisito</label>
        <textarea name="descripcion" id="rl_descripcion" class="form-control form-control-sm mb-2" rows="2" placeholder="Describa el requisito legal aplicable" required></textarea>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="small fw-bold mb-1">Entidad</label>
                <input type="text" name="entidad" id="rl_entidad" class="form-control form-control-sm" placeholder="Ej: CAR, ANLA, MinAmbiente">
            </div>
            <div class="col-6">
                <label class="small fw-bold mb-1">Periodicidad</label>
                <select name="periodicidad" id="rl_periodicidad" class="form-select form-select-sm">
                    <option value="">Seleccione...</option>
                    <option value="unica_vez">&Uacute;nica vez</option>
                    <option value="mensual">Mensual</option>
                    <option value="trimestral">Trimestral</option>
                    <option value="semestral">Semestral</option>
                    <option value="anual">Anual</option>
                    <option value="bianual">Bianual</option>
                    <option value="permanente">Permanente</option>
                </select>
            </div>
        </div>
        <label class="small fw-bold mb-1">Estado de Cumplimiento</label>
        <select name="cumplimiento" id="rl_cumplimiento" class="form-select form-select-sm mb-2">
            <option value="">Seleccione...</option>
            <option value="cumple">Cumple</option>
            <option value="parcial">Cumplimiento Parcial</option>
            <option value="no_cumple">No Cumple</option>
            <option value="no_aplica">No Aplica</option>
        </select>
        <label class="small fw-bold mb-1">Evidencia / Observaciones</label>
        <textarea name="evidencia" id="rl_evidencia" class="form-control form-control-sm" rows="2" placeholder="Evidencia del cumplimiento u observaciones"></textarea>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success btn-sm">Guardar Requisito</button></div>
</form></div></div>

<script>
function editarReqLegal(r) {
    document.getElementById('modalNormaTitle').innerText='Actualizar Requisito Legal';
    document.getElementById('rl_id').value=r.id||'';
    document.getElementById('rl_norma').value=r.norma||'';
    document.getElementById('rl_descripcion').value=r.descripcion||'';
    document.getElementById('rl_entidad').value=r.entidad||'';
    document.getElementById('rl_periodicidad').value=r.periodicidad||'';
    document.getElementById('rl_cumplimiento').value=r.cumplimiento||'';
    document.getElementById('rl_evidencia').value=r.evidencia||'';
    new bootstrap.Modal(document.getElementById('modalNorma')).show();
}
document.getElementById('modalNorma').addEventListener('hidden.bs.modal',function(){
    document.getElementById('modalNormaTitle').innerText='Nuevo Requisito Legal';
    document.getElementById('formNorma').reset();
});
</script>
