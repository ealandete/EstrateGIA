<?php
/**
 * Portal del Proveedor - Autoevaluación Externa
 * Acceso sin login. El proveedor ingresa con código de evaluación.
 */
session_start();
require_once __DIR__ . '/../lib/EstrateGiaCore.php';
require_once __DIR__ . '/../lib/ProveedoresManager.php';

$core = EstrateGiaCore::getInstance();
$codigo = $_GET['codigo'] ?? '';
$paso = (int)($_GET['paso'] ?? 1);
$ok = $_GET['ok'] ?? null;
$error = '';

$proveedor = null;
if ($codigo) {
    $proveedor = $core->fetchOne("SELECT * FROM cal_proveedores WHERE prov_codigo = :c", ['c' => $codigo]);
    if (!$proveedor) $error = 'Código de proveedor no encontrado.';
} else {
    $error = 'Ingrese el código de proveedor para continuar.';
}

// Procesar autoevaluación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $proveedor) {
    $m = new ProveedoresManager();
    $criterios = $_POST['criterio'] ?? [];
    $m->evaluar($proveedor['prov_id'], $criterios, 'Autoevaluación del proveedor - ' . date('Y-m-d'));
    header('Location: ?codigo=' . $codigo . '&ok=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal del Proveedor - EstrateGIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .portal-card { max-width:650px; width:100%; background:white; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.08); }
    </style>
</head>
<body>
<div class="portal-card m-3">
    <div class="p-4 text-center" style="background:#1a1e34;color:white;border-radius:12px 12px 0 0">
        <i class="fas fa-building-check fs-1 mb-2 d-block"></i>
        <h4>Portal del Proveedor</h4>
        <small class="opacity-75">Autoevaluación de Calidad - EstrateGIA</small>
    </div>

    <div class="p-4">
        <?php if ($ok): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Autoevaluación enviada correctamente. Gracias por participar.</div>
        <?php elseif ($error && !$codigo): ?>
        <form method="GET" class="text-center">
            <p class="text-muted mb-3">Ingrese el código de proveedor asignado para acceder a la autoevaluación.</p>
            <input type="text" name="codigo" class="form-control mb-3 text-center" placeholder="Código (ej: PRV-2026-123)" style="font-size:1.2rem;letter-spacing:2px">
            <button class="btn btn-primary w-100"><i class="fas fa-arrow-right me-1"></i>Continuar</button>
        </form>
        <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <a href="?" class="btn btn-outline-secondary btn-sm">Volver</a>
        <?php elseif ($proveedor): ?>
        <?php $m = new ProveedoresManager(); $criterios = $m->getCriteriosPorTipo($proveedor['prov_tipo'] ?? 'servicios'); ?>
        <div class="mb-3">
            <h5><?= htmlspecialchars($proveedor['prov_nombre']) ?></h5>
            <small class="text-muted">Código: <?= $proveedor['prov_codigo'] ?> · Tipo: <?= $proveedor['prov_tipo'] ?></small>
        </div>
        <div class="alert alert-info small"><i class="fas fa-info-circle me-1"></i>Califique cada criterio de 0 a 100 según su autoevaluación. Sea objetivo.</div>
        <form method="POST">
            <?php foreach ($criterios as $c): ?>
            <div class="mb-3 p-3 border rounded" style="border-left:4px solid #007bff">
                <div class="d-flex justify-content-between mb-2">
                    <strong><?= $c['item'] ?></strong>
                    <span class="badge bg-primary">Peso: <?= $c['peso'] ?>%</span>
                </div>
                <small class="text-muted d-block mb-2"><?= $c['desc'] ?></small>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger" style="font-size:0.6rem">0</span>
                    <input type="range" name="criterio[<?= $c['id'] ?>]" class="form-range" min="0" max="100" value="80" oninput="this.nextElementSibling.value=this.value" style="flex:1">
                    <input type="number" class="form-control form-control-sm" style="width:65px" value="80" min="0" max="100" readonly>
                    <span class="badge bg-success" style="font-size:0.6rem">100</span>
                </div>
            </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-success btn-lg w-100"><i class="fas fa-paper-plane me-1"></i>Enviar Autoevaluación</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
