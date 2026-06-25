<div class="row g-4">
    <div class="col-md-4">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-plug me-2"></i>Asistente de Minería CRM</div>
            <div class="card-box-body">
                <p class="small text-muted">Conecta tus CRMs y sistemas externos para extraer indicadores automáticamente y minimizar el registro manual.</p>

                <div class="mb-3">
                    <label class="form-label small">Empresa</label>
                    <select id="empresaMineria" class="form-select form-select-sm">
                        <?php 
                        $pm = new PlanManager();
                        foreach ($pm->getEmpresas() as $e): 
                        ?>
                        <option value="<?= $e['empresa_id'] ?>"><?= htmlspecialchars($e['empresa_nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="btn btn-purple btn-sm w-100 mb-2" onclick="iniciarMineria()" id="btnMineria">
                    <i class="fas fa-search me-1"></i>Ejecutar Minería de Datos
                </button>

                <button class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="document.getElementById('conexionForm').style.display='block'">
                    <i class="fas fa-plus me-1"></i>Nueva Conexión CRM
                </button>

                <div id="conexionForm" style="display:none" class="p-3 border rounded mt-2">
                    <h6 class="small">Conectar CRM / ERP / API</h6>
                    <input type="text" id="conexionNombre" class="form-control form-control-sm mb-1" placeholder="Nombre de la conexión">
                    <select id="conexionTipo" class="form-select form-select-sm mb-1">
                        <option value="crm">CRM (Salesforce, HubSpot)</option>
                        <option value="erp">ERP (SAP, Oracle)</option>
                        <option value="api_rest">API REST</option>
                        <option value="base_datos">Base de Datos</option>
                        <option value="web_service">Web Service</option>
                    </select>
                    <input type="text" id="conexionURL" class="form-control form-control-sm mb-1" placeholder="URL / Endpoint">
                    <input type="text" id="conexionKey" class="form-control form-control-sm mb-1" placeholder="API Key">
                    <button class="btn btn-sm btn-primary w-100" onclick="guardarConexion()">Guardar Conexión</button>
                </div>

                <div id="mineriaStatus" class="mt-2"></div>
            </div>
        </div>

        <div class="card-box mt-3">
            <div class="card-box-header"><i class="fas fa-info-circle me-2"></i>¿Cómo funciona?</div>
            <div class="card-box-body small">
                <ol class="mb-0">
                    <li class="mb-2"><strong>Conecta tu CRM</strong> - Salesforce, HubSpot, SAP, Oracle, o cualquier API REST.</li>
                    <li class="mb-2"><strong>Configura mapeos</strong> - Indica qué campos del CRM corresponden a qué indicadores.</li>
                    <li class="mb-2"><strong>Ejecuta minería</strong> - El sistema extrae datos automáticamente y los registra como mediciones.</li>
                    <li><strong>Programa sincronización</strong> - Configura ejecución diaria/semanal automática.</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-database me-2"></i>Resultados de Minería</div>
            <div class="card-box-body" id="mineriaResultados">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-robot" style="font-size:3rem;color:#ccc;display:block;margin-bottom:12px"></i>
                    <p>Conecta un CRM y ejecuta la minería para ver los indicadores detectados automáticamente.</p>
                    <p class="small">La minería busca en correos, documentos, bases de datos y APIs patrones que correspondan a tus KPIs definidos.</p>
                </div>
            </div>
        </div>

        <!-- Mapeos sugeridos -->
        <div class="card-box mt-3">
            <div class="card-box-header"><i class="fas fa-link me-2"></i>Mapeos de Datos Sugeridos</div>
            <div class="card-box-body">
                <table class="table-box small">
                    <thead><tr><th>Indicador</th><th>Fuente CRM</th><th>Campo</th><th>Frecuencia</th></tr></thead>
                    <tbody>
                        <tr><td>Cumplimiento de metas</td><td>Salesforce</td><td>Opportunity.Stage = 'Closed Won'</td><td>Diaria</td></tr>
                        <tr><td>Oportunidad en entrega</td><td>HubSpot</td><td>Deals.ClosedDate vs Expected</td><td>Diaria</td></tr>
                        <tr><td>Satisfacción (NPS)</td><td>Zendesk</td><td>Survey.Score</td><td>Semanal</td></tr>
                        <tr><td>Productividad equipo</td><td>Jira</td><td>Issues.Resolved / Sprint</td><td>Semanal</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
async function iniciarMineria() {
    const btn = document.getElementById('btnMineria');
    const empresaId = document.getElementById('empresaMineria').value;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Minando datos...';
    document.getElementById('mineriaStatus').innerHTML = '';

    try {
        const resp = await fetch('/mediciones/mineria/ejecutar', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'empresa_id=' + empresaId,
            credentials: 'same-origin'
        });
        const data = await resp.json();

        let html = '<h6 class="mb-3">Resultados de la Minería</h6>';
        html += '<div class="row g-2 mb-3">';
        html += '<div class="col-4"><div class="p-2 bg-light rounded text-center"><strong>' + (data.mapeos_sincronizados || 0) + '</strong><br><small>Mapeos sincronizados</small></div></div>';
        html += '<div class="col-4"><div class="p-2 bg-light rounded text-center"><strong>' + (data.minerias_ejecutadas || 0) + '</strong><br><small>Minerías ejecutadas</small></div></div>';
        html += '<div class="col-4"><div class="p-2 bg-light rounded text-center"><strong>' + (data.mediciones_creadas || 0) + '</strong><br><small>Mediciones creadas</small></div></div>';
        html += '</div>';

        if (data.errores && data.errores.length > 0) {
            html += '<div class="alert alert-warning small"><strong>Advertencias:</strong><ul class="mb-0">';
            data.errores.forEach(e => html += '<li>' + e + '</li>');
            html += '</ul></div>';
        }

        if ((data.mediciones_creadas || 0) === 0) {
            html += '<div class="alert alert-info small"><i class="fas fa-info-circle me-1"></i>No se encontraron nuevas mediciones. Asegúrate de tener conexiones CRM configuradas con mapeos de datos activos.</div>';
        }

        document.getElementById('mineriaResultados').innerHTML = html;
        document.getElementById('mineriaStatus').innerHTML = '<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> Minería completada</div>';
    } catch(e) {
        document.getElementById('mineriaResultados').innerHTML = '<div class="alert alert-danger">Error al ejecutar minería: ' + e.message + '</div>';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-search me-1"></i>Ejecutar Minería de Datos';
}

async function guardarConexion() {
    const nombre = document.getElementById('conexionNombre').value;
    const tipo = document.getElementById('conexionTipo').value;
    const url = document.getElementById('conexionURL').value;
    if (!nombre) { alert('Ingresa un nombre'); return; }
    try {
        var fd = new FormData();
        fd.append('nombre', nombre); fd.append('tipo', tipo); fd.append('url', url);
        fd.append('empresa_id', <?= $empresaId ?? 2 ?>);
        var r = await fetch('/crm/conexion/crear', { method: 'POST', body: fd });
        if (r.ok) {
            alert('Conexión "' + nombre + '" guardada correctamente.');
            location.reload();
        } else {
            alert('Error al guardar la conexión.');
        }
    } catch(e) {
        alert('Error: ' + e.message);
    }
}
</script>
<style>
.btn-purple { background:#6f42c1;color:#fff;border:none; }
.btn-purple:hover { background:#5a32a3;color:#fff; }
</style>
