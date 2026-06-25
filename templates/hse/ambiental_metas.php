<!-- ============================================================ -->
<!-- METAS AMBIENTALES                                              -->
<!-- ============================================================ -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">Metas ambientales con seguimiento de avance y cumplimiento</div>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalMeta"><i class="fas fa-plus me-1"></i>Nueva Meta</button>
</div>

<div class="card-box">
    <div class="card-box-header"><i class="fas fa-bullseye me-2"></i>Metas Ambientales (<?= count($metasAmbientales ?? []) ?>)</div>
    <div class="card-box-body p-0">
        <?php if (!empty($metasAmbientales)): ?>
        <table class="table-box small mb-0">
            <thead><tr><th>Meta</th><th>Tipo</th><th>Objetivo</th><th>Actual</th><th>Avance</th><th>Estado</th><th class="text-end">Acci&oacute;n</th></tr></thead>
            <tbody>
                <?php foreach ($metasAmbientales as $m):
                    $avance = ((float)($m['meta_valor_objetivo'] ?? 1)) > 0 ? min(100, ((float)($m['meta_valor_actual'] ?? 0) / (float)($m['meta_valor_objetivo'] ?? 1)) * 100) : 0;
                    $tipoLabel = ['reduccion_gei' => 'Reducci&oacute;n GEI', 'eficiencia_agua' => 'Eficiencia Agua', 'eficiencia_energia' => 'Eficiencia Energ&iacute;a', 'residuos' => 'Residuos', 'reciclaje' => 'Reciclaje', 'otro' => 'Otro'];
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($m['meta_nombre'] ?? '') ?></strong><br><small class="text-muted"><?= $m['meta_anio'] ?? '' ?></small></td>
                    <td><?= $tipoLabel[$m['meta_tipo'] ?? 'otro'] ?? $m['meta_tipo'] ?></td>
                    <td><?= number_format((float)($m['meta_valor_objetivo'] ?? 0), 2) ?> <?= htmlspecialchars($m['meta_unidad'] ?? '') ?></td>
                    <td><?= number_format((float)($m['meta_valor_actual'] ?? 0), 2) ?> <?= htmlspecialchars($m['meta_unidad'] ?? '') ?></td>
                    <td>
                        <div class="progress" style="width:100px;height:6px"><div class="progress-bar bg-<?= $avance >= 100 ? 'success' : ($avance >= 50 ? 'warning' : 'danger') ?>" style="width:<?= $avance ?>%"></div></div>
                        <small><?= number_format($avance, 1) ?>%</small>
                    </td>
                    <td><span class="badge bg-<?= ($m['meta_estado'] ?? '') === 'cumplida' ? 'success' : (($m['meta_estado'] ?? '') === 'activa' ? 'info' : 'secondary') ?>"><?= $m['meta_estado'] ?? '' ?></span></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary" onclick="editarMeta(<?= htmlspecialchars(json_encode($m)) ?>)"><i class="fas fa-edit"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-5 text-muted"><i class="fas fa-bullseye" style="font-size:3rem;color:#ddd;display:block;margin-bottom:10px"></i>No hay metas ambientales definidas.<br><small>Defina metas para medir el desempe&ntilde;o ambiental.</small></div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Meta -->
<div class="modal fade" id="modalMeta"><div class="modal-dialog"><form method="POST" action="/ambiental/meta/crear" class="modal-content" id="formMeta">
    <input type="hidden" name="id" id="meta_id">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? '' ?>">
    <div class="modal-header"><h5 id="modalMetaTitle">Nueva Meta Ambiental</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="small fw-bold mb-1">Nombre de la Meta</label>
        <input type="text" name="nombre" id="meta_nombre" class="form-control form-control-sm mb-2" required>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="small fw-bold mb-1">Tipo</label>
                <select name="tipo" id="meta_tipo" class="form-select form-select-sm">
                    <option value="reduccion_gei">Reducci&oacute;n GEI</option>
                    <option value="eficiencia_agua">Eficiencia Agua</option>
                    <option value="eficiencia_energia">Eficiencia Energ&iacute;a</option>
                    <option value="residuos">Residuos</option>
                    <option value="reciclaje">Reciclaje</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="col-6">
                <label class="small fw-bold mb-1">A&ntilde;o</label>
                <input type="number" name="anio" id="meta_anio" class="form-control form-control-sm" value="<?= $anio ?>" required>
            </div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-4">
                <label class="small fw-bold mb-1">Valor Objetivo</label>
                <input type="number" step="0.00001" name="valor_objetivo" id="meta_objetivo" class="form-control form-control-sm" required>
            </div>
            <div class="col-4">
                <label class="small fw-bold mb-1">Valor Actual</label>
                <input type="number" step="0.00001" name="valor_actual" id="meta_actual" class="form-control form-control-sm" value="0">
            </div>
            <div class="col-4">
                <label class="small fw-bold mb-1">Unidad</label>
                <select name="unidad" id="meta_unidad" class="form-select form-select-sm">
                    <option value="tCO2e">tCO2e</option>
                    <option value="m3">m&sup3;</option>
                    <option value="kWh">kWh</option>
                    <option value="kg">kg</option>
                    <option value="%">%</option>
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Guardar</button></div>
</form></div></div>

<script>
function editarMeta(m) {
    document.getElementById('modalMetaTitle').textContent = 'Editar Meta Ambiental';
    document.getElementById('formMeta').action = '/ambiental/meta/editar';
    document.getElementById('meta_id').value = m.meta_id || '';
    document.getElementById('meta_nombre').value = m.meta_nombre || '';
    document.getElementById('meta_tipo').value = m.meta_tipo || 'reduccion_gei';
    document.getElementById('meta_anio').value = m.meta_anio || '<?= $anio ?>';
    document.getElementById('meta_objetivo').value = m.meta_valor_objetivo || 0;
    document.getElementById('meta_actual').value = m.meta_valor_actual || 0;
    document.getElementById('meta_unidad').value = m.meta_unidad || 'tCO2e';
    new bootstrap.Modal(document.getElementById('modalMeta')).show();
}
document.getElementById('modalMeta').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalMetaTitle').textContent = 'Nueva Meta Ambiental';
    document.getElementById('formMeta').action = '/ambiental/meta/crear';
    document.getElementById('meta_id').value = '';
    document.getElementById('formMeta').reset();
});
</script>
