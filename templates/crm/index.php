<div class="row g-4">
    <div class="col-md-4">
        <!-- Conexiones -->
        <div class="card-box mb-3">
            <div class="card-box-header d-flex justify-content-between">
                <span><i class="fas fa-plug me-2"></i>Conexiones (<?= count($conexiones ?? []) ?>)</span>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalConexion"><i class="fas fa-plus"></i></button>
            </div>
            <div class="card-box-body p-0">
                <?php foreach ($conexiones as $con): ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between mb-1">
                        <strong><?= htmlspecialchars($con['conexion_nombre']) ?></strong>
                        <span class="badge bg-<?= $con['conexion_estado_salud']==='ok'?'success':'secondary' ?>"><?= $con['conexion_estado_salud']?></span>
                    </div>
                    <small class="text-muted"><?= $con['conexion_tipo'] ?> · <?= htmlspecialchars($con['conexion_proveedor']??'') ?></small>
                    <?php if ($con['conexion_url']): ?><div class="small text-truncate"><?= htmlspecialchars($con['conexion_url']) ?></div><?php endif; ?>
                    <button class="btn btn-sm btn-outline-success mt-1" onclick="testConexion(<?= $con['conexion_id'] ?>)"><i class="fas fa-vial me-1"></i>Probar</button>
                </div>
                <?php endforeach; ?>
                <?php if (empty($conexiones)): ?><div class="p-3 text-muted small text-center">Sin conexiones. Crea una.</div><?php endif; ?>
            </div>
        </div>

        <!-- Mapeos -->
        <div class="card-box">
            <div class="card-box-header d-flex justify-content-between">
                <span><i class="fas fa-link me-2"></i>Mapeos (<?= count($mapeos ?? []) ?>)</span>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalMapeo"><i class="fas fa-plus"></i></button>
            </div>
            <div class="card-box-body p-0">
                <?php foreach ($mapeos as $m): ?>
                <div class="p-2 px-3 border-bottom small">
                    <strong><?= htmlspecialchars($m['mapeo_nombre']) ?></strong>
                    <span class="badge bg-light text-dark ms-1"><?= $m['mapeo_tipo_indicador'] ?></span>
                    <div class="text-muted"><?= htmlspecialchars($m['conexion_nombre']) ?> → <?= htmlspecialchars($m['indicador_nombre']??'Sin indicador') ?></div>
                    <div class="d-flex justify-content-between mt-1">
                        <small><?= $m['mapeo_ultima_ejecucion'] ? date('d/m H:i', strtotime($m['mapeo_ultima_ejecucion'])) : 'Nunca' ?></small>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-success" onclick="sincronizar(<?= $m['mapeo_id'] ?>)"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Resultados -->
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-terminal me-2"></i>Consola de Resultados</div>
            <div class="card-box-body" id="consola" style="min-height:200px;background:#1a1a2e;color:#0f0;font-family:monospace;font-size:0.8rem;max-height:300px;overflow-y:auto">
                <div class="text-muted">> Esperando acciones...</div>
                <div class="text-muted">> Crea una conexión, configura un mapeo y sincroniza para ver resultados aquí.</div>
            </div>
        </div>

        <!-- Historial -->
        <?php if (!empty($sincros)): ?>
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-history me-2"></i>Historial de Sincronización</div>
            <div class="card-box-body p-0"><table class="table-box small">
                <thead><tr><th>Mapeo</th><th>Conexión</th><th>Tipo</th><th>Última ejecución</th></tr></thead>
                <tbody>
                <?php foreach ($sincros as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['mapeo_nombre']) ?></td>
                    <td><?= htmlspecialchars($s['conexion_nombre']) ?></td>
                    <td><?= $s['mapeo_tipo_indicador'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($s['mapeo_ultima_ejecucion'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL Conexión -->
<div class="modal fade" id="modalConexion"><div class="modal-dialog"><form method="POST" action="/crm/conexion/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5>Nueva Conexión</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="Nombre *" required>
        <div class="row g-2 mb-2"><div class="col-6"><select name="tipo" class="form-select form-select-sm"><option value="api_rest">API REST</option><option value="crm">CRM</option><option value="erp">ERP</option><option value="base_datos">Base de Datos</option></select></div><div class="col-6"><input type="text" name="proveedor" class="form-control form-control-sm" placeholder="Proveedor"></div></div>
        <input type="text" name="url" class="form-control form-control-sm mb-2" placeholder="URL / Endpoint">
        <select name="auth" class="form-select form-select-sm"><option value="api_key">API Key</option><option value="oauth2">OAuth 2.0</option><option value="basic">Basic Auth</option></select>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Crear</button></div>
</form></div></div>

<!-- MODAL Mapeo -->
<div class="modal fade" id="modalMapeo"><div class="modal-dialog"><form method="POST" action="/crm/mapeo/crear" class="modal-content">
    <div class="modal-header"><h5>Nuevo Mapeo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="Nombre del mapeo *" required>
        <select name="conexion_id" class="form-select form-select-sm mb-2" required><option value="">Conexión origen *</option><?php foreach ($conexiones as $c): ?><option value="<?= $c['conexion_id'] ?>"><?= htmlspecialchars($c['conexion_nombre']) ?></option><?php endforeach; ?></select>
        <select name="tipo_indicador" class="form-select form-select-sm mb-2"><option value="cumplimiento">Cumplimiento</option><option value="oportunidad">Oportunidad</option><option value="calidad">Calidad</option><option value="productividad">Productividad</option></select>
        <select name="indicador_id" class="form-select form-select-sm mb-2"><option value="">Vincular a indicador existente</option><?php foreach ($indicadores as $ind): ?><option value="<?= $ind['indicador_id'] ?>"><?= htmlspecialchars($ind['indicador_nombre']) ?></option><?php endforeach; ?></select>
        <div class="row g-2"><div class="col-6"><input type="text" name="endpoint" class="form-control form-control-sm" placeholder="Endpoint"></div><div class="col-6"><input type="text" name="campo" class="form-control form-control-sm" placeholder="Campo origen"></div></div>
        <select name="frecuencia" class="form-select form-select-sm mt-2"><option value="diaria">Diaria</option><option value="semanal">Semanal</option><option value="mensual">Mensual</option></select>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Crear Mapeo</button></div>
</form></div></div>

<script>
function log(msg, color) { const c=document.getElementById('consola'); c.innerHTML+=`<div style="color:${color||'#0f0'}">> ${msg}</div>`; c.scrollTop=c.scrollHeight; }
async function testConexion(id) {
    log('Probando conexión...', '#ff0');
    try {
        const r=await fetch('/crm/conexion/test',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'conexion_id='+id,credentials:'same-origin'});
        const d=await r.json();
        if(d.success){ log(`✅ ${d.status} - ${d.tiempo_respuesta_ms}ms - ${d.endpoints_detectados} endpoints - ${d.registros_accesibles} registros`, '#0f0'); }
        else { log('❌ Error: '+d.message, '#f00'); }
    } catch(e) { log('❌ Error de conexión', '#f00'); }
}
async function sincronizar(id) {
    log('Iniciando sincronización...', '#ff0');
    try {
        const r=await fetch('/crm/sincronizar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'mapeo_id='+id,credentials:'same-origin'});
        const d=await r.json();
        log(`✅ ${d.registros_procesados} registros procesados, ${d.mediciones_creadas} mediciones creadas, ${d.errores} errores`, '#0f0');
    } catch(e) { log('❌ Error en sincronización', '#f00'); }
}
</script>
