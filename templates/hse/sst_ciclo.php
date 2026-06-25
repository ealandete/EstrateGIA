<h6><i class="fas fa-arrows-spin me-2" style="color:#6f42c1"></i>Ciclo PHVA — Mejora Continua SST</h6>
<p class="small text-muted mb-3">ISO 45001:2018 §10 — El ciclo Planificar-Hacer-Verificar-Actuar reinicia desde los resultados de este período</p>

<div class="row g-4">
    <?php
    $phva = [
        ['P','Planificar','Establecer objetivos, identificar peligros, definir plan de trabajo, asignar recursos. Revisar la matriz de peligros y normatividad vigente.', '/sst?seccion=peligros', '#007bff'],
        ['H','Hacer','Implementar el plan: capacitaciones, inspecciones, exámenes, simulacros. Ejecutar actividades programadas y reportar incidentes.', '/sst?seccion=plan', '#28a745'],
        ['V','Verificar','Medir resultados: indicadores de frecuencia/severidad, auditorías internas, requisitos legales, investigación de incidentes.', '/sst?seccion=dashboard', '#ffc107'],
        ['A','Actuar','Revisión gerencial: acciones correctivas, actualizar matriz de riesgos, ajustar plan de trabajo, iniciar nuevo ciclo desde autoevaluación.', '/sst?seccion=autoevaluacion', '#dc3545'],
    ];
    foreach ($phva as $f):
    ?>
    <div class="col-md-3">
        <a href="<?= $f[4] ?>" class="text-decoration-none">
            <div class="card-box text-center p-4" style="border-top:4px solid <?= $f[5] ?>;transition:transform 0.15s">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;background:<?= $f[5] ?>;color:#fff;font-size:1.5rem;font-weight:bold"><?= $f[0] ?></div>
                <h6 style="color:<?= $f[5] ?>"><?= $f[1] ?></h6>
                <p class="small text-muted"><?= $f[2] ?></p>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="card-box mt-4">
    <div class="card-box-header"><i class="fas fa-history me-2"></i>Historial de Ciclos PHVA</div>
    <div class="card-box-body p-0"><table class="table-box small">
        <thead><tr><th>Período</th><th>Autoevaluación</th><th>Accidentes</th><th>Días perdidos</th><th>Capacitaciones</th><th>Cobertura exámenes</th><th>Estado</th></tr></thead>
        <tbody>
            <tr><td colspan="7" class="text-center text-muted py-3">Los ciclos se generan automáticamente al completar cada período anual</td></tr>
        </tbody>
    </table></div>
</div>
