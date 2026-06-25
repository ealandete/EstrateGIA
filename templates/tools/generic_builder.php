<?php
// Herramienta genérica: muestra los pasos guiados como tarjetas editables
$pasosGuia = json_decode($fase['fase_guia_paso_a_paso'] ?? '{}', true);
$steps = $pasosGuia['pasos'] ?? [];
$fases = $pm->getFases($planId);
$faseActualIdx = 0;
foreach ($fases as $i => $f) { if ($f['fase_id'] == $faseId) { $faseActualIdx = $i; break; } }
$siguienteFase = $fases[$faseActualIdx + 1] ?? null;
$faseAnterior = $fases[$faseActualIdx - 1] ?? null;
?>
<div class="row g-4">
    <div class="col-md-3">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-tasks me-2" style="color:#1a73e8"></i>Pasos de la Fase</div>
            <div class="card-box-body">
                <p class="small text-muted">Trabaja cada paso en orden. La IA te ayudará a completarlos.</p>
                <button class="btn btn-success btn-sm w-100" onclick="guardarAvance()"><i class="fas fa-save me-1"></i>Guardar avance</button>
                <div id="stepStatus" class="mt-2"></div>
            </div>
        </div>
        <?php if ($faseAnterior || $siguienteFase): ?>
        <div class="card-box mt-3">
            <div class="card-box-header">Navegación</div>
            <div class="card-box-body">
                <?php if ($faseAnterior): ?>
                <?php if (in_array($faseAnterior['fase_estado'] ?? '', ['completada', 'aprobada'])): ?>
                <div class="p-2 border rounded text-center small text-success mb-1">
                    ✅ <?= htmlspecialchars($faseAnterior['fase_nombre']) ?> (completada)
                </div>
                <?php else: ?>
                <a href="/workbench/<?= $planId ?>/<?= $faseAnterior['fase_id'] ?>" class="btn btn-sm btn-outline-secondary w-100 mb-1">
                    <i class="fas fa-arrow-left me-1"></i><?= htmlspecialchars($faseAnterior['fase_nombre']) ?>
                </a>
                <?php endif; ?>
                <?php endif; ?>
                <?php if ($siguienteFase && in_array($fase['fase_estado'] ?? '', ['completada', 'aprobada'])): ?>
                <a href="/workbench/<?= $planId ?>/<?= $siguienteFase['fase_id'] ?>" class="btn btn-sm btn-primary w-100">
                    <?= htmlspecialchars($siguienteFase['fase_nombre']) ?><i class="fas fa-arrow-right ms-1"></i>
                </a>
                <?php elseif ($siguienteFase): ?>
                <div class="p-2 border rounded border-warning text-center small text-muted">
                    <i class="fas fa-lock me-1"></i><?= htmlspecialchars($siguienteFase['fase_nombre']) ?> (bloqueada)
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!$siguienteFase): ?>
        <div class="card-box mt-3 border-success">
            <div class="card-box-body text-center">
                <p class="mb-2"><strong>¡Última fase del plan!</strong></p>
                <p class="text-muted small mb-3">Al completar esta fase, el plan estratégico estará terminado.</p>
                <a href="/planeacion/<?= $planId ?>" class="btn btn-success btn-sm w-100 mb-2">
                    <i class="fas fa-flag-checkered me-1"></i>Ver resumen del plan
                </a>
                <button class="btn btn-outline-success btn-sm w-100" onclick="completarFase(<?= $planId ?>, <?= $faseId ?>)">
                    <i class="fas fa-check-double me-1"></i>Completar y finalizar plan
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-md-9">
        <?php if (empty($steps)): ?>
            <div class="card-box"><div class="card-box-body text-center py-4 text-muted">Esta fase no tiene pasos definidos. Usa el asistente IA para generar contenido.</div></div>
        <?php else: ?>
            <?php foreach ($steps as $idx => $paso): 
                $pasoTitulo = is_array($paso) ? ($paso['titulo'] ?? $paso['descripcion'] ?? '') : $paso;
                $pasoDesc = is_array($paso) ? ($paso['descripcion'] ?? '') : '';
                $pasoContenido = $pasosGuia['contenido_paso_' . ($idx+1)] ?? '';
            ?>
            <div class="card mb-3 step-card" id="step_<?= $idx+1 ?>">
                <div class="card-header bg-white d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:32px;height:32px;min-width:32px;font-size:0.8rem"><?= $idx+1 ?></div>
                    <div>
                        <strong><?= htmlspecialchars($pasoTitulo) ?></strong>
                        <?php if ($pasoDesc): ?><small class="text-muted d-block"><?= htmlspecialchars($pasoDesc) ?></small><?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <textarea class="form-control paso-contenido" data-paso="<?= $idx+1 ?>" rows="5" placeholder="Desarrolla aquí el contenido de este paso... Usa el botón de IA para obtener sugerencias."><?= htmlspecialchars($pasoContenido) ?></textarea>
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn btn-sm btn-outline-purple" onclick="ayudaPaso(<?= $idx+1 ?>)"><i class="fas fa-brain me-1"></i>Sugerir con IA</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
async function pedirAyudaPaso() {
    const primerPaso = document.querySelector('.paso-contenido:focus') || document.querySelector('.paso-contenido');
    if (!primerPaso) return;
    const idx = primerPaso.dataset.paso;
    ayudaPaso(idx);
}

async function ayudaPaso(idx) {
    const ta = document.querySelector(`.paso-contenido[data-paso="${idx}"]`);
    if (!ta) return;
    const pasoActual = ta.closest('.step-card').querySelector('strong')?.textContent || '';
    const contenido = ta.value || pasoActual;
    
    document.getElementById('stepStatus').innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Generando contenido específico para este paso...</div>';
    
    try {
        const resp = await fetch('/generar', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `tipo=proceso&plan_id=<?= $planId ?>&contexto=${encodeURIComponent('Fase: <?= addslashes($fase['fase_nombre']) ?>. Paso '+idx+': '+pasoActual+'. Genera contenido profesional para este paso de planeación estratégica.')}`,
            credentials: 'same-origin'
        });
        
        if (resp.ok) {
            const data = await resp.json();
            if (data.success && data.contenido) {
                ta.value = data.contenido;
                ta.dispatchEvent(new Event('input', {bubbles: true}));
                activarBotonCompletar();
                document.getElementById('stepStatus').innerHTML = '<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> Contenido generado. Puedes editarlo directamente.</div>';
            }
        }
    } catch(e) {
        document.getElementById('stepStatus').innerHTML = '<div class="alert alert-warning py-1 px-2 small mt-1"><i class="fas fa-exclamation-triangle"></i> Intenta de nuevo.</div>';
    }
}

async function guardarAvance() {
    const data = {};
    document.querySelectorAll('.paso-contenido').forEach(ta => {
        data['contenido_paso_' + ta.dataset.paso] = ta.value;
    });
    document.getElementById('stepStatus').innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Guardando...</div>';
    try {
        await fetch('/tools/save-scenarios', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `plan_id=<?= $planId ?>&fase_id=<?= $faseId ?>&data=${encodeURIComponent(JSON.stringify(data))}`,
            credentials: 'same-origin'
        });
        document.getElementById('stepStatus').innerHTML = '<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> Guardado</div>';
        activarBotonCompletar();
    } catch(e) {
        document.getElementById('stepStatus').innerHTML = '<div class="alert alert-danger py-1 px-2 small mt-1">Error</div>';
    }
}

function activarBotonCompletar() {
    var hasContent = false;
    document.querySelectorAll('.paso-contenido').forEach(function(ta) { if (ta.value.trim()) hasContent = true; });
    if (!hasContent) return;
    ['btnCompletar', 'btnCompletarBottom'].forEach(function(id) {
        var b = document.getElementById(id);
        if (b) { b.disabled = false; b.className = b.id === 'btnCompletarBottom' ? 'btn btn-success btn-lg' : 'btn btn-success'; }
    });
    if (typeof updateCompleteButtons === 'function') updateCompleteButtons();
}
</script>
<style>
.btn-outline-purple { color: #6f42c1; border-color: #6f42c1; }
.btn-outline-purple:hover { background: #6f42c1; color: #fff; }
.btn-purple { background:#6f42c1;color:#fff;border:none; }
.btn-purple:hover { background:#5a32a3;color:#fff; }
.step-card .card-header { border-bottom: 1px solid #e0e0e0; }
</style>
