<div class="d-flex justify-content-between mb-2">
    <div><h5 class="mb-0"><i class="fas fa-capsules me-2" style="color:#dc3545"></i>Farmacovigilancia</h5><small class="text-muted">Resolución 1403/2007 · <?= count($medicamentos) ?> medicamentos · <?= count($eventos) ?> eventos</small></div>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalEventoFar"><i class="fas fa-exclamation-triangle me-1"></i>Reportar Evento</button>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card-box"><div class="card-box-header"><i class="fas fa-list me-2"></i>Eventos Adversos Reportados</div>
        <div class="card-box-body p-0">
        <?php if ($eventos): ?><table class="table-box small mb-0"><thead><tr><th>Fecha</th><th>Medicamento</th><th>Tipo</th><th>Paciente</th><th>Gravedad</th><th>Causalidad</th><th>INVIMA</th><th>Estado</th></tr></thead><tbody>
        <?php foreach ($eventos as $e): ?>
        <tr>
            <td><?= $e['evento_fecha_ocurrencia'] ?></td>
            <td><strong><?= htmlspecialchars($e['med_nombre_generico']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($e['med_laboratorio']) ?> | Lote: <?= htmlspecialchars($e['evento_lote']) ?></small></td>
            <td><span class="badge bg-info"><?= str_replace('_',' ',$e['evento_tipo']) ?></span></td>
            <td><small><?= htmlspecialchars($e['evento_paciente_identificacion']) ?> (<?= $e['evento_paciente_edad'] ?>a, <?= $e['evento_paciente_sexo'] ?>)</small></td>
            <td><span class="badge bg-<?= $e['evento_gravedad']==='grave'||$e['evento_gravedad']==='mortal'?'danger':'warning' ?>"><?= $e['evento_gravedad'] ?></span></td>
            <td><?= $e['evento_causalidad'] ?></td>
            <td><?= $e['evento_reporte_invima'] ? "<span class='badge bg-success'>Sí ({$e['evento_invima_folio']})</span>" : "<form method='POST' action='/farmacovigilancia/evento/reportar-invima' class='d-inline'><input type='hidden' name='evento_id' value='{$e['evento_id']}'><input name='folio' placeholder='Folio INVIMA' class='form-control form-control-sm' style='width:100px'><button class='btn btn-sm btn-outline-danger mt-1'>Reportar</button></form>" ?></td>
            <td><span class="badge bg-<?= $e['evento_estado']==='cerrado'?'success':'warning' ?>"><?= $e['evento_estado'] ?></span></td>
        </tr>
        <?php endforeach; ?></tbody></table>
        <?php else: ?><div class="text-center py-4 text-muted">Sin eventos reportados</div><?php endif; ?>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card-box"><div class="card-box-header"><i class="fas fa-pills me-2"></i>Medicamentos Monitoreados</div>
        <div class="card-box-body p-0">
        <?php foreach ($medicamentos as $m): ?>
        <div class="p-2 border-bottom"><strong><?= htmlspecialchars($m['med_nombre_generico']) ?></strong><br>
            <small class="text-muted"><?= htmlspecialchars($m['med_nombre_comercial']) ?> · <?= htmlspecialchars($m['med_laboratorio']) ?></small>
            <span class="badge bg-<?= $m['med_clasificacion']==='alto_riesgo'||$m['med_clasificacion']==='control_especial'?'danger':'secondary' ?> float-end"><?= str_replace('_',' ',$m['med_clasificacion']) ?></span>
        </div>
        <?php endforeach; ?>
        </div></div>
    </div>
</div>

<div class="modal fade" id="modalEventoFar"><div class="modal-dialog modal-lg"><form method="POST" action="/farmacovigilancia/evento/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId??2 ?>">
    <div class="modal-header"><h5>Reportar Evento Adverso a Medicamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><div class="col-6"><label class="small fw-bold">Medicamento</label><select name="med_id" class="form-select form-select-sm" required><option value="">Seleccione...</option><?php foreach($medicamentos as $m): ?><option value="<?=$m['med_id']?>"><?=htmlspecialchars($m['med_nombre_generico'].' - '.$m['med_laboratorio'])?></option><?php endforeach; ?></select></div>
        <div class="col-3"><label class="small fw-bold">Tipo</label><select name="tipo" class="form-select form-select-sm"><option value="reaccion_adversa">Reacción Adversa</option><option value="error_medicacion">Error Medicación</option><option value="fallo_terapeutico">Fallo Terapéutico</option><option value="interaccion">Interacción</option><option value="intoxicacion">Intoxicación</option></select></div>
        <div class="col-3"><label class="small fw-bold">Gravedad</label><select name="gravedad" class="form-select form-select-sm"><option value="leve">Leve</option><option value="moderada" selected>Moderada</option><option value="grave">Grave</option><option value="mortal">Mortal</option></select></div></div>
        <div class="row g-2 mb-2"><div class="col-3"><label class="small fw-bold">ID Paciente</label><input name="paciente_id" class="form-control form-control-sm"></div><div class="col-2"><label class="small fw-bold">Edad</label><input type="number" name="edad" class="form-control form-control-sm"></div><div class="col-2"><label class="small fw-bold">Sexo</label><select name="sexo" class="form-select form-select-sm"><option value="">—</option><option value="M">M</option><option value="F">F</option></select></div>
        <div class="col-3"><label class="small fw-bold">Fecha Ocurrencia</label><input type="date" name="fecha_ocurrencia" class="form-control form-control-sm" value="<?=date('Y-m-d')?>"></div></div>
        <label class="small fw-bold">Descripción del evento</label><textarea name="descripcion" class="form-control form-control-sm mb-2" rows="3" required></textarea>
        <div class="row g-2 mb-2"><div class="col-4"><label class="small fw-bold">Dosis</label><input name="dosis" class="form-control form-control-sm"></div><div class="col-4"><label class="small fw-bold">Vía</label><select name="via" class="form-select form-select-sm"><option value="">—</option><option>Oral</option><option>Intravenosa</option><option>Intramuscular</option><option>Subcutánea</option><option>Tópica</option></select></div><div class="col-4"><label class="small fw-bold">Lote</label><input name="lote" class="form-control form-control-sm"></div></div>
        <label class="small fw-bold">Causalidad</label><select name="causalidad" class="form-select form-select-sm"><option value="definitiva">Definitiva</option><option value="probable">Probable</option><option value="posible" selected>Posible</option><option value="improbable">Improbable</option><option value="no_relacionada">No Relacionada</option></select>
    </div><div class="modal-footer"><button class="btn btn-danger btn-sm"><i class="fas fa-exclamation-triangle me-1"></i>Reportar</button></div>
</form></div></div>
