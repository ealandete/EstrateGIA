<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted small">Informes y reportes ambientales disponibles</div>
    <div class="d-flex gap-1">
        <?= \ExportManager::renderExportButtons('tablaReportes', 'reportes_ambiental_' . ($anio ?? date('Y'))) ?>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalReporte"><i class="fas fa-plus me-1"></i>Generar Reporte</button>
    </div>
</div>
<?= \ExportManager::renderExportJS() ?>

<?php if (!empty($reportes)): ?>
<div class="card-box mb-3">
    <div class="card-box-header"><i class="fas fa-file-pdf me-2"></i>Reportes Generados (<?= count($reportes) ?>)</div>
    <div class="card-box-body p-0">
        <table class="table-box small mb-0" id="tablaReportes">
            <thead><tr><th>Reporte</th><th>Norma</th><th>Periodo</th><th>Fecha</th><th class="text-center">Descargar</th></tr></thead>
            <tbody>
                <?php foreach ($reportes as $rep): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($rep['reporte'] ?? '') ?></strong></td>
                    <td><?= htmlspecialchars($rep['norma'] ?? '') ?></td>
                    <td><?= htmlspecialchars($rep['periodo'] ?? '') ?></td>
                    <td><?= date('d/m/Y', strtotime($rep['fecha'] ?? '')) ?></td>
                    <td class="text-center"><a href="#" class="btn btn-sm btn-outline-primary"><i class="fas fa-download me-1"></i>PDF</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card-box mb-3">
    <div class="card-box-body text-center py-4 text-muted">
        <i class="fas fa-file-pdf" style="font-size:2rem;color:#ddd;display:block;margin-bottom:8px"></i>
        No hay reportes generados para <?= $anio ?>.
    </div>
</div>
<?php endif; ?>

<div class="card-box">
    <div class="card-box-header"><i class="fas fa-file-lines me-2"></i>Informes Disponibles</div>
    <div class="card-box-body">
        <div class="row g-2">
            <?php
            $informesDisponibles = [
                ['Res. 0631/2015','Informe de Vertimientos','Anual','vertimiento'],
                ['Res. 2254/2017','Informe de Calidad del Aire','Anual','aire'],
                ['Decreto 1076/2015','Evaluaci&oacute;n de Impacto Ambiental','Anual','eia'],
                ['ISO 14001:2015','Informe de Desempe&ntilde;o Ambiental','Anual','desempeno'],
                ['Res. 1407/2018','Informe de Residuos RESPEL','Anual','respel'],
                ['Res. 2184/2019','Informe de Separaci&oacute;n en la Fuente','Anual','separacion'],
                ['Decreto 1090/2018','Informe de Huella H&iacute;drica','Anual','hidrica'],
                ['Ley 1715/2014','Informe de Eficiencia Energ&eacute;tica','Anual','energia'],
            ];
            foreach ($informesDisponibles as $inf): ?>
            <div class="col-md-3">
                <div class="p-2 border rounded text-center small">
                    <strong><?= $inf[1] ?></strong>
                    <div class="text-muted"><?= $inf[0] ?> &middot; <?= $inf[2] ?></div>
                    <button class="btn btn-sm btn-outline-success mt-1" data-bs-toggle="modal" data-bs-target="#modalReporte"><i class="fas fa-file-pdf me-1"></i>Generar</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReporte"><div class="modal-dialog"><form method="POST" action="/ambiental/reporte/generar" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? '' ?>">
    <input type="hidden" name="anio" value="<?= $anio ?>">
    <div class="modal-header"><h5>Generar Reporte Ambiental</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="small fw-bold mb-1">Tipo de Reporte</label>
        <select name="tipo_reporte" class="form-select form-select-sm mb-2" required>
            <option value="">Seleccione...</option>
            <option value="vertimiento">Informe de Vertimientos (Res. 0631/2015)</option>
            <option value="aire">Informe de Calidad del Aire (Res. 2254/2017)</option>
            <option value="eia">Evaluaci&oacute;n de Impacto Ambiental</option>
            <option value="desempeno">Informe de Desempe&ntilde;o Ambiental (ISO 14001)</option>
            <option value="respel">Informe de Residuos Peligrosos (RESPEL)</option>
            <option value="separacion">Informe de Separaci&oacute;n en la Fuente</option>
            <option value="hidrica">Informe de Huella H&iacute;drica</option>
            <option value="energia">Informe de Eficiencia Energ&eacute;tica</option>
        </select>
        <label class="small fw-bold mb-1">Periodo</label>
        <select name="periodo" class="form-select form-select-sm mb-2" required>
            <option value="">Seleccione...</option>
            <option value="<?= $anio ?>-Q1"><?= $anio ?> - Primer Trimestre</option>
            <option value="<?= $anio ?>-Q2"><?= $anio ?> - Segundo Trimestre</option>
            <option value="<?= $anio ?>-Q3"><?= $anio ?> - Tercer Trimestre</option>
            <option value="<?= $anio ?>-Q4"><?= $anio ?> - Cuarto Trimestre</option>
            <option value="<?= $anio ?>-S1"><?= $anio ?> - Primer Semestre</option>
            <option value="<?= $anio ?>-S2"><?= $anio ?> - Segundo Semestre</option>
            <option value="<?= $anio ?>-A"><?= $anio ?> - Anual</option>
        </select>
        <label class="small fw-bold mb-1">Norma de Referencia</label>
        <input type="text" name="norma" class="form-control form-control-sm mb-2" placeholder="Norma o resoluci&oacute;n de referencia">
        <label class="small fw-bold mb-1">Observaciones</label>
        <textarea name="observaciones" class="form-control form-control-sm" rows="2" placeholder="Notas adicionales para el reporte"></textarea>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success btn-sm"><i class="fas fa-file-pdf me-1"></i>Generar Reporte</button></div>
</form></div></div>
