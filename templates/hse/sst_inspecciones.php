<div class="d-flex justify-content-between align-items-center mb-2">
    <div><h6 class="mb-0"><i class="fas fa-magnifying-glass me-2" style="color:#0dcaf0"></i>Inspecciones de Seguridad</h6></div>
    <button class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalInspeccion"><i class="fas fa-plus me-1"></i>Registrar Inspecci&oacute;n</button>
</div>

<div class="card-box">
    <div class="card-box-body p-0">
    <?php if (empty($inspecciones)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-magnifying-glass" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
            <p class="mb-2">No hay inspecciones registradas</p>
            <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalInspeccion"><i class="fas fa-plus me-1"></i>Registrar primera inspecci&oacute;n</button>
        </div>
    <?php else: ?>
    <table class="table-box small mb-0">
        <thead><tr>
            <th>Fecha</th><th>Tipo</th><th>&Aacute;rea</th><th>Hallazgos</th><th>Estado</th><th>Observaciones</th><th class="text-center" style="width:80px">Acciones</th>
        </tr></thead>
        <tbody>
        <?php foreach ($inspecciones as $insp): ?>
        <tr>
            <td><?= date('d/m/Y', strtotime($insp['insp_fecha'] ?? '')) ?></td>
            <td><span class="badge bg-<?= ($insp['insp_tipo'] ?? '') === 'planeada' ? 'primary' : (($insp['insp_tipo'] ?? '') === 'especifica' ? 'warning' : 'info') ?>"><?= htmlspecialchars(str_replace('_', ' ', $insp['insp_tipo'] ?? '')) ?></span></td>
            <td><?= htmlspecialchars($insp['insp_area'] ?? '') ?></td>
            <td><?= htmlspecialchars(mb_substr($insp['insp_hallazgos'] ?? '', 0, 50)) ?></td>
            <td>
                <span class="badge bg-<?= ($insp['insp_estado'] ?? '') === 'cerrada' ? 'success' : (($insp['insp_estado'] ?? '') === 'en_proceso' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars(str_replace('_', ' ', $insp['insp_estado'] ?? '')) ?></span>
            </td>
            <td><?= htmlspecialchars(mb_substr($insp['insp_observaciones'] ?? '', 0, 40)) ?></td>
            <td class="text-center">
                <form method="POST" action="/sst/inspeccion/eliminar" style="display:inline" onsubmit="return confirm('&iquest;Eliminar esta inspecci&oacute;n?')">
                    <input type="hidden" name="id" value="<?= $insp['insp_id'] ?>">
                    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                    <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php $page = $_GET['page'] ?? 1; ?>
    <?php if (isset($inspecciones['pagination']) && $inspecciones['pagination']['total_pages'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center p-2 small">
        <span class="text-muted"><?= $inspecciones['pagination']['total'] ?> registros</span>
        <div class="btn-group btn-group-sm">
            <?php for ($i=1; $i<=$inspecciones['pagination']['total_pages']; $i++): ?>
            <a href="?seccion=inspecciones&page=<?= $i ?>" class="btn <?= $i==$page?'btn-info':'btn-outline-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    </div>
</div>

<!-- Modal Registrar Inspección -->
<div class="modal fade" id="modalInspeccion">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="/sst/inspeccion/guardar" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <div class="modal-header"><h5>Registrar Inspecci&oacute;n</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small">Tipo *</label>
                        <select name="tipo" class="form-select form-select-sm" required>
                            <option value="planeada">Planeada</option>
                            <option value="no_planeada">No Planeada</option>
                            <option value="especifica">Espec&iacute;fica</option>
                            <option value="general">General</option>
                            <option value="verificacion">Verificaci&oacute;n</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">&Aacute;rea *</label>
                        <input type="text" name="area" class="form-control form-control-sm" placeholder="Ej: Planta de producci&oacute;n" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Fecha *</label>
                        <input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Hallazgos</label>
                    <textarea name="hallazgos" class="form-control form-control-sm" rows="3" placeholder="Hallazgos encontrados durante la inspecci&oacute;n..."></textarea>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Estado</label>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="pendiente">Pendiente</option><option value="en_proceso">En Proceso</option><option value="cerrada">Cerrada</option>
                        </select>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Observaciones</label>
                    <textarea name="observaciones" class="form-control form-control-sm" rows="2" placeholder="Observaciones adicionales..."></textarea>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-info text-white">Registrar</button></div>
        </form>
    </div>
</div>
