<?php $evaluado = $_GET['evaluado'] ?? null; ?>
<?php if ($evaluado): ?><div class="alert alert-success">Estándar evaluado correctamente</div><?php endif; ?>

<nav class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="/calidad">Calidad</a></li><li class="breadcrumb-item active">Acreditación</li></ol></nav>

<div class="d-flex justify-content-between mb-3">
    <div>
        <h5><i class="fas fa-certificate me-2" style="color:#ffc107"></i>Acreditación en Salud · <?= htmlspecialchars($empresa['empresa_nombre']) ?></h5>
        <small class="text-muted">Ministerio de Salud y Protección Social · Resolución 5095/2018</small>
    </div>
    <div>
        <a href="/calidad/autoevaluacion?empresa_id=<?= $empresaId ?>" class="btn btn-outline-success btn-sm me-1"><i class="fas fa-clipboard-check me-1"></i>Autoevaluación</a>
        <a href="/acreditacion/reporte?empresa_id=<?= $empresaId ?>" class="btn btn-outline-primary btn-sm" target="_blank"><i class="fas fa-file-pdf me-1"></i>Informe</a>
    </div>
</div>

<!-- KPIs General -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card-box text-center"><div class="card-box-body">
            <h3 class="text-primary mb-0"><?= $total ?></h3><small class="text-muted">Estándares Totales</small>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card-box text-center"><div class="card-box-body">
            <h3 class="text-<?= $pctCumplimiento>=90?'success':($pctCumplimiento>=60?'warning':'danger') ?> mb-0"><?= $pctCumplimiento ?>%</h3><small class="text-muted">Cumplimiento</small>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card-box text-center"><div class="card-box-body">
            <h3 class="text-success mb-0"><?= $cumplen ?></h3><small class="text-muted">Cumplen</small>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card-box text-center"><div class="card-box-body">
            <h3 class="text-warning mb-0"><?= $parcial ?></h3><small class="text-muted">Parcial</small>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card-box text-center"><div class="card-box-body">
            <h3 class="text-danger mb-0"><?= $noCumplen ?></h3><small class="text-muted">No Cumplen</small>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card-box text-center"><div class="card-box-body">
            <h3 class="mb-0"><?= $total - $cumplen - $parcial - $noCumplen ?></h3><small class="text-muted">Sin Evaluar</small>
        </div></div>
    </div>
</div>

<!-- Avance por Tipo de Estándar -->
<h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Avance por Estándar de Acreditación</h5>
<div class="row g-3 mb-4">
<?php foreach ($porTipo as $tipo => $datos):
    $pctTipo = $datos['total'] > 0 ? round(($datos['cumple'] / $datos['total']) * 100, 1) : 0;
    $colorBar = $pctTipo >= 90 ? 'success' : ($pctTipo >= 60 ? 'primary' : 'warning');
    $labels = ['SUA'=>'Sist. Único Acreditación','ISO7101'=>'ISO 7101:2023','Habilitacion'=>'Habilitación Res.3100'];
?>
<div class="col-md-4">
<div class="card-box h-100">
    <div class="card-box-header d-flex justify-content-between">
        <strong><?= $labels[$tipo] ?? $tipo ?></strong>
        <span class="badge bg-<?= $colorBar ?>"><?= $pctTipo ?>%</span>
    </div>
    <div class="card-box-body">
        <div class="progress mb-2" style="height:12px"><div class="progress-bar bg-<?= $colorBar ?>" style="width:<?= $pctTipo ?>%"><?= $pctTipo ?>%</div></div>
        <div class="d-flex justify-content-between small">
            <span class="text-success"><?= $datos['cumple'] ?> Cumplen</span>
            <span class="text-warning"><?= $datos['parcial'] ?> Parcial</span>
            <span class="text-danger"><?= $datos['no_cumple'] ?> No Cumplen</span>
            <span class="text-muted">/ <?= $datos['total'] ?></span>
        </div>
    </div>
</div>
</div>
<?php endforeach; ?>
</div>

<!-- Cumplimiento por Grupo -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <h6><i class="fas fa-table me-2"></i>Estándares por Grupo</h6>
        <div class="card-box"><div class="card-box-body p-0" style="max-height:400px;overflow-y:auto">
        <table class="table-box small">
            <thead><tr><th>Grupo</th><th>Estándar</th><th>Nivel</th><th>Puntaje</th><th>Cumplimiento</th><th>Evidencia</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($estandares as $est): $c = $est['ultimo_cumplimiento'] ?? 'no_evaluado'; ?>
            <tr>
                <td><small><?= htmlspecialchars($est['estandar_grupo']) ?></small></td>
                <td><strong><?= htmlspecialchars($est['estandar_codigo']) ?></strong><br><small><?= htmlspecialchars(substr($est['estandar_nombre'], 0, 50)) ?></small></td>
                <td><?= $est['estandar_nivel'] ?></td>
                <td><span class="badge bg-<?= ($est['ultimo_puntaje']??0)>=90?'success':(($est['ultimo_puntaje']??0)>=70?'warning':'danger') ?>"><?= $est['ultimo_puntaje'] ?? 0 ?>%</span></td>
                <td><span class="badge bg-<?= $c==='cumple'?'success':($c==='cumple_parcial'?'warning':($c==='no_cumple'?'danger':'secondary')) ?>"><?= str_replace('_',' ',$c) ?></span></td>
                <td><small><?= htmlspecialchars(substr($est['evidencia_descripcion']??'', 0, 40)) ?></small></td>
                <td><button class="btn btn-sm btn-outline-primary" onclick="abrirEvaluacion(<?= $est['estandar_id'] ?>,'<?= addslashes($est['estandar_codigo']) ?> - <?= addslashes($est['estandar_nombre']) ?>')"><i class="fas fa-edit"></i></button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div></div>
    </div>
    <div class="col-md-4">
        <h6><i class="fas fa-tasks me-2"></i>Actividades de Acreditación</h6>
        <?php if (empty($actividades)): ?>
        <div class="card-box"><div class="card-box-body text-center py-3 text-muted small">Sin actividades programadas</div></div>
        <?php else: ?>
        <div class="card-box"><div class="card-box-body p-0" style="max-height:400px;overflow-y:auto">
        <?php foreach ($actividades as $act): ?>
        <div class="p-2 border-bottom small">
            <div class="d-flex justify-content-between">
                <strong><?= htmlspecialchars($act['act_descripcion']) ?></strong>
                <span class="badge bg-<?= $act['act_estado']==='completada'?'success':($act['act_estado']==='en_proceso'?'primary':'warning') ?>"><?= $act['act_estado'] ?></span>
            </div>
            <div class="text-muted"><?= $act['act_estandar_tipo'] ?> · Fin: <?= $act['act_fecha_fin'] ?> · <?= htmlspecialchars($act['responsable_nombre']??'-') ?></div>
            <div class="progress mt-1" style="height:5px"><div class="progress-bar bg-<?= $act['act_avance']>=80?'success':'primary' ?>" style="width:<?= $act['act_avance'] ?? 0 ?>%"></div></div>
        </div>
        <?php endforeach; ?>
        </div></div>
        <?php endif; ?>
    </div>
</div>

<!-- Ciclos de Acreditación -->
<?php if (!empty($ciclos)): ?>
<h6 class="mb-3"><i class="fas fa-sync-alt me-2"></i>Ciclos de Acreditación</h6>
<div class="row g-3 mb-4">
<?php foreach ($ciclos as $ciclo): $pct = $ciclo['nivel_puntaje_actual']; ?>
<div class="col-md-4">
<div class="card p-3 h-100" style="border-left:4px solid <?= $ciclo['nivel_estandar_tipo']==='SUA'?'#28a745':($ciclo['nivel_estandar_tipo']==='ISO7101'?'#007bff':'#ffc107') ?>">
    <div class="d-flex justify-content-between mb-2">
        <strong><?= $ciclo['nivel_estandar_tipo'] ?></strong>
        <span class="badge bg-<?= $pct>=90?'success':($pct>=60?'warning':'danger') ?>"><?= $pct ?>%</span>
    </div>
    <div class="progress mb-2" style="height:10px"><div class="progress-bar bg-<?= $pct>=90?'success':($pct>=60?'primary':'warning') ?>" style="width:<?= $pct ?>%"></div></div>
    <div class="d-flex justify-content-between small text-muted">
        <span>Meta: <?= $ciclo['nivel_puntaje_objetivo'] ?>%</span>
        <span>Fase: <?= $ciclo['nivel_fase'] ?></span>
    </div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- MODAL Evaluar Estándar -->
<div class="modal fade" id="modalEvaluar"><div class="modal-dialog"><form method="POST" action="/acreditacion/evaluar" class="modal-content" enctype="multipart/form-data">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <input type="hidden" name="estandar_id" id="evalEstandarId">
    <div class="modal-header"><h5>Evaluar Estándar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div id="evalTitulo" class="fw-bold mb-3"></div>
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small">Cumplimiento</label><select name="cumplimiento" class="form-select form-select-sm"><option value="no_cumple">No Cumple</option><option value="cumple_parcial">Cumple Parcial</option><option value="cumple">Cumple</option></select></div><div class="col-6"><label class="form-label small">Puntaje (0-100)</label><input type="number" name="puntaje" class="form-control form-control-sm" min="0" max="100" value="50"></div></div>
        <div class="mb-2"><label class="form-label small">Evidencia</label><textarea name="evidencia" class="form-control form-control-sm" rows="2" placeholder="Describa la evidencia encontrada"></textarea></div>
        <div class="mb-2"><label class="form-label small">Plan de Mejora</label><textarea name="plan_mejora" class="form-control form-control-sm" rows="2" placeholder="Acciones correctivas si aplica"></textarea></div>
        <div class="mb-2"><label class="form-label small">Archivo de Evidencia</label><input type="file" name="evidencia_archivo" class="form-control form-control-sm" accept="image/*,.pdf"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Evaluar</button></div>
</form></div></div>

<script>
function abrirEvaluacion(id, titulo) {
    document.getElementById('evalEstandarId').value = id;
    document.getElementById('evalTitulo').textContent = titulo;
    new bootstrap.Modal(document.getElementById('modalEvaluar')).show();
}
</script>
<?php $moduloContexto = 'acreditación en salud'; require BASE_PATH . '/templates/hse/ia_panel.php'; ?>
