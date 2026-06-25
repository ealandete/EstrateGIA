<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Checklists de Evaluación de Proveedores</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalChecklist"><i class="fas fa-plus me-1"></i>Nueva Plantilla</button>
</div>

<div class="card-box">
    <div class="card-box-header">Plantillas Existentes</div>
    <div class="card-box-body p-0">
        <table class="table-box">
            <thead><tr><th>Nombre</th><th>Tipo Proveedor</th><th>Criterios</th><th>Activo</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($checklists)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No hay plantillas de checklist registradas</td></tr>
            <?php else: foreach ($checklists as $cl): ?>
            <tr>
                <td><strong><?= htmlspecialchars($cl['checklist_nombre']) ?></strong></td>
                <td><?= htmlspecialchars($cl['checklist_tipo_proveedor']) ?></td>
                <td><?php $criterios = json_decode($cl['checklist_criterios_json'] ?? '[]', true); echo count($criterios); ?> criterios</td>
                <td><span class="badge bg-<?= ($cl['checklist_activo'] ?? 1) ? 'success' : 'secondary' ?>"><?= ($cl['checklist_activo'] ?? 1) ? 'Sí' : 'No' ?></span></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="editarChecklist(<?= htmlspecialchars(json_encode($cl)) ?>)"><i class="fas fa-pen"></i></button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalChecklist">
    <div class="modal-dialog modal-lg">
        <form id="formChecklist" class="modal-content">
            <input type="hidden" name="checklist_id" id="chkId">
            <div class="modal-header">
                <h5 id="chkTitle">Nueva Plantilla de Checklist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small">Nombre de la plantilla</label>
                        <input type="text" name="checklist_nombre" id="chkNombre" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Tipo de Proveedor</label>
                        <select name="checklist_tipo_proveedor" id="chkTipo" class="form-select form-select-sm" required>
                            <option value="">Seleccionar...</option>
                            <option value="medicamentos">Medicamentos</option>
                            <option value="insumos">Insumos</option>
                            <option value="equipos">Equipos</option>
                            <option value="servicios">Servicios</option>
                            <option value="consultoria">Consultoría</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="mb-2 d-flex justify-content-between align-items-center">
                    <strong class="small">Criterios de Evaluación</strong>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="agregarCriterio()"><i class="fas fa-plus me-1"></i>Criterio</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm small" id="tblCriterios">
                        <thead><tr><th>Nombre</th><th>Peso (%)</th><th>Descripción</th><th></th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarChecklist()">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function agregarCriterio(nombre, peso, descripcion) {
    var tbody = document.getElementById('tblCriterios').querySelector('tbody');
    var row = document.createElement('tr');
    row.innerHTML = `<td><input type="text" class="form-control form-control-sm crit-nombre" value="${nombre||''}" required></td>
        <td style="width:100px"><input type="number" class="form-control form-control-sm crit-peso" value="${peso||''}" min="0" max="100" step="0.1"></td>
        <td><input type="text" class="form-control form-control-sm crit-desc" value="${descripcion||''}"></td>
        <td><button type="button" class="btn btn-sm text-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>`;
    tbody.appendChild(row);
}
function guardarChecklist() {
    var criterios = [];
    document.querySelectorAll('#tblCriterios tbody tr').forEach(function(r) {
        var n = r.querySelector('.crit-nombre').value.trim();
        var p = parseFloat(r.querySelector('.crit-peso').value) || 0;
        var d = r.querySelector('.crit-desc').value.trim();
        if (n) criterios.push({nombre:n, peso:p, descripcion:d});
    });
    var fd = new FormData();
    fd.append('checklist_id', document.getElementById('chkId').value);
    fd.append('checklist_nombre', document.getElementById('chkNombre').value);
    fd.append('checklist_tipo_proveedor', document.getElementById('chkTipo').value);
    fd.append('checklist_criterios_json', JSON.stringify(criterios));
    fetch('/proveedores/checklist/guardar', {method:'POST', body:fd})
        .then(function(r){ return r.text(); })
        .then(function(t){
            try { var j = JSON.parse(t); if (j.success) location.reload(); else alert(j.message||'Error'); }
            catch(e) { location.reload(); }
        });
}
function editarChecklist(cl) {
    document.getElementById('chkId').value = cl.checklist_id || '';
    document.getElementById('chkNombre').value = cl.checklist_nombre || '';
    document.getElementById('chkTipo').value = cl.checklist_tipo_proveedor || '';
    document.getElementById('chkTitle').textContent = 'Editar Plantilla de Checklist';
    document.querySelector('#tblCriterios tbody').innerHTML = '';
    var criterios = [];
    try { criterios = JSON.parse(cl.checklist_criterios_json || '[]'); } catch(e) {}
    criterios.forEach(function(c) { agregarCriterio(c.nombre, c.peso, c.descripcion); });
    new bootstrap.Modal(document.getElementById('modalChecklist')).show();
}
document.getElementById('modalChecklist').addEventListener('show.bs.modal', function(ev) {
    if (ev.relatedTarget && ev.relatedTarget.getAttribute('data-bs-target') === '#modalChecklist' && !document.getElementById('chkId').value) {
        document.getElementById('chkId').value = '';
        document.getElementById('chkNombre').value = '';
        document.getElementById('chkTipo').value = '';
        document.getElementById('chkTitle').textContent = 'Nueva Plantilla de Checklist';
        document.querySelector('#tblCriterios tbody').innerHTML = '';
        agregarCriterio();
    }
});
</script>
