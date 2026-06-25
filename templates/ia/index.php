<?php $modeloColors = ['recomendacion'=>'#28a745','prediccion'=>'#007bff','analisis'=>'#ffc107','generacion'=>'#6f42c1','asistente'=>'#1a73e8']; ?>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-robot me-2"></i>Asistente IA de Planeación</div>
            <div class="card-box-body">
                <div id="chatContainer" style="height:400px;overflow-y:auto;border:1px solid #eee;border-radius:8px;padding:16px;background:#fafafa;margin-bottom:12px">
                    <div class="mb-3">
                        <div class="d-flex gap-2">
                            <div style="width:32px;height:32px;border-radius:50%;background:#6f42c1;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0"><i class="fas fa-robot"></i></div>
                            <div class="p-3 rounded-3" style="background:#fff;max-width:80%;box-shadow:0 1px 2px rgba(0,0,0,0.05)">
                                ¡Hola! Soy EstrateGIA, tu asistente de planeación estratégica con IA. Puedo ayudarte a:
                                <ul class="mt-2 mb-0 small">
                                    <li>Definir misión, visión y valores</li>
                                    <li>Construir análisis FODA/PESTEL</li>
                                    <li>Crear objetivos y estrategias SMART</li>
                                    <li>Recomendar indicadores KPIs</li>
                                    <li>Generar documentación ISO</li>
                                    <li>Predecir tendencias</li>
                                </ul>
                                ¿En qué te puedo ayudar?
                            </div>
                        </div>
                    </div>
                </div>
                <form id="iaForm" onsubmit="return sendPrompt()">
                    <div class="input-group">
                        <select id="contexto" class="form-select" style="max-width:160px">
                            <option value="general">General</option>
                            <option value="planeacion">Planeación</option>
                            <option value="procesos">Procesos</option>
                            <option value="indicadores">Indicadores</option>
                            <option value="documentacion">Documentación</option>
                            <option value="evaluacion">Evaluación</option>
                        </select>
                        <input type="text" id="prompt" class="form-control" placeholder="Escribe tu consulta de planeación estratégica..." required>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-chart-bar me-2"></i>Estadísticas IA</div>
            <div class="card-box-body">
                <div class="mb-2"><strong><?= $stats['total_asistencias'] ?></strong> consultas realizadas</div>
                <div class="mb-2"><strong><?= $stats['total_recomendaciones'] ?></strong> recomendaciones</div>
                <div class="mb-2"><strong><?= $stats['recomendaciones_aplicadas'] ?></strong> aplicadas</div>
                <div><strong><?= $stats['tasa_aplicacion'] ?>%</strong> tasa de aplicación</div>
            </div>
        </div>
        <div class="card-box">
            <div class="card-box-header">Historial Reciente</div>
            <div class="card-box-body">
                <?php foreach (array_slice($historial, 0, 10) as $h): ?>
                <div class="mb-2 p-2 border rounded small">
                    <strong><?= $h['asistencia_contexto'] ?></strong>
                    <div class="text-muted"><?= htmlspecialchars(substr($h['asistencia_prompt'], 0, 80)) ?>...</div>
                    <div class="text-end"><small><?= date('d/m H:i', strtotime($h['created_at'])) ?></small></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
async function sendPrompt() {
    const prompt = document.getElementById('prompt').value;
    const contexto = document.getElementById('contexto').value;
    if (!prompt) return false;

    const container = document.getElementById('chatContainer');
    container.innerHTML += `<div class="mb-3 text-end"><div class="d-inline-block p-3 rounded-3" style="background:#1a73e8;color:#fff;max-width:80%">${prompt}</div></div>`;
    document.getElementById('prompt').value = '';
    container.innerHTML += '<div class="mb-3"><div class="d-flex gap-2"><div style="width:32px;height:32px;border-radius:50%;background:#6f42c1;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0"><i class="fas fa-robot"></i></div><div class="p-3 rounded-3" style="background:#fff;max-width:80%"><i class="fas fa-spinner fa-spin"></i> Pensando...</div></div></div>';
    container.scrollTop = container.scrollHeight;

    try {
        const resp = await fetch('/ia/preguntar', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `contexto=${encodeURIComponent(contexto)}&prompt=${encodeURIComponent(prompt)}`
        });
        const data = await resp.json();
        container.lastChild.innerHTML = container.lastChild.innerHTML.replace('<i class="fas fa-spinner fa-spin"></i> Pensando...', data.respuesta || 'Sin respuesta del asistente.');
    } catch(e) {
        container.lastChild.innerHTML = container.lastChild.innerHTML.replace('<i class="fas fa-spinner fa-spin"></i> Pensando...', 'Error de conexión con la IA.');
    }
    container.scrollTop = container.scrollHeight;
    return false;
}
</script>
