<nav class="mb-3"><ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="/planeacion">Planes</a></li>
    <li class="breadcrumb-item"><a href="/planeacion/<?= $plan['plan_id'] ?>"><?= htmlspecialchars($plan['plan_nombre']) ?></a></li>
    <li class="breadcrumb-item active">Editar</li>
</ol></nav>

<div class="card-box">
    <div class="card-box-header"><i class="fas fa-edit me-2"></i>Editar Plan: <?= htmlspecialchars($plan['plan_nombre']) ?></div>
    <div class="card-box-body">
        <form method="POST" action="/planeacion/<?= $plan['plan_id'] ?>/update">
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label">Nombre del Plan *</label>
                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($plan['plan_nombre']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="borrador" <?= $plan['plan_estado']=='borrador'?'selected':'' ?>>Borrador</option>
                        <option value="en_proceso" <?= $plan['plan_estado']=='en_proceso'?'selected':'' ?>>En Proceso</option>
                        <option value="aprobado" <?= $plan['plan_estado']=='aprobado'?'selected':'' ?>>Aprobado</option>
                        <option value="ejecucion" <?= $plan['plan_estado']=='ejecucion'?'selected':'' ?>>Ejecución</option>
                        <option value="completado" <?= $plan['plan_estado']=='completado'?'selected':'' ?>>Completado</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($plan['plan_descripcion'] ?? '') ?></textarea>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4"><label class="form-label">Fecha Inicio</label><input type="date" name="fecha_inicio" class="form-control" value="<?= $plan['plan_fecha_inicio'] ?? '' ?>"></div>
                <div class="col-md-4"><label class="form-label">Fecha Fin</label><input type="date" name="fecha_fin" class="form-control" value="<?= $plan['plan_fecha_fin'] ?? '' ?>"></div>
                <div class="col-md-4"><label class="form-label">Presupuesto Total</label><input type="number" name="presupuesto" class="form-control" step="0.01" value="<?= $plan['plan_presupuesto_total'] ?? '' ?>"></div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Cambios</button>
                <a href="/planeacion/<?= $plan['plan_id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
