<?php
$objetivosDelPlan = $pm->getObjetivos($planId);
$objMap = []; foreach ($objetivosDelPlan as $o) $objMap[$o['objetivo_id']] = $o;

// Cargar estrategias por cada objetivo
$estrategias = [];
foreach ($objetivosDelPlan as $obj) {
    $ests = $pm->getEstrategias($obj['objetivo_id']);
    foreach ($ests as $est) {
        $estrategias[] = $est;
    }
}
$perspColors = ['financiera'=>'#28a745','cliente'=>'#007bff','procesos'=>'#ff9800','aprendizaje'=>'#6f42c1'];
// Agrupar estrategias por objetivo_id que esté en el plan
$estsPorObj = [];
$estsSinObj = [];
foreach ($estrategias as $est) {
    $oid = (int)$est['estrategia_objetivo_id'];
    if ($oid && isset($objMap[$oid])) {
        $estsPorObj[$oid][] = $est;
    } else {
        $estsSinObj[] = $est;
    }
}
$tipos = ['ofensiva'=>'Ofensiva','defensiva'=>'Defensiva','adaptativa'=>'Adaptativa','supervivencia'=>'Supervivencia','innovacion'=>'Innovación','crecimiento'=>'Crecimiento'];
$prioridades = ['critico'=>'Crítico','alto'=>'Alto','medio'=>'Medio','bajo'=>'Bajo'];
$estados = ['pendiente'=>'Pendiente','en_proceso'=>'En Proceso','implementada'=>'Implementada','evaluada'=>'Evaluada','cancelada'=>'Cancelada'];
$estadoColors = ['pendiente'=>'secondary','en_proceso'=>'warning','implementada'=>'success','evaluada'=>'info','cancelada'=>'danger'];
?>
<div class="d-flex justify-content-between mb-2">
    <h5><i class="fas fa-rocket me-2"></i>Iniciativas Estratégicas</h5>
    <small class="text-muted"><?= count($estrategias) ?> iniciativas</small>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-brain me-2" style="color:#1a73e8"></i>Acciones</div>
            <div class="card-box-body">
                <button type="button" class="btn btn-purple btn-sm w-100 mb-2" onclick="generarIniciativas()">&#129504; Sugerir iniciativas</button>
                <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="abrirNuevaIni()">+ Nueva iniciativa</button>
                <div id="iniStatus" class="mt-2"></div>
            </div>
        </div>
        <div class="card-box mt-3">
            <div class="card-box-header">Distribución</div>
            <div class="card-box-body small">
                <?php foreach ($tipos as $tk => $tv): $cnt = 0; foreach ($estrategias as $e) if (($e['estrategia_tipo']??'') === $tk) $cnt++; if (!$cnt) continue; ?>
                <div class="d-flex justify-content-between mb-1"><span><?= $tv ?></span><strong><?= $cnt ?></strong></div>
                <?php endforeach; ?>
                <hr>
                <?php $presupTotal = 0; foreach ($estrategias as $e) $presupTotal += (float)($e['estrategia_presupuesto'] ?? 0); ?>
                <div class="d-flex justify-content-between"><span>Presupuesto total</span><strong>$<?= number_format($presupTotal, 0) ?></strong></div>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <?php if (empty($estsPorObj) && empty($estsSinObj)): ?>
        <div class="card-box">
            <div class="card-box-body text-center py-5 text-muted">
                <p><i class="fas fa-rocket" style="font-size:3rem;color:#ccc;display:block;margin-bottom:16px"></i></p>
                <h5>Sin iniciativas</h5>
                <p>Usa el asistente IA o crea iniciativas manualmente vinculadas a objetivos.</p>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($estsPorObj as $oid => $ests): $obj = $objMap[$oid]; ?>
        <div class="card-box mb-3" style="border-left:4px solid <?= $perspColors[$obj['objetivo_perspectiva'] ?? 'financiera'] ?>" id="obj-<?= $oid ?>">
            <div class="card-box-header d-flex justify-content-between">
                <span>&#127919; <?= htmlspecialchars(substr($obj['objetivo_nombre'], 0, 45)) ?></span>
                <small style="color:<?= $perspColors[$obj['objetivo_perspectiva'] ?? 'financiera'] ?>"><?= $obj['objetivo_perspectiva'] ?? '' ?></small>
            </div>
            <div class="card-box-body p-2">
                <div class="table-responsive">
                <table class="table table-sm small mb-0" style="font-size:0.75rem">
                    <thead><tr><th>Iniciativa</th><th>Tipo</th><th>Prioridad</th><th>Presupuesto</th><th>Avance</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($ests as $est): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars(substr($est['estrategia_nombre'],0,40)) ?></strong></td>
                        <td><?= $tipos[$est['estrategia_tipo'] ?? 'crecimiento'] ?></td>
                        <td><span class="badge bg-<?= $est['estrategia_prioridad']==='critico'?'danger':($est['estrategia_prioridad']==='alto'?'warning':'secondary') ?>"><?= $prioridades[$est['estrategia_prioridad']??'medio'] ?></span></td>
                        <td><?= $est['estrategia_presupuesto'] ? '$'.number_format($est['estrategia_presupuesto'],0) : '—' ?></td>
                        <td>
                            <div class="progress" style="width:60px;height:6px"><div class="progress-bar bg-<?= ($est['estrategia_avance_porcentaje']??0)>=100?'success':(($est['estrategia_avance_porcentaje']??0)>=50?'warning':'danger') ?>" style="width:<?= $est['estrategia_avance_porcentaje']??0 ?>%"></div></div>
                            <small><?= $est['estrategia_avance_porcentaje']??0 ?>%</small>
                        </td>
                        <td><span class="badge bg-<?= $estadoColors[$est['estrategia_estado']??'pendiente'] ?>"><?= $estados[$est['estrategia_estado']??'pendiente'] ?></span></td>
                        <td>
                            <button type="button" class="btn btn-sm p-0 px-1" onclick="editarIni(<?= htmlspecialchars(json_encode($est)) ?>)" style="font-size:0.7rem">&#9998;</button>
                            <button type="button" class="btn btn-sm p-0 px-1 text-danger" onclick="eliminarIni(<?= $est['estrategia_id'] ?>)" style="font-size:0.7rem">&#10005;</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($estsSinObj)): ?>
        <div class="card-box mb-3" style="border-left:4px solid #6c757d">
            <div class="card-box-header">Sin objetivo asociado</div>
            <div class="card-box-body p-2">
                <div class="table-responsive">
                <table class="table table-sm small mb-0" style="font-size:0.75rem">
                    <thead><tr><th>Iniciativa</th><th>Tipo</th><th>Prioridad</th><th>Presupuesto</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($estsSinObj as $est): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars(substr($est['estrategia_nombre'],0,40)) ?></strong></td>
                        <td><?= $tipos[$est['estrategia_tipo'] ?? 'crecimiento'] ?></td>
                        <td><?= $prioridades[$est['estrategia_prioridad']??'medio'] ?></td>
                        <td><?= $est['estrategia_presupuesto'] ? '$'.number_format($est['estrategia_presupuesto'],0) : '—' ?></td>
                        <td><span class="badge bg-<?= $estadoColors[$est['estrategia_estado']??'pendiente'] ?>"><?= $estados[$est['estrategia_estado']??'pendiente'] ?></span></td>
                        <td>
                            <button type="button" class="btn btn-sm p-0 px-1" onclick="editarIni(<?= htmlspecialchars(json_encode($est)) ?>)" style="font-size:0.7rem">&#9998;</button>
                            <button type="button" class="btn btn-sm p-0 px-1 text-danger" onclick="eliminarIni(<?= $est['estrategia_id'] ?>)" style="font-size:0.7rem">&#10005;</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nueva/Editar Iniciativa -->
<div class="modal fade" id="modalIni" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2"><h6 class="modal-title" id="modalIniTitle">Nueva iniciativa</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="ini-edit-id" value="">
                <div class="row g-2">
                    <div class="col-md-8">
                        <label class="form-label small">Nombre</label>
                        <input id="ini-nombre" class="form-control form-control-sm" placeholder="Ej: Programa de transformación digital">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Objetivo</label>
                        <select id="ini-objetivo" class="form-select form-select-sm">
                            <option value="">— Sin objetivo —</option>
                            <?php foreach ($objetivosDelPlan as $obj): ?>
                            <option value="<?= $obj['objetivo_id'] ?>"><?= htmlspecialchars(substr($obj['objetivo_nombre'],0,35)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Tipo</label>
                        <select id="ini-tipo" class="form-select form-select-sm">
                            <?php foreach ($tipos as $tk => $tv): ?>
                            <option value="<?= $tk ?>"><?= $tv ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Prioridad</label>
                        <select id="ini-prioridad" class="form-select form-select-sm">
                            <?php foreach ($prioridades as $pk => $pv): ?>
                            <option value="<?= $pk ?>"><?= $pv ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Estado</label>
                        <select id="ini-estado" class="form-select form-select-sm">
                            <?php foreach ($estados as $ek => $ev): ?>
                            <option value="<?= $ek ?>"><?= $ev ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Presupuesto ($)</label>
                        <input id="ini-presupuesto" class="form-control form-control-sm" type="number" step="0.01" placeholder="50000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Avance (%)</label>
                        <input id="ini-avance" class="form-control form-control-sm" type="number" min="0" max="100" step="1" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Responsable</label>
                        <select id="ini-responsable" class="form-select form-select-sm">
                            <option value="">— Sin asignar —</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Descripción</label>
                        <textarea id="ini-desc" class="form-control form-control-sm" rows="3" placeholder="Describe el alcance, hitos y entregables..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="guardarIni()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
function abrirNuevaIni() {
    document.getElementById('ini-edit-id').value = '';
    document.getElementById('ini-nombre').value = '';
    document.getElementById('ini-desc').value = '';
    document.getElementById('ini-presupuesto').value = '';
    document.getElementById('ini-avance').value = 0;
    document.getElementById('modalIniTitle').textContent = 'Nueva iniciativa';
    var modal = new bootstrap.Modal(document.getElementById('modalIni'));
    modal.show();
}

function editarIni(est) {
    document.getElementById('ini-edit-id').value = est.estrategia_id;
    document.getElementById('ini-nombre').value = est.estrategia_nombre || '';
    document.getElementById('ini-desc').value = est.estrategia_descripcion || '';
    document.getElementById('ini-presupuesto').value = est.estrategia_presupuesto || '';
    document.getElementById('ini-avance').value = est.estrategia_avance_porcentaje || 0;
    document.getElementById('ini-tipo').value = est.estrategia_tipo || 'crecimiento';
    document.getElementById('ini-prioridad').value = est.estrategia_prioridad || 'medio';
    document.getElementById('ini-estado').value = est.estrategia_estado || 'pendiente';
    document.getElementById('ini-objetivo').value = est.estrategia_objetivo_id || '';
    document.getElementById('modalIniTitle').textContent = 'Editar iniciativa';
    var modal = new bootstrap.Modal(document.getElementById('modalIni'));
    modal.show();
}

async function guardarIni() {
    var id = document.getElementById('ini-edit-id').value;
    var data = {
        estrategia_objetivo_id: parseInt(document.getElementById('ini-objetivo').value) || null,
        estrategia_nombre: document.getElementById('ini-nombre').value.trim(),
        estrategia_descripcion: document.getElementById('ini-desc').value.trim(),
        estrategia_tipo: document.getElementById('ini-tipo').value,
        estrategia_prioridad: document.getElementById('ini-prioridad').value,
        estrategia_estado: document.getElementById('ini-estado').value,
        estrategia_presupuesto: parseFloat(document.getElementById('ini-presupuesto').value) || 0,
        estrategia_avance_porcentaje: parseInt(document.getElementById('ini-avance').value) || 0,
    };
    if (!data.estrategia_nombre) { alert('Nombre requerido'); return; }
    var url = id ? '/tools/edit-estrategia' : '/tools/save-estrategia';
    if (id) data.estrategia_id = id;
    if (!data.estrategia_objetivo_id) delete data.estrategia_objetivo_id;
    try {
        var r = await fetch(url, {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: 'data='+encodeURIComponent(JSON.stringify(data))});
        var d = await r.json();
        if (d.success) location.reload();
    } catch(e) { alert('Error'); }
}

async function eliminarIni(id) {
    if (!confirm('¿Eliminar esta iniciativa?')) return;
    await fetch('/tools/delete-estrategia', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id});
    location.reload();
}

async function generarIniciativas() {
    var btn = event.target;
    btn.disabled = true; btn.innerHTML = '&#9203; Generando...';
    try {
        var r = await fetch('/generar', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'tipo=iniciativas&plan_id=<?= $planId ?>&contexto=Genera iniciativas estratégicas para cada objetivo del plan'});
        var d = await r.json();
        if (d.success) {
            document.getElementById('iniStatus').innerHTML = '<div class="alert alert-success py-1 small">'+(d.creadas||0)+' iniciativas creadas</div>';
            location.reload();
        }
    } catch(e) {}
    btn.disabled = false; btn.innerHTML = '&#129504; Sugerir iniciativas';
}
</script>
<?php if (!empty($_GET['auto_open']) && !empty($_GET['obj_id'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){document.getElementById('ini-objetivo').value=<?= (int)$_GET['obj_id'] ?>;new bootstrap.Modal(document.getElementById('modalIni')).show();});</script>
<?php endif; ?>
