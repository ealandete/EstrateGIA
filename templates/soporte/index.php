<div class="d-flex justify-content-between mb-3">
    <div><h5><i class="fas fa-headset me-2" style="color:#6f42c1"></i>Soporte — Mesa de Ayuda (N1)</h5>
    <small class="text-muted"><?=$abiertos?> abiertos | <?=$enProgreso?> en progreso | <?=$resueltosHoy?> resueltos hoy</small></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6"><div class="card-box text-center py-3"><small class="text-muted">SLA Cumplido</small><h3 class="mb-0" style="color:<?=$slaCumplido>=90?'#059669':'#dc2626'?>"><?=$slaCumplido?>%</h3></div></div>
    <div class="col-md-3 col-6"><div class="card-box text-center py-3"><small class="text-muted">Tiempo Prom. Resolucion</small><h3 class="mb-0"><?=$avgHoras?>h</h3></div></div>
    <div class="col-md-3 col-6"><div class="card-box text-center py-3"><small class="text-muted">Abiertos</small><h3 class="mb-0" style="color:#2563eb"><?=$abiertos?></h3></div></div>
    <div class="col-md-3 col-6"><div class="card-box text-center py-3"><small class="text-muted">Resueltos Hoy</small><h3 class="mb-0" style="color:#059669"><?=$resueltosHoy?></h3></div></div>
</div>

<div class="card-box mb-3">
    <div class="card-box-header">Tickets por Prioridad</div>
    <div class="card-box-body">
        <div class="row">
        <?php $colors = ['CRITICA'=>'#dc2626','ALTA'=>'#f59e0b','MEDIA'=>'#2563eb','BAJA'=>'#64748b']; $maxPri = max(array_column($ticketsPrioridad?:[['total'=>1]], 'total'));
        if (empty($ticketsPrioridad)): ?>
            <div class="col-12 text-muted">Sin tickets activos</div>
        <?php else: foreach ($ticketsPrioridad as $tp): $pct = round(($tp['total']/$maxPri)*100); ?>
        <div class="col-md-3 col-6 mb-2">
            <small style="font-weight:600;color:<?=$colors[$tp['prioridad']]??'#64748b'?>"><?=$tp['prioridad']?></small>
            <div style="background:#e2e8f0;border-radius:6px;height:8px;margin:4px 0"><div style="width:<?=$pct?>%;height:100%;background:<?=$colors[$tp['prioridad']]??'#2563eb'?>;border-radius:6px"></div></div>
            <strong><?=$tp['total']?></strong>
        </div>
        <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<div class="mb-3">
    <a href="/soporte/tickets" class="btn btn-primary btn-sm">Gestionar Tickets</a>
    <a href="/soporte/crear" class="btn btn-outline-primary btn-sm">Crear Ticket</a>
    <a href="/soporte/kb" class="btn btn-outline-secondary btn-sm">Base de Conocimiento</a>
    <a href="/soporte/reporte/sla" class="btn btn-outline-secondary btn-sm">Reporte SLA</a>
</div>

<div class="card-box">
    <div class="card-box-header">Tickets Recientes</div>
    <div class="card-box-body p-0">
    <?php if (empty($recientes)): ?><p class="p-3 text-muted">Sin tickets registrados</p>
    <?php else: ?>
    <table class="table-box"><thead><tr><th>ID</th><th>Asunto</th><th>Prioridad</th><th>Estado</th><th>Asignado</th><th>Actualizado</th></tr></thead><tbody>
    <?php foreach ($recientes as $t): ?>
    <tr>
        <td><a href="/soporte/ver/<?=$t['id']?>">#<?=$t['id']?></a></td>
        <td><?=htmlspecialchars(mb_substr($t['asunto']??'',0,60))?></td>
        <td><span class="badge bg-<?=$t['prioridad']==='CRITICA'?'danger':($t['prioridad']==='ALTA'?'warning':($t['prioridad']==='BAJA'?'secondary':'primary'))?>"><?=$t['prioridad']?></span></td>
        <td><span class="badge bg-<?=$t['estado']==='ABIERTO'?'info':($t['estado']==='EN_PROGRESO'?'primary':($t['estado']==='RESUELTO'?'success':'secondary'))?>"><?=$t['estado']?></span></td>
        <td><?=htmlspecialchars($t['asignado_nombre']??'Sin asignar')?></td>
        <td style="font-size:.7rem"><?=$t['updated_at']??$t['created_at']?date('d/m H:i',strtotime($t['updated_at']??$t['created_at'])):'—'?></td>
    </tr>
    <?php endforeach; ?></tbody></table>
    <?php endif; ?>
    </div>
</div>
