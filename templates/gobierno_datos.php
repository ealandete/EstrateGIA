<div class="container-fluid p-3">
    <h4><i class="fas fa-database me-2"></i>Gobierno de Datos <small class="text-muted fs-6">DAMA-DMBOK · Nivel 4 Gestionado</small></h4>

    <!-- KPIs -->
    <div class="row g-3 mb-3">
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Catálogo</div><div class="stat-value"><?= count($catalogo) ?></div><small class="text-muted">columnas documentadas</small></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Clasificaciones</div><div class="stat-value"><?= count($clasificaciones) ?></div><small class="text-muted">tablas etiquetadas</small></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Consentimientos</div><div class="stat-value"><?= count($consentimientos) ?></div><small class="text-muted">Ley 1581</small></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Calidad</div><div class="stat-value"><?= count(array_filter($metricas, fn($m)=>($m['metrica_semaforo']??'')==='verde')) ?>/<?= count($metricas) ?></div><small class="text-muted">métricas verdes</small></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Linaje</div><div class="stat-value"><?= count($linajes) ?></div><small class="text-muted">trazas</small></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Retención</div><div class="stat-value"><?= count($retenciones) ?></div><small class="text-muted">políticas activas</small></div></div>
    </div>

    <ul class="nav nav-tabs mb-3 small">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabCatalogo">Catálogo</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabClasificacion">Clasificación</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabAuditoria">Auditoría Accesos</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabConsentimientos">Consentimientos</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSolicitudes">Derechos ARCO</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabCalidad">Calidad Datos</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabLinaje">Linaje</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabRetencion">Retención</a></li>
    </ul>

    <div class="tab-content">
        <!-- CATALOGO -->
        <div class="tab-pane fade show active" id="tabCatalogo">
            <div class="card-box"><div class="card-box-body p-0"><div class="table-responsive"><table class="table-box small mb-0"><thead><tr><th>Tabla</th><th>Columna</th><th>Tipo</th><th>Descripción Negocio</th><th>Clasificación</th><th>Origen</th><th>Data Owner</th><th>Audita</th></tr></thead><tbody>
            <?php foreach($catalogo as $c): ?>
            <tr><td><code><?= $c['catalogo_tabla'] ?></code></td><td><strong><?= $c['catalogo_columna'] ?></strong></td><td><?= $c['catalogo_tipo_dato'] ?></td><td><small><?= htmlspecialchars($c['catalogo_descripcion_negocio'] ?? '') ?></small></td>
            <td><span class="badge bg-<?= $c['catalogo_clasificacion']==='sensible'?'danger':($c['catalogo_clasificacion']==='confidencial'?'warning':'info') ?>"><?= $c['catalogo_clasificacion'] ?></span></td>
            <td><?= $c['catalogo_origen'] ?></td><td><small><?= htmlspecialchars($c['catalogo_data_owner'] ?? '') ?></small></td>
            <td><?= $c['catalogo_requiere_auditoria']?'<span class="badge bg-success">Sí</span>':'<span class="badge bg-secondary">No</span>' ?></td></tr>
            <?php endforeach; ?></tbody></table></div></div></div>
        </div>

        <!-- CLASIFICACION -->
        <div class="tab-pane fade" id="tabClasificacion">
            <div class="card-box"><div class="card-box-body p-0"><table class="table-box small mb-0"><thead><tr><th>Tabla</th><th>Nivel</th><th>Base Legal</th><th>Retención</th><th>Encript</th><th>Audita</th><th>Anonimizable</th></tr></thead><tbody>
            <?php foreach($clasificaciones as $c): ?>
            <tr><td><code><?= $c['clasificacion_tabla'] ?></code></td>
            <td><span class="badge bg-<?= $c['clasificacion_nivel']==='sensible'||$c['clasificacion_nivel']==='critico'?'danger':($c['clasificacion_nivel']==='confidencial'?'warning':'info') ?>"><?= $c['clasificacion_nivel'] ?></span></td>
            <td><small><?= htmlspecialchars($c['clasificacion_base_legal'] ?? '') ?></small></td>
            <td><?= round($c['clasificacion_tiempo_retencion_meses']/12,1) ?> años</td>
            <td><?= $c['clasificacion_requiere_encriptacion']?'🔒':'' ?></td>
            <td><?= $c['clasificacion_requiere_auditoria_accesos']?'👁':'' ?></td>
            <td><?= $c['clasificacion_anonimizable']?'✅':'' ?></td></tr>
            <?php endforeach; ?></tbody></table></div></div>
        </div>

        <!-- AUDITORIA ACCESOS -->
        <div class="tab-pane fade" id="tabAuditoria">
            <div class="card-box"><div class="card-box-body p-0"><table class="table-box small mb-0"><thead><tr><th>Fecha</th><th>Usuario</th><th>Tabla</th><th>Registro</th><th>Acción</th><th>IP</th><th>Justificación</th></tr></thead><tbody>
            <?php foreach($accesos as $a): ?>
            <tr><td><?= $a['acceso_fecha'] ?></td><td><?= htmlspecialchars($a['usuario_nombre'] ?? 'Sistema') ?></td>
            <td><code><?= $a['acceso_tabla'] ?></code></td><td><?= $a['acceso_registro_id'] ?></td>
            <td><span class="badge bg-<?= $a['acceso_accion']==='eliminacion'?'danger':($a['acceso_accion']==='exportacion'?'warning':'info') ?>"><?= $a['acceso_accion'] ?></span></td>
            <td><small><?= $a['acceso_ip'] ?></small></td><td><small><?= htmlspecialchars($a['acceso_justificacion'] ?? '') ?></small></td></tr>
            <?php endforeach; ?></tbody></table></div></div>
        </div>

        <!-- CONSENTIMIENTOS -->
        <div class="tab-pane fade" id="tabConsentimientos">
            <button class="btn btn-success btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalConsent"><i class="fas fa-plus"></i> Nuevo Consentimiento</button>
            <div class="card-box"><div class="card-box-body p-0"><table class="table-box small mb-0"><thead><tr><th>Titular</th><th>Tipo</th><th>Tratamiento</th><th>Finalidad</th><th>Otorgado</th><th>Estado</th></tr></thead><tbody>
            <?php foreach($consentimientos as $c): ?>
            <tr><td><strong><?= htmlspecialchars($c['consentimiento_titular_nombre'] ?? $c['consentimiento_titular_id']) ?></strong><br><small class="text-muted"><?= $c['consentimiento_titular_id'] ?></small></td>
            <td><?= $c['consentimiento_tipo'] ?></td><td><?= $c['consentimiento_tratamiento'] ?></td>
            <td><small><?= htmlspecialchars(mb_strimwidth($c['consentimiento_finalidad'],0,80,'...')) ?></small></td>
            <td><?= $c['consentimiento_fecha_otorgamiento'] ?></td>
            <td><span class="badge bg-<?= $c['consentimiento_estado']==='vigente'?'success':'warning' ?>"><?= $c['consentimiento_estado'] ?></span></td></tr>
            <?php endforeach; ?></tbody></table></div></div>
        </div>

        <!-- DERECHOS ARCO -->
        <div class="tab-pane fade" id="tabSolicitudes">
            <button class="btn btn-success btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalSolicitud"><i class="fas fa-plus"></i> Nueva Solicitud</button>
            <div class="card-box"><div class="card-box-body p-0"><table class="table-box small mb-0"><thead><tr><th>Fecha</th><th>Tipo</th><th>Titular</th><th>Descripción</th><th>Estado</th></tr></thead><tbody>
            <?php if($solicitudes): foreach($solicitudes as $s): ?>
            <tr><td><?= $s['solicitud_fecha'] ?></td><td><span class="badge bg-info"><?= $s['solicitud_tipo'] ?></span></td>
            <td><?= htmlspecialchars($s['solicitud_titular_nombre'] ?? $s['solicitud_titular_id']) ?></td>
            <td><small><?= htmlspecialchars(mb_strimwidth($s['solicitud_descripcion']??'',0,60,'...')) ?></small></td>
            <td><span class="badge bg-<?= $s['solicitud_estado']==='resuelta'?'success':($s['solicitud_estado']==='rechazada'?'danger':'warning') ?>"><?= $s['solicitud_estado'] ?></span></td></tr>
            <?php endforeach; else: ?><tr><td colspan="5" class="text-center text-muted py-3">Sin solicitudes de titulares</td></tr><?php endif; ?></tbody></table></div></div>
        </div>

        <!-- CALIDAD DATOS -->
        <div class="tab-pane fade" id="tabCalidad">
            <div class="card-box"><div class="card-box-body p-0"><table class="table-box small mb-0"><thead><tr><th>Dimensión</th><th>Tabla</th><th>Regla</th><th>Esperado</th><th>Real</th><th>Semáforo</th><th>Fecha</th><th>Acción</th></tr></thead><tbody>
            <?php foreach($metricas as $m): ?>
            <tr><td><?= $m['metrica_dimension'] ?></td><td><code><?= $m['metrica_tabla'] ?></code></td>
            <td><small><?= htmlspecialchars($m['metrica_regla'] ?? '') ?></small></td>
            <td><?= $m['metrica_valor_esperado'] ?>%</td>
            <td><strong><?= $m['metrica_valor_real'] ?>%</strong></td>
            <td><span class="badge bg-<?= $m['metrica_semaforo']==='verde'?'success':($m['metrica_semaforo']==='amarillo'?'warning':'danger') ?>"><?= $m['metrica_semaforo'] ?></span></td>
            <td><?= $m['metrica_fecha_medicion'] ?></td>
            <td><form method="POST" action="/gobierno-datos/metrica/evaluar" class="d-flex gap-1"><input type="hidden" name="metrica_id" value="<?= $m['metrica_id'] ?>"><input type="number" step="0.1" name="valor_real" class="form-control form-control-sm" style="width:60px" placeholder="%"><button class="btn btn-sm btn-outline-primary">OK</button></form></td></tr>
            <?php endforeach; ?></tbody></table></div></div>
        </div>

        <!-- LINAJE -->
        <div class="tab-pane fade" id="tabLinaje">
            <div class="card-box"><div class="card-box-body p-0"><table class="table-box small mb-0"><thead><tr><th>Origen</th><th>→</th><th>Destino</th><th>Transformación</th><th>Tipo</th></tr></thead><tbody>
            <?php foreach($linajes as $l): ?>
            <tr><td><code><?= $l['linaje_origen_tabla'] ?>.<?= $l['linaje_origen_columna'] ?></code></td>
            <td>→</td><td><code><?= $l['linaje_destino_tabla'] ?></code></td>
            <td><small><?= htmlspecialchars($l['linaje_transformacion'] ?? '') ?></small></td>
            <td><span class="badge bg-info"><?= $l['linaje_tipo'] ?></span></td></tr>
            <?php endforeach; ?></tbody></table></div></div>
        </div>

        <!-- RETENCION -->
        <div class="tab-pane fade" id="tabRetencion">
            <div class="card-box"><div class="card-box-body p-0"><table class="table-box small mb-0"><thead><tr><th>Tabla</th><th>Retención</th><th>Acción</th><th>Justificación Normativa</th></tr></thead><tbody>
            <?php foreach($retenciones as $r): ?>
            <tr><td><code><?= $r['retencion_tabla'] ?></code></td>
            <td><?= round($r['retencion_periodo_meses']/12,1) ?> años</td>
            <td><span class="badge bg-<?= $r['retencion_accion']==='eliminar'?'danger':($r['retencion_accion']==='anonimizar'?'warning':'info') ?>"><?= $r['retencion_accion'] ?></span></td>
            <td><small><?= htmlspecialchars($r['retencion_justificacion_normativa'] ?? '') ?></small></td></tr>
            <?php endforeach; ?></tbody></table></div></div>
        </div>
    </div>
</div>

<!-- MODAL CONSENTIMIENTO -->
<div class="modal fade" id="modalConsent"><div class="modal-dialog"><form method="POST" action="/gobierno-datos/consentimiento/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId??2 ?>">
    <div class="modal-header"><h5>Nuevo Consentimiento Ley 1581</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><div class="col-6"><label class="small fw-bold">Tipo</label><select name="tipo" class="form-select form-select-sm"><option value="paciente">Paciente</option><option value="trabajador">Trabajador</option><option value="proveedor">Proveedor</option></select></div>
        <div class="col-6"><label class="small fw-bold">Tratamiento</label><select name="tratamiento" class="form-select form-select-sm"><option value="datos_personales">Datos Personales</option><option value="datos_sensibles">Datos Sensibles</option><option value="datos_salud">Datos de Salud</option></select></div></div>
        <input name="titular_id" class="form-control form-control-sm mb-2" placeholder="ID del titular (CC, NIT)" required>
        <input name="titular_nombre" class="form-control form-control-sm mb-2" placeholder="Nombre del titular">
        <textarea name="finalidad" class="form-control form-control-sm mb-2" rows="2" placeholder="Finalidad del tratamiento de datos" required></textarea>
        <input type="date" name="fecha_otorgamiento" class="form-control form-control-sm" value="<?=date('Y-m-d')?>">
    </div><div class="modal-footer"><button class="btn btn-success btn-sm">Guardar</button></div>
</form></div></div>

<!-- MODAL SOLICITUD ARCO -->
<div class="modal fade" id="modalSolicitud"><div class="modal-dialog"><form method="POST" action="/gobierno-datos/solicitud/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId??2 ?>">
    <div class="modal-header"><h5>Nueva Solicitud de Titular (Derechos ARCO)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <select name="tipo" class="form-select form-select-sm mb-2" required><option value="acceso">Acceso</option><option value="rectificacion">Rectificación</option><option value="supresion">Supresión (Derecho al olvido)</option><option value="oposicion">Oposición</option><option value="revocacion">Revocación consentimiento</option><option value="portabilidad">Portabilidad</option></select>
        <input name="titular_id" class="form-control form-control-sm mb-2" placeholder="ID del titular" required>
        <input name="titular_nombre" class="form-control form-control-sm mb-2" placeholder="Nombre del titular">
        <textarea name="descripcion" class="form-control form-control-sm" rows="2" placeholder="Descripción de la solicitud"></textarea>
    </div><div class="modal-footer"><button class="btn btn-info btn-sm">Registrar Solicitud</button></div>
</form></div></div>
