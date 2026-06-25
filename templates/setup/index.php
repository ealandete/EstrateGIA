<div class="setup-card">
    <div class="setup-header">
        <h1><i class="fas fa-magic"></i> Configuración Inicial</h1>
        <p class="text-muted">Bienvenido a EstrateGIA. Vamos a configurar el sistema en 4 simples pasos.</p>
    </div>
    
    <div class="setup-steps">
        <div class="setup-step <?= $paso >= 1 ? ($paso > 1 ? 'completed' : 'active') : 'pending' ?>">1</div>
        <div class="setup-step <?= $paso >= 2 ? ($paso > 2 ? 'completed' : 'active') : 'pending' ?>">2</div>
        <div class="setup-step <?= $paso >= 3 ? ($paso > 3 ? 'completed' : 'active') : 'pending' ?>">3</div>
        <div class="setup-step <?= $paso >= 4 ? 'active' : 'pending' ?>">4</div>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($exito): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($exito) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($paso === 1): ?>
    <!-- Paso 1: Requisitos -->
    <div class="text-center">
        <h3>Paso 1: Verificar Requisitos del Sistema</h3>
        <p>Verificaremos que tu servidor cumpla con los requisitos necesarios.</p>
        <a href="/setup/requisitos" class="btn btn-primary btn-lg mt-3">
            <i class="fas fa-play"></i> Comenzar Verificación
        </a>
    </div>
    
    <?php elseif ($paso === 2): ?>
    <!-- Paso 2: Empresa -->
    <div class="text-center">
        <h3>Paso 2: Configurar Empresa Principal</h3>
        <p>Ingresa los datos de tu empresa o institución.</p>
        <a href="/setup/empresa" class="btn btn-primary btn-lg mt-3">
            <i class="fas fa-building"></i> Configurar Empresa
        </a>
    </div>
    
    <?php elseif ($paso === 3): ?>
    <!-- Paso 3: Usuario Admin -->
    <div class="text-center">
        <h3>Paso 3: Crear Usuario Administrador</h3>
        <p>Crea el usuario administrador del sistema.</p>
        <a href="/setup/usuario" class="btn btn-primary btn-lg mt-3">
            <i class="fas fa-user-shield"></i> Crear Administrador
        </a>
    </div>
    
    <?php elseif ($paso === 4): ?>
    <!-- Paso 4: Finalizar -->
    <div class="text-center">
        <h3>Paso 4: Finalizar Configuración</h3>
        <p>Revisa el resumen y completa la configuración.</p>
        <a href="/setup/finalizar" class="btn btn-success btn-lg mt-3">
            <i class="fas fa-flag-checkered"></i> Finalizar
        </a>
    </div>
    <?php endif; ?>
</div>
