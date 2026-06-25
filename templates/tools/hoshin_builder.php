<?php
$nombreFase = mb_strtolower($fase['fase_nombre'] ?? '', 'UTF-8');
$guia = json_decode($fase['fase_guia_paso_a_paso'] ?? '{}', true) ?: [];
$pasos = $guia['pasos'] ?? [];
$objetivosDelPlan = $pm->getObjetivos($planId);

// Detectar fase Hoshin
$faseHK = 'catchball';
if (str_contains($nombreFase, 'control diario')) $faseHK = 'control';
elseif (str_contains($nombreFase, 'revisión del presidente')) $faseHK = 'revision';

$titulos = ['catchball'=>'Despliegue en Catchball — Negociación Vertical','control'=>'Control Diario — Gestión Visual','revision'=>'Revisión del Presidente — Diagnóstico'];
$colores = ['catchball'=>'#ff9800','control'=>'#00bcd4','revision'=>'#6f42c1'];
$iconos = ['catchball'=>'fa-arrows-up-down','control'=>'fa-chart-simple','revision'=>'fa-user-tie'];
$descripciones = [
    'catchball' => 'Proceso iterativo de negociación entre niveles jerárquicos. La dirección propone objetivos anuales y cada nivel los ajusta según su capacidad real, en un diálogo de ida y vuelta hasta lograr consenso.',
    'control' => 'Gestión diaria con tablero visual de indicadores clave. Reuniones breves (15 min) para revisar avance, identificar desviaciones y tomar acciones correctivas inmediatas.',
    'revision' => 'Evaluación periódica de alto nivel donde el presidente/director revisa el avance del plan Hoshin, analiza brechas estratégicas y toma decisiones de ajuste para el siguiente período.',
];
$contenidoGuardado = $guia['contenido_'.$faseHK] ?? $guia['contenido'] ?? '';
?>
<div class="d-flex justify-content-between mb-2">
    <h5><i class="fas <?= $iconos[$faseHK] ?> me-2" style="color:<?= $colores[$faseHK] ?>"></i><?= $titulos[$faseHK] ?></h5>
    <span class="badge" style="background:<?= $colores[$faseHK] ?>;color:#fff">Hoshin Kanri</span>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card-box">
            <div class="card-box-header" style="border-bottom:2px solid <?= $colores[$faseHK] ?>">Guía</div>
            <div class="card-box-body small">
                <p style="font-size:0.75rem"><?= $descripciones[$faseHK] ?></p>
            </div>
        </div>
        <div class="card-box mt-3">
            <div class="card-box-header">Herramientas</div>
            <div class="card-box-body">
                <button type="button" class="btn btn-purple btn-sm w-100 mb-2" onclick="sugerirContenidoHK()">&#129504; Sugerir con IA</button>
                <button type="button" class="btn btn-success btn-sm w-100" onclick="guardarHK()">&#128190; Guardar</button>
                <div id="hkStatus" class="mt-2"></div>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <?php if ($faseHK === 'catchball'): ?>
        <!-- Matriz de Catchball -->
        <div class="card-box mb-3">
            <div class="card-box-header">Matriz de Despliegue Catchball</div>
            <div class="card-box-body p-2">
                <table class="table table-sm small mb-0" style="font-size:0.7rem">
                    <thead><tr><th>Nivel</th><th>Objetivo Hoshin</th><th>Indicador</th><th>Meta</th><th>Medios</th><th>Responsable</th></tr></thead>
                    <tbody id="hk-catchball-tbody">
                        <tr>
                            <td>Dirección</td>
                            <td><input class="form-control form-control-sm hk-obj" placeholder="Objetivo anual"></td>
                            <td><input class="form-control form-control-sm" placeholder="KPI"></td>
                            <td><input class="form-control form-control-sm" style="width:70px" placeholder="Meta"></td>
                            <td><input class="form-control form-control-sm" placeholder="Cómo"></td>
                            <td><input class="form-control form-control-sm" style="width:90px" placeholder="Quién"></td>
                        </tr>
                        <tr>
                            <td>Gerencia</td>
                            <td><input class="form-control form-control-sm" placeholder="Objetivo táctico"></td>
                            <td><input class="form-control form-control-sm" placeholder="KPI"></td>
                            <td><input class="form-control form-control-sm" style="width:70px" placeholder="Meta"></td>
                            <td><input class="form-control form-control-sm" placeholder="Cómo"></td>
                            <td><input class="form-control form-control-sm" style="width:90px" placeholder="Quién"></td>
                        </tr>
                        <tr>
                            <td>Jefatura</td>
                            <td><input class="form-control form-control-sm" placeholder="Objetivo operativo"></td>
                            <td><input class="form-control form-control-sm" placeholder="KPI"></td>
                            <td><input class="form-control form-control-sm" style="width:70px" placeholder="Meta"></td>
                            <td><input class="form-control form-control-sm" placeholder="Cómo"></td>
                            <td><input class="form-control form-control-sm" style="width:90px" placeholder="Quién"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($faseHK === 'control'): ?>
        <!-- Tablero de Control Diario -->
        <div class="card-box mb-3">
            <div class="card-box-header">Tablero de Control Diario</div>
            <div class="card-box-body p-2">
                <table class="table table-sm small mb-0" style="font-size:0.7rem">
                    <thead><tr><th>Indicador</th><th>Actual</th><th>Meta</th><th></th><th>Acción Correctiva</th></tr></thead>
                    <tbody id="hk-control-tbody">
                        <?php for ($i=1; $i<=5; $i++): ?>
                        <tr>
                            <td><input class="form-control form-control-sm" placeholder="Nombre del indicador"></td>
                            <td><input class="form-control form-control-sm" style="width:70px" placeholder="Valor"></td>
                            <td><input class="form-control form-control-sm" style="width:70px" placeholder="Meta"></td>
                            <td><select class="form-select form-select-sm" style="width:80px"><option>Verde</option><option>Amarillo</option><option>Rojo</option></select></td>
                            <td><input class="form-control form-control-sm" placeholder="Qué hacer si se desvía"></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                <div class="mt-2 small">
                    <strong>Reunión diaria (15 min):</strong>
                    <ol class="mb-0 ps-3" style="font-size:0.7rem">
                        <li>¿Qué se logró ayer? <input class="form-control form-control-sm d-inline-block" style="width:300px" placeholder="Logros del día anterior"></li>
                        <li>¿Qué se hará hoy? <input class="form-control form-control-sm d-inline-block" style="width:300px" placeholder="Plan del día"></li>
                        <li>¿Hay bloqueos? <input class="form-control form-control-sm d-inline-block" style="width:300px" placeholder="Bloqueos y acciones"></li>
                    </ol>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Revisión del Presidente -->
        <div class="card-box mb-3">
            <div class="card-box-header">Revisión del Presidente — Diagnóstico Estratégico</div>
            <div class="card-box-body p-2">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small mb-0">Cumplimiento de Hoshin</label>
                        <div class="d-flex gap-2 mb-2">
                            <input class="form-control form-control-sm" style="width:60px" placeholder="OK" value="0" type="number"> <span class="small">en verde</span>
                            <input class="form-control form-control-sm" style="width:60px" placeholder="Alerta" value="0" type="number"> <span class="small">amarillo</span>
                            <input class="form-control form-control-sm" style="width:60px" placeholder="Crítico" value="0" type="number"> <span class="small">rojo</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-0">% Indicadores en meta</label>
                        <input class="form-control form-control-sm mb-2" style="width:100px" placeholder="85" type="number">
                    </div>
                </div>
                <label class="form-label small mb-0">Brechas identificadas</label>
                <table class="table table-sm small mb-2" style="font-size:0.7rem">
                    <thead><tr><th>Brecha</th><th>Causa Raíz</th><th>Acción</th><th>Responsable</th></tr></thead>
                    <tbody id="hk-revision-tbody">
                        <?php for ($i=1; $i<=3; $i++): ?>
                        <tr>
                            <td><input class="form-control form-control-sm" placeholder="Desviación encontrada"></td>
                            <td><input class="form-control form-control-sm" placeholder="¿Por qué ocurrió?"></td>
                            <td><input class="form-control form-control-sm" placeholder="Plan de acción"></td>
                            <td><input class="form-control form-control-sm" style="width:100px" placeholder="Responsable"></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                <label class="form-label small mb-0">Decisiones estratégicas</label>
                <textarea class="form-control form-control-sm" id="hk-decisiones" rows="3" placeholder="Decisiones tomadas en esta revisión..."></textarea>
                <label class="form-label small mb-0 mt-2">Fecha próxima revisión</label>
                <input class="form-control form-control-sm" style="width:200px" type="date" id="hk-fecha-revision">
            </div>
        </div>
        <?php endif; ?>

        <div class="card-box">
            <div class="card-box-header">Notas y Observaciones</div>
            <div class="card-box-body">
                <textarea id="hk-contenido" class="form-control" rows="12" style="font-size:0.8rem" placeholder="Desarrolla aquí el contenido detallado de esta fase. Usa el asistente IA para sugerencias."><?= htmlspecialchars($contenidoGuardado) ?></textarea>
            </div>
        </div>
    </div>
</div>

<script>
async function sugerirContenidoHK() {
    var fase = '<?= $faseHK ?>';
    var ta = document.getElementById('hk-contenido');
    var btn = event.target;
    btn.disabled = true; btn.innerHTML = '&#9203; Generando...';
    document.getElementById('hkStatus').innerHTML = '<div class="text-muted small">Generando...</div>';
    try {
        var r = await fetch('/generar', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'tipo=proceso&plan_id=<?= $planId ?>&contexto='+encodeURIComponent('Fase: '+fase+'. Hoshin Kanri. Genera contenido para la fase de '+fase+' con formato profesional.')});
        var d = await r.json();
        if (d.success && d.contenido) {
            ta.value = d.contenido;
            ta.dispatchEvent(new Event('input', {bubbles: true}));
            if (typeof updateCompleteButtons === 'function') updateCompleteButtons();
            document.getElementById('hkStatus').innerHTML = '<div class="alert alert-success py-1 small">Contenido generado</div>';
        }
    } catch(e) { document.getElementById('hkStatus').innerHTML = '<div class="alert alert-danger py-1 small">Error</div>'; }
    btn.disabled = false; btn.innerHTML = '&#129504; Sugerir con IA';
}

async function guardarHK() {
    var data = {};
    document.querySelectorAll('#hk-catchball-tbody input, #hk-control-tbody input, #hk-control-tbody select, #hk-revision-tbody input, #hk-decisiones, #hk-fecha-revision').forEach(function(el){
        if (el && el.id) data[el.id] = el.value;
        else if (el && el.name) data[el.name] = el.value;
    });
    data['contenido_<?= $faseHK ?>'] = document.getElementById('hk-contenido').value;
    try {
        await fetch('/tools/save-scenarios', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'plan_id=<?= $planId ?>&fase_id=<?= $faseId ?>&data='+encodeURIComponent(JSON.stringify(data))});
        document.getElementById('hkStatus').innerHTML = '<div class="alert alert-success py-1 small">Guardado</div>';
        if (typeof updateCompleteButtons === 'function') updateCompleteButtons();
    } catch(e) { document.getElementById('hkStatus').innerHTML = '<div class="alert alert-danger py-1 small">Error</div>'; }
}
</script>
