<?php $created = $_GET['created'] ?? null; ?>
<?php if ($created): ?><div class="alert alert-success">Auditoría programada</div><?php endif; ?>

<nav class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="/calidad">Acreditación</a></li><li class="breadcrumb-item active">PAMEC</li></ol></nav>

<div class="d-flex justify-content-between mb-3">
    <div>
        <h5><i class="fas fa-search me-2" style="color:#6f42c1"></i>PAMEC - Programa de Auditoría para el Mejoramiento de la Calidad</h5>
        <small class="text-muted"><?= htmlspecialchars($empresa['empresa_nombre']) ?> · Ciclo <?= date('Y') ?></small>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPamec"><i class="fas fa-plus me-1"></i>Programar Auditoría</button>
</div>

<!-- ¿Qué es PAMEC? -->
<div class="alert alert-info small mb-4">
    <strong><i class="fas fa-info-circle me-1"></i>PAMEC</strong> — Programa de Auditoría para el Mejoramiento de la Calidad. Es el conjunto de auditorías internas que evalúan el cumplimiento de los estándares de acreditación. Cada auditoría se enfoca en un estándar específico (SUA, ISO 7101, Habilitación) y uno o varios procesos.
</div>

<?php if (empty($pamec)): ?>
<div class="card-box"><div class="card-box-body text-center py-4 text-muted">Sin auditorías. Programa la primera usando el botón "Programar Auditoría".</div></div>
<?php else: ?>
<div class="card-box"><div class="card-box-body p-0"><table class="table-box">
    <thead><tr><th>Año</th><th>Tipo</th><th>Estándar a Evaluar</th><th>Proceso / Área</th><th>Auditor</th><th>Programada</th><th>Estado</th><th>Resultado</th></tr></thead>
    <tbody>
    <?php foreach ($pamec as $pa): ?>
    <tr>
        <td><?= $pa['pamec_anio'] ?></td>
        <td><?= str_replace('_',' ',$pa['pamec_tipo']) ?></td>
        <td><strong><?= $pa['pamec_estandar'] ?></strong></td>
        <td><?= htmlspecialchars($pa['proceso_nombre']??'Todos los procesos') ?></td>
        <td><?= htmlspecialchars($pa['pamec_auditor_lider']??'Por definir') ?></td>
        <td><?= date('d/m/Y', strtotime($pa['pamec_fecha_programada'])) ?></td>
        <td><span class="badge bg-<?= $pa['pamec_estado']==='cerrada'?'success':($pa['pamec_estado']==='ejecutada'?'primary':'warning') ?>"><?= $pa['pamec_estado'] ?></span></td>
        <td><?= $pa['pamec_calificacion'] ? $pa['pamec_calificacion'].'%' : '-' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>
<?php endif; ?>

<!-- MODAL -->
<div class="modal fade" id="modalPamec"><div class="modal-dialog"><form method="POST" action="/calidad/pamec/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5>Programar Auditoría PAMEC</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small">Año</label><input type="number" name="anio" class="form-control form-control-sm" value="<?= date('Y') ?>"></div><div class="col-6"><label class="form-label small">Tipo</label><select name="tipo" class="form-select form-select-sm"><option value="interna">Interna</option><option value="externa">Externa</option><option value="acreditacion">Acreditación</option></select></div></div>
        <div class="mb-2"><label class="form-label small">Estándar a evaluar</label><select name="estandar" class="form-select form-select-sm"><option value="SUA">SUA</option><option value="ISO 7101">ISO 7101</option><option value="Habilitacion">Habilitación Res.3100</option></select></div>
        <div class="mb-2"><label class="form-label small">Proceso (opcional)</label><select name="proceso_id" class="form-select form-select-sm"><option value="">Todos los procesos</option><?php foreach ($procesos as $pr): ?><option value="<?= $pr['proceso_id'] ?>"><?= htmlspecialchars($pr['proceso_nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="row g-2"><div class="col-8"><label class="form-label small">Auditor Líder</label><input type="text" name="auditor_lider" class="form-control form-control-sm" placeholder="Nombre"></div><div class="col-4"><label class="form-label small">Fecha</label><input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Programar</button></div>
</form></div></div>
