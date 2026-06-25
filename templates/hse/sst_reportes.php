<div class="d-flex justify-content-between align-items-center mb-2">
    <div><h6 class="mb-0"><i class="fas fa-file-pdf me-2" style="color:#dc3545"></i>Reportes SST <?= $anio ?></h6></div>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalGenerarReporte"><i class="fas fa-plus me-1"></i>Generar Reporte</button>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-folder-open me-2"></i>Reportes Generados</div>
            <div class="card-box-body p-0">
            <?php if (empty($reportes)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-file-pdf" style="font-size:2rem;display:block;margin-bottom:8px;color:#ccc"></i>
                    <p class="mb-0">No hay reportes generados en <?= $anio ?></p>
                </div>
            <?php else: ?>
            <table class="table-box small mb-0">
                <thead><tr>
                    <th>Norma</th><th>Reporte</th><th>Periodo</th><th>Fecha</th><th>Estado</th><th class="text-center">Descargar</th>
                </tr></thead>
                <tbody>
                <?php foreach ($reportes as $rep): ?>
                <tr>
                    <td><?= htmlspecialchars($rep['rep_norma'] ?? '') ?></td>
                    <td><strong><?= htmlspecialchars($rep['rep_nombre'] ?? '') ?></strong></td>
                    <td><?= htmlspecialchars($rep['rep_periodo'] ?? '') ?></td>
                    <td><?= date('d/m/Y', strtotime($rep['rep_fecha'] ?? '')) ?></td>
                    <td><span class="badge bg-<?= ($rep['rep_estado'] ?? '') === 'generado' ? 'success' : 'warning' ?>"><?= htmlspecialchars($rep['rep_estado'] ?? '') ?></span></td>
                    <td class="text-center">
                        <?php if (!empty($rep['rep_archivo'])): ?>
                        <a href="/sst/reporte/descargar?id=<?= $rep['rep_id'] ?>" class="btn btn-sm btn-outline-danger"><i class="fas fa-download"></i> PDF</a>
                        <a href="/sst/reporte/imprimir/<?= $rep['rep_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-1"><i class="fas fa-print"></i> Imprimir</a>
                        <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary" disabled><i class="fas fa-clock"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-file-lines me-2"></i>Informes Disponibles</div>
            <div class="card-box-body">
                <div class="list-group list-group-flush small">
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div><i class="fas fa-file-pdf me-1 text-danger"></i>Informe Condiciones Salud</div>
                        <button class="btn btn-outline-danger btn-sm" onclick="generarReporte('condiciones_salud')"><i class="fas fa-gear me-1"></i>Generar</button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div><i class="fas fa-file-pdf me-1 text-danger"></i>Matriz Peligros y Riesgos</div>
                        <button class="btn btn-outline-danger btn-sm" onclick="generarReporte('matriz_peligros')"><i class="fas fa-gear me-1"></i>Generar</button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div><i class="fas fa-file-pdf me-1 text-danger"></i>Investigaci&oacute;n Accidentes</div>
                        <button class="btn btn-outline-danger btn-sm" onclick="generarReporte('investigacion_accidentes')"><i class="fas fa-gear me-1"></i>Generar</button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div><i class="fas fa-file-pdf me-1 text-danger"></i>Autoevaluaci&oacute;n SG-SST</div>
                        <button class="btn btn-outline-danger btn-sm" onclick="generarReporte('autoevaluacion')"><i class="fas fa-gear me-1"></i>Generar</button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div><i class="fas fa-file-pdf me-1 text-danger"></i>Indicadores de Gesti&oacute;n</div>
                        <button class="btn btn-outline-danger btn-sm" onclick="generarReporte('indicadores')"><i class="fas fa-gear me-1"></i>Generar</button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div><i class="fas fa-file-pdf me-1 text-danger"></i>Plan de Trabajo Anual</div>
                        <button class="btn btn-outline-danger btn-sm" onclick="generarReporte('plan_trabajo')"><i class="fas fa-gear me-1"></i>Generar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Generar Reporte -->
<div class="modal fade" id="modalGenerarReporte">
    <div class="modal-dialog">
        <form method="POST" action="/sst/reporte/generar" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <input type="hidden" name="anio" value="<?= $anio ?>">
            <div class="modal-header"><h5>Generar Reporte</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Norma *</label>
                    <select name="norma" class="form-select form-select-sm" required>
                        <option value="">-- Seleccionar --</option>
                        <option value="Res. 0312/2019">Res. 0312/2019</option>
                        <option value="Res. 1401/2007">Res. 1401/2007</option>
                        <option value="Decreto 1072/2015">Decreto 1072/2015</option>
                        <option value="ISO 45001:2018">ISO 45001:2018</option>
                        <option value="Res. 2346/2007">Res. 2346/2007</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Nombre del Reporte *</label>
                    <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej: Informe de Condiciones de Salud" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Periodo</label>
                    <select name="periodo" class="form-select form-select-sm">
                        <option value="anual">Anual</option><option value="semestral">Semestral</option><option value="trimestral">Trimestral</option><option value="mensual">Mensual</option><option value="evento">Por Evento</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger">Generar</button></div>
        </form>
    </div>
</div>

<script>
function generarReporte(tipo) {
    var nombres = {
        'condiciones_salud': 'Informe de Condiciones de Salud',
        'matriz_peligros': 'Matriz de Peligros y Valoraci&oacute;n de Riesgos',
        'investigacion_accidentes': 'Investigaci&oacute;n de Accidentes',
        'autoevaluacion': 'Autoevaluaci&oacute;n SG-SST',
        'indicadores': 'Indicadores de Gesti&oacute;n SST',
        'plan_trabajo': 'Plan de Trabajo Anual SST'
    };
    var md = new bootstrap.Modal(document.getElementById('modalGenerarReporte'));
    document.querySelector('#modalGenerarReporte [name="nombre"]').value = nombres[tipo] || '';
    document.querySelector('#modalGenerarReporte [name="norma"]').value = 'Res. 0312/2019';
    md.show();
}
</script>
