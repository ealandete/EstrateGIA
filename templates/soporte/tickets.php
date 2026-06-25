<div class="d-flex justify-content-between mb-3">
    <div><h5><i class="fas fa-ticket-alt me-2" style="color:#6f42c1"></i>Gestion de Tickets</h5><small class="text-muted"><?=$total?> tickets encontrados</small></div>
    <a href="/soporte/crear" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Nuevo Ticket</a>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><select name="estado" class="form-select form-select-sm"><option value="">Todos estados</option>
        <?php foreach (['ABIERTO','EN_PROGRESO','RESUELTO','CERRADO','ESCALADO_N2','ESCALADO_N3'] as $e): ?>
        <option value="<?=$e?>" <?=($estado??'')===$e?'selected':''?>><?=ucfirst(strtolower(str_replace('_',' ',$e)))?></option>
        <?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><select name="prioridad" class="form-select form-select-sm"><option value="">Todas prioridades</option>
        <?php foreach (['CRITICA','ALTA','MEDIA','BAJA'] as $p): ?>
        <option value="<?=$p?>" <?=($prioridad??'')===$p?'selected':''?>><?=$p?></option>
        <?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><select name="modulo" class="form-select form-select-sm"><option value="">Todos modulos</option>
        <?php foreach ($modulos as $m): ?>
        <option value="<?=htmlspecialchars($m)?>" <?=($modulo??'')===$m?'selected':''?>><?=htmlspecialchars($m)?></option>
        <?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100">Filtrar</button></div>
    <div class="col-md-1"><?php if ($page > 1): ?><a href="?<?=http_build_query(array_merge($_GET,['page'=>$page-1]))?>" class="btn btn-sm btn-outline-secondary">&laquo;</a><?php endif; ?></div>
    <div class="col-md-1"><?php if ($page < $totalPages): ?><a href="?<?=http_build_query(array_merge($_GET,['page'=>$page+1]))?>" class="btn btn-sm btn-outline-secondary">&raquo;</a><?php endif; ?></div>
    <div class="col-md-1"><span class="small text-muted">Pg. <?=$page?>/<?=$totalPages?></span></div>
</form>

<?php if (empty($tickets)): ?>
<div class="card-box text-center py-5"><h5 class="text-muted">No hay tickets con los filtros seleccionados</h5></div>
<?php else: ?>
<div class="card-box"><div class="card-box-body p-0">
<table class="table-box"><thead><tr><th>ID</th><th>Asunto</th><th>Modulo</th><th>Prioridad</th><th>Estado</th><th>Nivel</th><th>Asignado</th><th>SLA</th><th></th></tr></thead><tbody>
<?php foreach ($tickets as $t):
    $slaStyle = ''; $slaVencido = false;
    if ($t['fecha_limite_sla'] && $t['estado'] === 'ABIERTO') {
        $slaVencido = strtotime($t['fecha_limite_sla']) < time();
        $slaStyle = $slaVencido ? 'color:#dc2626;font-weight:700' : '';
    }
?>
<tr>
    <td><a href="/soporte/ver/<?=$t['id']?>">#<?=$t['id']?></a></td>
    <td style="max-width:200px"><?=htmlspecialchars(mb_substr($t['asunto']??'',0,50))?></td>
    <td><span class="badge bg-secondary"><?=htmlspecialchars($t['modulo_afectado']??'General')?></span></td>
    <td><span class="badge bg-<?=$t['prioridad']==='CRITICA'?'danger':($t['prioridad']==='ALTA'?'warning':($t['prioridad']==='BAJA'?'secondary':'primary'))?>"><?=$t['prioridad']?></span></td>
    <td><span class="badge bg-<?=$t['estado']==='ABIERTO'?'info':($t['estado']==='EN_PROGRESO'?'primary':($t['estado']==='RESUELTO'?'success':'secondary'))?>"><?=$t['estado']?></span></td>
    <td><?=$t['nivel_actual']??'N1'?></td>
    <td style="font-size:.7rem"><?=htmlspecialchars($t['asignado_nombre']??'—')?></td>
    <td style="font-size:.65rem;<?=$slaStyle?>"><?=$t['fecha_limite_sla'] ? date('d/m H:i', strtotime($t['fecha_limite_sla'])) : '—'?></td>
    <td><a href="/soporte/ver/<?=$t['id']?>" class="btn btn-sm btn-outline-primary">Ver</a></td>
</tr>
<?php endforeach; ?></tbody></table></div></div>
<?php endif; ?>
