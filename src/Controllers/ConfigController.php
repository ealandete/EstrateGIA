<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class ConfigController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $pm = new PlanManager();
        $empresas = $pm->getEmpresas();
        $sectores = (new DocManager())->getSectores();
        $planes = $pm->getPlanes();
        $roles = $this->safeAll('SELECT * FROM sys_roles WHERE rol_activo=1');
        $usuarios = $this->safeAll("SELECT u.*, r.rol_nombre FROM sys_usuarios u JOIN sys_roles r ON u.usuario_rol_id=r.rol_id ORDER BY u.usuario_nombre");

        // Asignaciones usuario-empresa
        $asignaciones = $this->safeAll("SELECT ue.*, u.usuario_nombre, e.empresa_nombre FROM sys_usuario_empresa ue JOIN sys_usuarios u ON ue.ue_usuario_id=u.usuario_id JOIN plan_empresas e ON ue.ue_empresa_id=e.empresa_id");

        // Cargar configuraciones por empresa para la nueva pestaña
        $empresaConfigs = [];
        foreach ($empresas as $e) {
            $empresaConfigs[$e['empresa_id']] = $this->core->getEmpresaConfig((int)$e['empresa_id']);
        }
        $configClaves = array_keys(EstrateGiaCore::EMPRESA_CONFIG_DEFAULTS);

        $pageTitle = 'Configuración';
        ob_start(); require BASE_PATH . '/templates/admin/config.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crearEmpresa(): void {
        $pm = new PlanManager();
        $id = $pm->createEmpresa([
            'empresa_nombre' => $_POST['nombre'],
            'empresa_razon_social' => $_POST['razon_social'] ?? '',
            'empresa_nit' => $_POST['nit'] ?? '',
            'empresa_sector_id' => $_POST['sector_id'] ?? null,
            'empresa_direccion' => $_POST['direccion'] ?? '',
            'empresa_telefono' => $_POST['telefono'] ?? '',
            'empresa_email' => $_POST['email'] ?? '',
            'usuario_id' => Auth::userId(),
        ]);
        $this->core->audit('crear', 'plan_empresas', $id, null,
            ['empresa_nombre' => $_POST['nombre'], 'empresa_nit' => $_POST['nit'] ?? ''],
            'Empresa creada');
        header('Location: /admin/config?empresa_ok=1'); exit;
    }

    public function editarEmpresa(): void {
        $pm = new PlanManager();
        $empId = (int)$_POST['empresa_id'];
        $anterior = $pm->getEmpresa($empId);
        $pm->updateEmpresa($empId, [
            'empresa_nombre' => $_POST['nombre'],
            'empresa_razon_social' => $_POST['razon_social'] ?? '',
            'empresa_nit' => $_POST['nit'] ?? '',
            'empresa_sector_id' => $_POST['sector_id'] ?? null,
            'empresa_direccion' => $_POST['direccion'] ?? '',
            'empresa_telefono' => $_POST['telefono'] ?? '',
            'empresa_email' => $_POST['email'] ?? '',
        ]);
        $this->core->audit('editar', 'plan_empresas', $empId,
            $anterior ? ['empresa_nombre' => $anterior['empresa_nombre'], 'empresa_nit' => $anterior['empresa_nit']] : null,
            ['empresa_nombre' => $_POST['nombre'], 'empresa_nit' => $_POST['nit'] ?? ''],
            'Empresa editada');
        header('Location: /admin/config?empresa_ok=1'); exit;
    }

    public function asignarUsuarioEmpresa(): void {
        $this->safeExec('INSERT IGNORE INTO sys_usuario_empresa VALUES (?, ?, ?)', [
            (int)$_POST['usuario_id'],
            (int)$_POST['empresa_id'],
            $_POST['rol_empresa'] ?? 'colaborador',
        ]);
        header('Location: /admin/config?asignado=1');
        exit;
    }

    public function guardarPersonalizacion(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $this->safeExec("UPDATE plan_empresas SET empresa_tipo=? WHERE empresa_id=?", [$_POST['empresa_tipo']??'general', $empresaId]);
        $configs = [
            'empresa_color_primario' => $_POST['color_primario'] ?? '#1a73e8',
            'empresa_nombre_corto' => $_POST['nombre_corto'] ?? '',
            'empresa_formato_fecha' => $_POST['formato_fecha'] ?? 'd/m/Y',
            'empresa_moneda' => $_POST['moneda'] ?? 'COP',
            'empresa_logo_url' => $_POST['logo_url'] ?? '',
        ];
        foreach ($configs as $k => $v) {
            $this->safeExec("INSERT INTO sys_configuraciones (config_clave, config_valor, config_descripcion) VALUES (?,?,'') ON DUPLICATE KEY UPDATE config_valor=?", [$k, $v, $v]);
        }
        $this->core->audit('personalizar', 'plan_empresas', $empresaId, null, $configs, 'Personalización guardada');
        header('Location: /admin/config?ok=1');
        exit;
    }

    public function guardarCodificacionDocumental(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $modulo = $_POST['modulo'] ?? 'documentos';
        (new DocManager())->guardarConfiguracionCodificacion($empresaId, [
            'codif_modulo'            => $modulo,
            'codif_prefijo'           => $_POST['prefijo'] ?? '',
            'codif_formato'           => $_POST['formato'] ?? '{prefijo}-{tipo}-{consecutivo}',
            'codif_separador'         => $_POST['separador'] ?? '-',
            'codif_consecutivo_actual'=> (int)($_POST['consecutivo_actual'] ?? 0),
        ]);
        $this->core->audit('configurar_codificacion', 'conf_codificacion', null, null,
            ['empresa_id' => $empresaId, 'modulo' => $modulo],
            'Configuración de codificación documental');
        header('Location: /admin/config?codif_ok=1#tabCodificacion');
        exit;
    }

    public function apiEmpresaConfig(): void {
        $empresaId = $this->core->getEmpresaActiva();
        $config = $this->core->getEmpresaConfig($empresaId);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $config]);
        exit;
    }

    public function guardarConfigEmpresa(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $configs = [];
        $campos = [
            'empresa_nombre_corto',
            'empresa_logo_url',
            'empresa_color_primario',
            'empresa_color_secundario',
            'empresa_modo_oscuro_default',
            'empresa_idioma_default',
            'empresa_timezone',
            'empresa_formato_fecha',
            'empresa_moneda',
            'empresa_moneda_simbolo',
            'empresa_documento_codigo_prefijo',
            'empresa_documento_codigo_formato',
            'empresa_proceso_codigo_formato',
            'empresa_indicador_codigo_formato',
        ];
        foreach ($campos as $campo) {
            $configs[$campo] = $_POST[$campo] ?? '';
        }
        // Manejar upload de logo
        if (!empty($_FILES['logo_upload']['tmp_name'])) {
            $uploadDir = BASE_PATH . '/uploads/logos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['logo_upload']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_empresa_' . $empresaId . '.' . $ext;
            $destPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['logo_upload']['tmp_name'], $destPath)) {
                $configs['empresa_logo_url'] = '/uploads/logos/' . $filename;
            }
        }
        $this->core->guardarEmpresaConfig($empresaId, $configs);
        $this->core->audit('configurar', 'conf_empresa_config', null, null, $configs, 'Configuración de empresa');
        header('Location: /admin/config?cfg_ok=1#tabConfigEmpresa');
        exit;
    }

    public function sistemaIntegrado(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $politicas = $this->safeAll("SELECT * FROM sys_politicas WHERE empresa_id=? AND politica_activa=1", [$empresaId]);
        $capas = $this->safeAll("SELECT * FROM sys_capa WHERE empresa_id=? ORDER BY FIELD(capa_estado,'abierta','en_progreso','vencida','cerrada'), created_at DESC", [$empresaId]);
        $revisiones = $this->safeAll("SELECT * FROM sys_revision_direccion WHERE empresa_id=? ORDER BY revision_anio DESC", [$empresaId]);
        $contextos = $this->safeAll("SELECT * FROM sys_contexto WHERE empresa_id=? AND contexto_activo=1 ORDER BY contexto_tipo", [$empresaId]);
        $comunicaciones = $this->safeAll("SELECT * FROM sys_comunicaciones WHERE empresa_id=? AND comunicacion_activo=1 ORDER BY comunicacion_modulo, comunicacion_tipo", [$empresaId]);
        $pageTitle = 'Sistema Integrado de Gestión';
        ob_start();
        echo '<div class="container-fluid p-3"><h4><i class="fas fa-sitemap me-2"></i>Sistema Integrado de Gestión ISO</h4>';
        require BASE_PATH . '/templates/sistema_integrado.php';
        echo '</div>';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crearCAPA(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $this->safeInsert('sys_capa', ['empresa_id' => $empresaId, 'capa_tipo' => $_POST['tipo'] ?? 'correctiva', 'capa_origen' => $_POST['origen'] ?? 'nc', 'capa_modulo' => $_POST['modulo'] ?? 'general', 'capa_descripcion' => $_POST['descripcion'] ?? '', 'capa_analisis_causa' => $_POST['analisis_causa'] ?? '', 'capa_accion' => $_POST['accion'] ?? '', 'capa_responsable_id' => $_POST['responsable_id'] ? (int)$_POST['responsable_id'] : Auth::userId(), 'capa_fecha_compromiso' => $_POST['fecha_compromiso'] ?? null]);
        header('Location: /sistema?capa_ok=1'); exit;
    }

    public function cerrarCAPA(): void {
        $id = (int)$_POST['capa_id'];
        $this->safeUpdate('sys_capa', ['capa_estado' => 'cerrada', 'capa_fecha_cierre' => date('Y-m-d'), 'capa_verificacion_eficacia' => $_POST['verificacion'] ?? ''], 'capa_id=?', [$id]);
        header('Location: /sistema?capa_ok=1'); exit;
    }

    public function crearRevisionDireccion(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $this->safeInsert('sys_revision_direccion', ['empresa_id' => $empresaId, 'revision_anio' => $_POST['anio'] ?? date('Y'), 'revision_fecha' => $_POST['fecha'] ?? date('Y-m-d'), 'revision_participantes' => $_POST['participantes'] ?? '', 'revision_alcance' => $_POST['alcance'] ?? 'integrada', 'revision_compromisos' => $_POST['compromisos'] ?? '']);
        header('Location: /sistema?rev_ok=1'); exit;
    }

    public function guardarPolitica(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $existente = $this->safeOne("SELECT politica_id FROM sys_politicas WHERE empresa_id=? AND politica_tipo=? AND politica_activa=1", [$empresaId, $_POST['tipo'] ?? 'calidad']);
        if ($existente) $this->safeUpdate('sys_politicas', ['politica_activa' => 0], 'politica_id=?', [(int)$existente['politica_id']]);
        $this->safeInsert('sys_politicas', ['empresa_id' => $empresaId, 'politica_tipo' => $_POST['tipo'] ?? 'calidad', 'politica_texto' => $_POST['texto'] ?? '', 'politica_fecha_aprobacion' => $_POST['fecha_aprobacion'] ?? date('Y-m-d'), 'politica_firmante' => $_POST['firmante'] ?? '', 'politica_version' => (int)($existente['politica_version'] ?? 0) + 1]);
        header('Location: /sistema?pol_ok=1'); exit;
    }

    public function guardarContexto(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $this->safeInsert('sys_contexto', ['empresa_id' => $empresaId, 'contexto_modulo' => $_POST['modulo'] ?? 'integrado', 'contexto_tipo' => $_POST['tipo'] ?? 'fortaleza', 'contexto_descripcion' => $_POST['descripcion'] ?? '', 'contexto_impacto' => $_POST['impacto'] ?? 'medio']);
        header('Location: /sistema?ctx_ok=1'); exit;
    }

    public function guardarComunicacion(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $this->safeInsert('sys_comunicaciones', ['empresa_id' => $empresaId, 'comunicacion_modulo' => $_POST['modulo'] ?? 'integrada', 'comunicacion_tipo' => $_POST['tipo'] ?? 'interna', 'comunicacion_que' => $_POST['que'] ?? '', 'comunicacion_cuando' => $_POST['cuando'] ?? '', 'comunicacion_a_quien' => $_POST['a_quien'] ?? '', 'comunicacion_como' => $_POST['como'] ?? '', 'comunicacion_quien' => $_POST['quien'] ?? '']);
        header('Location: /sistema?com_ok=1'); exit;
    }

    // ============================================================
    // GOBIERNO DE DATOS
    // ============================================================
    public function gobiernoDatosDashboard(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $catalogo = $this->safeAll("SELECT * FROM sys_catalogo_datos WHERE empresa_id=? ORDER BY catalogo_clasificacion, catalogo_tabla", [$empresaId]);
        $clasificaciones = $this->safeAll("SELECT * FROM sys_clasificacion_datos WHERE empresa_id=? ORDER BY FIELD(clasificacion_nivel,'critico','sensible','confidencial','interno','publico')", [$empresaId]);
        $accesos = $this->safeAll("SELECT a.*, u.usuario_nombre FROM sys_auditoria_accesos a LEFT JOIN sys_usuarios u ON a.usuario_id=u.usuario_id WHERE a.empresa_id=? ORDER BY a.acceso_fecha DESC LIMIT 50", [$empresaId]);
        $consentimientos = $this->safeAll("SELECT * FROM sys_consentimientos WHERE empresa_id=? ORDER BY consentimiento_fecha_otorgamiento DESC", [$empresaId]);
        $solicitudes = $this->safeAll("SELECT * FROM sys_solicitudes_datos WHERE empresa_id=? ORDER BY solicitud_fecha DESC", [$empresaId]);
        $metricas = $this->safeAll("SELECT * FROM sys_calidad_datos_metricas WHERE empresa_id=? ORDER BY FIELD(metrica_semaforo,'rojo','amarillo','verde'), metrica_fecha_medicion DESC", [$empresaId]);
        $linajes = $this->safeAll("SELECT * FROM sys_linaje_datos WHERE empresa_id=? ORDER BY linaje_origen_tabla", [$empresaId]);
        $retenciones = $this->safeAll("SELECT * FROM sys_politica_retencion WHERE empresa_id=? AND retencion_activo=1 ORDER BY retencion_periodo_meses DESC", [$empresaId]);
        $pageTitle = 'Gobierno de Datos';
        ob_start(); require BASE_PATH . '/templates/gobierno_datos.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crearConsentimiento(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $this->safeInsert('sys_consentimientos', ['empresa_id' => $empresaId, 'consentimiento_tipo' => $_POST['tipo'] ?? 'paciente', 'consentimiento_titular_id' => $_POST['titular_id'] ?? '', 'consentimiento_titular_nombre' => $_POST['titular_nombre'] ?? '', 'consentimiento_finalidad' => $_POST['finalidad'] ?? '', 'consentimiento_tratamiento' => $_POST['tratamiento'] ?? 'datos_personales', 'consentimiento_fecha_otorgamiento' => $_POST['fecha_otorgamiento'] ?? date('Y-m-d'), 'consentimiento_medio' => $_POST['medio'] ?? 'fisico']);
        header('Location: /gobierno-datos?cons_ok=1'); exit;
    }

    public function crearSolicitudDatos(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $this->safeInsert('sys_solicitudes_datos', ['empresa_id' => $empresaId, 'solicitud_tipo' => $_POST['tipo'] ?? 'acceso', 'solicitud_titular_id' => $_POST['titular_id'] ?? '', 'solicitud_titular_nombre' => $_POST['titular_nombre'] ?? '', 'solicitud_fecha' => date('Y-m-d'), 'solicitud_descripcion' => $_POST['descripcion'] ?? '']);
        header('Location: /gobierno-datos?sol_ok=1'); exit;
    }

    public function evaluarMetricaCalidad(): void {
        $id = (int)$_POST['metrica_id'];
        $this->safeUpdate('sys_calidad_datos_metricas', ['metrica_valor_real' => (float)$_POST['valor_real'], 'metrica_fecha_medicion' => date('Y-m-d'), 'metrica_accion_correctiva' => $_POST['accion'] ?? null], 'metrica_id = ?', [$id]);
        header('Location: /gobierno-datos?met_ok=1'); exit;
    }

    public function catalogoDatos(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $catalogo = $this->safeAll("SELECT * FROM sys_catalogo_datos WHERE empresa_id=? ORDER BY catalogo_clasificacion, catalogo_tabla", [$empresaId]);
        header('Content-Type: application/json');
        echo json_encode($catalogo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
