<div class="setup-card">
    <div class="setup-header">
        <h1><i class="fas fa-user-shield"></i> Crear Administrador</h1>
        <p class="text-muted">Crea el usuario administrador del sistema</p>
    </div>
    
    <div class="setup-steps">
        <div class="setup-step completed">1</div>
        <div class="setup-step completed">2</div>
        <div class="setup-step active">3</div>
        <div class="setup-step pending">4</div>
    </div>
    
    <?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> 
        <?php
        $errores = [
            'campos_requeridos' => 'Todos los campos son obligatorios',
            'passwords_no_coinciden' => 'Las contraseñas no coinciden',
            'password_corto' => 'La contraseña debe tener al menos 8 caracteres'
        ];
        echo htmlspecialchars($errores[$_GET['error']] ?? $_GET['error']);
        ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="/setup/usuario">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control" required 
                       value="<?= htmlspecialchars($usuario['usuario_nombre'] ?? '') ?>"
                       placeholder="Juan">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Apellido</label>
                <input type="text" name="apellido" class="form-control" 
                       value="<?= htmlspecialchars($usuario['usuario_apellido'] ?? '') ?>"
                       placeholder="Pérez">
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required 
                   value="<?= htmlspecialchars($usuario['usuario_email'] ?? '') ?>"
                   placeholder="admin@miempresa.com">
            <small class="form-text">Este será tu usuario para iniciar sesión</small>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" required 
                       minlength="8" placeholder="Mínimo 8 caracteres">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                <input type="password" name="confirmar" class="form-control" required 
                       minlength="8" placeholder="Repite la contraseña">
            </div>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>Recomendación:</strong> Usa una contraseña segura con al menos 8 caracteres, incluyendo mayúsculas, minúsculas, números y símbolos.
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="/setup/empresa" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Anterior
            </a>
            <button type="submit" class="btn btn-primary">
                Siguiente: Finalizar <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>
