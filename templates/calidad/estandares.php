<?php $created = $_GET['created'] ?? null; $updated = $_GET['updated'] ?? null; ?>
<?php if ($created): ?><div class="alert alert-success">Estándar creado</div><?php endif; ?>
<?php if ($updated): ?><div class="alert alert-info">Estándar actualizado</div><?php endif; ?>

<div class="d-flex justify-content-between mb-3">
    <h5><i class="fas fa-list-check me-2"></i>Gestión de Estándares de Acreditación (<?= count($estandares) ?>)</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEstandar"><i class="fas fa-plus me-1"></i>Nuevo Estándar</button>
</div>

<div class="card-box"><div class="card-box-body p-0">
<table class="table-box">
    <thead><tr><th>Código</th><th>Nombre</th><th>Grupo</th><th>Tipo</th><th>Nivel</th><th>Activo</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($estandares as $e): ?>
    <tr>
        <td><strong><?= htmlspecialchars($e['estandar_codigo']) ?></strong></td>
        <td><?= htmlspecialchars($e['estandar_nombre']) ?></td>
        <td><?= htmlspecialchars($e['estandar_grupo']) ?></td>
        <td><span class="badge bg-light text-dark"><?= $e['estandar_tipo'] ?></span></td>
        <td><?= $e['estandar_nivel'] ?></td>
        <td><?= $e['estandar_activo']?'✅':'❌' ?></td>
        <td>
            <button class="btn btn-sm btn-outline-secondary" onclick="editar(<?= $e['estandar_id'] ?>,'<?= addslashes($e['estandar_codigo']) ?>','<?= addslashes($e['estandar_nombre']) ?>','<?= addslashes($e['estandar_grupo']) ?>','<?= $e['estandar_tipo'] ?>','<?= $e['estandar_nivel'] ?>')"><i class="fas fa-edit"></i></button>
            <form method="POST" action="/calidad/estandares/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
                <input type="hidden" name="estandar_id" value="<?= $e['estandar_id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>

<!-- MODAL -->
<div class="modal fade" id="modalEstandar" tabindex="-1"><div class="modal-dialog">
<form method="POST" action="/calidad/estandares/crear" class="modal-content" id="formEstandar">
    <div class="modal-header"><h5 class="modal-title" id="tituloModal">Nuevo Estándar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2">
            <div class="col-6"><input type="text" name="codigo" class="form-control form-control-sm" placeholder="Código *" id="ecodigo" required></div>
            <div class="col-6"><input type="text" name="grupo" class="form-control form-control-sm" placeholder="Grupo *" id="egrupo" required></div>
        </div>
        <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="Nombre del estándar *" id="enombre" required>
        <textarea name="descripcion" class="form-control form-control-sm mb-2" rows="2" placeholder="Descripción"></textarea>
        <div class="row g-2">
            <div class="col-6">
                <select name="tipo" class="form-select form-select-sm" id="etipo">
                    <option value="SUA">SUA</option><option value="ISO7101">ISO 7101</option><option value="Habilitacion">Habilitación</option><option value="PAMEC">PAMEC</option><option value="SOGC">SOGC</option>
                </select>
            </div>
            <div class="col-6">
                <select name="nivel" class="form-select form-select-sm" id="enivel">
                    <option value="basico">Básico</option><option value="avanzado">Avanzado</option><option value="excelencia">Excelencia</option>
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
</form></div></div>

<script>
function editar(id, codigo, nombre, grupo, tipo, nivel) {
    document.getElementById('formEstandar').action = '/calidad/estandares/editar';
    document.getElementById('tituloModal').textContent = 'Editar Estándar';
    document.getElementById('ecodigo').value = codigo;
    document.getElementById('enombre').value = nombre;
    document.getElementById('egrupo').value = grupo;
    document.getElementById('etipo').value = tipo;
    document.getElementById('enivel').value = nivel;
    let hi = document.querySelector('#formEstandar input[name=estandar_id]');
    if (!hi) { hi = document.createElement('input'); hi.type = 'hidden'; hi.name = 'estandar_id'; document.getElementById('formEstandar').appendChild(hi); }
    hi.value = id;
    new bootstrap.Modal(document.getElementById('modalEstandar')).show();
}
</script>
