<div class="d-flex justify-content-between mb-3">
    <div><h5><i class="fas fa-book-open me-2" style="color:#2563eb"></i><?=htmlspecialchars($a['titulo'])?></h5>
    <small class="text-muted">Modulo: <?=htmlspecialchars($a['modulo']??'General')?> | <?=$a['vistas']?> vistas | Actualizado: <?=date('d/m/Y', strtotime($a['updated_at']??$a['created_at']))?></small></div>
    <a href="/soporte/kb" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Volver a KB</a>
</div>

<div class="card-box">
    <div class="card-box-body" style="white-space:pre-wrap;line-height:1.7"><?=htmlspecialchars($a['contenido'])?></div>
    <?php if ($a['tags']??''): ?>
    <div class="card-box-footer">
        <strong>Tags:</strong>
        <?php foreach (explode(',',$a['tags']) as $tag): ?>
        <span class="badge bg-light text-dark me-1"><?=htmlspecialchars(trim($tag))?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="mt-2">
    <a href="/soporte/kb" class="btn btn-outline-secondary btn-sm">&laquo; Volver a KB</a>
</div>
