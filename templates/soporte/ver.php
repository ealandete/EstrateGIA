<?php $created = isset($_GET['created']); ?>
<?php if ($created): ?><div class="alert alert-success py-2"><i class="fas fa-check-circle me-2"></i>Ticket creado exitosamente</div><?php endif; ?>

<div class="d-flex justify-content-between mb-3">
    <div><h5><i class="fas fa-ticket-alt me-2" style="color:#6f42c1"></i>Ticket #<?=$ticket['id']?>: <?=htmlspecialchars(mb_substr($ticket['asunto']??'',0,60))?></h5>
    <small class="text-muted">Estado: <?=$ticket['estado']?> | Prioridad: <?=$ticket['prioridad']?> | Nivel: <?=$ticket['nivel_actual']??'N1'?> | Creado por: <?=htmlspecialchars($ticket['creado_por']??'—')?></small></div>
    <a href="/soporte/tickets" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
</div>

<div class="row">
<div class="col-md-8">
    <div class="card-box mb-3">
        <div class="card-box-header">Detalles del Ticket</div>
        <div class="card-box-body">
            <table class="table table-sm mb-0"><tbody>
                <tr><td style="width:120px;font-weight:600">Reportado por</td><td><?=htmlspecialchars($ticket['creado_por']??'—')?></td></tr>
                <tr><td>Modulo</td><td><?=htmlspecialchars($ticket['modulo_afectado']??'General')?></td></tr>
                <tr><td>SLA limite</td><td><?=$ticket['fecha_limite_sla'] ? date('d/m/Y H:i', strtotime($ticket['fecha_limite_sla'])) : '—'?></td></tr>
                <tr><td>Creado</td><td><?=$ticket['created_at'] ?? $ticket['fecha_creacion']?date('d/m/Y H:i', strtotime($ticket['created_at'] ?? $ticket['fecha_creacion'])):'—'?></td></tr>
                <?php if ($ticket['fecha_resolucion']): ?><tr><td>Resuelto</td><td><?=date('d/m/Y H:i', strtotime($ticket['fecha_resolucion']))?> (<?=$ticket['tiempo_resolucion_min']?> min)</td></tr><?php endif; ?>
                <tr><td>Descripcion</td><td style="white-space:pre-wrap"><?=htmlspecialchars($ticket['descripcion']??'—')?></td></tr>
                <?php if ($ticket['resolucion']): ?>
                <tr><td>Resolucion</td><td style="white-space:pre-wrap;background:#f0fdf4;border-radius:6px;padding:8px"><?=htmlspecialchars($ticket['resolucion'])?></td></tr>
                <?php endif; ?>
            </tbody></table>
        </div>
    </div>

    <div class="card-box mb-3">
        <div class="card-box-header">Historial de Respuestas (<?=count($respuestas)?>)</div>
        <div class="card-box-body" style="max-height:400px;overflow-y:auto">
        <?php if (empty($respuestas)): ?><p class="text-muted">Sin respuestas aun.</p><?php endif; ?>
        <?php foreach ($respuestas as $r):
            $bg = ($r['tipo']??'')==='CERRADO'||($r['tipo']??'')==='CIERRE'?'#f0fdf4':(($r['tipo']??'')==='ESCALACION'?'#fef2f2':(($r['tipo']??'')==='DIAGNOSTICO_IA'?'#eff6ff':''));
        ?>
        <div class="mb-2 p-2" style="border-left:3px solid #2563eb;<?=$bg?'background:'.$bg:''?>;border-radius:4px">
            <strong style="font-size:.8rem"><?=htmlspecialchars($r['autor']??'Sistema')?></strong>
            <span style="font-size:.65rem;color:#64748b"><?=date('d/m H:i', strtotime($r['created_at']))?></span>
            <?php if ($r['tipo']??''): ?><span class="badge bg-<?=($r['tipo']==='DIAGNOSTICO_IA')?'info':(($r['tipo']==='ESCALACION')?'danger':($r['tipo']==='CERRADO'||$r['tipo']==='CIERRE'?'success':'secondary'))?>" style="font-size:.55rem"><?=$r['tipo']?></span><?php endif; ?>
            <div style="font-size:.8rem;white-space:pre-wrap;margin-top:4px"><?=htmlspecialchars($r['contenido']??$r['mensaje']??'')?></div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <?php if (!in_array($ticket['estado'], ['CERRADO','RESUELTO'])): ?>
    <div class="card-box mb-3">
        <div class="card-box-header">Responder</div>
        <div class="card-box-body">
        <form method="POST">
            <input type="hidden" name="_accion" value="responder">
            <textarea name="respuesta" class="form-control mb-2" rows="3" required placeholder="Escriba su respuesta..."></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Enviar Respuesta</button>
        </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="col-md-4">
    <?php if (!in_array($ticket['estado'], ['CERRADO','RESUELTO'])): ?>
    <div class="card-box mb-2">
        <div class="card-box-header">Cerrar Ticket</div>
        <div class="card-box-body">
        <form method="POST">
            <input type="hidden" name="_accion" value="cerrar">
            <textarea name="resolucion" class="form-control mb-2" rows="2" placeholder="Describa la resolucion..." required></textarea>
            <button type="submit" class="btn btn-success btn-sm w-100">Cerrar con Resolucion</button>
        </form>
        </div>
    </div>

    <div class="card-box mb-2">
        <div class="card-box-header">Escalar</div>
        <div class="card-box-body">
        <form method="POST" onsubmit="return confirm('Escalar este ticket?')">
            <input type="hidden" name="_accion" value="escalar">
            <select name="nivel_escalar" class="form-select form-select-sm mb-2">
                <option value="N2">Nivel 2 (Especialista) — SLA +8h</option>
                <option value="N3">Nivel 3 (Desarrollo) — SLA +24h</option>
            </select>
            <button type="submit" class="btn btn-warning btn-sm w-100">Escalar</button>
        </form>
        </div>
    </div>

    <?php if (!$ticket['asignado_a']): ?>
    <div class="card-box mb-2">
        <div class="card-box-header">Asignar Tecnico</div>
        <div class="card-box-body">
        <form method="POST">
            <input type="hidden" name="_accion" value="asignar">
            <select name="asignar_a" class="form-select form-select-sm mb-2">
                <?php foreach ($techs as $t): ?>
                <option value="<?=$t['usuario_id']?>"><?=htmlspecialchars($t['usuario_nombre'].' '.($t['usuario_apellido']??''))?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm w-100">Asignar</button>
        </form>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
</div>
