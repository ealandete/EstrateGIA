<?php
$core = \EstrateGiaCore::getInstance();
// Usar __route si está disponible (routing de nginx)
$currentPath = $_GET['__route'] ?? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentPath = '/' . trim($currentPath, '/');
if ($currentPath === '/index.php' || $currentPath === '/') $currentPath = '/';

// Detectar plan activo para contexto de navegación
$navPlanId = ($_COOKIE['plan_activo'] ?? null) ?: ($_GET['plan_id'] ?? null);

// Cargar configuración de empresa activa para estilos y parametrización
$empresaActivaId = $core->getEmpresaActiva();
$empresaConfig = $core->getEmpresaConfig($empresaActivaId);
$empresaColorPrimario = $empresaConfig['empresa_color_primario']['valor'] ?? '#1a73e8';
$empresaColorSecundario = $empresaConfig['empresa_color_secundario']['valor'] ?? '#1557b0';
$empresaLogoUrl = $empresaConfig['empresa_logo_url']['valor'] ?? '';
$empresaModoOscuro = (int)($empresaConfig['empresa_modo_oscuro_default']['valor'] ?? 0);
$empresaIdioma = $empresaConfig['empresa_idioma_default']['valor'] ?? 'es';

$menuGroups = [
    '📊 Estratégico' => [
        ['label' => 'Planeación',    'icon' => 'bullseye',     'path' => '/planeacion', 'planAware' => false],
        ['label' => 'Dashboard',     'icon' => 'chart-line',   'path' => $navPlanId ? '/planeacion/' . $navPlanId : '/planeacion', 'planAware' => false],
        ['label' => 'PHVA',          'icon' => 'sync-alt',     'path' => '/phva'],
        ['label' => 'Calendario',    'icon' => 'calendar-alt', 'path' => '/calendario'],
        ['label' => 'Evaluación',    'icon' => 'user-check',   'path' => '/evaluacion' . ($navPlanId ? '?plan_id=' . $navPlanId : '')],
        ['label' => 'IA Asistente',  'icon' => 'brain',        'path' => '/ia'],
    ],
    '⚙️ Operativo' => [
        ['label' => 'Procesos',      'icon' => 'diagram-project','path' => '/procesos'],
        ['label' => 'Indicadores',   'icon' => 'gauge-high',   'path' => '/indicadores'],
        ['label' => 'Mediciones',    'icon' => 'plus-circle',  'path' => '/mediciones'],
        ['label' => 'Documentos ISO','icon' => 'file-lines',   'path' => '/documentos'],
    ],
    '✅ Calidad' => [
        ['label' => 'Acreditación',  'icon' => 'certificate',  'path' => '/calidad'],
        ['label' => 'PAMEC',         'icon' => 'search',       'path' => '/calidad/pamec'],
        ['label' => 'NC',            'icon' => 'triangle-exclamation','path' => '/nc'],
        ['label' => 'Riesgos',       'icon' => 'bolt',         'path' => '/calidad/riesgos'],
        ['label' => 'Proveedores',   'icon' => 'truck',        'path' => '/proveedores'],
        ['label' => 'Formación',     'icon' => 'graduation-cap','path' => '/formacion'],
        ['label' => 'Satisfacción',  'icon' => 'face-smile',   'path' => '/satisfaccion'],
        ['label' => 'SST',           'icon' => 'hard-hat',     'path' => '/sst'],
        ['label' => 'Ambiental',     'icon' => 'leaf',         'path' => '/ambiental'],
    ],
    '🔌 Integraciones' => [
        ['label' => 'CRM / Datos',   'icon' => 'plug',         'path' => '/crm'],
        ['label' => 'Minería',       'icon' => 'database',     'path' => '/indicadores'],
    ],
    '🔧 Sistema' => [
        ['label' => 'Usuarios',      'icon' => 'users',        'path' => '/admin/usuarios'],
        ['label' => 'Permisos',      'icon' => 'shield-halved','path' => '/admin/roles'],
        ['label' => 'Auditoría',     'icon' => 'history',      'path' => '/admin/auditoria'],
        ['label' => 'Configuración', 'icon' => 'gear',         'path' => '/admin/config'],
        ['label' => 'Documentación', 'icon' => 'book',         'path' => '/documentacion'],
        ['label' => 'Soporte',       'icon' => 'headset',      'path' => '/soporte'],
    ],
];
$userRol = (int)($_SESSION['auth_user']['usuario_rol_id'] ?? 0);
$adminRoles = [1]; // Super Admin (rol_id=1 en sys_roles)
$stmt = $core->getConnection()->query("SELECT rol_id FROM sys_roles WHERE rol_nombre LIKE '%ADMIN%' AND rol_id > 1");
if ($stmt) { while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $adminRoles[] = (int)$row['rol_id']; }
if (!in_array($userRol, $adminRoles)) unset($menuGroups['🔧 Sistema']);
?>
<?php
if (defined('IS_AJAX') && IS_AJAX) {
    echo $content ?? '';
    return;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EstrateGIA - <?= $pageTitle ?? 'Planeación Estratégica' ?></title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="/assets/css/app.css?v=22">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="<?= $empresaColorPrimario ?>">
    <style>
        :root {
            --primary-color: <?= $empresaColorPrimario ?>;
            --primary-color-hover: <?= $empresaColorSecundario ?>;
            --primary-color-light: <?= $empresaColorPrimario ?>1a;
        }
    </style>
    <script src="/assets/js/chart.min.js"></script>
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <?php if ($empresaLogoUrl): ?>
                <img src="<?= htmlspecialchars($empresaLogoUrl) ?>" alt="Logo" style="height:28px;width:auto;">
            <?php else: ?>
                <i class="fas fa-bullseye"></i>
            <?php endif; ?>
            <span><?= htmlspecialchars($empresaConfig['empresa_nombre_corto']['valor'] ?? 'EstrateGIA') ?></span>
        </div>
        <nav class="sidebar-nav">
            <?php $gid = 0; foreach ($menuGroups as $groupName => $items): $gid++; ?>
            <div class="sidebar-group">
                <div class="sidebar-group-title" onclick="toggleGroup('group_<?= $gid ?>')" style="cursor:pointer">
                    <i class="fas fa-chevron-down group-arrow" id="arrow_group_<?= $gid ?>" style="font-size:0.6rem;margin-right:4px;transition:transform 0.2s"></i>
                    <?= $groupName ?>
                </div>
                <div class="sidebar-group-items" id="group_<?= $gid ?>">
                <?php foreach ($items as $item): ?>
                <a href="<?= $item['path'] ?>" class="sidebar-link <?= ($item['path']==='/' && ($currentPath==='/'||$currentPath==='')) ? 'active' : (str_starts_with($currentPath, $item['path']) && $item['path']!=='/' ? 'active' : '') ?>">
                    <i class="fas fa-<?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                </a>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr(\Auth::userName(), 0, 2)) ?></div>
                <div>
                    <div class="user-name"><?= \Auth::userName() ?></div>
                    <div class="user-role"><?= \Auth::userCargo() ?></div>
                </div>
            </div>
            <a href="/login.php?logout=1" class="btn-logout" title="Cerrar sesión">
                <i class="fas fa-right-from-bracket"></i>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <h5 class="mb-0"><?= $pageTitle ?? 'Dashboard' ?></h5>
                <?php 
                require_once BASE_PATH . '/lib/PlanManager.php';
                $pm = new PlanManager();
                $todosPlanes = $pm->getPlanes();
                $todasEmpresas = $pm->getEmpresas();
                // Default: empresa con plan activo, no la primera alfabética
                $empresaActivaDefault = $todasEmpresas[0]['empresa_id'] ?? 1;
                foreach ($todosPlanes as $p) {
                    if (in_array($p['plan_estado'], ['completado','ejecucion','en_proceso','aprobado'])) {
                        $empresaActivaDefault = $p['plan_empresa_id'];
                        break;
                    }
                }
                $planActual = (int)($_GET['plan_id'] ?? ($_COOKIE['plan_activo'] ?? ($todosPlanes[0]['plan_id'] ?? 1)));
                $empresaActual = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? $empresaActivaDefault));
                ?>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small fw-bold">Empresa:</span>
                    <select id="selEmpresa" class="form-select form-select-sm" style="width:180px;font-size:0.75rem">
                        <?php foreach ($todasEmpresas as $e): ?>
                        <option value="<?= $e['empresa_id'] ?>" <?= $empresaActual == $e['empresa_id'] ? 'selected' : '' ?>>
                            🏢 <?= htmlspecialchars(substr($e['empresa_nombre'], 0, 22)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="text-muted small fw-bold">Plan:</span>
                    <select id="selPlan" class="form-select form-select-sm" style="width:220px;font-size:0.75rem">
                        <?php 
                        $planesFiltrados = array_filter($todosPlanes, fn($p) => $p['plan_empresa_id'] == $empresaActual);
                        if (empty($planesFiltrados)) {
                            echo '<option value="">Sin planes para esta empresa</option>';
                        } else {
                            foreach ($planesFiltrados as $p): 
                                $sel = ($planActual == $p['plan_id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $p['plan_id'] ?>" <?= $sel ?>>
                            📋 <?= htmlspecialchars(substr($p['plan_nombre'], 0, 25)) ?> (<?= $p['plan_estado'] ?>)
                        </option>
                        <?php endforeach; } ?>
                    </select>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php $notifs = $core->getUnreadNotifications(\Auth::userId() ?? 0, 5); ?>
                <div class="dropdown">
                    <button class="btn btn-icon" data-bs-toggle="dropdown" title="Notificaciones">
                        <i class="fas fa-bell"></i>
                        <?php if (count($notifs) > 0): ?><span class="badge-notif"><?= count($notifs) ?></span><?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 320px;">
                        <?php if (empty($notifs)): ?>
                            <div class="text-center text-muted py-3">Sin notificaciones</div>
                        <?php else: foreach ($notifs as $n): ?>
                            <div class="notif-item"><strong><?= htmlspecialchars($n['notif_titulo']) ?></strong><small class="d-block text-muted"><?= htmlspecialchars($n['notif_mensaje']) ?></small></div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <a href="/lang/es" class="btn btn-sm <?= ($_COOKIE['lang']??'es')==='es'?'btn-primary':'btn-outline-secondary' ?>" title="Español" style="font-size:0.7rem;padding:2px 6px">ES</a>
                    <a href="/lang/en" class="btn btn-sm <?= ($_COOKIE['lang']??'es')==='en'?'btn-primary':'btn-outline-secondary' ?>" title="English" style="font-size:0.7rem;padding:2px 6px">EN</a>
                    <button class="btn btn-sm btn-outline-secondary" title="Modo oscuro/claro" style="font-size:0.7rem;padding:2px 6px" onclick="toggleDarkMode()"><i class="fas fa-moon"></i></button>
                </div>
            </div>
        </header>

        <div class="page-content">
            <?php
            $okMsg = $_GET['ok'] ?? null;
            $errMsg = $_GET['err'] ?? $_GET['error'] ?? null;
            if ($okMsg):
            ?>
            <div class="toast-msg toast-ok" id="toastOk"><i class="fas fa-check-circle me-2"></i><?= $okMsg === '1' ? 'Operación completada exitosamente.' : htmlspecialchars($okMsg) ?></div>
            <?php elseif ($errMsg): ?>
            <div class="toast-msg toast-err" id="toastErr"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($errMsg) ?></div>
            <?php endif; ?>
            <?= $content ?? '' ?>
        </div>
    </main>
</div>

    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script>
    window.CSRF_TOKEN = <?= json_encode($csrfToken ?? '') ?>;

    // Hash-based navigation: DESACTIVADO POR AHORA (navegación directa)
    /* HASH ROUTER DESACTIVADO
    (function(){
        var mainTarget = document.querySelector('.main-content > div:last-of-type') || document.querySelector('.main-content > :nth-child(3)') || document.querySelector('.main-content');
        var currentPath = '';
        function loadContent(path) { ... }
        function updateActiveLink(path) { ... }
        document.addEventListener('click', function(e) { ... });
        window.addEventListener('popstate', function() { ... });
    })();
    */
    var _origFetch = window.fetch;
    window.fetch = function(url, opts) {
        opts = opts || {};
        if ((opts.method || 'GET').toUpperCase() === 'POST') {
            opts.headers = opts.headers || {};
            if (opts.headers['Content-Type'] === 'application/x-www-form-urlencoded' || !opts.headers['Content-Type']) {
                var body = opts.body || '';
                if (typeof body === 'string' && body.indexOf('csrf_token=') === -1) {
                    opts.body = body + (body ? '&' : '') + 'csrf_token=' + CSRF_TOKEN;
                }
            }
        }
        return _origFetch(url, opts);
    };
    </script>
<script>
document.getElementById('selEmpresa').addEventListener('change', function() {
    document.cookie = 'empresa_activa=' + this.value + ';path=/';
    document.cookie = 'plan_activo=;path=/;expires=Thu, 01 Jan 1970 00:00:00 GMT';
    location.reload();
});
document.getElementById('selPlan').addEventListener('change', function() {
    if (this.value) { document.cookie = 'plan_activo=' + this.value + ';path=/'; location.reload(); }
});

// Menú colapsable con persistencia
function toggleGroup(id) {
    var el = document.getElementById(id);
    var arrow = document.getElementById('arrow_' + id);
    if (!el) return;
    if (el.style.display === 'none') {
        el.style.display = 'block';
        if (arrow) arrow.style.transform = 'rotate(0deg)';
        updateMenuState(id, false);
    } else {
        el.style.display = 'none';
        if (arrow) arrow.style.transform = 'rotate(-90deg)';
        updateMenuState(id, true);
    }
}
function updateMenuState(id, collapsed) {
    var state = JSON.parse(localStorage.getItem('menuState') || '{}');
    state[id] = collapsed;
    localStorage.setItem('menuState', JSON.stringify(state));
}
// Restaurar estado al cargar
(function() {
    var state = JSON.parse(localStorage.getItem('menuState') || '{}');
    document.querySelectorAll('.sidebar-group-items').forEach(function(el) {
        if (state[el.id]) { 
            el.style.display = 'none'; 
            var a = document.getElementById('arrow_'+el.id); 
            if(a) a.style.transform = 'rotate(-90deg)'; 
        }
    });
    if('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');
})();

// Modo oscuro - respeta configuración de empresa
(function(){
    var empresaDefault = <?= $empresaModoOscuro ?>;
    var stored = localStorage.getItem('theme');
    if (stored === 'dark' || (!stored && empresaDefault === 1)) {
        document.documentElement.setAttribute('data-bs-theme','dark');
        var icon = document.querySelector('.fa-moon');
        if (icon) { icon.classList.replace('fa-moon','fa-sun'); }
    }
})();
function toggleDarkMode(){
    var isDark = document.documentElement.getAttribute('data-bs-theme')==='dark';
    if(isDark){document.documentElement.removeAttribute('data-bs-theme');localStorage.setItem('theme','light');document.querySelector('.fa-sun').classList.replace('fa-sun','fa-moon');}
    else{document.documentElement.setAttribute('data-bs-theme','dark');localStorage.setItem('theme','dark');document.querySelector('.fa-moon').classList.replace('fa-moon','fa-sun');}
}

// Atajos de teclado
document.addEventListener('keydown',function(e){
    if(e.key==='Escape'){var modals=document.querySelectorAll('.modal.show');if(modals.length){bootstrap.Modal.getInstance(modals[modals.length-1]).hide();e.preventDefault();}}
    if(e.ctrlKey&&e.key==='s'){var form=document.querySelector('.modal.show form');if(form){form.querySelector('button[type=submit]').click();e.preventDefault();}}
    if(e.ctrlKey&&e.key==='n'){var btns=document.querySelectorAll('[data-bs-toggle=modal]');if(btns.length){btns[btns.length-1].click();e.preventDefault();}}
});
</script>
<script>
window.addEventListener('error', function(e) {
    var data = {
        message: e.message,
        source: e.filename,
        lineno: e.lineno,
        colno: e.colno,
        url: window.location.href,
        userAgent: navigator.userAgent
    };
    fetch('/api/error/report', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).catch(function() {});
});
window.addEventListener('unhandledrejection', function(e) {
    fetch('/api/error/report', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            message: 'Unhandled Promise: ' + (e.reason && e.reason.message ? e.reason.message : e.reason),
            url: window.location.href,
            userAgent: navigator.userAgent
        })
    }).catch(function() {});
});
</script>
</body>
</html>
