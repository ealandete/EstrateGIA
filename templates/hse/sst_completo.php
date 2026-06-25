<?php $ok = $_GET['ok'] ?? null; $err = $_GET['err'] ?? null;
$seccion = $_GET['seccion'] ?? 'dashboard';
$sections = [
    'autoevaluacion' => ['icon'=>'clipboard-check','label'=>'Autoevaluación'],
    'dashboard' => ['icon'=>'gauge-high','label'=>'Dashboard'],
    'peligros' => ['icon'=>'triangle-exclamation','label'=>'Peligros'],
    'incidentes' => ['icon'=>'clipboard-list','label'=>'Incidentes'],
    'plan' => ['icon'=>'calendar-alt','label'=>'Plan Anual'],
    'normatividad' => ['icon'=>'scale-balanced','label'=>'Normatividad'],
    'ausentismo' => ['icon'=>'calendar-xmark','label'=>'Ausentismo'],
    'capacitaciones' => ['icon'=>'graduation-cap','label'=>'Capacitación'],
    'examenes' => ['icon'=>'stethoscope','label'=>'Exámenes'],
    'inspecciones' => ['icon'=>'magnifying-glass','label'=>'Inspecciones'],
    'emergencias' => ['icon'=>'tower-broadcast','label'=>'Emergencias'],
    'reportes' => ['icon'=>'file-pdf','label'=>'Reportes'],
    'ciclo' => ['icon'=>'arrows-spin','label'=>'Ciclo PHVA'],
];
if($ok): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Operaci&oacute;n realizada.</div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>
<div class="d-flex justify-content-between mb-2">
    <div><h5 class="mb-0"><i class="fas fa-hard-hat me-2" style="color:#ffc107"></i>Seguridad y Salud en el Trabajo</h5>
    <small class="text-muted">ISO 45001:2018 &middot; Decreto 1072/2015 &middot; <?= htmlspecialchars($empresa['empresa_nombre']) ?></small></div>
    <select class="form-select form-select-sm" style="width:100px" onchange="location.href='?seccion=<?= $seccion ?>&anio='+this.value">
        <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?><option value="<?=$y?>" <?=$anio==$y?'selected':''?>><?=$y?></option><?php endfor; ?>
    </select>
</div>
<ul class="nav nav-tabs nav-tabs-scroll small mb-3">
    <?php foreach($sections as $key=>$sec): ?>
    <li class="nav-item"><a class="nav-link <?= $seccion===$key?'active':'' ?>" href="?seccion=<?=$key?>&anio=<?=$anio?>"><i class="fas fa-<?=$sec['icon']?> me-1" style="font-size:0.65rem"></i><?=$sec['label']?></a></li>
    <?php endforeach; ?>
</ul>
<?php
if($seccion==='autoevaluacion') require __DIR__.'/sst_autoevaluacion.php';
elseif($seccion==='dashboard') require __DIR__.'/sst_dashboard.php';
elseif($seccion==='peligros') require __DIR__.'/sst_peligros.php';
elseif($seccion==='incidentes') require __DIR__.'/sst_incidentes.php';
elseif($seccion==='plan') require __DIR__.'/sst_plan.php';
elseif($seccion==='normatividad') require __DIR__.'/sst_normatividad.php';
elseif($seccion==='ausentismo') require __DIR__.'/sst_ausentismo.php';
elseif($seccion==='capacitaciones') require __DIR__.'/sst_capacitaciones.php';
elseif($seccion==='examenes') require __DIR__.'/sst_examenes.php';
elseif($seccion==='inspecciones') require __DIR__.'/sst_inspecciones.php';
elseif($seccion==='emergencias') require __DIR__.'/sst_emergencias.php';
elseif($seccion==='reportes') require __DIR__.'/sst_reportes.php';
elseif($seccion==='ciclo') require __DIR__.'/sst_ciclo.php';
?>
<?php $moduloContexto='seguridad y salud en el trabajo (ISO 45001)'; require __DIR__.'/ia_panel.php'; ?>
