<div class="d-flex justify-content-between mb-3">
    <div><h5><i class="fas fa-plus-circle me-2" style="color:#2563eb"></i>Nuevo Articulo KB</h5></div>
    <a href="/soporte/kb" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Volver a KB</a>
</div>

<div class="card-box">
    <div class="card-box-body">
    <form method="POST">
        <div class="mb-3"><label class="form-label">Titulo *</label><input type="text" name="titulo" class="form-control" required maxlength="200"></div>
        <div class="mb-3"><label class="form-label">Modulo</label><input type="text" name="modulo" class="form-control" placeholder="Ej: planeacion, sst, sistema"></div>
        <div class="mb-3"><label class="form-label">Contenido *</label><textarea name="contenido" class="form-control" rows="8" required></textarea></div>
        <div class="mb-3"><label class="form-label">Tags (separados por coma)</label><input type="text" name="tags" class="form-control" placeholder="Ej: login, error, acceso"></div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Crear Articulo</button>
            <a href="/soporte/kb" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
    </div>
</div>
