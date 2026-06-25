<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">Auditor&iacute;as internas y externas del sistema de gesti&oacute;n ambiental</div>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAuditoria"><i class="fas fa-plus me-1"></i>Nueva Auditor&iacute;a</button>
</div>

<div class="card-box">
    <div class="card-box-header"><i class="fas fa-search me-2"></i>Auditor&iacute;as Ambientales (<?= count($auditorias ?? []) ?>)</div>
    <div class="card-box-body p-0">
        <?php if (!empty($auditorias)): ?>
        <table class="table-box small mb-0">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Auditor</th><th>Hallazgos</th><th>NC</th><th>Resultado</th><th>Estado</th></tr></thead>
            <tbody>
                <?php foreach ($auditorias as $aud): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($aud['fecha'] ?? '')) ?></td>
                    <td><span class="badge bg-<?= ($aud['tipo'] ?? '') === 'interna' ? 'info' : (($aud['tipo'] ?? '') === 'externa' ? 'warning' : 'primary') ?>"><?= htmlspecialchars($aud['tipo'] ?? '') ?></span></td>
                    <td><?= htmlspecialchars($aud['auditor'] ?? '') ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($aud['hallazgos'] ?? '', 0, 40, '...')) ?></td>
                    <td><span class="badge bg-<?= ($aud['no_conformidades'] ?? 0) > 0 ? 'danger' : 'success' ?>"><?= $aud['no_conformidades'] ?? 0 ?></span></td>
                    <td><?= htmlspecialchars(mb_strimwidth($aud['resultado'] ?? '', 0, 30, '...')) ?></td>
                    <td><span class="badge bg-<?= ($aud['estado'] ?? '') === 'cerrada' ? 'success' : (($aud['estado'] ?? '') === 'en_proceso' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($aud['estado'] ?? '') ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-5 text-muted"><i class="fas fa-search" style="font-size:3rem;color:#ddd;display:block;margin-bottom:10px"></i>No hay auditor&iacute;as registradas.<br><small>Programe auditor&iacute;as internas o registre auditor&iacute;as externas.</small></div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalAuditoria"><div class="modal-dialog"><form method="POST" action="/ambiental/auditoria/guardar" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? '' ?>">
    <input type="hidden" name="anio" value="<?= $anio ?>">
    <div class="modal-header"><h5>Nueva Auditor&iacute;a Ambiental</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="small fw-bold mb-1">Fecha</label>
        <input type="date" name="fecha" class="form-control form-control-sm mb-2" value="<?= date('Y-m-d') ?>" required>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="small fw-bold mb-1">Tipo de Auditor&iacute;a</label>
                <select name="tipo" class="form-select form-select-sm" required>
                    <option value="interna">Interna</option>
                    <option value="externa">Externa</option>
                    <option value="certificacion">Certificaci&oacute;n</option>
                    <option value="seguimiento">Seguimiento</option>
                </select>
            </div>
            <div class="col-6">
                <label class="small fw-bold mb-1">Estado</label>
                <select name="estado" class="form-select form-select-sm" required>
                    <option value="programada">Programada</option>
                    <option value="en_proceso">En Proceso</option>
                    <option value="cerrada">Cerrada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>
        </div>
        <label class="small fw-bold mb-1">Auditor / Ente Certificador</label>
        <input type="text" name="auditor" class="form-control form-control-sm mb-2" placeholder="Nombre del auditor o ente" required>
        <label class="small fw-bold mb-1">Hallazgos</label>
        <textarea name="hallazgos" class="form-control form-control-sm mb-2" rows="2" placeholder="Hallazgos encontrados durante la auditor&iacute;a"></textarea>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="small fw-bold mb-1">No Conformidades</label>
                <input type="number" name="no_conformidades" class="form-control form-control-sm" placeholder="0" min="0" value="0">
            </div>
            <div class="col-6">
                <label class="small fw-bold mb-1">Observaciones</label>
                <input type="number" name="observaciones_count" class="form-control form-control-sm" placeholder="0" min="0" value="0">
            </div>
        </div>
        <label class="small fw-bold mb-1">Resultado / Conclusi&oacute;n</label>
        <textarea name="resultado" class="form-control form-control-sm" rows="2" placeholder="Resultado general de la auditor&iacute;a" required></textarea>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success btn-sm">Guardar Auditor&iacute;a</button></div>
</form></div></div>
