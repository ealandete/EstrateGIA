<div class="d-flex justify-content-between mb-3">
    <div><h5><i class="fas fa-chart-bar me-2" style="color:#dc2626"></i>Reporte SLA — <?=date('F Y', strtotime($inicio))?></h5></div>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><input type="month" name="mes" class="form-control form-control-sm" value="<?=$mes?>"></div>
    <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100">Ver</button></div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card-box text-center py-3"><small class="text-muted">Total Tickets</small><h3><?=$totalTickets?></h3></div></div>
    <div class="col-md-4"><div class="card-box text-center py-3"><small class="text-muted">Cerrados</small><h3><?=$cerrados?> (<?=round(($cerrados/$totalTickets)*100)?>%)</h3></div></div>
    <div class="col-md-4"><div class="card-box text-center py-3"><small class="text-muted">SLA Cumplido</small><h3 style="color:<?=$slaPct>=90?'#059669':'#dc2626'?>"><?=$slaPct?>%</h3></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6"><div class="card-box text-center py-3"><small class="text-muted">Tiempo Prom. Resolucion</small><h3><?=$promRes?>h</h3></div></div>
    <div class="col-md-6"><div class="card-box text-center py-3"><small class="text-muted">Tickets Dentro SLA</small><h3><?=$dentroSLA?>/<?=$cerrados?></h3></div></div>
</div>

<?php if (!empty($porPrioridad)): ?>
<div class="card-box"><div class="card-box-header">Desglose por Prioridad</div>
<table class="table-box"><thead><tr><th>Prioridad</th><th>Total</th><th>Dentro SLA</th><th>% Cumplimiento</th></tr></thead><tbody>
<?php $colorsPri = ['CRITICA'=>'#dc2626','ALTA'=>'#f59e0b','MEDIA'=>'#2563eb','BAJA'=>'#64748b'];
foreach ($porPrioridad as $p): $pct = $p['total'] > 0 ? round(($p['dentro_sla']/$p['total'])*100,1) : 0; ?>
<tr>
    <td><span style="color:<?=$colorsPri[$p['prioridad']]??'#64748b'?>;font-weight:600"><?=$p['prioridad']?></span></td>
    <td><?=$p['total']?></td>
    <td><?=$p['dentro_sla']?></td>
    <td>
        <div style="display:flex;align-items:center;gap:8px">
            <div style="flex:1;background:#e2e8f0;border-radius:6px;height:6px"><div style="width:<?=$pct?>%;height:100%;background:<?=$pct>=90?'#059669':'#dc2626'?>;border-radius:6px"></div></div>
            <strong style="font-size:.75rem"><?=$pct?>%</strong>
        </div>
    </td>
</tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?>
