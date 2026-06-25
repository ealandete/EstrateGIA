<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">Plan de gesti&oacute;n ambiental <?= $anio ?></div>
    <div>
        <?php if (!empty($planGestion)): ?>
        <button class="btn btn-outline-success btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modalPrograma"><i class="fas fa-plus me-1"></i>A&ntilde;adir Programa</button>
        <?php endif; ?>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalPlan"><i class="fas fa-plus me-1"></i><?= empty($planGestion) ? 'Crear Plan' : 'Editar Plan' ?></button>
    </div>
</div>

<?php if (empty($planGestion)): ?>
<div class="card-box">
    <div class="card-box-body text-center py-5">
        <i class="fas fa-calendar-alt" style="font-size:3rem;color:#ddd;display:block;margin-bottom:10px"></i>
        <p class="text-muted">No hay un plan de gesti&oacute;n ambiental para <?= $anio ?>.</p>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPlan"><i class="fas fa-plus me-1"></i>Crear Plan de Gesti&oacute;n Ambiental</button>
    </div>
</div>
<?php else: ?>
<div class="card-box mb-3">
    <div class="card-box-header"><i class="fas fa-calendar-alt me-2"></i>Plan de Gesti&oacute;n Ambiental <?= $anio ?></div>
    <div class="card-box-body">
        <div class="row g-2 small">
            <div class="col-md-4"><strong>Objetivo:</strong> <?= htmlspecialchars($planGestion['objetivo'] ?? '') ?></div>
            <div class="col-md-4"><strong>Alcance:</strong> <?= htmlspecialchars($planGestion['alcance'] ?? '') ?></div>
            <div class="col-md-4"><strong>Presupuesto:</strong> $<?= number_format($planGestion['presupuesto'] ?? 0, 0) ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <?php foreach ($programas as $prg): ?>
    <div class="col-md-6">
        <div class="card-box">
            <div class="card-box-header d-flex justify-content-between align-items-center">
                <div><i class="fas fa-leaf me-2"></i><?= htmlspecialchars($prg['nombre'] ?? 'Programa') ?></div>
                <span class="badge bg-<?= ($prg['tipo'] ?? '') === 'prevencion' ? 'info' : (($prg['tipo'] ?? '') === 'mitigacion' ? 'warning' : (($prg['tipo'] ?? '') === 'compensacion' ? 'success' : 'secondary')) ?>"><?= htmlspecialchars($prg['tipo'] ?? '') ?></span>
            </div>
            <div class="card-box-body small">
                <p class="mb-2"><strong>Objetivo:</strong> <?= htmlspecialchars($prg['objetivo'] ?? '') ?></p>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span><strong>Indicador:</strong> <?= number_format($prg['indicador_valor'] ?? 0, 1) ?> de <?= number_format($prg['indicador_meta'] ?? 0, 1) ?> <?= htmlspecialchars($prg['unidad'] ?? '') ?></span>
                    <span class="badge bg-<?= ($prg['estado'] ?? '') === 'completado' ? 'success' : (($prg['estado'] ?? '') === 'en_progreso' ? 'warning' : (($prg['estado'] ?? '') === 'pendiente' ? 'secondary' : 'danger')) ?>"><?= htmlspecialchars($prg['estado'] ?? '') ?></span>
                </div>
                <div class="progress" style="height:6px"><div class="progress-bar bg-success" style="width:<?= min(100, (($prg['indicador_valor'] ?? 0) / max(1, $prg['indicador_meta'] ?? 1)) * 100) ?>%"></div></div>
                <div class="mt-2 text-end">
                    <button class="btn btn-sm btn-outline-secondary" onclick="actualizarPrograma(<?= htmlspecialchars(json_encode($prg)) ?>)"><i class="fas fa-edit me-1"></i>Actualizar</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="modal fade" id="modalPlan"><div class="modal-dialog"><form method="POST" action="/ambiental/plan/guardar" class="modal-content">
    <input type="hidden" name="id" value="<?= $planGestion['id'] ?? '' ?>">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? '' ?>">
    <input type="hidden" name="anio" value="<?= $anio ?>">
    <div class="modal-header"><h5><?= empty($planGestion) ? 'Crear' : 'Editar' ?> Plan de Gesti&oacute;n Ambiental</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <?php if (!empty($planesEstrategicos)): ?>
        <label class="small fw-bold mb-1">Plan Estrat&eacute;gico Asociado</label>
        <select name="plan_estrategico_id" class="form-select form-select-sm mb-2">
            <option value="">Ninguno</option>
            <?php foreach ($planesEstrategicos as $pe): ?>
            <option value="<?= $pe['id'] ?? '' ?>" <?= ($planGestion['plan_estrategico_id'] ?? '') == ($pe['id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($pe['nombre'] ?? $pe['titulo'] ?? '') ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <label class="small fw-bold mb-1">Objetivo General</label>
        <textarea name="objetivo" class="form-control form-control-sm mb-2" rows="2" placeholder="Objetivo del plan de gesti&oacute;n ambiental *" required><?= htmlspecialchars($planGestion['objetivo'] ?? '') ?></textarea>
        <label class="small fw-bold mb-1">Alcance</label>
        <textarea name="alcance" class="form-control form-control-sm mb-2" rows="2" placeholder="Alcance y cobertura del plan *" required><?= htmlspecialchars($planGestion['alcance'] ?? '') ?></textarea>
        <label class="small fw-bold mb-1">Presupuesto Estimado ($)</label>
        <input type="number" name="presupuesto" class="form-control form-control-sm" placeholder="0" step="0.01" value="<?= $planGestion['presupuesto'] ?? '' ?>">
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success btn-sm">Guardar Plan</button></div>
</form></div></div>

<div class="modal fade" id="modalPrograma"><div class="modal-dialog"><form method="POST" action="/ambiental/programa/guardar" class="modal-content" id="formPrograma">
    <input type="hidden" name="id" id="prg_id">
    <input type="hidden" name="plan_gestion_id" value="<?= $planGestion['id'] ?? '' ?>">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?? '' ?>">
    <input type="hidden" name="anio" value="<?= $anio ?>">
    <div class="modal-header"><h5 id="modalProgramaTitle">A&ntilde;adir Programa Ambiental</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="small fw-bold mb-1">Nombre del Programa</label>
        <input type="text" name="nombre" id="prg_nombre" class="form-control form-control-sm mb-2" placeholder="Ej: Programa de ahorro de agua" required>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="small fw-bold mb-1">Tipo</label>
                <select name="tipo" id="prg_tipo" class="form-select form-select-sm" required>
                    <option value="">Seleccione...</option>
                    <option value="prevencion">Prevenci&oacute;n</option>
                    <option value="mitigacion">Mitigaci&oacute;n</option>
                    <option value="compensacion">Compensaci&oacute;n</option>
                    <option value="control">Control</option>
                    <option value="monitoreo">Monitoreo</option>
                </select>
            </div>
            <div class="col-6">
                <label class="small fw-bold mb-1">Estado</label>
                <select name="estado" id="prg_estado" class="form-select form-select-sm" required>
                    <option value="pendiente">Pendiente</option>
                    <option value="en_progreso">En Progreso</option>
                    <option value="completado">Completado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
        </div>
        <label class="small fw-bold mb-1">Objetivo del Programa</label>
        <textarea name="objetivo" id="prg_objetivo" class="form-control form-control-sm mb-2" rows="2" placeholder="Objetivo espec&iacute;fico del programa" required></textarea>
        <div class="row g-2 mb-2">
            <div class="col-4">
                <label class="small fw-bold mb-1">Indicador (valor)</label>
                <input type="number" name="indicador_valor" id="prg_valor" class="form-control form-control-sm" placeholder="0" step="0.01">
            </div>
            <div class="col-4">
                <label class="small fw-bold mb-1">Meta</label>
                <input type="number" name="indicador_meta" id="prg_meta" class="form-control form-control-sm" placeholder="0" step="0.01">
            </div>
            <div class="col-4">
                <label class="small fw-bold mb-1">Unidad</label>
                <input type="text" name="unidad" id="prg_unidad" class="form-control form-control-sm" placeholder="Ej: %">
            </div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success btn-sm">Guardar Programa</button></div>
</form></div></div>

<script>
function actualizarPrograma(p) {
    document.getElementById('modalProgramaTitle').innerText='Actualizar Programa';
    document.getElementById('prg_id').value=p.id||'';
    document.getElementById('prg_nombre').value=p.nombre||'';
    document.getElementById('prg_tipo').value=p.tipo||'';
    document.getElementById('prg_estado').value=p.estado||'pendiente';
    document.getElementById('prg_objetivo').value=p.objetivo||'';
    document.getElementById('prg_valor').value=p.indicador_valor||'';
    document.getElementById('prg_meta').value=p.indicador_meta||'';
    document.getElementById('prg_unidad').value=p.unidad||'';
    new bootstrap.Modal(document.getElementById('modalPrograma')).show();
}
document.getElementById('modalPrograma').addEventListener('hidden.bs.modal',function(){
    document.getElementById('modalProgramaTitle').innerText='A&ntilde;adir Programa Ambiental';
    document.getElementById('formPrograma').reset();
});
</script>
