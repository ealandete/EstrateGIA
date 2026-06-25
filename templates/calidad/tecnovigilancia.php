<div class="d-flex justify-content-between mb-2">
    <div><h5 class="mb-0"><i class="fas fa-microchip me-2" style="color:#6f42c1"></i>Tecnovigilancia</h5><small class="text-muted">Resolución 4816/2008 · <?= count($dispositivos) ?> dispositivos · <?= count($eventos) ?> eventos</small></div>
    <button class="btn btn-purple btn-sm" style="background:#6f42c1;color:#fff" data-bs-toggle="modal" data-bs-target="#modalEventoTecno"><i class="fas fa-exclamation-triangle me-1"></i>Reportar Evento</button>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card-box"><div class="card-box-header"><i class="fas fa-list me-2"></i>Eventos Adversos Reportados</div>
        <div class="card-box-body p-0">
        <?php if ($eventos): ?><table class="table-box small mb-0"><thead><tr><th>Fecha</th><th>Dispositivo</th><th>Tipo</th><th>Paciente</th><th>Gravedad</th><th>INVIMA</th><th>Estado</th></tr></thead><tbody>
        <?php foreach ($eventos as $e): ?>
        <tr>
            <td><?= $e['tec_evento_fecha_ocurrencia'] ?></td>
            <td><strong><?= htmlspecialchars($e['disp_nombre']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($e['disp_marca']) ?></small></td>
            <td><span class="badge bg-info"><?= str_replace('_',' ',$e['tec_evento_tipo']) ?></span></td>
            <td><small><?= htmlspecialchars($e['tec_evento_paciente_identificacion'] ?: 'N/A') ?></small></td>
            <td><span class="badge bg-<?= $e['tec_evento_gravedad']==='grave'||$e['tec_evento_gravedad']==='mortal'?'danger':'warning' ?>"><?= $e['tec_evento_gravedad'] ?></span></td>
            <td><?= $e['tec_evento_reporte_invima'] ? "<span class='badge bg-success'>Sí ({$e['tec_evento_invima_folio']})</span>" : "<form method='POST' action='/tecnovigilancia/evento/reportar-invima' class='d-inline'><input type='hidden' name='evento_id' value='{$e['tec_evento_id']}'><input name='folio' placeholder='Folio INVIMA' class='form-control form-control-sm' style='width:100px'><button class='btn btn-sm btn-outline-purple mt-1'>Reportar</button></form>" ?></td>
            <td><span class="badge bg-<?= $e['tec_evento_estado']==='cerrado'?'success':'warning' ?>"><?= $e['tec_evento_estado'] ?></span></td>
        </tr>
        <?php endforeach; ?></tbody></table>
        <?php else: ?><div class="text-center py-4 text-muted">Sin eventos reportados</div><?php endif; ?>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card-box"><div class="card-box-header"><i class="fas fa-laptop-medical me-2"></i>Dispositivos Monitoreados</div>
        <div class="card-box-body p-0">
        <?php foreach ($dispositivos as $d): ?>
        <div class="p-2 border-bottom"><strong><?= htmlspecialchars($d['disp_nombre']) ?></strong><br>
            <small class="text-muted"><?= htmlspecialchars($d['disp_marca']) ?> · <?= htmlspecialchars($d['disp_ubicacion']) ?></small>
            <span class="badge bg-<?= $d['disp_clasificacion_riesgo']==='III'?'danger':($d['disp_clasificacion_riesgo']==='IIb'?'warning':'info') ?> float-end">Clase <?= $d['disp_clasificacion_riesgo'] ?></span>
        </div>
        <?php endforeach; ?>
        </div></div>
    </div>
</div>

<div class="modal fade" id="modalEventoTecno"><div class="modal-dialog modal-lg"><form method="POST" action="/tecnovigilancia/evento/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId??2 ?>">
    <div class="modal-header"><h5>Reportar Evento Adverso a Dispositivo Médico</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><div class="col-6"><label class="small fw-bold">Dispositivo</label><select name="disp_id" class="form-select form-select-sm" required><option value="">Seleccione...</option><?php foreach($dispositivos as $d): ?><option value="<?=$d['disp_id']?>"><?=htmlspecialchars($d['disp_nombre'].' - '.$d['disp_marca'])?></option><?php endforeach; ?></select></div>
        <div class="col-3"><label class="small fw-bold">Tipo</label><select name="tipo" class="form-select form-select-sm"><option value="falla_funcionamiento">Falla Funcionamiento</option><option value="error_uso">Error de Uso</option><option value="alarma_falsa">Alarma Falsa</option><option value="deterioro">Deterioro</option><option value="rotura">Rotura</option></select></div>
        <div class="col-3"><label class="small fw-bold">Gravedad</label><select name="gravedad" class="form-select form-select-sm"><option value="leve">Leve</option><option value="moderada" selected>Moderada</option><option value="grave">Grave</option><option value="mortal">Mortal</option></select></div></div>
        <div class="row g-2 mb-2"><div class="col-5"><label class="small fw-bold">ID Paciente</label><input name="paciente_id" class="form-control form-control-sm"></div><div class="col-7"><label class="small fw-bold">Fecha Ocurrencia</label><input type="date" name="fecha_ocurrencia" class="form-control form-control-sm" value="<?=date('Y-m-d')?>"></div></div>
        <label class="small fw-bold">Descripción del evento</label><textarea name="descripcion" class="form-control form-control-sm mb-2" rows="3" required></textarea>
        <label class="small fw-bold">Acción inmediata tomada</label><textarea name="accion" class="form-control form-control-sm" rows="2"></textarea>
    </div><div class="modal-footer"><button class="btn btn-purple btn-sm" style="background:#6f42c1;color:#fff"><i class="fas fa-exclamation-triangle me-1"></i>Reportar</button></div>
</form></div></div>
