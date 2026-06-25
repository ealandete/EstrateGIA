<?php
$fodaData = json_decode($foda['analisis_contenido'] ?? '{}', true);
$fortalezas = $fodaData['fortalezas'] ?? [];
$debilidades = $fodaData['debilidades'] ?? [];
$oportunidades = $fodaData['oportunidades'] ?? [];
$amenazas = $fodaData['amenazas'] ?? [];
?>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card-box">
            <div class="card-box-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-check-circle me-2" style="color:#28a745"></i>Plan</span>
                <small><?= htmlspecialchars($plan['plan_nombre']) ?></small>
            </div>
        </div>
        <div id="toolSidebar" class="mt-3">
            <p class="small text-muted"><i class="fas fa-info-circle me-1"></i>Haz clic en <strong>"Generar con IA"</strong> para obtener un análisis automático basado en el sector <strong><?= htmlspecialchars($empresa['sector_nombre']??'General') ?></strong>.</p>
            <p class="small text-muted"><i class="fas fa-edit me-1"></i>Puedes editar cada elemento manualmente.</p>

            <button class="btn btn-purple btn-sm w-100 mb-2" onclick="generarFODA()" id="btnGenerar">
                <i class="fas fa-brain me-1"></i>Generar con IA (<?= htmlspecialchars($empresa['sector_nombre']??'General') ?>)
            </button>
            <button class="btn btn-success btn-sm w-100" onclick="guardarFODA()">
                <i class="fas fa-save me-1"></i>Guardar Análisis
            </button>
            <div id="saveStatus" class="mt-2"></div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="row g-3">
            <!-- Fortalezas -->
            <div class="col-md-6">
                <div class="card" style="border-top: 4px solid #28a745;border-radius:12px">
                    <div class="card-body p-3">
                        <h6 class="text-success mb-3"><i class="fas fa-plus-circle me-2"></i>FORTALEZAS (Interno +)</h6>
                        <div id="fortalezasList" class="mb-2">
                            <?php foreach ($fortalezas as $i => $f): ?>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text bg-success text-white" style="font-size:0.65rem">F<?= $i+1 ?></span>
                                <input type="text" class="form-control form-control-sm foda-item" data-zone="fortalezas" value="<?= htmlspecialchars($f) ?>">
                                <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.input-group').remove()"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-sm btn-outline-success w-100" onclick="addItem('fortalezasList','fortalezas','F')"><i class="fas fa-plus me-1"></i>Añadir fortaleza</button>
                    </div>
                </div>
            </div>

            <!-- Debilidades -->
            <div class="col-md-6">
                <div class="card" style="border-top: 4px solid #dc3545;border-radius:12px">
                    <div class="card-body p-3">
                        <h6 class="text-danger mb-3"><i class="fas fa-minus-circle me-2"></i>DEBILIDADES (Interno -)</h6>
                        <div id="debilidadesList" class="mb-2">
                            <?php foreach ($debilidades as $i => $d): ?>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text bg-danger text-white" style="font-size:0.65rem">D<?= $i+1 ?></span>
                                <input type="text" class="form-control form-control-sm foda-item" data-zone="debilidades" value="<?= htmlspecialchars($d) ?>">
                                <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.input-group').remove()"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-sm btn-outline-danger w-100" onclick="addItem('debilidadesList','debilidades','D')"><i class="fas fa-plus me-1"></i>Añadir debilidad</button>
                    </div>
                </div>
            </div>

            <!-- Oportunidades -->
            <div class="col-md-6">
                <div class="card" style="border-top: 4px solid #007bff;border-radius:12px">
                    <div class="card-body p-3">
                        <h6 class="text-primary mb-3"><i class="fas fa-lightbulb me-2"></i>OPORTUNIDADES (Externo +)</h6>
                        <div id="oportunidadesList" class="mb-2">
                            <?php foreach ($oportunidades as $i => $o): ?>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text bg-primary text-white" style="font-size:0.65rem">O<?= $i+1 ?></span>
                                <input type="text" class="form-control form-control-sm foda-item" data-zone="oportunidades" value="<?= htmlspecialchars($o) ?>">
                                <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.input-group').remove()"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100" onclick="addItem('oportunidadesList','oportunidades','O')"><i class="fas fa-plus me-1"></i>Añadir oportunidad</button>
                    </div>
                </div>
            </div>

            <!-- Amenazas -->
            <div class="col-md-6">
                <div class="card" style="border-top: 4px solid #ffc107;border-radius:12px">
                    <div class="card-body p-3">
                        <h6 class="text-warning mb-3"><i class="fas fa-triangle-exclamation me-2"></i>AMENAZAS (Externo -)</h6>
                        <div id="amenazasList" class="mb-2">
                            <?php foreach ($amenazas as $i => $a): ?>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text bg-warning text-dark" style="font-size:0.65rem">A<?= $i+1 ?></span>
                                <input type="text" class="form-control form-control-sm foda-item" data-zone="amenazas" value="<?= htmlspecialchars($a) ?>">
                                <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.input-group').remove()"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-sm btn-outline-warning w-100" onclick="addItem('amenazasList','amenazas','A')"><i class="fas fa-plus me-1"></i>Añadir amenaza</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Matriz de estrategias cruzadas -->
        <div class="card mt-3" style="border-radius:12px">
            <div class="card-body p-3">
                <h6 class="mb-3"><i class="fas fa-arrows-left-right me-2"></i>Estrategias Cruzadas FODA</h6>
                <div class="row g-2 small">
                    <div class="col-md-6">
                        <div class="p-2 bg-success bg-opacity-10 rounded">
                            <strong class="text-success">FO - Estrategias Ofensivas</strong>
                            <p class="text-muted mb-1">Usar fortalezas para aprovechar oportunidades</p>
                            <textarea id="estrategiasFO" class="form-control form-control-sm" rows="2" placeholder="Estrategias ofensivas..."></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 bg-danger bg-opacity-10 rounded">
                            <strong class="text-danger">DA - Estrategias Defensivas</strong>
                            <p class="text-muted mb-1">Reducir debilidades para evitar amenazas</p>
                            <textarea id="estrategiasDA" class="form-control form-control-sm" rows="2" placeholder="Estrategias defensivas..."></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 bg-primary bg-opacity-10 rounded">
                            <strong class="text-primary">DO - Estrategias Adaptativas</strong>
                            <p class="text-muted mb-1">Superar debilidades aprovechando oportunidades</p>
                            <textarea id="estrategiasDO" class="form-control form-control-sm" rows="2" placeholder="Estrategias adaptativas..."></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 bg-warning bg-opacity-10 rounded">
                            <strong class="text-warning">FA - Estrategias de Supervivencia</strong>
                            <p class="text-muted mb-1">Usar fortalezas para mitigar amenazas</p>
                            <textarea id="estrategiasFA" class="form-control form-control-sm" rows="2" placeholder="Estrategias de supervivencia..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function addItem(listId, zone, prefix) {
    const list = document.getElementById(listId);
    const count = list.querySelectorAll('.foda-item').length;
    const div = document.createElement('div');
    div.className = 'input-group input-group-sm mb-1';
    div.innerHTML = `<span class="input-group-text text-white" style="font-size:0.65rem;background:${zone==='fortalezas'?'#28a745':zone==='debilidades'?'#dc3545':zone==='oportunidades'?'#007bff':'#ffc107'}">${prefix}${count+1}</span>
        <input type="text" class="form-control form-control-sm foda-item" data-zone="${zone}" placeholder="Escribe aquí...">
        <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.input-group').remove()"><i class="fas fa-times"></i></button>`;
    list.appendChild(div);
    div.querySelector('input').focus();
}

function collectZones() {
    const zones = {};
    document.querySelectorAll('.foda-item').forEach(inp => {
        const zone = inp.dataset.zone;
        if (!zones[zone]) zones[zone] = [];
        if (inp.value.trim()) zones[zone].push(inp.value.trim());
    });
    return zones;
}

async function generarFODA() {
    const btn = document.getElementById('btnGenerar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generando...';

    try {
        const resp = await fetch('/generar', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `tipo=foda&plan_id=<?= $planId ?>`
        });
        const data = await resp.json();
        if (data.success && data.contenido) {
            const foda = JSON.parse(data.contenido);

            // Limpiar y llenar cada zona
            ['fortalezas','debilidades','oportunidades','amenazas'].forEach(zone => {
                const list = document.getElementById(zone+'List');
                list.innerHTML = '';
                const items = foda[zone] || [];
                const prefix = zone[0].toUpperCase();
                items.forEach((item, i) => {
                    const div = document.createElement('div');
                    div.className = 'input-group input-group-sm mb-1';
                    const colors = {fortalezas:'#28a745',debilidades:'#dc3545',oportunidades:'#007bff',amenazas:'#ffc107'};
                    div.innerHTML = `<span class="input-group-text text-white" style="font-size:0.65rem;background:${colors[zone]}">${prefix}${i+1}</span>
                        <input type="text" class="form-control form-control-sm foda-item" data-zone="${zone}" value="${item.replace(/"/g,'&quot;')}">
                        <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.input-group').remove()"><i class="fas fa-times"></i></button>`;
                    list.appendChild(div);
                });
            });

            document.getElementById('saveStatus').innerHTML = '<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> FODA generado con IA</div>';
        }
    } catch(e) {
        document.getElementById('saveStatus').innerHTML = '<div class="alert alert-danger py-1 px-2 small mt-1">Error al generar</div>';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-brain me-1"></i>Generar con IA';
}

async function guardarFODA() {
    const zones = collectZones();
    document.getElementById('saveStatus').innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Guardando...</div>';

    try {
        const resp = await fetch('/tools/save-foda', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `plan_id=<?= $planId ?>&foda_data=${encodeURIComponent(JSON.stringify(zones))}`
        });
        const data = await resp.json();
        if (data.success) {
            document.getElementById('saveStatus').innerHTML = '<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> Guardado</div>';
        }
    } catch(e) {
        document.getElementById('saveStatus').innerHTML = '<div class="alert alert-danger py-1 px-2 small mt-1">Error al guardar</div>';
    }
}
</script>

<style>
.btn-purple { background:#6f42c1;color:#fff;border:none; }
.btn-purple:hover { background:#5a32a3; }
</style>
