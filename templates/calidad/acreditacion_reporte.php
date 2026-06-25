<?php
$cumplen = count(array_filter($estandares, fn($e)=>($e['ultimo_cumplimiento']??'')==='cumple'));
$total = count($estandares);
$pct = $total>0 ? round($cumplen/$total*100,1) : 0;
$tipoLabels = ['SUA'=>'Sist. Único Acreditación','ISO7101'=>'ISO 7101:2023','Habilitacion'=>'Habilitación'];
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Informe de Acreditación - <?= htmlspecialchars($empresa['empresa_nombre']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
@media print { body{background:white;} .no-print{display:none;} .page-break{page-break-before:always;} }
body{font-family:'Segoe UI',sans-serif;color:#333;max-width:1000px;margin:0 auto;padding:20px}
.header{text-align:center;border-bottom:3px solid #ffc107;padding-bottom:20px;margin-bottom:30px}
.header h1{color:#f39c12;margin:0;font-size:1.8rem}
.header h2{color:#555;font-size:1.2rem;margin-top:5px}
.metric{text-align:center;padding:15px;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.06)}
.metric .value{font-size:2rem;font-weight:700}
table{width:100%;font-size:0.85rem}
th{background:#f8f9fa;padding:8px;text-align:left;font-weight:600}
td{padding:8px;border-bottom:1px solid #eee}
.c-cumple{color:#28a745}.c-parcial{color:#ffc107}.c-no{color:#dc3545}
.summary-box{background:#fff8e1;border-left:4px solid #ffc107;padding:16px;border-radius:4px;margin-bottom:20px}
.section-title{color:#f39c12;border-bottom:2px solid #ffc107;padding-bottom:6px;margin:30px 0 15px}
.signature-box{border-top:1px solid #ccc;padding-top:20px;margin-top:40px}
</style></head><body>

<div class="no-print" style="position:fixed;top:20px;right:20px">
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Imprimir / PDF</button>
    <a href="/acreditacion" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="header">
    <h1>Informe de Cumplimiento de Acreditación en Salud</h1>
    <h2><?= htmlspecialchars($empresa['empresa_nombre']) ?></h2>
    <p class="text-muted small"><?= date('d/m/Y H:i') ?> · Generado por EstrateGIA v2.1 · Ministerio de Salud y Protección Social</p>
</div>

<div class="summary-box">
    <p class="mb-2"><strong>Resumen Ejecutivo:</strong> De <?= $total ?> estándares de acreditación evaluados, <strong class="text-success"><?= $cumplen ?> cumplen (<?= $pct ?>%)</strong>, <strong class="text-warning"><?= $parcial ?> cumplen parcialmente</strong> y <strong class="text-danger"><?= $noCumplen ?> no cumplen</strong>. Puntaje promedio ponderado: <strong><?= $pctTotal ?>%</strong>.</p>
</div>

<div class="row mb-4">
    <div class="col-3"><div class="metric"><div class="value text-warning"><?= $pct ?>%</div><small>Cumplimiento General</small></div></div>
    <div class="col-3"><div class="metric"><div class="value text-warning"><?= $pctTotal ?>%</div><small>Puntaje Promedio</small></div></div>
    <div class="col-3"><div class="metric"><div class="value text-success"><?= count($pamecData) ?></div><small>Auditorías PAMEC</small></div></div>
    <div class="col-3"><div class="metric"><div class="value text-danger"><?= count(array_filter($riesgos, fn($r)=>in_array($r['riesgo_nivel'],['extremo','alto']))) ?></div><small>Riesgos Altos</small></div></div>
</div>

<!-- Cumplimiento por Tipo -->
<h3 class="section-title">1. Cumplimiento por Tipo de Estándar</h3>
<?php foreach ($porTipo as $tipo => $datos):
    $pctT = $datos['total']>0 ? round(($datos['cumple']/$datos['total'])*100,1) : 0;
    $promT = $datos['total']>0 ? round($datos['puntaje_total']/$datos['total'],1) : 0;
?>
<div class="row mb-3">
    <div class="col-4"><strong><?= $tipoLabels[$tipo]??$tipo ?></strong></div>
    <div class="col-8">
        <div class="progress" style="height:18px"><div class="progress-bar bg-<?= $pctT>=90?'success':($pctT>=60?'warning':'danger') ?>" style="width:<?= $pctT ?>%"><?= $pctT ?>% (<?= $datos['cumple'] ?>/<?= $datos['total'] ?>)</div></div>
        <div class="small text-muted">Promedio: <?= $promT ?>% · Parcial: <?= $datos['parcial'] ?> · No Cumple: <?= $datos['no_cumple'] ?></div>
    </div>
</div>
<?php endforeach; ?>

<!-- Estándares detallados -->
<h3 class="section-title page-break">2. Detalle de Estándares Evaluados</h3>
<table><thead><tr><th>Código</th><th>Estándar</th><th>Tipo</th><th>Grupo</th><th>Puntaje</th><th>Estado</th><th>Última Evaluación</th></tr></thead><tbody>
<?php foreach ($estandares as $e): $c=$e['ultimo_cumplimiento']??'no_evaluado'; ?>
<tr>
    <td><strong><?= htmlspecialchars($e['estandar_codigo']) ?></strong></td>
    <td><?= htmlspecialchars(substr($e['estandar_nombre'],0,60)) ?></td>
    <td><?= $tipoLabels[$e['estandar_tipo']]??$e['estandar_tipo'] ?></td>
    <td><?= htmlspecialchars($e['estandar_grupo']) ?></td>
    <td><strong><?= $e['ultimo_puntaje'] ?? 0 ?>%</strong></td>
    <td class="c-<?= $c==='cumple'?'cumple':($c==='cumple_parcial'?'parcial':($c==='no_cumple'?'no':'')) ?>"><?= str_replace('_',' ',$c) ?></td>
    <td class="small"><?= $e['evidencia_fecha_evaluacion'] ? date('d/m/Y', strtotime($e['evidencia_fecha_evaluacion'])) : '-' ?></td>
</tr>
<?php endforeach; ?></tbody></table>

<!-- No Conformidades -->
<h3 class="section-title page-break">3. No Conformidades</h3>
<table><thead><tr><th>Código</th><th>Tipo</th><th>Descripción</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>
<?php foreach ($ncs as $nc): ?>
<tr><td><?= htmlspecialchars($nc['nc_codigo']) ?></td><td><?= $nc['nc_tipo'] ?></td><td><?= htmlspecialchars(substr($nc['nc_descripcion'],0,120)) ?></td><td><?= $nc['nc_estado'] ?></td><td><?= date('d/m/Y', strtotime($nc['nc_fecha_deteccion'])) ?></td></tr>
<?php endforeach; ?></tbody></table>

<!-- Auditorías PAMEC -->
<h3 class="section-title page-break">4. Auditorías PAMEC</h3>
<table><thead><tr><th>Estándar</th><th>Proceso</th><th>Fecha Prog.</th><th>Estado</th><th>Calificación</th></tr></thead><tbody>
<?php foreach ($pamecData as $pa): ?>
<tr><td><?= $pa['pamec_estandar'] ?></td><td><?= htmlspecialchars($pa['proceso_nombre']??'General') ?></td><td><?= date('d/m/Y', strtotime($pa['pamec_fecha_programada'])) ?></td><td><?= $pa['pamec_estado'] ?></td><td><?= $pa['pamec_calificacion'] ? $pa['pamec_calificacion'].'%' : '-' ?></td></tr>
<?php endforeach; ?></tbody></table>

<!-- Riesgos -->
<h3 class="section-title page-break">5. Matriz de Riesgos</h3>
<table><thead><tr><th>Código</th><th>Descripción</th><th>Nivel</th><th>Prob.</th><th>Impacto</th><th>Estado</th></tr></thead><tbody>
<?php foreach ($riesgos as $r): ?>
<tr><td><?= htmlspecialchars($r['riesgo_codigo']) ?></td><td><?= htmlspecialchars(substr($r['riesgo_descripcion'],0,100)) ?></td><td><?= $r['riesgo_nivel'] ?></td><td><?= $r['riesgo_probabilidad'] ?></td><td><?= $r['riesgo_impacto'] ?></td><td><?= $r['riesgo_estado'] ?></td></tr>
<?php endforeach; ?></tbody></table>

<!-- Conclusiones y Firmas -->
<div class="signature-box">
    <h3 class="section-title">6. Conclusiones y Recomendaciones</h3>
    <p>La institución <strong><?= htmlspecialchars($empresa['empresa_nombre']) ?></strong> presenta un cumplimiento general del <strong><?= $pct ?>%</strong> frente a los estándares de acreditación en salud del Ministerio de Salud y Protección Social.</p>
    <ul>
        <li>Se recomienda priorizar los <strong class="text-danger"><?= $noCumplen ?> estándares no cumplidos</strong> mediante planes de mejora inmediatos.</li>
        <li>Los <strong class="text-warning"><?= $parcial ?> estándares con cumplimiento parcial</strong> requieren acciones correctivas para alcanzar el 100%.</li>
        <li>Mantener las <strong><?= count($pamecData) ?> auditorías PAMEC</strong> como mecanismo de verificación continua.</li>
    </ul>
    <div class="row mt-4">
        <div class="col-6">
            <p><strong>Elaborado por:</strong><br><br>_________________________</p>
            <p class="small text-muted">Coordinador de Calidad</p>
        </div>
        <div class="col-6">
            <p><strong>Revisado por:</strong><br><br>_________________________</p>
            <p class="small text-muted">Dirección General</p>
        </div>
    </div>
    <p class="text-muted small mt-3">Documento generado automáticamente por EstrateGIA v2.1 · <?= date('d/m/Y H:i') ?> · Cumple con lineamientos del Ministerio de Salud y Protección Social (Decreto 1011/2006, Resolución 0123/2012, Resolución 5095/2018).</p>
</div>
</body></html>
