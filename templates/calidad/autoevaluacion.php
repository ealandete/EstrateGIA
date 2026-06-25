<form method="POST" action="/calidad/autoevaluacion/guardar">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5><i class="fas fa-clipboard-check me-2" style="color:#28a745"></i>Autoevaluación de Estándares · <?= htmlspecialchars($empresa['empresa_nombre']) ?></h5>
        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Guardar Evaluación</button>
    </div>

    <?php 
    $tiposEstandar = ['SUA'=>'Sist. Único de Acreditación (SUA)','ISO7101'=>'ISO 7101:2023','Habilitacion'=>'Habilitación Res. 3100/2019'];
    $cumplOptions = ['cumple'=>'Cumple','cumple_parcial'=>'Cumple Parcialmente','no_cumple'=>'No Cumple','no_aplica'=>'No Aplica'];
    foreach ($tiposEstandar as $tk => $tl):
        $ests = array_filter($estandares, fn($e) => $e['estandar_tipo'] === $tk);
        if (empty($ests)) continue;
    ?>
    <div class="card-box mb-4">
        <div class="card-box-header"><strong><?= $tl ?></strong> (<?= count($ests) ?> estándares)</div>
        <div class="card-box-body p-0">
            <?php foreach ($ests as $est): 
                $actual = $est['ultimo_cumplimiento'] ?? 'cumple_parcial';
            ?>
            <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong><?= htmlspecialchars($est['estandar_codigo']) ?> - <?= htmlspecialchars($est['estandar_nombre']) ?></strong>
                        <div class="text-muted small"><?= htmlspecialchars($est['estandar_grupo']) ?> · Nivel <?= $est['estandar_nivel'] ?></div>
                    </div>
                    <select name="estandar[<?= $est['estandar_id'] ?>]" class="form-select form-select-sm" style="width:160px">
                        <?php foreach ($cumplOptions as $ck => $cv): ?>
                        <option value="<?= $ck ?>" <?= $actual===$ck?'selected':'' ?>><?= $cv ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="evidencia[<?= $est['estandar_id'] ?>]" class="form-control form-control-sm" placeholder="Evidencia / Evidencia / Evidencia..." value="<?= htmlspecialchars($est['evidencia_descripcion']??'') ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="proceso[<?= $est['estandar_id'] ?>]" class="form-select form-select-sm">
                            <option value="">Sin proceso</option>
                            <?php foreach ($procesos as $pr): ?>
                            <option value="<?= $pr['proceso_id'] ?>"><?= htmlspecialchars($pr['proceso_nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="plan_mejora[<?= $est['estandar_id'] ?>]" class="form-control form-control-sm" placeholder="Plan de mejora (si aplica)" value="<?= htmlspecialchars($est['evidencia_plan_mejora']??'') ?>">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="text-end">
        <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-1"></i>Guardar Autoevaluación Completa</button>
    </div>
</form>
