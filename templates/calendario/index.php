<?php
$mesesES = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$diasSemana = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
$vista = $_GET['vista'] ?? 'mes';
$hoy = date('Y-m-d');

// Calcular según vista
if ($vista === 'dia') {
    $fechaVista = $_GET['fecha'] ?? $hoy;
    $diaSem = date('N', strtotime($fechaVista));
    $inicioSemana = date('Y-m-d', strtotime($fechaVista . ' -' . ($diaSem-1) . ' days'));
    $tareas = array_filter($tareas, fn($t) => ($t['tarea_fecha_inicio']??'') <= $fechaVista && ($t['tarea_fecha_fin']??'') >= $fechaVista);
} elseif ($vista === 'semana') {
    $semanaInicio = $_GET['semana'] ?? date('Y-m-d', strtotime('monday this week'));
    $fechaVista = $semanaInicio;
    $tareasSemana = [];
    for ($d=0; $d<7; $d++) {
        $fd = date('Y-m-d', strtotime($semanaInicio . " +$d days"));
        foreach ($tareas as $t) {
            if (($t['tarea_fecha_inicio']??'') <= $fd && ($t['tarea_fecha_fin']??'') >= $fd) {
                $tareasSemana[$fd][] = $t;
            }
        }
    }
}

// Tareas vencidas (alerta)
$vencidas = array_filter($tareas, fn($t) => ($t['tarea_fecha_fin']??'') < $hoy && !in_array($t['tarea_estado'], ['completada','cancelada']));

// Cumplimiento por categoría
$cats = ['acreditacion','pamec','nc','reportes','riesgos','general'];
$cumplimiento = [];
foreach ($cats as $cat) {
    $totalCat = count(array_filter($tareas, fn($t)=>$t['tarea_modulo']===$cat));
    $compCat = count(array_filter($tareas, fn($t)=>$t['tarea_modulo']===$cat && $t['tarea_estado']==='completada'));
    $cumplimiento[$cat] = $totalCat > 0 ? round(($compCat/$totalCat)*100) : 0;
}
?>

<div class="d-flex justify-content-between mb-3">
    <div>
        <h5><i class="fas fa-calendar-alt me-2" style="color:#1a73e8"></i>Programador</h5>
        <small class="text-muted"><?= htmlspecialchars($empresa['empresa_nombre']) ?></small>
    </div>
    <div class="d-flex gap-2">
        <div class="btn-group btn-group-sm">
            <a href="?vista=anio&anio=<?= $anio ?>" class="btn btn-sm btn-<?= $vista==='anio'?'primary':'outline-secondary' ?>" style="font-size:0.75rem;padding:3px 10px">Año</a>
            <a href="?vista=mes&mes=<?= $mes ?>&anio=<?= $anio ?>" class="btn btn-sm btn-<?= $vista==='mes'?'primary':'outline-secondary' ?>" style="font-size:0.75rem;padding:3px 10px">Mes</a>
            <a href="?vista=semana&semana=<?= date('Y-m-d', strtotime('monday this week')) ?>" class="btn btn-sm btn-<?= $vista==='semana'?'primary':'outline-secondary' ?>" style="font-size:0.75rem;padding:3px 10px">Sem</a>
            <a href="?vista=dia&fecha=<?= $hoy ?>" class="btn btn-sm btn-<?= $vista==='dia'?'primary':'outline-secondary' ?>" style="font-size:0.75rem;padding:3px 10px">Día</a>
        </div>
    </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-9">
        <!-- Alertas vencidas -->
        <?php if (!empty($vencidas)): ?>
        <div class="alert alert-danger py-2 mb-3">
            <i class="fas fa-exclamation-triangle me-2"></i><strong><?= count($vencidas) ?> tareas vencidas</strong>
            <?php foreach (array_slice($vencidas,0,3) as $v): ?>
            <span class="badge bg-danger ms-2" style="cursor:pointer" onclick="verTarea(<?= $v['tarea_id'] ?>)"><?= htmlspecialchars(substr($v['tarea_titulo'],0,30)) ?>...</span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Navegación -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <?php if ($vista === 'mes'): ?>
            <a href="?vista=mes&mes=<?= date('m', $mesAnterior) ?>&anio=<?= date('Y', $mesAnterior) ?>" class="btn btn-sm btn-light"><i class="fas fa-chevron-left"></i></a>
            <h4 class="mb-0"><?= $mesesES[$mes] ?> <?= $anio ?></h4>
            <a href="?vista=mes&mes=<?= date('m', $mesSiguiente) ?>&anio=<?= date('Y', $mesSiguiente) ?>" class="btn btn-sm btn-light"><i class="fas fa-chevron-right"></i></a>
            <?php elseif ($vista === 'semana'): ?>
            <a href="?vista=semana&semana=<?= date('Y-m-d', strtotime($semanaInicio.' -7 days')) ?>" class="btn btn-sm btn-light"><i class="fas fa-chevron-left"></i></a>
            <h4 class="mb-0">Semana del <?= date('d/m', strtotime($semanaInicio)) ?></h4>
            <a href="?vista=semana&semana=<?= date('Y-m-d', strtotime($semanaInicio.' +7 days')) ?>" class="btn btn-sm btn-light"><i class="fas fa-chevron-right"></i></a>
            <?php elseif ($vista === 'dia'): ?>
            <a href="?vista=dia&fecha=<?= date('Y-m-d', strtotime($fechaVista.' -1 day')) ?>" class="btn btn-sm btn-light"><i class="fas fa-chevron-left"></i></a>
            <h4 class="mb-0"><?= date('d/m/Y', strtotime($fechaVista)) ?></h4>
            <a href="?vista=dia&fecha=<?= date('Y-m-d', strtotime($fechaVista.' +1 day')) ?>" class="btn btn-sm btn-light"><i class="fas fa-chevron-right"></i></a>
             <?php endif; ?>
        </div>

        <!-- VISTA AÑO -->
        <?php if ($vista === 'anio'): ?>
        <h4 class="mb-3"><?= $anio ?></h4>
        <div class="row g-2">
            <?php for ($m=1; $m<=12; $m++): $s = $statsAnual[$m]; ?>
            <div class="col-md-4 col-lg-3">
                <a href="?vista=mes&mes=<?= $m ?>&anio=<?= $anio ?>" class="card p-2 text-decoration-none h-100" style="border-left:3px solid <?= $s['total']>0?'#1a73e8':'#e0e0e0' ?>">
                    <div class="d-flex justify-content-between">
                        <strong style="color:#333"><?= $mesesES[$m] ?></strong>
                        <span class="badge bg-light text-dark"><?= $s['total'] ?></span>
                    </div>
                    <div class="small text-muted mt-1">
                        ✅ <?= $s['completadas'] ?> 
                        <?php if ($s['vencidas']>0): ?><span class="text-danger">⚠️ <?= $s['vencidas'] ?> vencidas</span><?php endif; ?>
                    </div>
                    <?php if ($s['total']>0): ?>
                    <div class="progress mt-1" style="height:4px"><div class="progress-bar bg-success" style="width:<?= round(($s['completadas']/$s['total'])*100) ?>%"></div><?php if ($s['vencidas']>0): ?><div class="progress-bar bg-danger" style="width:<?= round(($s['vencidas']/$s['total'])*100) ?>%"></div><?php endif; ?></div>
                    <?php endif; ?>
                </a>
            </div>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- VISTA MES -->
        <?php if ($vista === 'mes'): ?>
        <div class="card-box"><div class="card-box-body p-2"><table class="table table-bordered text-center" style="table-layout:fixed">
            <thead><tr><?php foreach ($diasSemana as $d): ?><th class="small bg-light"><?= $d ?></th><?php endforeach; ?></tr></thead>
            <tbody><tr>
            <?php for ($i=1; $i<$diaSemanaInicio; $i++) echo '<td class="text-muted"></td>';
            for ($dia=1; $dia<=$diasEnMes; $dia++):
                $fechaStr = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
                $tareasDia = $porFecha[$fechaStr] ?? [];
                $esHoy = ($fechaStr === $hoy);
                if (($dia+$diaSemanaInicio-2) % 7 === 0 && $dia > 1) echo '</tr><tr>';
            ?>
            <td style="height:70px;vertical-align:top;padding:3px;<?= $esHoy?'background:#e8f0fe':'' ?>">
                <a href="?vista=dia&fecha=<?= $fechaStr ?>" class="small <?= $esHoy?'fw-bold text-primary':'text-dark' ?> text-decoration-none"><?= $dia ?></a>
                <?php foreach (array_slice($tareasDia,0,2) as $td): ?>
                <div class="small mb-1 p-1 rounded" style="background:<?= ($moduloColors[$td['tarea_modulo']]??'#888').'20' ?>;border-left:3px solid <?= $moduloColors[$td['tarea_modulo']]??'#888' ?>;font-size:0.55rem;cursor:pointer" onclick="verTarea(<?= $td['tarea_id'] ?>)">
                    <?= htmlspecialchars(substr($td['tarea_titulo'],0,22)) ?>
                </div>
                <?php endforeach; ?>
            </td>
            <?php endfor; ?>
            </tr></tbody>
        </table></div></div>
        <?php endif; ?>

        <!-- VISTA SEMANA -->
        <?php if ($vista === 'semana'): ?>
        <div class="card-box"><div class="card-box-body p-2">
            <?php for ($d=0; $d<7; $d++): $fd = date('Y-m-d', strtotime($semanaInicio . " +$d days")); $tds = $tareasSemana[$fd] ?? []; ?>
            <div class="mb-2 p-2 border rounded <?= $fd===$hoy?'bg-primary bg-opacity-10':'' ?>">
                <strong><?= $diasSemana[$d] ?> <?= date('d/m', strtotime($fd)) ?></strong>
                <?php foreach ($tds as $td): ?>
                <div class="small p-1 rounded mt-1" style="background:<?= ($moduloColors[$td['tarea_modulo']]??'#888').'20' ?>;border-left:3px solid <?= $moduloColors[$td['tarea_modulo']]??'#888' ?>;cursor:pointer" onclick="verTarea(<?= $td['tarea_id'] ?>)">
                    <span class="badge" style="background:<?= $moduloColors[$td['tarea_modulo']]??'#888' ?>;font-size:0.5rem"><?= $td['tarea_modulo'] ?></span>
                    <?= htmlspecialchars($td['tarea_titulo']) ?>
                    <small class="text-muted"><?= $td['tarea_fecha_inicio'] ? date('d/m',strtotime($td['tarea_fecha_inicio'])):'' ?>→<?= $td['tarea_fecha_fin'] ? date('d/m',strtotime($td['tarea_fecha_fin'])):'' ?></small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
        </div></div>
        <?php endif; ?>

        <!-- VISTA DÍA -->
        <?php if ($vista === 'dia'): ?>
        <div class="card-box"><div class="card-box-body">
            <h6><?= date('l d/m/Y', strtotime($fechaVista)) ?></h6>
            <?php if (empty($tareas)): ?><p class="text-muted">Sin tareas para este día.</p>
            <?php else: foreach ($tareas as $td): ?>
            <div class="p-3 border rounded mb-2" style="border-left:4px solid <?= $moduloColors[$td['tarea_modulo']]??'#888' ?>;cursor:pointer" onclick="verTarea(<?= $td['tarea_id'] ?>)">
                <div class="d-flex justify-content-between">
                    <strong><?= htmlspecialchars($td['tarea_titulo']) ?></strong>
                    <span class="badge bg-<?= $estadoColors[$td['tarea_estado']] ?>"><?= $td['tarea_estado'] ?></span>
                </div>
                <div class="small text-muted"><?= $td['tarea_modulo'] ?> · Nivel <?= $td['tarea_nivel'] ?> · <?= $td['tarea_fecha_inicio'] ?> → <?= $td['tarea_fecha_fin'] ?></div>
                <?php if ($td['responsable_nombre']): ?><small><?= htmlspecialchars($td['responsable_nombre']) ?></small><?php endif; ?>
                <?php if ($td['tarea_avance']>0): ?>
                <div class="progress mt-1" style="height:4px;width:100px"><div class="progress-bar bg-<?= $td['tarea_avance']>=80?'success':'primary' ?>" style="width:<?= $td['tarea_avance'] ?>%"></div></div>
                <?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
        </div></div>
        <?php endif; ?>
    </div>

    <div class="col-md-3">
        <!-- Cumplimiento por categoría -->
        <div class="card-box mb-3"><div class="card-box-header">Cumplimiento</div>
        <div class="card-box-body p-2">
            <?php foreach ($cumplimiento as $cat => $pct): if ($pct==0 && empty(array_filter($tareas, fn($t)=>$t['tarea_modulo']===$cat))) continue; ?>
            <div class="mb-2"><small><?= ucfirst($cat) ?></small>
                <div class="progress" style="height:6px"><div class="progress-bar bg-<?= $pct>=80?'success':($pct>=50?'warning':'danger') ?>" style="width:<?= $pct ?>%"></div></div>
                <small class="text-muted"><?= $pct ?>%</small>
            </div>
            <?php endforeach; ?>
        </div></div>

        <!-- Filtro -->
        <div class="card-box"><div class="card-box-header">Filtrar</div>
        <div class="card-box-body p-2">
            <?php foreach (['acreditacion'=>'Acreditación','pamec'=>'Auditorías','nc'=>'No Conformidades','reportes'=>'Reportes','riesgos'=>'Riesgos'] as $mk=>$ml): ?>
            <a href="?modulo=<?= $mk ?>&vista=<?= $vista ?>" class="d-block p-1 rounded-2 text-decoration-none small mb-1 <?= $modulo===$mk?'bg-primary bg-opacity-10':'' ?>" style="color:#333">
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $moduloColors[$mk] ?>;margin-right:6px"></span>
                <?= $ml ?>
            </a>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>

<!-- MODAL Tarea -->
<div class="modal fade" id="modalTarea"><div class="modal-dialog"><div class="modal-content" id="modalTareaContent"></div></div></div>

<script>
<?php if (!empty($vencidas)): ?>
// Auto-mostrar alerta si hay vencidas
setTimeout(function() { alert('⚠️ Hay <?= count($vencidas) ?> tareas vencidas. Revisa el panel de alertas en el calendario.'); }, 500);
<?php endif; ?>

async function verTarea(id) {
    var modal = new bootstrap.Modal(document.getElementById('modalTarea'));
    document.getElementById('modalTareaContent').innerHTML = '<div class="modal-body text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
    modal.show();
    // Hacer fetch de los detalles o construir desde datos locales
    var tareasData = <?= json_encode(array_map(function($t){return ['id'=>$t['tarea_id'],'titulo'=>$t['tarea_titulo'],'modulo'=>$t['tarea_modulo'],'tema'=>$t['tarea_tema'],'estado'=>$t['tarea_estado'],'avance'=>$t['tarea_avance'],'inicio'=>$t['tarea_fecha_inicio'],'fin'=>$t['tarea_fecha_fin'],'responsable'=>$t['responsable_nombre'],'nivel'=>$t['tarea_nivel']];}, array_values($tareas))) ?>;
    var t = tareasData.find(function(x){return x.id==id;});
    if (t) {
        var urlModulo = {acreditacion:'/calidad',pamec:'/calidad/pamec',nc:'/nc',reportes:'/calidad',riesgos:'/calidad/riesgos',general:'/calidad'};
        document.getElementById('modalTareaContent').innerHTML =
            '<div class="modal-header"><h5>'+t.titulo+'</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>'+
            '<div class="modal-body">'+
            '<p><strong>Módulo:</strong> '+t.modulo+' | <strong>Tema:</strong> '+(t.tema||'-')+' | <strong>Nivel:</strong> '+(t.nivel||1)+'</p>'+
            '<p><strong>Estado:</strong> '+t.estado+' | <strong>Avance:</strong> '+t.avance+'%</p>'+
            '<p><strong>Período:</strong> '+(t.inicio||'?')+' → '+(t.fin||'?')+'</p>'+
            '<p><strong>Responsable:</strong> '+(t.responsable||'No asignado')+'</p>'+
            '<hr><a href="'+(urlModulo[t.modulo]||'/calidad')+'" class="btn btn-primary btn-sm">Ir al módulo</a>'+
            '</div>';
    }
}
</script>
