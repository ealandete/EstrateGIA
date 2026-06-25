<?php $ok = $_GET['empresa_ok'] ?? null; $asignado = $_GET['asignado'] ?? null; $codifOk = $_GET['codif_ok'] ?? null; $cfgOk = $_GET['cfg_ok'] ?? null; ?>
<?php if ($ok): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Empresa actualizada</div><?php endif; ?>
<?php if ($asignado): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Usuario asignado a empresa</div><?php endif; ?>
<?php if ($codifOk): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Configuración de codificación guardada</div><?php endif; ?>
<?php if ($cfgOk): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Configuración de empresa guardada</div><?php endif; ?>

<!-- Pestañas de configuración -->
<ul class="nav nav-tabs mb-4" id="configTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabEmpresas"><i class="fas fa-building me-1"></i>Empresas</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabConfigEmpresa" id="tabConfigEmpresaLink"><i class="fas fa-sliders-h me-1"></i>Configuración Empresa</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSistema"><i class="fas fa-gear me-1"></i>Sistema</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabPersonalizacion"><i class="fas fa-palette me-1"></i>Personalización</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabCodificacion" id="tabCodificacionLink"><i class="fas fa-tag me-1"></i>Codificación Documental</a></li>
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
                    <thead><tr><th>Nombre</th><th>NIT</th><th>Sector</th><th>Dirección</th><th>Planes</th><th></th></tr></thead>
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
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" title="Editar" onclick="editarEmpresa(<?= $e['empresa_id'] ?>,'<?= htmlspecialchars(addslashes($e['empresa_nombre'])) ?>','<?= htmlspecialchars(addslashes($e['empresa_razon_social']??'')) ?>','<?= htmlspecialchars(addslashes($e['empresa_nit']??'')) ?>',<?= $e['empresa_sector_id']??'null' ?>,'<?= htmlspecialchars(addslashes($e['empresa_direccion']??'')) ?>','<?= htmlspecialchars(addslashes($e['empresa_telefono']??'')) ?>','<?= htmlspecialchars(addslashes($e['empresa_email']??'')) ?>')"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div></div>
            </div>
        </div>
    </div>

    <!-- TAB 2: CONFIGURACIÓN EMPRESA -->
    <div class="tab-pane fade" id="tabConfigEmpresa">
        <div class="card-box"><div class="card-box-header"><i class="fas fa-sliders-h me-2"></i>Parametrización por Empresa</div>
        <div class="card-box-body">
            <p class="small text-muted mb-3">Configure los parámetros globales que se aplican a cada empresa: colores, logo, formatos, moneda y códigos.</p>
            <div class="mb-3">
                <label class="form-label small">Seleccionar Empresa</label>
                <select id="cfgEmpresaSel" class="form-select form-select-sm" style="width:300px" onchange="cargarConfigEmpresa(this.value)">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($empresas as $e): ?>
                    <option value="<?= $e['empresa_id'] ?>"><?= htmlspecialchars($e['empresa_nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <form id="formConfigEmpresa" method="POST" action="/admin/config/empresa/guardar" enctype="multipart/form-data">
                <input type="hidden" name="empresa_id" id="cfgEmpresaId">
                <div class="row g-3">
                    <!-- Identidad -->
                    <div class="col-12"><h6 class="text-muted small fw-bold mb-2">Identidad Corporativa</h6></div>
                    <div class="col-md-4">
                        <label class="form-label small">Nombre Corto</label>
                        <input type="text" name="empresa_nombre_corto" id="cfgNombreCorto" class="form-control form-control-sm" placeholder="Ej: Hospital Central">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Logo (URL o subir)</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="empresa_logo_url" id="cfgLogoUrl" class="form-control form-control-sm" placeholder="URL del logo">
                            <input type="file" name="logo_upload" class="form-control form-control-sm" accept="image/*" style="max-width:150px">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Color Primario</label>
                        <input type="color" name="empresa_color_primario" id="cfgColorPrimario" class="form-control form-control-sm" value="#1a73e8" style="height:32px">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Color Secundario</label>
                        <input type="color" name="empresa_color_secundario" id="cfgColorSecundario" class="form-control form-control-sm" value="#1557b0" style="height:32px">
                    </div>

                    <!-- Apariencia / Localización -->
                    <div class="col-12 mt-3"><h6 class="text-muted small fw-bold mb-2">Apariencia y Localización</h6></div>
                    <div class="col-md-3">
                        <label class="form-label small">Modo Oscuro por Defecto</label>
                        <select name="empresa_modo_oscuro_default" id="cfgModoOscuro" class="form-select form-select-sm">
                            <option value="0">Claro</option>
                            <option value="1">Oscuro</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Idioma por Defecto</label>
                        <select name="empresa_idioma_default" id="cfgIdioma" class="form-select form-select-sm">
                            <option value="es">Español</option>
                            <option value="en">English</option>
                            <option value="pt">Português</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Zona Horaria</label>
                        <select name="empresa_timezone" id="cfgTimezone" class="form-select form-select-sm">
                            <?php foreach (timezone_identifiers_list() as $tz): ?>
                            <option value="<?= $tz ?>" <?= $tz === 'America/Bogota' ? 'selected' : '' ?>><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Formato de Fecha</label>
                        <select name="empresa_formato_fecha" id="cfgFormatoFecha" class="form-select form-select-sm">
                            <option value="d/m/Y">d/m/Y (31/12/2026)</option>
                            <option value="m/d/Y">m/d/Y (12/31/2026)</option>
                            <option value="Y-m-d">Y-m-d (2026-12-31)</option>
                            <option value="d-m-Y">d-m-Y (31-12-2026)</option>
                            <option value="d.m.Y">d.m.Y (31.12.2026)</option>
                            <option value="d/M/Y">d/M/Y (31/Dic/2026)</option>
                        </select>
                    </div>

                    <!-- Moneda -->
                    <div class="col-12 mt-3"><h6 class="text-muted small fw-bold mb-2">Moneda</h6></div>
                    <div class="col-md-6">
                        <label class="form-label small">Moneda Principal</label>
                        <select name="empresa_moneda" id="cfgMoneda" class="form-select form-select-sm">
                            <option value="COP">COP - Peso Colombiano</option>
                            <option value="USD">USD - Dólar estadounidense</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="MXN">MXN - Peso Mexicano</option>
                            <option value="PEN">PEN - Sol Peruano</option>
                            <option value="CLP">CLP - Peso Chileno</option>
                            <option value="ARS">ARS - Peso Argentino</option>
                            <option value="BRL">BRL - Real Brasileño</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Símbolo de Moneda</label>
                        <input type="text" name="empresa_moneda_simbolo" id="cfgMonedaSimbolo" class="form-control form-control-sm" placeholder="$" maxlength="5">
                    </div>

                    <!-- Códigos Documentales -->
                    <div class="col-12 mt-3"><h6 class="text-muted small fw-bold mb-2">Formatos de Código</h6></div>
                    <div class="col-md-4">
                        <label class="form-label small">Prefijo Documentos</label>
                        <input type="text" name="empresa_documento_codigo_prefijo" id="cfgDocPrefijo" class="form-control form-control-sm" placeholder="DOC">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Formato Doc. ISO</label>
                        <input type="text" name="empresa_documento_codigo_formato" id="cfgDocFormato" class="form-control form-control-sm" placeholder="{PREFIJO}-{TIPO}-{CONSECUTIVO}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Formato Procesos</label>
                        <input type="text" name="empresa_proceso_codigo_formato" id="cfgProcFormato" class="form-control form-control-sm" placeholder="{PREFIJO}-{TIPO}-{CONSECUTIVO}">
                    </div>
                    <div class="col-md-4 mt-0">
                        <label class="form-label small">Formato Indicadores</label>
                        <input type="text" name="empresa_indicador_codigo_formato" id="cfgIndFormato" class="form-control form-control-sm" placeholder="IND-{CONSECUTIVO}">
                    </div>
                    <div class="col-md-8 mt-0 d-flex align-items-end">
                        <small class="text-muted">Variables: <code>{PREFIJO}</code> <code>{TIPO}</code> <code>{CONSECUTIVO}</code> <code>{SEPARADOR}</code></small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save me-1"></i>Guardar Configuración</button>
            </form>
        </div></div>
    </div>

    <!-- TAB 3: SISTEMA -->
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

    <!-- TAB 4: PERSONALIZACIÓN -->
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

    <!-- TAB 5: CODIFICACIÓN DOCUMENTAL -->
    <div class="tab-pane fade" id="tabCodificacion">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-tag me-2"></i>Configuración de Codificación Documental</div>
            <div class="card-box-body">
                <p class="small text-muted mb-3">Configure el esquema de codificación para documentos, procesos e indicadores de cada empresa. El código se genera automáticamente al crear nuevos registros.</p>
                <form method="POST" action="/admin/config/codificacion-documental">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small">Empresa</label>
                            <select name="empresa_id" class="form-select form-select-sm" id="codifEmpresaSel">
                                <?php foreach ($empresas as $e): ?>
                                <option value="<?= $e['empresa_id'] ?>"><?= htmlspecialchars($e['empresa_nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Módulo</label>
                            <select name="modulo" class="form-select form-select-sm">
                                <option value="documentos">Documentos</option>
                                <option value="procesos">Procesos</option>
                                <option value="indicadores">Indicadores</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Prefijo</label>
                            <input type="text" name="prefijo" class="form-control form-control-sm" placeholder="Ej: DOC, PROC, IND" value="DOC">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Formato del Código</label>
                            <input type="text" name="formato" class="form-control form-control-sm" placeholder="{prefijo}-{tipo}-{consecutivo}" value="{prefijo}-{tipo}-{consecutivo}">
                            <small class="text-muted">Variables: <code>{prefijo}</code> <code>{tipo}</code> <code>{consecutivo}</code> <code>{separador}</code></small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Separador</label>
                            <input type="text" name="separador" class="form-control form-control-sm" maxlength="5" value="-">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Consecutivo Actual</label>
                            <input type="number" name="consecutivo_actual" class="form-control form-control-sm" min="0" value="0">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save me-1"></i>Guardar Codificación</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Empresa -->
<div class="modal fade" id="modalEditarEmpresa" tabindex="-1">
    <div class="modal-dialog"><form method="POST" action="/admin/config/editar-empresa" class="modal-content">
        <input type="hidden" name="empresa_id" id="editEmpId">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Empresa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="text" name="nombre" id="editEmpNombre" class="form-control form-control-sm mb-2" placeholder="Nombre *" required>
            <div class="row g-2 mb-2">
                <div class="col-8"><input type="text" name="razon_social" id="editEmpRazon" class="form-control form-control-sm" placeholder="Razón social"></div>
                <div class="col-4"><input type="text" name="nit" id="editEmpNit" class="form-control form-control-sm" placeholder="NIT"></div>
            </div>
            <select name="sector_id" id="editEmpSector" class="form-select form-select-sm mb-2">
                <option value="">Sector económico</option>
                <?php foreach ($sectores as $s): ?><option value="<?= $s['sector_id'] ?>"><?= htmlspecialchars($s['sector_nombre']) ?></option><?php endforeach; ?>
            </select>
            <input type="text" name="direccion" id="editEmpDir" class="form-control form-control-sm mb-2" placeholder="Dirección">
            <div class="row g-2 mb-2">
                <div class="col-6"><input type="text" name="telefono" id="editEmpTel" class="form-control form-control-sm" placeholder="Teléfono"></div>
                <div class="col-6"><input type="email" name="email" id="editEmpEmail" class="form-control form-control-sm" placeholder="Email"></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar Cambios</button></div>
    </form></div>
</div>

<script>
function editarEmpresa(id, nombre, razon, nit, sector, dir, tel, email) {
    document.getElementById('editEmpId').value = id;
    document.getElementById('editEmpNombre').value = nombre;
    document.getElementById('editEmpRazon').value = razon;
    document.getElementById('editEmpNit').value = nit;
    document.getElementById('editEmpSector').value = sector || '';
    document.getElementById('editEmpDir').value = dir;
    document.getElementById('editEmpTel').value = tel;
    document.getElementById('editEmpEmail').value = email;
    new bootstrap.Modal(document.getElementById('modalEditarEmpresa')).show();
}

var empresaConfigsData = <?= json_encode($empresaConfigs ?? []) ?>;

function cargarConfigEmpresa(empresaId) {
    if (!empresaId) return;
    document.getElementById('cfgEmpresaId').value = empresaId;
    var cfg = empresaConfigsData[empresaId];
    if (!cfg) return;
    document.getElementById('cfgNombreCorto').value = (cfg.empresa_nombre_corto && cfg.empresa_nombre_corto.valor) || '';
    document.getElementById('cfgLogoUrl').value = (cfg.empresa_logo_url && cfg.empresa_logo_url.valor) || '';
    document.getElementById('cfgColorPrimario').value = (cfg.empresa_color_primario && cfg.empresa_color_primario.valor) || '#1a73e8';
    document.getElementById('cfgColorSecundario').value = (cfg.empresa_color_secundario && cfg.empresa_color_secundario.valor) || '#1557b0';
    document.getElementById('cfgModoOscuro').value = (cfg.empresa_modo_oscuro_default && cfg.empresa_modo_oscuro_default.valor) || '0';
    document.getElementById('cfgIdioma').value = (cfg.empresa_idioma_default && cfg.empresa_idioma_default.valor) || 'es';
    document.getElementById('cfgTimezone').value = (cfg.empresa_timezone && cfg.empresa_timezone.valor) || 'America/Bogota';
    document.getElementById('cfgFormatoFecha').value = (cfg.empresa_formato_fecha && cfg.empresa_formato_fecha.valor) || 'd/m/Y';
    document.getElementById('cfgMoneda').value = (cfg.empresa_moneda && cfg.empresa_moneda.valor) || 'COP';
    document.getElementById('cfgMonedaSimbolo').value = (cfg.empresa_moneda_simbolo && cfg.empresa_moneda_simbolo.valor) || '$';
    document.getElementById('cfgDocPrefijo').value = (cfg.empresa_documento_codigo_prefijo && cfg.empresa_documento_codigo_prefijo.valor) || '';
    document.getElementById('cfgDocFormato').value = (cfg.empresa_documento_codigo_formato && cfg.empresa_documento_codigo_formato.valor) || '{PREFIJO}-{TIPO}-{CONSECUTIVO}';
    document.getElementById('cfgProcFormato').value = (cfg.empresa_proceso_codigo_formato && cfg.empresa_proceso_codigo_formato.valor) || '{PREFIJO}-{TIPO}-{CONSECUTIVO}';
    document.getElementById('cfgIndFormato').value = (cfg.empresa_indicador_codigo_formato && cfg.empresa_indicador_codigo_formato.valor) || 'IND-{CONSECUTIVO}';
}

// Activar tab de configuración empresa si venimos de guardar
(function() {
    if (window.location.hash === '#tabConfigEmpresa') {
        var tabEl = document.getElementById('tabConfigEmpresaLink');
        if (tabEl) { var tab = new bootstrap.Tab(tabEl); tab.show(); }
    }
})();

// Activar tab de codificación si venimos de guardar
(function() {
    if (window.location.hash === '#tabCodificacion') {
        var tabEl = document.getElementById('tabCodificacionLink');
        if (tabEl) { var tab = new bootstrap.Tab(tabEl); tab.show(); }
    }
})();
</script>
