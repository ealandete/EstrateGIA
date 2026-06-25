<?php
$macroColors = ['estrategico'=>'#1a73e8','misional'=>'#28a745','apoyo'=>'#ffc107','evaluacion'=>'#6f42c1'];
$macroIcons = ['estrategico'=>'crown','misional'=>'stethoscope','apoyo'=>'gear','evaluacion'=>'magnifying-glass-chart'];
$macroLabels = ['estrategico'=>'Estratégicos','misional'=>'Misionales','apoyo'=>'De Apoyo','evaluacion'=>'De Evaluación'];
$created = $_GET['created'] ?? null; $updated = $_GET['updated'] ?? null; $deleted = $_GET['deleted'] ?? null;
?>
<?php if ($created): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Creado correctamente</div><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="fw-bold mb-0" style="font-size:1.15rem"><i class="fas fa-sitemap me-2"></i>Mapa de Procesos · <?= htmlspecialchars($empresa['empresa_nombre']) ?></div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMacroproceso">
        <i class="fas fa-plus me-1"></i>Nuevo Macroproceso
    </button>
</div>

<?php if (empty($macroprocesos)): ?>
<div class="card-box"><div class="card-box-body text-center py-5">
    <i class="fas fa-sitemap" style="font-size:4rem;color:#ccc;display:block;margin-bottom:16px"></i>
    <h5>No hay procesos definidos</div>
    <p class="text-muted">Crea el primer macroproceso para comenzar.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMacroproceso"><i class="fas fa-plus me-1"></i>Crear Macroproceso</button>
</div></div>
<?php else: ?>

<?php $porTipo = ['estrategico'=>[],'misional'=>[],'apoyo'=>[],'evaluacion'=>[]];
foreach ($macroprocesos as $mp) $porTipo[$mp['macro_tipo']][] = $mp; ?>

<?php foreach ($porTipo as $tipo => $macros): if (empty($macros)) continue; ?>
<div class="card-box mb-4">
    <div class="card-box-header" style="border-left:5px solid <?= $macroColors[$tipo] ?>">
        <i class="fas fa-<?= $macroIcons[$tipo] ?> me-2" style="color:<?= $macroColors[$tipo] ?>"></i>
        <strong><?= $macroLabels[$tipo] ?></strong>
        <span class="badge ms-2" style="background:<?= $macroColors[$tipo] ?>;color:#fff"><?= count($macros) ?> MP · <?= array_sum(array_column($macros, 'total_procesos')) ?> procesos</span>
    </div>
    <div class="card-box-body p-0">
        <table class="table-box">
            <thead><tr><th>Macroproceso</th><th>Código</th><th>Procesos</th><th>Orden</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($macros as $mp): ?>
            <tr class="hover-bg">
                <td><strong><?= htmlspecialchars($mp['macro_nombre']) ?></strong></td>
                <td><code><?= htmlspecialchars($mp['macro_codigo'] ?? '-') ?></code></td>
                <td>
                    <?php foreach ($mp['procesos'] as $proc): ?>
                    <a href="/procesos/ver/<?= $proc['proceso_id'] ?>" class="badge bg-light text-dark text-decoration-none me-1 mb-1">
                        <?= htmlspecialchars($proc['proceso_nombre']) ?>
                        <small class="text-muted">(<?= $proc['total_procedimientos'] ?>p/<?= $proc['total_tareas'] ?>t)</small>
                    </a>
                    <?php endforeach; ?>
                    <button class="btn btn-sm btn-link small" onclick="nuevoProceso(<?= $mp['macro_id'] ?>,'<?= $mp['macro_tipo'] ?>')"><i class="fas fa-plus"></i></button>
                </td>
                <td><small><?= $mp['macro_orden'] ?? 0 ?></small></td>
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary" title="Editar" onclick="editarMP(<?= $mp['macro_id'] ?>,'<?= htmlspecialchars(addslashes($mp['macro_nombre'])) ?>','<?= $mp['macro_tipo'] ?>')"><i class="fas fa-edit"></i></button>
                    <form method="POST" action="/procesos/eliminar-macroproceso" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
                        <input type="hidden" name="macro_id" value="<?= $mp['macro_id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- MODALS igual que antes -->
<div class="modal fade" id="modalMacroproceso" tabindex="-1">
    <div class="modal-dialog"><form method="POST" action="/procesos/crear-macroproceso" class="modal-content">
        <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-folder-plus me-2"></i>Macroproceso</div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="text" name="nombre" class="form-control mb-2" placeholder="Nombre *" required>
            <div class="row g-2 mb-2"><div class="col-6"><input type="text" name="codigo" class="form-control" placeholder="Código"></div><div class="col-6"><input type="number" name="orden" class="form-control" value="1"></div></div>
            <select name="tipo" class="form-select mb-2" required>
                <option value="estrategico">Estratégico</option><option value="misional">Misional</option><option value="apoyo">Apoyo</option><option value="evaluacion">Evaluación</option>
            </select>
            <textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción"></textarea>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Crear</button></div>
    </form></div>
</div>

<div class="modal fade" id="modalProceso" tabindex="-1">
    <div class="modal-dialog modal-lg"><form method="POST" action="/procesos/crear-proceso" class="modal-content">
        <input type="hidden" name="macro_id" id="procMacroId">
        <div class="modal-header"><h5 class="modal-title">Nuevo Proceso</div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row g-2"><div class="col-md-8"><input type="text" name="nombre" class="form-control" placeholder="Nombre *" required></div><div class="col-md-4"><input type="text" name="codigo" class="form-control" placeholder="Código"></div></div>
            <input type="text" name="objetivo" class="form-control mt-2" placeholder="Objetivo">
            <textarea name="alcance" class="form-control mt-2" rows="2" placeholder="Alcance"></textarea>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Crear Proceso</button></div>
    </form></div>
</div>

<script>
function nuevoProceso(macroId, tipo) { document.getElementById('procMacroId').value = macroId; new bootstrap.Modal(document.getElementById('modalProceso')).show(); }
function editarMP(id, nombre, tipo) {
    var form = document.querySelector('#modalMacroproceso form');
    form.action = '/procesos/editar-macroproceso';
    form.querySelector('[name="nombre"]').value = nombre;
    form.querySelector('[name="tipo"]').value = tipo;
    var hidden = form.querySelector('[name="id"]');
    if (!hidden) { hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'id'; form.appendChild(hidden); }
    hidden.value = id;
    form.querySelector('.modal-title').innerHTML = '<i class=\"fas fa-edit me-2\"></i>Editar Macroproceso';
    form.querySelector('button[type=\"submit\"]').textContent = 'Guardar';
    new bootstrap.Modal(document.getElementById('modalMacroproceso')).show();
}
document.getElementById('modalMacroproceso').addEventListener('hidden.bs.modal', function() {
    var form = this.querySelector('form');
    form.action = '/procesos/crear-macroproceso';
    form.reset();
    var h = form.querySelector('[name=\"id\"]'); if (h) h.remove();
    form.querySelector('.modal-title').innerHTML = '<i class=\"fas fa-folder-plus me-2\"></i>Macroproceso';
    form.querySelector('button[type=\"submit\"]').textContent = 'Crear';
});
</script>
<style>.hover-bg:hover { background: #f5f6fa; }</style>
