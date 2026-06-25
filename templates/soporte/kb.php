<div class="d-flex justify-content-between mb-3">
    <div><h5><i class="fas fa-book me-2" style="color:#2563eb"></i>Base de Conocimiento</h5><small class="text-muted"><?=count($articulos)?> articulos</small></div>
    <a href="/soporte/kb/crear" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Nuevo Articulo</a>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-6"><input type="text" name="q" class="form-control" placeholder="Buscar en base de conocimiento..." value="<?=htmlspecialchars($search)?>"></div>
    <div class="col-md-3">
        <select name="modulo" class="form-select"><option value="">Todos modulos</option>
            <?php foreach ($modulos as $m): ?>
            <option value="<?=htmlspecialchars($m)?>" <?=$moduloFilter===$m?'selected':''?>><?=htmlspecialchars($m)?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3"><button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-search me-1"></i>Buscar</button></div>
</form>

<?php if (empty($articulos)): ?>
<div class="card-box text-center py-5"><h5 class="text-muted">No se encontraron articulos</h5><?php if($search): ?><p>Intenta con otros terminos de busqueda</p><?php endif; ?></div>
<?php else: ?>
<?php foreach ($articulos as $a): ?>
<div class="card-box mb-2" style="cursor:pointer" onclick="location.href='/soporte/kb/<?=$a['id']?>'">
    <div class="card-box-body">
        <strong><?=htmlspecialchars($a['titulo'])?></strong>
        <?php if ($a['modulo']??''): ?><span class="badge bg-secondary ms-1"><?=htmlspecialchars($a['modulo'])?></span><?php endif; ?>
        <span class="badge bg-light text-dark ms-1"><?=$a['vistas']?> vistas</span>
        <div style="font-size:.75rem;color:#64748b;margin-top:4px"><?=htmlspecialchars(mb_substr(strip_tags($a['contenido']),0,150))?>...</div>
        <?php if ($a['tags']??''): ?><div style="font-size:.65rem;margin-top:4px"><?php foreach (explode(',',$a['tags']) as $tag): ?><span class="badge bg-light text-dark me-1"><?=htmlspecialchars(trim($tag))?></span><?php endforeach; ?></div><?php endif; ?>
    </div>
</div>
<?php endforeach; endif; ?>
