<?php
$cumplen = count(array_filter($estandares, fn($e)=>($e['ultimo_cumplimiento']??'')==='cumple'));
$total = count($estandares);
$pct = $total>0 ? round($cumplen/$total*100,1) : 0;
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Informe de Calidad - <?= htmlspecialchars($empresa['empresa_nombre']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
@media print { body{background:white;} .no-print{display:none;} .page-break{page-break-before:always;} }
body{font-family:'Segoe UI',sans-serif;color:#333;max-width:1000px;margin:0 auto;padding:20px}
.header{text-align:center;border-bottom:3px solid #1a73e8;padding-bottom:20px;margin-bottom:30px}
.header h1{color:#1a73e8;margin:0}
.metric{text-align:center;padding:15px}
.metric .value{font-size:2rem;font-weight:700}
table{width:100%;font-size:0.85rem}
th{background:#f8f9fa;padding:8px;text-align:left}
td{padding:8px;border-bottom:1px solid #eee}
.c-cumple{color:#28a745}.c-parcial{color:#ffc107}.c-no{color:#dc3545}
</style></head><body>

<div class="no-print" style="position:fixed;top:20px;right:20px">
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Imprimir / PDF</button>
    <a href="/calidad" class="btn btn-light"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="header">
    <h1>Informe de Calidad y Acreditación</h1>
    <p class="mb-1"><strong><?= htmlspecialchars($empresa['empresa_nombre']) ?></strong></p>
    <p class="text-muted"><?= date('d/m/Y') ?> · Generado por EstrateGIA</p>
</div>

<div class="row mb-4">
    <div class="col-3"><div class="metric"><div class="value text-primary"><?= $pct ?>%</div><small>Cumplimiento Estándares</small></div></div>
    <div class="col-3"><div class="metric"><div class="value text-success"><?= count($pamec) ?></div><small>Auditorías PAMEC</small></div></div>
    <div class="col-3"><div class="metric"><div class="value text-danger"><?= count(array_filter($riesgos, fn($r)=>in_array($r['riesgo_nivel'],['extremo','alto']))) ?></div><small>Riesgos Altos</small></div></div>
    <div class="col-3"><div class="metric"><div class="value text-warning"><?= count(array_filter($ncs, fn($n)=>$n['nc_estado']!=='cerrada')) ?></div><small>NC Abiertas</small></div></div>
</div>

<h4 class="page-break">1. Cumplimiento de Estándares</h4>
<table><thead><tr><th>Código</th><th>Estándar</th><th>Tipo</th><th>Grupo</th><th>Estado</th></tr></thead><tbody>
<?php foreach ($estandares as $e): $c=$e['ultimo_cumplimiento']??'no_aplica'; ?>
<tr><td><?= htmlspecialchars($e['estandar_codigo']) ?></td><td><?= htmlspecialchars($e['estandar_nombre']) ?></td><td><?= $e['estandar_tipo'] ?></td><td><?= htmlspecialchars($e['estandar_grupo']) ?></td><td class="c-<?= $c==='cumple'?'cumple':($c==='cumple_parcial'?'parcial':'no') ?>"><?= str_replace('_',' ',$c) ?></td></tr>
<?php endforeach; ?></tbody></table>

<h4 class="page-break">2. No Conformidades</h4>
<table><thead><tr><th>Código</th><th>Tipo</th><th>Descripción</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>
<?php foreach ($ncs as $nc): ?>
<tr><td><?= htmlspecialchars($nc['nc_codigo']) ?></td><td><?= $nc['nc_tipo'] ?></td><td><?= htmlspecialchars(substr($nc['nc_descripcion'],0,120)) ?></td><td><?= $nc['nc_estado'] ?></td><td><?= date('d/m/Y', strtotime($nc['nc_fecha_deteccion'])) ?></td></tr>
<?php endforeach; ?></tbody></table>

<h4 class="page-break">3. Matriz de Riesgos</h4>
<table><thead><tr><th>Código</th><th>Descripción</th><th>Nivel</th><th>Probabilidad</th><th>Impacto</th><th>Estado</th></tr></thead><tbody>
<?php foreach ($riesgos as $r): ?>
<tr><td><?= htmlspecialchars($r['riesgo_codigo']) ?></td><td><?= htmlspecialchars(substr($r['riesgo_descripcion'],0,100)) ?></td><td><?= $r['riesgo_nivel'] ?></td><td><?= $r['riesgo_probabilidad'] ?></td><td><?= $r['riesgo_impacto'] ?></td><td><?= $r['riesgo_estado'] ?></td></tr>
<?php endforeach; ?></tbody></table>

<h4>4. Plan de Auditorías PAMEC</h4>
<table><thead><tr><th>Estándar</th><th>Proceso</th><th>Fecha Prog.</th><th>Estado</th><th>Calificación</th></tr></thead><tbody>
<?php foreach ($pamec as $pa): ?>
<tr><td><?= $pa['pamec_estandar'] ?></td><td><?= htmlspecialchars($pa['proceso_nombre']??'General') ?></td><td><?= date('d/m/Y', strtotime($pa['pamec_fecha_programada'])) ?></td><td><?= $pa['pamec_estado'] ?></td><td><?= $pa['pamec_calificacion'] ? $pa['pamec_calificacion'].'%' : '-' ?></td></tr>
<?php endforeach; ?></tbody></table>

<div class="page-break" style="margin-top:40px;border-top:1px solid #ccc;padding-top:20px">
    <p><strong>Elaborado por:</strong> _________________________</p>
    <p><strong>Revisado por:</strong> _________________________</p>
    <p><strong>Fecha:</strong> <?= date('d/m/Y') ?></p>
    <p class="text-muted small">Documento generado automáticamente por EstrateGIA v1.0</p>
</div>
</body></html>
