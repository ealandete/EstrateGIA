<?php
require_once BASE_PATH . '/lib/IndicatorManager.php';
$im = new IndicatorManager();
$indicadores = $im->getIndicadores($plan['plan_id']);
$objetivos = $this->pm->getObjetivos($plan['plan_id']);
$fases = $this->pm->getFases($plan['plan_id']);
$fasesCompletadasRep = 0; foreach ($fases as $f) if (in_array($f['fase_estado']??'',['completada','aprobada'])) $fasesCompletadasRep++;

// Datos del plan: FODA/PESTEL/Misión/Visión/Valores
$fodaData = json_decode($foda['analisis_contenido'] ?? '{}', true) ?: [];
$pestelData = json_decode($pestel['analisis_contenido'] ?? '{}', true) ?: [];
$faseMVData = json_decode($arbol[1]['fase_guia_paso_a_paso'] ?? '{}', true) ?: [];

// Misión/Visión/Valores vienen en fodaData (el builder guarda todo junto)
$mision = $fodaData['mision'] ?? $faseMVData['mision'] ?? '';
$vision = $fodaData['vision'] ?? $faseMVData['vision'] ?? '';
$valores = $fodaData['valores'] ?? $faseMVData['valores'] ?? '';
if (is_array($valores) && isset($valores[0])) $valores = $valores; elseif (is_string($valores) && !empty($valores)) $valores = explode("\n", trim($valores));
else $valores = [];

// PESTEL: viene en fodaData (se guardó con el builder de PESTEL)
$pestelDims = ['politico'=>'Político','economico'=>'Económico','social'=>'Social','tecnologico'=>'Tecnológico','ecologico'=>'Ecológico','legal'=>'Legal'];
$tienePESTEL = false;
foreach ($pestelDims as $pk => $pv) { if (!empty($fodaData[$pk] ?? [])) { $tienePESTEL = true; break; } }
// Si no hay en fodaData, probar pestelData
if (!$tienePESTEL) foreach ($pestelDims as $pk => $pv) { if (!empty($pestelData[$pk] ?? [])) { $tienePESTEL = true; break; } }

$tieneMision = !empty(trim($mision));
$tieneVision = !empty(trim($vision));
$tieneValores = !empty($valores);

$persps = ['financiera'=>'Financiera','cliente'=>'Cliente','procesos'=>'Procesos Internos','aprendizaje'=>'Aprendizaje y Crecimiento'];
$colors = ['financiera'=>'#28a745','cliente'=>'#007bff','procesos'=>'#ff9800','aprendizaje'=>'#6f42c1'];
$icons = ['financiera'=>'💰','cliente'=>'👥','procesos'=>'⚙️','aprendizaje'=>'📚'];

$indsPorObj = []; foreach ($indicadores as $ind) $indsPorObj[(int)($ind['indicador_objetivo_id']??0)][] = $ind;
$totalPresup = 0; $totalEst = 0;
$estsPorObj = [];
$estadosLabels = ['pendiente'=>'Pendiente','en_proceso'=>'En Proceso','implementada'=>'Implementada','evaluada'=>'Evaluada','cancelada'=>'Cancelada'];
foreach ($objetivos as $obj) {
    $ests = $this->pm->getEstrategias($obj['objetivo_id']);
    $estsPorObj[$obj['objetivo_id']] = $ests;
    $totalEst += count($ests);
    foreach ($ests as $e) $totalPresup += (float)($e['estrategia_presupuesto']??0);
}
$isPrint = $print ?? false;

if ($isPrint):
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= htmlspecialchars($plan['plan_nombre']) ?> - Reporte</title><link href="/assets/css/bootstrap.min.css" rel="stylesheet"><meta name="viewport" content="width=device-width,initial-scale=1.0"><style>@media print{.no-print{display:none}} body{font-size:12pt} .card{margin-bottom:1rem;border:1px solid #dee2e6} .table{font-size:10pt}</style></head><body><button class="no-print btn btn-sm btn-secondary m-2" onclick="window.print()">Imprimir</button>
<?php else: ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="fas fa-file-alt me-2"></i>Reporte Ejecutivo: <?= htmlspecialchars($plan['plan_nombre']) ?></h5>
    <div>
        <a href="?print=1" target="_blank" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-print me-1"></i>Imprimir</a>
        <a href="/planeacion/<?= $plan['plan_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
    </div>
</div>
<?php endif; ?>

<div class="container" style="max-width:1100px">
    <!-- Encabezado -->
    <div class="text-center mb-4">
        <h3><?= htmlspecialchars($plan['plan_nombre']) ?></h3>
        <p class="text-muted mb-1"><?= htmlspecialchars($empresa['empresa_nombre'] ?? '') ?></p>
        <span class="badge bg-primary"><?= htmlspecialchars($plan['metodologia_nombre']) ?></span>
        <span class="badge bg-<?= $fasesCompletadasRep>=count($fases)?'success':'warning' ?> ms-1"><?= $fasesCompletadasRep ?>/<?= count($fases) ?> fases · <?= $plan['plan_estado'] ?></span>
        <?php if ($plan['plan_fecha_inicio']): ?><div class="small text-muted mt-1"><?= date('d/m/Y', strtotime($plan['plan_fecha_inicio'])) ?> a <?= date('d/m/Y', strtotime($plan['plan_fecha_fin'])) ?></div><?php endif; ?>
    </div>

    <!-- KPIs resumen -->
    <div class="row g-3 mb-4 text-center">
        <div class="col-3"><div class="card"><div class="card-body py-2"><div style="font-size:1.8rem;font-weight:bold;color:#1a73e8"><?= count($objetivos) ?></div><small class="text-muted">Objetivos</small></div></div></div>
        <div class="col-3"><div class="card"><div class="card-body py-2"><div style="font-size:1.8rem;font-weight:bold;color:#28a745"><?= count($indicadores) ?></div><small class="text-muted">Indicadores</small></div></div></div>
        <div class="col-3"><div class="card"><div class="card-body py-2"><div style="font-size:1.8rem;font-weight:bold;color:#ff9800"><?= $totalEst ?></div><small class="text-muted">Iniciativas</small></div></div></div>
        <div class="col-3"><div class="card"><div class="card-body py-2"><div style="font-size:1.8rem;font-weight:bold;color:#6f42c1">$<?= number_format($totalPresup,0) ?></div><small class="text-muted">Presupuesto</small></div></div></div>
    </div>

    <!-- Misión / Visión / Valores -->
    <div class="card mb-4"><div class="card-header fw-bold">Identidad Estratégica</div><div class="card-body p-2">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="p-2 rounded" style="background:<?= $tieneMision?'#d4edda':'#fff3cd' ?>"><strong><?= $tieneMision ? '✓' : '⚠' ?> Misión</strong><p class="small mb-0 mt-1"><?= $tieneMision ? nl2br(htmlspecialchars($mision)) : 'Pendiente — Fase 2' ?></p></div>
            </div>
            <div class="col-md-4">
                <div class="p-2 rounded" style="background:<?= $tieneVision?'#d4edda':'#fff3cd' ?>"><strong><?= $tieneVision ? '✓' : '⚠' ?> Visión</strong><p class="small mb-0 mt-1"><?= $tieneVision ? nl2br(htmlspecialchars($vision)) : 'Pendiente — Fase 2' ?></p></div>
            </div>
            <div class="col-md-4">
                <div class="p-2 rounded" style="background:<?= $tieneValores?'#d4edda':'#fff3cd' ?>"><strong><?= $tieneValores ? '✓' : '⚠' ?> Valores</strong>
                    <?php if ($tieneValores): ?><ul class="small mb-0 mt-1 ps-3"><?php foreach ($valores as $v): ?><li><?= htmlspecialchars(is_array($v)?($v['nombre']??$v['titulo']??''):$v) ?></li><?php endforeach; ?></ul><?php else: ?><p class="small mb-0 mt-1">Pendiente</p><?php endif; ?>
                </div>
            </div>
        </div>
    </div></div>

    <!-- Análisis del Entorno: PESTEL -->
    <?php if ($tienePESTEL): ?>
    <div class="card mb-4"><div class="card-header fw-bold">Análisis del Entorno — PESTEL</div><div class="card-body p-2">
        <div class="row g-2 small">
            <?php foreach ($pestelDims as $pk => $pv): $items = $fodaData[$pk] ?? $pestelData[$pk] ?? []; if (empty($items)) continue; ?>
            <div class="col-md-6">
                <div class="p-1"><strong><?= $pv ?></strong><ul class="mb-0 ps-3"><?php foreach ($items as $item): ?><li><?= htmlspecialchars($item) ?></li><?php endforeach; ?></ul></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div></div>
    <?php endif; ?>

    <!-- Mapa Estratégico BSC -->
    <div class="card mb-4"><div class="card-header fw-bold">Mapa Estratégico — Despliegue BSC</div><div class="card-body p-2">
        <div class="text-center mb-3 small" style="font-size:0.75rem">
            <span style="color:#6f42c1">📚 Aprendizaje</span> <span class="mx-1">&#8593;</span>
            <span style="color:#ff9800">⚙️ Procesos</span> <span class="mx-1">&#8593;</span>
            <span style="color:#007bff">👥 Cliente</span> <span class="mx-1">&#8593;</span>
            <span style="color:#28a745">💰 Financiera</span>
            <div class="text-muted">Causa → Efecto</div>
        </div>
        <?php foreach ($persps as $pk => $pv): $objsPersp = array_filter($objetivos, fn($o) => ($o['objetivo_perspectiva']??'') === $pk); 
            $nInds = 0; foreach ($objsPersp as $o) $nInds += count($indsPorObj[$o['objetivo_id']] ?? []);
            $nEsts = 0; foreach ($objsPersp as $o) $nEsts += count($estsPorObj[$o['objetivo_id']] ?? []);
        ?>
        <div class="mb-2 p-2 rounded" style="border-left:4px solid <?= $colors[$pk] ?>;background:linear-gradient(90deg,<?= $colors[$pk] ?>08,transparent)">
            <strong style="color:<?= $colors[$pk] ?>"><?= $icons[$pk] ?> <?= $pv ?></strong>
            <span class="small text-muted ms-2"><?= count($objsPersp) ?> obj · <?= $nInds ?> KPIs · <?= $nEsts ?> iniciativas</span>
            <div class="row g-1 mt-1">
                <?php foreach ($objsPersp as $obj): $oid = $obj['objetivo_id']; 
                    $objInds = $indsPorObj[$oid] ?? [];
                    $objEsts = $estsPorObj[$oid] ?? [];
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="small p-2 bg-white border rounded" style="font-size:0.7rem">
                        <strong><?= htmlspecialchars(substr($obj['objetivo_nombre'],0,35)) ?></strong>
                        <div class="d-flex gap-2 mt-1">
                            <span class="badge bg-info"><?= count($objInds) ?> KPIs</span>
                            <span class="badge bg-primary"><?= count($objEsts) ?> inicia.</span>
                        </div>
                        <?php if (!empty($objInds)): $kpiNames = array_map(fn($i) => $i['indicador_nombre'], array_slice($objInds,0,3)); ?>
                        <div class="text-muted mt-1" style="font-size:0.6rem"><?= implode(', ', $kpiNames) ?><?= count($objInds)>3?'...':'' ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div></div>

    <!-- Indicadores por Perspectiva y Objetivo -->
    <div class="card mb-4"><div class="card-header fw-bold">Sistema de Indicadores — <?= count($indicadores) ?> KPIs</div>
        <div class="card-body p-2">
        <?php foreach ($persps as $pk => $pv): $objsPersp = array_filter($objetivos, fn($o) => ($o['objetivo_perspectiva']??'') === $pk);
            if (empty($objsPersp)) continue;
            $totalKpiPersp = 0; foreach ($objsPersp as $o) $totalKpiPersp += count($indsPorObj[$o['objetivo_id']] ?? []);
        ?>
        <div class="mb-3 p-2 rounded" style="border-left:4px solid <?= $colors[$pk] ?>;background:linear-gradient(90deg,<?= $colors[$pk] ?>08,transparent)">
            <h6 style="color:<?= $colors[$pk] ?>;margin-bottom:8px"><?= $icons[$pk] ?> <?= $pv ?> — <?= count($objsPersp) ?> objetivos, <?= $totalKpiPersp ?> KPIs</h6>
            <?php foreach ($objsPersp as $obj): $oid = $obj['objetivo_id']; $kpis = $indsPorObj[$oid] ?? []; ?>
            <div class="mb-2 ms-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong style="font-size:0.8rem">&#127919; <?= htmlspecialchars($obj['objetivo_nombre']) ?></strong>
                    <span class="badge bg-<?= count($kpis)>=2?'success':(count($kpis)>=1?'warning':'danger') ?>"><?= count($kpis) ?> KPI<?= count($kpis)!=1?'s':'' ?></span>
                </div>
                <?php if (!empty($kpis)): ?>
                <table class="table table-sm small ms-3 mb-0" style="font-size:0.7rem;width:95%">
                    <thead><tr><th style="width:30%">Indicador</th><th style="width:30%">Fórmula</th><th style="width:15%">Unidad</th><th style="width:15%">Meta</th><th style="width:10%">Frecuencia</th></tr></thead>
                    <tbody>
                    <?php foreach ($kpis as $kpi): ?>
                    <tr>
                        <td><?= htmlspecialchars($kpi['indicador_nombre']) ?></td>
                        <td><code style="font-size:0.65rem"><?= htmlspecialchars($kpi['indicador_formula'] ?? '') ?></code></td>
                        <td><?= htmlspecialchars($kpi['indicador_unidad_medida'] ?? '—') ?></td>
                        <td><?= ($kpi['indicador_rango_maximo']??0) > 0 ? '<strong>'.number_format($kpi['indicador_rango_maximo'],1).'</strong>' : '<span class="text-muted">—</span>' ?></td>
                        <td><?= htmlspecialchars($kpi['indicador_frecuencia_medicion'] ?? 'mensual') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="small text-muted ms-3 mb-0">Sin indicadores asignados</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- Iniciativas por Perspectiva y Objetivo -->
    <?php if ($totalEst > 0): ?>
    <div class="card mb-4"><div class="card-header fw-bold">Iniciativas Estratégicas — <?= $totalEst ?> proyectos</div>
        <div class="card-body p-2">
        <?php foreach ($persps as $pk => $pv): $objsPersp = array_filter($objetivos, fn($o) => ($o['objetivo_perspectiva']??'') === $pk);
            if (empty($objsPersp)) continue;
            $totalEstPersp = 0; foreach ($objsPersp as $o) $totalEstPersp += count($estsPorObj[$o['objetivo_id']] ?? []);
            if ($totalEstPersp === 0) continue;
        ?>
        <div class="mb-3 p-2 rounded" style="border-left:4px solid <?= $colors[$pk] ?>;background:linear-gradient(90deg,<?= $colors[$pk] ?>08,transparent)">
            <h6 style="color:<?= $colors[$pk] ?>;margin-bottom:8px"><?= $icons[$pk] ?> <?= $pv ?> — <?= $totalEstPersp ?> iniciativas</h6>
            <?php foreach ($objsPersp as $obj): $oid = $obj['objetivo_id']; $ests = $estsPorObj[$oid] ?? []; if (empty($ests)) continue; ?>
            <div class="mb-2 ms-2">
                <strong style="font-size:0.8rem">&#127919; <?= htmlspecialchars(substr($obj['objetivo_nombre'],0,40)) ?></strong>
                <table class="table table-sm small ms-3 mb-0" style="font-size:0.7rem;width:95%">
                    <thead><tr><th>Iniciativa</th><th>Tipo</th><th>Prioridad</th><th>Presupuesto</th><th>Avance</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($ests as $est): 
                        $estEstado = $est['estrategia_estado'] ?? 'pendiente';
                        $estAvance = (float)($est['estrategia_avance_porcentaje'] ?? 0);
                        $estColor = $estAvance >= 100 ? 'success' : ($estAvance >= 50 ? 'warning' : ($estAvance > 0 ? 'danger' : 'secondary'));
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($est['estrategia_nombre']) ?></strong><br><small class="text-muted"><?= htmlspecialchars(substr($est['estrategia_descripcion']??'',0,80)) ?></small></td>
                        <td><?= htmlspecialchars($est['estrategia_tipo'] ?? 'crecimiento') ?></td>
                        <td><span class="badge bg-<?= ($est['estrategia_prioridad']??'')==='critico'?'danger':(($est['estrategia_prioridad']??'')==='alto'?'warning':'secondary') ?>"><?= $est['estrategia_prioridad']??'medio' ?></span></td>
                        <td><?= $est['estrategia_presupuesto'] ? '<strong>$'.number_format($est['estrategia_presupuesto'],0).'</strong>' : '—' ?></td>
                        <td>
                            <div class="progress" style="width:70px;height:6px;display:inline-block"><div class="progress-bar bg-<?= $estColor ?>" style="width:<?= $estAvance ?>%"></div></div>
                            <small class="ms-1"><?= $estAvance ?>%</small>
                        </td>
                        <td><span class="badge bg-<?= $estadoLabels[$estEstado] === 'Implementada' || $estadoLabels[$estEstado] === 'Evaluada' ? 'success' : ($estadoLabels[$estEstado] === 'En Proceso' ? 'warning' : ($estadoLabels[$estEstado] === 'Cancelada' ? 'danger' : 'secondary')) ?>"><?= $estadosLabels[$estEstado] ?? $estEstado ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Detalle por Fases -->
    <div class="card mb-4"><div class="card-header fw-bold">Fases del Plan (<?= $fasesCompletadasRep ?>/<?= count($fases) ?>)</div>
        <div class="card-body p-2">
        <?php foreach ($fases as $i => $f): 
            $estado = $f['fase_estado'] ?? 'pendiente';
            $completada = in_array($estado, ['completada','aprobada']);
        ?>
        <div class="mb-3 p-2 rounded" style="border-left:3px solid <?= $completada?'#28a745':'#ffc107' ?>;background:#f8f9fa">
            <div class="d-flex justify-content-between align-items-center">
                <strong style="font-size:0.85rem">Fase <?= $i+1 ?>: <?= htmlspecialchars($f['fase_nombre']) ?></strong>
                <span class="badge bg-<?= $completada?'success':'warning' ?>" style="font-size:0.6rem"><?= $estado ?></span>
            </div>
            <div class="progress mt-1 mb-1" style="height:4px"><div class="progress-bar bg-<?= ($f['fase_avance_porcentaje']??0)>=100?'success':'warning' ?>" style="width:<?= $f['fase_avance_porcentaje']??0 ?>%"></div></div>
            <?php
            switch ($i) {
                case 0: echo '<div class="small mt-1">'.($tienePESTEL ? '<span class="text-success">✓ Análisis PESTEL con 6 dimensiones</span>' : '<span class="text-muted">PESTEL pendiente</span>').'</div>'; break;
                case 1: $st = ''; if ($tieneMision) $st .= '✓ Misión '; else $st .= '✗ Misión '; if ($tieneVision) $st .= '✓ Visión '; else $st .= '✗ Visión '; if ($tieneValores) $st .= '✓ Valores'; else $st .= '✗ Valores'; echo '<div class="small mt-1">'.$st.'</div>'; break;
                case 2: case 3: echo '<div class="small mt-1 text-success">✓ '.count($objetivos).' objetivos en 4 perspectivas con relaciones causa-efecto</div>'; break;
                case 4: echo '<div class="small mt-1 text-success">✓ '.count($indicadores).' KPIs definidos con fórmula y meta numérica</div>'; break;
                case 5: echo '<div class="small mt-1 text-success">✓ '.$totalEst.' iniciativas · Presupuesto: $'.number_format($totalPresup,0).'</div>'; break;
                case 6: echo '<div class="small mt-1 text-success">✓ Evaluación completada · '.$fasesCompletadasRep.'/'.count($fases).' fases ('.round($fasesCompletadasRep/max(count($fases),1)*100).'%)</div>'; break;
            }
            ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <div class="text-center text-muted small mt-3 mb-3">EstrateGIA · <?= date('d/m/Y H:i') ?></div>
</div>
<?php if ($isPrint): ?></body></html><?php endif; ?>