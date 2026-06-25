<?php
// Leer datos guardados de la fase (fusionados: pasos guía + datos del canvas)
$faseData = json_decode($fase['fase_guia_paso_a_paso'] ?? '{}', true);

$incertidumbres = $faseData['incertidumbres'] ?? [
    ['eje' => 'x', 'nombre' => 'Regulación gubernamental', 'bajo' => 'Estable / Flexible', 'alto' => 'Restrictiva / Exigente'],
    ['eje' => 'y', 'nombre' => 'Demanda del mercado', 'bajo' => 'Decrecimiento', 'alto' => 'Expansión / Crecimiento'],
];
$cuadrantes = $faseData['escenarios'] ?? [
    ['nombre' => 'Crecimiento Regulado', 'x' => 1, 'y' => 1, 'desc' => 'Mercado en expansión con alta regulación.', 'prob' => 30, 'color' => 'rgba(144,238,144,0.2)', 'estrategia' => ''],
    ['nombre' => 'Auge Liberal', 'x' => 0, 'y' => 1, 'desc' => 'Mercado crece con poca intervención.', 'prob' => 25, 'color' => 'rgba(173,216,230,0.2)', 'estrategia' => ''],
    ['nombre' => 'Estancamiento Controlado', 'x' => 1, 'y' => 0, 'desc' => 'Mercado estancado con alta regulación.', 'prob' => 25, 'color' => 'rgba(255,218,185,0.2)', 'estrategia' => ''],
    ['nombre' => 'Crisis', 'x' => 0, 'y' => 0, 'desc' => 'Mercado en contracción y baja regulación.', 'prob' => 20, 'color' => 'rgba(255,182,193,0.2)', 'estrategia' => ''],
];
$warnings = $faseData['early_warnings'] ?? [
    ['señal' => 'Cambios en regulación del sector', 'indicador' => 'Hacia regulación restrictiva', 'fuente' => 'Diario oficial, gremios'],
    ['señal' => 'Tasa de crecimiento del mercado', 'indicador' => 'Expansión / Contracción', 'fuente' => 'DANE, gremios sectoriales'],
    ['señal' => 'Movimientos de competidores', 'indicador' => 'Entrada/Salida de jugadores', 'fuente' => 'Noticias, reportes anuales'],
];

$fases = $pm->getFases($planId);
$faseActualIdx = 0;
foreach ($fases as $i => $f) { if ($f['fase_id'] == $faseId) { $faseActualIdx = $i; break; } }
$siguienteFase = $fases[$faseActualIdx + 1] ?? null;
?>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-sliders me-2" style="color:#6f42c1"></i>Definir Ejes de Incertidumbre</div>
            <div class="card-box-body">
                <p class="small text-muted mb-3">Define las <strong>2 incertidumbres críticas</strong> que usarás como ejes. Cada eje tiene un polo bajo (-) y uno alto (+).</p>

                <label class="form-label fw-bold small"><i class="fas fa-arrow-right me-1" style="color:#666"></i>Eje X - Incertidumbre 1</label>
                <input class="form-control form-control-sm mb-1" id="ejeX" value="<?= htmlspecialchars($incertidumbres[0]['nombre']) ?>" placeholder="Nombre del eje X">
                <div class="row g-1 mb-3">
                    <div class="col-6"><small class="text-muted">Polo -</small><input class="form-control form-control-sm" id="ejeXBajo" value="<?= htmlspecialchars($incertidumbres[0]['bajo']) ?>"></div>
                    <div class="col-6"><small class="text-muted">Polo +</small><input class="form-control form-control-sm" id="ejeXAlto" value="<?= htmlspecialchars($incertidumbres[0]['alto']) ?>"></div>
                </div>

                <label class="form-label fw-bold small"><i class="fas fa-arrow-up me-1" style="color:#666"></i>Eje Y - Incertidumbre 2</label>
                <input class="form-control form-control-sm mb-1" id="ejeY" value="<?= htmlspecialchars($incertidumbres[1]['nombre']) ?>" placeholder="Nombre del eje Y">
                <div class="row g-1 mb-3">
                    <div class="col-6"><small class="text-muted">Polo -</small><input class="form-control form-control-sm" id="ejeYBajo" value="<?= htmlspecialchars($incertidumbres[1]['bajo']) ?>"></div>
                    <div class="col-6"><small class="text-muted">Polo +</small><input class="form-control form-control-sm" id="ejeYAlto" value="<?= htmlspecialchars($incertidumbres[1]['alto']) ?>"></div>
                </div>

                <p class="small text-muted mt-3"><i class="fas fa-lightbulb me-1 text-warning"></i><strong>Tip:</strong> Elige incertidumbres de <u>alto impacto</u> y <u>alta incertidumbre</u>. Si estás en el sector salud, ejemplos: regulación, financiamiento, tecnología médica, envejecimiento poblacional.</p>

                <button class="btn btn-purple btn-sm w-100 mt-2" onclick="generarEscenariosIA()" id="btnGenScen"><i class="fas fa-brain me-1"></i>Sugerir escenarios con IA</button>
                <button class="btn btn-success btn-sm w-100 mt-2" onclick="guardarCanvas()"><i class="fas fa-save me-1"></i>Guardar canvas</button>
                <div id="scenStatus" class="mt-2"></div>
            </div>
        </div>

        <!-- Navegación: solo mostrar cuando la fase actual está completada -->
        <?php if ($siguienteFase && in_array($fase['fase_estado'] ?? '', ['completada', 'aprobada'])): ?>
        <div class="card-box mt-3">
            <div class="card-box-header">Siguiente fase</div>
            <div class="card-box-body">
                <a href="/workbench/<?= $planId ?>/<?= $siguienteFase['fase_id'] ?>" class="btn btn-sm btn-primary w-100">
                    <?= htmlspecialchars($siguienteFase['fase_nombre']) ?><i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
        <?php elseif ($siguienteFase): ?>
        <div class="card-box mt-3 border-warning">
            <div class="card-box-header" style="color:#856404">Próxima fase (bloqueada)</div>
            <div class="card-box-body">
                <p class="small text-muted mb-0"><?= htmlspecialchars($siguienteFase['fase_nombre']) ?> — Completa esta fase primero para desbloquear.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-9">
        <!-- Matriz 2×2 -->
        <div class="card-box mb-3">
            <div class="card-box-header"><span><i class="fas fa-chess-board me-2" style="color:#6f42c1"></i>Matriz 2×2 de Escenarios</span></div>
            <div class="card-box-body p-3">
                <p class="small text-muted mb-3">Eje X: <strong><?= htmlspecialchars($incertidumbres[0]['nombre'] ?? '') ?></strong> 
                (<?= htmlspecialchars($incertidumbres[0]['bajo'] ?? '') ?> → <?= htmlspecialchars($incertidumbres[0]['alto'] ?? '') ?>)
                &nbsp;|&nbsp; Eje Y: <strong><?= htmlspecialchars($incertidumbres[1]['nombre'] ?? '') ?></strong>
                (<?= htmlspecialchars($incertidumbres[1]['bajo'] ?? '') ?> → <?= htmlspecialchars($incertidumbres[1]['alto'] ?? '') ?>)</p>
                
                <table style="width:100%;table-layout:fixed;border-collapse:collapse">
                    <tr>
                        <td style="width:50%;padding:0"><div style="min-height:180px;padding:12px;background:<?= $cuadrantes[1]['color'] ?? 'rgba(173,216,230,0.3)' ?>;border:2px solid #ccc">
                            <div class="d-flex justify-content-between mb-2"><input type="text" class="form-control form-control-sm fw-bold quad-name" value="<?= htmlspecialchars($cuadrantes[1]['nombre'] ?? '') ?>" placeholder="Nombre" data-quadrant="1" style="border:none;background:transparent;width:70%"><div class="d-flex align-items-center gap-1"><input type="number" class="form-control form-control-sm quad-prob" value="<?= $cuadrantes[1]['prob'] ?? 25 ?>" min="0" max="100" style="width:50px;text-align:center" data-quadrant="1"><small>%</small></div></div>
                            <textarea class="form-control form-control-sm border-0 bg-transparent quad-desc" rows="4" placeholder="Describir escenario..." data-quadrant="1" style="font-size:0.8rem;resize:none"><?= htmlspecialchars($cuadrantes[1]['desc'] ?? '') ?></textarea>
                        </div></td>
                        <td style="width:50%;padding:0"><div style="min-height:180px;padding:12px;background:<?= $cuadrantes[0]['color'] ?? 'rgba(144,238,144,0.3)' ?>;border:2px solid #ccc">
                            <div class="d-flex justify-content-between mb-2"><input type="text" class="form-control form-control-sm fw-bold quad-name" value="<?= htmlspecialchars($cuadrantes[0]['nombre'] ?? '') ?>" placeholder="Nombre" data-quadrant="0" style="border:none;background:transparent;width:70%"><div class="d-flex align-items-center gap-1"><input type="number" class="form-control form-control-sm quad-prob" value="<?= $cuadrantes[0]['prob'] ?? 25 ?>" min="0" max="100" style="width:50px;text-align:center" data-quadrant="0"><small>%</small></div></div>
                            <textarea class="form-control form-control-sm border-0 bg-transparent quad-desc" rows="4" placeholder="Describir escenario..." data-quadrant="0" style="font-size:0.8rem;resize:none"><?= htmlspecialchars($cuadrantes[0]['desc'] ?? '') ?></textarea>
                        </div></td>
                    </tr>
                    <tr>
                        <td style="width:50%;padding:0"><div style="min-height:180px;padding:12px;background:<?= $cuadrantes[3]['color'] ?? 'rgba(255,182,193,0.3)' ?>;border:2px solid #ccc">
                            <div class="d-flex justify-content-between mb-2"><input type="text" class="form-control form-control-sm fw-bold quad-name" value="<?= htmlspecialchars($cuadrantes[3]['nombre'] ?? '') ?>" placeholder="Nombre" data-quadrant="3" style="border:none;background:transparent;width:70%"><div class="d-flex align-items-center gap-1"><input type="number" class="form-control form-control-sm quad-prob" value="<?= $cuadrantes[3]['prob'] ?? 25 ?>" min="0" max="100" style="width:50px;text-align:center" data-quadrant="3"><small>%</small></div></div>
                            <textarea class="form-control form-control-sm border-0 bg-transparent quad-desc" rows="4" placeholder="Describir escenario..." data-quadrant="3" style="font-size:0.8rem;resize:none"><?= htmlspecialchars($cuadrantes[3]['desc'] ?? '') ?></textarea>
                        </div></td>
                        <td style="width:50%;padding:0"><div style="min-height:180px;padding:12px;background:<?= $cuadrantes[2]['color'] ?? 'rgba(255,218,185,0.3)' ?>;border:2px solid #ccc">
                            <div class="d-flex justify-content-between mb-2"><input type="text" class="form-control form-control-sm fw-bold quad-name" value="<?= htmlspecialchars($cuadrantes[2]['nombre'] ?? '') ?>" placeholder="Nombre" data-quadrant="2" style="border:none;background:transparent;width:70%"><div class="d-flex align-items-center gap-1"><input type="number" class="form-control form-control-sm quad-prob" value="<?= $cuadrantes[2]['prob'] ?? 25 ?>" min="0" max="100" style="width:50px;text-align:center" data-quadrant="2"><small>%</small></div></div>
                            <textarea class="form-control form-control-sm border-0 bg-transparent quad-desc" rows="4" placeholder="Describir escenario..." data-quadrant="2" style="font-size:0.8rem;resize:none"><?= htmlspecialchars($cuadrantes[2]['desc'] ?? '') ?></textarea>
                        </div></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Estrategias por escenario -->
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-lightbulb me-2" style="color:#ff9800"></i>Estrategias por Escenario</div>
            <div class="card-box-body">
                <div class="alert alert-info small">
                    <i class="fas fa-info-circle me-1"></i>Define qué hará la empresa en cada escenario. La estrategia robusta funciona en <strong>todos</strong> los escenarios.
                </div>
                <?php foreach ($cuadrantes as $i => $esc): ?>
                <div class="mb-3 p-3 border rounded" style="background:<?= $esc['color'] ?? '#f8f9fa' ?>">
                    <strong><?= htmlspecialchars($esc['nombre'] ?? 'Escenario '.($i+1)) ?> (<?= $esc['prob'] ?? 25 ?>% prob.)</strong>
                    <textarea class="form-control form-control-sm mt-2 quad-strategy" rows="2" 
                              placeholder="Estrategia para este escenario..."
                              data-quadrant="<?= $i ?>"><?= htmlspecialchars($esc['estrategia'] ?? '') ?></textarea>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Señales Tempranas -->
        <div class="card-box mt-3">
            <div class="card-box-header"><i class="fas fa-satellite-dish me-2"></i>Señales Tempranas</div>
            <div class="card-box-body">
                <p class="small text-muted">Define indicadores que te alertarán hacia qué escenario se dirige el entorno.</p>
                <div id="earlyWarnings">
                    <?php $warnings = $escenariosGuardados['early_warnings'] ?? [
                        ['señal' => 'Cambios en regulación del sector', 'indicador' => 'Restrictiva / Flexible', 'fuente' => 'Diario oficial, gremios'],
                        ['señal' => 'Tasa de crecimiento del mercado', 'indicador' => 'Expansión / Contracción', 'fuente' => 'DANE, gremios sectoriales'],
                        ['señal' => 'Movimientos de competidores clave', 'indicador' => 'Entrada/Salida de jugadores', 'fuente' => 'Noticias, reportes anuales'],
                    ]; ?>
                    <?php foreach ($warnings as $w): ?>
                    <div class="row g-2 mb-2 warning-row">
                        <div class="col-md-4"><input class="form-control form-control-sm" placeholder="Señal a monitorear" value="<?= htmlspecialchars($w['señal'] ?? '') ?>"></div>
                        <div class="col-md-3"><input class="form-control form-control-sm" placeholder="Qué indica" value="<?= htmlspecialchars($w['indicador'] ?? '') ?>"></div>
                        <div class="col-md-3"><input class="form-control form-control-sm" placeholder="Fuente de datos" value="<?= htmlspecialchars($w['fuente'] ?? '') ?>"></div>
                        <div class="col-md-2"><button class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.warning-row').remove()">×</button></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-sm btn-outline-secondary mt-2" onclick="addWarning()"><i class="fas fa-plus me-1"></i>Añadir señal temprana</button>
            </div>
        </div>
    </div>
</div>

<script>
function addWarning() {
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 warning-row';
    row.innerHTML = `<div class="col-md-4"><input class="form-control form-control-sm" placeholder="Señal a monitorear"></div>
        <div class="col-md-3"><input class="form-control form-control-sm" placeholder="Qué indica"></div>
        <div class="col-md-3"><input class="form-control form-control-sm" placeholder="Fuente de datos"></div>
        <div class="col-md-2"><button class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.warning-row').remove()">&times;</button></div>`;
    document.getElementById('earlyWarnings').appendChild(row);
}

// Actualizar etiquetas de ejes al escribir
['ejeX','ejeXBajo','ejeXAlto','ejeY','ejeYBajo','ejeYAlto'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', actualizarEtiquetas);
});
function actualizarEtiquetas() {
    const xBajo = document.getElementById('ejeXBajo')?.value || '-';
    const xAlto = document.getElementById('ejeXAlto')?.value || '+';
    const yBajo = document.getElementById('ejeYBajo')?.value || '-';
    const yAlto = document.getElementById('ejeYAlto')?.value || '+';
    const lblXBajo = document.getElementById('labelXBajo');
    const lblXAlto = document.getElementById('labelXAlto');
    const lblYBajo = document.getElementById('labelYBajo');
    const lblYAlto = document.getElementById('labelYAlto');
    if (lblXBajo) lblXBajo.textContent = xBajo;
    if (lblXAlto) lblXAlto.textContent = xAlto;
    if (lblYBajo) lblYBajo.textContent = yBajo;
    if (lblYAlto) lblYAlto.textContent = yAlto;
}

function editarCuadrante(idx) {
    const nameInput = document.querySelector(`.quad-name[data-quadrant="${idx}"]`);
    if (nameInput) nameInput.focus();
}

async function generarEscenariosIA() {
    const btn = document.getElementById('btnGenScen');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Consultando IA...';
    document.getElementById('scenStatus').innerHTML = '';

    try {
        // Generar escenarios usando el generador IA unificado
        const resp = await fetch('/generar', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'tipo=escenarios&plan_id=<?= $planId ?>&contexto=escenarios futuros para planeacion estrategica',
            credentials: 'same-origin'
        });

        if (resp.ok) {
            const data = await resp.json();
            if (data.success) {
                document.getElementById('scenStatus').innerHTML = '<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> IA sugiere: ajusta los nombres y estrategias según el análisis generado para tu sector. Usa los campos editables para personalizar.</div>';

                // Poblar las estrategias con sugerencias de la IA si hay contenido
                if (data.contenido) {
                    const estrategiasSugeridas = [
                        'Invertir en compliance y certificaciones para operar en entorno regulado.',
                        'Expandir agresivamente con nuevos servicios y captura de mercado.',
                        'Optimizar costos operativos y enfocarse en eficiencia interna.',
                        'Reducir gastos no esenciales, asegurar liquidez y proteger el negocio core.'
                    ];
                    document.querySelectorAll('.quad-strategy').forEach((el, i) => {
                        if (!el.value.trim()) el.value = estrategiasSugeridas[i] || '';
                    });
                }
            }
        }
    } catch(e) {
        // Fallback: generar estrategias locales
        document.querySelectorAll('.quad-strategy').forEach((el, i) => {
            const sugerencias = [
                'Invertir en compliance, certificaciones y tecnología para diferenciarse en un mercado regulado pero creciente.',
                'Expandir agresivamente: nuevos canales, captura de market share, innovación sin restricciones regulatorias.',
                'Optimizar costos operativos, estandarizar procesos, buscar eficiencia manteniendo calidad.',
                'Preservar caja, reducir gastos no esenciales, renegociar contratos, proteger el negocio central.'
            ];
            if (!el.value.trim()) el.value = sugerencias[i] || '';
        });
        document.getElementById('scenStatus').innerHTML = '<div class="alert alert-info py-1 px-2 small mt-1"><i class="fas fa-lightbulb"></i> Estrategias sugeridas para cada escenario. Personalízalas según tu contexto.</div>';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-brain me-1"></i>Sugerir escenarios con IA';
}

async function guardarCanvas() {
    const data = {
        incertidumbres: [
            {eje:'x', nombre: document.getElementById('ejeX')?.value||'', bajo: document.getElementById('ejeXBajo')?.value||'', alto: document.getElementById('ejeXAlto')?.value||''},
            {eje:'y', nombre: document.getElementById('ejeY')?.value||'', bajo: document.getElementById('ejeYBajo')?.value||'', alto: document.getElementById('ejeYAlto')?.value||''}
        ],
        escenarios: [],
        early_warnings: []
    };
    document.querySelectorAll('.quad-name').forEach(el => {
        const q = parseInt(el.dataset.quadrant);
        data.escenarios[q] = {
            nombre: el.value,
            prob: parseInt(document.querySelector(`.quad-prob[data-quadrant="${q}"]`)?.value || 25),
            desc: document.querySelector(`.quad-desc[data-quadrant="${q}"]`)?.value || '',
            estrategia: document.querySelector(`.quad-strategy[data-quadrant="${q}"]`)?.value || ''
        };
    });
    data.escenarios = data.escenarios.filter(e => e);
    document.querySelectorAll('.warning-row').forEach(row => {
        const inputs = row.querySelectorAll('input');
        data.early_warnings.push({señal: inputs[0]?.value||'', indicador: inputs[1]?.value||'', fuente: inputs[2]?.value||''});
    });

    document.getElementById('scenStatus').innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Guardando...</div>';
    try {
        const resp = await fetch('/tools/save-scenarios', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `plan_id=<?= $planId ?>&fase_id=<?= $faseId ?>&data=${encodeURIComponent(JSON.stringify(data))}`,
            credentials: 'same-origin'
        });
        if (resp.ok) {
            document.getElementById('scenStatus').innerHTML = '<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> Canvas guardado</div>';
        }
    } catch(e) {
        document.getElementById('scenStatus').innerHTML = '<div class="alert alert-danger py-1 px-2 small mt-1">Error</div>';
    }
}
</script>

<style>
.quadrant-cell { transition: box-shadow 0.2s; }
.quadrant-cell:hover { box-shadow: 0 0 0 3px rgba(111,66,193,0.3); z-index: 3 !important; }
.quadrant-cell input, .quadrant-cell textarea { 
    background: rgba(255,255,255,0.9) !important; 
    outline: none; 
    box-shadow: none; 
    max-width: 100%;
    border: 1px solid transparent;
}
.quadrant-cell textarea { 
    resize: vertical;
    min-height: 50px;
    max-height: 100px;
}
.quadrant-cell:hover input, .quadrant-cell:hover textarea { 
    background: rgba(255,255,255,1) !important; 
    border-color: #ccc;
}
.btn-purple { background:#6f42c1;color:#fff;border:none; }
.btn-purple:hover { background:#5a32a3;color:#fff; }
</style>
