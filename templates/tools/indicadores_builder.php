<?php
require_once BASE_PATH . '/lib/IndicatorManager.php';
$im = new IndicatorManager();
$perspectivas = ['financiera'=>'Financiera','cliente'=>'Cliente','procesos'=>'Procesos Internos','aprendizaje'=>'Aprendizaje y Crecimiento'];
$colores = ['financiera'=>'#28a745','cliente'=>'#007bff','procesos'=>'#ff9800','aprendizaje'=>'#6f42c1','sostenibilidad'=>'#00bcd4'];
$indicadores = $im->getIndicadores($planId);
$objetivos = $pm->getObjetivos($planId);
$perspActivas = isset($_GET['perspectivas']) ? explode(',',$_GET['perspectivas']) : array_keys($perspectivas);

// Agrupar indicadores por perspectiva según objetivo asociado
$indsPorPersp = [];
$indsSinPersp = [];
foreach ($indicadores as $ind) {
    $oid = (int)($ind['indicador_objetivo_id'] ?? 0);
    $persp = null;
    if ($oid) {
        foreach ($objetivos as $obj) {
            if ((int)$obj['objetivo_id'] === $oid) { $persp = $obj['objetivo_perspectiva'] ?? null; break; }
        }
    }
    if ($persp && isset($perspectivas[$persp])) {
        $indsPorPersp[$persp][] = $ind;
    } else {
        $indsSinPersp[] = $ind;
    }
}
?>
<div class="d-flex justify-content-between mb-2">
    <h5><i class="fas fa-gauge-high me-2"></i>Indicadores KPIs y Metas</h5>
    <div class="d-flex gap-1 small">
        <?php foreach ($perspectivas as $pk => $pv): $checked = in_array($pk, $perspActivas); ?>
        <a href="?perspectivas=<?= $checked ? implode(',',array_diff($perspActivas,[$pk])) : implode(',',array_merge($perspActivas,[$pk])) ?>" class="btn btn-sm <?= $checked ? 'btn-primary' : 'btn-outline-secondary' ?>" style="font-size:0.7rem"><?= $pv ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-brain me-2" style="color:#1a73e8"></i>Asistente IA</div>
            <div class="card-box-body">
                <button type="button" class="btn btn-purple btn-sm w-100 mb-2" onclick="generarIndicadores()">&#129504; Sugerir KPIs por perspectiva</button>
                <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="abrirNuevoInd()">+ Nuevo indicador</button>
                <div id="indStatus" class="mt-2"></div>
            </div>
        </div>

        <div class="card-box mt-3">
            <div class="card-box-header">Pareto de Impacto</div>
            <div class="card-box-body">
                <div id="paretoChart" style="height:200px;position:relative">
                    <?php
                    $top10 = array_slice($indicadores, 0, 10);
                    $maxVal = 0; foreach ($top10 as $i) { $v = (float)($i['indicador_rango_maximo'] ?? 0); if ($v > $maxVal) $maxVal = $v; }
                    $maxVal = max($maxVal, 1);
                    $acum = 0; $count = count($top10);
                    foreach ($top10 as $idx => $ind):
                        $val = (float)($ind['indicador_rango_maximo'] ?? 50);
                        $pct = round($val / $maxVal * 100);
                        $acum += ($idx < $count ? (100 / $count) : 0);
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-1 small" style="font-size:0.65rem">
                        <span style="width:80px;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(substr($ind['indicador_nombre'],0,15)) ?></span>
                        <div style="flex:1;background:#eee;border-radius:3px;height:8px"><div style="width:<?= $pct ?>%;background:<?= $colores['financiera'] ?>;border-radius:3px;height:8px"></div></div>
                        <span style="width:30px;font-weight:bold"><?= round($val) ?></span>
                    </div>
                    <?php endforeach; if(empty($top10)): ?>
                    <p class="text-muted small text-center">Crea indicadores para ver el Pareto</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card-box mt-3">
            <div class="card-box-header">Resumen</div>
            <div class="card-box-body small">
                <?php foreach ($perspectivas as $pk => $pv): $cnt = count($indsPorPersp[$pk] ?? []); ?>
                <div class="d-flex justify-content-between mb-1">
                    <span style="color:<?= $colores[$pk] ?>">&#9679; <?= $pv ?></span>
                    <strong><?= $cnt ?></strong>
                </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between"><span>Total KPIs</span><strong><?= count($indicadores) ?></strong></div>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <?php foreach ($perspectivas as $pk => $pv): if (!in_array($pk, $perspActivas)) continue; $inds = $indsPorPersp[$pk] ?? []; ?>
        <div class="card-box mb-3" style="border-left:4px solid <?= $colores[$pk] ?>">
            <div class="card-box-header d-flex justify-content-between align-items-center">
                <span style="color:<?= $colores[$pk] ?>">&#9679; <?= $pv ?></span>
                <button type="button" class="btn btn-sm btn-outline-secondary p-0 px-1" onclick="abrirNuevoInd('<?= $pk ?>')" title="Añadir KPI a <?= $pv ?>" style="font-size:0.7rem">&plus;</button>
            </div>
            <div class="card-box-body p-2">
                <?php if (empty($inds)): ?>
                <p class="text-muted small text-center py-2">Sin indicadores en esta perspectiva</p>
                <?php else: ?>
                <div class="table-responsive">
                <table class="table table-sm small mb-0" style="font-size:0.75rem">
                    <thead><tr><th>Indicador</th><th>Fórmula</th><th>Meta</th><th>Semáforo</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($inds as $ind): 
                        $sem = json_decode($ind['indicador_semaforo_json'] ?? '{}', true) ?: [];
                        $meta = $ind['indicador_rango_maximo'] ?? $ind['indicador_rango_minimo'] ?? 0;
                    ?>
                    <tr data-ind-id="<?= $ind['indicador_id'] ?>" data-obj-id="<?= $oid ?>" data-ind-name="<?= htmlspecialchars($ind['indicador_nombre'], ENT_QUOTES) ?>">
                        <td><strong><?= htmlspecialchars(substr($ind['indicador_nombre'],0,40)) ?></strong><br><small class="text-muted"><?= htmlspecialchars($ind['indicador_fuente_datos'] ?? '') ?></small></td>
                        <td><?= htmlspecialchars($ind['indicador_formula'] ?? '') ?></td>
                        <td><?= $ind['indicador_unidad_medida'] ? htmlspecialchars($ind['indicador_unidad_medida']) . ': ' . ($ind['indicador_rango_maximo'] ?? '—') : '—' ?></td>
                        <td>
                            <?php $colorSem = $sem['color'] ?? 'secondary'; ?>
                            <span class="d-inline-block" style="width:12px;height:12px;border-radius:50%;background:<?= $colorSem === 'green' ? '#28a745' : ($colorSem === 'yellow' ? '#ffc107' : ($colorSem === 'red' ? '#dc3545' : '#6c757d')) ?>"></span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm p-0 px-1" onclick="editarInd(<?= $ind['indicador_id'] ?>)" title="Editar" style="font-size:0.7rem">&#9998;</button>
                            <button type="button" class="btn btn-sm p-0 px-1 text-danger" onclick="eliminarInd(<?= $ind['indicador_id'] ?>)" title="Eliminar" style="font-size:0.7rem">&#10005;</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($indsSinPersp)): ?>
        <div class="card-box mb-3" style="border-left:4px solid #6c757d">
            <div class="card-box-header d-flex justify-content-between align-items-center">
                <span style="color:#6c757d">&#9679; Sin clasificar</span>
            </div>
            <div class="card-box-body p-2">
                <div class="table-responsive">
                <table class="table table-sm small mb-0" style="font-size:0.75rem">
                    <thead><tr><th>Indicador</th><th>Fórmula</th><th>Meta</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($indsSinPersp as $ind): $meta = $ind['indicador_rango_maximo'] ?? 0; ?>
                    <tr data-ind-id="<?= $ind['indicador_id'] ?>">
                        <td><strong><?= htmlspecialchars(substr($ind['indicador_nombre'],0,40)) ?></strong></td>
                        <td><?= htmlspecialchars($ind['indicador_formula'] ?? '') ?></td>
                        <td><?= $ind['indicador_unidad_medida'] ? htmlspecialchars($ind['indicador_unidad_medida']) . ': ' . ($ind['indicador_rango_maximo'] ?? '—') : '—' ?></td>
                        <td>
                            <button type="button" class="btn btn-sm p-0 px-1" onclick="editarInd(<?= $ind['indicador_id'] ?>)" style="font-size:0.7rem">&#9998;</button>
                            <button type="button" class="btn btn-sm p-0 px-1 text-danger" onclick="eliminarInd(<?= $ind['indicador_id'] ?>)" style="font-size:0.7rem">&#10005;</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nuevo/Editar Indicador -->
<div class="modal fade" id="modalInd" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2"><h6 class="modal-title" id="modalIndTitle">Nuevo indicador</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="ind-edit-id" value="">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">Perspectiva</label>
                        <select id="ind-persp" class="form-select form-select-sm">
                            <?php foreach ($perspectivas as $pk => $pv): if (!in_array($pk, $perspActivas)) continue; ?>
                            <option value="<?= $pk ?>"><?= $pv ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Objetivo asociado</label>
                        <select id="ind-objetivo" class="form-select form-select-sm">
                            <option value="">— Sin objetivo —</option>
                            <?php foreach ($objetivos as $obj): ?>
                            <option value="<?= $obj['objetivo_id'] ?>"><?= htmlspecialchars(substr($obj['objetivo_nombre'],0,35)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Nombre del indicador</label>
                        <input id="ind-nombre" class="form-control form-control-sm" placeholder="Ej: Margen EBITDA">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Fórmula</label>
                        <input id="ind-formula" class="form-control form-control-sm" placeholder="(Ingresos - Costos) / Ingresos">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Unidad</label>
                        <input id="ind-unidad" class="form-control form-control-sm" placeholder="%">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Meta (rango max)</label>
                        <input id="ind-meta" class="form-control form-control-sm" type="number" step="0.01" placeholder="18">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Semáforo</label>
                        <select id="ind-semaforo" class="form-select form-select-sm">
                            <option value="green">Verde (>= meta)</option>
                            <option value="yellow">Amarillo (>= 70% meta)</option>
                            <option value="red">Rojo (< 70% meta)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Fuente de datos</label>
                        <input id="ind-fuente" class="form-control form-control-sm" placeholder="ERP / CRM / Sistema contable">
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="guardarInd()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
function abrirNuevoInd(persp) {
    document.getElementById('ind-edit-id').value = '';
    document.getElementById('ind-persp').value = persp || 'financiera';
    document.getElementById('ind-nombre').value = '';
    document.getElementById('ind-formula').value = '';
    document.getElementById('ind-unidad').value = '';
    document.getElementById('ind-meta').value = '';
    document.getElementById('ind-fuente').value = '';
    document.getElementById('modalIndTitle').textContent = 'Nuevo indicador';
    var modal = new bootstrap.Modal(document.getElementById('modalInd'));
    modal.show();
}

async function guardarInd() {
    var id = document.getElementById('ind-edit-id').value;
    var data = {
        indicador_plan_id: <?= $planId ?>,
        indicador_nombre: document.getElementById('ind-nombre').value.trim(),
        indicador_formula: document.getElementById('ind-formula').value.trim(),
        indicador_unidad_medida: document.getElementById('ind-unidad').value.trim(),
        indicador_rango_maximo: parseFloat(document.getElementById('ind-meta').value) || 0,
        indicador_semaforo_json: JSON.stringify({color: document.getElementById('ind-semaforo').value}),
        indicador_fuente_datos: document.getElementById('ind-fuente').value.trim(),
        indicador_objetivo_id: parseInt(document.getElementById('ind-objetivo').value) || null,
    };
    if (!data.indicador_nombre) { alert('Nombre requerido'); return; }
    var url = id ? '/tools/edit-indicador' : '/tools/save-indicador';
    if (id) data.indicador_id = id;
    try {
        var r = await fetch(url, {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: 'data='+encodeURIComponent(JSON.stringify(data))});
        var d = await r.json();
        if (d.success) location.reload();
        else alert('Error: '+JSON.stringify(d));
    } catch(e) { alert('Error de conexión'); }
}

async function eliminarInd(id) {
    if (!confirm('¿Eliminar este indicador?')) return;
    await fetch('/tools/delete-indicador', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id});
    location.reload();
}

async function editarInd(id) {
    var row = document.querySelector('tr[data-ind-id="'+id+'"]');
    if (!row) return;
    var name = row.getAttribute('data-ind-name');
    document.getElementById('ind-edit-id').value = id;
    document.getElementById('ind-nombre').value = name;
    document.getElementById('modalIndTitle').textContent = 'Editar indicador';
    var modal = new bootstrap.Modal(document.getElementById('modalInd'));
    modal.show();
}

async function generarIndicadores() {
    var btn = event.target;
    btn.disabled = true; btn.innerHTML = '&#9203; Generando...';
    try {
        var r = await fetch('/generar', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'tipo=indicadores&plan_id=<?= $planId ?>&contexto=Genera KPIs para cada perspectiva del BSC'});
        var d = await r.json();
        if (d.success) {
            document.getElementById('indStatus').innerHTML = '<div class="alert alert-success py-1 small">'+(d.indicadores_creados||0)+' KPIs creados</div>';
            location.reload();
        }
    } catch(e) { document.getElementById('indStatus').innerHTML = '<div class="alert alert-danger py-1 small">Error</div>'; }
    btn.disabled = false; btn.innerHTML = '&#129504; Sugerir KPIs por perspectiva';
}
</script>
<?php if (!empty($_GET['auto_open']) && !empty($_GET['obj_id'])): 
    $objLookup = []; foreach ($objetivos as $o) $objLookup[$o['objetivo_id']] = $o;
    $objPersp = $objLookup[(int)$_GET['obj_id']]['objetivo_perspectiva'] ?? 'financiera';
?>
<script>
document.addEventListener('DOMContentLoaded',function(){
    document.getElementById('ind-persp').value = '<?= $objPersp ?>';
    document.getElementById('ind-objetivo').value = <?= (int)$_GET['obj_id'] ?>;
    new bootstrap.Modal(document.getElementById('modalInd')).show();
});
</script>
<?php endif; ?>
