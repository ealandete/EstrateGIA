<?php $created = $_GET['created'] ?? null; $deleted = $_GET['deleted'] ?? null; $updated = $_GET['updated'] ?? null; ?>
<?php if ($created): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Creado correctamente</div><?php endif; ?>
<?php if ($updated): ?><div class="alert alert-info"><i class="fas fa-check-circle me-2"></i>Actualizado</div><?php endif; ?>

<nav class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="/procesos">Mapa de Procesos</a></li><li class="breadcrumb-item active"><?= htmlspecialchars($proceso['proceso_nombre']) ?></li></ol></nav>

<div class="row g-4">
    <div class="col-md-8">
        <!-- Info del proceso -->
        <div class="card-box mb-3">
            <div class="card-box-header d-flex justify-content-between">
                <span><i class="fas fa-diagram-project me-2"></i><?= htmlspecialchars($proceso['proceso_nombre']) ?></span>
                <div>
                    <span class="badge bg-light text-dark me-2"><?= $proceso['proceso_tipo'] ?></span>
                    <button class="btn btn-sm btn-outline-secondary me-1" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditarProceso"><i class="fas fa-edit"></i></button>
                </div>
            </div>
            <div class="card-box-body">
                <div class="row">
                    <div class="col-6"><strong>Macroproceso:</strong> <?= htmlspecialchars($proceso['macro_nombre']) ?></div>
                    <div class="col-6"><strong>Responsable:</strong> <?= htmlspecialchars($proceso['responsable_nombre'] ?? 'No asignado') ?></div>
                    <div class="col-12 mt-2"><strong>Objetivo:</strong> <span class="text-muted"><?= htmlspecialchars($proceso['proceso_objetivo'] ?? 'No definido') ?></span></div>
                    <div class="col-12 mt-1"><strong>Alcance:</strong> <span class="text-muted"><?= htmlspecialchars($proceso['proceso_alcance'] ?? 'No definido') ?></span></div>
                </div>
            </div>
        </div>

        <!-- Procedimientos -->
        <div class="card-box mb-3">
            <div class="card-box-header d-flex justify-content-between">
                <span><i class="fas fa-list-ol me-2"></i>Procedimientos (<?= count($procedimientos) ?>)</span>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalProcedimiento"><i class="fas fa-plus me-1"></i>Nuevo</button>
            </div>
            <div class="card-box-body p-0">
                <?php if (empty($procedimientos)): ?>
                <div class="p-3 text-muted small text-center">Sin procedimientos definidos</div>
                <?php else: ?>
                <?php foreach ($procedimientos as $pr): ?>
                <div class="p-2 px-3 border-bottom small">
                    <strong><?= htmlspecialchars($pr['procedimiento_nombre']) ?></strong>
                    <?php if ($pr['procedimiento_codigo']): ?><span class="badge bg-light text-dark ms-1"><?= htmlspecialchars($pr['procedimiento_codigo']) ?></span><?php endif; ?>
                    <?php if ($pr['procedimiento_descripcion']): ?><div class="text-muted"><?= htmlspecialchars($pr['procedimiento_descripcion']) ?></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tareas -->
        <div class="card-box">
            <div class="card-box-header d-flex justify-content-between">
                <span><i class="fas fa-tasks me-2"></i>Tareas (<?= count($tareas) ?>)</span>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalTarea"><i class="fas fa-plus me-1"></i>Nueva Tarea</button>
            </div>
            <div class="card-box-body p-0">
                <?php if (empty($tareas)): ?>
                <div class="p-3 text-muted small text-center">Sin tareas definidas</div>
                <?php else: ?>
                <table class="table-box small">
                    <thead><tr><th>#</th><th>Tarea</th><th>Tipo</th><th>Tiempo Est.</th><th>Responsable</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($tareas as $t): ?>
                    <tr>
                        <td><?= $t['tarea_orden'] ?></td>
                        <td><strong><?= htmlspecialchars($t['tarea_nombre']) ?></strong></td>
                        <td><?= $t['tarea_tipo'] ?></td>
                        <td><?= $t['tarea_tiempo_estimado_minutos'] ? round($t['tarea_tiempo_estimado_minutos']/60,1).'h' : '-' ?></td>
                        <td><?= htmlspecialchars($t['responsable_nombre'] ?? '-') ?></td>
                        <td>
                            <form method="POST" action="/procesos/eliminar-tarea" onsubmit="return confirm('¿Eliminar?')">
                                <input type="hidden" name="tarea_id" value="<?= $t['tarea_id'] ?>">
                                <input type="hidden" name="proceso_id" value="<?= $proceso['proceso_id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Documentos asociados -->
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-file-alt me-2"></i>Documentos (<?= count($documentos) ?>)</div>
            <div class="card-box-body p-0">
                <?php foreach ($documentos as $doc): ?>
                <a href="/documentos/ver/<?= $doc['documento_id'] ?>" class="d-block p-2 px-3 border-bottom text-decoration-none small" style="color:#333">
                    <i class="fas fa-file-alt text-primary me-1"></i><?= htmlspecialchars($doc['documento_titulo']) ?>
                    <span class="badge bg-light text-dark ms-1">v<?= $doc['documento_version'] ?></span>
                </a>
                <?php endforeach; ?>
                <?php if (empty($documentos)): ?><div class="p-3 text-muted small text-center">Sin documentos</div><?php endif; ?>
            </div>
        </div>

        <!-- Indicadores del proceso -->
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-gauge me-2"></i>Indicadores (<?= count($indicadores) ?>)</div>
            <div class="card-box-body p-0">
                <?php foreach ($indicadores as $ind): ?>
                <div class="p-2 px-3 border-bottom small">
                    <strong><?= htmlspecialchars($ind['indicador_nombre']) ?></strong>
                    <span class="badge bg-light text-dark ms-1"><?= $ind['categoria_nombre'] ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($indicadores)): ?><div class="p-3 text-muted small text-center">Sin indicadores</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Nuevo Procedimiento -->
<div class="modal fade" id="modalProcedimiento" tabindex="-1">
    <div class="modal-dialog"><form method="POST" action="/procesos/crear-procedimiento" class="modal-content">
        <input type="hidden" name="proceso_id" value="<?= $proceso['proceso_id'] ?>">
        <div class="modal-header"><h5 class="modal-title">Nuevo Procedimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-2"><input type="text" name="nombre" class="form-control" placeholder="Nombre *" required></div>
            <div class="row g-2 mb-2"><div class="col-6"><input type="text" name="codigo" class="form-control" placeholder="Código"></div><div class="col-6"><input type="number" name="orden" class="form-control" placeholder="Orden" value="1"></div></div>
            <div class="mb-2"><textarea name="objetivo" class="form-control" rows="2" placeholder="Objetivo"></textarea></div>
            <textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción"></textarea>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Crear</button></div>
    </form></div>
</div>

<!-- MODAL: Nueva Tarea -->
<div class="modal fade" id="modalTarea" tabindex="-1">
    <div class="modal-dialog"><form method="POST" action="/procesos/crear-tarea" class="modal-content">
        <input type="hidden" name="proceso_id" value="<?= $proceso['proceso_id'] ?>">
        <div class="modal-header"><h5 class="modal-title">Nueva Tarea</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-2"><input type="text" name="nombre" class="form-control" placeholder="Nombre de la tarea *" required></div>
            <div class="row g-2 mb-2">
                <div class="col-4"><input type="text" name="codigo" class="form-control" placeholder="Código"></div>
                <div class="col-4"><input type="number" name="orden" class="form-control" placeholder="Orden" value="1"></div>
                <div class="col-4"><input type="number" name="tiempo_estimado" class="form-control" placeholder="Minutos est."></div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <select name="tipo_tarea" class="form-select form-select-sm">
                        <option value="manual">Manual</option><option value="automatica">Automática</option><option value="semi_automatica">Semi-automática</option><option value="decision">Decisión</option>
                    </select>
                </div>
                <div class="col-6">
                    <select name="responsable_id" class="form-select form-select-sm">
                        <option value="">Sin responsable</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['usuario_id'] ?>"><?= htmlspecialchars($u['usuario_nombre'].' '.$u['usuario_apellido']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción"></textarea>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Crear Tarea</button></div>
    </form></div>
</div>

<!-- MODAL: Editar Proceso -->
<div class="modal fade" id="modalEditarProceso" tabindex="-1">
    <div class="modal-dialog modal-lg"><form method="POST" action="/procesos/editar-proceso" class="modal-content">
        <input type="hidden" name="proceso_id" value="<?= $proceso['proceso_id'] ?>">
        <div class="modal-header"><h5 class="modal-title">Editar Proceso</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-2"><input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($proceso['proceso_nombre']) ?>" required></div>
            <div class="mb-2"><input type="text" name="objetivo" class="form-control" value="<?= htmlspecialchars($proceso['proceso_objetivo'] ?? '') ?>" placeholder="Objetivo"></div>
            <div class="mb-2"><textarea name="alcance" class="form-control" rows="2" placeholder="Alcance"><?= htmlspecialchars($proceso['proceso_alcance'] ?? '') ?></textarea></div>
            <div class="mb-2"><textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción"><?= htmlspecialchars($proceso['proceso_descripcion'] ?? '') ?></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
    </form></div>
</div>
