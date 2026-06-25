<?php $ok = $_GET['empresa_ok'] ?? null; $asignado = $_GET['asignado'] ?? null; ?>
<?php if ($ok): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Empresa actualizada</div><?php endif; ?>
<?php if ($asignado): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Usuario asignado a empresa</div><?php endif; ?>

<!-- Pestañas de configuración -->
<ul class="nav nav-tabs mb-4" id="configTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabEmpresas"><i class="fas fa-building me-1"></i>Empresas</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSistema"><i class="fas fa-gear me-1"></i>Sistema</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabPersonalizacion"><i class="fas fa-palette me-1"></i>Personalización</a></li>
</ul>

<div class="tab-content">
    <!-- TAB 1: EMPRESAS -->
    <div class="tab-pane fade show active" id="tabEmpresas">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card-box"><div class="card-box-header"><i class="fas fa-plus me-2"></i>Nueva Empresa</div>
                <div class="card-box-body">
                    <form method="POST" action="/admin/config/crear-empresa">
                        <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="Nombre *" required>
                        <div class="row g-2 mb-2">
                            <div class="col-8"><input type="text" name="razon_social" class="form-control form-control-sm" placeholder="Razón social"></div>
                            <div class="col-4"><input type="text" name="nit" class="form-control form-control-sm" placeholder="NIT"></div>
                        </div>
                        <select name="sector_id" class="form-select form-select-sm mb-2">
                            <option value="">Sector económico</option>
                            <?php foreach ($sectores as $s): ?><option value="<?= $s['sector_id'] ?>"><?= htmlspecialchars($s['sector_nombre']) ?></option><?php endforeach; ?>
                        </select>
                        <input type="text" name="direccion" class="form-control form-control-sm mb-2" placeholder="Dirección">
                        <div class="row g-2 mb-2">
                            <div class="col-6"><input type="text" name="telefono" class="form-control form-control-sm" placeholder="Teléfono"></div>
                            <div class="col-6"><input type="email" name="email" class="form-control form-control-sm" placeholder="Email"></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Crear Empresa</button>
                    </form>
                </div></div>
            </div>
            <div class="col-md-8">
                <div class="card-box"><div class="card-box-header">Empresas Registradas (<?= count($empresas) ?>)</div>
                <div class="card-box-body p-0"><table class="table-box">
                    <thead><tr><th>Nombre</th><th>NIT</th><th>Sector</th><th>Dirección</th><th>Planes</th></tr></thead>
                    <tbody>
                    <?php foreach ($empresas as $e): 
                        $planesEmp = array_filter($planes, fn($p)=>$p['plan_empresa_id']==$e['empresa_id']);
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($e['empresa_nombre']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($e['empresa_razon_social']??'') ?></small></td>
                        <td><?= htmlspecialchars($e['empresa_nit']??'-') ?></td>
                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($e['sector_nombre']??'General') ?></span></td>
                        <td><small><?= htmlspecialchars($e['empresa_direccion']??'-') ?></small></td>
                        <td><?= count($planesEmp) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div></div>
            </div>
        </div>
    </div>

    <!-- TAB 2: SISTEMA -->
    <div class="tab-pane fade" id="tabSistema">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card-box"><div class="card-box-header"><i class="fas fa-database me-2"></i>Estadísticas del Sistema</div>
                <div class="card-box-body">
                    <?php 
                    $stats = [
                        'Empresas' => count($empresas),
                        'Planes' => count($planes),
                        'Usuarios' => count($usuarios),
                        'Documentos' => EstrateGiaCore::getInstance()->fetchColumn('SELECT COUNT(*) FROM doc_documentos WHERE documento_activo=1'),
                        'Indicadores' => EstrateGiaCore::getInstance()->fetchColumn('SELECT COUNT(*) FROM ind_indicadores WHERE indicador_activo=1'),
                        'Mediciones' => EstrateGiaCore::getInstance()->fetchColumn('SELECT COUNT(*) FROM ind_mediciones'),
                    ];
                    foreach ($stats as $k => $v): ?>
                    <div class="d-flex justify-content-between mb-2"><strong><?= $k ?></strong><span class="badge bg-light text-dark"><?= $v ?></span></div>
                    <?php endforeach; ?>
                </div></div>
            </div>
            <div class="col-md-6">
                <div class="card-box"><div class="card-box-header"><i class="fas fa-info-circle me-2"></i>Versión</div>
                <div class="card-box-body">
                    <p><strong>EstrateGIA v1.0</strong></p>
                    <p class="text-muted small">Sistema de Gestión de Planeación Estratégica con IA</p>
                    <p class="small">Motor: PHP <?= phpversion() ?> · BD: MySQL/MariaDB</p>
                    <p class="small"><?= date('d/m/Y H:i') ?></p>
                </div></div>
            </div>
        </div>
    </div>

    <!-- TAB 3: PERSONALIZACIÓN -->
    <div class="tab-pane fade" id="tabPersonalizacion">
        <div class="card-box"><div class="card-box-header"><i class="fas fa-palette me-2"></i>Personalización por Empresa</div>
        <div class="card-box-body">
            <p class="small text-muted mb-3">Seleccione la empresa y configure sus parámetros visuales y funcionales.</p>
            <form method="POST" action="/admin/config/guardar-personalizacion">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small">Empresa</label>
                        <select name="empresa_id" class="form-select form-select-sm">
                            <?php foreach ($empresas as $e): ?>
                            <option value="<?= $e['empresa_id'] ?>"><?= htmlspecialchars($e['empresa_nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Tipo de Empresa</label>
                        <select name="empresa_tipo" class="form-select form-select-sm">
                            <option value="general">General</option><option value="salud">Salud</option><option value="industrial">Industrial</option><option value="servicios">Servicios</option><option value="educacion">Educación</option><option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Color Primario</label>
                        <input type="color" name="color_primario" class="form-control form-control-sm" value="#1a73e8" style="height:38px">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Nombre Corto</label>
                        <input type="text" name="nombre_corto" class="form-control form-control-sm" placeholder="Ej: Hospital Central">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Formato de Fecha</label>
                        <select name="formato_fecha" class="form-select form-select-sm">
                            <option value="d/m/Y">d/m/Y (31/12/2026)</option>
                            <option value="m/d/Y">m/d/Y (12/31/2026)</option>
                            <option value="Y-m-d">Y-m-d (2026-12-31)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Moneda Principal</label>
                        <select name="moneda" class="form-select form-select-sm">
                            <option value="COP">COP - Peso Colombiano</option><option value="USD">USD - Dólar</option><option value="EUR">EUR - Euro</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">URL del Logo</label>
                        <input type="url" name="logo_url" class="form-control form-control-sm" placeholder="https://empresa.com/logo.png">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save me-1"></i>Guardar Personalización</button>
            </form>
        </div></div>
    </div>
</div>
