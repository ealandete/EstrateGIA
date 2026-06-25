<?php $created = $_GET['created'] ?? null; ?>
<?php if ($created): ?><div class="alert alert-success">NC registrada</div><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5><i class="fas fa-triangle-exclamation me-2" style="color:#dc3545"></i>No Conformidades · <?= htmlspecialchars($empresa['empresa_nombre']) ?></h5>
        <small class="text-muted">Gestión de hallazgos, quejas, incidentes y oportunidades de mejora</small>
    </div>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalNC"><i class="fas fa-plus me-1"></i>Nueva NC</button>
</div>

<!-- Orígenes de NC -->
<div class="row g-3 mb-4">
    <?php 
    $origenes = [
        ['auditoria_interna','Auditoría Interna','search','Las NC detectadas durante auditorías PAMEC programadas','#6f42c1'],
        ['auditoria_externa','Auditoría Externa','user-tie','Hallazgos de entes externos (Invima, Supersalud, certificadoras)','#dc3545'],
        ['queja_cliente','Queja / Reclamo','comments','PQRS de pacientes y familias registradas en el sistema','#ffc107'],
        ['incidente','Incidente Seguridad','shield-halved','Eventos adversos, incidentes y cuasi-fallas reportados','#fd7e14'],
        ['revision_direccion','Revisión Dirección','users-gear','Oportunidades identificadas en revisiones gerenciales','#007bff'],
        ['otro','Otro','ellipsis','Otras fuentes de no conformidad','#888'],
    ];
    $core = EstrateGiaCore::getInstance();
    foreach ($origenes as $o):
        $count = $core->fetchColumn("SELECT COUNT(*) FROM cal_no_conformidades WHERE nc_empresa_id=:eid AND nc_origen=:ori", ['eid'=>$empresaId,'ori'=>$o[0]]);
    ?>
    <div class="col-md-4">
        <div class="card p-3" style="border-left:4px solid <?= $o[4] ?>;border-radius:12px">
            <div class="d-flex justify-content-between mb-1">
                <strong><i class="fas fa-<?= $o[2] ?> me-1" style="color:<?= $o[4] ?>"></i><?= $o[1] ?></strong>
                <span class="badge bg-light text-dark"><?= $count ?></span>
            </div>
            <small class="text-muted"><?= $o[3] ?></small>
            <a href="?estado=abierta" class="btn btn-sm btn-link p-0 mt-1">Ver abiertas →</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filtro por estado -->
<div class="d-flex gap-2 mb-3">
    <a href="/nc" class="badge text-decoration-none <?= !$estado?'bg-primary':'bg-light text-dark' ?> py-2 px-3">Todas</a>
    <a href="?estado=abierta" class="badge text-decoration-none <?= $estado==='abierta'?'bg-danger':'bg-light text-dark' ?> py-2 px-3">Abiertas</a>
    <a href="?estado=analisis" class="badge text-decoration-none <?= $estado==='analisis'?'bg-warning text-dark':'bg-light text-dark' ?> py-2 px-3">En Análisis</a>
    <a href="?estado=plan_accion" class="badge text-decoration-none <?= $estado==='plan_accion'?'bg-info':'bg-light text-dark' ?> py-2 px-3">Plan Acción</a>
    <a href="?estado=cerrada" class="badge text-decoration-none <?= $estado==='cerrada'?'bg-success':'bg-light text-dark' ?> py-2 px-3">Cerradas</a>
</div>

<div class="card-box"><div class="card-box-body p-0">
<?php if (empty($ncs)): ?><div class="text-center py-5 text-muted">Sin No Conformidades</div>
<?php else: ?>
<table class="table-box">
    <thead><tr><th>Código</th><th>Origen</th><th>Tipo</th><th>Descripción</th><th>Proceso</th><th>Gravedad</th><th>Estado</th><th>Fecha</th></tr></thead>
    <tbody>
    <?php foreach ($ncs as $nc): 
        $estadoColor = ['abierta'=>'danger','analisis'=>'warning','plan_accion'=>'info','implementacion'=>'primary','verificacion'=>'secondary','cerrada'=>'success'];
    ?>
    <tr>
        <td><a href="/nc/ver/<?= $nc['nc_id'] ?>"><strong><?= htmlspecialchars($nc['nc_codigo']) ?></strong></a></td>
        <td><small><?= str_replace('_',' ',$nc['nc_origen']) ?></small></td>
        <td><?= str_replace('_',' ',$nc['nc_tipo']) ?></td>
        <td><small><?= htmlspecialchars(substr($nc['nc_descripcion'],0,70)) ?>...</small></td>
        <td><small><?= htmlspecialchars($nc['proceso_nombre']??'-') ?></small></td>
        <td><span class="badge bg-<?= $nc['nc_gravedad']==='mayor'?'danger':'warning' ?>"><?= $nc['nc_gravedad'] ?></span></td>
        <td><span class="badge bg-<?= $estadoColor[$nc['nc_estado']]??'light' ?>"><?= $nc['nc_estado'] ?></span></td>
        <td><small><?= date('d/m/Y', strtotime($nc['nc_fecha_deteccion'])) ?></small></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</div></div>

<!-- MODAL Nueva NC -->
<div class="modal fade" id="modalNC" tabindex="-1"><div class="modal-dialog modal-lg">
<form method="POST" action="/nc/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5 class="modal-title"><i class="fas fa-triangle-exclamation me-2"></i>Registrar No Conformidad</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="alert alert-info small mb-3"><i class="fas fa-info-circle me-1"></i><strong>¿De dónde viene esta NC?</strong> Las NC pueden originarse en auditorías PAMEC, quejas de pacientes, incidentes de seguridad, revisiones gerenciales o hallazgos externos.</div>
        <div class="row g-2 mb-2">
            <div class="col-md-3"><label class="form-label small">Origen *</label><select name="origen" class="form-select form-select-sm" required>
                <option value="auditoria_interna">Auditoría Interna (PAMEC)</option><option value="auditoria_externa">Auditoría Externa</option><option value="queja_cliente">Queja / Reclamo</option><option value="incidente">Incidente Seguridad</option><option value="revision_direccion">Revisión Dirección</option><option value="otro">Otro</option>
            </select></div>
            <div class="col-md-3"><label class="form-label small">Tipo</label><select name="tipo" class="form-select form-select-sm"><option value="no_conformidad">No Conformidad</option><option value="observacion">Observación</option><option value="oportunidad_mejora">Oportunidad Mejora</option></select></div>
            <div class="col-md-3"><label class="form-label small">Gravedad</label><select name="gravedad" class="form-select form-select-sm"><option value="mayor">Mayor</option><option value="menor" selected>Menor</option><option value="observacion">Observación</option></select></div>
            <div class="col-md-3"><label class="form-label small">Proceso</label><select name="proceso_id" class="form-select form-select-sm"><option value="">Seleccionar...</option><?php foreach ($procesos as $pr): ?><option value="<?= $pr['proceso_id'] ?>"><?= htmlspecialchars($pr['proceso_nombre']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="mb-2"><label class="form-label small">Descripción del hallazgo *</label><textarea name="descripcion" class="form-control" rows="3" required placeholder="Describe qué se encontró, la evidencia objetiva, el requisito incumplido y el impacto..."></textarea></div>
        <div class="row g-2">
            <div class="col-6"><label class="form-label small">Requisito / Norma</label><input type="text" name="requisito_iso" class="form-control form-control-sm" placeholder="Ej: ISO 9001:2015 §8.5.1, SUA-AC02"></div>
            <div class="col-6"><label class="form-label small">Fecha detección</label><input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger">Registrar NC</button></div>
</form></div></div>

