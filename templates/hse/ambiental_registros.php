<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">Registro de mediciones ambientales (consumos, residuos, emisiones)</div>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegistro"><i class="fas fa-plus me-1"></i>Nuevo Registro</button>
</div>

<div class="card-box">
    <div class="card-box-header"><i class="fas fa-chart-line me-2"></i>Registros Ambientales (<?= count($registros ?? []) ?>)</div>
    <div class="card-box-body p-0">
        <?php if (!empty($registros)): ?>
        <table class="table-box small mb-0">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Valor</th><th>Unidad</th><th>Observaciones</th></tr></thead>
            <tbody>
                <?php foreach ($registros as $r): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($r['fecha'] ?? '')) ?></td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($r['tipo'] ?? '') ?></span></td>
                    <td><strong><?= number_format($r['valor'] ?? 0, 2) ?></strong></td>
                    <td><?= htmlspecialchars($r['unidad'] ?? '') ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($r['observaciones'] ?? '', 0, 60, '...')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-5 text-muted"><i class="fas fa-chart-line" style="font-size:3rem;color:#ddd;display:block;margin-bottom:10px"></i>No hay registros ambientales.<br><small>Comience registrando mediciones de consumos o residuos.</small></div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalRegistro"><div class="modal-dialog"><form method="POST" action="/ambiental/registro/guardar" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? '' ?>">
    <input type="hidden" name="anio" value="<?= $anio ?>">
    <div class="modal-header"><h5>Nuevo Registro Ambiental</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="small fw-bold mb-1">Tipo de Registro</label>
        <select name="tipo" class="form-select form-select-sm mb-2" required>
            <option value="">Seleccione...</option>
            <option value="consumo_agua">Consumo de Agua</option>
            <option value="consumo_energia">Consumo de Energ&iacute;a</option>
            <option value="residuos_ordinarios">Residuos Ordinarios</option>
            <option value="residuos_peligrosos">Residuos Peligrosos</option>
            <option value="residuos_reciclables">Residuos Reciclables</option>
            <option value="emisiones_co2">Emisiones CO<sub>2</sub></option>
            <option value="vertimientos">Vertimientos</option>
            <option value="ruido_db">Ruido (dB)</option>
            <option value="consumo_combustible">Consumo Combustible</option>
        </select>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="small fw-bold mb-1">Valor</label>
                <input type="number" name="valor" class="form-control form-control-sm" placeholder="Ej: 150.5" step="0.01" required>
            </div>
            <div class="col-6">
                <label class="small fw-bold mb-1">Unidad</label>
                <select name="unidad" class="form-select form-select-sm" required>
                    <option value="">Unidad...</option>
                    <option value="m³">m&sup3; - Metros c&uacute;bicos</option>
                    <option value="kWh">kWh - Kilovatios/hora</option>
                    <option value="kg">kg - Kilogramos</option>
                    <option value="ton">ton - Toneladas</option>
                    <option value="L">L - Litros</option>
                    <option value="gal">gal - Galones</option>
                    <option value="dB">dB - Decibeles</option>
                    <option value="tCO2e">tCO<sub>2</sub>e - Ton CO<sub>2</sub> equiv.</option>
                </select>
            </div>
        </div>
        <label class="small fw-bold mb-1">Fecha</label>
        <input type="date" name="fecha" class="form-control form-control-sm mb-2" value="<?= date('Y-m-d') ?>" required>
        <label class="small fw-bold mb-1">Observaciones</label>
        <textarea name="observaciones" class="form-control form-control-sm" rows="2" placeholder="Detalles adicionales..."></textarea>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success btn-sm">Guardar Registro</button></div>
</form></div></div>
