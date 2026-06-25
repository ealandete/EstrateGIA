<?php
$ok = $_GET['ok'] ?? null; $err = $_GET['err'] ?? null;
$seccion = $_GET['seccion'] ?? 'dashboard';
$anio = $_GET['anio'] ?? date('Y');
$sections = [
    'dashboard'    => ['icon' => 'gauge-high',         'label' => 'Dashboard'],
    'huella'       => ['icon' => 'cloud',              'label' => 'Huella Carbono'],
    'aspectos'     => ['icon' => 'seedling',           'label' => 'Aspectos AIA'],
    'controles'    => ['icon' => 'shield-haltered',    'label' => 'Controles'],
    'registros'    => ['icon' => 'chart-line',          'label' => 'Registros'],
    'plan'         => ['icon' => 'calendar-alt',        'label' => 'Plan Gesti&oacute;n'],
    'planes'       => ['icon' => 'tasks',               'label' => 'Planes Trabajo'],
    'metas'        => ['icon' => 'bullseye',            'label' => 'Metas'],
    'normatividad' => ['icon' => 'scale-balanced',      'label' => 'Normatividad'],
    'auditorias'   => ['icon' => 'search',              'label' => 'Auditor&iacute;as'],
    'autoevaluacion' => ['icon' => 'clipboard-check',   'label' => 'Autoevaluaci&oacute;n'],
    'reportes'     => ['icon' => 'file-pdf',            'label' => 'Reportes'],
];
?>
<?php if ($ok): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle me-2"></i>Operaci&oacute;n exitosa<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="d-flex justify-content-between mb-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-leaf me-2" style="color:#28a745"></i>Gesti&oacute;n Ambiental</h5>
        <small class="text-muted">ISO 14001:2015 + ISO 14064 &middot; <?= htmlspecialchars($empresa['empresa_nombre'] ?? '') ?></small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" style="width:100px" onchange="location.href='?seccion=<?= $seccion ?>&anio='+this.value">
            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
            <option value="<?= $y ?>" <?= $y == $anio ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
</div>

<ul class="nav nav-tabs nav-tabs-scroll small mb-3">
    <?php foreach ($sections as $key => $tab): ?>
    <li class="nav-item">
        <a class="nav-link <?= $seccion == $key ? 'active' : '' ?>" href="?seccion=<?= $key ?>&anio=<?= $anio ?>">
            <i class="fas fa-<?= $tab['icon'] ?> me-1" style="font-size:0.65rem"></i><?= $tab['label'] ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<?php
if ($seccion === 'dashboard')    require __DIR__ . '/ambiental_dashboard.php';
elseif ($seccion === 'huella')       require __DIR__ . '/ambiental_huella.php';
elseif ($seccion === 'aspectos')     require __DIR__ . '/ambiental_aspectos.php';
elseif ($seccion === 'controles')    require __DIR__ . '/ambiental_controles.php';
elseif ($seccion === 'registros')    require __DIR__ . '/ambiental_registros.php';
elseif ($seccion === 'plan')         require __DIR__ . '/ambiental_plan.php';
elseif ($seccion === 'planes')       require __DIR__ . '/ambiental_planes.php';
elseif ($seccion === 'metas')        require __DIR__ . '/ambiental_metas.php';
elseif ($seccion === 'normatividad') require __DIR__ . '/ambiental_normatividad.php';
elseif ($seccion === 'auditorias')   require __DIR__ . '/ambiental_auditorias.php';
elseif ($seccion === 'autoevaluacion') require __DIR__ . '/ambiental_autoevaluacion.php';
elseif ($seccion === 'reportes')     require __DIR__ . '/ambiental_reportes.php';
?>

<?php $moduloContexto = 'gesti&oacute;n ambiental (ISO 14001 + ISO 14064)'; require __DIR__ . '/ia_panel.php'; ?>
