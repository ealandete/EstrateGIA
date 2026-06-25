<?php $created = $_GET['created'] ?? null; ?>
<?php if ($created): ?><div class="alert alert-success">Registro guardado</div><?php endif; ?>

<div class="d-flex justify-content-between mb-3">
    <h5><i class="fas fa-graduation-cap me-2"></i>Gestión de Formación · <?= htmlspecialchars($empresa['empresa_nombre']) ?></h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalForm"><i class="fas fa-plus me-1"></i>Registrar Capacitación</button>
</div>

<?php
$core = EstrateGiaCore::getInstance();
$empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
// Agrupar por mes para cronograma
$porMes = [];
foreach ($formaciones as $f) {
    $mes = substr($f['form_fecha'], 0, 7);
    $porMes[$mes][] = $f;
}
krsort($porMes);

// Estadísticas
$totalHoras = array_sum(array_column($formaciones, 'form_horas'));
$totalPersonas = count(array_unique(array_column($formaciones, 'form_usuario_id')));
$promedioCalif = round(array_sum(array_filter(array_column($formaciones, 'form_calificacion'), fn($v)=>$v>0)) / max(count(array_filter(array_column($formaciones, 'form_calificacion'), fn($v)=>$v>0)), 1), 1);
?>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Total Capacitaciones</div><div class="stat-value"><?= count($formaciones) ?></div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Horas Formación</div><div class="stat-value"><?= $totalHoras ?>h</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Personas Capacitadas</div><div class="stat-value"><?= $totalPersonas ?></div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Calificación Promedio</div><div class="stat-value"><?= $promedioCalif ?>%</div></div></div>
</div>

<!-- CRONOGRAMA por mes -->
<?php foreach ($porMes as $mes => $forms): ?>
<div class="card-box mb-3">
    <div class="card-box-header"><?php $mesesES = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']; echo $mesesES[(int)date('n',strtotime($mes.'-01'))].' '.date('Y',strtotime($mes.'-01')); ?> (<?= count($forms) ?> capacitaciones)</div>
    <div class="card-box-body p-0"><table class="table-box">
        <thead><tr><th>Fecha</th><th>Tema</th><th>Colaborador</th><th>Tipo</th><th>Horas</th><th>Instructor</th><th>Calificación</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($forms as $f): ?>
        <tr>
            <td><?= date('d/m', strtotime($f['form_fecha'])) ?></td>
            <td><strong><?= htmlspecialchars($f['form_tema']) ?></strong></td>
            <td><?= htmlspecialchars($f['usuario_nombre']) ?></td>
            <td><span class="badge bg-light text-dark"><?= $f['form_tipo'] ?></span></td>
            <td><?= $f['form_horas'] ?>h</td>
            <td><?= htmlspecialchars($f['form_instructor'] ?? '-') ?></td>
            <td><?= $f['form_calificacion'] ? "<span class='badge bg-".($f['form_calificacion']>=80?'success':'warning')."'>".$f['form_calificacion']."%</span>" : '-' ?></td>
            <td><span class="badge bg-<?= $f['form_estado']==='evaluada'?'success':($f['form_estado']==='realizada'?'primary':'warning') ?>"><?= $f['form_estado'] ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endforeach; ?>

<!-- MODAL -->
<div class="modal fade" id="modalForm"><div class="modal-dialog"><form method="POST" action="/formacion/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5>Registrar Capacitación</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="text" name="tema" class="form-control form-control-sm mb-2" placeholder="Tema de la capacitación *" required>
        <div class="row g-2 mb-2">
            <div class="col-6"><select name="usuario_id" class="form-select form-select-sm" required><option value="">Colaborador *</option><?php foreach ($usuarios as $u): ?><option value="<?= $u['usuario_id'] ?>"><?= htmlspecialchars($u['usuario_nombre'].' '.$u['usuario_apellido']) ?></option><?php endforeach; ?></select></div>
            <div class="col-6"><select name="tipo" class="form-select form-select-sm"><option value="tecnica">Técnica</option><option value="gestion">Gestión</option><option value="seguridad">Seguridad</option><option value="calidad">Calidad</option><option value="induccion">Inducción</option></select></div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-4"><input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-4"><input type="number" name="horas" class="form-control form-control-sm" placeholder="Horas" value="8"></div>
            <div class="col-4"><input type="number" name="calificacion" class="form-control form-control-sm" placeholder="Nota %" min="0" max="100" step="0.1"></div>
        </div>
        <input type="text" name="instructor" class="form-control form-control-sm mb-2" placeholder="Instructor">
        <select name="estado" class="form-select form-select-sm"><option value="programada">Programada</option><option value="realizada">Realizada</option><option value="evaluada">Evaluada</option></select>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
</form></div></div>
