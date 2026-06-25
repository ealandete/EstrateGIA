<div class="d-flex justify-content-between align-items-center mb-2">
    <div><h6 class="mb-0"><i class="fas fa-tower-broadcast me-2" style="color:#dc3545"></i>Planes de Emergencia</h6></div>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalEmergencia"><i class="fas fa-plus me-1"></i>Nuevo Plan</button>
</div>

<?php if (empty($emergencias)): ?>
<div class="card-box">
    <div class="card-box-body text-center py-5">
        <i class="fas fa-tower-broadcast" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
        <p class="text-muted mb-3">No hay planes de emergencia registrados</p>
        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalEmergencia"><i class="fas fa-plus me-1"></i>Registrar primer plan</button>
    </div>
</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($emergencias as $em): 
        $iconos = [
            'incendio'=>'fire', 'sismo'=>'house-crack', 'evacuacion'=>'person-running',
            'derrame'=>'droplet', 'explosion'=>'explosion', 'medica'=>'kit-medical',
            'inundacion'=>'water', 'amenaza'=>'triangle-exclamation', 'otro'=>'circle-exclamation'
        ];
        $icono = $iconos[$em['eme_tipo'] ?? ''] ?? 'circle-exclamation';
        $colores = ['incendio'=>'danger','sismo'=>'warning','evacuacion'=>'success','derrame'=>'info','explosion'=>'danger','medica'=>'primary','inundacion'=>'primary','amenaza'=>'dark','otro'=>'secondary'];
        $color = $colores[$em['eme_tipo'] ?? ''] ?? 'secondary';
    ?>
    <div class="col-md-4">
        <div class="card-box">
            <div class="card-box-header d-flex align-items-center" style="border-left:4px solid var(--bs-<?= $color ?>)">
                <i class="fas fa-<?= $icono ?> me-2" style="color:var(--bs-<?= $color ?>)"></i>
                <span class="flex-grow-1"><strong><?= htmlspecialchars($em['eme_nombre'] ?? '') ?></strong></span>
                <span class="badge bg-<?= ($em['eme_estado'] ?? '') === 'activo' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($em['eme_estado'] ?? '') ?></span>
            </div>
            <div class="card-box-body">
                <div class="small mb-2">
                    <div class="text-muted"><i class="fas fa-<?= $icono ?> me-1" style="width:16px"></i><?= htmlspecialchars(str_replace('_', ' ', $em['eme_tipo'] ?? '')) ?></div>
                    <?php if (!empty($em['eme_brigadistas'])): ?>
                    <div class="text-muted"><i class="fas fa-users me-1" style="width:16px"></i><?= htmlspecialchars($em['eme_brigadistas']) ?> brigadistas</div>
                    <?php endif; ?>
                    <?php if (!empty($em['eme_procedimiento'])): ?>
                    <div class="mt-1 text-muted"><i class="fas fa-clipboard me-1"></i><?= htmlspecialchars(mb_substr($em['eme_procedimiento'], 0, 80)) ?></div>
                    <?php endif; ?>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between small">
                    <span class="text-muted">&Uacute;ltimo simulacro: <?= !empty($em['eme_ultimo_simulacro']) ? date('d/m/Y', strtotime($em['eme_ultimo_simulacro'])) : 'N/A' ?></span>
                    <span class="text-muted">Pr&oacute;ximo: <?= !empty($em['eme_proximo_simulacro']) ? date('d/m/Y', strtotime($em['eme_proximo_simulacro'])) : 'N/A' ?></span>
                </div>
            </div>
            <div class="card-box-footer text-end">
                <form method="POST" action="/sst/emergencia/eliminar" style="display:inline" onsubmit="return confirm('&iquest;Eliminar este plan de emergencia?')">
                    <input type="hidden" name="id" value="<?= $em['eme_id'] ?>">
                    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal Nuevo Plan de Emergencia -->
<div class="modal fade" id="modalEmergencia">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="/sst/emergencia/guardar" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <div class="modal-header"><h5>Registrar Plan de Emergencia</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Nombre *</label>
                        <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej: Plan contra incendios" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Tipo *</label>
                        <select name="tipo" class="form-select form-select-sm" required>
                            <option value="incendio">Incendio</option>
                            <option value="sismo">Sismo</option>
                            <option value="evacuacion">Evacuaci&oacute;n</option>
                            <option value="derrame">Derrame Qu&iacute;mico</option>
                            <option value="explosion">Explosi&oacute;n</option>
                            <option value="medica">Emergencia M&eacute;dica</option>
                            <option value="inundacion">Inundaci&oacute;n</option>
                            <option value="amenaza">Amenaza Externa</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Procedimiento</label>
                    <textarea name="procedimiento" class="form-control form-control-sm" rows="3" placeholder="Describa el procedimiento de respuesta..."></textarea>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Brigadistas</label>
                        <input type="number" name="brigadistas" class="form-control form-control-sm" min="0" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Estado</label>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="activo">Activo</option><option value="inactivo">Inactivo</option><option value="en_revision">En Revisi&oacute;n</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">&Uacute;ltimo Simulacro</label>
                        <input type="date" name="ultimo_simulacro" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Pr&oacute;ximo Simulacro</label>
                        <input type="date" name="proximo_simulacro" class="form-control form-control-sm">
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger">Registrar Plan</button></div>
        </form>
    </div>
</div>
