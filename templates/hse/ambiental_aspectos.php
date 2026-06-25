<div class="row g-2 mb-2">
    <div class="col-md-3">
        <select class="form-select form-select-sm" id="filtroRecurso" onchange="location.href='?seccion=aspectos&anio=<?= $anio ?>&recurso='+this.value">
            <option value="">Todos los recursos</option>
            <?php foreach ($recursos as $k => $v): ?>
            <option value="<?= $k ?>" <?= ($_GET['recurso'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select form-select-sm" id="filtroArea" onchange="location.href='?seccion=aspectos&anio=<?= $anio ?>&recurso=<?= $_GET['recurso'] ?? '' ?>&area_id='+this.value">
            <option value="">Todas las &aacute;reas</option>
            <?php foreach ($procesos as $p): ?>
            <option value="<?= $p['proceso_id'] ?>" <?= ($_GET['area_id'] ?? '') == $p['proceso_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['proceso_nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAspecto"><i class="fas fa-plus me-1"></i>Nuevo Aspecto</button>
    </div>
</div>

<div class="card-box">
    <div class="card-box-header"><i class="fas fa-seedling me-2"></i>Matriz AIA - Aspectos e Impactos Ambientales (<?= count($aspectos ?? []) ?>)</div>
    <div class="card-box-body p-0">
        <?php if (!empty($aspectos)): ?>
        <div class="table-responsive">
        <table class="table-box small mb-0">
            <thead><tr>
                <th>C&oacute;digo</th><th>Recurso</th><th>Aspecto</th><th>Tipo</th><th>Significancia</th>
                <th>Impacto Residual</th><th>Controles</th><th>Estado</th><th class="text-end">Acciones</th>
            </tr></thead>
            <tbody>
                <?php foreach ($aspectos as $a):
                    $residual = (float)($a['asp_impacto_residual'] ?? 0);
                    $controles = (int)($a['asp_controles_efectivos'] ?? 0);
                    $recursoIcon = ['agua' => 'droplet', 'aire' => 'wind', 'suelo' => 'earth-americas', 'flora' => 'leaf', 'fauna' => 'paw', 'energia' => 'bolt', 'residuos' => 'trash'];
                    $icono = htmlspecialchars($recursoIcon[$a['asp_recurso'] ?? ''] ?? 'circle');
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($a['asp_codigo'] ?? '') ?></strong></td>
                    <td><i class="fas fa-<?= $icono ?> me-1" title="<?= htmlspecialchars($a['asp_recurso'] ?? '') ?>"></i><?= htmlspecialchars(ucfirst($a['asp_recurso'] ?? '')) ?></td>
                    <td><span title="<?= htmlspecialchars($a['asp_descripcion'] ?? '') ?>"><?= htmlspecialchars(mb_strimwidth($a['asp_descripcion'] ?? '', 0, 45, '...')) ?></span></td>
                    <td><?= htmlspecialchars($a['asp_tipo'] ?? '') ?></td>
                    <td><span class="badge bg-<?= ($a['asp_significancia'] ?? '') === 'critico' ? 'danger' : (($a['asp_significancia'] ?? '') === 'alto' ? 'warning' : (($a['asp_significancia'] ?? '') === 'medio' ? 'info' : 'secondary')) ?>"><?= htmlspecialchars($a['asp_significancia'] ?? '') ?></span></td>
                    <td>
                        <span class="badge bg-<?= $residual >= 7 ? 'danger' : ($residual >= 4 ? 'warning' : 'success') ?>"><?= number_format($residual, 1) ?></span>
                        <small class="text-muted ms-1">/10</small>
                    </td>
                    <td><span class="badge bg-<?= $controles > 0 ? 'success' : 'secondary' ?>"><?= $controles ?></span></td>
                    <td><span class="badge bg-<?= ($a['asp_estado'] ?? '') === 'controlado' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($a['asp_estado'] ?? '') ?></span></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary" onclick="editarAspecto(<?= htmlspecialchars(json_encode($a)) ?>)"><i class="fas fa-edit"></i></button>
                        <form method="POST" action="/ambiental/aspecto/eliminar" class="d-inline" onsubmit="return confirm('Eliminar este aspecto ambiental?')"><input type="hidden" name="id" value="<?= $a['asp_id'] ?? '' ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted"><i class="fas fa-seedling" style="font-size:3rem;color:#ddd;display:block;margin-bottom:10px"></i>No hay aspectos ambientales registrados.<br><small>Agregue aspectos para iniciar la identificaci&oacute;n de impactos.</small></div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalAspecto"><div class="modal-dialog modal-lg"><form method="POST" action="/ambiental/aspecto/guardar" class="modal-content" id="formAspecto">
    <input type="hidden" name="id" id="asp_id">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? '' ?>">
    <div class="modal-header"><h5 id="modalAspectoTitle">Nuevo Aspecto Ambiental</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2">
            <div class="col-md-4">
                <label class="small fw-bold mb-1">C&oacute;digo</label>
                <input type="text" name="codigo" id="asp_codigo" class="form-control form-control-sm" placeholder="ASP-2025-001" required>
            </div>
            <div class="col-md-4">
                <label class="small fw-bold mb-1">Recurso Afectado</label>
                <select name="recurso" id="asp_recurso" class="form-select form-select-sm" required>
                    <option value="">Seleccione...</option>
                    <option value="agua">Agua</option>
                    <option value="aire">Aire</option>
                    <option value="suelo">Suelo</option>
                    <option value="flora">Flora</option>
                    <option value="fauna">Fauna</option>
                    <option value="energia">Energ&iacute;a</option>
                    <option value="residuos">Residuos</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="small fw-bold mb-1">&Aacute;rea / Proceso</label>
                <select name="area_id" id="asp_area" class="form-select form-select-sm">
                    <option value="">Seleccione...</option>
                    <?php foreach ($procesos as $p): ?>
                    <option value="<?= $p['proceso_id'] ?>"><?= htmlspecialchars($p['proceso_nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <label class="small fw-bold mb-1">Descripci&oacute;n del Aspecto</label>
        <input type="text" name="descripcion" id="asp_descripcion" class="form-control form-control-sm mb-2" placeholder="Describa el aspecto ambiental" required>
        <label class="small fw-bold mb-1">Descripci&oacute;n Operativa Detallada</label>
        <textarea name="operacion_descripcion" id="asp_operacion" class="form-control form-control-sm mb-2" rows="3" placeholder="Describa la actividad realizada y su impacto ambiental. Ej: En el &aacute;rea de cirug&iacute;a se utilizan gases anest&eacute;sicos que contribuyen al calentamiento global."></textarea>
        <div class="row g-2 mb-2">
            <div class="col-md-4">
                <label class="small fw-bold mb-1">Tipo</label>
                <select name="tipo" id="asp_tipo" class="form-select form-select-sm" required>
                    <option value="">Seleccione...</option>
                    <option value="consumo_agua">Consumo de agua</option>
                    <option value="consumo_energia">Consumo de energ&iacute;a</option>
                    <option value="generacion_residuos">Generaci&oacute;n de residuos</option>
                    <option value="emision_atmosferica">Emisi&oacute;n atmosf&eacute;rica</option>
                    <option value="vertimiento">Vertimiento</option>
                    <option value="ruido">Ruido ambiental</option>
                    <option value="consumo_materias">Consumo de materias primas</option>
                    <option value="afectacion_flora">Afectaci&oacute;n a flora</option>
                    <option value="afectacion_fauna">Afectaci&oacute;n a fauna</option>
                    <option value="ocupacion_suelo">Ocupaci&oacute;n del suelo</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="small fw-bold mb-1">Significancia</label>
                <select name="significancia" id="asp_significancia" class="form-select form-select-sm" required>
                    <option value="critico">Cr&iacute;tico</option>
                    <option value="alto">Alto</option>
                    <option value="medio" selected>Medio</option>
                    <option value="bajo">Bajo</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="small fw-bold mb-1">Estado</label>
                <select name="estado" id="asp_estado" class="form-select form-select-sm">
                    <option value="identificado">Identificado</option>
                    <option value="evaluado">Evaluado</option>
                    <option value="controlado">Controlado</option>
                    <option value="en_plan">En Plan de Acci&oacute;n</option>
                </select>
            </div>
        </div>
        <label class="small fw-bold mb-1">Impacto Ambiental</label>
        <textarea name="impacto" id="asp_impacto" class="form-control form-control-sm mb-2" rows="2" placeholder="Describa el impacto ambiental generado"></textarea>
        <div class="row g-2 mb-2">
            <div class="col-md-6">
                <label class="small fw-bold mb-1">Controles Existentes</label>
                <input type="text" name="controles" id="asp_controles" class="form-control form-control-sm" placeholder="Describa los controles actuales">
            </div>
            <div class="col-md-6">
                <label class="small fw-bold mb-1">Plan de Acci&oacute;n Actual</label>
                <input type="text" name="plan_accion_actual" id="asp_plan_accion" class="form-control form-control-sm" placeholder="Plan de acci&oacute;n vigente">
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="small fw-bold mb-1">C&aacute;lculo Posible</label>
                <input type="text" name="calculo_posible" id="asp_calculo" class="form-control form-control-sm" placeholder="Ej: Medici&oacute;n directa, estimaci&oacute;n">
            </div>
            <div class="col-md-6">
                <label class="small fw-bold mb-1">Proporci&oacute;n Cient&iacute;fica Estimada</label>
                <input type="text" name="proporcion_cientificamente_estimada" id="asp_proporcion" class="form-control form-control-sm" placeholder="Ej: Factor de emisi&oacute;n IPCC">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Guardar</button>
    </div>
</form></div></div>

<script>
function editarAspecto(a) {
    document.getElementById('modalAspectoTitle').textContent = 'Editar Aspecto Ambiental';
    document.getElementById('formAspecto').action = '/ambiental/aspecto/editar';
    document.getElementById('asp_id').value = a.asp_id || '';
    document.getElementById('asp_codigo').value = a.asp_codigo || '';
    document.getElementById('asp_recurso').value = a.asp_recurso || '';
    document.getElementById('asp_area').value = a.asp_area_id || '';
    document.getElementById('asp_descripcion').value = a.asp_descripcion || '';
    document.getElementById('asp_operacion').value = a.asp_operacion_descripcion || '';
    document.getElementById('asp_tipo').value = a.asp_tipo || '';
    document.getElementById('asp_significancia').value = a.asp_significancia || 'medio';
    document.getElementById('asp_estado').value = a.asp_estado || 'identificado';
    document.getElementById('asp_impacto').value = a.asp_impacto || '';
    document.getElementById('asp_controles').value = a.asp_controles || '';
    document.getElementById('asp_plan_accion').value = a.asp_plan_accion_actual || '';
    document.getElementById('asp_calculo').value = a.asp_calculo_posible || '';
    document.getElementById('asp_proporcion').value = a.asp_proporcion_cientificamente_estimada || '';
    new bootstrap.Modal(document.getElementById('modalAspecto')).show();
}
document.getElementById('modalAspecto').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalAspectoTitle').textContent = 'Nuevo Aspecto Ambiental';
    document.getElementById('formAspecto').action = '/ambiental/aspecto/guardar';
    document.getElementById('asp_id').value = '';
    document.getElementById('formAspecto').reset();
});
</script>
