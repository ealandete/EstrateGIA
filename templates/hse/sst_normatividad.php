<div class="d-flex justify-content-between align-items-center mb-2">
    <div><h6 class="mb-0"><i class="fas fa-scale-balanced me-2" style="color:#6f42c1"></i>Requisitos Legales y Normatividad</h6></div>
    <div class="d-flex gap-2">
        <input type="text" id="busquedaNormas" class="form-control form-control-sm" style="width:250px" placeholder="Buscar por norma..." onkeydown="if(event.key==='Enter')buscarNormas()">
        <button class="btn btn-purple btn-sm" data-bs-toggle="modal" data-bs-target="#modalRequisito"><i class="fas fa-plus me-1"></i>Nuevo Requisito</button>
    </div>
</div>

<div class="card-box">
    <div class="card-box-body p-0">
    <?php if (empty($reqLegales)): ?>
        <?php if (!empty($_GET['buscar'])): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-search" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
            <p class="mb-2">No se encontraron resultados para '<?= htmlspecialchars($_GET['buscar']) ?>'</p>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-scale-balanced" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
            <p class="mb-2">No hay requisitos legales registrados</p>
            <button class="btn btn-outline-purple btn-sm" data-bs-toggle="modal" data-bs-target="#modalRequisito"><i class="fas fa-plus me-1"></i>Registrar primer requisito</button>
        </div>
        <?php endif; ?>
    <?php else: ?>
    <table class="table-box small mb-0">
        <thead><tr>
            <th>Norma</th><th>Art&iacute;culo</th><th>Descripci&oacute;n</th><th>Entidad</th><th>Periodicidad</th><th>L&iacute;mite</th><th>Cumplimiento</th><th class="text-center" style="width:100px">Acciones</th>
        </tr></thead>
        <tbody>
        <?php foreach ($reqLegales as $req): ?>
        <tr>
            <td><strong><?= htmlspecialchars($req['req_norma'] ?? '') ?></strong></td>
            <td><?= htmlspecialchars($req['req_articulo'] ?? '') ?></td>
            <td><?= htmlspecialchars(mb_substr($req['req_descripcion'] ?? '', 0, 60)) ?></td>
            <td><?= htmlspecialchars($req['req_entidad'] ?? '') ?></td>
            <td><?= htmlspecialchars($req['req_periodicidad'] ?? '') ?></td>
            <td><?= htmlspecialchars($req['req_limite'] ?? '') ?></td>
            <td>
                <?php $cumpl = $req['req_cumplimiento'] ?? 'pendiente'; ?>
                <span class="badge bg-<?= $cumpl === 'cumple' ? 'success' : ($cumpl === 'parcial' ? 'warning' : 'danger') ?>"><?= ucfirst(htmlspecialchars($cumpl)) ?></span>
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-purple" title="Actualizar cumplimiento" onclick="abrirCumplimiento(<?= htmlspecialchars(json_encode($req)) ?>)"><i class="fas fa-check-double"></i></button>
                <form method="POST" action="/sst/normatividad/eliminar" style="display:inline" onsubmit="return confirm('&iquest;Eliminar este requisito?')">
                    <input type="hidden" name="id" value="<?= $req['req_id'] ?>">
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

<!-- Modal Nuevo Requisito -->
<div class="modal fade" id="modalRequisito">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="/sst/normatividad/guardar" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <div class="modal-header"><h5>Registrar Requisito Legal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Norma *</label>
                        <input type="text" name="norma" class="form-control form-control-sm" placeholder="Ej: Decreto 1072/2015" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Art&iacute;culo</label>
                        <input type="text" name="articulo" class="form-control form-control-sm" placeholder="Ej: Art. 2.2.4.6.8">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Descripci&oacute;n *</label>
                    <textarea name="descripcion" class="form-control form-control-sm" rows="2" required></textarea>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small">Entidad</label>
                        <input type="text" name="entidad" class="form-control form-control-sm" placeholder="Ej: MinTrabajo">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Periodicidad</label>
                        <select name="periodicidad" class="form-select form-select-sm">
                            <option value="unica">&Uacute;nica</option><option value="diaria">Diaria</option><option value="semanal">Semanal</option><option value="mensual">Mensual</option><option value="trimestral">Trimestral</option><option value="semestral">Semestral</option><option value="anual">Anual</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">L&iacute;mite</label>
                        <input type="text" name="limite" class="form-control form-control-sm" placeholder="Ej: &lt; 85 dB">
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">Cumplimiento</label>
                        <select name="cumplimiento" class="form-select form-select-sm">
                            <option value="pendiente">Pendiente</option><option value="parcial">Parcial</option><option value="cumple">Cumple</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-purple">Registrar</button></div>
        </form>
    </div>
</div>

<!-- Modal Actualizar Cumplimiento -->
<div class="modal fade" id="modalCumplimiento">
    <div class="modal-dialog">
        <form method="POST" action="/sst/normatividad/cumplimiento" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <input type="hidden" name="id" id="cumplimientoId">
            <div class="modal-header"><h5>Actualizar Cumplimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small" id="cumplimientoNorma"></p>
                <div class="mb-2">
                    <label class="form-label small">Estado</label>
                    <select name="cumplimiento" id="cumplimientoEstado" class="form-select form-select-sm">
                        <option value="pendiente">Pendiente</option><option value="parcial">Parcial</option><option value="cumple">Cumple</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Evidencia</label>
                    <textarea name="evidencia" id="cumplimientoEvidencia" class="form-control form-control-sm" rows="2" placeholder="Evidencia del cumplimiento..."></textarea>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-purple">Actualizar</button></div>
        </form>
    </div>
</div>

<script>
function buscarNormas() {
    var val = document.getElementById('busquedaNormas').value.trim();
    location.href = '?seccion=normatividad' + (val ? '&buscar=' + encodeURIComponent(val) : '');
}
function abrirCumplimiento(req) {
    document.getElementById('cumplimientoId').value = req.req_id || '';
    document.getElementById('cumplimientoNorma').innerText = (req.req_norma || '') + ' - ' + (req.req_articulo || '');
    document.getElementById('cumplimientoEstado').value = req.req_cumplimiento || 'pendiente';
    document.getElementById('cumplimientoEvidencia').value = req.req_evidencia || '';
    new bootstrap.Modal(document.getElementById('modalCumplimiento')).show();
}
</script>

<style>
.btn-purple { background:#6f42c1;color:#fff;border:none; }
.btn-purple:hover { background:#5a32a3;color:#fff; }
.btn-outline-purple { color:#6f42c1;border-color:#6f42c1; }
.btn-outline-purple:hover { background:#6f42c1;color:#fff; }
</style>
