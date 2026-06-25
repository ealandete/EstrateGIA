<?php
require_once BASE_PATH . '/lib/IndicatorManager.php';
$im = new IndicatorManager();
$objetivos = $pm->getObjetivos($planId);
$indicadores = $im->getIndicadores($planId);
$fases = $pm->getFases($planId);
$perspColors = ['financiera'=>'#28a745','cliente'=>'#007bff','procesos'=>'#ff9800','aprendizaje'=>'#6f42c1'];
$perspNames = ['financiera'=>'Financiera','cliente'=>'Cliente','procesos'=>'Procesos','aprendizaje'=>'Aprendizaje'];

// Datos reales: indicadores, estrategias, fases
$indsPorObj = [];
foreach ($indicadores as $ind) {
    $oid = (int)($ind['indicador_objetivo_id'] ?? 0);
    $indsPorObj[$oid][] = $ind;
}
$estsPorObj = [];
foreach ($objetivos as $obj) {
    $estsPorObj[$obj['objetivo_id']] = $pm->getEstrategias($obj['objetivo_id']);
}

// Avance real del plan: fases completadas / total
$fasesCompletadas = 0; foreach ($fases as $f) { if (in_array($f['fase_estado']??'',['completada','aprobada'])) $fasesCompletadas++; }
$avancePlan = count($fases) > 0 ? round($fasesCompletadas / count($fases) * 100) : 0;

// Calcular "salud" del objetivo: cuántos KPIs e iniciativas tiene
function saludObj($oid, $indsPorObj, $estsPorObj) {
    $nInds = count($indsPorObj[$oid] ?? []);
    $nEsts = count($estsPorObj[$oid] ?? []);
    if ($nInds >= 4 && $nEsts >= 2) return ['nivel'=>'Sólido','color'=>'success'];
    if ($nInds >= 2 && $nEsts >= 1) return ['nivel'=>'En desarrollo','color'=>'warning'];
    return ['nivel'=>'Requiere atención','color'=>'danger'];
}

$totalObj = count($objetivos);
$totalInd = count($indicadores);
$totalEst = 0; $totalPresup = 0;
foreach ($estsPorObj as $ests) { $totalEst += count($ests); foreach ($ests as $e) $totalPresup += (float)($e['estrategia_presupuesto'] ?? 0); }

// Generar soluciones IA concretas para cada hallazgo
$sugerencias = [];
$kpiPool = [
    'financiera' => [
        ['nombre'=>'Margen Bruto','formula'=>'(Ingresos - Costo_Ventas) / Ingresos * 100','unidad'=>'%','meta'=>40],
        ['nombre'=>'Rotación de Activos','formula'=>'Ingresos / Activos Totales','unidad'=>'veces','meta'=>1.5],
        ['nombre'=>'Razón Corriente','formula'=>'Activo_Corriente / Pasivo_Corriente','unidad'=>'ratio','meta'=>2.0],
        ['nombre'=>'Deuda Neta / EBITDA','formula'=>'Deuda_Neta / EBITDA','unidad'=>'veces','meta'=>2.5],
        ['nombre'=>'Crecimiento de Ingresos','formula'=>'(Ingresos_Periodo - Ingresos_Anterior) / Ingresos_Anterior * 100','unidad'=>'%','meta'=>12],
        ['nombre'=>'Flujo de Caja Operativo','formula'=>'EBITDA - CAPEX - Impuestos','unidad'=>'$M','meta'=>5],
        ['nombre'=>'Margen EBITDA','formula'=>'EBITDA / Ingresos Totales * 100','unidad'=>'%','meta'=>18],
        ['nombre'=>'Retorno sobre Activos (ROA)','formula'=>'Utilidad Neta / Activos Totales * 100','unidad'=>'%','meta'=>10],
        ['nombre'=>'Utilidad por Acción','formula'=>'Utilidad Neta / Acciones en Circulación','unidad'=>'$','meta'=>3.5],
        ['nombre'=>'Cobertura de Intereses','formula'=>'EBIT / Gastos Financieros','unidad'=>'veces','meta'=>4],
        ['nombre'=>'Rotación de Inventarios','formula'=>'Costo_Ventas / Inventario_Promedio','unidad'=>'veces','meta'=>6],
        ['nombre'=>'Ciclo de Conversión de Efectivo','formula'=>'Dias_Inventario + Dias_Cobro - Dias_Pago','unidad'=>'dias','meta'=>45],
    ],
    'cliente' => [
        ['nombre'=>'Costo de Adquisición (CAC)','formula'=>'Gasto_Marketing / Nuevos_Clientes','unidad'=>'$','meta'=>50],
        ['nombre'=>'Valor de Vida del Cliente (LTV)','formula'=>'Ticket_Promedio * Compras_Año * Vida_Cliente','unidad'=>'$','meta'=>5000],
        ['nombre'=>'Tasa de Conversión','formula'=>'Clientes_Concretados / Leads_Calificados * 100','unidad'=>'%','meta'=>25],
        ['nombre'=>'Tiempo Promedio de Respuesta','formula'=>'Suma_Tiempos / Total_Solicitudes','unidad'=>'min','meta'=>30],
        ['nombre'=>'Net Promoter Score (NPS)','formula'=>'%_Promotores - %_Detractores','unidad'=>'puntos','meta'=>75],
        ['nombre'=>'Tasa de Retención','formula'=>'Clientes_Final - Clientes_Nuevos / Clientes_Inicial * 100','unidad'=>'%','meta'=>85],
        ['nombre'=>'Satisfacción (CSAT)','formula'=>'Suma_Puntuaciones / Total_Encuestas / Escala_Max * 100','unidad'=>'%','meta'=>90],
        ['nombre'=>'Participación de Mercado','formula'=>'Ventas_Empresa / Ventas_Total_Sector * 100','unidad'=>'%','meta'=>35],
        ['nombre'=>'Tasa de Cancelación (Churn)','formula'=>'Clientes_Perdidos / Clientes_Inicial * 100','unidad'=>'%','meta'=>5],
        ['nombre'=>'Ticket Promedio','formula'=>'Ingresos_Totales / Numero_Transacciones','unidad'=>'$','meta'=>250],
        ['nombre'=>'Clientes Activos','formula'=>'Conteo de clientes con transacción en últimos 90 días','unidad'=>'clientes','meta'=>5000],
        ['nombre'=>'Engagement en Redes','formula'=>'Interacciones / Publicaciones * 100','unidad'=>'%','meta'=>8],
    ],
    'procesos' => [
        ['nombre'=>'Cumplimiento de Entregas (OTIF)','formula'=>'Pedidos_OK_a_Tiempo / Total_Pedidos * 100','unidad'=>'%','meta'=>95],
        ['nombre'=>'Capacidad Utilizada','formula'=>'Produccion_Real / Capacidad_Instalada * 100','unidad'=>'%','meta'=>85],
        ['nombre'=>'Costo por Unidad','formula'=>'Costos_Produccion / Unidades_Producidas','unidad'=>'$','meta'=>25],
        ['nombre'=>'Tasa de Automatización','formula'=>'Tareas_Auto / Total_Tareas * 100','unidad'=>'%','meta'=>60],
        ['nombre'=>'% Procesos Digitalizados','formula'=>'Procesos_Digitalizados / Total_Procesos * 100','unidad'=>'%','meta'=>80],
        ['nombre'=>'Eficiencia del Proceso','formula'=>'Tiempo_Estandar / Tiempo_Real * 100','unidad'=>'%','meta'=>85],
        ['nombre'=>'Tasa de Defectos','formula'=>'Unidades_Defectuosas / Total_Unidades * 100','unidad'=>'%','meta'=>2],
        ['nombre'=>'Tiempo de Ciclo Promedio','formula'=>'Fecha_Entrega - Fecha_Inicio','unidad'=>'dias','meta'=>15],
        ['nombre'=>'Tasa de Re-trabajo','formula'=>'Horas_Re-trabajo / Horas_Productivas * 100','unidad'=>'%','meta'=>3],
        ['nombre'=>'Disponibilidad de Sistemas','formula'=>'Tiempo_Operativo / Tiempo_Total * 100','unidad'=>'%','meta'=>99.5],
        ['nombre'=>'Índice de Calidad','formula'=>'Unidades_Conformes / Total_Unidades * 100','unidad'=>'%','meta'=>98],
        ['nombre'=>'Rotación de Proveedores','formula'=>'Compras_Anuales / Cuentas_Pagar_Promedio','unidad'=>'veces','meta'=>8],
    ],
    'aprendizaje' => [
        ['nombre'=>'Tasa de Promoción Interna','formula'=>'Ascensos_Internos / Vacantes_Cubiertas * 100','unidad'=>'%','meta'=>60],
        ['nombre'=>'Índice de Innovación','formula'=>'Ideas_Implementadas / Ideas_Recibidas * 100','unidad'=>'%','meta'=>30],
        ['nombre'=>'Inversión en Capacitación','formula'=>'Presupuesto_Cap / Total_Empleados','unidad'=>'$/año','meta'=>2000],
        ['nombre'=>'Tiempo de Cobertura de Vacantes','formula'=>'Fecha_Contratacion - Fecha_Apertura','unidad'=>'dias','meta'=>30],
        ['nombre'=>'Rotación de Personal','formula'=>'Bajas_Periodo / Promedio_Empleados * 100','unidad'=>'%','meta'=>10],
        ['nombre'=>'% Personal Certificado','formula'=>'Personal_Certificado / Total_Personal * 100','unidad'=>'%','meta'=>60],
        ['nombre'=>'Horas de Formación','formula'=>'Total_Horas_Formacion / Total_Empleados','unidad'=>'horas/año','meta'=>40],
        ['nombre'=>'Clima Laboral','formula'=>'Puntuacion_Promedio_Encuesta / Escala_Max * 100','unidad'=>'%','meta'=>85],
        ['nombre'=>'Productividad por Empleado','formula'=>'Ingresos / Total_Empleados','unidad'=>'$/empleado','meta'=>85000],
        ['nombre'=>'Tasa de Ausentismo','formula'=>'Dias_Ausencia / Dias_Laborables * 100','unidad'=>'%','meta'=>3],
        ['nombre'=>'Satisfacción con Formación','formula'=>'Puntuacion_Encuesta_Formacion / Escala_Max * 100','unidad'=>'%','meta'=>85],
        ['nombre'=>'Adopción de Nuevas Herramientas','formula'=>'Usuarios_Activos_Nueva_Herramienta / Total_Usuarios * 100','unidad'=>'%','meta'=>75],
    ],
];
$iniPool = [
    ['nombre'=>'Programa de optimización de KPIs','tipo'=>'ofensiva','prioridad'=>'alto','presupuesto'=>35000,'descripcion'=>'Establecer línea base, definir metas y responsables para los KPIs faltantes de este objetivo.'],
    ['nombre'=>'Plan de medición y seguimiento','tipo'=>'ofensiva','prioridad'=>'medio','presupuesto'=>20000,'descripcion'=>'Implementar dashboard de seguimiento con los KPIs del objetivo y revisiones quincenales.'],
    ['nombre'=>'Iniciativa de mejora operativa','tipo'=>'ofensiva','prioridad'=>'alto','presupuesto'=>45000,'descripcion'=>'Contratar consultoría especializada para diseñar e implementar mejoras en este objetivo en 6 meses.'],
    ['nombre'=>'Proyecto de transformación del objetivo','tipo'=>'crecimiento','prioridad'=>'alto','presupuesto'=>55000,'descripcion'=>'Rediseñar procesos, asignar equipo dedicado y establecer metas trimestrales para este objetivo.'],
];

// Objetivos con pocos KPIs: sugerir KPIs concretos y únicos por objetivo
$kpiPoolUsado = ['financiera'=>[],'cliente'=>[],'procesos'=>[],'aprendizaje'=>[]];
foreach ($objetivos as $obj) {
    $oid = $obj['objetivo_id'];
    $nInds = count($indsPorObj[$oid] ?? []);
    $persp = $obj['objetivo_perspectiva'] ?? 'financiera';
    if ($nInds < 2) {
        $pool = $kpiPool[$persp] ?? $kpiPool['financiera'];
        // Seleccionar KPIs no usados aún para esta perspectiva
        $disponibles = [];
        foreach ($pool as $i => $kpi) {
            if (!in_array($kpi['nombre'], $kpiPoolUsado[$persp])) $disponibles[] = $kpi;
        }
        if (empty($disponibles)) { $kpiPoolUsado[$persp] = []; $disponibles = $pool; }
        $cant = min(2 - $nInds, count($disponibles));
        $keys = array_rand($disponibles, $cant);
        if (!is_array($keys)) $keys = [$keys];
        $kpiSug = [];
        foreach ($keys as $k) {
            $kpiSug[] = $disponibles[$k];
            $kpiPoolUsado[$persp][] = $disponibles[$k]['nombre'];
        }
        $nombresKPI = implode(', ', array_column($kpiSug, 'nombre'));
        $sugerencias[] = [
            'titulo' => htmlspecialchars(substr($obj['objetivo_nombre'],0,35)),
            'problema' => 'Tiene solo '.$nInds.' KPI(s). Necesita al menos 2.',
            'solucion' => 'Crear: <strong>'.$nombresKPI.'</strong>',
            'tipo' => 'critico',
            'perspectiva' => $persp,
            'accion' => 'crear_kpis',
            'obj_id' => $oid,
            'kpis' => $kpiSug,
        ];
    }
}

// Objetivos sin iniciativas: sugerir iniciativas concretas
foreach ($objetivos as $obj) {
    $oid = $obj['objetivo_id'];
    $nEsts = count($estsPorObj[$oid] ?? []);
    $persp = $obj['objetivo_perspectiva'] ?? 'financiera';
    if ($nEsts < 1) {
        $ini = $iniPool[array_rand($iniPool)];
        $sugerencias[] = [
            'titulo' => htmlspecialchars(substr($obj['objetivo_nombre'],0,35)),
            'problema' => 'Sin iniciativas asignadas.',
            'solucion' => 'Crear: <strong>'.$ini['nombre'].'</strong> ($'.number_format($ini['presupuesto'],0).') — '.$ini['descripcion'],
            'tipo' => 'critico',
            'perspectiva' => $persp,
            'accion' => 'crear_iniciativa',
            'obj_id' => $oid,
            'iniciativa' => $ini,
        ];
    }
}

// Balance de perspectivas
foreach (['financiera','cliente','procesos','aprendizaje'] as $pk) {
    $objsPersp = array_filter($objetivos, fn($o) => ($o['objetivo_perspectiva']??'') === $pk);
    $totalKPIs = 0; foreach ($objsPersp as $o) $totalKPIs += count($indsPorObj[$o['objetivo_id']] ?? []);
    if ($totalKPIs < 4 && !empty($objsPersp)) {
        $pool = $kpiPool[$pk] ?? $kpiPool['financiera'];
        $nombres = implode(', ', array_column(array_slice($pool,0,3), 'nombre'));
        $sugerencias[] = [
            'titulo' => 'Perspectiva '.$perspNames[$pk].' desbalanceada',
            'problema' => 'Solo tiene '.$totalKPIs.' KPIs en total. Un BSC balanceado necesita al menos 4 por perspectiva.',
            'solucion' => 'Sugiero agregar: <strong>'.$nombres.'</strong>',
            'tipo' => 'mejora',
            'perspectiva' => $pk,
            'accion' => 'crear_kpis_perspectiva',
            'kpis' => array_slice($pool, 0, 3),
        ];
    }
}
?>
<div class="d-flex justify-content-between mb-2">
    <h5><i class="fas fa-chart-line me-2"></i>Evaluación y Ajuste</h5>
    <div>
        <small class="text-muted">Avance del plan: </small>
        <span class="badge bg-<?= $avancePlan >= 80 ? 'success' : ($avancePlan >= 50 ? 'warning' : 'danger') ?>"><?= $avancePlan ?>%</span>
        <small class="text-muted ms-2">(<?= $fasesCompletadas ?>/<?= count($fases) ?> fases)</small>
    </div>
</div>

<div class="alert alert-primary border-start border-4 border-primary mb-3 d-flex align-items-center gap-2">
    <i class="fas fa-brain" style="font-size:1.5rem;color:#1a73e8"></i>
    <div style="flex:1">
        <strong>Asistente de Evaluación IA</strong>
        <p class="mb-0 small">Analiza el estado actual del plan y sugiere mejoras concretas para cada objetivo.</p>
    </div>
    <button type="button" class="btn btn-purple" onclick="sugerirMejorasIA()">&#129504; Sugerir mejoras</button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-box text-center" onclick="location.href='/planeacion/<?= $planId ?>#seccion-objetivos'" style="cursor:pointer">
            <div class="card-box-body">
                <div style="font-size:2rem;font-weight:bold;color:#1a73e8"><?= $totalObj ?></div>
                <small class="text-muted">Objetivos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-box text-center" onclick="location.href='/workbench/<?= $planId ?>/<?= $fases[4]['fase_id'] ?? '' ?>'" style="cursor:pointer">
            <div class="card-box-body">
                <div style="font-size:2rem;font-weight:bold;color:#28a745"><?= $totalInd ?></div>
                <small class="text-muted">KPIs</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-box text-center" onclick="location.href='/workbench/<?= $planId ?>/<?= $fases[5]['fase_id'] ?? '' ?>'" style="cursor:pointer">
            <div class="card-box-body">
                <div style="font-size:2rem;font-weight:bold;color:#ff9800"><?= $totalEst ?></div>
                <small class="text-muted">Iniciativas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-box text-center">
            <div class="card-box-body">
                <div style="font-size:2rem;font-weight:bold;color:#6f42c1">$<?= number_format($totalPresup, 0) ?></div>
                <small class="text-muted">Presupuesto</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <?php foreach ($perspColors as $pk => $color): 
            $objsPersp = array_filter($objetivos, fn($o) => ($o['objetivo_perspectiva'] ?? '') === $pk);
            if (empty($objsPersp)) continue;
        ?>
        <div class="card-box mb-3" style="border-left:4px solid <?= $color ?>">
            <div class="card-box-header">
                <span style="color:<?= $color ?>;font-weight:bold">&#9679; <?= $perspNames[$pk] ?></span>
                <small class="text-muted ms-2"><?= count($objsPersp) ?> objetivos</small>
            </div>
            <div class="card-box-body p-2">
                <div class="table-responsive">
                <table class="table table-sm small mb-0" style="font-size:0.75rem">
                    <thead><tr><th>Objetivo</th><th>KPIs</th><th>Iniciativas</th><th>Salud</th><th>Acción</th></tr></thead>
                    <tbody>
                    <?php foreach ($objsPersp as $obj): 
                        $oid = $obj['objetivo_id'];
                        $nInds = count($indsPorObj[$oid] ?? []);
                        $nEsts = count($estsPorObj[$oid] ?? []);
                        $salud = saludObj($oid, $indsPorObj, $estsPorObj);
                        $accion = '';
                        $accionUrl = '';
                        if ($nInds < 2) {
                            $faseIdInd = $fases[4]['fase_id'] ?? '';
                            $accion = 'Faltan KPIs';
                            $accionUrl = '/workbench/'.$planId.'/'.$faseIdInd.'?perspectivas='.($obj['objetivo_perspectiva']??'financiera').'&obj_id='.$oid.'&auto_open=1';
                        } elseif ($nEsts < 1) {
                            $faseIdIni = $fases[5]['fase_id'] ?? '';
                            $accion = 'Sin iniciativas';
                            $accionUrl = '/workbench/'.$planId.'/'.$faseIdIni.'?perspectivas='.($obj['objetivo_perspectiva']??'financiera').'&obj_id='.$oid.'&auto_open=1';
                        }
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars(substr($obj['objetivo_nombre'], 0, 45)) ?></strong></td>
                        <td><span class="badge bg-<?= $nInds >= 2 ? 'info' : 'secondary' ?>"><?= $nInds ?></span></td>
                        <td><span class="badge bg-<?= $nEsts >= 1 ? 'primary' : 'secondary' ?>"><?= $nEsts ?></span></td>
                        <td><span class="badge bg-<?= $salud['color'] ?>" style="cursor:pointer" onclick="editarObjetivoDesdeEval(<?= htmlspecialchars(json_encode($obj)) ?>)" title="Ajustar objetivo"><?= $salud['nivel'] ?></span></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary p-0 px-1" onclick="editarObjetivoDesdeEval(<?= htmlspecialchars(json_encode($obj)) ?>)" title="Ajustar meta" style="font-size:0.65rem">Ajustar</button>
                            <?php if ($accion): ?>
                            <a href="<?= $accionUrl ?>" class="badge bg-warning text-dark text-decoration-none ms-1" style="font-size:0.6rem"><?= $accion ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="col-md-4">
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-lightbulb me-2" style="color:#ffc107"></i>Recomendaciones</div>
            <div class="card-box-body p-2 small">
                <p class="mb-1"><strong>Próximo ciclo:</strong></p>
                <ul class="mb-0 ps-3" style="font-size:0.75rem">
                    <li>Objetivos en <span class="badge bg-danger">Requiere atención</span>: haz clic en la etiqueta para ajustarlos</li>
                    <li><span class="badge bg-warning text-dark">Faltan KPIs</span>: clic directo para crear el indicador</li>
                    <li><span class="badge bg-warning text-dark">Sin iniciativas</span>: clic directo para crear la iniciativa</li>
                    <li>Asegura al menos 2 KPIs y 1 iniciativa por objetivo</li>
                    <?php if ($avancePlan >= 80): ?>
                    <li>Considera subir la ambición de metas para objetivos sólidos</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-bullseye me-2" style="color:#1a73e8"></i>Ajustes rápidos</div>
            <div class="card-box-body p-2 small">
                <p class="text-muted mb-2">Haz clic en <strong>Ajustar</strong> junto a cualquier objetivo para modificar su nombre o perspectiva.</p>
                <span class="badge bg-info"><?= $totalObj ?> objetivos</span>
                <span class="badge bg-success ms-1"><?= $avancePlan ?>% avance plan</span>
            </div>
        </div>
    </div>
</div>

<script>
var objEditando = null;
function editarObjetivoDesdeEval(obj) {
    objEditando = obj;
    document.getElementById('eval-obj-nombre').value = obj.objetivo_nombre || '';
    document.getElementById('eval-obj-persp').value = obj.objetivo_perspectiva || 'financiera';
    document.getElementById('eval-modal-obj-id').textContent = obj.objetivo_id;
    new bootstrap.Modal(document.getElementById('modalAjusteObj')).show();
}

async function guardarAjusteObj() {
    if (!objEditando) return;
    var id = objEditando.objetivo_id;
    var nombre = document.getElementById('eval-obj-nombre').value.trim();
    var persp = document.getElementById('eval-obj-persp').value;
    if (!nombre) { alert('Nombre requerido'); return; }
    try {
        var r = await fetch('/tools/edit-objetivo', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'obj_id='+id+'&obj_nombre='+encodeURIComponent(nombre)+'&perspectiva='+persp});
        var d = await r.json();
        if (d.success) {
            var modal = bootstrap.Modal.getInstance(document.getElementById('modalAjusteObj'));
            if (modal) modal.hide();
            location.reload();
        } else { alert('Error al ajustar'); }
    } catch(e) { alert('Error de conexión'); }
}

var mejorasIA = <?= json_encode($sugerencias, JSON_UNESCAPED_UNICODE) ?>;
var filterPersp = '';
function sugerirMejorasIA() {
    filterPersp = '';
    renderLista();
    new bootstrap.Modal(document.getElementById('modalMejoras')).show();
}

function setFilter(persp) {
    filterPersp = (filterPersp === persp) ? '' : persp;
    document.querySelectorAll('#mejoraFilters .btn').forEach(function(b){ b.classList.remove('active'); });
    if (filterPersp) document.getElementById('filt-'+filterPersp).classList.add('active');
    renderLista();
}

function renderLista() {
    var html = '';
    var colors = {financiera:'#28a745',cliente:'#007bff',procesos:'#ff9800',aprendizaje:'#6f42c1'};
    mejorasIA.forEach(function(m, idx) {
        if (filterPersp && m.perspectiva !== filterPersp) return;
        html += '<div class="d-flex align-items-start gap-2 mb-2 p-2 rounded" style="background:#f8f9fa;font-size:0.8rem;border-left:3px solid '+ (colors[m.perspectiva]||'#ccc') +'">';
        html += '<input type="checkbox" class="form-check-input mt-1 mejora-check" data-idx="'+idx+'" checked style="flex-shrink:0">';
        html += '<div style="flex:1">';
        html += '<span class="badge bg-'+ (m.tipo==='critico'?'danger':(m.tipo==='mejora'?'warning':'info')) +' me-1">'+ (m.tipo==='critico'?'Crítico':(m.tipo==='mejora'?'Mejora':'Info')) +'</span>';
        if (m.perspectiva) html += '<span style="color:'+colors[m.perspectiva]+';font-size:0.7rem">&#9679; '+m.perspectiva+'</span>';
        html += '<div class="fw-bold">'+m.titulo+'</div>';
        html += '<div class="text-danger small">'+m.problema+'</div>';
        html += '<div class="text-success small">'+m.solucion+'</div>';
        html += '</div></div>';
    });
    document.getElementById('mejoraLista').innerHTML = html || '<p class="text-center text-muted py-3">Sin sugerencias para este filtro</p>';
    document.getElementById('mejoraTotal').textContent = document.querySelectorAll('.mejora-check').length;
}

function toggleTodos() {
    var all = document.getElementById('mejoraToggle').checked;
    document.querySelectorAll('.mejora-check:not([style*="display: none"])').forEach(function(cb){ cb.checked = all; });
}

async function aplicarTodas() {
    var checks = document.querySelectorAll('.mejora-check:checked');
    if (checks.length === 0) { alert('Selecciona al menos una sugerencia'); return; }
    var btn = document.getElementById('btnMejoraApplyAll');
    btn.disabled = true; btn.textContent = 'Aplicando...';
    // Cargar existentes con par (nombre + objetivo_id) para evitar falsos duplicados
    var existingPairs = [];
    <?php foreach ($indicadores as $ind): ?>existingPairs.push(<?= json_encode(strtolower($ind['indicador_nombre']).'|'.(int)($ind['indicador_objetivo_id']??0), JSON_UNESCAPED_UNICODE) ?>);<?php endforeach; ?>
    var total = checks.length; var ok = 0; var skipped = 0;
    for (var c = 0; c < total; c++) {
        var idx = parseInt(checks[c].getAttribute('data-idx'));
        var m = mejorasIA[idx];
        try {
            if (m.accion === 'crear_kpis' && m.kpis) {
                for (var i = 0; i < m.kpis.length; i++) {
                    var kpi = m.kpis[i];
                    var pair = kpi.nombre.toLowerCase() + '|' + m.obj_id;
                    if (existingPairs.indexOf(pair) !== -1) { skipped++; continue; }
                    await fetch('/tools/save-indicador', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
                        body:'data='+encodeURIComponent(JSON.stringify({
                            indicador_plan_id: <?= $planId ?>, indicador_objetivo_id: m.obj_id,
                            indicador_nombre: kpi.nombre, indicador_formula: kpi.formula,
                            indicador_unidad_medida: kpi.unidad, indicador_rango_maximo: kpi.meta,
                            indicador_frecuencia_medicion: 'mensual', indicador_categoria_id: 1
                        }))});
                    existingPairs.push(pair); ok++;
                }
            } else if (m.accion === 'crear_iniciativa' && m.iniciativa) {
                await fetch('/tools/save-estrategia', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:'data='+encodeURIComponent(JSON.stringify({
                        estrategia_objetivo_id: m.obj_id, estrategia_nombre: m.iniciativa.nombre,
                        estrategia_descripcion: m.iniciativa.descripcion, estrategia_tipo: m.iniciativa.tipo,
                        estrategia_prioridad: m.iniciativa.prioridad, estrategia_presupuesto: m.iniciativa.presupuesto
                    }))});
                ok++;
            } else if (m.accion === 'crear_kpis_perspectiva' && m.kpis) {
                var objId = null;
                <?php foreach ($objetivos as $obj): ?>
                if ('<?= $obj['objetivo_perspectiva'] ?>' === m.perspectiva && !objId) objId = <?= $obj['objetivo_id'] ?>;
                <?php endforeach; ?>
                for (var i = 0; i < m.kpis.length; i++) {
                    var kpi = m.kpis[i];
                    var pair = kpi.nombre.toLowerCase() + '|' + objId;
                    if (existingPairs.indexOf(pair) !== -1) { skipped++; continue; }
                    await fetch('/tools/save-indicador', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
                        body:'data='+encodeURIComponent(JSON.stringify({
                            indicador_plan_id: <?= $planId ?>, indicador_objetivo_id: objId,
                            indicador_nombre: kpi.nombre, indicador_formula: kpi.formula,
                            indicador_unidad_medida: kpi.unidad, indicador_rango_maximo: kpi.meta,
                            indicador_frecuencia_medicion: 'mensual', indicador_categoria_id: 1
                        }))});
                    existingPairs.push(pair); ok++;
                }
            }
            btn.textContent = 'Aplicando ' + (c+1) + '/' + total + '...';
        } catch(e) { console.error(e); }
    }
    var modal = bootstrap.Modal.getInstance(document.getElementById('modalMejoras'));
    if (modal) modal.hide();
    var msg = ok + ' creados';
    if (skipped > 0) msg += ', ' + skipped + ' ya existían';
    alert(msg + '. Recargando...');
    location.reload();
}

function aplicarMejora() { aplicarTodas(); }
</script>

<!-- Modal Mejoras IA -->
<div class="modal fade" id="modalMejoras" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">&#129504; Mejoras sugeridas (<span id="mejoraTotal">0</span>)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-header py-1 bg-light d-flex gap-2 align-items-center">
                <div class="form-check form-check-inline mb-0 small">
                    <input class="form-check-input" type="checkbox" id="mejoraToggle" checked onchange="toggleTodos()">
                    <label class="form-check-label" for="mejoraToggle">Todas</label>
                </div>
                <div id="mejoraFilters" class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-success" id="filt-financiera" onclick="setFilter('financiera')" style="font-size:0.65rem">&#9679; Financiera</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="filt-cliente" onclick="setFilter('cliente')" style="font-size:0.65rem">&#9679; Cliente</button>
                    <button type="button" class="btn btn-sm btn-outline-warning" id="filt-procesos" onclick="setFilter('procesos')" style="font-size:0.65rem">&#9679; Procesos Internos</button>
                    <button type="button" class="btn btn-sm btn-outline-purple" id="filt-aprendizaje" onclick="setFilter('aprendizaje')" style="font-size:0.65rem">&#9679; Aprendizaje y Crecimiento</button>
                </div>
                <small class="text-muted ms-auto">Marca las que quieras aplicar</small>
            </div>
            <div class="modal-body" style="max-height:60vh;overflow-y:auto" id="mejoraLista"></div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-success" id="btnMejoraApplyAll" onclick="aplicarTodas()">&#10003; Aplicar seleccionadas</button>
            </div>
        </div>
    </div>
</div>
