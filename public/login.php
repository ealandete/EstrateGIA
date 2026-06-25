<?php
session_start();
require_once __DIR__ . '/../lib/EstrateGiaCore.php';
require_once __DIR__ . '/../src/Auth.php';

if (isset($_GET['logout'])) {
    Auth::logout();
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if (Auth::login($email, $password)) {
        if (Auth::needs2FA()) {
            header('Location: /login.php?2fa=1');
        } else {
            header('Location: /index.php');
        }
        exit;
    }
    $error = 'Credenciales inválidas';
}

// 2FA verification
if (isset($_GET['2fa']) && Auth::needs2FA()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['2fa_code'])) {
        if (Auth::verify2FA($_POST['2fa_code'])) {
            header('Location: /index.php');
            exit;
        }
        $error2fa = 'Código inválido';
    }
}

if (Auth::check()) {
    header('Location: /index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EstrateGIA - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0d1b2a 0%, #1b2a3a 50%, #1a3a4a 100%);
        }
        .login-card {
            background: white;
            border-radius: 16px;
            padding: 48px 40px;
            width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-brand {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-brand h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a73e8;
            letter-spacing: 1px;
            margin: 0;
        }
        .login-brand p {
            color: #888;
            margin: 4px 0 0;
            font-size: 0.9rem;
        }
        .login-brand .ia-badge {
            display: inline-block;
            background: #f0f0ff;
            color: #6f42c1;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 8px;
        }
        .alert { border-radius: 10px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-brand">
            <i class="fas fa-bullseye" style="font-size:2.5rem;color:#1a73e8;margin-bottom:8px;"></i>
            <h1>EstrateGIA</h1>
            <p>Gestión de Planeación Estratégica</p>
            <span class="ia-badge"><i class="fas fa-brain me-1"></i>con IA</span>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-triangle-exclamation me-2"></i><?= $error ?></div>
        <?php endif; ?>

        <?php if (Auth::needs2FA()): ?>
        <div class="alert alert-info"><i class="fas fa-shield-halved me-2"></i>Verificación en dos pasos</div>
        <?php if (isset($error2fa)): ?><div class="alert alert-danger"><?= $error2fa ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Código de autenticación (6 dígitos)</label>
                <input type="text" name="2fa_code" class="form-control text-center" style="font-size:1.5rem;letter-spacing:8px" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg">Verificar</button>
        </form>
        <hr class="my-3">
        <form method="POST" action="/login.php"><button class="btn btn-sm btn-outline-secondary w-100">Cancelar — Volver al login</button></form>
        <?php else: ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="tu@email.com" value="admin@estrategia.com" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Contraseña" value="admin123" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:1rem;">
                <i class="fas fa-right-to-bracket me-2"></i>Ingresar
            </button>
        </form>
        <?php endif; ?>
    </div>
    </div>
</body>
</html>
