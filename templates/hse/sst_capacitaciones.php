<div class="d-flex justify-content-between align-items-center mb-2">
    <div><h6 class="mb-0"><i class="fas fa-graduation-cap me-2" style="color:#0d6efd"></i>Capacitaciones SST <?= $anio ?></h6></div>
    <div class="d-flex gap-2">
        <input type="text" id="busquedaCapacitaciones" class="form-control form-control-sm" style="width:250px" placeholder="Buscar por tema..." onkeydown="if(event.key==='Enter')buscarCapacitaciones()">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCapacitacion"><i class="fas fa-plus me-1"></i>Registrar Capacitaci&oacute;n</button>
    </div>
</div>

<div class="card-box">
    <div class="card-box-body p-0">
    <?php if (empty($capacitaciones)): ?>
        <?php if (!empty($_GET['buscar'])): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-search" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
            <p class="mb-2">No se encontraron resultados para '<?= htmlspecialchars($_GET['buscar']) ?>'</p>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-graduation-cap" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
            <p class="mb-2">No hay capacitaciones registradas en <?= $anio ?></p>
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCapacitacion"><i class="fas fa-plus me-1"></i>Registrar primera capacitaci&oacute;n</button>
        </div>
        <?php endif; ?>
    <?php else: ?>
    <table class="table-box small mb-0">
        <thead><tr>
            <th>Tema</th><th>Fecha</th><th>Tipo</th><th>Duraci&oacute;n</th><th>Facilitador</th><th>Participantes</th><th>Evaluaci&oacute;n</th><th class="text-center" style="width:80px">Acciones</th>
        </tr></thead>
        <tbody>
        <?php foreach ($capacitaciones as $cap): ?>
        <tr>
            <td><strong><?= htmlspecialchars($cap['cap_tema'] ?? '') ?></strong></td>
            <td><?= date('d/m/Y', strtotime($cap['cap_fecha'] ?? '')) ?></td>
            <td><span class="badge bg-<?= ($cap['cap_tipo'] ?? '') === 'induccion' ? 'success' : (($cap['cap_tipo'] ?? '') === 'reentrenamiento' ? 'warning' : 'info') ?>"><?= htmlspecialchars(str_replace('_', ' ', $cap['cap_tipo'] ?? '')) ?></span></td>
            <td><?= htmlspecialchars($cap['cap_duracion_horas'] ?? '0') ?>h</td>
            <td><?= htmlspecialchars($cap['cap_facilitador'] ?? '') ?></td>
            <td><?= htmlspecialchars($cap['cap_participantes'] ?? '0') ?></td>
            <td>
                <?php $eval = $cap['cap_evaluacion'] ?? 0; ?>
                <span class="badge bg-<?= $eval >= 80 ? 'success' : ($eval >= 60 ? 'warning' : 'danger') ?>"><?= $eval ?>%</span>
            </td>
            <td class="text-center">
                <form method="POST" action="/sst/capacitacion/eliminar" style="display:inline" onsubmit="return confirm('&iquest;Eliminar esta capacitaci&oacute;n?')">
                    <input type="hidden" name="id" value="<?= $cap['cap_id'] ?>">
                    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                    <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php $page = $_GET['page'] ?? 1; ?>
    <?php if (isset($capacitaciones['pagination']) && $capacitaciones['pagination']['total_pages'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center p-2 small">
        <span class="text-muted"><?= $capacitaciones['pagination']['total'] ?> registros</span>
        <div class="btn-group btn-group-sm">
            <?php for ($i=1; $i<=$capacitaciones['pagination']['total_pages']; $i++): ?>
            <a href="?seccion=capacitaciones&anio=<?= $anio ?>&page=<?= $i ?>" class="btn <?= $i==$page?'btn-primary':'btn-outline-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
    </div>
</div>

<script>
function buscarCapacitaciones() {
    var val = document.getElementById('busquedaCapacitaciones').value.trim();
    location.href = '?seccion=capacitaciones&anio=<?= $anio ?>' + (val ? '&buscar=' + encodeURIComponent(val) : '');
}
</script>
    <?php endif; ?>
    <?php endif; ?>
    </div>
</div>

<!-- Modal Registrar Capacitación -->
<div class="modal fade" id="modalCapacitacion">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="/sst/capacitacion/guardar" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <div class="modal-header"><h5>Registrar Capacitaci&oacute;n</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-8">
                        <label class="form-label small">Tema *</label>
                        <input type="text" name="tema" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Fecha *</label>
                        <input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small">Duraci&oacute;n (horas)</label>
                        <input type="number" name="duracion_horas" class="form-control form-control-sm" min="0" step="0.5" value="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Participantes</label>
                        <input type="number" name="participantes" class="form-control form-control-sm" min="0" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Evaluaci&oacute;n (%)</label>
                        <input type="number" name="evaluacion" class="form-control form-control-sm" min="0" max="100" value="0">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Facilitador</label>
                        <input type="text" name="facilitador" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Tipo</label>
                        <select name="tipo" class="form-select form-select-sm">
                            <option value="induccion">Inducci&oacute;n</option>
                            <option value="entrenamiento">Entrenamiento</option>
                            <option value="reentrenamiento">Reentrenamiento</option>
                            <option value="charla">Charla de Seguridad</option>
                            <option value="taller">Taller</option>
                            <option value="simulacro">Simulacro</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Registrar</button></div>
        </form>
    </div>
</div>

<script>
function buscarCapacitaciones() {
    var val = document.getElementById('busquedaCapacitaciones').value.trim();
    location.href = '?seccion=capacitaciones&anio=<?= $anio ?>' + (val ? '&buscar=' + encodeURIComponent(val) : '');
}
</script>
