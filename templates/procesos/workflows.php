<?php
$macroColors = ['estrategico'=>'#1a73e8','misional'=>'#28a745','apoyo'=>'#ffc107','evaluacion'=>'#6f42c1'];
$estadoBadges = ['diseno'=>'bg-secondary','pruebas'=>'bg-warning text-dark','activo'=>'bg-success','obsoleto'=>'bg-dark'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="fw-bold mb-0" style="font-size:1.15rem"><i class="fas fa-project-diagram me-2"></i>Workflows · <?= htmlspecialchars($empresa['empresa_nombre']) ?></div>
</div>

<?php if (empty($workflows)): ?>
<div class="card-box"><div class="card-box-body text-center py-5">
    <i class="fas fa-project-diagram" style="font-size:4rem;color:#ccc;display:block;margin-bottom:16px"></i>
    <h5>No hay workflows definidos</h5>
    <p class="text-muted">Los workflows se crean desde el detalle de cada proceso.</p>
</div></div>
<?php else: ?>
<div class="card-box">
    <div class="card-box-body p-0"><table class="table-box">
        <thead><tr><th>Workflow</th><th>Proceso</th><th>Macroproceso</th><th>Estado</th><th>Descripción</th></tr></thead>
        <tbody>
        <?php foreach ($workflows as $w): ?>
        <tr>
            <td><strong><?= htmlspecialchars($w['workflow_nombre']) ?></strong></td>
            <td><a href="/procesos/ver/<?= $w['workflow_proceso_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($w['proceso_nombre']) ?> <small class="text-muted">(<?= $w['proceso_codigo'] ?>)</small></a></td>
            <td><span style="color:<?= $macroColors[$w['macro_tipo']] ?? '#999' ?>">●</span> <?= htmlspecialchars($w['macro_nombre']) ?></td>
            <td><span class="badge <?= $estadoBadges[$w['workflow_estado']] ?? 'bg-secondary' ?>"><?= $w['workflow_estado'] ?></span></td>
            <td><small class="text-muted"><?= htmlspecialchars(mb_substr($w['workflow_descripcion'] ?? '', 0, 80)) ?></small></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>
