<?php
declare(strict_types=1);

/**
 * SSE (Server-Sent Events) - Dashboard en tiempo real
 * 
 * Endpoint: GET /sse/dashboard?empresa_id=X
 * Cliente JS: new EventSource('/sse/dashboard?empresa_id=X')
 * 
 * Envia metricas ambientales en tiempo real cada 5 segundos
 */
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');

if (session_status() === PHP_SESSION_NONE) session_start();

// Verificar autenticacion
if (!isset($_SESSION['auth_user'])) {
    echo "event: error\ndata: " . json_encode(['error' => 'No autenticado']) . "\n\n";
    flush();
    exit;
}

$empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
$intervalo = min(10, max(1, (int)($_GET['interval'] ?? 5)));

require_once BASE_PATH . '/lib/SafeQuery.php';
require_once BASE_PATH . '/lib/BaseHSEManager.php';
require_once BASE_PATH . '/lib/AmbientalManager.php';

$m = new AmbientalManager();
$sent = 0;
$maxEvents = (int)($_GET['max'] ?? 120);

while ($sent < $maxEvents) {
    if (connection_aborted()) break;

    try {
        $anio = (int)date('Y');
        $estadisticas = $m->getEstadisticasAmbiental($empresaId, $anio);
        $huella = $m->getHuellaCarbono($empresaId, $anio);
        $programas = $m->getDashboardProgramas($empresaId);

        $payload = json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'empresa_id' => $empresaId,
            'estadisticas' => $estadisticas,
            'huella' => $huella,
            'programas' => $programas,
            'alertas' => array_filter($programas, fn($p) => ($p['alerta'] ?? '') === 'critica'),
        ]);

        echo "event: update\n";
        echo "data: $payload\n\n";
    } catch (\Throwable $e) {
        echo "event: error\n";
        echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    }

    ob_flush(); flush();
    $sent++;
    sleep($intervalo);
}
