<?php $updated = $_GET['updated'] ?? null; $deleted = $_GET['deleted'] ?? null; ?>
<?php if ($updated): ?><div class="alert alert-success">Ítem actualizado</div><?php endif; ?>
<?php if ($deleted): ?><div class="alert alert-info">Ítem eliminado</div><?php endif; ?>

<nav class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="/calidad">Calidad</a></li><li class="breadcrumb-item"><a href="/calidad/pamec">PAMEC</a></li><li class="breadcrumb-item active">Checklist</li></ol></nav>

<div class="d-flex justify-content-between mb-3">
    <h5><i class="fas fa-list-check me-2" style="color:#28a745"></i>Checklist Parametrizable</h5>
    <div>
        <?php if (!empty($tipos)): ?>
        <div class="btn-group btn-group-sm me-2">
            <a href="/calidad/checklist" class="btn btn-outline-secondary <?= empty($tipo)?'active':'' ?>">Todos</a>
            <?php foreach ($tipos as $t): ?>
            <a href="/calidad/checklist?tipo=<?= urlencode($t['item_servicio']) ?>" class="btn btn-outline-secondary <?= $tipo===$t['item_servicio']?'active':'' ?>"><?= htmlspecialchars($t['item_servicio']) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalChecklist"><i class="fas fa-plus me-1"></i>Nuevo Ítem</button>
    </div>
</div>

<?php if (empty($items)): ?>
<div class="card-box"><div class="card-box-body text-center py-4 text-muted">No hay ítems de checklist. Agregue el primero para comenzar a parametrizar sus verificaciones.</div></div>
<?php else: ?>
<div class="card-box"><div class="card-box-body p-0">
<table class="table-box">
    <thead><tr><th>Orden</th><th>Servicio</th><th>Criterio</th><th>Estándar</th><th>Tipo</th><th>Activo</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $it): ?>
    <tr>
        <td><?= $it['item_orden'] ?></td>
        <td><?= htmlspecialchars($it['item_servicio']) ?></td>
        <td><?= htmlspecialchars(substr($it['item_criterio'], 0, 80)) ?></td>
        <td><?= htmlspecialchars($it['item_estandar']) ?></td>
        <td><span class="badge bg-light text-dark"><?= str_replace('_',' ',$it['item_tipo']) ?></span></td>
        <td><?= $it['item_activo'] ? '<span class="text-success">Activo</span>' : '<span class="text-muted">Inactivo</span>' ?></td>
        <td>
            <button class="btn btn-sm btn-outline-secondary" onclick="editarChecklist(<?= $it['item_id'] ?>,'<?= addslashes($it['item_servicio']) ?>','<?= addslashes($it['item_criterio']) ?>','<?= addslashes($it['item_estandar']) ?>','<?= $it['item_tipo'] ?>',<?= $it['item_orden'] ?>,<?= $it['item_activo'] ?>)"><i class="fas fa-edit"></i></button>
            <form method="POST" action="/calidad/checklist/eliminar" class="d-inline" onsubmit="return confirm('¿Desactivar este ítem?')">
                <input type="hidden" name="item_id" value="<?= $it['item_id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>
<?php endif; ?>

<div class="card-box mt-4">
    <div class="card-box-header"><i class="fas fa-info-circle me-2"></i>Parametrización de Checklists</div>
    <div class="card-box-body small">
        <p>Los checklists parametrizables permiten estandarizar los criterios de verificación por servicio y tipo de evaluación. Cada ítem se asocia a:</p>
        <ul class="mb-0">
            <li><strong>Servicio:</strong> Área o servicio específico (Urgencias, UCI, Consulta Externa, Farmacia, etc.)</li>
            <li><strong>Tipo:</strong> Contexto de uso del ítem (PAMEC, ronda de calidad, auditoría, general)</li>
            <li><strong>Estándar:</strong> Referencia normativa (SUA, ISO 7101, Habilitación)</li>
            <li><strong>Orden:</strong> Secuencia de aplicación dentro del checklist</li>
        </ul>
    </div>
</div>

<!-- MODAL Checklist -->
<div class="modal fade" id="modalChecklist" tabindex="-1"><div class="modal-dialog">
<form method="POST" action="/calidad/checklist/crear" class="modal-content" id="formChecklist">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? ($_COOKIE['empresa_activa'] ?? 2) ?>">
    <div class="modal-header"><h5 class="modal-title" id="tituloChecklist">Nuevo Ítem de Checklist</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label class="form-label small">Servicio</label><input type="text" name="item_servicio" class="form-control form-control-sm" id="chkServicio" placeholder="Ej: Urgencias, UCI, Consulta Externa"></div>
        <div class="mb-2"><label class="form-label small">Criterio *</label><textarea name="item_criterio" class="form-control form-control-sm" rows="2" id="chkCriterio" placeholder="Criterio a verificar" required></textarea></div>
        <div class="row g-2 mb-2">
            <div class="col-5"><label class="form-label small">Estándar</label><input type="text" name="item_estandar" class="form-control form-control-sm" id="chkEstandar" placeholder="Ej: SUA 4.3.1"></div>
            <div class="col-4"><label class="form-label small">Tipo</label><select name="item_tipo" class="form-select form-select-sm" id="chkTipo"><option value="general">General</option><option value="pamec">PAMEC</option><option value="ronda_calidad">Ronda Calidad</option><option value="auditoria">Auditoría</option></select></div>
            <div class="col-3"><label class="form-label small">Orden</label><input type="number" name="item_orden" class="form-control form-control-sm" id="chkOrden" value="0"></div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
</form></div></div>

<script>
function editarChecklist(id, servicio, criterio, estandar, tipo, orden, activo) {
    document.getElementById('formChecklist').action = '/calidad/checklist/actualizar';
    document.getElementById('tituloChecklist').textContent = 'Editar Ítem de Checklist';
    document.getElementById('chkServicio').value = servicio;
    document.getElementById('chkCriterio').value = criterio;
    document.getElementById('chkEstandar').value = estandar;
    document.getElementById('chkTipo').value = tipo;
    document.getElementById('chkOrden').value = orden;
    var hi = document.querySelector('#formChecklist input[name=item_id]');
    if (!hi) { hi = document.createElement('input'); hi.type = 'hidden'; hi.name = 'item_id'; document.getElementById('formChecklist').appendChild(hi); }
    hi.value = id;
    var hia = document.querySelector('#formChecklist input[name=item_activo]');
    if (!hia) { hia = document.createElement('input'); hia.type = 'hidden'; hia.name = 'item_activo'; document.getElementById('formChecklist').appendChild(hia); }
    hia.value = activo;
    new bootstrap.Modal(document.getElementById('modalChecklist')).show();
}
</script>
