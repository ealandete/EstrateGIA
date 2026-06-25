<?php $created = $_GET['created'] ?? null; ?>
<?php if ($created): ?><div class="alert alert-success">Encuesta registrada</div><?php endif; ?>

<div class="d-flex justify-content-between mb-3"><h5><i class="fas fa-face-smile me-2"></i>Satisfacción de Clientes · <?= htmlspecialchars($empresa['empresa_nombre']) ?></h5><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSat"><i class="fas fa-plus me-1"></i>Registrar</button></div>

<!-- Gráfico NPS -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card-box"><div class="card-box-header">NPS Actual</div>
        <div class="card-box-body text-center">
            <div class="fs-1 fw-bold" style="color:<?= ($satisfaccion[0]['sat_nps']??0)>=70?'#28a745':($satisfaccion[0]['sat_nps']??0>=50?'#ffc107':'#dc3545') ?>"><?= $satisfaccion[0]['sat_nps']??0 ?></div>
            <small class="text-muted">Net Promoter Score · Último período</small>
        </div></div>
    </div>
    <div class="col-md-8">
        <div class="card-box"><div class="card-box-header">Tendencia NPS</div>
        <div class="card-box-body"><canvas id="npsChart" height="80"></canvas></div></div>
    </div>
</div>

<div class="card-box"><div class="card-box-body p-0"><table class="table-box">
    <thead><tr><th>Período</th><th>Proceso</th><th>NPS</th><th>Encuestas</th><th>Promotores</th><th>Neutros</th><th>Detractores</th></tr></thead>
    <tbody>
    <?php foreach ($satisfaccion as $s): ?>
    <tr><td><?= $s['sat_periodo'] ?></td><td><?= htmlspecialchars($s['proceso_nombre']??'General') ?></td><td><strong style="color:<?= $s['sat_nps']>=70?'#28a745':($s['sat_nps']>=50?'#ffc107':'#dc3545') ?>"><?= $s['sat_nps'] ?></strong></td><td><?= $s['sat_total_encuestas'] ?></td><td class="text-success"><?= $s['sat_promotores'] ?></td><td class="text-muted"><?= $s['sat_neutros'] ?></td><td class="text-danger"><?= $s['sat_detractores'] ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>

<div class="modal fade" id="modalSat"><div class="modal-dialog"><form method="POST" action="/satisfaccion/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5>Registrar Encuesta NPS</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small">Período</label><input type="text" name="periodo" class="form-control form-control-sm" value="<?= date('Y-m') ?>" placeholder="YYYY-MM"></div><div class="col-6"><label class="form-label small">Proceso</label><select name="proceso_id" class="form-select form-select-sm"><option value="">General</option><?php foreach ($procesos as $pr): ?><option value="<?= $pr['proceso_id'] ?>"><?= htmlspecialchars($pr['proceso_nombre']) ?></option><?php endforeach; ?></select></div></div>
        <div class="row g-2 mb-2"><div class="col-4"><label class="form-label small">Total Encuestas</label><input type="number" name="total" class="form-control form-control-sm" required></div><div class="col-4"><label class="form-label small">Promotores (9-10)</label><input type="number" name="promotores" class="form-control form-control-sm" required></div><div class="col-4"><label class="form-label small">Detractores (0-6)</label><input type="number" name="detractores" class="form-control form-control-sm" required></div></div>
        <label class="form-label small">Neutros (7-8)</label><input type="number" name="neutros" class="form-control form-control-sm" required>
        <small class="text-muted">NPS = ((Promotores - Detractores) / Total) × 100. Se calcula automáticamente.</small>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Registrar</button></div>
</form></div></div>

<script>
<?php $labels = []; $values = []; foreach (array_reverse(array_slice($satisfaccion, 0, 6)) as $s) { $labels[] = $s['sat_periodo']; $values[] = (float)($s['sat_nps']??0); } ?>
new Chart(document.getElementById('npsChart'),{type:'line',data:{labels:<?= json_encode($labels) ?>,datasets:[{data:<?= json_encode($values) ?>,borderColor:'#28a745',tension:0.3,fill:false}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{min:0,max:100}}}});
</script>
<?php $moduloContexto = "extras"; require BASE_PATH . "/templates/hse/ia_panel.php"; ?>
