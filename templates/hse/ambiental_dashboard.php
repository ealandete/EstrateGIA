<div class="row g-3 mb-3">
    <div class="col-md-3">
        <a href="?seccion=registros" class="text-decoration-none">
        <div class="stat-card drill-card">
            <div class="stat-label">Consumo de Agua <?= $anio ?></div>
            <div class="stat-value"><?= number_format($estadisticas['agua'] ?? 0, 0) ?> m&sup3;</div>
            <small class="text-muted">Total anual</small>
        </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?seccion=registros" class="text-decoration-none">
        <div class="stat-card drill-card">
            <div class="stat-label">Consumo de Energ&iacute;a</div>
            <div class="stat-value"><?= number_format($estadisticas['energia'] ?? 0, 0) ?> kWh</div>
            <small class="text-muted">Total anual</small>
        </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?seccion=huella" class="text-decoration-none">
        <div class="stat-card drill-card">
            <div class="stat-label">Huella de Carbono</div>
            <div class="stat-value"><?= number_format($huellaCarbono['total'] ?? 0, 2) ?> tCO<sub>2</sub>e</div>
            <small class="<?= ($huellaCarbono['variacion'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                <?= ($huellaCarbono['variacion'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($huellaCarbono['variacion'] ?? 0, 1) ?>% vs <?= $anio - 1 ?>
            </small>
        </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?seccion=aspectos" class="text-decoration-none">
        <div class="stat-card drill-card">
            <div class="stat-label">Aspectos Identificados</div>
            <div class="stat-value"><?= $estadisticas['aspectos'] ?? 0 ?></div>
            <small class="text-muted"><?= $estadisticas['controles'] ?? 0 ?> controles activos</small>
        </div>
        </a>
    </div>
</div>
<style>.drill-card{cursor:pointer;transition:all 0.2s}.drill-card:hover{transform:translateY(-3px);box-shadow:0 4px 16px rgba(0,0,0,0.12)}</style>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="stat-card border-start border-3 border-danger">
            <div class="stat-label">Alcance 1 - Directo</div>
            <div class="stat-value"><?= number_format($huellaCarbono['alcance1'] ?? 0, 2) ?></div>
            <small class="text-muted">tCO<sub>2</sub>e</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card border-start border-3 border-warning">
            <div class="stat-label">Alcance 2 - Indirecto</div>
            <div class="stat-value"><?= number_format($huellaCarbono['alcance2'] ?? 0, 2) ?></div>
            <small class="text-muted">tCO<sub>2</sub>e</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card border-start border-3 border-info">
            <div class="stat-label">Alcance 3 - Cadena Valor</div>
            <div class="stat-value"><?= number_format($huellaCarbono['alcance3'] ?? 0, 2) ?></div>
            <small class="text-muted">tCO<sub>2</sub>e</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Cumplimiento Meta Huella</div>
            <div class="stat-value"><?= number_format($huellaCarbono['cumplimientoMeta'] ?? 0, 1) ?>%</div>
            <div class="progress mt-1" style="height:6px"><div class="progress-bar bg-<?= ($huellaCarbono['cumplimientoMeta'] ?? 0) > 100 ? 'danger' : 'success' ?>" style="width:<?= min(100, $huellaCarbono['cumplimientoMeta'] ?? 0) ?>%"></div></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($indicadores as $ind): $nombre = $ind['sind_nombre'] ?? ''; $valor = (float)($ind['sind_valor'] ?? 0); $unidad = $ind['sind_unidad'] ?? ''; $meta = (float)($ind['sind_meta'] ?? 1); ?>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label"><?= htmlspecialchars($nombre) ?: '—' ?></div>
            <div class="stat-value"><?= $valor ? number_format($valor, 0) : '—' ?><?= $unidad ? ' ' . htmlspecialchars($unidad) : '' ?></div>
            <div class="progress mt-1" style="height:5px"><div class="progress-bar bg-success" style="width:<?= $meta > 0 ? min(100, ($valor / $meta) * 100) : 0 ?>%"></div></div>
            <small>Meta: <?= $meta > 0 ? number_format($meta, 0) : '—' ?><?= $unidad ? ' ' . htmlspecialchars($unidad) : '' ?></small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-3">
    <div class="col-md-4">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-droplet me-2"></i>Consumo de Agua <?= $anio ?></div>
            <div class="card-box-body"><canvas id="chartAgua" style="max-height:220px"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-bolt me-2"></i>Consumo de Energ&iacute;a <?= $anio ?></div>
            <div class="card-box-body"><canvas id="chartEnergia" style="max-height:220px"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-cloud me-2"></i>Huella de Carbono por Alcance</div>
            <div class="card-box-body"><canvas id="chartHuellaDash" style="max-height:220px"></canvas></div>
        </div>
    </div>
</div>

<?php $labelsAgua = json_encode(array_column($tendenciaAgua ?? [], 'mes')); $valuesAgua = json_encode(array_column($tendenciaAgua ?? [], 'total')); ?>
<?php $labelsEnergia = json_encode(array_column($tendenciaEnergia ?? [], 'mes')); $valuesEnergia = json_encode(array_column($tendenciaEnergia ?? [], 'total')); ?>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var ctx1=document.getElementById('chartAgua').getContext('2d');
    new Chart(ctx1,{type:'bar',data:{labels:<?= $labelsAgua ?: '[]' ?>,datasets:[{label:'Agua (m&sup3;)',data:<?= $valuesAgua ?: '[]' ?>,backgroundColor:'rgba(23,162,184,0.7)',borderColor:'#17a2b8',borderWidth:1}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
    var ctx2=document.getElementById('chartEnergia').getContext('2d');
    new Chart(ctx2,{type:'line',data:{labels:<?= $labelsEnergia ?: '[]' ?>,datasets:[{label:'Energ&iacute;a (kWh)',data:<?= $valuesEnergia ?: '[]' ?>,backgroundColor:'rgba(255,193,7,0.2)',borderColor:'#ffc107',borderWidth:2,tension:0.3,fill:true}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
    var ctx3=document.getElementById('chartHuellaDash').getContext('2d');
    new Chart(ctx3,{type:'doughnut',data:{labels:['Alcance 1','Alcance 2','Alcance 3'],datasets:[{data:[<?= $huellaCarbono['alcance1'] ?? 0 ?>,<?= $huellaCarbono['alcance2'] ?? 0 ?>,<?= $huellaCarbono['alcance3'] ?? 0 ?>],backgroundColor:['#dc3545','#ffc107','#17a2b8']}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});
});
</script>
