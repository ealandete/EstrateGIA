<?php
$programaCreado = $_GET['programa_creado'] ?? $_GET['created'] ?? null;
$equipoAgregado = $_GET['equipo_agregado'] ?? null;
$autoevalGuardada = $_GET['autoeval_guardada'] ?? null;
$auditoriaCreada = $_GET['auditoria_creada'] ?? null;
$rondaCreada = $_GET['ronda_creada'] ?? null;
$checklistCreado = $_GET['checklist_creado'] ?? null;
?>
<?php if ($programaCreado): ?><div class="alert alert-success">Programa PAMEC creado correctamente</div><?php endif; ?>
<?php if ($equipoAgregado): ?><div class="alert alert-success">Miembro del equipo agregado</div><?php endif; ?>
<?php if ($autoevalGuardada): ?><div class="alert alert-success">Autoevaluación guardada</div><?php endif; ?>
<?php if ($auditoriaCreada): ?><div class="alert alert-info">Auditoría registrada</div><?php endif; ?>
<?php if ($rondaCreada): ?><div class="alert alert-info">Ronda de calidad registrada</div><?php endif; ?>
<?php if ($checklistCreado): ?><div class="alert alert-info">Ítem de checklist creado</div><?php endif; ?>

<nav class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="/calidad">Acreditación</a></li><li class="breadcrumb-item"><a href="/acreditacion">Acreditación Dashboard</a></li><li class="breadcrumb-item active">PAMEC</li></ol></nav>

<div class="d-flex justify-content-between mb-3">
    <div>
        <h5><i class="fas fa-search me-2" style="color:#6f42c1"></i>PAMEC - Programa de Auditoría para el Mejoramiento de la Calidad</h5>
        <small class="text-muted"><?= htmlspecialchars($empresa['empresa_nombre']) ?> · Ministerio de Salud y Protección Social</small>
    </div>
    <div>
        <a href="/calidad/checklist?empresa_id=<?= $empresaId ?>" class="btn btn-outline-secondary btn-sm me-1"><i class="fas fa-list-check me-1"></i>Checklist</a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalProgramaPamec"><i class="fas fa-plus me-1"></i>Nuevo Programa</button>
    </div>
</div>

<!-- Definición PAMEC Ministerio de Salud -->
<div class="alert alert-info small mb-4">
    <strong><i class="fas fa-info-circle me-1"></i>PAMEC</strong> — De acuerdo con el Ministerio de Salud y Protección Social (Decreto 1011 de 2006, Resolución 0123 de 2012), el Programa de Auditoría para el Mejoramiento de la Calidad es el mecanismo sistemático y continuo de evaluación del cumplimiento de estándares de calidad en salud. Comprende: <strong>(1)</strong> Autoevaluación de estándares, <strong>(2)</strong> Auditorías internas/externas, <strong>(3)</strong> Rondas de calidad, <strong>(4)</strong> Planes de mejoramiento y <strong>(5)</strong> Seguimiento a indicadores.
</div>

<ul class="nav nav-tabs mb-4" id="pamecTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabProgramas"><i class="fas fa-calendar-alt me-1"></i>Programas</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabAutoevaluacion"><i class="fas fa-clipboard-check me-1"></i>Autoevaluación</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabAuditorias"><i class="fas fa-magnifying-glass me-1"></i>Auditorías</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabRondas"><i class="fas fa-sync-alt me-1"></i>Rondas Calidad</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabEquipo"><i class="fas fa-users me-1"></i>Equipo</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabChecklist"><i class="fas fa-list-check me-1"></i>Checklist</a></li>
</ul>

<div class="tab-content">
<!-- TAB 1: PROGRAMAS PAMEC -->
<div class="tab-pane fade show active" id="tabProgramas">
<div class="mb-3"><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalProgramaPamec"><i class="fas fa-plus me-1"></i>Crear Programa PAMEC</button></div>
<?php if (empty($programas)): ?>
<div class="card-box"><div class="card-box-body text-center py-4 text-muted">No hay programas PAMEC. Cree uno para iniciar el ciclo de mejoramiento.</div></div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($programas as $prog):
    $progAudits = array_filter($auditorias, fn($a) => (int)$a['pamec_id'] === (int)$prog['pamec_id']);
    $progAutoevs = array_filter($autoevaluaciones, fn($a) => (int)$a['pamec_id'] === (int)$prog['pamec_id']);
    $hallazgos = array_sum(array_map(fn($a) => (int)$a['auditoria_hallazgos'], $progAudits));
?>
<div class="col-md-6">
<div class="card-box h-100">
    <div class="card-box-header d-flex justify-content-between align-items-center">
        <strong><?= htmlspecialchars($prog['pamec_nombre']) ?></strong>
        <span class="badge bg-<?= $prog['pamec_estado']==='completado'?'success':($prog['pamec_estado']==='en_progreso'?'primary':'warning') ?>"><?= $prog['pamec_estado'] ?></span>
    </div>
    <div class="card-box-body">
        <div class="small text-muted mb-2"><strong>Año:</strong> <?= $prog['pamec_anio'] ?> · <strong>Creado:</strong> <?= date('d/m/Y', strtotime($prog['created_at'])) ?></div>
        <?php if ($prog['pamec_objetivo']): ?><p class="small mb-2"><strong>Objetivo:</strong> <?= htmlspecialchars(substr($prog['pamec_objetivo'], 0, 150)) ?></p><?php endif; ?>
        <?php if ($prog['pamec_alcance']): ?><p class="small mb-2"><strong>Alcance:</strong> <?= htmlspecialchars(substr($prog['pamec_alcance'], 0, 150)) ?></p><?php endif; ?>
        <div class="row g-2 text-center">
            <div class="col-4"><div class="bg-light rounded p-2"><strong class="text-primary"><?= count($progAutoevs) ?></strong><br><small>Autoevaluaciones</small></div></div>
            <div class="col-4"><div class="bg-light rounded p-2"><strong class="text-info"><?= count($progAudits) ?></strong><br><small>Auditorías</small></div></div>
            <div class="col-4"><div class="bg-light rounded p-2"><strong class="text-danger"><?= $hallazgos ?></strong><br><small>Hallazgos</small></div></div>
        </div>
    </div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<!-- TAB 2: AUTOEVALUACIÓN -->
<div class="tab-pane fade" id="tabAutoevaluacion">
<div class="mb-3"><button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAutoevaluacion"><i class="fas fa-plus me-1"></i>Nueva Autoevaluación</button></div>
<?php if (empty($autoevaluaciones)): ?>
<div class="card-box"><div class="card-box-body text-center py-4 text-muted">Sin autoevaluaciones registradas.</div></div>
<?php else: ?>
<div class="card-box"><div class="card-box-body p-0"><table class="table-box small">
    <thead><tr><th>Criterio</th><th>Estándar</th><th>Calificación</th><th>Estado</th><th>Evidencia</th></tr></thead>
    <tbody>
    <?php foreach ($autoevaluaciones as $ae): ?>
    <tr>
        <td><?= htmlspecialchars(substr($ae['autoeval_criterio'], 0, 80)) ?></td>
        <td><?= htmlspecialchars($ae['autoeval_estandar']) ?></td>
        <td><span class="badge bg-<?= $ae['autoeval_calificacion']>=90?'success':($ae['autoeval_calificacion']>=60?'warning':'danger') ?>"><?= $ae['autoeval_calificacion'] ?></span></td>
        <td><span class="badge bg-<?= $ae['autoeval_estado']==='aprobado'?'success':($ae['autoeval_estado']==='evaluado'?'primary':'secondary') ?>"><?= $ae['autoeval_estado'] ?></span></td>
        <td>
            <?php if ($ae['autoeval_foto_url']): ?><a href="<?= htmlspecialchars($ae['autoeval_foto_url']) ?>" target="_blank"><i class="fas fa-image"></i></a><?php endif; ?>
            <?= htmlspecialchars(substr($ae['autoeval_evidencia'] ?? '', 0, 40)) ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>
<?php endif; ?>
</div>

<!-- TAB 3: AUDITORÍAS -->
<div class="tab-pane fade" id="tabAuditorias">
<div class="mb-3"><button class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalAuditoria"><i class="fas fa-plus me-1"></i>Nueva Auditoría</button></div>
<?php if (empty($auditorias)): ?>
<div class="card-box"><div class="card-box-body text-center py-4 text-muted">Sin auditorías registradas.</div></div>
<?php else: ?>
<div class="card-box"><div class="card-box-body p-0"><table class="table-box small">
    <thead><tr><th>Fecha</th><th>Auditor</th><th>Servicio</th><th>Hallazgos</th><th>Resultado</th></tr></thead>
    <tbody>
    <?php foreach ($auditorias as $aud): ?>
    <tr>
        <td><?= date('d/m/Y', strtotime($aud['auditoria_fecha'])) ?></td>
        <td><?= htmlspecialchars($aud['auditoria_auditor']) ?></td>
        <td><?= htmlspecialchars($aud['auditoria_servicio']) ?></td>
        <td><span class="badge <?= $aud['auditoria_hallazgos']>5?'bg-danger':($aud['auditoria_hallazgos']>0?'bg-warning text-dark':'bg-success') ?>"><?= $aud['auditoria_hallazgos'] ?></span></td>
        <td><span class="badge bg-<?= $aud['auditoria_resultado']==='conforme'?'success':($aud['auditoria_resultado']==='con_observaciones'?'warning':'danger') ?>"><?= str_replace('_',' ',$aud['auditoria_resultado']) ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>
<?php endif; ?>
</div>

<!-- TAB 4: RONDAS DE CALIDAD -->
<div class="tab-pane fade" id="tabRondas">
<div class="mb-3"><button class="btn btn-warning btn-sm text-dark" data-bs-toggle="modal" data-bs-target="#modalRonda"><i class="fas fa-plus me-1"></i>Nueva Ronda</button></div>
<?php if (empty($rondas)): ?>
<div class="card-box"><div class="card-box-body text-center py-4 text-muted">Sin rondas de calidad registradas.</div></div>
<?php else: ?>
<div class="card-box"><div class="card-box-body p-0"><table class="table-box small">
    <thead><tr><th>Servicio</th><th>Mes</th><th>Calificación</th><th>Observaciones</th><th>Foto</th><th>Registrado por</th></tr></thead>
    <tbody>
    <?php foreach ($rondas as $ronda): ?>
    <tr>
        <td><?= htmlspecialchars($ronda['ronda_servicio']) ?></td>
        <td><?= $ronda['ronda_mes'] ?></td>
        <td><span class="badge bg-<?= $ronda['ronda_calificacion']>=90?'success':($ronda['ronda_calificacion']>=60?'warning':'danger') ?>"><?= $ronda['ronda_calificacion'] ?></span></td>
        <td><?= htmlspecialchars(substr($ronda['ronda_observaciones'] ?? '', 0, 60)) ?></td>
        <td><?php if ($ronda['ronda_foto_url']): ?><a href="<?= htmlspecialchars($ronda['ronda_foto_url']) ?>" target="_blank"><i class="fas fa-image"></i></a><?php else: ?>-<?php endif; ?></td>
        <td><small><?= htmlspecialchars($ronda['registrado_por'] ?? '-') ?></small></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>
<?php endif; ?>
</div>

<!-- TAB 5: EQUIPO PAMEC -->
<div class="tab-pane fade" id="tabEquipo">
<div class="mb-3"><button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEquipo"><i class="fas fa-user-plus me-1"></i>Agregar Miembro</button></div>
<?php if (empty($equipos)): ?>
<div class="card-box"><div class="card-box-body text-center py-4 text-muted">No hay equipo conformado.</div></div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($equipos as $eq): ?>
<div class="col-md-4">
<div class="card-box h-100">
    <div class="card-box-body text-center">
        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center fw-bold" style="width:48px;height:48px;background:<?= $eq['equipo_rol']==='lider'?'#6f42c1':($eq['equipo_rol']==='evaluador'?'#1a73e8':($eq['equipo_rol']==='experto_tecnico'?'#28a745':'#6c757d')) ?>;color:#fff;font-size:0.9rem"><?= substr(htmlspecialchars($eq['usuario_nombre'] ?? 'U'), 0, 2) ?></div>
        <strong><?= htmlspecialchars($eq['usuario_nombre'] ?? 'Usuario #'.$eq['usuario_id']) ?></strong><br>
        <span class="badge bg-<?= $eq['equipo_rol']==='lider'?'purple':($eq['equipo_rol']==='evaluador'?'primary':($eq['equipo_rol']==='experto_tecnico'?'success':'secondary')) ?>"><?= str_replace('_',' ',$eq['equipo_rol']) ?></span>
    </div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<!-- TAB 6: CHECKLIST -->
<div class="tab-pane fade" id="tabChecklist">
<div class="mb-3"><button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalChecklist"><i class="fas fa-plus me-1"></i>Nuevo Ítem</button></div>
<?php if (empty($checklist)): ?>
<div class="card-box"><div class="card-box-body text-center py-4 text-muted">No hay ítems en el checklist. <a href="/calidad/checklist?empresa_id=<?= $empresaId ?>">Ir al editor completo</a></div></div>
<?php else: ?>
<div class="card-box"><div class="card-box-body p-0"><table class="table-box small">
    <thead><tr><th>Servicio</th><th>Criterio</th><th>Estándar</th><th>Tipo</th><th>Orden</th></tr></thead>
    <tbody>
    <?php foreach ($checklist as $cl): ?>
    <tr>
        <td><?= htmlspecialchars($cl['item_servicio']) ?></td>
        <td><?= htmlspecialchars(substr($cl['item_criterio'], 0, 70)) ?></td>
        <td><?= htmlspecialchars($cl['item_estandar']) ?></td>
        <td><span class="badge bg-light text-dark"><?= str_replace('_',' ',$cl['item_tipo']) ?></span></td>
        <td><?= $cl['item_orden'] ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>
<?php endif; ?>
</div>

</div><!-- /tab-content -->

<!-- ======================================================================= -->
<!-- MODALES -->
<!-- ======================================================================= -->

<!-- MODAL: Crear Programa PAMEC -->
<div class="modal fade" id="modalProgramaPamec"><div class="modal-dialog"><form method="POST" action="/calidad/pamec/programa/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5>Crear Programa PAMEC</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label class="form-label small">Nombre del Programa *</label><input type="text" name="pamec_nombre" class="form-control form-control-sm" value="PAMEC <?= date('Y') ?>" required></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small">Año</label><input type="number" name="pamec_anio" class="form-control form-control-sm" value="<?= date('Y') ?>"></div></div>
        <div class="mb-2"><label class="form-label small">Objetivo</label><textarea name="pamec_objetivo" class="form-control form-control-sm" rows="2" placeholder="Objetivo del programa de mejoramiento"></textarea></div>
        <div class="mb-2"><label class="form-label small">Alcance</label><textarea name="pamec_alcance" class="form-control form-control-sm" rows="2" placeholder="Áreas, servicios y procesos incluidos"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Crear Programa</button></div>
</form></div></div>

<!-- MODAL: Agregar Miembro al Equipo -->
<div class="modal fade" id="modalEquipo"><div class="modal-dialog"><form method="POST" action="/calidad/pamec/equipo/crear" class="modal-content">
    <div class="modal-header"><h5>Agregar Miembro al Equipo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label class="form-label small">Programa PAMEC *</label><select name="pamec_id" class="form-select form-select-sm" required><?php foreach ($programas as $p): ?><option value="<?= $p['pamec_id'] ?>"><?= htmlspecialchars($p['pamec_nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-2"><label class="form-label small">Usuario *</label><select name="usuario_id" class="form-select form-select-sm" required><?php foreach ($usuarios as $u): ?><option value="<?= $u['usuario_id'] ?>"><?= htmlspecialchars($u['usuario_nombre'].' '.$u['usuario_apellido']) ?></option><?php endforeach; ?></select></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small">Rol</label><select name="equipo_rol" class="form-select form-select-sm"><option value="lider">Líder</option><option value="evaluador">Evaluador</option><option value="experto_tecnico">Experto Técnico</option><option value="observador">Observador</option></select></div><div class="col-6"><label class="form-label small">Fecha Conformación</label><input type="date" name="equipo_fecha_conformacion" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Agregar</button></div>
</form></div></div>

<!-- MODAL: Autoevaluación -->
<div class="modal fade" id="modalAutoevaluacion"><div class="modal-dialog"><form method="POST" action="/calidad/pamec/autoevaluacion/guardar" class="modal-content" enctype="multipart/form-data">
    <div class="modal-header"><h5>Nueva Autoevaluación</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label class="form-label small">Programa PAMEC *</label><select name="pamec_id" class="form-select form-select-sm" required><?php foreach ($programas as $p): ?><option value="<?= $p['pamec_id'] ?>"><?= htmlspecialchars($p['pamec_nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-2"><label class="form-label small">Criterio a Evaluar *</label><textarea name="autoeval_criterio" class="form-control form-control-sm" rows="2" placeholder="Describa el criterio o estándar" required></textarea></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small">Estándar de Referencia</label><input type="text" name="autoeval_estandar" class="form-control form-control-sm" placeholder="Ej: SUA 4.2.1"></div><div class="col-6"><label class="form-label small">Calificación (0-100)</label><input type="number" name="autoeval_calificacion" class="form-control form-control-sm" min="0" max="100" step="0.1" value="0"></div></div>
        <div class="mb-2"><label class="form-label small">Evidencia (descripción)</label><textarea name="autoeval_evidencia" class="form-control form-control-sm" rows="2" placeholder="Describa la evidencia encontrada"></textarea></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small">Foto / Evidencia</label><input type="file" name="autoeval_foto" class="form-control form-control-sm" accept="image/*,.pdf"></div><div class="col-6"><label class="form-label small">Estado</label><select name="autoeval_estado" class="form-select form-select-sm"><option value="pendiente">Pendiente</option><option value="evaluado">Evaluado</option><option value="aprobado">Aprobado</option></select></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Guardar Autoevaluación</button></div>
</form></div></div>

<!-- MODAL: Nueva Auditoría -->
<div class="modal fade" id="modalAuditoria"><div class="modal-dialog"><form method="POST" action="/calidad/pamec/auditoria/crear" class="modal-content">
    <div class="modal-header"><h5>Registrar Auditoría</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label class="form-label small">Programa PAMEC *</label><select name="pamec_id" class="form-select form-select-sm" required><?php foreach ($programas as $p): ?><option value="<?= $p['pamec_id'] ?>"><?= htmlspecialchars($p['pamec_nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small">Fecha *</label><input type="date" name="auditoria_fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div><div class="col-6"><label class="form-label small">Auditor</label><input type="text" name="auditoria_auditor" class="form-control form-control-sm" placeholder="Nombre del auditor"></div></div>
        <div class="mb-2"><label class="form-label small">Servicio / Área</label><input type="text" name="auditoria_servicio" class="form-control form-control-sm" placeholder="Ej: Urgencias, Consulta Externa"></div>
        <div class="row g-2 mb-2"><div class="col-4"><label class="form-label small">Hallazgos</label><input type="number" name="auditoria_hallazgos" class="form-control form-control-sm" value="0" min="0"></div><div class="col-8"><label class="form-label small">Resultado</label><select name="auditoria_resultado" class="form-select form-select-sm"><option value="con_observaciones">Con Observaciones</option><option value="conforme">Conforme</option><option value="no_conforme">No Conforme</option></select></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-info text-white">Registrar</button></div>
</form></div></div>

<!-- MODAL: Ronda de Calidad -->
<div class="modal fade" id="modalRonda"><div class="modal-dialog"><form method="POST" action="/calidad/rondas/crear" class="modal-content" enctype="multipart/form-data">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5>Nueva Ronda de Calidad</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small">Servicio *</label><input type="text" name="ronda_servicio" class="form-control form-control-sm" placeholder="Ej: Hospitalización" required></div><div class="col-6"><label class="form-label small">Mes</label><input type="month" name="ronda_mes" class="form-control form-control-sm" value="<?= date('Y-m') ?>"></div></div>
        <div class="mb-2"><label class="form-label small">Calificación (0-100)</label><input type="number" name="ronda_calificacion" class="form-control form-control-sm" min="0" max="100" step="0.1" value="0"></div>
        <div class="mb-2"><label class="form-label small">Observaciones</label><textarea name="ronda_observaciones" class="form-control form-control-sm" rows="2" placeholder="Hallazgos y observaciones"></textarea></div>
        <div class="mb-2"><label class="form-label small">Foto / Evidencia</label><input type="file" name="ronda_foto" class="form-control form-control-sm" accept="image/*,.pdf"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-warning">Registrar Ronda</button></div>
</form></div></div>

<!-- MODAL: Checklist Item -->
<div class="modal fade" id="modalChecklist"><div class="modal-dialog"><form method="POST" action="/calidad/checklist/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5>Nuevo Ítem de Checklist</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label class="form-label small">Servicio</label><input type="text" name="item_servicio" class="form-control form-control-sm" placeholder="Ej: Urgencias, UCI, Farmacia"></div>
        <div class="mb-2"><label class="form-label small">Criterio *</label><textarea name="item_criterio" class="form-control form-control-sm" rows="2" placeholder="Criterio a verificar" required></textarea></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small">Estándar</label><input type="text" name="item_estandar" class="form-control form-control-sm" placeholder="Ej: SUA 4.3"></div><div class="col-4"><label class="form-label small">Tipo</label><select name="item_tipo" class="form-select form-select-sm"><option value="general">General</option><option value="pamec">PAMEC</option><option value="ronda_calidad">Ronda Calidad</option><option value="auditoria">Auditoría</option></select></div><div class="col-2"><label class="form-label small">Orden</label><input type="number" name="item_orden" class="form-control form-control-sm" value="0"></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Agregar</button></div>
</form></div></div>

<?php $moduloContexto = 'PAMEC y calidad en salud'; require BASE_PATH . '/templates/hse/ia_panel.php'; ?>
