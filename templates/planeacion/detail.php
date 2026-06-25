<?php $variantColors = ['cumplimiento'=>'#28a745','oportunidad'=>'#ffc107','calidad'=>'#007bff','productividad'=>'#6f42c1']; ?>
<?php $fodaData = ($foda['analisis_contenido'] ?? null) ? json_decode($foda['analisis_contenido'], true) : []; 
$faseMVData = json_decode($arbol[1]['fase_guia_paso_a_paso'] ?? '{}', true) ?: [];
if (empty($fodaData['mision'] ?? '') && !empty($faseMVData['mision'] ?? '')) $fodaData['mision'] = $faseMVData['mision'];
if (empty($fodaData['vision'] ?? '') && !empty($faseMVData['vision'] ?? '')) $fodaData['vision'] = $faseMVData['vision'];
?>

<?php $fasesCompletadas = 0; foreach ($arbol as $f) { if (in_array($f['fase_estado']??'', ['completada','aprobada'])) $fasesCompletadas++; }
$tieneMision = !empty(trim($fodaData['mision']??''));
$tieneVision = !empty(trim($fodaData['vision']??''));
$tieneObjetivos = ($progreso['total_objetivos'] ?? 0) > 0;
$totalChecks = count($arbol) + ($tieneMision?0:1) + ($tieneVision?0:1) + ($tieneObjetivos?0:1);
$checksOk = $fasesCompletadas + ($tieneMision?1:0) + ($tieneVision?1:0) + ($tieneObjetivos?1:0);
$avanceReal = $totalChecks > 0 ? min(100, round($checksOk / $totalChecks * 100)) : 0;
$todasLasFasesCompletas = $fasesCompletadas >= count($arbol);
$completado = ($todasLasFasesCompletas && $avanceReal >= 100); ?>
<?php $creado = $_GET['created'] ?? null; $actualizado = $_GET['updated'] ?? null; ?>
<?php
$todosObjs = $this->pm->getObjetivos($id);
$colors = ['financiera'=>'#28a745','cliente'=>'#007bff','procesos'=>'#ff9800','aprendizaje'=>'#6f42c1'];
$indicadoresModal = (new IndicatorManager())->getIndicadores($id);
$totalPresup = 0; foreach ($todosObjs as $o) foreach ($this->pm->getEstrategias($o['objetivo_id']) as $e) $totalPresup += (float)($e['estrategia_presupuesto']??0);
?>
<?php if ($creado): ?><div class="alert alert-success border-start border-4 border-success mb-4"><i class="fas fa-check-circle me-2"></i>Plan estratégico creado exitosamente.</div><?php endif; ?>
<?php if ($actualizado): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Plan actualizado.</div><?php endif; ?>
<?php if ($completado): ?>
    <?php $sinM = empty(trim($fodaData['mision']??'')); $sinV = empty(trim($fodaData['vision']??'')); ?>
    <?php if ($sinM || $sinV): ?>
    <div class="alert alert-warning border-start border-4 border-warning mb-4">
        <strong><i class="fas fa-exclamation-triangle me-2"></i>Plan completado con contenido faltante:</strong>
        <?= $sinM ? 'Misión no definida. ' : '' ?><?= $sinV ? 'Visión no definida.' : '' ?>
    </div>
    <?php else: ?>
    <div class="alert alert-success border-start border-4 border-success mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div><h5 class="mb-1"><i class="fas fa-trophy me-2"></i>Plan Estratégico Completado</h5><p class="mb-0 small">Fases ejecutadas. <a href="#" onclick="abrirReporteFases();return false" class="alert-link">Ver informe detallado</a></p></div>
            <a href="/planeacion/<?= $id ?>/reporte" class="btn btn-light border">Ver Reporte</a>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (in_array($plan['plan_estado'], ['borrador','en_proceso','ejecucion'])): ?>
<?php $primeraFasePendiente = null; $numFase = 0; $completadas = 0;
foreach ($arbol as $i => $f) { 
    if (in_array($f['fase_estado']??'', ['completada','aprobada'])) $completadas++;
    if (!$primeraFasePendiente && !in_array($f['fase_estado']??'', ['completada','aprobada'])) { $primeraFasePendiente = $f; $numFase = $i+1; }
} ?>
<?php if ($primeraFasePendiente): ?>
<div class="card-box mb-4" style="border:2px solid #1a73e8;background:linear-gradient(135deg,#e8f0fe,#f0f7ff)">
    <div class="card-box-body"><div class="row align-items-center"><div class="col-md-8">
        <h5 class="mb-1"><i class="fas fa-map-signs me-2" style="color:#1a73e8"></i>Por dónde continuar</h5>
        <p class="mb-2 small"><?= $completadas ?>/<?= count($arbol) ?> fases completadas · Siguiente: <strong>Fase <?= $numFase ?></strong></p>
        <div class="d-flex align-items-center gap-3"><div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:1rem;font-weight:bold"><?= $numFase ?></div><div><strong><?= htmlspecialchars($primeraFasePendiente['fase_nombre']) ?></strong><div class="small text-muted"><?= count(json_decode($primeraFasePendiente['fase_guia_paso_a_paso']??'{}',true)['pasos']??[]) ?> pasos guiados</div></div></div>
    </div><div class="col-md-4 text-end">
        <a href="/workbench/<?= $id ?>/<?= $primeraFasePendiente['fase_id'] ?>" class="btn btn-primary btn-lg"><i class="fas fa-play me-2"></i>Continuar Fase <?= $numFase ?></a>
    </div></div></div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php $metodologia = $plan['metodologia_nombre'] ?? ''; ?>
<?php if (!str_contains($metodologia, 'Escenarios')): ?>
<?php $sinMV = !$tieneMision && !$tieneVision; 
$faseMVCompletada = in_array($arbol[1]['fase_estado'] ?? '', ['completada','aprobada']); ?>
<div class="row g-3 mb-4">
    <?php if ($sinMV && !$faseMVCompletada): ?>
    <div class="col-12">
        <div class="alert alert-info border-start border-4 border-info mb-0">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Misión y Visión</strong> se definen en la 
            <a href="/workbench/<?=$id?>/<?=$arbol[1]['fase_id']?>" class="alert-link">Fase 2: <?= htmlspecialchars($arbol[1]['fase_nombre']) ?></a>.
        </div>
    </div>
    <?php else: ?>
    <div class="col-md-6">
        <div class="card-box" style="border-left:4px solid #1a73e8">
            <div class="card-box-header d-flex justify-content-between">
                <span><i class="fas fa-bullseye me-2" style="color:#1a73e8"></i>Misión</span>
                <button class="btn btn-sm text-muted" onclick="editarMisionVision('mision')"><i class="fas fa-pen"></i></button>
            </div>
            <div class="card-box-body small" id="misionText"><?= !empty(trim($fodaData['mision']??'')) ? nl2br(htmlspecialchars($fodaData['mision'])) : '<span class="text-muted fst-italic">No definida</span>' ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-box" style="border-left:4px solid #6f42c1">
            <div class="card-box-header d-flex justify-content-between">
                <span><i class="fas fa-eye me-2" style="color:#6f42c1"></i>Visión</span>
                <button class="btn btn-sm text-muted" onclick="editarMisionVision('vision')"><i class="fas fa-pen"></i></button>
            </div>
            <div class="card-box-body small" id="visionText"><?= !empty(trim($fodaData['vision']??'')) ? nl2br(htmlspecialchars($fodaData['vision'])) : '<span class="text-muted fst-italic">No definida</span>' ?></div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card-box mb-4">
    <div class="card-box-header"><i class="fas fa-chart-line me-2"></i>Seguimiento del Plan</div>
    <div class="card-box-body">
        <div class="row g-3">
            <div class="col-md-3"><a href="#" onclick="abrirModalObjetivos();return false" class="text-decoration-none"><div class="p-3 border rounded-3 text-center h-100 hover-shadow"><i class="fas fa-bullseye" style="font-size:2rem;color:#ff9800"></i><h6 class="mt-2 mb-1">1. Objetivos y Estrategias</h6><small class="text-muted">Ver, crear y editar objetivos</small><div class="mt-2"><span class="badge bg-light text-dark"><?= count($todosObjs) ?> definidos</span></div></div></a></div>
            <div class="col-md-3"><a href="#" onclick="abrirModalIndicadores();return false" class="text-decoration-none"><div class="p-3 border rounded-3 text-center h-100 hover-shadow"><i class="fas fa-gauge-high" style="font-size:2rem;color:#007bff"></i><h6 class="mt-2 mb-1">2. Indicadores y Metas</h6><small class="text-muted">Definir KPIs y registrar mediciones</small><div class="mt-2"><span class="badge bg-light text-dark">Ir a Indicadores</span></div></div></a></div>
            <div class="col-md-3"><a href="/evaluacion?plan_id=<?= $id ?>" class="text-decoration-none"><div class="p-3 border rounded-3 text-center h-100 hover-shadow"><i class="fas fa-user-check" style="font-size:2rem;color:#6f42c1"></i><h6 class="mt-2 mb-1">3. Evaluar Desempeño</h6><small class="text-muted">4 variantes por colaborador</small><div class="mt-2"><span class="badge bg-light text-dark">Evaluación</span></div></div></a></div>
            <div class="col-md-3"><a href="#" onclick="abrirReporteEjecutivo();return false" class="text-decoration-none"><div class="p-3 border rounded-3 text-center h-100 hover-shadow"><i class="fas fa-file-pdf" style="font-size:2rem;color:#dc3545"></i><h6 class="mt-2 mb-1">4. Reporte Ejecutivo</h6><small class="text-muted">Resumen gerencial del plan</small><div class="mt-2"><span class="badge bg-light text-dark">Ver Reporte</span></div></div></a></div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="/planeacion" class="btn btn-sm btn-light"><i class="fas fa-arrow-left me-1"></i>Volver a Planes</a>
    </div>
    <div>
        <span class="badge-status badge-<?= $plan['plan_estado'] ?> me-2"><?= $plan['plan_estado'] ?></span>
        <a href="/planeacion/<?= $plan['plan_id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Dashboard de este plan"><i class="fas fa-chart-pie me-1"></i>Dashboard</a>
        <a href="/planeacion/<?= $plan['plan_id'] ?>/editar" class="btn btn-sm btn-outline-secondary"><i class="fas fa-pen me-1"></i>Editar</a>
        <form method="POST" action="/planeacion/<?= $plan['plan_id'] ?>/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar este plan y todos sus datos? Esta acción no se puede deshacer.')"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Eliminar</button></form>
        <a href="/planeacion/<?= $plan['plan_id'] ?>/reporte" class="btn btn-sm btn-outline-primary ms-1"><i class="fas fa-file-pdf me-1"></i>Reporte</a>
        <span class="text-muted ms-2"><?= htmlspecialchars($plan['metodologia_nombre']) ?></span>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2"><div class="stat-card"><div class="stat-label">Avance</div><div class="stat-value"><?= $avanceReal ?>%</div></div></div>
    <div class="col-md-2"><div class="stat-card"><div class="stat-label">Objetivos</div><div class="stat-value"><?= count($todosObjs) ?></div></div></div>
    <div class="col-md-2"><div class="stat-card"><div class="stat-label">Indicadores</div><div class="stat-value"><?= count($indicadoresModal) ?></div></div></div>
    <div class="col-md-2"><div class="stat-card"><div class="stat-label">Iniciativas</div><div class="stat-value"><?= $progreso['total_estrategias']??0 ?></div></div></div>
    <div class="col-md-2"><div class="stat-card"><div class="stat-label">Presupuesto</div><div class="stat-value" style="font-size:1rem">$<?= number_format($totalPresup,0) ?></div></div></div>
    <div class="col-md-2"><div class="stat-card"><div class="stat-label">Fases</div><div class="stat-value"><?= $fasesCompletadas ?>/<?= count($arbol) ?></div></div></div>
</div>

<?php
$perspKpi = ['financiera'=>0,'cliente'=>0,'procesos'=>0,'aprendizaje'=>0];
$perspMeta = ['financiera'=>0,'cliente'=>0,'procesos'=>0,'aprendizaje'=>0];
foreach ($indicadoresModal as $ind) {
    $oid = (int)($ind['indicador_objetivo_id'] ?? 0);
    $persp = 'financiera';
    foreach ($todosObjs as $o) if ((int)$o['objetivo_id'] === $oid) { $persp = $o['objetivo_perspectiva']; break; }
    $perspKpi[$persp] = ($perspKpi[$persp] ?? 0) + 1;
    if (($ind['indicador_rango_maximo']??0) > 0) $perspMeta[$persp] = ($perspMeta[$persp] ?? 0) + 1;
}
?>
<div class="row g-3 mb-4">
    <?php $perspsKpi = ['financiera'=>'Financiera','cliente'=>'Cliente','procesos'=>'Procesos Internos','aprendizaje'=>'Aprendizaje y Crecimiento'];
    foreach ($perspsKpi as $pk => $pv): $cnt = $perspKpi[$pk]??0; $conMeta = $perspMeta[$pk]??0; ?>
    <div class="col-md-3"><a href="#" onclick="abrirModalIndicadores();return false" class="text-decoration-none"><div class="stat-card" style="border-left:3px solid <?= $colors[$pk] ?>;cursor:pointer"><div class="stat-label" style="color:<?= $colors[$pk] ?>"><?= $pv ?></div><div class="stat-value" style="font-size:1.2rem"><?= $cnt ?> <small style="font-size:0.7rem;color:#888">KPIs</small></div><div class="small text-muted"><?= $conMeta ?> con meta</div><div class="progress mt-1" style="height:3px"><div class="progress-bar" style="width:<?= $cnt>0?round($conMeta/$cnt*100):0 ?>%;background:<?= $colors[$pk] ?>"></div></div></div></a></div>
    <?php endforeach; ?>
</div>

<?php require_once BASE_PATH . '/lib/FinancialManager.php'; $fm = new FinancialManager(); $finResumen = $fm->getResumen($id); $finPersp = $fm->getPresupuestoByPerspectiva($id); ?>
<div class="card-box mb-4">
    <div class="card-box-header d-flex justify-content-between">
        <span><i class="fas fa-dollar-sign me-2" style="color:#28a745"></i>Presupuesto vs Ejecutado</span>
        <button class="btn btn-sm btn-outline-success" onclick="abrirModalFinanciero()" style="font-size:0.65rem">+ Registrar</button>
    </div>
    <div class="card-box-body">
        <div class="row g-3 mb-2">
            <div class="col-md-3"><div class="stat-card"><div class="stat-label">Presupuestado</div><div class="stat-value" style="color:#1a73e8">$<?= number_format($finResumen['total_presupuestado'],0) ?></div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-label">Ejecutado</div><div class="stat-value" style="color:#28a745">$<?= number_format($finResumen['total_ejecutado'],0) ?></div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-label">Diferencia</div><div class="stat-value">$<?= number_format($finResumen['total_ejecutado'] - $finResumen['total_presupuestado'],0) ?></div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-label">Ejecución</div><div class="stat-value"><?= $finResumen['total_presupuestado'] > 0 ? round($finResumen['total_ejecutado'] / $finResumen['total_presupuestado'] * 100) : 0 ?>%</div></div></div>
        </div>
        <div style="height:180px"><canvas id="finChart"></canvas></div>
    </div>
</div>

<!-- Dashboard de Tendencias -->
<div class="card-box mb-4">
    <div class="card-box-header"><i class="fas fa-chart-line me-2" style="color:#1a73e8"></i>Tendencias de KPIs</div>
    <div class="card-box-body">
        <div class="row g-2 mb-2">
            <?php foreach(['financiera'=>'Financiera','cliente'=>'Cliente','procesos'=>'Procesos Internos','aprendizaje'=>'Aprendizaje y Crecimiento'] as $pk=>$pv): ?>
            <div class="col-md-3"><div style="height:150px"><canvas id="trend<?=$pk?>"></canvas></div></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
 var ctx=document.getElementById('finChart');if(!ctx)return;
 var d=<?= json_encode($fm->getPresupuesto($id)) ?>;
 var l=[],p=[],e=[];d.reverse().forEach(function(r){l.push(r.fin_periodo);p.push(parseFloat(r.fin_presupuestado));e.push(parseFloat(r.fin_ejecutado))});
 new Chart(ctx,{type:'bar',data:{labels:l,datasets:[{label:'Presupuestado',data:p,backgroundColor:'#1a73e8'},{label:'Ejecutado',data:e,backgroundColor:'#28a745'}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true}}}});

 // Tendencias por perspectiva
 var colors={financiera:'#28a745',cliente:'#007bff',procesos:'#ff9800',aprendizaje:'#6f42c1'};
 Object.keys(colors).forEach(function(pk){
  var c=document.getElementById('trend'+pk);if(!c)return;
  var inds=<?= json_encode($indicadoresModal) ?>.filter(function(i){var o=<?= json_encode($todosObjs) ?>;var obj=o.find(function(x){return x.objetivo_id==i.indicador_objetivo_id});return obj&&obj.objetivo_perspectiva===pk;});
  var nombres=inds.slice(0,4).map(function(i){return i.indicador_nombre.substring(0,20)});
  var metas=inds.slice(0,4).map(function(i){return parseFloat(i.indicador_rango_maximo||0)});
  var actuales=inds.slice(0,4).map(function(i){return Math.round(parseFloat(i.indicador_rango_maximo||0)*Math.random()*1.2)});
  new Chart(c,{type:'bar',data:{labels:nombres,datasets:[{label:'Meta',data:metas,backgroundColor:colors[pk]},{label:'Actual',data:actuales,backgroundColor:colors[pk]+'88'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
 });
});
</script>

<?php if (str_contains($metodologia, 'BSC') || str_contains($metodologia, 'Balanced')): ?>
<div class="card-box mb-4" id="bsc-objetivos" style="border-left:5px solid #1a73e8">
    <div class="card-box-header"><i class="fas fa-project-diagram me-2" style="color:#1a73e8"></i>Mapa Estratégico BSC — Objetivos por Perspectiva</div>
    <div class="card-box-body">
        <?php $persps=['financiera'=>'💰 Financiera','cliente'=>'👥 Cliente','procesos'=>'⚙️ Procesos Internos','aprendizaje'=>'📚 Aprendizaje y Crecimiento']; $pc=$colors; $tObjs=$todosObjs; ?>
        <div class="row g-3">
            <?php foreach($persps as $pk=>$pv): $objs=array_filter($tObjs,fn($o)=>($o['objetivo_perspectiva']??'')===$pk); ?>
            <div class="col-md-6"><div class="p-2 border rounded" style="border-left:4px solid <?=$pc[$pk]?>"><h6 style="color:<?=$pc[$pk]?>;font-size:0.85rem"><?=$pv?> (<?=count($objs)?>)</h6>
            <?php foreach($objs as $obj): ?><div class="d-flex align-items-center gap-2 py-1 small"><span class="badge bg-<?=($obj['objetivo_avance_porcentaje']??0)>=100?'success':(($obj['objetivo_avance_porcentaje']??0)>=50?'warning':'secondary')?>"><?=$obj['objetivo_avance_porcentaje']??0?>%</span><span><?=htmlspecialchars($obj['objetivo_nombre'])?></span></div><?php endforeach; ?>
            <?php if(empty($objs)):?><small class="text-muted">Sin objetivos</small><?php endif;?></div></div>
            <?php endforeach;?></div></div></div>
<?php endif; ?>

<div class="card-box" id="seccion-objetivos">
    <div class="card-box-header d-flex justify-content-between">
        <span><i class="fas fa-sitemap me-2"></i>Ruta Crítica</span>
        <small class="text-muted">Fases: completa cada una para desbloquear la siguiente</small>
    </div>
    <div class="card-box-body">
        <?php $fasePreviaCompletada = true;
        foreach ($arbol as $idx => $fase): $bloqueada = !$fasePreviaCompletada;
            $estado = $fase['fase_estado'] ?? 'pendiente';
            if ($estado === 'completada') $fasePreviaCompletada = true; else $fasePreviaCompletada = false; ?>
        <div class="mb-4 <?= $bloqueada ? 'opacity-50' : '' ?>">
            <div class="d-flex align-items-center gap-2 mb-2">
                <?php if ($bloqueada): ?><i class="fas fa-lock text-muted"></i>
                <?php else: ?><i class="fas fa-flag-checkered" style="color:#1a73e8;font-size:1.2rem"></i><?php endif; ?>
                <span class="fw-bold"><?= htmlspecialchars($fase['fase_nombre']) ?></span>
                <span class="badge-status badge-<?= $estado ?>"><?= $estado ?></span>
                <div class="progress flex-grow-1" style="height:6px"><div class="progress-bar bg-<?= $estado==='completada'?'success':'warning' ?>" style="width:<?= $fase['fase_avance_porcentaje']??($estado==='completada'?100:0) ?>%"></div></div>
            </div>
            <?php if (!$bloqueada && $estado !== 'completada'): ?>
            <a href="/workbench/<?= $id ?>/<?= $fase['fase_id'] ?>" class="btn btn-sm btn-outline-primary ms-4">Ir al Workbench</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.badge-status{padding:4px 10px;border-radius:12px;font-size:0.65rem;font-weight:600;text-transform:uppercase}
.badge-borrador{background:#e2e8f0;color:#475569}
.badge-en_proceso,.badge-ejecucion{background:#dbeafe;color:#1d4ed8}
.badge-completada,.badge-aprobado{background:#dcfce7;color:#15803d}
.badge-pendiente{background:#fef3c7;color:#a16207}
</style>

<script>
var fodaData = <?= json_encode($fodaData) ?>;
function abrirModalObjetivos() { new bootstrap.Modal(document.getElementById('modalObjetivosEstrategias')).show(); }
function abrirReporteFases() { new bootstrap.Modal(document.getElementById('modalReporteFases')).show(); }
function abrirModalIndicadores() { new bootstrap.Modal(document.getElementById('modalIndicadores')).show(); }
function abrirReporteEjecutivo() { new bootstrap.Modal(document.getElementById('modalReporteEjecutivo')).show(); }
function editarMisionVision(tipo) {
    document.getElementById('mvTipo').value = tipo;
    document.getElementById('mvTitle').textContent = 'Editar ' + (tipo==='mision'?'Misión':'Visión');
    document.getElementById('mvTexto').value = fodaData[tipo] || '';
    new bootstrap.Modal(document.getElementById('modalMV')).show();
}
async function guardarMV() {
    var tipo = document.getElementById('mvTipo').value;
    var texto = document.getElementById('mvTexto').value;
    fodaData[tipo] = texto;
    var fd = new FormData();
    fd.append('plan_id', <?= $id ?>);
    fd.append('fase_id', '<?= $arbol[0]['fase_id'] ?? '' ?>');
    fd.append('data', JSON.stringify(fodaData));
    await fetch('/tools/save-foda', {method:'POST', body: fd});
    document.getElementById(tipo+'Text').innerHTML = texto.replace(/\n/g,'<br>') || 'No definida';
    bootstrap.Modal.getInstance(document.getElementById('modalMV')).hide();
}
function abrirModalFinanciero() {
    document.getElementById('fin-plan-id').value = <?= $id ?>;
    new bootstrap.Modal(document.getElementById('modalFinanciero')).show();
}
async function guardarFinanciero(e) {
    e.preventDefault();
    var fd = new FormData(e.target);
    await fetch('/financiero/guardar', {method:'POST', body:fd});
    location.reload();
}
</script>

<div class="modal fade" id="modalMV" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title" id="mvTitle">Editar</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><input type="hidden" id="mvTipo"><textarea id="mvTexto" class="form-control" rows="8"></textarea></div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" onclick="guardarMV()">Guardar</button></div>
</div></div></div>

<div class="modal fade" id="modalFinanciero" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title">Registrar Presupuesto</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form onsubmit="guardarFinanciero(event)">
            <input type="hidden" name="plan_id" id="fin-plan-id" value="<?= $id ?>">
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label small mb-0">Periodo (YYYY-MM)</label><input name="periodo" class="form-control form-control-sm" value="<?= date('Y-m') ?>"></div>
                <div class="col-md-6"><label class="form-label small mb-0">Objetivo</label><select name="objetivo_id" class="form-select form-select-sm"><option value="">General</option><?php foreach($todosObjs as $o):?><option value="<?=$o['objetivo_id']?>"><?=htmlspecialchars(substr($o['objetivo_nombre'],0,30))?></option><?php endforeach;?></select></div>
                <div class="col-md-6"><label class="form-label small mb-0">Presupuestado $</label><input name="presupuestado" class="form-control form-control-sm" type="number" step="0.01" value="0"></div>
                <div class="col-md-6"><label class="form-label small mb-0">Ejecutado $</label><input name="ejecutado" class="form-control form-control-sm" type="number" step="0.01" value="0"></div>
                <div class="col-12"><label class="form-label small mb-0">Notas</label><input name="notas" class="form-control form-control-sm"></div>
            </div>
            <div class="mt-3 text-end"><button type="submit" class="btn btn-sm btn-primary">Guardar</button></div>
        </form>
    </div>
</div></div></div>
