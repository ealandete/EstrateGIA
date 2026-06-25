<?php
$objetivosExistentes = $pm->getObjetivos($planId);
$nombreFase = mb_strtolower($fase['fase_nombre'] ?? '', 'UTF-8');
$esCheckin = str_contains($nombreFase, 'check') || str_contains($nombreFase, 'ejecución');
$esCierre = str_contains($nombreFase, 'cierre') || str_contains($nombreFase, 'retrospectiva');
$guia = json_decode($fase['fase_guia_paso_a_paso'] ?? '{}', true) ?: [];
$checkinsGuardados = $guia['checkins'] ?? [];
?>
<?php if ($esCheckin): ?>
<!-- Check-in Semanal -->
<div class="alert alert-info border-start border-4 border-info mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong><i class="fas fa-clipboard-check me-2"></i>Check-in Semanal de OKRs</strong>
        <button type="button" class="btn btn-sm btn-outline-info" onclick="addCheckin()">+ Nuevo check-in</button>
    </div>
    <div id="hk-checkins" style="font-size:0.75rem">
        <?php foreach ($checkinsGuardados as $ci): ?>
        <div class="p-2 mb-1 bg-white rounded border small">
            <strong><?= htmlspecialchars($ci['fecha'] ?? '') ?></strong>
            <?php foreach (($ci['krs'] ?? []) as $kr): ?>
            <div class="d-flex gap-2 align-items-center ms-2">
                <span><?= htmlspecialchars($kr['nombre'] ?? '') ?></span>
                <span class="badge bg-<?= ($kr['confianza']??'')==='on_track'?'success':(($kr['confianza']??'')==='at_risk'?'warning':'danger') ?>"><?= htmlspecialchars($kr['confianza'] ?? 'on_track') ?></span>
                <span><?= ($kr['avance']??0)*100 ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($esCierre): ?>
<!-- Scoring Final -->
<div class="card-box mb-3" style="border-left:4px solid #28a745">
    <div class="card-box-header"><i class="fas fa-trophy me-2" style="color:#28a745"></i>Scoring Final de OKRs</div>
    <div class="card-box-body p-2">
        <table class="table table-sm small mb-0" style="font-size:0.75rem">
            <thead><tr><th>Objetivo</th><th>Avg KR Score</th><th>Peso</th><th>Ponderado</th><th>Resultado</th></tr></thead>
            <tbody>
            <?php foreach ($objetivosExistentes as $obj): 
                $krs = json_decode($obj['objetivo_descripcion'] ?? '[]', true) ?: [];
                $scores = array_column($krs, 'score');
                $avgScore = !empty($scores) ? array_sum($scores) / count($scores) : 0;
                $peso = ($obj['objetivo_peso_relativo'] ?? 25) / 100;
                $pond = round($avgScore * $peso * 100);
            ?>
            <tr>
                <td><?= htmlspecialchars(substr($obj['objetivo_nombre'],0,35)) ?></td>
                <td><?= number_format($avgScore, 2) ?></td>
                <td><?= $obj['objetivo_peso_relativo'] ?? 25 ?>%</td>
                <td><?= $pond ?>%</td>
                <td><span class="badge bg-<?= $avgScore>=0.8?'success':($avgScore>=0.5?'warning':'danger') ?>"><?= $avgScore>=0.8?'Excedido':($avgScore>=0.6?'Cumplido':($avgScore>=0.3?'Parcial':'Replanificar')) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mt-2 small">
            <strong>Retrospectiva:</strong>
            <textarea class="form-control form-control-sm mt-1" id="okr-retro" rows="3" placeholder="¿Qué funcionó bien? ¿Qué mejorar? Lecciones aprendidas..."><?= htmlspecialchars($guia['retrospectiva'] ?? '') ?></textarea>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-bullseye me-2" style="color:#1a73e8"></i>OKR Builder</div>
            <div class="card-box-body">
                <p class="small text-muted">Define 3-5 objetivos ambiciosos con sus Key Results medibles. Puntúa cada KR de 0.0 a 1.0.</p>
                <button class="btn btn-purple btn-sm w-100 mb-2" onclick="generarOKR()"><i class="fas fa-brain me-1"></i>Sugerir OKRs con IA</button>
                <button class="btn btn-primary btn-sm w-100 mb-2" onclick="addOKR()"><i class="fas fa-plus me-1"></i>Nuevo Objetivo</button>
                <button class="btn btn-success btn-sm w-100" onclick="guardarOKR()"><i class="fas fa-save me-1"></i>Guardar OKRs</button>
                <div id="okrStatus" class="mt-2"></div>
            </div>
        </div>

        <?php if (!empty($objetivosExistentes)): ?>
        <div class="card-box mt-3">
            <div class="card-box-header">Objetivos Existentes</div>
            <div class="card-box-body small">
                <?php foreach ($objetivosExistentes as $obj): ?>
                <div class="mb-2 p-2 border rounded">
                    <strong><?= htmlspecialchars($obj['objetivo_nombre']) ?></strong>
                    <div class="text-muted"><?= $obj['objetivo_avance_porcentaje'] ?>%</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-9">
        <div id="okrContainer">
            <?php $okrIndex = 0; ?>
            <?php foreach ($objetivosExistentes as $obj): ?>
            <div class="card mb-3 okr-card" style="border-left: 4px solid #1a73e8;border-radius:12px">
                <div class="card-body p-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-primary mb-1">OBJETIVO <?= ++$okrIndex ?></label>
                            <input type="text" class="form-control okr-obj" value="<?= htmlspecialchars($obj['objetivo_nombre']) ?>" placeholder="Objetivo ambicioso y cualitativo...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Peso (%)</label>
                            <input type="number" class="form-control form-control-sm okr-peso" value="<?= $obj['objetivo_peso_relativo'] ?? 25 ?>" min="0" max="100">
                        </div>
                        <div class="col-md-2 text-end">
                            <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.okr-card').remove()"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label small fw-bold mb-2">Key Results (3-5 KRs medibles)</label>
                        <div class="kr-list">
                            <?php $krs = json_decode($obj['objetivo_descripcion'] ?? '[]', true) ?: []; ?>
                            <?php foreach (($krs ?: ['']) as $kr): ?>
                            <div class="row g-2 mb-2 kr-row">
                                <div class="col-md-6">
                                    <input type="text" class="form-control form-control-sm kr-desc" value="<?= htmlspecialchars(is_array($kr)?($kr['desc']??''):$kr) ?>" placeholder="Key Result medible...">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control form-control-sm kr-target" value="<?= is_array($kr)?($kr['target']??100):100 ?>" placeholder="Meta">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control form-control-sm kr-score" value="<?= is_array($kr)?($kr['score']??0.7):0.7 ?>" step="0.1" min="0" max="1" placeholder="Score 0-1">
                                </div>
                                <div class="col-md-2">
                                    <div class="progress" style="height:8px;border-radius:4px;margin-top:8px">
                                        <div class="progress-bar bg-success kr-bar" style="width:<?= ((is_array($kr)?($kr['score']??0.7):0.7)*100) ?>%"></div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger mt-1" onclick="this.closest('.kr-row').remove()" style="font-size:0.65rem">×</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="addKR(this)"><i class="fas fa-plus me-1"></i>Añadir Key Result</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
let okrCount = <?= $okrIndex ?>;

function addOKR() {
    okrCount++;
    const container = document.getElementById('okrContainer');
    const card = document.createElement('div');
    card.className = 'card mb-3 okr-card';
    card.style = 'border-left: 4px solid #1a73e8;border-radius:12px';
    card.innerHTML = `<div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-8">
                <label class="form-label small fw-bold text-primary mb-1">OBJETIVO ${okrCount}</label>
                <input type="text" class="form-control okr-obj" placeholder="Objetivo ambicioso y cualitativo...">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Peso (%)</label>
                <input type="number" class="form-control form-control-sm okr-peso" value="${Math.round(100/okrCount)}" min="0" max="100">
            </div>
            <div class="col-md-2 text-end">
                <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.okr-card').remove()"><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <div class="mt-3">
            <label class="form-label small fw-bold mb-2">Key Results (3-5 KRs medibles)</label>
            <div class="kr-list">
                <div class="row g-2 mb-2 kr-row">
                    <div class="col-md-6"><input type="text" class="form-control form-control-sm kr-desc" placeholder="Key Result medible..."></div>
                    <div class="col-md-2"><input type="number" class="form-control form-control-sm kr-target" value="100" placeholder="Meta"></div>
                    <div class="col-md-2"><input type="number" class="form-control form-control-sm kr-score" value="0.7" step="0.1" min="0" max="1" placeholder="Score"></div>
                    <div class="col-md-2"><div class="progress" style="height:8px;border-radius:4px;margin-top:8px"><div class="progress-bar bg-success" style="width:70%"></div></div></div>
                </div>
            </div>
            <button class="btn btn-sm btn-outline-secondary" onclick="addKR(this)"><i class="fas fa-plus me-1"></i>Añadir Key Result</button>
        </div>
    </div>`;
    container.appendChild(card);
}

function addKR(btn) {
    const list = btn.previousElementSibling;
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 kr-row';
    row.innerHTML = `<div class="col-md-6"><input type="text" class="form-control form-control-sm kr-desc" placeholder="Key Result medible..."></div>
        <div class="col-md-2"><input type="number" class="form-control form-control-sm kr-target" value="100"></div>
        <div class="col-md-2"><input type="number" class="form-control form-control-sm kr-score" value="0.7" step="0.1" min="0" max="1"></div>
        <div class="col-md-2"><div class="progress" style="height:8px;border-radius:4px;margin-top:8px"><div class="progress-bar bg-success" style="width:70%"></div></div><button class="btn btn-sm btn-outline-danger mt-1" onclick="this.closest('.kr-row').remove()" style="font-size:0.65rem">×</button></div>`;
    list.appendChild(row);
}

async function generarOKR() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
    try {
        const resp = await fetch('/generar', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'tipo=objetivos&plan_id=<?= $planId ?>'});
        const data = await resp.json();
        if (data.success) {
            document.getElementById('okrStatus').innerHTML = '<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> '+data.objetivos_creados+' OKRs generados</div>';
            setTimeout(()=>location.reload(), 1500);
        }
    } catch(e) {}
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-brain me-1"></i>Sugerir OKRs con IA';
}

async function guardarOKR() {
    document.getElementById('okrStatus').innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Guardando...</div>';
    const cards = document.querySelectorAll('.okr-card');
    for (const card of cards) {
        const objName = card.querySelector('.okr-obj')?.value;
        if (!objName) continue;
        const krs = [];
        card.querySelectorAll('.kr-row').forEach(row => {
            krs.push({desc: row.querySelector('.kr-desc')?.value||'', target: row.querySelector('.kr-target')?.value||100, score: row.querySelector('.kr-score')?.value||0.7});
        });
        try {
            await fetch('/tools/save-okr', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body: `plan_id=<?= $planId ?>&obj_nombre=${encodeURIComponent(objName)}&krs=${encodeURIComponent(JSON.stringify(krs))}`});
        } catch(e) {}
    }
    document.getElementById('okrStatus').innerHTML = '<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> Guardado</div>';
}

// Update progress bars on score change
document.addEventListener('input', e => { if(e.target.classList.contains('kr-score')){ const bar=e.target.closest('.kr-row').querySelector('.kr-bar'); if(bar)bar.style.width=(parseFloat(e.target.value||0)*100)+'%'; }});

<?php if ($esCheckin): ?>
function addCheckin() {
    var hoy = new Date().toISOString().split('T')[0];
    var html = '<div class="p-2 mb-1 bg-white rounded border small">';
    html += '<strong>'+hoy+'</strong> <button class="btn btn-sm btn-outline-danger p-0 px-1 float-end" onclick="this.closest(\'div.bg-white\').remove()" style="font-size:0.6rem">x</button>';
    <?php foreach ($objetivosExistentes as $idx => $obj): ?>
    html += '<div class="d-flex gap-2 align-items-center ms-2 mt-1"><span><?= htmlspecialchars(substr($obj['objetivo_nombre'],0,25)) ?>:</span><input class="form-control form-control-sm" style="width:60px" type="number" min="0" max="100" value="0" placeholder="%"><select class="form-select form-select-sm" style="width:100px"><option>on_track</option><option>at_risk</option><option>off_track</option></select></div>';
    <?php endforeach; ?>
    html += '</div>';
    document.getElementById('hk-checkins').insertAdjacentHTML('afterbegin', html);
}

// Sobreescribir guardarOKR para incluir checkins
var _guardarOKR_orig = guardarOKR;
guardarOKR = async function() {
    await _guardarOKR_orig();
    // Guardar checkins
    var checkins = [];
    document.querySelectorAll('#hk-checkins .bg-white').forEach(function(el){
        var ci = {fecha: el.querySelector('strong').textContent, krs: []};
        el.querySelectorAll('.d-flex').forEach(function(row, i){
            var inputs = row.querySelectorAll('input, select');
            ci.krs.push({nombre: '<?= $objetivosExistentes[$idx]['objetivo_nombre'] ?? '' ?>', avance: parseFloat(inputs[0]?.value||0)/100, confianza: inputs[1]?.value||'on_track'});
        });
        checkins.push(ci);
    });
    try {
        await fetch('/tools/save-scenarios', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'plan_id=<?= $planId ?>&fase_id=<?= $faseId ?>&data='+encodeURIComponent(JSON.stringify({checkins:checkins}))});
        if (typeof updateCompleteButtons === 'function') updateCompleteButtons();
    } catch(e) {}
    document.getElementById('okrStatus').innerHTML = '<div class="alert alert-success py-1 px-2 small mt-1">Check-in guardado</div>';
};
<?php endif; ?>

<?php if ($esCierre): ?>
var _guardarOKR_orig2 = guardarOKR;
guardarOKR = async function() {
    await _guardarOKR_orig2();
    var retro = document.getElementById('okr-retro')?.value || '';
    try {
        await fetch('/tools/save-scenarios', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'plan_id=<?= $planId ?>&fase_id=<?= $faseId ?>&data='+encodeURIComponent(JSON.stringify({retrospectiva:retro}))});
        if (typeof updateCompleteButtons === 'function') updateCompleteButtons();
    } catch(e) {}
};
<?php endif; ?>
</script>
