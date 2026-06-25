<div class="d-flex justify-content-between mb-3">
    <div><h5><i class="fas fa-plus-circle me-2" style="color:#6f42c1"></i>Crear Ticket de Soporte</h5></div>
</div>

<div class="card-box">
    <div class="card-box-body">
    <form method="POST">
        <div class="mb-3"><label class="form-label">Asunto *</label><input type="text" name="asunto" class="form-control" required maxlength="300"></div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">Modulo</label>
                <select name="modulo" class="form-select">
                    <option value="">General / Sistema</option>
                    <?php foreach ($modulosDisponibles as $m): ?>
                    <option value="<?=$m?>"><?=ucfirst($m)?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Prioridad</label>
                <select name="prioridad" class="form-select">
                    <option value="MEDIA">Media (SLA: 8h)</option>
                    <option value="BAJA">Baja (SLA: 24h)</option>
                    <option value="ALTA">Alta (SLA: 4h)</option>
                    <option value="CRITICA">Critica (SLA: 1h)</option>
                </select>
            </div>
        </div>
        <div class="mb-3"><label class="form-label">Descripcion *</label><textarea name="descripcion" class="form-control" rows="5" required></textarea></div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Crear Ticket</button>
            <a href="/soporte/tickets" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
    </div>
</div>
