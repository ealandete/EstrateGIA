<?php $created = $_GET['created'] ?? null; $error = $_GET['error'] ?? null; $asignado = $_GET['asignado'] ?? null; ?>
<?php if ($created): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Usuario creado</div><?php endif; ?>
<?php if ($asignado): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Usuario asignado a empresa</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card-box"><div class="card-box-header"><i class="fas fa-user-plus me-2"></i>Nuevo Usuario</div>
        <div class="card-box-body">
            <form method="POST" action="/admin/usuarios/crear">
                <div class="mb-2"><input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre" required></div>
                <div class="mb-2"><input type="text" name="apellido" class="form-control form-control-sm" placeholder="Apellido"></div>
                <div class="mb-2"><input type="email" name="email" class="form-control form-control-sm" placeholder="Email" required></div>
                <div class="mb-2"><input type="password" name="password" class="form-control form-control-sm" placeholder="Contraseña" required></div>
                <div class="mb-2"><input type="text" name="cargo" class="form-control form-control-sm" placeholder="Cargo"></div>
                <div class="mb-2"><input type="text" name="departamento" class="form-control form-control-sm" placeholder="Departamento"></div>
                <select name="rol_id" class="form-select form-select-sm mb-2" required><option value="">Rol *</option><?php foreach ($roles as $r): ?><option value="<?= $r['rol_id'] ?>"><?= htmlspecialchars($r['rol_nombre']) ?></option><?php endforeach; ?></select>
                <button type="submit" class="btn btn-primary btn-sm w-100">Crear Usuario</button>
            </form>
        </div></div>

        <!-- Asignar a Empresa -->
        <?php 
        $core = EstrateGiaCore::getInstance();
        $empresas = (new PlanManager())->getEmpresas();
        $asignaciones = $core->fetchAll("SELECT ue.*, u.usuario_nombre, e.empresa_nombre FROM sys_usuario_empresa ue JOIN sys_usuarios u ON ue.ue_usuario_id=u.usuario_id JOIN plan_empresas e ON ue.ue_empresa_id=e.empresa_id");
        ?>
        <div class="card-box mt-3"><div class="card-box-header"><i class="fas fa-link me-2"></i>Asignar a Empresa</div>
        <div class="card-box-body">
            <form method="POST" action="/admin/config/asignar-usuario">
                <select name="usuario_id" class="form-select form-select-sm mb-2" required><option value="">Usuario *</option><?php foreach ($usuarios as $u): ?><option value="<?= $u['usuario_id'] ?>"><?= htmlspecialchars($u['usuario_nombre'].' '.$u['usuario_apellido']) ?></option><?php endforeach; ?></select>
                <select name="empresa_id" class="form-select form-select-sm mb-2" required><option value="">Empresa *</option><?php foreach ($empresas as $e): ?><option value="<?= $e['empresa_id'] ?>"><?= htmlspecialchars($e['empresa_nombre']) ?></option><?php endforeach; ?></select>
                <select name="rol_empresa" class="form-select form-select-sm mb-2"><option value="admin">Admin</option><option value="gerente">Gerente</option><option value="coordinador">Coordinador</option><option value="analista">Analista</option><option value="colaborador">Colaborador</option><option value="consultor">Consultor</option></select>
                <button type="submit" class="btn btn-primary btn-sm w-100">Asignar</button>
            </form>
        </div></div>
    </div>

    <div class="col-md-8">
        <div class="card-box"><div class="card-box-header"><i class="fas fa-users me-2"></i>Usuarios (<?= count($usuarios) ?>)</div>
        <div class="card-box-body p-0"><table class="table-box">
            <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Empresas</th><th>Último Acceso</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><strong><?= htmlspecialchars($u['usuario_nombre'].' '.($u['usuario_apellido']??'')) ?></strong></td>
                <td><small><?= htmlspecialchars($u['usuario_email']) ?></small></td>
                <td><span class="badge bg-light text-dark"><?= htmlspecialchars($u['rol_nombre']) ?></span></td>
                <td><small><?= htmlspecialchars($u['empresas_asignadas']??'Sin asignar') ?></small></td>
                <td><small><?= $u['usuario_ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['usuario_ultimo_acceso'])) : 'Nunca' ?></small></td>
                <td><span class="badge bg-<?= $u['usuario_activo']?'success':'danger' ?>"><?= $u['usuario_activo']?'Activo':'Inactivo' ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div></div>

        <!-- Asignaciones existentes -->
        <?php if (!empty($asignaciones)): ?>
        <div class="card-box mt-3"><div class="card-box-header">Asignaciones Actuales</div>
        <div class="card-box-body p-0"><table class="table-box small">
            <thead><tr><th>Usuario</th><th>Empresa</th><th>Rol</th></tr></thead>
            <tbody><?php foreach ($asignaciones as $a): ?><tr><td><?= htmlspecialchars($a['usuario_nombre']) ?></td><td><?= htmlspecialchars($a['empresa_nombre']) ?></td><td><?= $a['ue_rol_empresa'] ?></td></tr><?php endforeach; ?></tbody>
        </table></div></div>
        <?php endif; ?>
    </div>
</div>
