<?php $saved = $_GET['saved'] ?? null; ?>
<?php if ($saved): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Permisos guardados</div><?php endif; ?>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card-box"><div class="card-box-header">Seleccionar Rol</div>
        <div class="card-box-body p-0">
            <?php foreach ($roles as $rol): ?>
            <a href="?rol=<?= $rol['rol_id'] ?>" class="d-block p-2 px-3 border-bottom text-decoration-none <?= ($_GET['rol']??1)==$rol['rol_id']?'bg-primary bg-opacity-10 fw-bold':'text-dark' ?>">
                <i class="fas fa-user-tag me-2"></i><?= htmlspecialchars($rol['rol_nombre']) ?>
            </a>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="col-md-9">
        <?php $rolSel = (int)($_GET['rol'] ?? 1); ?>
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-shield-halved me-2"></i>Permisos: <?= htmlspecialchars($roles[array_search($rolSel, array_column($roles, 'rol_id'))]['rol_nombre'] ?? '') ?></div>
            <div class="card-box-body">
                <form method="POST" action="/admin/roles/guardar">
                    <input type="hidden" name="rol_id" value="<?= $rolSel ?>">
                    <p class="small text-muted mb-3">Marca los permisos que este rol puede realizar en cada módulo.</p>
                    <div class="row g-3">
                        <?php 
                        $acciones = ['ver'=>'👁 Ver','crear'=>'➕ Crear','editar'=>'✏️ Editar','eliminar'=>'🗑 Eliminar','exportar'=>'📥 Exportar','imprimir'=>'🖨 Imprimir','copiar'=>'📋 Copiar','aprobar'=>'✅ Aprobar'];
                        foreach ($modulos as $mod): 
                            $permsModulo = $permisosPorRol[$rolSel][$mod['modulo_nombre']] ?? [];
                        ?>
                        <div class="col-md-6">
                            <div class="p-3 border rounded">
                                <strong class="d-block mb-2"><?= htmlspecialchars($mod['modulo_nombre']) ?></strong>
                                <select name="alcance_<?= $mod['modulo_id'] ?>" class="form-select form-select-sm mb-2" style="font-size:0.7rem">
                                    <option value="propio">Alcance: Propio</option>
                                    <option value="empresa">Alcance: Empresa</option>
                                    <option value="global">Alcance: Global</option>
                                </select>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($acciones as $ak => $av):
                                        $todosPermisos = EstrateGiaCore::getInstance()->fetchAll('SELECT permiso_id FROM sys_permisos WHERE permiso_modulo_id=:mid AND permiso_accion=:acc', ['mid'=>$mod['modulo_id'],'acc'=>$ak]);
                                        $pid = $todosPermisos[0]['permiso_id'] ?? null;
                                        $checked = $pid && in_array($ak, $permsModulo);
                                        if (!$pid) continue;
                                    ?>
                                    <label class="btn btn-sm <?= $checked?'btn-primary':'btn-outline-secondary' ?>" style="font-size:0.75rem">
                                        <input type="checkbox" name="permisos[]" value="<?= $pid ?>" <?= $checked?'checked':'' ?> class="d-none" onchange="this.parentElement.classList.toggle('btn-primary',this.checked);this.parentElement.classList.toggle('btn-outline-secondary',!this.checked)">
                                        <?= $av ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save me-1"></i>Guardar Permisos</button>
                </form>
            </div>
        </div>
    </div>
</div>
