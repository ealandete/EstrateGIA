<!-- SISTEMA INTEGRADO DE GESTION ISO -->
<ul class="nav nav-tabs mb-3 small">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabPoliticas">Políticas</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabCAPA">CAPA</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabRevision">Revisión Dirección</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabContexto">Contexto</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabComunicaciones">Comunicaciones</a></li>
</ul>

<div class="tab-content">
    <!-- POLITICAS -->
    <div class="tab-pane fade show active" id="tabPoliticas">
        <?php foreach ($politicas as $p): ?>
        <div class="card-box mb-2"><div class="card-box-header"><span class="badge bg-<?= $p['politica_tipo']==='calidad'?'primary':($p['politica_tipo']==='ambiental'?'success':'warning') ?> me-2"><?= strtoupper($p['politica_tipo']) ?></span> v<?= $p['politica_version'] ?> — Aprobada: <?= $p['politica_fecha_aprobacion'] ?> por <?= htmlspecialchars($p['politica_firmante']) ?></div>
        <div class="card-box-body"><p class="small"><?= nl2br(htmlspecialchars($p['politica_texto'])) ?></p></div></div>
        <?php endforeach; ?>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalPolitica"><i class="fas fa-plus"></i> Nueva Política</button>
    </div>

    <!-- CAPA -->
    <div class="tab-pane fade" id="tabCAPA">
        <div class="d-flex justify-content-between mb-2"><small class="text-muted">Acciones Correctivas, Preventivas y de Mejora</small>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalCAPA"><i class="fas fa-plus"></i> Nueva CAPA</button></div>
        <?php if ($capas): ?>
        <table class="table-box small"><thead><tr><th>Tipo</th><th>Origen</th><th>Módulo</th><th>Acción</th><th>Responsable</th><th>Fecha</th><th>Estado</th><th></th></tr></thead><tbody>
        <?php foreach ($capas as $c): ?>
        <tr><td><span class="badge bg-<?= $c['capa_tipo']==='correctiva'?'danger':($c['capa_tipo']==='preventiva'?'warning':'info') ?>"><?= $c['capa_tipo'] ?></span></td>
        <td><?= $c['capa_origen'] ?></td><td><?= $c['capa_modulo'] ?></td>
        <td><?= htmlspecialchars(mb_strimwidth($c['capa_accion'],0,60,'...')) ?></td>
        <td><small>ID:<?= $c['capa_responsable_id'] ?></small></td>
        <td><?= $c['capa_fecha_compromiso'] ?></td>
        <td><span class="badge bg-<?= $c['capa_estado']==='cerrada'?'success':($c['capa_estado']==='vencida'?'danger':'warning') ?>"><?= $c['capa_estado'] ?></span></td>
        <td><?php if ($c['capa_estado']!=='cerrada'): ?><form method="POST" action="/sistema/capa/cerrar" class="d-inline"><input type="hidden" name="capa_id" value="<?= $c['capa_id'] ?>"><input name="verificacion" class="form-control form-control-sm d-inline" style="width:120px" placeholder="Verificación"><button class="btn btn-sm btn-success">Cerrar</button></form><?php endif; ?></td></tr>
        <?php endforeach; ?></tbody></table>
        <?php else: ?><p class="text-muted text-center py-4">Sin CAPAs registradas</p><?php endif; ?>
    </div>

    <!-- REVISION DIRECCION -->
    <div class="tab-pane fade" id="tabRevision">
        <button class="btn btn-success btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalRevision"><i class="fas fa-plus"></i> Nueva Revisión</button>
        <?php if ($revisiones): ?>
        <?php foreach ($revisiones as $r): ?>
        <div class="card-box mb-2"><div class="card-box-header">Revisión <?= $r['revision_anio'] ?> — <?= $r['revision_fecha'] ?> (<?= $r['revision_alcance'] ?>)</div>
        <div class="card-box-body"><p><strong>Participantes:</strong> <?= htmlspecialchars($r['revision_participantes']) ?></p></div></div>
        <?php endforeach; ?>
        <?php else: ?><p class="text-muted text-center py-4">Sin revisiones por la dirección</p><?php endif; ?>
    </div>

    <!-- CONTEXTO -->
    <div class="tab-pane fade" id="tabContexto">
        <button class="btn btn-success btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalContexto"><i class="fas fa-plus"></i> Nuevo Elemento</button>
        <?php if ($contextos): ?>
        <table class="table-box small"><thead><tr><th>Tipo</th><th>Módulo</th><th>Descripción</th><th>Impacto</th></tr></thead><tbody>
        <?php foreach ($contextos as $c): ?>
        <tr><td><span class="badge bg-<?= in_array($c['contexto_tipo'],['fortaleza','oportunidad'])?'success':'danger' ?>"><?= $c['contexto_tipo'] ?></span></td>
        <td><?= $c['contexto_modulo'] ?></td><td><?= htmlspecialchars($c['contexto_descripcion']) ?></td>
        <td><span class="badge bg-<?= $c['contexto_impacto']==='alto'?'danger':($c['contexto_impacto']==='medio'?'warning':'info') ?>"><?= $c['contexto_impacto'] ?></span></td></tr>
        <?php endforeach; ?></tbody></table>
        <?php else: ?><p class="text-muted text-center py-4">Sin análisis de contexto</p><?php endif; ?>
    </div>

    <!-- COMUNICACIONES -->
    <div class="tab-pane fade" id="tabComunicaciones">
        <button class="btn btn-success btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalComunicacion"><i class="fas fa-plus"></i> Nueva Comunicación</button>
        <?php if ($comunicaciones): ?>
        <table class="table-box small"><thead><tr><th>Módulo</th><th>Tipo</th><th>Qué</th><th>A quién</th><th>Cómo</th><th>Cuándo</th></tr></thead><tbody>
        <?php foreach ($comunicaciones as $c): ?>
        <tr><td><?= $c['comunicacion_modulo'] ?></td><td><?= $c['comunicacion_tipo'] ?></td>
        <td><?= htmlspecialchars($c['comunicacion_que']) ?></td><td><?= htmlspecialchars($c['comunicacion_a_quien']) ?></td>
        <td><?= htmlspecialchars($c['comunicacion_como']) ?></td><td><?= htmlspecialchars($c['comunicacion_cuando']) ?></td></tr>
        <?php endforeach; ?></tbody></table>
        <?php else: ?><p class="text-muted text-center py-4">Sin matriz de comunicaciones</p><?php endif; ?>
    </div>
</div>

<!-- MODALES -->
<div class="modal fade" id="modalPolitica"><div class="modal-dialog"><form method="POST" action="/sistema/politica/guardar" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? 2 ?>">
    <div class="modal-header"><h5>Nueva Política</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <select name="tipo" class="form-select form-select-sm mb-2"><option value="calidad">Calidad</option><option value="ambiental">Ambiental</option><option value="sst">SST</option><option value="integrada">Integrada</option></select>
        <textarea name="texto" class="form-control form-control-sm mb-2" rows="5" placeholder="Texto de la política..." required></textarea>
        <div class="row g-2"><div class="col-6"><input type="date" name="fecha_aprobacion" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div><div class="col-6"><input name="firmante" class="form-control form-control-sm" placeholder="Firmante"></div></div>
    </div><div class="modal-footer"><button class="btn btn-success btn-sm">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalCAPA"><div class="modal-dialog"><form method="POST" action="/sistema/capa/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? 2 ?>">
    <div class="modal-header"><h5>Nueva CAPA</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><div class="col-4"><select name="tipo" class="form-select form-select-sm"><option value="correctiva">Correctiva</option><option value="preventiva">Preventiva</option><option value="mejora">Mejora</option></select></div>
        <div class="col-4"><select name="origen" class="form-select form-select-sm"><option value="nc">No Conformidad</option><option value="auditoria">Auditoría</option><option value="incidente">Incidente</option><option value="inspeccion">Inspección</option><option value="queja">Queja</option><option value="otro">Otro</option></select></div>
        <div class="col-4"><select name="modulo" class="form-select form-select-sm"><option value="general">General</option><option value="sst">SST</option><option value="ambiental">Ambiental</option><option value="calidad">Calidad</option></select></div></div>
        <textarea name="descripcion" class="form-control form-control-sm mb-2" rows="2" placeholder="Descripción de la no conformidad o hallazgo"></textarea>
        <textarea name="analisis_causa" class="form-control form-control-sm mb-2" rows="2" placeholder="Análisis de causa raíz"></textarea>
        <textarea name="accion" class="form-control form-control-sm mb-2" rows="2" placeholder="Acción a tomar" required></textarea>
        <input type="date" name="fecha_compromiso" class="form-control form-control-sm">
    </div><div class="modal-footer"><button class="btn btn-success btn-sm">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalRevision"><div class="modal-dialog"><form method="POST" action="/sistema/revision/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? 2 ?>">
    <div class="modal-header"><h5>Nueva Revisión por la Dirección</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><div class="col-4"><input type="number" name="anio" class="form-control form-control-sm" value="<?= date('Y') ?>"></div><div class="col-4"><input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
        <div class="col-4"><select name="alcance" class="form-select form-select-sm"><option value="integrada">Integrada</option><option value="calidad">Calidad</option><option value="ambiental">Ambiental</option><option value="sst">SST</option></select></div></div>
        <textarea name="participantes" class="form-control form-control-sm mb-2" rows="2" placeholder="Participantes (nombres y cargos)"></textarea>
        <textarea name="compromisos" class="form-control form-control-sm" rows="2" placeholder="Compromisos y decisiones"></textarea>
    </div><div class="modal-footer"><button class="btn btn-success btn-sm">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalContexto"><div class="modal-dialog"><form method="POST" action="/sistema/contexto/guardar" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? 2 ?>">
    <div class="modal-header"><h5>Nuevo Elemento de Contexto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><div class="col-6"><select name="tipo" class="form-select form-select-sm"><option value="fortaleza">Fortaleza</option><option value="debilidad">Debilidad</option><option value="oportunidad">Oportunidad</option><option value="amenaza">Amenaza</option></select></div>
        <div class="col-6"><select name="modulo" class="form-select form-select-sm"><option value="integrado">Integrado</option><option value="calidad">Calidad</option><option value="ambiental">Ambiental</option><option value="sst">SST</option></select></div></div>
        <textarea name="descripcion" class="form-control form-control-sm mb-2" rows="3" required></textarea>
        <select name="impacto" class="form-select form-select-sm"><option value="alto">Alto</option><option value="medio" selected>Medio</option><option value="bajo">Bajo</option></select>
    </div><div class="modal-footer"><button class="btn btn-success btn-sm">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalComunicacion"><div class="modal-dialog"><form method="POST" action="/sistema/comunicacion/guardar" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? 2 ?>">
    <div class="modal-header"><h5>Nueva Comunicación</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><div class="col-6"><select name="modulo" class="form-select form-select-sm"><option value="integrada">Integrada</option><option value="calidad">Calidad</option><option value="ambiental">Ambiental</option><option value="sst">SST</option></select></div>
        <div class="col-6"><select name="tipo" class="form-select form-select-sm"><option value="interna">Interna</option><option value="externa">Externa</option></select></div></div>
        <input name="que" class="form-control form-control-sm mb-2" placeholder="¿Qué se comunica?" required>
        <input name="a_quien" class="form-control form-control-sm mb-2" placeholder="¿A quién?">
        <input name="como" class="form-control form-control-sm mb-2" placeholder="¿Cómo?">
        <input name="cuando" class="form-control form-control-sm mb-2" placeholder="¿Cuándo?">
        <input name="quien" class="form-control form-control-sm" placeholder="¿Quién comunica?">
    </div><div class="modal-footer"><button class="btn btn-success btn-sm">Guardar</button></div>
</form></div></div>
