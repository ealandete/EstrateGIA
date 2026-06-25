<?php $ok = $_GET['ok'] ?? null; $error = $_GET['error'] ?? null; $importados = $_GET['importados'] ?? 0; ?>

<?php if ($ok): ?>
<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Medición registrada correctamente<?= $importados ? " ($importados registros importados)" : '' ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Columna: Registrar una medición -->
    <div class="col-md-5">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-plus-circle me-2"></i>Registrar Nueva Medición</div>
            <div class="card-box-body">
                <form method="POST" action="/mediciones/registrar">
                    <input type="hidden" name="plan_id" value="<?= $planId ?>">
                    <div class="mb-3">
                        <label class="form-label">Indicador</label>
                        <select name="indicador_id" class="form-select" required>
                            <option value="">Seleccionar indicador...</option>
                            <?php foreach ($indicadores as $ind): ?>
                            <option value="<?= $ind['indicador_id'] ?>">
                                <?= htmlspecialchars($ind['indicador_nombre']) ?> (<?= $ind['categoria_tipo'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Valor</label>
                            <input type="number" name="valor" class="form-control" step="0.01" required placeholder="Ej: 85.5">
                            <small class="text-muted">Unidad según indicador</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Período</label>
                        <input type="text" name="periodo" class="form-control" value="<?= date('Y-m') ?>" placeholder="YYYY-MM">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas sobre esta medición..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Registrar Medición</button>
                </form>
            </div>
        </div>

        <!-- Excel -->
        <div class="card-box mt-3">
            <div class="card-box-header"><i class="fas fa-file-excel me-2"></i>Importar / Exportar Excel</div>
            <div class="card-box-body">
                <div class="d-flex gap-2 mb-3">
                    <a href="/mediciones/descargar-plantilla?plan_id=<?= $planId ?>" class="btn btn-outline-success btn-sm flex-fill">
                        <i class="fas fa-download me-1"></i>Descargar Plantilla CSV
                    </a>
                </div>
                <form method="POST" action="/mediciones/subir-csv" enctype="multipart/form-data">
                    <input type="hidden" name="plan_id" value="<?= $planId ?>">
                    <label class="form-label small">Subir archivo CSV con mediciones</label>
                    <div class="input-group input-group-sm">
                        <input type="file" name="archivo" class="form-control" accept=".csv" required>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i>Subir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Columna: Últimas mediciones -->
    <div class="col-md-7">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-history me-2"></i>Últimas Mediciones Registradas</div>
            <div class="card-box-body">
                <?php if (empty($ultimasMediciones)): ?>
                    <div class="text-center text-muted py-4">Sin mediciones. Registra la primera o importa desde Excel.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table-box">
                        <thead>
                            <tr><th>Indicador</th><th>Valor</th><th>Fecha</th><th>Cumplim.</th><th>Semáforo</th><th>Origen</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasMediciones as $indId => $meds): ?>
                            <?php foreach (array_slice($meds, 0, 2) as $m): 
                                $ind = array_values(array_filter($indicadores, fn($i) => $i['indicador_id'] == $indId))[0] ?? null;
                            ?>
                            <tr>
                                <td><small><?= htmlspecialchars($ind['indicador_nombre'] ?? "ID $indId") ?></small></td>
                                <td><strong><?= number_format($m['medicion_valor'], 2) ?></strong> <?= $ind['indicador_unidad_medida'] ?? '' ?></td>
                                <td><?= $m['medicion_fecha'] ?></td>
                                <td><?= number_format($m['medicion_cumplimiento_porcentaje'] ?? 0, 1) ?>%</td>
                                <td>
                                    <span class="semaforo-dot d-inline-block" style="background:<?= $m['medicion_semaforo']==='verde'?'#28a745':($m['medicion_semaforo']==='amarillo'?'#ffc107':'#dc3545') ?>;width:14px;height:14px;border-radius:50%;vertical-align:middle"></span>
                                    <?= $m['medicion_semaforo'] ?? '-' ?>
                                </td>
                                <td><small class="text-muted"><?= $m['medicion_origen'] ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
