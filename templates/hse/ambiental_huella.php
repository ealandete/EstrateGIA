<!-- ============================================================ -->
<!-- HUELLA DE CARBONO - ISO 14064                                -->
<!-- ============================================================ -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="stat-card border-start border-3 border-danger">
            <div class="stat-label">Alcance 1 - Emisiones Directas</div>
            <div class="stat-value"><?= number_format($huellaCarbono['alcance1'] ?? 0, 2) ?> tCO<sub>2</sub>e</div>
            <small class="text-muted">Combustibles, flota, fugas refrigerantes</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card border-start border-3 border-warning">
            <div class="stat-label">Alcance 2 - Emisiones Indirectas</div>
            <div class="stat-value"><?= number_format($huellaCarbono['alcance2'] ?? 0, 2) ?> tCO<sub>2</sub>e</div>
            <small class="text-muted">Consumo energ&iacute;a el&eacute;ctrica</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card border-start border-3 border-info">
            <div class="stat-label">Alcance 3 - Cadena de Valor</div>
            <div class="stat-value"><?= number_format($huellaCarbono['alcance3'] ?? 0, 2) ?> tCO<sub>2</sub>e</div>
            <small class="text-muted">Viajes, residuos, compras</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card border-start border-3 border-<?= ($huellaCarbono['total'] ?? 0) > ($huellaCarbono['meta'] ?? 999999) ? 'danger' : 'success' ?>">
            <div class="stat-label">Huella Total <?= $anio ?></div>
            <div class="stat-value"><?= number_format($huellaCarbono['total'] ?? 0, 2) ?> tCO<sub>2</sub>e</div>
            <small class="<?= ($huellaCarbono['variacion'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                <?= ($huellaCarbono['variacion'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($huellaCarbono['variacion'] ?? 0, 1) ?>% vs <?= $anio - 1 ?>
            </small>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Cumplimiento Meta Anual</div>
            <div class="stat-value"><?= number_format($huellaCarbono['cumplimientoMeta'] ?? 0, 1) ?>%</div>
            <div class="progress mt-1" style="height:8px">
                <div class="progress-bar bg-<?= ($huellaCarbono['cumplimientoMeta'] ?? 0) > 100 ? 'danger' : 'success' ?>" style="width:<?= min(100, $huellaCarbono['cumplimientoMeta'] ?? 0) ?>%"></div>
            </div>
            <small class="text-muted">Meta: <?= number_format($huellaCarbono['meta'] ?? 0, 2) ?> tCO<sub>2</sub>e</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Carbono Evitado</div>
            <div class="stat-value"><?= number_format($indicadoresCarbono['carbonoEvitado'] ?? 0, 2) ?> tCO<sub>2</sub>e</div>
            <small class="text-muted">Compensaciones y reducciones</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Eficiencia Energ&eacute;tica</div>
            <div class="stat-value"><?= number_format($indicadoresCarbono['eficiencia'] ?? 0, 1) ?>%</div>
            <small class="text-muted"><?= number_format($indicadoresCarbono['energiaRenovable'] ?? 0, 2) ?> MWh renovable</small>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">Registro de emisiones GEI &middot; ISO 14064</div>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalEmision"><i class="fas fa-plus me-1"></i>Nueva Emisi&oacute;n</button>
</div>

<div class="card-box">
    <div class="card-box-header"><i class="fas fa-cloud me-2"></i>Emisiones Registradas (<?= count($emisionesGEI ?? []) ?>)</div>
    <div class="card-box-body p-0">
        <?php if (!empty($emisionesGEI)): ?>
        <table class="table-box small mb-0">
            <thead><tr><th>Alcance</th><th>Fuente</th><th>Cantidad</th><th>Factor</th><th>tCO<sub>2</sub>e</th><th>Per&iacute;odo</th><th class="text-end">Acci&oacute;n</th></tr></thead>
            <tbody>
                <?php foreach ($emisionesGEI as $e):
                    $emiTotal = ((float)($e['gei_cantidad'] ?? 0)) * ((float)($e['gei_factor_emision'] ?? 1));
                    $alcanceLabel = ['alcance_1' => 'Directo', 'alcance_2' => 'Indirecto', 'alcance_3' => 'Cadena Valor'];
                ?>
                <tr>
                    <td><span class="badge bg-<?= $e['gei_alcance'] === 'alcance_1' ? 'danger' : ($e['gei_alcance'] === 'alcance_2' ? 'warning' : 'info') ?>"><?= $alcanceLabel[$e['gei_alcance']] ?? $e['gei_alcance'] ?></span></td>
                    <td><?= htmlspecialchars($e['gei_fuente'] ?? '') ?></td>
                    <td><?= number_format((float)($e['gei_cantidad'] ?? 0), 2) ?> <?= htmlspecialchars($e['gei_unidad'] ?? '') ?></td>
                    <td><?= number_format((float)($e['gei_factor_emision'] ?? 0), 4) ?></td>
                    <td><strong><?= number_format($emiTotal, 2) ?></strong></td>
                    <td><?= htmlspecialchars($e['gei_periodo'] ?? '') ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary" onclick="editarEmision(<?= htmlspecialchars(json_encode($e)) ?>)"><i class="fas fa-edit"></i></button>
                        <form method="POST" action="/ambiental/emision/eliminar" class="d-inline" onsubmit="return confirm('Eliminar esta emisi\u00f3n?')"><input type="hidden" name="id" value="<?= $e['gei_id'] ?? '' ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-5 text-muted"><i class="fas fa-cloud" style="font-size:3rem;color:#ddd;display:block;margin-bottom:10px"></i>No hay emisiones registradas.<br><small>Registre emisiones para calcular la huella de carbono.</small></div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mt-3">
    <div class="col-md-6">
        <div class="card-box"><div class="card-box-header"><i class="fas fa-chart-pie me-2"></i>Distribuci&oacute;n por Alcance</div><div class="card-box-body"><canvas id="chartAlcance" style="max-height:250px"></canvas></div></div>
    </div>
    <div class="col-md-6">
        <div class="card-box"><div class="card-box-header"><i class="fas fa-chart-bar me-2"></i>Emisiones por Tipo de Fuente</div><div class="card-box-body"><canvas id="chartFuentes" style="max-height:250px"></canvas></div></div>
    </div>
</div>

<!-- Modal Emision GEI -->
<div class="modal fade" id="modalEmision"><div class="modal-dialog"><form method="POST" action="/ambiental/emision/crear" class="modal-content" id="formEmision">
    <input type="hidden" name="id" id="gei_id">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? '' ?>">
    <div class="modal-header"><h5 id="modalEmisionTitle">Nueva Emisi&oacute;n GEI</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="small fw-bold mb-1">Alcance</label>
                <select name="alcance" id="gei_alcance" class="form-select form-select-sm" required>
                    <option value="alcance_1">Alcance 1 - Directo</option>
                    <option value="alcance_2">Alcance 2 - Indirecto (Energ&iacute;a)</option>
                    <option value="alcance_3">Alcance 3 - Cadena de Valor</option>
                </select>
            </div>
            <div class="col-6">
                <label class="small fw-bold mb-1">Tipo de Fuente</label>
                <select name="tipo_fuente" id="gei_tipo_fuente" class="form-select form-select-sm" required>
                    <option value="combustible">Combustible</option>
                    <option value="electricidad">Electricidad</option>
                    <option value="refrigerante">Refrigerante</option>
                    <option value="viajes">Viajes corporativos</option>
                    <option value="residuos">Residuos</option>
                    <option value="compras">Adquisiciones</option>
                    <option value="transporte">Transporte</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
        </div>
        <label class="small fw-bold mb-1">Fuente Espec&iacute;fica</label>
        <input type="text" name="fuente" id="gei_fuente" class="form-control form-control-sm mb-2" placeholder="Ej: Flota ambulancias, Planta el&eacute;ctrica" required>
        <label class="small fw-bold mb-1">Descripci&oacute;n</label>
        <textarea name="descripcion" id="gei_descripcion" class="form-control form-control-sm mb-2" rows="2" placeholder="Detalle de la fuente de emisi&oacute;n"></textarea>
        <div class="row g-2 mb-2">
            <div class="col-4">
                <label class="small fw-bold mb-1">Cantidad</label>
                <input type="number" step="0.00001" name="cantidad" id="gei_cantidad" class="form-control form-control-sm" required>
            </div>
            <div class="col-4">
                <label class="small fw-bold mb-1">Unidad</label>
                <select name="unidad" id="gei_unidad" class="form-select form-select-sm">
                    <option value="tCO2e">tCO2e</option>
                    <option value="kgCO2e">kgCO2e</option>
                    <option value="gal">Galones</option>
                    <option value="kWh">kWh</option>
                    <option value="km">Kil&oacute;metros</option>
                    <option value="kg">Kilogramos</option>
                </select>
            </div>
            <div class="col-4">
                <label class="small fw-bold mb-1">Factor Emisi&oacute;n</label>
                <input type="number" step="0.00001" name="factor_emision" id="gei_factor_emision" class="form-control form-control-sm" value="1.0" required>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-6">
                <label class="small fw-bold mb-1">Per&iacute;odo</label>
                <input type="number" name="periodo" id="gei_periodo" class="form-control form-control-sm" value="<?= $anio ?>" required>
            </div>
            <div class="col-6">
                <label class="small fw-bold mb-1">Coordenadas (opcional)</label>
                <input type="text" name="coordenadas" id="gei_coordenadas" class="form-control form-control-sm" placeholder="Lat, Lng">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Guardar</button>
    </div>
</form></div></div>

<script>
function editarEmision(e) {
    document.getElementById('modalEmisionTitle').textContent = 'Editar Emisi\u00f3n GEI';
    document.getElementById('formEmision').action = '/ambiental/emision/editar';
    document.getElementById('gei_id').value = e.gei_id || '';
    document.getElementById('gei_alcance').value = e.gei_alcance || 'alcance_1';
    document.getElementById('gei_tipo_fuente').value = e.gei_tipo_fuente || 'combustible';
    document.getElementById('gei_fuente').value = e.gei_fuente || '';
    document.getElementById('gei_descripcion').value = e.gei_descripcion || '';
    document.getElementById('gei_cantidad').value = e.gei_cantidad || 0;
    document.getElementById('gei_unidad').value = e.gei_unidad || 'tCO2e';
    document.getElementById('gei_factor_emision').value = e.gei_factor_emision || 1;
    document.getElementById('gei_periodo').value = e.gei_periodo || '<?= $anio ?>';
    document.getElementById('gei_coordenadas').value = e.gei_coordenadas_generacion || '';
    new bootstrap.Modal(document.getElementById('modalEmision')).show();
}
document.getElementById('modalEmision').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalEmisionTitle').textContent = 'Nueva Emisi\u00f3n GEI';
    document.getElementById('formEmision').action = '/ambiental/emision/crear';
    document.getElementById('gei_id').value = '';
    document.getElementById('formEmision').reset();
});
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('chartAlcance').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Alcance 1', 'Alcance 2', 'Alcance 3'],
            datasets: [{
                data: [<?= $huellaCarbono['alcance1'] ?? 0 ?>, <?= $huellaCarbono['alcance2'] ?? 0 ?>, <?= $huellaCarbono['alcance3'] ?? 0 ?>],
                backgroundColor: ['#dc3545', '#ffc107', '#17a2b8']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
    <?php
    $tipos = []; foreach ($emisionesGEI ?? [] as $e) { $t = $e['gei_tipo_fuente'] ?? 'otro'; $tipos[$t] = ($tipos[$t] ?? 0) + ((float)($e['gei_cantidad'] ?? 0) * (float)($e['gei_factor_emision'] ?? 1)); }
    ?>
    new Chart(document.getElementById('chartFuentes').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($tipos)) ?>,
            datasets: [{
                label: 'tCO2e',
                data: <?= json_encode(array_values($tipos)) ?>,
                backgroundColor: 'rgba(40,167,69,0.7)',
                borderColor: '#28a745',
                borderWidth: 1
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
});
</script>
