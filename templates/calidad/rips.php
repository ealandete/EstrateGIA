<div class="d-flex justify-content-between mb-2">
    <div><h5 class="mb-0"><i class="fas fa-file-medical me-2" style="color:#0d6efd"></i>RIPS - Registro Individual de Prestaciones de Salud</h5><small class="text-muted">Resolución 3374/2000 · Res 948/2024 · Periodo: <?= htmlspecialchars($periodo) ?></small></div>
    <div><a href="/rips/exportar/<?= $periodo ?>" class="btn btn-success btn-sm me-1"><i class="fas fa-download me-1"></i>Exportar CSV</a>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRIPS"><i class="fas fa-plus me-1"></i>Nuevo Registro</button></div>
</div>

<div class="row g-3 mb-3">
    <?php foreach ($resumen as $r): ?>
    <div class="col-md-2"><div class="stat-card"><div class="stat-label"><?= ucfirst(str_replace('_',' ',$r['rips_tipo'])) ?></div><div class="stat-value"><?= $r['total'] ?></div><small class="text-muted">$<?= number_format($r['valor_total']??0,0) ?></small></div></div>
    <?php endforeach; ?>
</div>

<div class="card-box"><div class="card-box-header d-flex justify-content-between"><div><i class="fas fa-table me-2"></i>Registros <?= $periodo ?> (<?= count($rips) ?>)</div>
    <select class="form-select form-select-sm" style="width:120px" onchange="location.href='?periodo='+this.value"><option value="<?=date('Y-m')?>"><?=date('Y-m')?></option><option value="2026-06">2026-06</option><option value="2026-05">2026-05</option></select>
</div>
<div class="card-box-body p-0">
<?php if ($rips): ?><div class="table-responsive"><table class="table-box small mb-0"><thead><tr><th>Fecha</th><th>ID Paciente</th><th>Nombre</th><th>Tipo</th><th>DX (CIE-10)</th><th>CUPS</th><th>EPS</th><th>Valor</th><th>Exportado</th></tr></thead><tbody>
<?php foreach ($rips as $r): ?>
<tr><td><?= $r['rips_fecha_ingreso'] ?></td><td><?= htmlspecialchars($r['rips_paciente_tipo_id'].' '.$r['rips_paciente_id']) ?></td><td><?= htmlspecialchars($r['rips_paciente_nombre']) ?></td>
<td><span class="badge bg-<?= $r['rips_tipo']==='urgencias'?'danger':($r['rips_tipo']==='hospitalizacion'?'warning':'info') ?>"><?= $r['rips_tipo'] ?></span></td>
<td><code><?= $r['rips_diagnostico_principal'] ?></code></td><td><code><?= $r['rips_codigo_cups'] ?></code></td>
<td><small><?= htmlspecialchars($r['rips_eps']) ?></small></td><td class="text-end">$<?= number_format($r['rips_valor']??0,0) ?></td>
<td><?= $r['rips_exportado']?'<span class="badge bg-success">'.($r['rips_fecha_exportacion']??'Sí').'</span>':'<span class="badge bg-secondary">No</span>' ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php else: ?><div class="text-center py-4 text-muted">Sin registros para este período</div><?php endif; ?>
</div></div>

<div class="modal fade" id="modalRIPS"><div class="modal-dialog modal-lg"><form method="POST" action="/rips/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId??2 ?>">
    <input type="hidden" name="periodo" value="<?= $periodo ?>">
    <div class="modal-header"><h5>Nuevo Registro RIPS</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><div class="col-2"><label class="small fw-bold">Tipo ID</label><select name="tipo_id" class="form-select form-select-sm"><option value="CC">CC</option><option value="TI">TI</option><option value="CE">CE</option><option value="PA">PA</option></select></div><div class="col-3"><label class="small fw-bold">Número ID</label><input name="paciente_id" class="form-control form-control-sm" required></div><div class="col-3"><label class="small fw-bold">Nombre</label><input name="nombre" class="form-control form-control-sm"></div><div class="col-2"><label class="small fw-bold">Edad</label><input type="number" name="edad" class="form-control form-control-sm"></div><div class="col-2"><label class="small fw-bold">Sexo</label><select name="sexo" class="form-select form-select-sm"><option value="">—</option><option>M</option><option>F</option></select></div></div>
        <div class="row g-2 mb-2"><div class="col-3"><label class="small fw-bold">Fecha Ingreso</label><input type="date" name="fecha_ingreso" class="form-control form-control-sm" value="<?=date('Y-m-d')?>"></div><div class="col-3"><label class="small fw-bold">Tipo</label><select name="tipo" class="form-select form-select-sm"><option value="consulta">Consulta</option><option value="procedimiento">Procedimiento</option><option value="urgencias">Urgencias</option><option value="hospitalizacion">Hospitalización</option><option value="medicamento">Medicamento</option></select></div><div class="col-3"><label class="small fw-bold">Régimen</label><select name="regimen" class="form-select form-select-sm"><option value="contributivo">Contributivo</option><option value="subsidiado">Subsidiado</option><option value="especial">Especial</option><option value="particular">Particular</option></select></div><div class="col-3"><label class="small fw-bold">EPS</label><input name="eps" class="form-control form-control-sm" value="Nueva EPS"></div></div>
        <div class="row g-2 mb-2"><div class="col-3"><label class="small fw-bold">DX Principal (CIE-10)</label><input name="diagnostico" class="form-control form-control-sm"></div><div class="col-3"><label class="small fw-bold">CUPS</label><input name="cups" class="form-control form-control-sm"></div><div class="col-3"><label class="small fw-bold">Finalidad</label><select name="finalidad" class="form-select form-select-sm"><option value="diagnostico">Diagnóstico</option><option value="terapeutico">Terapéutico</option></select></div><div class="col-3"><label class="small fw-bold">Valor</label><input type="number" name="valor" class="form-control form-control-sm" value="85000"></div></div>
    </div><div class="modal-footer"><button class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Guardar</button></div>
</form></div></div>
