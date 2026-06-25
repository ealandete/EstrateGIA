<div class="d-flex justify-content-between align-items-center mb-2">
    <div><h6 class="mb-0"><i class="fas fa-stethoscope me-2" style="color:#198754"></i>Ex&aacute;menes M&eacute;dicos Ocupacionales <?= $anio ?></h6></div>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalExamen"><i class="fas fa-plus me-1"></i>Registrar Examen</button>
</div>

<div class="card-box">
    <div class="card-box-body p-0">
    <?php if (empty($examenes)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-stethoscope" style="font-size:3rem;display:block;margin-bottom:12px;color:#ccc"></i>
            <p class="mb-2">No hay ex&aacute;menes registrados en <?= $anio ?></p>
            <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalExamen"><i class="fas fa-plus me-1"></i>Registrar primer examen</button>
        </div>
    <?php else: ?>
    <table class="table-box small mb-0">
        <thead><tr>
            <th>Colaborador</th><th>Tipo</th><th>Fecha</th><th>Resultado</th><th>IPS</th><th>Restricciones</th><th class="text-center" style="width:80px">Acciones</th>
        </tr></thead>
        <tbody>
        <?php foreach ($examenes as $ex): ?>
        <tr>
            <td><?= htmlspecialchars($ex['usuario_nombre'] ?? $ex['exa_usuario_id'] ?? '') ?></td>
            <td><span class="badge bg-info"><?= htmlspecialchars(str_replace('_', ' ', $ex['exa_tipo'] ?? '')) ?></span></td>
            <td><?= date('d/m/Y', strtotime($ex['exa_fecha'] ?? '')) ?></td>
            <td>
                <?php $resultado = $ex['exa_resultado'] ?? 'pendiente'; ?>
                <span class="badge bg-<?= $resultado === 'apto' ? 'success' : ($resultado === 'apto_restricciones' ? 'warning' : 'danger') ?>"><?= htmlspecialchars(str_replace('_', ' ', $resultado)) ?></span>
            </td>
            <td><?= htmlspecialchars($ex['exa_ips'] ?? '') ?></td>
            <td><?= htmlspecialchars(mb_substr($ex['exa_restricciones'] ?? '', 0, 30)) ?></td>
            <td class="text-center">
                <form method="POST" action="/sst/examen/eliminar" style="display:inline" onsubmit="return confirm('&iquest;Eliminar este examen?')">
                    <input type="hidden" name="id" value="<?= $ex['exa_id'] ?>">
                    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                    <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php $page = $_GET['page'] ?? 1; ?>
    <?php if (isset($examenes['pagination']) && $examenes['pagination']['total_pages'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center p-2 small">
        <span class="text-muted"><?= $examenes['pagination']['total'] ?> registros</span>
        <div class="btn-group btn-group-sm">
            <?php for ($i=1; $i<=$examenes['pagination']['total_pages']; $i++): ?>
            <a href="?seccion=examenes&anio=<?= $anio ?>&page=<?= $i ?>" class="btn <?= $i==$page?'btn-success':'btn-outline-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    </div>
</div>

<!-- Modal Registrar Examen -->
<div class="modal fade" id="modalExamen">
    <div class="modal-dialog">
        <form method="POST" action="/sst/examen/guardar" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <div class="modal-header"><h5>Registrar Examen M&eacute;dico</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Colaborador *</label>
                    <select name="usuario_id" class="form-select form-select-sm" required>
                        <option value="">-- Seleccionar --</option>
                        <?php if (!empty($usuarios)): foreach ($usuarios as $u): ?>
                        <option value="<?= $u['usuario_id'] ?>"><?= htmlspecialchars($u['usuario_nombre'] ?? '') ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Tipo *</label>
                        <select name="tipo" class="form-select form-select-sm" required>
                            <option value="ingreso">Ingreso</option>
                            <option value="periodico">Peri&oacute;dico</option>
                            <option value="retiro">Retiro</option>
                            <option value="reintegro">Reintegro</option>
                            <option value="post_incapacidad">Post-Incapacidad</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Fecha *</label>
                        <input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Resultado *</label>
                        <select name="resultado" class="form-select form-select-sm" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="apto">Apto</option>
                            <option value="apto_restricciones">Apto con Restricciones</option>
                            <option value="no_apto">No Apto</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">IPS</label>
                        <input type="text" name="ips" class="form-control form-control-sm" placeholder="Nombre de la IPS">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Restricciones</label>
                    <textarea name="restricciones" class="form-control form-control-sm" rows="2" placeholder="Restricciones m&eacute;dicas..."></textarea>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Registrar</button></div>
        </form>
    </div>
</div>
