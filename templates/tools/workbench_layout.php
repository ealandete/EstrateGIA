<?php
$pm = $this->pm;
$variantColors = ['cumplimiento'=>'#28a745','oportunidad'=>'#ffc107','calidad'=>'#007bff','productividad'=>'#6f42c1'];
$toolColors = ['foda'=>'#28a745','pestel'=>'#007bff','bsc'=>'#1a73e8','okr'=>'#ff9800','scenarios'=>'#6f42c1','vision'=>'#e91e63','design'=>'#00bcd4','generic'=>'#1a73e8'];
$toolNames = ['foda'=>'FODA','pestel'=>'PESTEL','bsc'=>'Mapa Estratégico BSC','okr'=>'OKR Builder','scenarios'=>'Canvas de Escenarios','vision'=>'Visión/Misión','design'=>'Design Thinking','generic'=>'Guía Paso a Paso'];
$toolIcons = ['foda'=>'chess-board','pestel'=>'globe','bsc'=>'project-diagram','okr'=>'bullseye','scenarios'=>'chess','vision'=>'eye','design'=>'lightbulb','generic'=>'list-check'];
?>

<?php if (!empty($fasesPreviasPendientes)): ?>
<div class="alert alert-warning border-start border-4 border-warning mb-4">
    <h5><i class="fas fa-lock me-2"></i>Ruta Crítica - Fase Bloqueada</h5>
    <p class="mb-2">Debes completar primero:</p>
    <ul class="mb-2">
        <?php foreach ($fasesPreviasPendientes as $fp): ?>
        <li><a href="/workbench/<?= $planId ?>/<?= $fp['fase_id'] ?>" class="fw-bold"><?= htmlspecialchars($fp['fase_nombre']) ?></a> <span class="badge-status badge-<?= $fp['fase_estado'] ?>"><?= $fp['fase_estado'] ?></span></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="/planeacion">Planes</a></li>
        <li class="breadcrumb-item"><a href="/planeacion/<?= $planId ?>"><?= htmlspecialchars($plan['plan_nombre']) ?></a></li>
    </ol>
</nav>

<div class="d-flex align-items-center gap-3 mb-4">
    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:<?= $toolColors[$tool] ?? '#888' ?>;color:#fff;font-size:1.4rem">
        <i class="fas fa-<?= $toolIcons[$tool] ?? 'circle' ?>"></i>
    </div>
    <div>
        <h4 class="mb-0"><?= htmlspecialchars($fase['fase_nombre']) ?></h4>
        <span class="text-muted small"><?= $toolNames[$tool] ?? '' ?> · <?= htmlspecialchars($plan['metodologia_nombre']) ?></span>
    </div>
    <div class="ms-auto d-flex gap-2 align-items-center">
        <span class="badge-status badge-<?= $fase['fase_estado']??'pendiente' ?>"><?= $fase['fase_estado']??'pendiente' ?></span>
        <?php if (!$bloquearAcceso && !in_array($fase['fase_estado'] ?? '', ['completada', 'aprobada'])): ?>
        <button class="btn btn-success" id="btnCompletar" disabled title="Completa al menos un campo antes de finalizar" onclick="completarFase(<?= $planId ?>, <?= $faseId ?>)"><i class="fas fa-check me-1"></i>Completar fase</button>
        <?php endif; ?>
    </div>
</div>

<?php if (in_array($fase['fase_estado'] ?? '', ['completada', 'aprobada'])): ?>
<div class="alert alert-success border-start border-4 border-success mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div><strong><i class="fas fa-check-circle me-2"></i>Fase completada</strong></div>
        <div class="d-flex gap-2">
            <a href="/planeacion/<?= $planId ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Volver al Plan</a>
            <?php $sgte = null; foreach ($todasLasFases as $i => $f) { if ($f['fase_id'] == $faseId && isset($todasLasFases[$i+1])) { $sgte = $todasLasFases[$i+1]; break; } } ?>
            <?php if ($sgte): ?>
            <a href="/workbench/<?= $planId ?>/<?= $sgte['fase_id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-arrow-right me-1"></i><?= htmlspecialchars($sgte['fase_nombre']) ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- GUÍA DE LA FASE -->
<?php
$guiasFase = [
    'pestel' => ['🎯 Objetivo','Analiza el macroentorno en 6 dimensiones (Político, Económico, Social, Tecnológico, Ecológico, Legal) para identificar oportunidades y amenazas externas que afectan la estrategia.','💡 Ejemplo','Para un hospital: "Reforma al sistema de salud" (Político), "Envejecimiento poblacional" (Social), "Telemedicina en expansión" (Tecnológico).','📝 Qué hacer','Agrega 2-3 factores por cada dimensión. Sé específico. La IA puede sugerir contenido para el sector. Al terminar, haz clic en <b>Completar fase</b>.'],
    'foda' => ['🎯 Objetivo','Identifica Fortalezas y Debilidades (internas) + Oportunidades y Amenazas (externas). Es el diagnóstico estratégico fundamental. Define también Misión y Visión.','💡 Ejemplo','Fortaleza: "85% del personal certificado". Debilidad: "Rotación del 22%". Oportunidad: "Turismo de salud crece 12%". Amenaza: "Reforma regulatoria".','📝 Qué hacer','Define primero Misión y Visión. Luego lista 3-5 elementos por cada cuadrante. Usa verbos de acción. Sé honesto con las debilidades.'],
    'bsc' => ['🎯 Objetivo','Construye el Mapa Estratégico conectando objetivos en 4 perspectivas: Financiera, Cliente, Procesos Internos y Aprendizaje. Cada objetivo debe tener indicadores KPIs.','💡 Ejemplo','Financiera: "Aumentar margen EBITDA a 18%". Cliente: "NPS superior a 75". Procesos: "Digitalizar 80% de procesos". Aprendizaje: "Reducir rotación a <10%".','📝 Qué hacer','Define 1-3 objetivos por perspectiva. Conéctalos con flechas causa-efecto. Cada objetivo tendrá indicadores que medirás después.'],
    'okr' => ['🎯 Objetivo','Define Objetivos cualitativos ambiciosos con 3-5 Key Results cuantitativos medibles (escala 0.0-1.0). Operan en ciclos trimestrales.','💡 Ejemplo','Objetivo: "Revolucionar la experiencia del paciente". KR1: "NPS de 45 a 75" (0.7). KR2: "Tiempo de espera de 45min a 15min" (0.6). KR3: "Quejas reducidas 50%" (0.5).','📝 Qué hacer','Define 3-5 objetivos. Para cada uno, 3-5 KRs. Cada KR debe ser medible y ambicioso. Puntúa avance 0.0-1.0 en check-ins semanales.'],
    'scenarios' => ['🎯 Objetivo','Construye 2-4 escenarios futuros basados en las 2 incertidumbres más críticas. Diseña estrategias que funcionen en MÚLTIPLES escenarios (robustas).','💡 Ejemplo','Eje X: Regulación (Estable ↔ Restrictiva). Eje Y: Financiamiento (Abundante ↔ Escaso). 4 escenarios: "Crecimiento Regulado", "Austeridad Controlada", "Boom Liberalizado", "Crisis Sistémica".','📝 Qué hacer','Selecciona las 2 incertidumbres más impactantes. Cruza en matriz 2×2. Escribe una narrativa para cada escenario. Diseña estrategias que sirvan en al menos 3 de los 4 escenarios.'],
    'vision' => ['🎯 Objetivo','Define la Misión (propósito actual), Visión (futuro deseado a 3-5 años) y Valores organizacionales. Son la base de toda la estrategia.','💡 Ejemplo','Misión: "Brindar servicios de salud integrales, humanizados y seguros". Visión: "Ser referente nacional en excelencia clínica para 2028". Valores: Excelencia, Humanización, Innovación, Transparencia.','📝 Qué hacer','Misión: ¿qué hacemos, para quién y cómo? (1-2 frases). Visión: ¿dónde queremos estar en 3-5 años? (aspiracional pero alcanzable). Valores: 4-6 principios no negociables.'],
    'generic' => ['🎯 Objetivo','Trabaja en esta fase siguiendo los pasos guiados del panel derecho. Cada paso es un entregable concreto.','💡 Ejemplo','Revisa los pasos enumerados en el panel lateral. Cada uno tiene un objetivo específico.','📝 Qué hacer','Completa cada paso en orden. Puedes usar el asistente IA para generar borradores. Guarda tu avance frecuentemente.'],
];
$guia = $guiasFase[$tool] ?? $guiasFase['generic'];
?>
<div class="alert alert-info border-start border-4 border-info mb-4" id="guiaFase" style="font-size:0.9rem">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong><i class="fas fa-info-circle me-2"></i><?= $guia[0] ?></strong>
        <button class="btn btn-sm text-muted" onclick="document.getElementById('guiaBody').classList.toggle('d-none')" title="Mostrar/ocultar guía"><i class="fas fa-chevron-up" id="guiaToggle"></i></button>
    </div>
    <div id="guiaBody">
        <p class="mb-2"><?= $guia[1] ?></p>
        <p class="mb-1"><strong><?= $guia[2] ?></strong></p>
        <p class="mb-2 text-muted"><?= $guia[3] ?></p>
        <p class="mb-0"><strong><?= $guia[4] ?></strong></p>
        <p class="mb-0"><?= $guia[5] ?></p>
    </div>
</div>

<!-- Checklist de progreso dinámico -->
<div class="alert alert-light border mb-3 small" id="progressChecklist" style="display:none;font-size:0.85rem">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong><i class="fas fa-clipboard-list me-1"></i>Progreso: <span id="checkCount">0</span>/<span id="checkTotal">4</span></strong>
            <div class="d-flex gap-1 mt-1 flex-wrap" id="checkItems"></div>
        </div>
        <button class="btn btn-sm btn-success" id="btnCompletePhase" style="display:none" onclick="completarFase(<?= $planId ?>, <?= $faseId ?>)"><i class="fas fa-check me-1"></i>Completar fase</button>
    </div>
</div>

<?php if (!$bloquearAcceso): ?>
<div class="card-box">
    <div class="card-box-body p-0">
        <?php
        $toolFile = BASE_PATH . '/templates/tools/' . $tool . '_builder.php';
        if (file_exists($toolFile)) {
            require $toolFile;
        } else {
            echo '<div class="p-4 text-center text-muted"><i class="fas fa-tools" style="font-size:3rem;color:#ccc;display:block;margin-bottom:16px"></i><h5>Herramienta en desarrollo</h5></div>';
        }
        ?>
    </div>
</div>
<?php if (!in_array($fase['fase_estado'] ?? '', ['completada', 'aprobada'])): ?>
<div class="text-center mt-3" id="bottomComplete">
    <button class="btn btn-success btn-lg" id="btnCompletarBottom" disabled onclick="completarFase(<?= $planId ?>, <?= $faseId ?>)"><i class="fas fa-check-circle me-2"></i>Completar Fase y Avanzar</button>
    <p class="small text-muted mt-1">Al completar esta fase se desbloqueará la siguiente automáticamente.</p>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
async function completarFase(planId, faseId) {
    if (!confirm('¿Marcar esta fase como completada y pasar a la siguiente?')) return;
    try {
        const resp = await fetch('/tools/completar-fase', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`plan_id=${planId}&fase_id=${faseId}`, credentials:'same-origin' });
        if (resp.ok) { 
            const r = await resp.json(); 
            alert('✅ Fase completada. Avance del plan: ' + (r.avance||'?') + '%');
            location.href = '/planeacion/' + planId;
        } else { 
            alert('❌ Error al completar. Código: ' + resp.status + '. Intenta de nuevo.'); 
        }
    } catch(e) { 
        alert('❌ Error de conexión. Verifica tu red.'); 
    }
}

// Activar botones cuando haya contenido suficiente — validación por tipo de herramienta
function hasValidContent() {
    var toolArea = document.querySelector('.card-box-body');
    if (!toolArea) return false;
    // FODA: al menos 2 cuadrantes con items llenos
    var fodaItems = toolArea.querySelectorAll('.foda-item');
    if (fodaItems.length > 0) {
        var filledInZones = {};
        fodaItems.forEach(function(el) { if (el.value && el.value.trim()) filledInZones[el.dataset.zone] = true; });
        return Object.keys(filledInZones).length >= 2;
    }
    // BSC: al menos un nodo de objetivo
    if (toolArea.querySelector('.bsc-node')) return true;
    // PESTEL: al menos un input con valor
    var pestelInputs = toolArea.querySelectorAll('.pestel-item');
    if (pestelInputs.length > 0) {
        for (var i = 0; i < pestelInputs.length; i++) { if (pestelInputs[i].value && pestelInputs[i].value.trim()) return true; }
        return false;
    }
    // Generic/paso-a-paso: al menos un textarea con contenido
    var pasoTextareas = toolArea.querySelectorAll('.paso-contenido');
    if (pasoTextareas.length > 0) {
        for (var j = 0; j < pasoTextareas.length; j++) { if (pasoTextareas[j].value && pasoTextareas[j].value.trim()) return true; }
        return false;
    }
    // General: cualquier input/textarea con valor
    var inputs = toolArea.querySelectorAll('input[type=text], input[type=number], textarea');
    for (var k = 0; k < inputs.length; k++) { if (inputs[k].value && inputs[k].value.trim()) return true; }
    // Tablas con filas
    if (toolArea.querySelectorAll('table tbody tr').length > 0) return true;
    // Texto visible significativo
    var text = toolArea.textContent.replace(/\s+/g, ' ').trim();
    return text.length > 40;
}

function updateCompleteButtons() {
    var valid = hasValidContent();
    var btn = document.getElementById('btnCompletar');
    var btnBottom = document.getElementById('btnCompletarBottom');
    var btnChecklist = document.getElementById('btnCompletePhase');
    [btn, btnBottom, btnChecklist].forEach(function(b) {
        if (!b) return;
        if (valid) {
            b.disabled = false;
            b.title = '';
            b.style.display = '';
            if (b.id === 'btnCompletarBottom') b.className = 'btn btn-success btn-lg';
            else if (b.id === 'btnCompletePhase') b.className = 'btn btn-sm btn-success';
            else b.className = 'btn btn-success';
        } else {
            b.disabled = true;
            b.title = 'Completa los campos requeridos antes de finalizar';
        }
    });
    // Actualizar checklist si existe
    var checkCount = document.getElementById('checkCount');
    var checkTotal = document.getElementById('checkTotal');
    if (checkCount && checkTotal) {
        var total = parseInt(checkTotal.textContent) || 4;
        checkCount.textContent = valid ? total : 0;
        var checklist = document.getElementById('progressChecklist');
        if (checklist) checklist.style.display = 'block';
    }
}
document.addEventListener('DOMContentLoaded', updateCompleteButtons);
document.addEventListener('input', updateCompleteButtons);
document.addEventListener('change', updateCompleteButtons);
setTimeout(updateCompleteButtons, 500);
setTimeout(updateCompleteButtons, 1500);
</script>
