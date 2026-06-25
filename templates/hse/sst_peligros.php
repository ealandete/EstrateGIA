<div class="d-flex justify-content-between align-items-center mb-2">
    <div><h6 class="mb-0"><i class="fas fa-triangle-exclamation me-2" style="color:#ffc107"></i>Matriz de Peligros y Valoraci&oacute;n de Riesgos</h6></div>
    <div class="d-flex gap-2">
        <input type="text" id="busquedaPeligros" class="form-control form-control-sm" style="width:250px" placeholder="Buscar peligro..." onkeydown="if(event.key==='Enter')buscarPeligros()">
        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalPeligro" onclick="limpiarModalPeligro()"><i class="fas fa-plus me-1"></i>Nuevo Peligro</button>
    </div>
</div>

<div class="card-box">
    <div class="card-box-body p-0">
    <?php if (empty($peligros)): ?>
        <?php if (!empty($_GET['buscar'])): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-search" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
            <p class="mb-2">No se encontraron resultados para '<?= htmlspecialchars($_GET['buscar']) ?>'</p>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-triangle-exclamation" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
            <p class="mb-2">No hay peligros registrados</p>
            <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalPeligro" onclick="limpiarModalPeligro()"><i class="fas fa-plus me-1"></i>Registrar primer peligro</button>
        </div>
        <?php endif; ?>
    <?php else: ?>
    <table class="table-box small mb-0">
        <thead><tr>
            <th>C&oacute;digo</th><th>Descripci&oacute;n</th><th>Tipo</th><th>Prob.</th><th>Cons.</th><th>Nivel</th><th>Proceso</th><th>Estado</th><th class="text-center" style="width:100px">Acciones</th>
        </tr></thead>
        <tbody>
        <?php foreach ($peligros as $p): ?>
        <tr>
            <td><strong><?= htmlspecialchars($p['peligro_codigo']) ?></strong></td>
            <td><?= htmlspecialchars(mb_substr($p['peligro_descripcion'] ?? '', 0, 60)) ?></td>
            <td><?= htmlspecialchars($p['peligro_tipo'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['peligro_probabilidad'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['peligro_consecuencia'] ?? '') ?></td>
            <td><span class="badge bg-<?= ($p['peligro_nivel'] ?? '') === 'inaceptable' ? 'danger' : (($p['peligro_nivel'] ?? '') === 'importante' ? 'warning' : (($p['peligro_nivel'] ?? '') === 'moderado' ? 'info' : 'success')) ?>"><?= htmlspecialchars($p['peligro_nivel'] ?? '') ?></span></td>
            <td><?= htmlspecialchars($p['proceso_nombre'] ?? $p['peligro_proceso_id'] ?? '') ?></td>
            <td><span class="badge bg-<?= ($p['peligro_estado'] ?? '') === 'controlado' ? 'success' : 'warning' ?>"><?= htmlspecialchars($p['peligro_estado'] ?? '') ?></span></td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary" title="Editar" onclick="editarPeligro(<?= htmlspecialchars(json_encode($p)) ?>)"><i class="fas fa-pen"></i></button>
                <form method="POST" action="/sst/peligro/eliminar" style="display:inline" onsubmit="return confirm('&iquest;Eliminar este peligro?')">
                    <input type="hidden" name="id" value="<?= $p['peligro_id'] ?>">
                    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                    <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalPeligro">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="/sst/peligro/guardar" class="modal-content" id="formPeligro">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <input type="hidden" name="id" id="peligroId">
            <div class="modal-header"><h5 id="peligroModalTitle">Nuevo Peligro</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-6"><label class="form-label small">Descripci&oacute;n *</label><textarea name="descripcion" id="peligroDescripcion" class="form-control form-control-sm" rows="2" required></textarea></div>
                    <div class="col-md-6"><label class="form-label small">C&oacute;digo</label><input type="text" name="codigo" id="peligroCodigo" class="form-control form-control-sm"></div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small">Tipo</label>
                        <select name="tipo" id="peligroTipo" class="form-select form-select-sm">
                            <option value="fisico">F&iacute;sico</option><option value="quimico">Qu&iacute;mico</option><option value="biologico">Biol&oacute;gico</option><option value="ergonomico">Ergon&oacute;mico</option><option value="psicosocial">Psicosocial</option><option value="mecanico">Mec&aacute;nico</option><option value="electrico">El&eacute;ctrico</option><option value="locativo">Locativo</option><option value="publico">P&uacute;blico</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Probabilidad</label>
                        <select name="probabilidad" id="peligroProbabilidad" class="form-select form-select-sm">
                            <option value="baja">Baja</option><option value="media">Media</option><option value="alta">Alta</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Consecuencia</label>
                        <select name="consecuencia" id="peligroConsecuencia" class="form-select form-select-sm">
                            <option value="leve">Leve</option><option value="moderada">Moderada</option><option value="grave">Grave</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Nivel de Riesgo</label>
                        <select name="nivel" id="peligroNivel" class="form-select form-select-sm">
                            <option value="aceptable">Aceptable</option><option value="moderado">Moderado</option><option value="importante">Importante</option><option value="inaceptable">Inaceptable</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Proceso</label>
                        <select name="proceso_id" id="peligroProcesoId" class="form-select form-select-sm">
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($procesos as $pro): ?>
                            <option value="<?= $pro['proceso_id'] ?>"><?= htmlspecialchars($pro['proceso_nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Controles Existentes</label>
                    <textarea name="controles" id="peligroControles" class="form-control form-control-sm" rows="2"></textarea>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">Estado</label>
                        <select name="estado" id="peligroEstado" class="form-select form-select-sm">
                            <option value="identificado">Identificado</option><option value="evaluado">Evaluado</option><option value="controlado">Controlado</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-warning">Guardar</button></div>
        </form>
    </div>
</div>

<script>
var buscar = '<?= addslashes($_GET['buscar'] ?? '') ?>';
function buscarPeligros() {
    var val = document.getElementById('busquedaPeligros').value.trim();
    location.href = '?seccion=peligros' + (val ? '&buscar=' + encodeURIComponent(val) : '');
}
function limpiarModalPeligro() {
    document.getElementById('peligroModalTitle').innerText = 'Nuevo Peligro';
    document.getElementById('formPeligro').action = '/sst/peligro/guardar';
    document.getElementById('peligroId').value = '';
    document.getElementById('peligroDescripcion').value = '';
    document.getElementById('peligroCodigo').value = '';
    document.getElementById('peligroTipo').value = 'fisico';
    document.getElementById('peligroProbabilidad').value = 'baja';
    document.getElementById('peligroConsecuencia').value = 'leve';
    document.getElementById('peligroNivel').value = 'aceptable';
    document.getElementById('peligroProcesoId').value = '';
    document.getElementById('peligroControles').value = '';
    document.getElementById('peligroEstado').value = 'identificado';
}
function editarPeligro(p) {
    document.getElementById('peligroModalTitle').innerText = 'Editar Peligro';
    document.getElementById('formPeligro').action = '/sst/peligro/actualizar';
    document.getElementById('peligroId').value = p.peligro_id || '';
    document.getElementById('peligroDescripcion').value = p.peligro_descripcion || '';
    document.getElementById('peligroCodigo').value = p.peligro_codigo || '';
    document.getElementById('peligroTipo').value = p.peligro_tipo || 'fisico';
    document.getElementById('peligroProbabilidad').value = p.peligro_probabilidad || 'baja';
    document.getElementById('peligroConsecuencia').value = p.peligro_consecuencia || 'leve';
    document.getElementById('peligroNivel').value = p.peligro_nivel || 'aceptable';
    document.getElementById('peligroProcesoId').value = p.peligro_proceso_id || '';
    document.getElementById('peligroControles').value = p.peligro_controles || '';
    document.getElementById('peligroEstado').value = p.peligro_estado || 'identificado';
    new bootstrap.Modal(document.getElementById('modalPeligro')).show();
}
</script>
