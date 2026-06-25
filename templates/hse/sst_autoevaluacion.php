<h6><i class="fas fa-clipboard-check me-2" style="color:#28a745"></i>Autoevaluación SG-SST</h6>
<p class="small text-muted mb-3">Decreto 1072/2015 · Res. 0312/2019 — Complete la autoevaluación para iniciar el ciclo PHVA</p>

<?php
$estandares = [
    ['Art. 2.2.4.6.16','Política de SST','¿Existe una política de SST firmada, fechada y comunicada?'],
    ['Art. 2.2.4.6.17','Objetivos SST','¿Los objetivos están documentados, medibles y alineados con la política?'],
    ['Art. 2.2.4.6.18','Evaluación inicial','¿Se realizó la evaluación inicial del sistema de gestión?'],
    ['Art. 2.2.4.6.19','Identificación de peligros','¿Existe metodología para identificar peligros y valorar riesgos?'],
    ['Art. 2.2.4.6.20','Medicina preventiva','¿Se realizan evaluaciones médicas ocupacionales periódicas?'],
    ['Art. 2.2.4.6.21','Plan de trabajo anual','¿Existe un plan de trabajo anual con responsables y fechas?'],
    ['Art. 2.2.4.6.22','Prevención de emergencias','¿Se implementó el plan de prevención y respuesta ante emergencias?'],
    ['Art. 2.2.4.6.23','Investigación de AT','¿Se investigan los accidentes e incidentes de trabajo?'],
    ['Art. 2.2.4.6.24','Capacitación','¿El programa de capacitación cubre a todos los trabajadores?'],
    ['Art. 2.2.4.6.25','Gestión del cambio','¿Existe procedimiento para gestionar cambios que afecten la SST?'],
    ['Art. 2.2.4.6.26','Adquisiciones','¿Se evalúan proveedores y contratistas en SST?'],
    ['Art. 2.2.4.6.27','Auditoría','¿Se realiza auditoría anual al SG-SST?'],
    ['Art. 2.2.4.6.28','Revisión gerencial','¿La alta dirección revisa el SG-SST periódicamente?'],
    ['Art. 2.2.4.6.29','Indicadores','¿Se calculan y analizan indicadores mínimos de SST?'],
];
$ultimaEval = json_decode($empresa['empresa_autoeval_sst_json'] ?? '{}', true) ?: [];
$historial = json_decode($empresa['empresa_autoeval_sst_historial_json'] ?? '[]', true) ?: [];
?>
<div class="card-box"><div class="card-box-body p-0"><table class="table-box small">
<thead><tr><th width="40%">Estándar</th><th>Tema</th><th style="width:50%">Criterio</th><th width="100">Calificación</th></tr></thead>
<tbody>
<?php foreach ($estandares as $i => $e): $prev = $ultimaEval[$i] ?? 0; ?>
<tr>
    <td><strong><?= $e[0] ?></strong></td>
    <td><?= $e[1] ?></td>
    <td class="small"><?= $e[2] ?></td>
    <td>
        <select class="form-select form-select-sm auto-eval" data-item="<?=$i?>" onchange="calcularAutoeval()">
            <option value="0" <?= $prev==0?'selected':'' ?>>Seleccione</option>
            <option value="2.5" <?= $prev==2.5?'selected':'' ?>>Cumple totalmente (2.5)</option>
            <option value="1.5" <?= $prev==1.5?'selected':'' ?>>Cumple parcialmente (1.5)</option>
            <option value="0" <?= $prev==0?'selected':'' ?>>No cumple (0)</option>
        </select>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr class="fw-bold bg-light">
    <td colspan="3" class="text-end">Puntaje Total: <span id="totalScore">0</span> / 35 | </td>
    <td><span id="clasificacion" class="badge"></span></td>
</tr>
</tfoot>
</table></div></div>

<!-- Historial -->
<div class="card-box mt-4">
    <div class="card-box-header"><i class="fas fa-history me-2"></i>Historial de Autoevaluaciones</div>
    <div class="card-box-body p-0 table-responsive">
    <?php if (!empty($historial)): ?>
    <table class="table table-sm small mb-0">
        <thead><tr><th>Fecha</th><th>Puntaje</th><th>%</th><th>Clasificación</th></tr></thead>
        <tbody>
        <?php foreach (array_reverse($historial) as $h): $pct = ($h['puntaje'] ?? 0) / (count($estandares)*2.5) * 100; ?>
        <tr>
            <td><?= htmlspecialchars($h['fecha'] ?? '') ?></td>
            <td><?= $h['puntaje'] ?? 0 ?> / <?= count($estandares)*2.5 ?></td>
            <td><?= round($pct) ?>%</td>
            <td><span class="badge bg-<?= $pct>=86?'success':($pct>=61?'warning':'danger') ?>"><?= $pct>=86?'Aceptable':($pct>=61?'Moderado':'Crítico') ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?><p class="text-muted small p-3 mb-0">Sin evaluaciones registradas.</p><?php endif; ?>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <button class="btn btn-success" onclick="guardarAutoeval()"><i class="fas fa-save me-1"></i>Guardar Autoevaluación</button>
    <a href="?seccion=dashboard" class="btn btn-outline-primary">Continuar al Dashboard <i class="fas fa-arrow-right ms-1"></i></a>
</div>

<script>
calcularAutoeval();
function calcularAutoeval(){
    var total=0, max=<?= count($estandares)*2.5 ?>;
    document.querySelectorAll('.auto-eval').forEach(function(s){total+=parseFloat(s.value||0);});
    document.getElementById('totalScore').textContent=total.toFixed(1);
    var pct=total/max*100;
    var cls=document.getElementById('clasificacion');
    if(pct>=86){cls.textContent='Aceptable (≥86%)';cls.className='badge bg-success';}
    else if(pct>=61){cls.textContent='Moderadamente aceptable (61-85%)';cls.className='badge bg-warning';}
    else{cls.textContent='Crítico - Requiere plan de mejora (<61%)';cls.className='badge bg-danger';}
}
async function guardarAutoeval(){
    var valores=[],total=0,max=<?= count($estandares)*2.5 ?>;
    document.querySelectorAll('.auto-eval').forEach(function(s){var v=parseFloat(s.value||0);valores.push(v);total+=v;});
    try {
        var r = await fetch('/sst/autoevaluacion/guardar', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'empresa_id=<?= $empresa['empresa_id'] ?? 0 ?>&valores='+encodeURIComponent(JSON.stringify(valores))+'&puntaje='+total+'&max='+max});
        var d = await r.json();
        if (d.success) { alert('Autoevaluación guardada. Puntaje: '+total.toFixed(1)+'/'+max); location.reload(); }
        else { alert('Error al guardar'); }
    } catch(e) { alert('Error de conexión'); }
}
</script>
