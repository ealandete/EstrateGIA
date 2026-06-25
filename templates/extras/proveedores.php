<?php $created = $_GET['created'] ?? null; $evalOk = $_GET['eval'] ?? null; $verId = (int)($_GET['ver'] ?? 0); ?>
<?php if ($created): ?><div class="alert alert-success">Proveedor registrado</div><?php endif; ?>
<?php if ($evalOk): ?><div class="alert alert-success">Evaluación registrada</div><?php endif; ?>

<?php if ($verId): 
    $prov = EstrateGiaCore::getInstance()->fetchOne("SELECT * FROM cal_proveedores WHERE prov_id=:id",['id'=>$verId]);
    $evals = EstrateGiaCore::getInstance()->fetchAll("SELECT * FROM cal_proveedor_evaluaciones WHERE eval_proveedor_id=:id ORDER BY eval_fecha DESC",['id'=>$verId]);
?>
<!-- Vista detalle con evaluaciones -->
<nav class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="/proveedores">Proveedores</a></li><li class="breadcrumb-item active"><?= htmlspecialchars($prov['prov_nombre']) ?></li></ol></nav>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card-box"><div class="card-box-header"><i class="fas fa-building me-2"></i><?= htmlspecialchars($prov['prov_nombre']) ?></div>
        <div class="card-box-body">
            <p><strong>Código:</strong> <?= htmlspecialchars($prov['prov_codigo']) ?></p>
            <p><strong>Tipo:</strong> <?= $prov['prov_tipo'] ?></p>
            <p><strong>Calificación:</strong> <span class="badge bg-<?= $prov['prov_calificacion']>=90?'success':($prov['prov_calificacion']>=70?'warning':'danger') ?>"><?= $prov['prov_calificacion'] ?>%</span></p>
            <p><strong>Estado:</strong> <?= $prov['prov_estado'] ?></p>
            <hr>
            <h6>Evaluación por Criterios</h6>
            <?php foreach (['calidad'=>'Calidad','entrega'=>'Entrega','precio'=>'Precio','servicio'=>'Servicio'] as $ck=>$cl): $v = $prov["prov_criterio_$ck"] ?? 0; ?>
            <div class="mb-2"><small><?= $cl ?></small><div class="progress" style="height:6px"><div class="progress-bar bg-<?= $v>=90?'success':($v>=70?'warning':'danger') ?>" style="width:<?= $v ?>%"></div></div><small class="float-end"><?= $v ?>%</small></div>
            <?php endforeach; ?>
        </div></div>

        <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#modalEval"><i class="fas fa-star me-1"></i>Nueva Evaluación</button>
    </div>
    <div class="col-md-8">
        <div class="card-box"><div class="card-box-header"><i class="fas fa-history me-2"></i>Historial de Evaluaciones</div>
        <div class="card-box-body p-0"><table class="table-box small">
            <thead><tr><th>Fecha</th><th>Calidad</th><th>Entrega</th><th>Precio</th><th>Servicio</th><th>Total</th><th>Observaciones</th></tr></thead>
            <tbody>
            <?php foreach ($evals as $ev): ?>
            <tr><td><?= date('d/m/Y',strtotime($ev['eval_fecha'])) ?></td><td><?= $ev['eval_calidad'] ?>%</td><td><?= $ev['eval_entrega'] ?>%</td><td><?= $ev['eval_precio'] ?>%</td><td><?= $ev['eval_servicio'] ?>%</td><td><strong><?= $ev['eval_total'] ?>%</strong></td><td><small><?= htmlspecialchars(substr($ev['eval_observaciones']??'',0,60)) ?></small></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div></div>
    </div>
</div>

<!-- Modal Evaluación -->
<div class="modal fade" id="modalEval"><div class="modal-dialog"><form method="POST" action="/proveedores/evaluar" class="modal-content">
    <input type="hidden" name="proveedor_id" value="<?= $prov['prov_id'] ?>">
    <div class="modal-header"><h5>Nueva Evaluación - <?= htmlspecialchars($prov['prov_nombre']) ?></h5></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><?php foreach(['calidad'=>'Calidad','entrega'=>'Oportunidad Entrega','precio'=>'Competitividad Precio','servicio'=>'Servicio Post-venta'] as $k=>$l): ?><div class="col-6"><label class="form-label small"><?= $l ?> (%)</label><input type="number" name="<?= $k ?>" class="form-control form-control-sm" min="0" max="100" value="80"></div><?php endforeach; ?></div>
        <label class="form-label small">Observaciones</label><textarea name="observaciones" class="form-control form-control-sm" rows="2"></textarea>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Registrar Evaluación</button></div>
</form></div></div>

<?php else: ?>
<!-- Lista de proveedores -->
<div class="d-flex justify-content-between mb-3"><h5><i class="fas fa-truck me-2"></i>Proveedores · <?= htmlspecialchars($empresa['empresa_nombre']) ?></h5><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalProv"><i class="fas fa-plus me-1"></i>Nuevo</button></div>

<div class="card-box"><div class="card-box-body p-0"><table class="table-box">
    <thead><tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Calidad</th><th>Entrega</th><th>Precio</th><th>Servicio</th><th>Total</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($proveedores as $p): ?>
    <tr>
        <td><?= htmlspecialchars($p['prov_codigo']) ?></td>
        <td><strong><?= htmlspecialchars($p['prov_nombre']) ?></strong></td>
        <td><?= $p['prov_tipo'] ?></td>
        <td><?= $p['prov_criterio_calidad'] ?: '-' ?></td>
        <td><?= $p['prov_criterio_entrega'] ?: '-' ?></td>
        <td><?= $p['prov_criterio_precio'] ?: '-' ?></td>
        <td><?= $p['prov_criterio_servicio'] ?: '-' ?></td>
        <td><span class="badge bg-<?= ($p['prov_calificacion']??0)>=90?'success':(($p['prov_calificacion']??0)>=70?'warning':'danger') ?>"><?= $p['prov_calificacion'] ?: '-' ?></span></td>
        <td><?= $p['prov_estado'] ?></td>
        <td><a href="/proveedores/ver/<?= $p['prov_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>

<div class="modal fade" id="modalProv"><div class="modal-dialog"><form method="POST" action="/proveedores/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5>Nuevo Proveedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="Nombre *" required>
        <select name="tipo" class="form-select form-select-sm mb-2"><option value="insumos">Insumos</option><option value="medicamentos">Medicamentos</option><option value="equipos">Equipos</option><option value="servicios">Servicios</option><option value="consultoria">Consultoría</option></select>
        <input type="text" name="contacto" class="form-control form-control-sm mb-2" placeholder="Contacto"><input type="email" name="email" class="form-control form-control-sm mb-2" placeholder="Email"><input type="text" name="telefono" class="form-control form-control-sm" placeholder="Teléfono">
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
</form></div></div>
<?php endif; ?>
<?php $moduloContexto = "extras"; require BASE_PATH . "/templates/hse/ia_panel.php"; ?>
