<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="fas fa-history me-2"></i>Registro de Actividad del Sistema</h5>
    <small class="text-muted"><?= $result['pagination']['total'] ?> eventos registrados</small>
</div>

<!-- Filtros avanzados -->
<div class="card-box mb-3">
    <div class="card-box-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaDesde ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaHasta ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Módulo</label>
                <select name="modulo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($modulos as $m): ?>
                    <option value="<?= htmlspecialchars($m['log_modulo']) ?>" <?= ($modulo===$m['log_modulo'])?'selected':'' ?>><?= htmlspecialchars($m['log_modulo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Acción</label>
                <select name="accion" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($acciones as $a): ?>
                    <option value="<?= htmlspecialchars($a['log_accion']) ?>" <?= ($accion===$a['log_accion'])?'selected':'' ?>><?= htmlspecialchars($a['log_accion']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Usuario</label>
                <select name="usuario_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['usuario_id'] ?>" <?= ($usuarioId==$u['usuario_id'])?'selected':'' ?>><?= htmlspecialchars($u['usuario_nombre'].' '.$u['usuario_apellido']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i>Filtrar</button>
            </div>
        </form>
        <div class="mt-2">
            <form method="GET" class="input-group input-group-sm">
                <input type="text" name="q" class="form-control" placeholder="Buscar en entidad, detalle o acción..." value="<?= htmlspecialchars($busqueda ?? '') ?>">
                <button class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de resultados -->
<div class="card-box">
    <div class="card-box-body p-0">
        <table class="table-box small">
            <thead><tr><th style="width:130px">Fecha</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Entidad</th><th>Detalle</th><th style="width:100px">IP</th></tr></thead>
            <tbody>
            <?php foreach (($result['data'] ?? []) as $log): 
                $accionColor = ['crear'=>'success','editar'=>'warning','eliminar'=>'danger','aprobar'=>'primary','publicar'=>'info','cambio_estado'=>'secondary'];
                $color = $accionColor[$log['log_accion']] ?? 'dark';
            ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                <td><strong><?= htmlspecialchars($log['usuario_nombre'] ?? 'Sistema') ?></strong></td>
                <td><span class="badge bg-<?= $color ?>"><?= htmlspecialchars($log['log_accion']) ?></span></td>
                <td><?= htmlspecialchars($log['log_modulo'] ?? '-') ?></td>
                <td><?= htmlspecialchars(($log['log_entidad']??'') . ($log['log_entidad_id'] ? ' #'.$log['log_entidad_id'] : '')) ?></td>
                <td><small class="text-muted"><?= htmlspecialchars(substr($log['log_detalle'] ?? '', 0, 100)) ?></small></td>
                <td><small><?= htmlspecialchars($log['log_ip'] ?? '') ?></small></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($result['data'])): ?>
            <tr><td colspan="7" class="text-center text-muted py-3">Sin registros<?= $busqueda?' para "'.htmlspecialchars($busqueda).'"':'' ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (($result['pagination']['total_pages'] ?? 1) > 1): ?>
    <div class="card-box-body border-top">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Pág <?= $result['pagination']['page'] ?>/<?= $result['pagination']['total_pages'] ?> · <?= $result['pagination']['total'] ?> registros</small>
            <div class="d-flex gap-1">
                <?php $qs = http_build_query(array_filter(['pagina'=>1,'modulo'=>$modulo,'accion'=>$accion,'usuario_id'=>$usuarioId,'fecha_desde'=>$fechaDesde,'fecha_hasta'=>$fechaHasta,'q'=>$busqueda])); ?>
                <?php if ($pagina > 1): ?><a href="?<?= $qs ?>&pagina=<?= $pagina-1 ?>" class="btn btn-sm btn-light"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                <?php for ($i=max(1,$pagina-2); $i<=min($result['pagination']['total_pages'],$pagina+2); $i++): ?>
                <a href="?<?= $qs ?>&pagina=<?= $i ?>" class="btn btn-sm <?= $i===$pagina?'btn-primary':'btn-light' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($pagina < $result['pagination']['total_pages']): ?><a href="?<?= $qs ?>&pagina=<?= $pagina+1 ?>" class="btn btn-sm btn-light"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
