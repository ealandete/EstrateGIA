<div class="d-flex justify-content-between align-items-center mb-2">
    <div><h6 class="mb-0"><i class="fas fa-calendar-xmark me-2" style="color:#dc3545"></i>Ausentismo Laboral <?= $anio ?></h6></div>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalAusentismo"><i class="fas fa-plus me-1"></i>Registrar Ausentismo</button>
</div>

<div class="card-box">
    <div class="card-box-body p-0">
    <?php if (empty($ausentismo['data'])): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-calendar-xmark" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
            <p class="mb-2">No hay registros de ausentismo en <?= $anio ?></p>
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalAusentismo"><i class="fas fa-plus me-1"></i>Registrar primer ausentismo</button>
        </div>
    <?php else: ?>
    <table class="table-box small mb-0">
        <thead><tr>
            <th>Colaborador</th><th>Tipo</th><th>Inicio</th><th>Fin</th><th>D&iacute;as</th><th>Diagn&oacute;stico</th><th class="text-center" style="width:80px">Acciones</th>
        </tr></thead>
        <tbody>
        <?php foreach ($ausentismo['data'] as $aus): ?>
        <tr>
            <td><?= htmlspecialchars($aus['usuario_nombre'] ?? $aus['aus_usuario_id'] ?? '') ?></td>
            <td><span class="badge bg-<?= ($aus['aus_tipo'] ?? '') === 'enfermedad_laboral' ? 'danger' : (($aus['aus_tipo'] ?? '') === 'accidente_trabajo' ? 'warning' : 'info') ?>"><?= htmlspecialchars(str_replace('_', ' ', $aus['aus_tipo'] ?? '')) ?></span></td>
            <td><?= date('d/m/Y', strtotime($aus['aus_fecha_inicio'] ?? '')) ?></td>
            <td><?= date('d/m/Y', strtotime($aus['aus_fecha_fin'] ?? '')) ?></td>
            <td><strong><?= htmlspecialchars($aus['aus_dias'] ?? '0') ?></strong></td>
            <td><?= htmlspecialchars(mb_substr($aus['aus_diagnostico'] ?? '', 0, 40)) ?></td>
            <td class="text-center">
                <form method="POST" action="/sst/ausentismo/eliminar" style="display:inline" onsubmit="return confirm('&iquest;Eliminar este registro?')">
                    <input type="hidden" name="id" value="<?= $aus['aus_id'] ?>">
                    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                    <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php $page = $_GET['page'] ?? 1; ?>
    <?php if (isset($ausentismo['pagination']) && $ausentismo['pagination']['total_pages'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center p-2 small">
        <span class="text-muted"><?= $ausentismo['pagination']['total'] ?> registros</span>
        <div class="btn-group btn-group-sm">
            <?php for ($i=1; $i<=$ausentismo['pagination']['total_pages']; $i++): ?>
            <a href="?seccion=ausentismo&anio=<?= $anio ?>&page=<?= $i ?>" class="btn <?= $i==$page?'btn-primary':'btn-outline-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    </div>
</div>

<!-- Modal Registrar Ausentismo -->
<div class="modal fade" id="modalAusentismo">
    <div class="modal-dialog">
        <form method="POST" action="/sst/ausentismo/guardar" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <div class="modal-header"><h5>Registrar Ausentismo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Colaborador *</label>
                    <select name="usuario_id" class="form-select form-select-sm" required>
                        <option value="">-- Seleccionar --</option>
                        <?php if (!empty($usuarios)): foreach ($usuarios as $u): ?>
                        <option value="<?= $u['usuario_id'] ?>"><?= htmlspecialchars($u['usuario_nombre'] ?? '') ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Tipo *</label>
                    <select name="tipo" class="form-select form-select-sm" required>
                        <option value="enfermedad_general">Enfermedad General</option>
                        <option value="enfermedad_laboral">Enfermedad Laboral</option>
                        <option value="accidente_trabajo">Accidente de Trabajo</option>
                        <option value="accidente_trayecto">Accidente de Trayecto</option>
                        <option value="licencia_maternidad">Licencia Maternidad</option>
                        <option value="licencia_paternidad">Licencia Paternidad</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Fecha Inicio *</label>
                        <input type="date" name="fecha_inicio" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Fecha Fin *</label>
                        <input type="date" name="fecha_fin" class="form-control form-control-sm" required>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">D&iacute;as *</label>
                        <input type="number" name="dias" class="form-control form-control-sm" min="1" required>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Diagn&oacute;stico</label>
                    <textarea name="diagnostico" class="form-control form-control-sm" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger">Registrar</button></div>
        </form>
    </div>
</div>
