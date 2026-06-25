<div class="d-flex justify-content-between align-items-center mb-2">
    <div><h6 class="mb-0"><i class="fas fa-clipboard-list me-2" style="color:#dc3545"></i>Registro de Incidentes</h6></div>
    <div>
        <select class="form-select form-select-sm d-inline-block" style="width:180px" onchange="location.href='?seccion=incidentes&anio=<?= $anio ?>&tipo='+this.value">
            <option value="" <?= empty($tipo) ? 'selected' : '' ?>>Todos los tipos</option>
            <option value="accidente" <?= ($tipo ?? '') === 'accidente' ? 'selected' : '' ?>>Accidente</option>
            <option value="incidente" <?= ($tipo ?? '') === 'incidente' ? 'selected' : '' ?>>Incidente</option>
            <option value="casi_accidente" <?= ($tipo ?? '') === 'casi_accidente' ? 'selected' : '' ?>>Casi Accidente</option>
            <option value="enfermedad_laboral" <?= ($tipo ?? '') === 'enfermedad_laboral' ? 'selected' : '' ?>>Enfermedad Laboral</option>
        </select>
        <button class="btn btn-danger btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#modalIncidente"><i class="fas fa-plus me-1"></i>Reportar Incidente</button>
    </div>
</div>

<div class="card-box">
    <div class="card-box-body p-0">
    <?php if (empty($incidentes['data'])): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-clipboard-list" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
            <p class="mb-2">No hay incidentes registrados en <?= $anio ?></p>
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalIncidente"><i class="fas fa-plus me-1"></i>Reportar primer incidente</button>
        </div>
    <?php else: ?>
    <table class="table-box small mb-0">
        <thead><tr>
            <th>C&oacute;digo</th><th>Fecha</th><th>Tipo</th><th>Descripci&oacute;n</th><th>Gravedad</th><th>D&iacute;as Incap.</th><th>Costo</th><th>Estado</th><th class="text-center" style="width:100px">Acciones</th>
        </tr></thead>
        <tbody>
        <?php foreach ($incidentes['data'] as $inc): ?>
        <tr>
            <td><strong><?= htmlspecialchars($inc['inc_codigo'] ?? '') ?></strong></td>
            <td><?= date('d/m/Y', strtotime($inc['inc_fecha'] ?? '')) ?></td>
            <td><span class="badge bg-<?= ($inc['inc_tipo'] ?? '') === 'accidente' ? 'danger' : (($inc['inc_tipo'] ?? '') === 'enfermedad_laboral' ? 'warning' : (($inc['inc_tipo'] ?? '') === 'casi_accidente' ? 'info' : 'secondary')) ?>"><?= htmlspecialchars(str_replace('_', ' ', $inc['inc_tipo'] ?? '')) ?></span></td>
            <td><?= htmlspecialchars(mb_substr($inc['inc_descripcion'] ?? '', 0, 50)) ?></td>
            <td><span class="badge bg-<?= ($inc['inc_gravedad'] ?? '') === 'grave' ? 'danger' : (($inc['inc_gravedad'] ?? '') === 'moderado' ? 'warning' : 'info') ?>"><?= htmlspecialchars($inc['inc_gravedad'] ?? '') ?></span></td>
            <td><?= htmlspecialchars($inc['inc_dias_incapacidad'] ?? '0') ?></td>
            <td>$<?= number_format($inc['inc_costo'] ?? 0, 0, ',', '.') ?></td>
            <td><span class="badge bg-<?= ($inc['inc_estado'] ?? '') === 'cerrado' ? 'success' : (($inc['inc_estado'] ?? '') === 'investigado' ? 'info' : 'warning') ?>"><?= htmlspecialchars($inc['inc_estado'] ?? '') ?></span></td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-info" title="Investigar" onclick="abrirInvestigacion(<?= htmlspecialchars(json_encode($inc)) ?>)"><i class="fas fa-magnifying-glass"></i></button>
                <form method="POST" action="/sst/incidente/eliminar" style="display:inline" onsubmit="return confirm('&iquest;Eliminar este incidente?')">
                    <input type="hidden" name="id" value="<?= $inc['inc_id'] ?>">
                    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                    <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    </div>
</div>

<?php if (!empty($incidentes['pagination'])): ?>
<nav class="mt-2">
    <ul class="pagination pagination-sm justify-content-center">
        <?php foreach ($incidentes['pagination'] as $pag): ?>
        <li class="page-item <?= ($pag['active'] ?? false) ? 'active' : '' ?> <?= ($pag['disabled'] ?? false) ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($pag['url'] ?? '#') ?>"><?= htmlspecialchars($pag['label'] ?? '') ?></a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Modal Reportar Incidente -->
<div class="modal fade" id="modalIncidente">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="/sst/incidente/guardar" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <div class="modal-header"><h5>Reportar Incidente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small">Tipo *</label>
                        <select name="tipo" class="form-select form-select-sm" required>
                            <option value="accidente">Accidente</option><option value="incidente">Incidente</option><option value="casi_accidente">Casi Accidente</option><option value="enfermedad_laboral">Enfermedad Laboral</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Gravedad *</label>
                        <select name="gravedad" class="form-select form-select-sm" required>
                            <option value="leve">Leve</option><option value="moderado">Moderado</option><option value="grave">Grave</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Fecha *</label>
                        <input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small">D&iacute;as Incapacidad</label>
                        <input type="number" name="dias_incapacidad" class="form-control form-control-sm" value="0" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Costo Estimado</label>
                        <input type="number" name="costo" class="form-control form-control-sm" value="0" step="0.01" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Proceso</label>
                        <select name="proceso_id" class="form-select form-select-sm">
                            <option value="">-- Seleccionar --</option>
                            <?php if (!empty($procesos)): foreach ($procesos as $pro): ?>
                            <option value="<?= $pro['proceso_id'] ?>"><?= htmlspecialchars($pro['proceso_nombre']) ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Parte del Cuerpo</label>
                        <input type="text" name="parte_cuerpo" class="form-control form-control-sm" placeholder="Ej: Mano derecha">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Agente Causante</label>
                        <input type="text" name="agente" class="form-control form-control-sm" placeholder="Ej: M&aacute;quina, sustancia...">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Descripci&oacute;n *</label>
                    <textarea name="descripcion" class="form-control form-control-sm" rows="3" placeholder="Describa el incidente con detalle" required></textarea>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger">Reportar</button></div>
        </form>
    </div>
</div>

<!-- Modal Investigar Incidente -->
<div class="modal fade" id="modalInvestigacion">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="/sst/incidente/investigar" class="modal-content" id="formInvestigacion">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <input type="hidden" name="id" id="investigacionId">
            <div class="modal-header"><h5>Investigar Incidente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">D&iacute;as Incapacidad</label>
                        <input type="number" name="dias_incapacidad" id="investigacionDias" class="form-control form-control-sm" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Costo</label>
                        <input type="number" name="costo" id="investigacionCosto" class="form-control form-control-sm" step="0.01" min="0">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Investigaci&oacute;n</label>
                    <textarea name="investigacion" id="investigacionTexto" class="form-control form-control-sm" rows="3" placeholder="Resultados de la investigaci&oacute;n..."></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Acci&oacute;n Correctiva</label>
                    <textarea name="accion_correctiva" id="investigacionAccion" class="form-control form-control-sm" rows="2" placeholder="Acciones a implementar..."></textarea>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-info text-white">Registrar Investigaci&oacute;n</button></div>
        </form>
    </div>
</div>

<script>
function abrirInvestigacion(inc) {
    document.getElementById('investigacionId').value = inc.inc_id || '';
    document.getElementById('investigacionDias').value = inc.inc_dias_incapacidad || 0;
    document.getElementById('investigacionCosto').value = inc.inc_costo || 0;
    document.getElementById('investigacionTexto').value = inc.inc_investigacion || '';
    document.getElementById('investigacionAccion').value = inc.inc_accion_correctiva || '';
    new bootstrap.Modal(document.getElementById('modalInvestigacion')).show();
}
</script>
