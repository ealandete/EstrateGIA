<div class="card-box mb-4">
    <div class="card-box-header"><i class="fas fa-trophy me-2"></i>Ranking General - <?= htmlspecialchars($periodo) ?></div>
    <div class="card-box-body">
        <table class="table-box">
            <thead><tr><th>#</th><th>Colaborador</th><th>Cargo</th><th>Depto</th><th>Cumplim.</th><th>Oportun.</th><th>Calidad</th><th>Product.</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($ranking as $i => $r): ?>
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

<div class="card-box">
    <div class="card-box-header"><i class="fas fa-building me-2"></i>Promedio por Departamento</div>
    <div class="card-box-body">
        <table class="table-box">
            <thead><tr><th>Departamento</th><th>Colaboradores</th><th>Cumplim.</th><th>Oportun.</th><th>Calidad</th><th>Product.</th><th>Promedio</th></tr></thead>
            <tbody>
            <?php foreach ($porDepto as $d): ?>
            <tr>
                <td><strong><?= htmlspecialchars($d['usuario_departamento'] ?: 'Sin depto') ?></strong></td>
                <td><?= $d['total_colaboradores'] ?></td>
                <td><?= number_format($d['promedio_cumplimiento']??0,1) ?>%</td>
                <td><?= number_format($d['promedio_oportunidad']??0,1) ?>%</td>
                <td><?= number_format($d['promedio_calidad']??0,1) ?>%</td>
                <td><?= number_format($d['promedio_productividad']??0,1) ?>%</td>
                <td class="fw-bold"><?= number_format($d['promedio_total']??0,1) ?>%</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
