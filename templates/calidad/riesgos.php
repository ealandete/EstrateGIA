<?php $created = $_GET['created'] ?? null; ?>
<?php if ($created): ?><div class="alert alert-success">Riesgo registrado</div><?php endif; ?>

<div class="d-flex justify-content-between mb-3">
    <h5><i class="fas fa-triangle-exclamation me-2" style="color:#dc3545"></i>Matriz de Riesgos · <?= htmlspecialchars($empresa['empresa_nombre']) ?></h5>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalRiesgo"><i class="fas fa-plus me-1"></i>Nuevo Riesgo</button>
</div>

<?php if (empty($riesgos)): ?>
<div class="card-box"><div class="card-box-body text-center py-4 text-muted">Sin riesgos identificados</div></div>
<?php else: ?>
<div class="card-box"><div class="card-box-body p-0">
<table class="table-box">
    <thead><tr><th>Código</th><th>Descripción</th><th>Tipo</th><th>Probabilidad</th><th>Impacto</th><th>Nivel</th><th>Proceso</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach ($riesgos as $r): ?>
    <tr>
        <td><strong><?= htmlspecialchars($r['riesgo_codigo']) ?></strong></td>
        <td><small><?= htmlspecialchars(substr($r['riesgo_descripcion'],0,70)) ?>...</small></td>
        <td><?= $r['riesgo_tipo'] ?></td>
        <td><?= $r['riesgo_probabilidad'] ?></td>
        <td><?= $r['riesgo_impacto'] ?></td>
        <td><span class="badge bg-<?= ['extremo'=>'danger','alto'=>'warning','medio'=>'info','bajo'=>'success'][$r['riesgo_nivel']] ?>"><?= $r['riesgo_nivel'] ?></span></td>
        <td><small><?= htmlspecialchars($r['proceso_nombre']??'-') ?></small></td>
        <td><?= $r['riesgo_estado'] ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>
<?php endif; ?>

<!-- Matriz de calor 5x5 -->
<div class="card-box mt-4">
    <div class="card-box-header">Matriz de Calor (Probabilidad × Impacto)</div>
    <div class="card-box-body">
        <table class="table table-bordered text-center small">
            <thead><tr><th>Prob / Imp</th><th>Insignificante</th><th>Menor</th><th>Moderado</th><th>Mayor</th><th>Catastrófico</th></tr></thead>
            <tbody>
            <?php 
            $probs = ['casi_seguro'=>'Casi Seguro','probable'=>'Probable','posible'=>'Posible','improbable'=>'Improbable','raro'=>'Raro'];
            $colors = ['bajo'=>'#d1fae5','medio'=>'#fef3c7','alto'=>'#fed7aa','extremo'=>'#fee2e2'];
            $matriz = ['raro'=>['bajo','bajo','medio','medio','alto'],'improbable'=>['bajo','medio','medio','alto','alto'],'posible'=>['bajo','medio','alto','alto','extremo'],'probable'=>['medio','medio','alto','extremo','extremo'],'casi_seguro'=>['medio','alto','extremo','extremo','extremo']];
            foreach ($probs as $pk => $pl):
                $riesgosEnFila = array_filter($riesgos, fn($r)=>$r['riesgo_probabilidad']===$pk);
            ?>
            <tr><td class="fw-bold"><?= $pl ?></td>
                <?php for ($i=0;$i<5;$i++): $nivel = $matriz[$pk][$i]; ?>
                <td style="background:<?= $colors[$nivel] ?>">
                    <?php foreach ($riesgosEnFila as $rr): 
                        $impIdx = ['insignificante'=>0,'menor'=>1,'moderado'=>2,'mayor'=>3,'catastrofico'=>4];
                        if ($impIdx[$rr['riesgo_impacto']] === $i): ?>
                        <span class="badge bg-<?= ['extremo'=>'danger','alto'=>'warning','medio'=>'info','bajo'=>'success'][$nivel] ?>" title="<?= htmlspecialchars($rr['riesgo_descripcion']) ?>"><?= htmlspecialchars($rr['riesgo_codigo']) ?></span>
                    <?php endif; endforeach; ?>
                </td>
                <?php endfor; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL Nuevo Riesgo -->
<div class="modal fade" id="modalRiesgo" tabindex="-1"><div class="modal-dialog"><form method="POST" action="/calidad/riesgos/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5 class="modal-title">Nuevo Riesgo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <textarea name="descripcion" class="form-control mb-2" rows="3" placeholder="Descripción del riesgo *" required></textarea>
        <div class="row g-2 mb-2">
            <div class="col-6"><select name="tipo" class="form-select form-select-sm"><option value="estrategico">Estratégico</option><option value="operativo">Operativo</option><option value="financiero">Financiero</option><option value="cumplimiento">Cumplimiento</option><option value="reputacional">Reputacional</option><option value="tecnologico">Tecnológico</option><option value="talento_humano">Talento Humano</option><option value="legal">Legal</option><option value="ambiental">Ambiental</option><option value="seguridad">Seguridad</option></select></div>
            <div class="col-6"><select name="proceso_id" class="form-select form-select-sm"><option value="">Sin proceso</option><?php foreach ($procesos as $pr): ?><option value="<?= $pr['proceso_id'] ?>"><?= htmlspecialchars($pr['proceso_nombre']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label small">Probabilidad</label><select name="probabilidad" class="form-select form-select-sm"><option value="raro">Raro</option><option value="improbable">Improbable</option><option value="posible" selected>Posible</option><option value="probable">Probable</option><option value="casi_seguro">Casi Seguro</option></select></div>
            <div class="col-6"><label class="form-label small">Impacto</label><select name="impacto" class="form-select form-select-sm"><option value="insignificante">Insignificante</option><option value="menor">Menor</option><option value="moderado" selected>Moderado</option><option value="mayor">Mayor</option><option value="catastrofico">Catastrófico</option></select></div>
        </div>
        <textarea name="controles" class="form-control form-control-sm mb-2" rows="2" placeholder="Controles existentes"></textarea>
        <input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger">Registrar Riesgo</button></div>
</form></div></div>
<?php $moduloContexto = "calidad"; require BASE_PATH . "/templates/hse/ia_panel.php"; ?>
