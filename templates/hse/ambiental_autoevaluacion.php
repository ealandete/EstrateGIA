<h6><i class="fas fa-leaf me-2" style="color:#28a745"></i>Autoevaluación Inicial ISO 14001:2015</h6>
<p class="small text-muted mb-3">Complete la autoevaluación de su Sistema de Gestión Ambiental basada en los requisitos de la norma ISO 14001:2015.</p>

<?php
$estandares = [
    ['4.1','Comprensión de la organización y su contexto','¿Se han identificado las cuestiones externas e internas que afectan el desempeño ambiental?'],
    ['4.2','Partes interesadas','¿Se han identificado las necesidades y expectativas de las partes interesadas?'],
    ['4.3','Alcance del SGA','¿Está documentado el alcance del sistema de gestión ambiental?'],
    ['4.4','Sistema de gestión ambiental','¿Se ha establecido, implementado y mantenido el SGA según la norma?'],
    ['5.1','Liderazgo y compromiso','¿La alta dirección demuestra liderazgo respecto al SGA?'],
    ['5.2','Política ambiental','¿Existe una política ambiental documentada, comunicada y disponible?'],
    ['6.1.1','Aspectos ambientales','¿Se identifican los aspectos ambientales significativos de todas las operaciones?'],
    ['6.1.2','Requisitos legales','¿Se ha determinado y se accede a los requisitos legales aplicables?'],
    ['6.2','Objetivos ambientales','¿Están definidos objetivos ambientales medibles y coherentes con la política?'],
    ['7.1','Recursos','¿Se proporcionan los recursos necesarios para el SGA?'],
    ['7.2','Competencia','¿El personal es competente para tareas que afectan el desempeño ambiental?'],
    ['7.3','Toma de conciencia','¿Los trabajadores conocen la política, impactos y su rol en el SGA?'],
    ['7.4','Comunicación','¿Existen procesos de comunicación interna y externa sobre el SGA?'],
    ['7.5','Información documentada','¿Se controla la información documentada del SGA?'],
    ['8.1','Planificación y control operacional','¿Se controlan los procesos para cumplir los requisitos del SGA?'],
    ['8.2','Preparación ante emergencias','¿Existe un plan de respuesta ante emergencias ambientales?'],
    ['9.1','Seguimiento y medición','¿Se hace seguimiento, medición y evaluación del desempeño ambiental?'],
    ['9.2','Auditoría interna','¿Se realizan auditorías internas periódicas al SGA?'],
    ['9.3','Revisión por la dirección','¿La alta dirección revisa el SGA periódicamente?'],
    ['10.1','Mejora','¿Se identifican oportunidades de mejora y se implementan acciones?'],
    ['10.2','No conformidades','¿Se gestionan las no conformidades y acciones correctivas?'],
];
$ultimaEval = json_decode($empresa['empresa_autoeval_ambiental_json'] ?? '{}', true) ?: [];
?>
<div class="card-box"><div class="card-box-body p-0"><table class="table-box small">
<thead><tr><th width="12%">ISO 14001</th><th width="22%">Requisito</th><th>Criterio de evaluación</th><th width="110">Calificación</th></tr></thead>
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
    <td colspan="3" class="text-end">Puntaje Total: <span id="totalScore">0</span> / <?= count($estandares)*2.5 ?> | </td>
    <td><span id="clasificacion" class="badge"></span></td>
</tr>
</tfoot>
</table></div></div>

<!-- Seguimiento periódico -->
<div class="card-box mt-4">
    <div class="card-box-header"><i class="fas fa-history me-2"></i>Historial de Autoevaluaciones Periódicas</div>
    <div class="card-box-body p-0 table-responsive">
    <?php 
    $historial = json_decode($empresa['empresa_autoeval_ambiental_historial_json'] ?? '[]', true) ?: [];
    if (!empty($historial)): ?>
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
    <?php else: ?><p class="text-muted small p-3 mb-0">Sin evaluaciones periódicas registradas. La primera se guardará al completar esta autoevaluación.</p>
    <?php endif; ?>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <button class="btn btn-success" onclick="guardarAutoeval()"><i class="fas fa-save me-1"></i>Guardar Autoevaluación</button>
    <span class="text-muted small mt-2">La autoevaluación periódica se registrará en el historial y actualizará la línea base.</span>
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
        var r = await fetch('/ambiental/autoevaluacion/guardar', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'empresa_id=<?= $empresa['empresa_id'] ?? 0 ?>&valores='+encodeURIComponent(JSON.stringify(valores))+'&puntaje='+total+'&max='+max});
        var d = await r.json();
        if (d.success) { alert('Autoevaluación guardada. Puntaje: '+total.toFixed(1)+'/'+max); location.reload(); }
        else { alert('Error al guardar'); }
    } catch(e) { alert('Error de conexión'); }
}
</script>
