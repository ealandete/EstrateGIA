<?php
$modColors = ['calidad'=>'#28a745','sst'=>'#ffc107','ambiental'=>'#007bff','estrategico'=>'#6f42c1'];
$modIcons = ['calidad'=>'certificate','sst'=>'hard-hat','ambiental'=>'leaf','estrategico'=>'bullseye'];
$estadoColors = ['abierta'=>'danger','analisis'=>'warning','plan_accion'=>'info','implementacion'=>'primary','verificacion'=>'secondary','cerrada'=>'success'];
?>

<div class="d-flex justify-content-between mb-3">
    <div>
        <h5><i class="fas fa-cubes me-2" style="color:#6f42c1"></i>Sistema Integrado de Gestión · <?= htmlspecialchars($empresa['empresa_nombre']) ?></h5>
        <small class="text-muted">ISO 9001 + ISO 14001 + ISO 45001 + ISO 7101 · Ecosistema interconectado</small>
    </div>
</div>

<!-- Panel central: Interconexión visual -->
<div class="card-box mb-4" style="background:linear-gradient(135deg,#0d1b2a,#1b2a3a);color:#fff">
    <div class="card-box-body">
        <h6 class="mb-3"><i class="fas fa-project-diagram me-2"></i>Ecosistema de Gestión Integrada</h6>
        <div class="row g-3 text-center">
            <?php 
            $mods = [
                'estrategico' => ['Planeación Estratégica', '/planeacion', 'Define objetivos, estrategias y metas'],
                'calidad' => ['Gestión de Calidad', '/calidad', 'Acreditación, PAMEC, NC, Riesgos'],
                'sst' => ['Seguridad y Salud', '/sst', 'ISO 45001, Peligros, Incidentes'],
                'ambiental' => ['Gestión Ambiental', '/ambiental', 'ISO 14001, Aspectos, Impactos'],
            ];
            foreach ($mods as $mk => $mm):
            ?>
            <div class="col-md-3">
                <a href="<?= $mm[1] ?>" class="text-decoration-none">
                    <div class="p-3 rounded-3" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12)">
                        <i class="fas fa-<?= $modIcons[$mk] ?> fs-3 mb-2 d-block" style="color:<?= $modColors[$mk] ?>"></i>
                        <strong class="d-block" style="color:#fff"><?= $mm[0] ?></strong>
                        <small class="d-block mt-1" style="color:#889"><?= $mm[2] ?></small>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Líneas de interconexión -->
        <div class="d-flex justify-content-center gap-2 mt-3 small">
            <span class="badge" style="background:rgba(255,255,255,0.1)"><i class="fas fa-link me-1"></i>NC → SST (incidentes)</span>
            <span class="badge" style="background:rgba(255,255,255,0.1)"><i class="fas fa-link me-1"></i>PAMEC → Todos</span>
            <span class="badge" style="background:rgba(255,255,255,0.1)"><i class="fas fa-link me-1"></i>Riesgos → Calidad+SST+Amb</span>
            <span class="badge" style="background:rgba(255,255,255,0.1)"><i class="fas fa-link me-1"></i>Calendario → Unificado</span>
        </div>
    </div>
</div>

<!-- KPIs unificados -->
<div class="row g-3 mb-4">
    <?php 
    $drillLinks = [
        'calidad' => ['nc_abiertas'=>'/nc','pamec_pendientes'=>'/calidad/pamec','acreditacion_pct'=>'/calidad'],
        'sst' => ['peligros_inaceptables'=>'/sst?seccion=peligros','accidentalidad'=>'/sst?seccion=incidentes'],
        'ambiental' => ['aspectos_altos'=>'/ambiental?seccion=aspectos','residuos_pct'=>'/ambiental?seccion=registros'],
        'estrategico' => ['plan_avance'=>'/planeacion','procesos_documentados'=>'/procesos'],
    ];
    foreach ($kpis as $area => $metrics): foreach ($metrics as $k => $v): 
        $label = str_replace('_',' ',$k);
        $color = $modColors[$area];
        $link = $drillLinks[$area][$k] ?? '#';
    ?>
    <div class="col-md-3">
        <a href="<?= $link ?>" class="text-decoration-none" style="cursor:pointer" title="Ver detalle de <?= ucfirst($label) ?>">
        <div class="stat-card" style="border-left:3px solid <?= $color ?>;transition:transform 0.15s">
            <div class="stat-label"><?= ucfirst($label) ?></div>
            <div class="stat-value"><?= is_numeric($v) ? number_format($v, str_contains((string)$v,'.')?1:0) : $v ?></div>
            <?php 
            $yoy = null;
            if (isset($kpisYoY[$k])) {
                $prev = $kpisYoY[$k]['anterior'] ?? 0;
                $curr = $kpisYoY[$k]['actual'] ?? 0;
                if ($prev > 0) { $yoy = round(($curr - $prev) / $prev * 100, 1); }
            }
            ?>
            <small class="<?= $yoy !== null ? ($yoy > 0 ? 'text-danger' : 'text-success') : 'text-muted' ?>">
                <?= ucfirst($area) ?>
                <?php if ($yoy !== null): ?>
                <?= $yoy > 0 ? '↑' : '↓' ?> <?= abs($yoy) ?>% vs <?= $anioAnterior ?>
                <?php endif; ?>
                <i class="fas fa-arrow-right ms-1" style="font-size:0.6rem"></i>
            </small>
        </div>
        </a>
    </div>
    <?php endforeach; endforeach; ?>
</div>

<?php if (!empty($alertas)): ?>
<div class="card-box mb-4" style="border-left:5px solid #dc3545">
<div class="card-box-header"><i class="fas fa-bell me-2" style="color:#dc3545"></i>Alertas de Vencimiento (próximos 30 días) <span class="badge bg-danger ms-2"><?= count($alertas) ?></span></div>
<div class="card-box-body p-0">
    <?php foreach ($alertas as $alerta): ?>
    <a href="<?= $alerta['link'] ?>" class="d-flex align-items-center p-2 px-3 border-bottom text-decoration-none hover-row" style="color:#333">
        <i class="fas fa-<?= $alerta['icon'] ?> me-3" style="color:<?= $alerta['color'] ?>;width:20px"></i>
        <div class="flex-grow-1"><strong><?= $alerta['texto'] ?></strong>: <?= $alerta['nombre'] ?></div>
        <span class="badge bg-<?= (strtotime($alerta['fecha'])-time())<86400*7?'danger':'warning' ?> ms-2"><?= date('d/m/Y',strtotime($alerta['fecha'])) ?></span>
    </a>
    <?php endforeach; ?>
</div></div>
<?php endif; ?>

<!-- Línea de tiempo unificada: NCs + Peligros + Aspectos -->
<div class="row g-4">
    <div class="col-md-4">
        <div class="card-box"><div class="card-box-header">
            <i class="fas fa-triangle-exclamation me-2" style="color:#dc3545"></i>No Conformidades Recientes
            <a href="/nc" class="btn btn-sm btn-outline-danger ms-auto">Ver todas</a>
        </div>
        <div class="card-box-body p-0">
            <?php foreach ($ncs as $nc): ?>
            <a href="/nc/ver/<?= $nc['nc_id'] ?>" class="d-block p-2 px-3 border-bottom text-decoration-none hover-row" style="color:#333">
                <div class="d-flex justify-content-between">
                    <strong><?= htmlspecialchars($nc['nc_codigo']) ?></strong>
                    <span class="badge bg-<?= $estadoColors[$nc['nc_estado']] ?>"><?= $nc['nc_estado'] ?></span>
                </div>
                <small class="text-muted"><?= htmlspecialchars(substr($nc['nc_descripcion'],0,60)) ?></small>
            </a>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card-box"><div class="card-box-header">
            <i class="fas fa-hard-hat me-2" style="color:#ffc107"></i>Peligros SST Identificados
            <a href="/sst" class="btn btn-sm btn-outline-warning ms-auto">Ver todos</a>
        </div>
        <div class="card-box-body p-0">
            <?php foreach ($peligros as $p): ?>
            <div class="p-2 px-3 border-bottom">
                <div class="d-flex justify-content-between">
                    <strong><?= htmlspecialchars($p['peligro_codigo']) ?></strong>
                    <span class="badge bg-<?= $p['peligro_nivel']==='inaceptable'?'danger':'warning' ?>"><?= $p['peligro_nivel'] ?></span>
                </div>
                <small class="text-muted"><?= htmlspecialchars(substr($p['peligro_descripcion'],0,60)) ?></small>
            </div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card-box"><div class="card-box-header">
            <i class="fas fa-leaf me-2" style="color:#28a745"></i>Aspectos Ambientales
            <a href="/ambiental" class="btn btn-sm btn-outline-success ms-auto">Ver todos</a>
        </div>
        <div class="card-box-body p-0">
            <?php foreach ($aspectos as $a): ?>
            <div class="p-2 px-3 border-bottom">
                <div class="d-flex justify-content-between">
                    <strong><?= htmlspecialchars($a['asp_codigo']) ?></strong>
                    <span class="badge bg-<?= $a['asp_significancia']==='alto'?'danger':'warning' ?>"><?= $a['asp_significancia'] ?></span>
                </div>
                <small class="text-muted"><?= htmlspecialchars(substr($a['asp_descripcion'],0,60)) ?></small>
            </div>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>

<!-- Tareas urgentes unificadas -->
<?php if (!empty($urgentes)): ?>
<div class="card-box mt-4">
    <div class="card-box-header"><i class="fas fa-clock me-2" style="color:#dc3545"></i>Tareas Urgentes (próximos 7 días) · Todos los módulos</div>
    <div class="card-box-body p-0"><table class="table-box small">
        <thead><tr><th>Tarea</th><th>Módulo</th><th>Estado</th><th>Vence</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($urgentes as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['tarea_titulo']) ?></td>
            <td><span class="badge" style="background:<?= $modColors[$u['tarea_modulo']]??'#888' ?>;color:#fff;font-size:0.65rem"><?= $u['tarea_modulo'] ?></span></td>
            <td><span class="badge bg-<?= $u['tarea_estado']==='vencida'?'danger':($u['tarea_estado']==='en_proceso'?'primary':'warning') ?>"><?= $u['tarea_estado'] ?></span></td>
            <td class="<?= strtotime($u['tarea_fecha_fin']??'')<time()?'text-danger fw-bold':'' ?>"><?= date('d/m', strtotime($u['tarea_fecha_fin']??'')) ?></td>
            <td><a href="/calendario?vista=dia&fecha=<?= $u['tarea_fecha_fin'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-calendar"></i></a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>
