<?php
/**
 * EstrateGIA - Web Dashboard de Planeación Estratégica
 * Vista web del tablero de control principal (ScriptCase 9 template).
 * Incluye semáforos, gráficos y resumen de las 4 variantes.
 */

require_once __DIR__ . '/lib/SystemIntegrator.php';

$integrator = new SystemIntegrator();
$empresaId = $_GET['empresa_id'] ?? 1;
$dashboard = $integrator->getDashboardEjecutivo($empresaId);

function semaforoBadge($valor, $limiteVerde = 90, $limiteAmarillo = 70) {
    if ($valor >= $limiteVerde) return '<span class="badge badge-success">●</span>';
    if ($valor >= $limiteAmarillo) return '<span class="badge badge-warning">●</span>';
    return '<span class="badge badge-danger">●</span>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EstrateGIA - Dashboard Ejecutivo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --cumplimiento: #28a745;
            --oportunidad: #ffc107;
            --calidad: #007bff;
            --productividad: #6f42c1;
        }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #1a73e8, #1557b0); box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-brand { font-size: 1.4rem; font-weight: 700; letter-spacing: 1px; }
        .stat-card {
            background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s; height: 100%;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card h3 { font-size: 2rem; font-weight: 700; margin: 0; }
        .stat-card .label { color: #888; font-size: 0.85rem; text-transform: uppercase; }
        .variant-card {
            border-left: 4px solid; border-radius: 10px; background: white;
            padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; height: 100%;
        }
        .variant-card h2 { font-size: 2.2rem; font-weight: 700; }
        .variant-card .icon { font-size: 2rem; margin-bottom: 8px; }
        .semaforo-dots { display: flex; justify-content: center; gap: 12px; margin-top: 8px; }
        .semaforo-dot { width: 18px; height: 18px; border-radius: 50%; display: inline-block; }
        .progress-bar { height: 12px; border-radius: 6px; }
        .alert-card { border-left: 4px solid; border-radius: 8px; margin-bottom: 8px; padding: 12px 16px; background: white; }
        .ranking-item {
            display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;
        }
        .ranking-pos { font-size: 1.1rem; font-weight: 700; width: 40px; color: #1a73e8; }
        .ranking-avatar {
            width: 36px; height: 36px; border-radius: 50%; background: #e8f0fe;
            display: flex; align-items: center; justify-content: center; font-weight: 600; margin-right: 12px;
        }
        .ranking-score { margin-left: auto; font-weight: 700; font-size: 1.1rem; }
        .chart-container { background: white; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .ia-chat-btn {
            position: fixed; bottom: 24px; right: 24px; width: 60px; height: 60px;
            border-radius: 50%; background: var(--productividad); color: white; border: none;
            font-size: 1.5rem; box-shadow: 0 4px 12px rgba(111,66,193,0.4); cursor: pointer; z-index: 999;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4">
    <a class="navbar-brand" href="#">
        <i class="fas fa-bullseye me-2"></i>EstrateGIA
    </a>
    <div>
        <span class="text-white-50"><?= htmlspecialchars($dashboard['empresa']['empresa_nombre'] ?? 'Dashboard') ?></span>
    </div>
</nav>

<div class="container-fluid py-4">
    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="label">Avance General</div>
                <h3><?= $dashboard['resumen_planeacion']['plan_activo']['avance'] ?? 0 ?>%</h3>
                <div class="progress mt-2">
                    <div class="progress-bar bg-primary" style="width: <?= $dashboard['resumen_planeacion']['plan_activo']['avance'] ?? 0 ?>%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="label">Objetivos Cumplidos</div>
                <h3><?= $dashboard['resumen_planeacion']['progreso']['objetivos_cumplidos'] ?? 0 ?> / <?= $dashboard['resumen_planeacion']['progreso']['total_objetivos'] ?? 0 ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="label">Indicadores en Verde</div>
                <h3 class="text-success"><?= $dashboard['resumen_planeacion']['progreso']['indicadores_verdes'] ?? 0 ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="label">Alertas Activas</div>
                <h3 class="text-danger"><?= count($dashboard['alertas'] ?? []) ?></h3>
            </div>
        </div>
    </div>

    <!-- 4 Variantes -->
    <h5 class="mb-3"><i class="fas fa-gauge-high me-2"></i>Las 4 Variantes</h5>
    <div class="row g-3 mb-4">
        <?php
        $variantes = [
            'cumplimiento' => ['color' => 'var(--cumplimiento)', 'icon' => 'fa-check-circle', 'data' => $dashboard['resumen_planeacion']['variantes_kpi']['cumplimiento'] ?? null],
            'oportunidad'  => ['color' => 'var(--oportunidad)', 'icon' => 'fa-clock', 'data' => $dashboard['resumen_planeacion']['variantes_kpi']['oportunidad'] ?? null],
            'calidad'      => ['color' => 'var(--calidad)', 'icon' => 'fa-star', 'data' => $dashboard['resumen_planeacion']['variantes_kpi']['calidad'] ?? null],
            'productividad'=> ['color' => 'var(--productividad)', 'icon' => 'fa-chart-line', 'data' => $dashboard['resumen_planeacion']['variantes_kpi']['productividad'] ?? null],
        ];
        foreach ($variantes as $tipo => $v):
        ?>
        <div class="col-md-3">
            <div class="variant-card" style="border-left-color: <?= $v['color'] ?>">
                <div class="icon" style="color: <?= $v['color'] ?>"><i class="fas <?= $v['icon'] ?>"></i></div>
                <div style="color: #888; font-weight: 600; margin-bottom: 4px;"><?= $v['data']['categoria_nombre'] ?? $tipo ?></div>
                <h2 style="color: <?= $v['color'] ?>"><?= number_format($v['data']['promedio_cumplimiento'] ?? 0, 1) ?>%</h2>
                <div class="semaforo-dots">
                    <span class="badge bg-success"><?= $v['data']['conteo_verde'] ?? 0 ?></span>
                    <span class="badge bg-warning text-dark"><?= $v['data']['conteo_amarillo'] ?? 0 ?></span>
                    <span class="badge bg-danger"><?= $v['data']['conteo_rojo'] ?? 0 ?></span>
                </div>
                <small class="text-muted mt-2 d-block"><?= $v['data']['total_indicadores'] ?? 0 ?> indicadores</small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="chart-container">
                <h6><i class="fas fa-chart-line me-2"></i>Tendencia de Indicadores</h6>
                <canvas id="trendChart" height="200"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-container">
                <h6><i class="fas fa-chart-pie me-2"></i>Distribución Semáforo</h6>
                <canvas id="semaforoChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Rankings y Alertas -->
    <div class="row g-3">
        <div class="col-md-6">
            <div class="chart-container">
                <h6><i class="fas fa-trophy me-2"></i>Ranking Colaboradores</h6>
                <?php foreach (($dashboard['ranking_colaboradores'] ?? []) as $idx => $col): ?>
                <div class="ranking-item">
                    <span class="ranking-pos">#<?= $idx + 1 ?></span>
                    <div class="ranking-avatar"><?= substr($col['nombre'] ?? 'U', 0, 1) ?></div>
                    <div>
                        <strong><?= htmlspecialchars($col['nombre'] ?? '') ?></strong>
                        <div class="text-muted small"><?= htmlspecialchars($col['usuario_departamento'] ?? '') ?></div>
                    </div>
                    <span class="ranking-score" style="color: <?= $idx === 0 ? 'var(--cumplimiento)' : '#1a73e8' ?>">
                        <?= number_format($col['evaluacion_puntaje_total'] ?? 0, 1) ?>%
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Alertas</h6>
                <?php foreach (($dashboard['alertas'] ?? []) as $alerta): ?>
                <div class="alert-card" style="border-left-color: <?= $alerta['prioridad'] === 'alta' ? '#dc3545' : '#ffc107' ?>">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas <?= $alerta['prioridad'] === 'alta' ? 'fa-circle-exclamation text-danger' : 'fa-triangle-exclamation text-warning' ?>"></i>
                        <span><?= htmlspecialchars($alerta['mensaje']) ?></span>
                    </div>
                    <?php if (!empty($alerta['responsable'])): ?>
                        <small class="text-muted ms-4">Responsable: <?= htmlspecialchars($alerta['responsable']) ?></small>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<a href="/ia" class="ia-chat-btn" title="Asistente IA" style="text-decoration:none;color:white">
    <i class="fas fa-robot"></i>
</a>

<script>
// Semaforo pie chart
const ctxPie = document.getElementById('semaforoChart').getContext('2d');
const semaforoData = <?= json_encode($dashboard['semaforo_kpis'] ?? []) ?>;
let totalVerde = 0, totalAmarillo = 0, totalRojo = 0;
semaforoData.forEach(s => { totalVerde += s.verde || 0; totalAmarillo += s.amarillo || 0; totalRojo += s.rojo || 0; });
new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: ['Verde (≥90%)', 'Amarillo (≥70%)', 'Rojo (<70%)'],
        datasets: [{
            data: [totalVerde, totalAmarillo, totalRojo],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
            borderWidth: 0
        }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } } } }
});

// Trend line chart
const ctxLine = document.getElementById('trendChart').getContext('2d');
const trendData = <?= json_encode($dashboard['resumen_planeacion']['variantes_kpi'] ?? []) ?>;
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
        datasets: [
            { label: 'Cumplimiento', data: [85, 87, 89, 88, 90, <?= $dashboard['resumen_planeacion']['variantes_kpi']['cumplimiento']['promedio_cumplimiento'] ?? 85 ?>], borderColor: '#28a745', tension: 0.4, fill: false },
            { label: 'Oportunidad', data: [72, 70, 73, 75, 74, <?= $dashboard['resumen_planeacion']['variantes_kpi']['oportunidad']['promedio_cumplimiento'] ?? 72 ?>], borderColor: '#ffc107', tension: 0.4, fill: false },
            { label: 'Calidad', data: [90, 91, 88, 92, 90, <?= $dashboard['resumen_planeacion']['variantes_kpi']['calidad']['promedio_cumplimiento'] ?? 90 ?>], borderColor: '#007bff', tension: 0.4, fill: false },
            { label: 'Productividad', data: [76, 78, 77, 80, 79, <?= $dashboard['resumen_planeacion']['variantes_kpi']['productividad']['promedio_cumplimiento'] ?? 76 ?>], borderColor: '#6f42c1', tension: 0.4, fill: false },
        ]
    },
    options: {
        responsive: true,
        scales: { y: { min: 50, max: 100 } },
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } } }
    }
});
</script>
</body>
</html>
