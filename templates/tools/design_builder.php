<?php
$nombreFase = mb_strtolower($fase['fase_nombre'] ?? '', 'UTF-8');
$guia = json_decode($fase['fase_guia_paso_a_paso'] ?? '{}', true) ?: [];
$pasos = $guia['pasos'] ?? [];

// Detectar fase específica
$faseDT = 'empatizar';
if (str_contains($nombreFase, 'definir')) $faseDT = 'definir';
elseif (str_contains($nombreFase, 'idear')) $faseDT = 'idear';
elseif (str_contains($nombreFase, 'prototipar')) $faseDT = 'prototipar';
elseif (str_contains($nombreFase, 'testear')) $faseDT = 'testear';

$faseTitulo = ['empatizar'=>'Empatizar — Investigación','definir'=>'Definir — Problema','idear'=>'Idear — Soluciones','prototipar'=>'Prototipar — MVP','testear'=>'Testear — Validación'];
$faseColor = ['empatizar'=>'#ff9800','definir'=>'#e91e63','idear'=>'#9c27b0','prototipar'=>'#00bcd4','testear'=>'#4caf50'];
$faseIcono = ['empatizar'=>'fa-users','definir'=>'fa-search','idear'=>'fa-lightbulb','prototipar'=>'fa-cubes','testear'=>'fa-flask'];
$faseDesc = [
    'empatizar' => 'Investiga a tus usuarios. Realiza entrevistas, observación y construye mapas de empatía para entender sus necesidades reales.',
    'definir' => 'Sintetiza los hallazgos. Define el problema central, crea un Point of View y formula preguntas generadoras (How Might We).',
    'idear' => 'Genera la mayor cantidad de ideas posibles. Usa brainstorming, brainwriting y otras técnicas de creatividad sin juzgar.',
    'prototipar' => 'Construye versiones rápidas y económicas de tus ideas. Un prototipo puede ser un sketch, wireframe, storyboard o mockup.',
    'testear' => 'Pon tus prototipos frente a usuarios reales. Recoge feedback, mide usabilidad e itera basado en lo aprendido.',
];
$contenidoGuardado = $guia['contenido_'.$faseDT] ?? $guia['contenido'] ?? '';
?>
<div class="d-flex justify-content-between mb-2">
    <h5><i class="fas <?= $faseIcono[$faseDT] ?> me-2" style="color:<?= $faseColor[$faseDT] ?>"></i><?= $faseTitulo[$faseDT] ?></h5>
    <span class="badge" style="background:<?= $faseColor[$faseDT] ?>;color:#fff"><?= $faseDT ?></span>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card-box">
            <div class="card-box-header" style="border-bottom:2px solid <?= $faseColor[$faseDT] ?>">Guía de la Fase</div>
            <div class="card-box-body small">
                <p><?= $faseDesc[$faseDT] ?></p>
                <?php if (!empty($pasos)): ?>
                <strong>Pasos:</strong>
                <ol class="ps-3 mb-0" style="font-size:0.75rem">
                    <?php foreach ($pasos as $p): ?>
                    <li><?= htmlspecialchars(is_array($p)?($p['nombre']??$p['titulo']??''):$p) ?></li>
                    <?php endforeach; ?>
                </ol>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-box mt-3">
            <div class="card-box-header">Herramientas</div>
            <div class="card-box-body">
                <button type="button" class="btn btn-purple btn-sm w-100 mb-2" onclick="sugerirContenidoDT()">&#129504; Sugerir con IA</button>
                <button type="button" class="btn btn-success btn-sm w-100" onclick="guardarDT()">&#128190; Guardar avance</button>
                <div id="dtStatus" class="mt-2"></div>
            </div>
        </div>

        <div class="card-box mt-3">
            <div class="card-box-header">Canvas <?= ucfirst($faseDT) ?></div>
            <div class="card-box-body" style="font-size:0.65rem">
                <?php if ($faseDT === 'empatizar'): ?>
                <table class="table table-sm small mb-0"><tbody>
                    <tr><td class="bg-light fw-bold">¿Qué PIENSA?</td><td><textarea class="form-control form-control-sm" id="dt-piensa" rows="2" placeholder="Preocupaciones, aspiraciones..."></textarea></td></tr>
                    <tr><td class="bg-light fw-bold">¿Qué SIENTE?</td><td><textarea class="form-control form-control-sm" id="dt-siente" rows="2" placeholder="Emociones, miedos, motivaciones..."></textarea></td></tr>
                    <tr><td class="bg-light fw-bold">¿Qué VE?</td><td><textarea class="form-control form-control-sm" id="dt-ve" rows="2" placeholder="Entorno, otros, medios..."></textarea></td></tr>
                    <tr><td class="bg-light fw-bold">¿Qué DICE/HACE?</td><td><textarea class="form-control form-control-sm" id="dt-dice" rows="2" placeholder="Comportamiento público..."></textarea></td></tr>
                    <tr><td class="bg-light fw-bold">¿Qué OYE?</td><td><textarea class="form-control form-control-sm" id="dt-oye" rows="2" placeholder="Amigos, jefe, influencers..."></textarea></td></tr>
                    <tr><td class="bg-light fw-bold">ESFUERZOS</td><td><textarea class="form-control form-control-sm" id="dt-esfuerzos" rows="2" placeholder="Miedos, frustraciones..."></textarea></td></tr>
                    <tr><td class="bg-light fw-bold">RESULTADOS</td><td><textarea class="form-control form-control-sm" id="dt-resultados" rows="2" placeholder="Deseos, necesidades..."></textarea></td></tr>
                </tbody></table>
                <?php elseif ($faseDT === 'definir'): ?>
                <div class="mb-2"><label class="form-label small mb-0">Usuario</label><input class="form-control form-control-sm" id="dt-usuario" placeholder="¿Quién es el usuario?"></div>
                <div class="mb-2"><label class="form-label small mb-0">Necesidad</label><input class="form-control form-control-sm" id="dt-necesidad" placeholder="¿Qué necesita?"></div>
                <div class="mb-2"><label class="form-label small mb-0">Insight</label><textarea class="form-control form-control-sm" id="dt-insight" rows="2" placeholder="¿Por qué? ¿Qué descubriste?"></textarea></div>
                <div class="mb-2"><label class="form-label small mb-0">Preguntas HMW</label><textarea class="form-control form-control-sm" id="dt-hmw" rows="4" placeholder="¿Cómo podríamos...? Una por línea"></textarea></div>
                <?php elseif ($faseDT === 'idear'): ?>
                <div class="mb-2"><label class="form-label small mb-0">Técnica</label><select class="form-select form-select-sm" id="dt-tecnica"><option>Brainstorming</option><option>Brainwriting</option><option>SCAMPER</option><option>Moodboard</option></select></div>
                <div class="mb-2"><label class="form-label small mb-0">Ideas generadas</label><textarea class="form-control form-control-sm" id="dt-ideas" rows="8" placeholder="Una idea por línea..."></textarea></div>
                <div class="mb-2"><label class="form-label small mb-0">Top 3 seleccionadas</label><textarea class="form-control form-control-sm" id="dt-top3" rows="3" placeholder="Las 3 mejores ideas..."></textarea></div>
                <?php elseif ($faseDT === 'prototipar'): ?>
                <div class="mb-2"><label class="form-label small mb-0">Nombre del prototipo</label><input class="form-control form-control-sm" id="dt-nombre-proto" placeholder="MVP de..."></div>
                <div class="mb-2"><label class="form-label small mb-0">Tipo</label><select class="form-select form-select-sm" id="dt-tipo-proto"><option>Wireframe</option><option>Mockup</option><option>Storyboard</option><option>Maqueta física</option><option>Role-play</option></select></div>
                <div class="mb-2"><label class="form-label small mb-0">Funcionalidades MVP</label><textarea class="form-control form-control-sm" id="dt-funcionalidades" rows="4" placeholder="Lista de funcionalidades esenciales..."></textarea></div>
                <div class="mb-2"><label class="form-label small mb-0">Materiales necesarios</label><input class="form-control form-control-sm" id="dt-materiales" placeholder="Figma, papel, cartón..."></div>
                <?php elseif ($faseDT === 'testear'): ?>
                <div class="mb-2"><label class="form-label small mb-0">Usuarios de prueba</label><input class="form-control form-control-sm" id="dt-usuarios-test" placeholder="5 usuarios del segmento objetivo" type="number" min="1" value="5"></div>
                <div class="mb-2"><label class="form-label small mb-0">Tareas a evaluar</label><textarea class="form-control form-control-sm" id="dt-tareas-test" rows="3" placeholder="Una tarea por línea..."></textarea></div>
                <div class="mb-2"><label class="form-label small mb-0">Resultados (SUS score 0-100)</label><input class="form-control form-control-sm" id="dt-sus" placeholder="78" type="number" min="0" max="100"></div>
                <div class="mb-2"><label class="form-label small mb-0">Feedback cualitativo</label><textarea class="form-control form-control-sm" id="dt-feedback" rows="4" placeholder="Comentarios y observaciones..."></textarea></div>
                <div class="mb-2"><label class="form-label small mb-0">Ajustes necesarios</label><textarea class="form-control form-control-sm" id="dt-ajustes" rows="3" placeholder="Cambios para la siguiente iteración..."></textarea></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card-box">
            <div class="card-box-header" style="border-bottom:2px solid <?= $faseColor[$faseDT] ?>">
                <i class="fas <?= $faseIcono[$faseDT] ?> me-2" style="color:<?= $faseColor[$faseDT] ?>"></i>
                <?= $faseTitulo[$faseDT] ?>
            </div>
            <div class="card-box-body">
                <!-- Diagrama de las 5 fases -->
                <div class="d-flex justify-content-between text-center mb-4 small" style="font-size:0.65rem">
                    <?php foreach (['empatizar'=>'Empatizar','definir'=>'Definir','idear'=>'Idear','prototipar'=>'Prototipar','testear'=>'Testear'] as $k => $v): 
                        $activo = $faseDT === $k;
                        $faseIdDT = null;
                        foreach ($fases as $f) if (str_contains(mb_strtolower($f['fase_nombre']??'','UTF-8'), $k)) { $faseIdDT = $f['fase_id']; break; }
                    ?>
                    <div style="flex:1">
                        <a href="<?= $faseIdDT ? '/workbench/'.$planId.'/'.$faseIdDT : '#' ?>" class="text-decoration-none">
                            <div class="rounded-circle mx-auto mb-1 d-flex align-items-center justify-content-center <?= $activo ? 'text-white' : 'bg-light' ?>" style="width:40px;height:40px;font-size:0.7rem;background:<?= $activo ? $faseColor[$k] : '#eee' ?>"><?= $activo ? '●' : '○' ?></div>
                            <span style="color:<?= $activo ? $faseColor[$k] : '#888' ?>"><?= $v ?></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <textarea id="dt-contenido" class="form-control" rows="20" style="font-size:0.8rem" placeholder="Desarrolla aquí el contenido de esta fase. Usa el asistente IA para sugerencias."><?= htmlspecialchars($contenidoGuardado) ?></textarea>
            </div>
        </div>
    </div>
</div>

<script>
async function sugerirContenidoDT() {
    var ta = document.getElementById('dt-contenido');
    var fase = '<?= $faseDT ?>';
    var paso = '<?= $pasos[0] ?? '' ?>';
    var btn = event.target;
    btn.disabled = true; btn.innerHTML = '&#9203; Generando...';
    document.getElementById('dtStatus').innerHTML = '<div class="text-muted small">Generando...</div>';
    try {
        var r = await fetch('/generar', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'tipo=proceso&plan_id=<?= $planId ?>&contexto='+encodeURIComponent('Fase: '+fase+'. Paso: '+paso+'. Design Thinking. Genera contenido para la fase de '+fase)});
        var d = await r.json();
        if (d.success && d.contenido) {
            ta.value = d.contenido;
            ta.dispatchEvent(new Event('input', {bubbles: true}));
            if (typeof updateCompleteButtons === 'function') updateCompleteButtons();
            document.getElementById('dtStatus').innerHTML = '<div class="alert alert-success py-1 small">Contenido generado</div>';
        }
    } catch(e) { document.getElementById('dtStatus').innerHTML = '<div class="alert alert-danger py-1 small">Error</div>'; }
    btn.disabled = false; btn.innerHTML = '&#129504; Sugerir con IA';
}

async function guardarDT() {
    var canvas = {};
    document.querySelectorAll('#dt-piensa, #dt-siente, #dt-ve, #dt-dice, #dt-oye, #dt-esfuerzos, #dt-resultados, #dt-usuario, #dt-necesidad, #dt-insight, #dt-hmw, #dt-ideas, #dt-top3, #dt-nombre-proto, #dt-tipo-proto, #dt-funcionalidades, #dt-materiales, #dt-usuarios-test, #dt-tareas-test, #dt-sus, #dt-feedback, #dt-ajustes, #dt-tecnica').forEach(function(el){
        if (el) canvas[el.id.replace('dt-','')] = el.value || el.options?.[el.selectedIndex]?.value || '';
    });
    canvas['contenido_<?= $faseDT ?>'] = document.getElementById('dt-contenido').value;
    try {
        await fetch('/tools/save-scenarios', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'plan_id=<?= $planId ?>&fase_id=<?= $faseId ?>&data='+encodeURIComponent(JSON.stringify(canvas))});
        document.getElementById('dtStatus').innerHTML = '<div class="alert alert-success py-1 small">Guardado</div>';
        if (typeof updateCompleteButtons === 'function') updateCompleteButtons();
    } catch(e) { document.getElementById('dtStatus').innerHTML = '<div class="alert alert-danger py-1 small">Error</div>'; }
}
</script>
