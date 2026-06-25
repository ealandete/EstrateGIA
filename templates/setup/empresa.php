<div class="setup-card">
    <div class="setup-header">
        <h1><i class="fas fa-building"></i> Configurar Empresa</h1>
        <p class="text-muted">Ingresa los datos de tu empresa o institución</p>
    </div>
    
    <div class="setup-steps">
        <div class="setup-step completed">1</div>
        <div class="setup-step active">2</div>
        <div class="setup-step pending">3</div>
        <div class="setup-step pending">4</div>
    </div>
    
    <?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_GET['error']) ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="/setup/empresa">
        <div class="mb-3">
            <label class="form-label">Nombre de la Empresa <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control" required 
                   value="<?= htmlspecialchars($empresa['empresa_nombre'] ?? '') ?>"
                   placeholder="Ej: Mi Empresa S.A.S.">
        </div>
        
        <div class="mb-3">
            <label class="form-label">NIT / RUC / ID Fiscal</label>
            <input type="text" name="nit" class="form-control" 
                   value="<?= htmlspecialchars($empresa['empresa_nit'] ?? '') ?>"
                   placeholder="Ej: 900.123.456-7">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Sector</label>
            <select name="sector" class="form-select">
                <option value="">Seleccionar...</option>
                <option value="salud" <?= ($empresa['empresa_sector'] ?? '') === 'salud' ? 'selected' : '' ?>>Salud</option>
                <option value="educacion" <?= ($empresa['empresa_sector'] ?? '') === 'educacion' ? 'selected' : '' ?>>Educación</option>
                <option value="manufactura" <?= ($empresa['empresa_sector'] ?? '') === 'manufactura' ? 'selected' : '' ?>>Manufactura</option>
                <option value="servicios" <?= ($empresa['empresa_sector'] ?? '') === 'servicios' ? 'selected' : '' ?>>Servicios</option>
                <option value="tecnologia" <?= ($empresa['empresa_sector'] ?? '') === 'tecnologia' ? 'selected' : '' ?>>Tecnología</option>
                <option value="gobierno" <?= ($empresa['empresa_sector'] ?? '') === 'gobierno' ? 'selected' : '' ?>>Gobierno</option>
                <option value="otro" <?= ($empresa['empresa_sector'] ?? '') === 'otro' ? 'selected' : '' ?>>Otro</option>
            </select>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="/setup?error=cancelado" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Anterior
            </a>
            <button type="submit" class="btn btn-primary">
                Siguiente: Crear Administrador <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>
