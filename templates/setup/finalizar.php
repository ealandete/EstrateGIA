<div class="setup-card">
    <div class="setup-header">
        <h1><i class="fas fa-flag-checkered"></i> Finalizar Configuración</h1>
        <p class="text-muted">Revisa el resumen y completa la configuración</p>
    </div>
    
    <div class="setup-steps">
        <div class="setup-step completed">1</div>
        <div class="setup-step completed">2</div>
        <div class="setup-step completed">3</div>
        <div class="setup-step active">4</div>
    </div>
    
    <div class="mt-4">
        <h4 class="mb-3">Resumen de Configuración</h4>
        
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-building"></i> Empresa
            </div>
            <div class="card-body">
                <?php if ($resumen['empresa']): ?>
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($resumen['empresa']['empresa_nombre']) ?></p>
                    <p><strong>NIT:</strong> <?= htmlspecialchars($resumen['empresa']['empresa_nit'] ?? 'No definido') ?></p>
                    <p><strong>Sector:</strong> <?= htmlspecialchars($resumen['empresa']['empresa_sector'] ?? 'No definido') ?></p>
                <?php else: ?>
                    <p class="text-danger">⚠️ No se ha configurado ninguna empresa</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <i class="fas fa-user-shield"></i> Administrador
            </div>
            <div class="card-body">
                <?php if ($resumen['usuario']): ?>
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($resumen['usuario']['usuario_nombre'] . ' ' . ($resumen['usuario']['usuario_apellido'] ?? '')) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($resumen['usuario']['usuario_email']) ?></p>
                    <p><strong>Rol:</strong> Super Administrador</p>
                <?php else: ?>
                    <p class="text-danger">⚠️ No se ha creado ningún administrador</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <i class="fas fa-chart-line"></i> Plan Estratégico
            </div>
            <div class="card-body">
                <p><strong>Planes existentes:</strong> <?= $resumen['planes'] ?></p>
                <p class="text-muted">Se creará automáticamente un plan estratégico inicial para el año actual.</p>
            </div>
        </div>
    </div>
    
    <?php if ($resumen['empresa'] && $resumen['usuario']): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> ¡Todo listo! Puedes finalizar la configuración.
        </div>
        
        <form method="POST" action="/setup/finalizar">
            <div class="d-flex justify-content-between mt-4">
                <a href="/setup/usuario" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Anterior
                </a>
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-rocket"></i> Finalizar e Iniciar EstrateGIA
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Debes completar todos los pasos antes de finalizar.
        </div>
        <a href="/setup" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al inicio
        </a>
    <?php endif; ?>
</div>
