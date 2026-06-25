<?php $variantColors = ['cumplimiento'=>'#28a745','oportunidad'=>'#ffc107','calidad'=>'#007bff','productividad'=>'#6f42c1']; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/planeacion">Planes</a></li>
        <li class="breadcrumb-item"><a href="/planeacion/<?= $planId ?>"><?= htmlspecialchars($plan['plan_nombre']) ?></a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($fase['fase_nombre']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <!-- Columna principal: Pasos del Wizard -->
    <div class="col-md-8">
        <div class="card-box">
            <div class="card-box-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-flag-checkered me-2" style="color:#1a73e8"></i><?= htmlspecialchars($fase['fase_nombre']) ?></span>
                <span class="badge-status badge-<?= $fase['fase_estado']??'pendiente' ?>"><?= $fase['fase_estado']??'pendiente' ?></span>
            </div>
            <div class="card-box-body">
                <!-- Barra de progreso de pasos -->
                <div class="d-flex align-items-center gap-2 mb-4 overflow-auto py-2">
                    <?php foreach ($pasos as $i => $p): $num = $i + 1; $activo = $num === $pasoActual; $completado = $num < $pasoActual; ?>
                    <a href="?paso=<?= $num ?>" class="text-decoration-none">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold
                            <?= $completado ? 'bg-success text-white' : ($activo ? 'bg-primary text-white' : 'bg-light text-muted') ?>"
                            style="width:34px;height:34px;min-width:34px;font-size:0.8rem;cursor:pointer"
                            title="<?= htmlspecialchars(is_array($p) ? ($p['titulo']??$p) : $p) ?>">
                            <?= $completado ? '<i class="fas fa-check"></i>' : $num ?>
                        </div>
                    </a>
                    <?php if ($i < $totalPasos - 1): ?>
                    <div style="flex:1;height:2px;min-width:20px;background:<?= $num < $pasoActual ? '#28a745' : '#ddd' ?>"></div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Paso actual -->
                <?php if ($pasoData): ?>
                <div class="p-4 bg-light rounded-3 border mb-4">
                    <h5 class="mb-3">
                        <span class="text-primary">Paso <?= $pasoActual ?>:</span>
                        <?= htmlspecialchars(is_array($pasoData) ? ($pasoData['titulo'] ?? $pasoData['nombre'] ?? reset($pasoData)) : $pasoData) ?>
                    </h5>

                    <?php if (is_array($pasoData) && !empty($pasoData['descripcion'])): ?>
                    <p class="text-muted"><?= htmlspecialchars($pasoData['descripcion']) ?></p>
                    <?php endif; ?>

                    <!-- Área de trabajo del paso - contenido dinámico según el tipo de paso -->
                    <div class="bg-white p-3 rounded border mt-3" style="min-height:150px">
                        <textarea id="pasoContenido" class="form-control" rows="8" placeholder="Desarrolla aquí el contenido de este paso... La IA puede ayudarte a generar un borrador."></textarea>
                    </div>

                    <!-- Botones de acción -->
                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-sm btn-purple" onclick="pedirAyudaIA()" style="background:#6f42c1;color:#fff">
                            <i class="fas fa-brain me-1"></i>Ayuda IA para este paso
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="guardarAvance()">
                            <i class="fas fa-save me-1"></i>Guardar avance
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="marcarCompletado()">
                            <i class="fas fa-check me-1"></i>Marcar completado
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Navegación entre pasos -->
                <div class="d-flex justify-content-between">
                    <?php if ($pasoActual > 1): ?>
                    <a href="?paso=<?= $pasoActual - 1 ?>" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i>Paso anterior</a>
                    <?php else: ?><div></div><?php endif; ?>
                    <?php if ($pasoActual < $totalPasos): ?>
                    <a href="?paso=<?= $pasoActual + 1 ?>" class="btn btn-primary">Siguiente paso<i class="fas fa-arrow-right ms-1"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Herramientas IA según el contexto de la fase -->
        <div class="card-box mt-4">
            <div class="card-box-header"><i class="fas fa-magic me-2" style="color:#6f42c1"></i>Generar con IA</div>
            <div class="card-box-body">
                <div class="row g-2">
                    <?php if (str_contains(strtolower($fase['fase_nombre']), 'visión') || str_contains(strtolower($fase['fase_nombre']), 'definición')): ?>
                    <div class="col-md-6"><button class="btn btn-outline-purple btn-sm w-100 py-2" onclick="generarIA('mision')"><i class="fas fa-bullseye me-1"></i>Generar Misión</button></div>
                    <div class="col-md-6"><button class="btn btn-outline-purple btn-sm w-100 py-2" onclick="generarIA('vision')"><i class="fas fa-eye me-1"></i>Generar Visión</button></div>
                    <div class="col-md-6"><button class="btn btn-outline-purple btn-sm w-100 py-2" onclick="generarIA('valores')"><i class="fas fa-heart me-1"></i>Generar Valores</button></div>
                    <div class="col-md-6"><button class="btn btn-outline-purple btn-sm w-100 py-2" onclick="generarIA('objetivos')"><i class="fas fa-flag me-1"></i>Generar Objetivos SMART</button></div>
                    <?php endif; ?>

                    <?php if (str_contains(strtolower($fase['fase_nombre']), 'análisis') || str_contains(strtolower($fase['fase_nombre']), 'entorno')): ?>
                    <div class="col-md-6"><button class="btn btn-outline-purple btn-sm w-100 py-2" onclick="generarIA('foda')"><i class="fas fa-chess-board me-1"></i>Generar FODA</button></div>
                    <div class="col-md-6"><button class="btn btn-outline-purple btn-sm w-100 py-2" onclick="generarIA('pestel')"><i class="fas fa-globe me-1"></i>Generar PESTEL</button></div>
                    <?php endif; ?>

                    <div class="col-md-6"><button class="btn btn-outline-purple btn-sm w-100 py-2" onclick="generarIA('indicadores')"><i class="fas fa-gauge me-1"></i>Sugerir KPIs</button></div>
                    <div class="col-md-6"><button class="btn btn-outline-purple btn-sm w-100 py-2" onclick="generarIA('proceso')"><i class="fas fa-diagram-project me-1"></i>Documentar Proceso</button></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna lateral: IA y Normatividad -->
    <div class="col-md-4">
        <!-- Chat rápido con IA -->
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-robot me-2" style="color:#6f42c1"></i>Asistente IA</div>
            <div class="card-box-body">
                <div id="chatBox" style="height:220px;overflow-y:auto;background:#fafafa;border-radius:8px;padding:10px;margin-bottom:8px;font-size:0.85rem">
                    <div class="text-muted small mb-2"><i class="fas fa-robot me-1"></i>Pregúntame sobre este paso, el sector <?= htmlspecialchars($empresa['sector_nombre']??'General') ?>, o la metodología <?= htmlspecialchars($plan['metodologia_nombre']) ?>.</div>
                </div>
                <div class="input-group input-group-sm">
                    <input type="text" id="chatInput" class="form-control" placeholder="Pregunta algo..." onkeypress="if(event.key==='Enter')preguntarChat()">
                    <button class="btn btn-primary" onclick="preguntarChat()"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>

        <!-- Normatividad aplicable al sector -->
        <?php if (!empty($normasAplicables)): ?>
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-certificate me-2" style="color:#28a745"></i>Normas ISO Aplicables</div>
            <div class="card-box-body">
                <?php foreach ($normasAplicables as $norma): ?>
                <div class="mb-2 p-2 border rounded small">
                    <strong><?= htmlspecialchars($norma['norma_codigo']) ?></strong>
                    <div class="text-muted"><?= htmlspecialchars(substr($norma['norma_nombre'],0,80)) ?></div>
                    <?php if (!empty($norma['norma_requisitos_json'])): ?>
                    <button class="btn btn-sm btn-link p-0 mt-1" type="button" data-bs-toggle="collapse" data-bs-target="#req_<?= $norma['norma_id'] ?>">
                        Ver requisitos
                    </button>
                    <div class="collapse mt-1" id="req_<?= $norma['norma_id'] ?>">
                        <?php $reqs = json_decode($norma['norma_requisitos_json'], true)['clausulas'] ?? []; ?>
                        <?php foreach ($reqs as $c): ?>
                        <div class="badge bg-light text-dark border me-1 mb-1">§<?= $c['num'] ?> <?= $c['nombre'] ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Análisis previos -->
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-clipboard-list me-2"></i>Análisis Realizados</div>
            <div class="card-box-body">
                <div class="mb-2">
                    <strong>FODA:</strong>
                    <?php if ($foda): ?>
                    <span class="text-success"><i class="fas fa-check-circle"></i> Completado</span>
                    <a href="/planeacion/<?= $planId ?>/foda" class="btn btn-sm btn-outline-primary ms-2">Ver</a>
                    <?php else: ?>
                    <span class="text-muted">Pendiente</span>
                    <button class="btn btn-sm btn-outline-purple ms-2" onclick="generarIA('pestel')"><i class="fas fa-magic"></i> Generar</button>
                    <?php endif; ?>
                </div>
                <div>
                    <strong>PESTEL:</strong>
                    <?php if ($pestel): ?>
                    <span class="text-success"><i class="fas fa-check-circle"></i> <a href="/planeacion/<?= $planId ?>/pestel" class="text-success">Completado</a></span>
                    <?php else: ?>
                    <span class="text-muted">Pendiente</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const planId = <?= $planId ?>;
const empresaNombre = '<?= addslashes($empresa['empresa_nombre'] ?? '') ?>';
const sectorNombre = '<?= addslashes($empresa['sector_nombre'] ?? 'General') ?>';

async function pedirAyudaIA() {
    const contenido = document.getElementById('pasoContenido').value || 'Quiero ayuda con este paso';
    document.getElementById('chatBox').innerHTML += `<div class="mb-1"><strong class="text-primary">Tú:</strong> ${contenido}</div>`;
    document.getElementById('chatBox').innerHTML += '<div class="mb-1 text-muted"><i class="fas fa-spinner fa-spin"></i> Pensando...</div>';
    try {
        const resp = await fetch('/ia/preguntar', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `contexto=planeacion&metodologia=<?= urlencode($plan['metodologia_nombre'] ?? '') ?>&fase=<?= urlencode($fase['fase_nombre'] ?? '') ?>&prompt=${encodeURIComponent('Estoy en la fase "<?= addslashes($fase['fase_nombre']) ?>" (paso <?= $pasoActual ?>) de la metodología <?= addslashes($plan['metodologia_nombre'] ?? '') ?>. Empresa: '+empresaNombre+', Sector: '+sectorNombre+'. Ayúdame con: '+contenido)}`
        });
        const data = await resp.json();
        document.getElementById('chatBox').lastChild.remove();
        document.getElementById('chatBox').innerHTML += `<div class="mb-2"><strong style="color:#6f42c1"><i class="fas fa-robot"></i> IA:</strong> ${data.respuesta || 'Sin respuesta'}</div>`;
        document.getElementById('pasoContenido').value = data.respuesta || '';
    } catch(e) {
        document.getElementById('chatBox').lastChild.remove();
        document.getElementById('chatBox').innerHTML += '<div class="mb-1 text-danger">Error de conexión</div>';
    }
    document.getElementById('chatBox').scrollTop = document.getElementById('chatBox').scrollHeight;
}

async function preguntarChat() {
    const input = document.getElementById('chatInput');
    const pregunta = input.value.trim();
    if (!pregunta) return;
    input.value = '';
    document.getElementById('chatBox').innerHTML += `<div class="mb-1"><strong class="text-primary">Tú:</strong> ${pregunta}</div>`;
    document.getElementById('chatBox').innerHTML += '<div class="mb-1 text-muted"><i class="fas fa-spinner fa-spin"></i></div>';
    try {
        const resp = await fetch('/ia/preguntar', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `contexto=planeacion&metodologia=<?= urlencode($plan['metodologia_nombre'] ?? '') ?>&prompt=${encodeURIComponent('Metodología: <?= addslashes($plan['metodologia_nombre'] ?? '') ?>. Empresa: '+empresaNombre+' ('+sectorNombre+'). Plan: <?= addslashes($plan['plan_nombre']) ?>. Fase: <?= addslashes($fase['fase_nombre']) ?>. Pregunta: '+pregunta)}`
        });
        const data = await resp.json();
        document.getElementById('chatBox').lastChild.remove();
        document.getElementById('chatBox').innerHTML += `<div class="mb-2"><strong style="color:#6f42c1"><i class="fas fa-robot"></i> IA:</strong> ${data.respuesta || 'Sin respuesta'}</div>`;
    } catch(e) {
        document.getElementById('chatBox').lastChild.remove();
        document.getElementById('chatBox').innerHTML += '<div class="mb-1 text-danger">Error</div>';
    }
    document.getElementById('chatBox').scrollTop = document.getElementById('chatBox').scrollHeight;
}

async function generarIA(tipo) {
    document.getElementById('chatBox').innerHTML += '<div class="mb-1 text-muted"><i class="fas fa-spinner fa-spin"></i> Generando '+tipo+'...</div>';
    try {
        const resp = await fetch('/fase/generar', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `plan_id=${planId}&fase_id=<?= $faseId ?>&tipo=${tipo}&metodologia=<?= urlencode($plan['metodologia_nombre'] ?? '') ?>&sector=<?= urlencode($empresa['sector_nombre'] ?? 'General') ?>&objetivo=${encodeURIComponent(document.getElementById('pasoContenido')?.value||'')}`
        });
        const data = await resp.json();
        document.getElementById('chatBox').lastChild.remove();
        if (data.success) {
            document.getElementById('chatBox').innerHTML += `<div class="mb-2"><strong style="color:#6f42c1"><i class="fas fa-robot"></i> IA generó ${tipo}:</strong><br><div class="small bg-light p-2 rounded mt-1" style="white-space:pre-wrap;max-height:300px;overflow-y:auto">${data.contenido||''}</div></div>`;
            if (tipo === 'foda') document.getElementById('pasoContenido').value = data.contenido || '';
        }
    } catch(e) {
        document.getElementById('chatBox').lastChild.remove();
        document.getElementById('chatBox').innerHTML += '<div class="mb-1 text-danger">Error generando contenido</div>';
    }
    document.getElementById('chatBox').scrollTop = document.getElementById('chatBox').scrollHeight;
}

async function guardarAvance() {
    var contenido = document.getElementById('pasoContenido')?.value || '';
    try {
        var fd = new FormData();
        fd.append('plan_id', '<?= $plan['plan_id'] ?>');
        fd.append('fase_id', '<?= $fase['fase_id'] ?>');
        fd.append('contenido', contenido);
        var r = await fetch('/tools/guardar-avance', {method:'POST',body:fd});
        var resp = await r.json();
        if (resp.success) { alert('✅ Avance guardado correctamente.'); }
        else { alert('Error al guardar.'); }
    } catch(e) { alert('Error de conexión.'); }
}

async function marcarCompletado() {
    if (!confirm('¿Marcar este paso como completado? Se desbloqueará el siguiente paso.')) return;
    try {
        var contenido = document.getElementById('pasoContenido')?.value || '';
        // Guardar primero
        var fd1 = new FormData();
        fd1.append('plan_id', '<?= $plan['plan_id'] ?>');
        fd1.append('fase_id', '<?= $fase['fase_id'] ?>');
        fd1.append('contenido', contenido);
        await fetch('/tools/guardar-avance', {method:'POST',body:fd1});
        // Completar
        var fd2 = new FormData();
        fd2.append('plan_id', '<?= $plan['plan_id'] ?>');
        fd2.append('fase_id', '<?= $fase['fase_id'] ?>');
        var r = await fetch('/tools/completar-fase', {method:'POST',body:fd2});
        var resp = await r.json();
        if (resp.success) {
            alert('✅ Paso completado. Avance del plan: ' + (resp.avance||'?') + '%');
            location.reload();
        }
    } catch(e) { alert('Error de conexión.'); }
}
</script>

<style>
.btn-outline-purple { color: #6f42c1; border-color: #6f42c1; }
.btn-outline-purple:hover { background: #6f42c1; color: #fff; }
</style>
