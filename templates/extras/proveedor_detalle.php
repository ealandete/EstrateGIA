<?php
$evalOk = $_GET['eval_ok'] ?? null;
$moduloContexto = 'evaluación de proveedores tipo ' . $prov['prov_tipo'];

// Checklist items por tipo con pesos (Pareto)
$checklistPorTipo = [
    'medicamentos' => [
        ['id'=>'bpa','item'=>'Cumplimiento BPA/BPD','peso'=>25,'desc'=>'Verificar certificación vigente, condiciones almacenamiento, registros INVIMA'],
        ['id'=>'cadena','item'=>'Cadena de Frío','peso'=>20,'desc'=>'Monitoreo temperatura, calibración equipos, registros continua, plan contingencia'],
        ['id'=>'calidad','item'=>'Calidad del Producto','peso'=>20,'desc'=>'Lotes consistentes, análisis microbiológico, fecha caducidad, empaque íntegro'],
        ['id'=>'entrega','item'=>'Oportunidad Entrega','peso'=>15,'desc'=>'Cumplimiento fechas pactadas, estado del transporte, documentación completa'],
        ['id'=>'precio','item'=>'Competitividad Precio','peso'=>10,'desc'=>'Comparación mercado, estabilidad precios, descuentos por volumen'],
        ['id'=>'servicio','item'=>'Servicio Post-venta','peso'=>10,'desc'=>'Atención reclamos, devoluciones, soporte técnico, disponibilidad contacto'],
    ],
    'insumos' => [
        ['id'=>'calidad','item'=>'Calidad de Insumos','peso'=>30,'desc'=>'Especificaciones técnicas, certificaciones, homogeneidad entre lotes'],
        ['id'=>'entrega','item'=>'Oportunidad Entrega','peso'=>25,'desc'=>'Tiempos de entrega, cumplimiento cantidades, estado llegada'],
        ['id'=>'precio','item'=>'Precio','peso'=>20,'desc'=>'Comparativo sectorial, estabilidad, relación costo/beneficio'],
        ['id'=>'servicio','item'=>'Servicio','peso'=>15,'desc'=>'Atención requerimientos, flexibilidad pedidos urgentes, comunicación'],
        ['id'=>'empaque','item'=>'Estado Empaque','peso'=>10,'desc'=>'Protección del producto, identificación, trazabilidad lote'],
    ],
    'equipos' => [
        ['id'=>'estado','item'=>'Estado del Equipo','peso'=>25,'desc'=>'Funcionamiento, calibración, vida útil remanente, historial mantenimiento'],
        ['id'=>'soporte','item'=>'Mantenimiento y Soporte','peso'=>25,'desc'=>'Tiempo respuesta, disponibilidad repuestos, personal calificado, contratos'],
        ['id'=>'garantia','item'=>'Cumplimiento Garantía','peso'=>20,'desc'=>'Cobertura, tiempos atención, exclusiones claras, procesos definidos'],
        ['id'=>'capacitacion','item'=>'Capacitación','peso'=>15,'desc'=>'Entrenamiento usuarios, manuales, actualización técnica'],
        ['id'=>'precio','item'=>'Precio','peso'=>15,'desc'=>'Comparativo, financiación, costo total propiedad (TCO)'],
    ],
    'servicios' => [
        ['id'=>'calidad','item'=>'Calidad del Servicio','peso'=>30,'desc'=>'Cumplimiento especificaciones, profesionalismo, resultados obtenidos'],
        ['id'=>'plazos','item'=>'Cumplimiento Plazos','peso'=>25,'desc'=>'Entregas a tiempo, hitos cumplidos, cronograma real vs planeado'],
        ['id'=>'personal','item'=>'Idoneidad del Personal','peso'=>20,'desc'=>'Competencias, certificaciones, presentación, rotación equipo asignado'],
        ['id'=>'precio','item'=>'Precio','peso'=>15,'desc'=>'Competitividad, facturación clara, costos ocultos, ajustes'],
        ['id'=>'respuesta','item'=>'Tiempo de Respuesta','peso'=>10,'desc'=>'Velocidad atención solicitudes, disponibilidad contacto, escalamiento'],
    ],
    'consultoria' => [
        ['id'=>'conocimiento','item'=>'Conocimiento Técnico','peso'=>30,'desc'=>'Dominio del tema, actualización, referencias, publicaciones'],
        ['id'=>'metodologia','item'=>'Metodología','peso'=>25,'desc'=>'Estructura trabajo, herramientas, adaptabilidad, documentación'],
        ['id'=>'resultados','item'=>'Resultados','peso'=>25,'desc'=>'Entregables, impacto medible, cumplimiento objetivos, valor agregado'],
        ['id'=>'comunicacion','item'=>'Comunicación','peso'=>10,'desc'=>'Claridad, frecuencia, informes, manejo objeciones'],
        ['id'=>'costo','item'=>'Costo/Beneficio','peso'=>10,'desc'=>'ROI del servicio, comparación alternativas, inversión vs retorno'],
    ],
];

$checklist = $checklistPorTipo[$prov['prov_tipo']] ?? $checklistPorTipo['insumos'];

// Historial de checklist evaluations previas
$core = EstrateGiaCore::getInstance();
$historialEvals = $core->fetchAll("SELECT * FROM cal_proveedor_evaluaciones WHERE eval_proveedor_id=:id ORDER BY eval_fecha DESC", ['id'=>$prov['prov_id']]);

// Calcular scores históricos para comparación
$historicoScores = [];
foreach ($historialEvals as $ev) {
    $historicoScores[] = [
        'fecha' => $ev['eval_fecha'],
        'total' => $ev['eval_total'],
    ];
}
?>

<?php if ($evalOk): ?>
<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Checklist de evaluación guardado. Próxima evaluación: <?= date('d/m/Y', strtotime('+6 months')) ?></div>
<?php endif; ?>

<nav class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="/proveedores">Proveedores</a></li><li class="breadcrumb-item active"><?= htmlspecialchars($prov['prov_nombre']) ?></li></ol></nav>

<div class="row g-4">
    <div class="col-md-7">
        <!-- CHECKLIST DE EVALUACIÓN GUIADA -->
        <div class="card-box">
            <div class="card-box-header d-flex justify-content-between">
                <span><i class="fas fa-clipboard-check me-2" style="color:#28a745"></i>Lista de Chequeo · <?= ucfirst($prov['prov_tipo']) ?></span>
                <small class="text-muted">Evalúa cada ítem de 0 a 100</small>
            </div>
            <div class="card-box-body">
                <form method="POST" action="/proveedores/evaluar" id="checklistForm">
                    <input type="hidden" name="proveedor_id" value="<?= $prov['prov_id'] ?>">
                    
                    <div class="alert alert-info small mb-3">
                        <strong><i class="fas fa-info-circle me-1"></i>Guía:</strong> Califica cada criterio según la evidencia objetiva. 
                        <90 = Excelente | 70-89 = Bueno | 50-69 = Aceptable | <50 = Deficiente
                    </div>

                    <?php foreach ($checklist as $item): ?>
                    <div class="card mb-2 checklist-item" style="border-left:4px solid #1a73e8">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong><?= $item['item'] ?></strong>
                                    <span class="badge bg-primary ms-2">Peso: <?= $item['peso'] ?>%</span>
                                </div>
                                <div class="text-end" style="min-width:80px">
                                    <span class="fs-5 fw-bold score-display" id="score_<?= $item['id'] ?>">—</span>
                                </div>
                            </div>
                            <small class="text-muted d-block mb-2"><?= $item['desc'] ?></small>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger" style="font-size:0.6rem">0</span>
                                <input type="range" name="criterio[<?= $item['id'] ?>]" class="form-range checklist-slider" 
                                    data-item="<?= $item['id'] ?>" min="0" max="100" value="80" 
                                    oninput="updateScore(this)" style="flex:1">
                                <span class="badge bg-success" style="font-size:0.6rem">100</span>
                                <input type="number" name="criterio_val[<?= $item['id'] ?>]" class="form-control form-control-sm" 
                                    style="width:70px" value="80" min="0" max="100"
                                    onchange="this.previousElementSibling.previousElementSibling.previousElementSibling.value=this.value;updateScore(this.previousElementSibling.previousElementSibling.previousElementSibling)">
                            </div>
                            <div class="mt-1">
                                <label class="form-label small mb-0">Evidencia encontrada:</label>
                                <input type="text" name="evidencia[<?= $item['id'] ?>]" class="form-control form-control-sm" placeholder="¿Qué evidenció? Ej: certificado vigente, acta de visita...">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Puntaje ponderado -->
                    <div class="card mt-3" style="background:#e8f5e9;border:2px solid #28a745">
                        <div class="card-body text-center">
                            <h5 class="mb-0">Puntaje Ponderado: <span id="ponderadoTotal" class="fs-3 fw-bold text-success">—</span></h5>
                            <small class="text-muted" id="clasificacion"></small>
                        </div>
                    </div>

                    <label class="form-label small mt-3">Observaciones generales</label>
                    <textarea name="observaciones" class="form-control form-control-sm" rows="2"></textarea>

                    <button type="submit" class="btn btn-success btn-lg w-100 mt-3">
                        <i class="fas fa-save me-2"></i>Guardar Evaluación y Programar Próxima
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <!-- PARETO de criterios -->
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-chart-bar me-2"></i>Pareto de Criterios (Pesos)</div>
            <div class="card-box-body"><canvas id="paretoChart" height="150"></canvas></div>
        </div>

        <!-- Histórico comparativo -->
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-chart-line me-2"></i>Comparativo Histórico</div>
            <div class="card-box-body"><canvas id="historicoChart" height="100"></canvas></div>
        </div>

        <!-- Trazabilidad -->
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-history me-2"></i>Últimas Evaluaciones</div>
            <div class="card-box-body p-0"><table class="table-box small">
                <thead><tr><th>Fecha</th><th>Total</th><th></th></tr></thead>
                <tbody>
                <?php foreach (array_slice($historialEvals,0,8) as $ev): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($ev['eval_fecha'])) ?></td>
                    <td><span class="badge bg-<?= $ev['eval_total']>=80?'success':($ev['eval_total']>=60?'warning':'danger') ?>"><?= $ev['eval_total'] ?>%</span></td>
                    <td><small><?= $ev['eval_proxima'] ?? '+6 meses' ?></small></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>

        <!-- Panel IA -->
        <?php require BASE_PATH . '/templates/hse/ia_panel.php'; ?>
    </div>
</div>

<script>
const pesos = <?= json_encode(array_column($checklist, 'peso', 'id')) ?>;
const pesosArray = <?= json_encode(array_values(array_column($checklist, 'peso'))) ?>;
const labelsPareto = <?= json_encode(array_values(array_column($checklist, 'item'))) ?>;

function updateScore(slider) {
    var id = slider.dataset.item;
    var val = parseInt(slider.value);
    document.getElementById('score_' + id).textContent = val + '%';
    document.getElementById('score_' + id).style.color = val >= 90 ? '#28a745' : (val >= 70 ? '#ffc107' : '#dc3545');

    // Calcular ponderado total
    var total = 0;
    document.querySelectorAll('.checklist-slider').forEach(function(s) {
        var itemId = s.dataset.item;
        var itemVal = parseInt(s.value);
        var itemPeso = pesos[itemId] || 0;
        total += (itemVal * itemPeso) / 100;
    });
    document.getElementById('ponderadoTotal').textContent = Math.round(total) + '%';
    document.getElementById('ponderadoTotal').style.color = total >= 90 ? '#28a745' : (total >= 70 ? '#ffc107' : '#dc3545');
    var clas = total >= 90 ? 'Excelente - Mantener y reconocer' : (total >= 70 ? 'Bueno - Monitorear' : 'Requiere plan de mejora');
    document.getElementById('clasificacion').textContent = clas;
}

// Inicializar
document.querySelectorAll('.checklist-slider').forEach(function(s) { updateScore(s); });

// Gráfico Pareto
new Chart(document.getElementById('paretoChart'), {
    type:'bar',
    data:{labels:labelsPareto,datasets:[{label:'Peso (%)',data:pesosArray,backgroundColor:pesosArray.map(function(p){return p>=25?'#1a73e8':p>=20?'#28a745':p>=15?'#ffc107':'#6f42c1'})}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{max:35}}}
});

// Histórico comparativo
new Chart(document.getElementById('historicoChart'), {
    type:'line',
    data:{
        labels:<?= json_encode(array_reverse(array_column($historicoScores, 'fecha'))) ?>,
        datasets:[{label:'Puntaje',data:<?= json_encode(array_reverse(array_column($historicoScores, 'total'))) ?>,borderColor:'#28a745',tension:0.3,fill:false}]
    },
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{min:0,max:100}}}
});
</script>
