<?php
$analisis = $pm->getAnalisisByPlan($planId, 'PESTEL');
$pestelData = $analisis[0]['analisis_contenido'] ?? null;
$pestelData = $pestelData ? json_decode($pestelData, true) : [];
$factores = [
    'politico' => ['Político', '#dc3545', 'Estabilidad, regulaciones, políticas fiscales, subsidios'],
    'economico' => ['Económico', '#28a745', 'Crecimiento, inflación, tasas, empleo, poder adquisitivo'],
    'social' => ['Social', '#007bff', 'Demografía, cultura, educación, tendencias de consumo'],
    'tecnologico' => ['Tecnológico', '#6f42c1', 'Innovación, automatización, I+D, digitalización'],
    'ecologico' => ['Ecológico', '#00bcd4', 'Clima, sostenibilidad, regulación ambiental, energía'],
    'legal' => ['Legal', '#ff9800', 'Leyes laborales, protección datos, normativas sectoriales'],
];

// Siguiente fase de este plan
$fases = $pm->getFases($planId);
$faseActualIdx = 0;
foreach ($fases as $i => $f) { if ($f['fase_id'] == $faseId) { $faseActualIdx = $i; break; } }
$siguienteFase = $fases[$faseActualIdx + 1] ?? null;
$faseAnterior = $fases[$faseActualIdx - 1] ?? null;
?>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-globe me-2" style="color:#007bff"></i>Análisis PESTEL</div>
            <div class="card-box-body">
                <p class="small text-muted">Analiza los 6 factores del macroentorno. La IA generará contenido basado en el sector <strong><?= htmlspecialchars($empresa['sector_nombre']??'General') ?></strong>.</p>
                <button class="btn btn-purple btn-sm w-100 mb-2" onclick="generarPESTEL()" id="btnGenPestel"><i class="fas fa-brain me-1"></i>Generar PESTEL con IA</button>
                <button class="btn btn-success btn-sm w-100" onclick="guardarPESTEL()"><i class="fas fa-save me-1"></i>Guardar Análisis</button>
                <div id="pestelStatus" class="mt-2"></div>
            </div>
        </div>

        <!-- Navegación entre fases -->
        <div class="card-box mt-3">
            <div class="card-box-header">Navegación</div>
            <div class="card-box-body">
                <?php if ($faseAnterior): ?>
                <?php if (in_array($faseAnterior['fase_estado'] ?? '', ['completada', 'aprobada'])): ?>
                <div class="p-2 border rounded text-center small text-success mb-1">✅ <?= htmlspecialchars($faseAnterior['fase_nombre']) ?></div>
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
                <?php else: ?>
                <a href="/planeacion/<?= $planId ?>" class="btn btn-sm btn-success w-100"><i class="fas fa-check me-1"></i>Finalizar fase</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="row g-3">
            <?php foreach ($factores as $key => $f): ?>
            <div class="col-md-6">
                <div class="card" style="border-top: 4px solid <?= $f[1] ?>;border-radius:12px">
                    <div class="card-body p-3">
                        <h6 style="color:<?= $f[1] ?>"><i class="fas fa-circle me-2"></i><?= $f[0] ?></h6>
                        <small class="text-muted d-block mb-2"><?= $f[2] ?></small>
                        <div class="pestel-items" data-factor="<?= $key ?>" style="min-height:80px" id="list_<?= $key ?>">
                            <?php foreach (($pestelData[$key] ?? []) as $i => $item): ?>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text text-white" style="background:<?= $f[1] ?>;font-size:0.6rem;min-width:28px">#<?= $i+1 ?></span>
                                <input type="text" class="form-control form-control-sm pestel-item" data-factor="<?= $key ?>" value="<?= htmlspecialchars(is_array($item)?($item['desc']??$item['factor']??''):$item) ?>" placeholder="Factor <?= strtolower($f[0]) ?>...">
                                <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.input-group').remove()"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary w-100 mt-2" onclick="addPESTELItem(this, '<?= $key ?>', '<?= $f[1] ?>')"><i class="fas fa-plus me-1"></i>Añadir factor <?= strtolower($f[0]) ?></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
const pestelColors = <?= json_encode(array_column($factores, 1, 0)) ?>;

function addPESTELItem(btn, factor, color) {
    const list = btn.closest('.card-body').querySelector('.pestel-items');
    const count = list.querySelectorAll('.pestel-item').length;
    const div = document.createElement('div');
    div.className = 'input-group input-group-sm mb-1';
    div.innerHTML = `<span class="input-group-text text-white" style="background:${color};font-size:0.6rem;min-width:28px">#${count+1}</span>
        <input type="text" class="form-control form-control-sm pestel-item" data-factor="${factor}" placeholder="Nuevo factor...">
        <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.input-group').remove()"><i class="fas fa-times"></i></button>`;
    list.appendChild(div);
    div.querySelector('input').focus();
}

function addPestelItemFromData(list, factor, text, idx, impacto) {
    const count = list.querySelectorAll('.pestel-item').length;
    const color = pestelColors[factor] || '#888';
    const div = document.createElement('div');
    div.className = 'input-group input-group-sm mb-1';
    div.innerHTML = `<span class="input-group-text text-white" style="background:${color};font-size:0.6rem;min-width:28px">#${count+1}</span>
        <input type="text" class="form-control form-control-sm pestel-item" data-factor="${factor}" value="${text.replace(/"/g,'&quot;').replace(/</g,'&lt;')}">
        <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.input-group').remove()"><i class="fas fa-times"></i></button>`;
    list.appendChild(div);
}

async function generarPESTEL() {
    const btn = document.getElementById('btnGenPestel');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generando...';
    document.getElementById('pestelStatus').innerHTML = '<div class="text-muted small">Consultando IA...</div>';

    try {
        const resp = await fetch('/tools/generar-pestel', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'plan_id=<?= $planId ?>',
            credentials: 'same-origin'
        });

        if (!resp.ok) {
            throw new Error('HTTP ' + resp.status);
        }

        const data = await resp.json();

        if (data.success && data.pestel) {
            Object.entries(data.pestel).forEach(([factor, items]) => {
                const list = document.getElementById('list_' + factor);
                if (!list) return;
                list.innerHTML = '';
                items.forEach((item, i) => {
                    const text = typeof item === 'string' ? item : (item.desc || item.factor || '');
                    addPestelItemFromData(list, factor, text, i+1);
                });
            });
            document.getElementById('pestelStatus').innerHTML = '<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> PESTEL generado para sector <?= htmlspecialchars($empresa['sector_nombre']??'General') ?></div>';
        } else {
            document.getElementById('pestelStatus').innerHTML = '<div class="alert alert-danger py-1 px-2 small mt-1">' + (data.message || 'Error') + '</div>';
        }
    } catch(e) {
        document.getElementById('pestelStatus').innerHTML = '<div class="alert alert-danger py-1 px-2 small mt-1">Error: ' + e.message + '</div>';
        console.error('PESTEL error:', e);
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-brain me-1"></i>Generar PESTEL con IA';
}

async function guardarPESTEL() {
    const data = {};
    document.querySelectorAll('.pestel-item').forEach(inp => {
        const factor = inp.dataset.factor;
        const val = inp.value.trim();
        if (val && factor) {
            if (!data[factor]) data[factor] = [];
            data[factor].push(val);
        }
    });

    document.getElementById('pestelStatus').innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Guardando...</div>';

    try {
        await fetch('/tools/save-foda', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'plan_id=<?= $planId ?>&foda_data=' + encodeURIComponent(JSON.stringify(data)),
            credentials: 'same-origin'
        });
        document.getElementById('pestelStatus').innerHTML = '<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> Análisis guardado</div>';
        // Activar botón Completar fase
        var btn = document.getElementById('btnCompletar');
        var btnB = document.getElementById('btnCompletarBottom');
        [btn, btnB].forEach(function(b) { if (b) { b.disabled = false; b.className = b.id === 'btnCompletarBottom' ? 'btn btn-success btn-lg' : 'btn btn-success'; } });
    } catch(e) {
        document.getElementById('pestelStatus').innerHTML = '<div class="alert alert-danger py-1 px-2 small mt-1">Error al guardar</div>';
    }
}
</script>

<style>
.btn-purple { background:#6f42c1;color:#fff;border:none; }
.btn-purple:hover { background:#5a32a3;color:#fff; }
</style>
