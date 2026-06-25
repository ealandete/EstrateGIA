<?php
// Contexto IA dinámico según módulo
$contextoIA = $contextoIA ?? 'general';
if (!$contextoIA || $contextoIA === 'general') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (str_contains($path, '/sst')) $contextoIA = 'sst';
    elseif (str_contains($path, '/ambiental')) $contextoIA = 'ambiental';
    elseif (str_contains($path, '/calidad') || str_contains($path, '/pamec') || str_contains($path, '/nc')) $contextoIA = 'calidad';
    elseif (str_contains($path, '/proveedores')) $contextoIA = 'proveedores';
    elseif (str_contains($path, '/planeacion')) $contextoIA = 'planeacion';
    elseif (str_contains($path, '/procesos')) $contextoIA = 'procesos';
}
?>
<div class="card-box mt-4">
    <div class="card-box-header" style="background:linear-gradient(135deg,#6f42c1,#4a2d8a);color:#fff">
        <i class="fas fa-brain me-2"></i>Asistente IA de <?= $pageTitle ?>
        <button class="btn btn-sm btn-light ms-auto" onclick="toggleIAPanel()" style="font-size:0.7rem">
            <i class="fas fa-chevron-up" id="iaToggleIcon"></i>
        </button>
    </div>
    <div id="iaPanel" class="card-box-body">
        <p class="small text-muted mb-2">La IA analiza los datos actuales de <?= htmlspecialchars($empresa['empresa_nombre']) ?> y sugiere estrategias, próximos pasos y recomendaciones basadas en las normas aplicables al sector.</p>

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <button class="btn btn-outline-purple btn-sm w-100" onclick="preguntarIA('sugerencias')">
                    <i class="fas fa-lightbulb me-1"></i>Sugerir estrategias
                </button>
            </div>
            <div class="col-md-4">
                <button class="btn btn-outline-purple btn-sm w-100" onclick="preguntarIA('brechas')">
                    <i class="fas fa-magnifying-glass-chart me-1"></i>Analizar brechas
                </button>
            </div>
            <div class="col-md-4">
                <button class="btn btn-outline-purple btn-sm w-100" onclick="preguntarIA('plan')">
                    <i class="fas fa-list-check me-1"></i>Plan de acción
                </button>
            </div>
        </div>

        <div id="iaRespuesta" style="min-height:100px;background:#f8f6fc;border-radius:8px;padding:12px;font-size:0.85rem">
            <div class="text-muted text-center py-3">
                <i class="fas fa-robot" style="font-size:2rem;color:#ccc;display:block;margin-bottom:8px"></i>
                Haz clic en una opción para recibir recomendaciones de la IA.
            </div>
        </div>

        <div class="input-group input-group-sm mt-2">
            <input type="text" id="iaCustomPrompt" class="form-control" placeholder="O escribe tu propia consulta..." onkeypress="if(event.key==='Enter')preguntarIA('custom')">
            <button class="btn btn-purple" onclick="preguntarIA('custom')"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script>
function toggleIAPanel() {
    var panel = document.getElementById('iaPanel');
    var icon = document.getElementById('iaToggleIcon');
    if (panel.style.display === 'none') { panel.style.display = 'block'; icon.className = 'fas fa-chevron-up'; }
    else { panel.style.display = 'none'; icon.className = 'fas fa-chevron-down'; }
}

async function preguntarIA(tipo) {
    var resp = document.getElementById('iaRespuesta');
    resp.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Analizando datos de <?= htmlspecialchars($empresa['empresa_nombre']) ?>...</div>';

    var prompt = '';
    var moduloContexto = '<?= $moduloContexto ?>';
    var empresa = '<?= addslashes($empresa['empresa_nombre']) ?>';
    var sector = '<?= addslashes($empresa['sector_nombre'] ?? 'General') ?>';

    if (tipo === 'sugerencias') {
        prompt = 'Eres un consultor experto en ' + moduloContexto + ' para el sector ' + sector + '. Analiza la empresa ' + empresa + ' y sugiere 3-5 estrategias concretas para mejorar en este módulo. Sé específico y accionable.';
    } else if (tipo === 'brechas') {
        prompt = 'Eres un auditor experto en ' + moduloContexto + ' para el sector ' + sector + '. Identifica las principales brechas que podría tener ' + empresa + ' en este aspecto y cómo cerrarlas.';
    } else if (tipo === 'plan') {
        prompt = 'Eres un planificador experto en ' + moduloContexto + '. Para la empresa ' + empresa + ' del sector ' + sector + ', genera un plan de acción de 5 pasos concreto con responsables sugeridos y tiempos estimados.';
    } else {
        prompt = document.getElementById('iaCustomPrompt').value || 'Dame recomendaciones para mejorar en ' + moduloContexto;
    }

    try {
        var r = await fetch('/ia/preguntar', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'contexto=<?= $contextoIA ?>&prompt=' + encodeURIComponent(prompt),
            credentials:'same-origin'
        });
        var d = await r.json();
        resp.innerHTML = '<div style="white-space:pre-wrap">' + (d.respuesta || 'Sin respuesta del asistente.') + '</div>';
    } catch(e) {
        resp.innerHTML = '<div class="text-danger">Error de conexión con la IA. Intenta de nuevo.</div>';
    }
}
</script>

<style>
.btn-purple { background:#6f42c1;color:#fff;border:none; }
.btn-purple:hover { background:#5a32a3;color:#fff; }
.btn-outline-purple { color:#6f42c1;border-color:#6f42c1; }
.btn-outline-purple:hover { background:#6f42c1;color:#fff; }
</style>
