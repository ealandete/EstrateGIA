<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-trello me-2" style="color:#0079bf"></i>Tablero Kanban</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalActividad"><i class="fas fa-plus me-1"></i>Nueva Actividad</button>
</div>

<div class="kanban-board d-flex gap-3" style="overflow-x:auto;min-height:60vh">
    <?php
    $columnas = [
        ['id'=>'pendiente', 'titulo'=>'Pendiente', 'color'=>'#6b7280', 'icono'=>'fa-circle'],
        ['id'=>'en_progreso', 'titulo'=>'En Progreso', 'color'=>'#3b82f6', 'icono'=>'fa-spinner'],
        ['id'=>'completada', 'titulo'=>'Completada', 'color'=>'#16a34a', 'icono'=>'fa-check-circle'],
        ['id'=>'bloqueada', 'titulo'=>'Bloqueada', 'color'=>'#dc2626', 'icono'=>'fa-lock'],
    ];
    foreach ($columnas as $col):
        $items = array_filter($actividades ?? [], fn($a) => ($a['estado'] ?? 'pendiente') === $col['id']);
    ?>
    <div class="kanban-col" style="min-width:280px;max-width:320px;flex:1"
         ondragover="event.preventDefault()"
         ondrop="moverActividad(event, '<?= $col['id'] ?>')">
        <div class="d-flex justify-content-between align-items-center p-2 mb-1" style="border-bottom:3px solid <?= $col['color'] ?>">
            <strong><i class="fas <?= $col['icono'] ?>" style="color:<?= $col['color'] ?>"></i> <?= $col['titulo'] ?></strong>
            <span class="badge bg-light text-dark"><?= count($items) ?></span>
        </div>
        <div class="kanban-items" style="min-height:60px">
            <?php foreach ($items as $item): ?>
            <div class="kanban-card mb-2" draggable="true" ondragstart="arrastrar(event)" id="act-<?= $item['id'] ?? uniqid() ?>"
                 data-id="<?= $item['id'] ?? '' ?>" data-estado="<?= $col['id'] ?>"
                 style="cursor:grab">
                <div class="d-flex justify-content-between">
                    <strong style="font-size:0.8rem"><?= htmlspecialchars($item['titulo'] ?? $item['nombre'] ?? $item['descripcion'] ?? 'Sin título') ?></strong>
                    <small class="text-muted"><?= htmlspecialchars($item['responsable'] ?? '') ?></small>
                </div>
                <?php if (!empty($item['fecha_limite'])): ?>
                <small class="text-<?= strtotime($item['fecha_limite']) < time() ? 'danger' : 'muted' ?>"><i class="fas fa-calendar me-1"></i><?= date('d/m', strtotime($item['fecha_limite'])) ?></small>
                <?php endif; ?>
                <?php if (!empty($item['descripcion'])): ?>
                <div class="small text-muted mt-1"><?= htmlspecialchars(substr($item['descripcion'], 0, 60)) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal nueva actividad -->
<div class="modal fade" id="modalActividad">
    <div class="modal-dialog">
        <form method="POST" action="/tools/kanban/guardar" class="modal-content" id="formKanban">
            <input type="hidden" name="plan_id" value="<?= $planId ?? '' ?>">
            <div class="modal-header"><h5>Nueva Actividad</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label small">Título</label><input name="titulo" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label small">Descripción</label><textarea name="descripcion" class="form-control form-control-sm" rows="2"></textarea></div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6"><label class="form-label small">Estado</label>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="pendiente">Pendiente</option><option value="en_progreso">En Progreso</option><option value="completada">Completada</option><option value="bloqueada">Bloqueada</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label small">Responsable</label><input name="responsable" class="form-control form-control-sm"></div>
                </div>
                <div class="mb-2"><label class="form-label small">Fecha límite</label><input type="date" name="fecha_limite" class="form-control form-control-sm"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
        </form>
    </div>
</div>

<style>
.kanban-board { padding: 12px 0; }
.kanban-col { background: #f1f5f9; border-radius: 10px; padding: 10px; }
.kanban-card { background: #fff; border-radius: 8px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); transition: transform 0.15s, box-shadow 0.15s; }
.kanban-card:hover { box-shadow: 0 3px 8px rgba(0,0,0,0.14); transform: translateY(-1px); }
.kanban-card.dragging { opacity: 0.5; transform: rotate(2deg); }
.kanban-col.drag-over { background: #e2e8f0; }
[data-bs-theme="dark"] .kanban-col { background: #1e293b; }
[data-bs-theme="dark"] .kanban-card { background: #334155; }
</style>

<script>
var itemArrastrado = null;
function arrastrar(e) {
    itemArrastrado = e.target.closest('.kanban-card');
    itemArrastrado.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}
async function moverActividad(e, nuevoEstado) {
    e.preventDefault();
    var col = e.target.closest('.kanban-col');
    if (col) col.classList.remove('drag-over');
    if (!itemArrastrado) return;
    itemArrastrado.classList.remove('dragging');
    var id = itemArrastrado.dataset.id;
    var itemsContainer = col.querySelector('.kanban-items');
    if (itemsContainer) itemsContainer.appendChild(itemArrastrado);
    itemArrastrado.dataset.estado = nuevoEstado;
    if (id) {
        var fd = new FormData();
        fd.append('id', id);
        fd.append('estado', nuevoEstado);
        await fetch('/tools/kanban/mover', {method:'POST', body:fd});
    }
}
document.querySelectorAll('.kanban-col').forEach(function(col) {
    col.addEventListener('dragover', function(e) { e.preventDefault(); col.classList.add('drag-over'); });
    col.addEventListener('dragleave', function() { col.classList.remove('drag-over'); });
});
</script>
