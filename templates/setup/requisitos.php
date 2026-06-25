<div class="setup-card">
    <div class="setup-header">
        <h1><i class="fas fa-server"></i> Requisitos del Sistema</h1>
        <p class="text-muted">Verificando que tu servidor cumpla con los requisitos necesarios</p>
    </div>
    
    <div class="setup-steps">
        <div class="setup-step active">1</div>
        <div class="setup-step pending">2</div>
        <div class="setup-step pending">3</div>
        <div class="setup-step pending">4</div>
    </div>
    
    <div class="mt-4">
        <?php foreach ($checks as $check => $ok): ?>
        <div class="check-item <?= $ok ? 'check-ok' : 'check-fail' ?>">
            <i class="fas fa-<?= $ok ? 'check-circle' : 'times-circle' ?>"></i>
            <span><?= htmlspecialchars($check) ?></span>
            <span class="ms-auto"><?= $ok ? '✅ OK' : '❌ Fallo' ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-4">
        <?php if ($todosOk): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> ¡Todos los requisitos cumplidos!
            </div>
            <a href="/setup/empresa" class="btn btn-primary btn-lg">
                Siguiente: Configurar Empresa <i class="fas fa-arrow-right"></i>
            </a>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Algunos requisitos no se cumplen. Por favor corrige los problemas antes de continuar.
            </div>
            <a href="/setup/requisitos" class="btn btn-secondary">
                <i class="fas fa-redo"></i> Reintentar
            </a>
        <?php endif; ?>
    </div>
</div>
