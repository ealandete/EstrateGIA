<?php
$macroColors = ['estrategico'=>'#1a73e8','misional'=>'#28a745','apoyo'=>'#ffc107','evaluacion'=>'#6f42c1'];
$macroIcons = ['estrategico'=>'crown','misional'=>'stethoscope','apoyo'=>'gear','evaluacion'=>'magnifying-glass-chart'];
$variantColors = ['cumplimiento'=>'#28a745','oportunidad'=>'#ffc107','calidad'=>'#007bff','productividad'=>'#6f42c1'];
$variantIcons = ['cumplimiento'=>'check-circle','oportunidad'=>'clock','calidad'=>'star','productividad'=>'chart-line'];
$scores = [
    ['label'=>'Cumplimiento','value'=>$evaluacion['evaluacion_puntaje_cumplimiento']??0,'color'=>'#28a745','icon'=>'check-circle'],
    ['label'=>'Oportunidad','value'=>$evaluacion['evaluacion_puntaje_oportunidad']??0,'color'=>'#ffc107','icon'=>'clock'],
    ['label'=>'Calidad','value'=>$evaluacion['evaluacion_puntaje_calidad']??0,'color'=>'#007bff','icon'=>'star'],
    ['label'=>'Productividad','value'=>$evaluacion['evaluacion_puntaje_productividad']??0,'color'=>'#6f42c1','icon'=>'chart-line'],
];
$acordeon = $_GET['acordeon'] ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="fas fa-user-check me-2"></i>Evaluación de Desempeño · <?= date('m/Y', strtotime($periodo)) ?></h5>
    <div class="d-flex gap-2">
        <select onchange="location.href='?periodo='+this.value" class="form-select form-select-sm" style="width:170px">
            <?php 
            $mesesES = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            for ($y=2025;$y<=date('Y');$y++): for ($m=1;$m<=12;$m++): 
                $p = $y.'-'.str_pad($m,2,'0',STR_PAD_LEFT); 
                if ($y == date('Y') && $m > date('m')) continue;
            ?>
            <option value="<?= $p ?>" <?= $periodo==$p?'selected':'' ?>><?= $mesesES[$m].' '.$y ?></option>
            <?php endfor; endfor; ?>
        </select>
    </div>
</div>

<!-- Mi evaluación personal -->
<div class="card-box mb-4">
    <div class="card-box-header"><i class="fas fa-id-card me-2"></i>Mi Evaluación Personal</div>
    <div class="card-box-body">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <div style="width:80px;height:80px;border-radius:50%;background:#1a73e8;color:#fff;display:inline-flex;align-items:center;justify-content:center;margin:10px 0">
                    <span style="font-size:1.5rem;font-weight:700"><?= number_format($evaluacion['evaluacion_puntaje_total']??0,1) ?>%</span>
                </div>
                <div class="small text-muted">Puntaje Total</div>
            </div>
            <div class="col-md-9">
                <div class="row g-2">
                    <?php foreach ($scores as $s): ?>
                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="fas fa-<?= $s['icon'] ?>" style="color:<?= $s['color'] ?>"></i>
                            <small><?= $s['label'] ?></small>
                            <strong class="ms-auto" style="color:<?= $s['color'] ?>"><?= number_format($s['value'],1) ?>%</strong>
                        </div>
                        <div class="progress" style="height:6px;border-radius:3px"><div class="progress-bar" style="width:<?= $s['value'] ?>%;background:<?= $s['color'] ?>;border-radius:3px"></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- NIVEL 1: Macroprocesos -->
<h5 class="mb-3"><i class="fas fa-sitemap me-2"></i>Rendimiento por Macroproceso</h5>
<div class="row g-4 mb-4">
    <?php foreach ($macroprocesos as $mp): 
        $pct = round(floatval($mp['puntaje_promedio'] ?? 0), 1);
    ?>
    <div class="col-md-6">
        <a href="/evaluacion?nivel=procesos&proceso_id=<?= $mp['macro_id'] ?>&periodo=<?= $periodo ?>" class="text-decoration-none">
            <div class="card p-3" style="border-left:4px solid <?= $macroColors[$mp['macro_tipo']]??'#888' ?>;border-radius:12px">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <i class="fas fa-<?= $macroIcons[$mp['macro_tipo']]??'folder' ?> me-1" style="color:<?= $macroColors[$mp['macro_tipo']]??'#888' ?>"></i>
                        <strong style="color:#333"><?= htmlspecialchars($mp['macro_nombre']) ?></strong>
                    </div>
                    <span class="badge bg-light text-dark"><?= $mp['macro_tipo'] ?></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:8px;border-radius:4px"><div class="progress-bar bg-<?= $pct>=90?'success':($pct>=70?'warning':'danger') ?>" style="width:<?= min($pct,100) ?>%"></div></div>
                    <strong style="color:<?= $pct>=90?'#28a745':($pct>=70?'#ffc107':'#dc3545') ?>"><?= $pct ?>%</strong>
                    <i class="fas fa-chevron-right text-muted small"></i>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Ranking de colaboradores -->
<h5 class="mb-3"><i class="fas fa-trophy me-2"></i>Ranking de Colaboradores</h5>
<div class="card-box">
    <div class="card-box-body p-0">
        <table class="table-box">
            <thead><tr><th>#</th><th>Colaborador</th><th>Cargo</th><th>Depto</th><th>Cumplim.</th><th>Oportun.</th><th>Calidad</th><th>Product.</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($ranking, 0, 15) as $i => $r): ?>
            <tr>
                <td class="fw-bold" style="color:<?= $i===0?'#ffc107':($i===1?'#aaa':($i===2?'#cd7f32':'#666')) ?>">#<?= $i+1 ?></td>
                <td><strong><?= htmlspecialchars($r['nombre']) ?></strong></td>
                <td><?= htmlspecialchars($r['usuario_cargo']) ?></td>
                <td><?= htmlspecialchars($r['usuario_departamento']) ?></td>
                <td><?= number_format($r['evaluacion_puntaje_cumplimiento']??0,1) ?>%</td>
                <td><?= number_format($r['evaluacion_puntaje_oportunidad']??0,1) ?>%</td>
                <td><?= number_format($r['evaluacion_puntaje_calidad']??0,1) ?>%</td>
                <td><?= number_format($r['evaluacion_puntaje_productividad']??0,1) ?>%</td>
                <td class="fw-bold text-success"><?= number_format($r['evaluacion_puntaje_total']??0,1) ?>%</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
