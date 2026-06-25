<?php
$tipoLabels = ['manual_calidad'=>'Manual de Calidad','politica'=>'Política','procedimiento'=>'Procedimiento','instructivo'=>'Instructivo','registro'=>'Registro','formato'=>'Formato','plan'=>'Plan','informe'=>'Informe'];
$tipoIconos = ['manual_calidad'=>'book','politica'=>'gavel','procedimiento'=>'diagram-project','instructivo'=>'list-ol','registro'=>'clipboard-check','formato'=>'file-alt','plan'=>'bullseye','informe'=>'chart-bar'];
$tipoOrden = ['manual_calidad','politica','plan','procedimiento','instructivo','formato','registro','informe'];
$estadoColor = ['borrador'=>'secondary','revision'=>'warning','aprobado'=>'success','publicado'=>'primary','obsoleto'=>'dark'];
$totalDocs = count($documentos);
?>

<nav class="mb-3"><ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="/documentos">Gestión Documental</a></li>
    <li class="breadcrumb-item active"><?= htmlspecialchars($proceso['proceso_nombre']) ?></li>
</ol></nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0"><?= htmlspecialchars($proceso['proceso_nombre']) ?></h5>
        <small class="text-muted"><?= htmlspecialchars($proceso['macro_nombre']) ?> · <?= htmlspecialchars($empresa['empresa_nombre']) ?></small>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-light text-dark"><?= $totalDocs ?> documentos</span>
        <a href="/documentos/crear?empresa_id=<?= $empresa['empresa_id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Nuevo Documento</a>
    </div>
</div>

<?php if ($totalDocs === 0): ?>
<div class="card-box"><div class="card-box-body text-center py-5 text-muted">
    <i class="fas fa-file-alt" style="font-size:3rem;color:#ccc;display:block;margin-bottom:12px"></i>
    <p>Este proceso no tiene documentos aún.</p>
    <a href="/documentos/crear?empresa_id=<?= $empresa['empresa_id'] ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Crear primer documento</a>
</div></div>
<?php else: ?>

<!-- Documentos agrupados por tipo -->
<?php foreach ($tipoOrden as $tk): 
    $docsTipo = $porTipo[$tk] ?? [];
    if (empty($docsTipo)) continue;
?>
<div class="card-box mb-3">
    <div class="card-box-header d-flex align-items-center gap-2">
        <i class="fas fa-<?= $tipoIconos[$tk]??'file' ?>" style="color:#1a73e8"></i>
        <strong><?= $tipoLabels[$tk]??$tk ?></strong>
        <span class="badge bg-primary ms-2"><?= count($docsTipo) ?></span>
    </div>
    <div class="card-box-body p-0">
        <?php foreach ($docsTipo as $doc): ?>
        <a href="/documentos/ver/<?= $doc['documento_id'] ?>" class="d-flex align-items-center gap-3 p-3 border-bottom text-decoration-none hover-row" style="color:#333">
            <i class="fas fa-file-alt text-primary" style="font-size:1.3rem"></i>
            <div style="flex:1">
                <div class="fw-bold"><?= htmlspecialchars($doc['documento_titulo']) ?></div>
                <div class="d-flex gap-2 mt-1">
                    <?php if ($doc['documento_codigo']): ?><code class="small"><?= htmlspecialchars($doc['documento_codigo']) ?></code><?php endif; ?>
                    <?php if ($doc['norma_codigo']): ?><small class="text-muted"><?= htmlspecialchars($doc['norma_codigo']) ?></small><?php endif; ?>
                </div>
            </div>
            <div class="text-end">
                <div><span class="badge bg-<?= $estadoColor[$doc['documento_estado']]??'light' ?>"><?= $doc['documento_estado'] ?></span></div>
                <div class="mt-1">
                    <span class="badge bg-light text-dark">v<?= $doc['documento_version'] ?></span>
                    <?php if ($doc['elaborado_por_nombre']): ?><small class="text-muted ms-1"><?= htmlspecialchars($doc['elaborado_por_nombre']) ?></small><?php endif; ?>
                </div>
                <?php if ($doc['documento_fecha_aprobacion']): ?>
                <div><small class="text-muted"><?= date('d/m/Y', strtotime($doc['documento_fecha_aprobacion'])) ?></small></div>
                <?php endif; ?>
            </div>
            <i class="fas fa-chevron-right text-muted"></i>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<style>
.hover-row:hover { background: #f8f9fb; }
</style>
