<!-- HABILITACION Res 3100/2019 -->
<div class="d-flex justify-content-between mb-2">
    <div><h5 class="mb-0"><i class="fas fa-hospital me-2" style="color:#2563eb"></i>Habilitación de Servicios de Salud</h5><small class="text-muted">Resolución 3100 de 2019 &middot; <?= count($servicios ?? []) ?> servicios</small></div>
</div>

<?php foreach ($servicios as $s): 
    $pct = $s['total_estandares'] > 0 ? round(($s['estandares_cumplen'] / $s['total_estandares']) * 100, 0) : 0;
    $estandares = $this->safeAll("SELECT * FROM cal_habilitacion_estandares WHERE hab_id=?", [(int)$s['hab_id']]);
?>
<div class="card-box mb-3">
    <div class="card-box-header d-flex justify-content-between align-items-center">
        <div><strong><?= htmlspecialchars($s['hab_servicio']) ?></strong> 
            <span class="badge bg-<?= $s['hab_estado']==='habilitado'?'success':($s['hab_estado']==='pendiente_renovacion'?'warning':'danger') ?> ms-2"><?= $s['hab_estado'] ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="progress" style="width:150px;height:8px"><div class="progress-bar bg-<?= $pct>=80?'success':($pct>=50?'warning':'danger') ?>" style="width:<?= $pct ?>%"></div></div>
            <small><?= $pct ?>% (<?= $s['estandares_cumplen'] ?>/<?= $s['total_estandares'] ?>)</small>
        </div>
    </div>
    <div class="card-box-body p-0">
        <table class="table-box small mb-0">
            <thead><tr><th>Estándar</th><th>Descripción</th><th>Cumple</th><th>Evidencia</th><th>Fecha</th></tr></thead>
            <tbody>
                <?php foreach ($estandares as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['he_estandar']) ?></td>
                    <td><small><?= htmlspecialchars($e['he_descripcion']) ?></small></td>
                    <td>
                        <form method="POST" action="/habilitacion/estandar/evaluar" class="d-flex gap-1 align-items-center">
                            <input type="hidden" name="he_id" value="<?= $e['he_id'] ?>">
                            <select name="cumple" class="form-select form-select-sm" onchange="this.form.submit()" style="width:80px">
                                <option value="si" <?= $e['he_cumple']==='si'?'selected':'' ?>>Sí</option>
                                <option value="parcial" <?= $e['he_cumple']==='parcial'?'selected':'' ?>>Parcial</option>
                                <option value="no" <?= $e['he_cumple']==='no'?'selected':'' ?>>No</option>
                            </select>
                        </form>
                    </td>
                    <td><small class="text-muted"><?= htmlspecialchars($e['he_evidencia'] ?? '') ?></small></td>
                    <td><small><?= $e['he_fecha_verificacion'] ?? '—' ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-box-footer small text-muted">
        Resolución: <?= htmlspecialchars($s['hab_resolucion'] ?? '—') ?> | 
        Otorgada: <?= $s['hab_fecha_otorgamiento'] ?> | 
        Vence: <?= $s['hab_fecha_vencimiento'] ?> |
        Capacidad: <?= $s['hab_capacidad_instalada'] ?> | 
        Sede: <?= htmlspecialchars($s['hab_sede'] ?? 'Principal') ?>
    </div>
</div>
<?php endforeach; ?>
