<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class AcreditacionController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    private function initTableCalidad(string $sql): void {
        $this->core->execute($sql);
    }

    // ========================================================================
    // GESTIÓN DE ESTÁNDARES (CRUD)
    // ========================================================================
    public function estandares(): void {
        $estandares = $this->safeAll("SELECT * FROM cal_estandares_acreditacion ORDER BY estandar_tipo, estandar_grupo, estandar_codigo");
        $pageTitle = 'Gestión de Estándares';
        ob_start(); require BASE_PATH . '/templates/calidad/estandares.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crearEstandar(): void {
        $id = $this->safeInsert('cal_estandares_acreditacion', [
            'estandar_grupo' => $_POST['grupo'],
            'estandar_codigo' => $_POST['codigo'],
            'estandar_nombre' => $_POST['nombre'],
            'estandar_descripcion' => $_POST['descripcion'] ?? '',
            'estandar_tipo' => $_POST['tipo'],
            'estandar_nivel' => $_POST['nivel'] ?? 'basico',
        ]);
        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'crear', 'calidad', 'estandar', $id);
        header('Location: /calidad/estandares?created=1'); exit;
    }

    public function editarEstandar(): void {
        $id = (int)$_POST['estandar_id'];
        $this->safeUpdate('cal_estandares_acreditacion', [
            'estandar_grupo' => $_POST['grupo'],
            'estandar_codigo' => $_POST['codigo'],
            'estandar_nombre' => $_POST['nombre'],
            'estandar_descripcion' => $_POST['descripcion'] ?? '',
            'estandar_tipo' => $_POST['tipo'],
            'estandar_nivel' => $_POST['nivel'] ?? 'basico',
        ], 'estandar_id = ?', [$id]);
        header('Location: /calidad/estandares?updated=1'); exit;
    }

    public function eliminarEstandar(): void {
        $id = (int)$_POST['estandar_id'];
        $this->safeUpdate('cal_estandares_acreditacion', ['estandar_activo' => 0], 'estandar_id = ?', [$id]);
        header('Location: /calidad/estandares?deleted=1'); exit;
    }

    // ========================================================================
    // GESTIÓN DE RIESGOS (CRUD)
    // ========================================================================
    public function riesgos(): void {
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);
        $riesgos = $this->safeAll(
            "SELECT r.*, p.proceso_nombre FROM cal_riesgos r LEFT JOIN proc_procesos p ON r.riesgo_proceso_id=p.proceso_id WHERE r.riesgo_empresa_id=? ORDER BY FIELD(r.riesgo_nivel,'extremo','alto','medio','bajo')",
            [$empresaId]
        );
        $procesos = $this->safeAll('SELECT p.proceso_id, p.proceso_nombre FROM proc_procesos p JOIN proc_macroprocesos m ON p.proceso_macro_id=m.macro_id WHERE m.macro_empresa_id=? AND p.proceso_activo=1', [$empresaId]);
        $pageTitle = 'Matriz de Riesgos';
        ob_start(); require BASE_PATH . '/templates/calidad/riesgos.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crearRiesgo(): void {
        $nivel = $this->calcularNivelRiesgo($_POST['probabilidad'], $_POST['impacto']);
        $id = $this->safeInsert('cal_riesgos', [
            'riesgo_empresa_id' => (int)$_POST['empresa_id'],
            'riesgo_proceso_id' => $_POST['proceso_id'] ? (int)$_POST['proceso_id'] : null,
            'riesgo_codigo' => 'R-' . date('Y') . '-' . str_pad(rand(1,999),3,'0',STR_PAD_LEFT),
            'riesgo_descripcion' => $_POST['descripcion'],
            'riesgo_tipo' => $_POST['tipo'] ?? 'asistencial',
            'riesgo_probabilidad' => $_POST['probabilidad'],
            'riesgo_impacto' => $_POST['impacto'],
            'riesgo_nivel' => $nivel,
            'riesgo_controles' => $_POST['controles'] ?? '',
            'riesgo_fecha_identificacion' => $_POST['fecha'] ?? date('Y-m-d'),
            'riesgo_responsable_id' => $_POST['responsable_id'] ? (int)$_POST['responsable_id'] : null,
        ]);
        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'crear', 'calidad', 'riesgo', $id);
        header('Location: /calidad/riesgos?created=1'); exit;
    }

    private function calcularNivelRiesgo(string $prob, string $imp): string {
        $matriz = [
            'raro' => ['insignificante'=>'bajo','menor'=>'bajo','moderado'=>'medio','mayor'=>'medio','catastrofico'=>'alto'],
            'improbable' => ['insignificante'=>'bajo','menor'=>'medio','moderado'=>'medio','mayor'=>'alto','catastrofico'=>'alto'],
            'posible' => ['insignificante'=>'bajo','menor'=>'medio','moderado'=>'alto','mayor'=>'alto','catastrofico'=>'extremo'],
            'probable' => ['insignificante'=>'medio','menor'=>'medio','moderado'=>'alto','mayor'=>'extremo','catastrofico'=>'extremo'],
            'casi_seguro' => ['insignificante'=>'medio','menor'=>'alto','moderado'=>'extremo','mayor'=>'extremo','catastrofico'=>'extremo'],
        ];
        return $matriz[$prob][$imp] ?? 'medio';
    }

    // ========================================================================
    // GESTIÓN DE PAMEC (CRUD)
    // ========================================================================
    public function pamec(): void {
        $this->initTablesPamec();
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);

        $pamec = $this->safeAll(
            "SELECT pa.*, p.proceso_nombre FROM cal_pamec_auditorias pa LEFT JOIN proc_procesos p ON pa.pamec_proceso_id=p.proceso_id WHERE pa.pamec_empresa_id=? ORDER BY pa.pamec_fecha_programada DESC",
            [$empresaId]
        );
        $programas = $this->safeAll(
            "SELECT * FROM cal_pamec_programa WHERE empresa_id=? ORDER BY pamec_anio DESC, created_at DESC",
            [$empresaId]
        );
        $equipos = $this->safeAll(
            "SELECT eq.*, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as usuario_nombre FROM cal_pamec_equipo eq LEFT JOIN sys_usuarios u ON eq.usuario_id=u.usuario_id WHERE eq.pamec_id IN (SELECT pamec_id FROM cal_pamec_programa WHERE empresa_id=?) ORDER BY eq.equipo_rol",
            [$empresaId]
        );
        $procesos = $this->safeAll('SELECT p.proceso_id, p.proceso_nombre FROM proc_procesos p JOIN proc_macroprocesos m ON p.proceso_macro_id=m.macro_id WHERE m.macro_empresa_id=? AND p.proceso_activo=1', [$empresaId]);
        $autoevaluaciones = $this->safeAll(
            "SELECT ae.* FROM cal_pamec_autoevaluacion ae WHERE ae.pamec_id IN (SELECT pamec_id FROM cal_pamec_programa WHERE empresa_id=?) ORDER BY ae.autoeval_estado, ae.autoeval_calificacion DESC",
            [$empresaId]
        );
        $auditorias = $this->safeAll(
            "SELECT * FROM cal_pamec_auditoria WHERE pamec_id IN (SELECT pamec_id FROM cal_pamec_programa WHERE empresa_id=?) ORDER BY auditoria_fecha DESC",
            [$empresaId]
        );
        $rondas = $this->safeAll(
            "SELECT r.*, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as registrado_por FROM cal_rondas_calidad r LEFT JOIN sys_usuarios u ON r.ronda_usuario_id=u.usuario_id WHERE r.empresa_id=? ORDER BY r.ronda_mes DESC, r.created_at DESC LIMIT 50",
            [$empresaId]
        );
        $checklist = $this->safeAll(
            "SELECT * FROM cal_checklist_items WHERE empresa_id=? AND item_activo=1 ORDER BY item_servicio, item_orden",
            [$empresaId]
        );
        $usuarios = $this->safeAll("SELECT usuario_id, usuario_nombre, usuario_apellido FROM sys_usuarios WHERE usuario_activo=1 ORDER BY usuario_nombre");

        $pageTitle = 'PAMEC - Programa Completo';
        ob_start(); require BASE_PATH . '/templates/calidad/pamec.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    private function initTablesPamec(): void {
        $this->core->execute("CREATE TABLE IF NOT EXISTS cal_pamec_programa (
            pamec_id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            pamec_anio YEAR NOT NULL,
            pamec_nombre VARCHAR(250),
            pamec_objetivo TEXT,
            pamec_alcance TEXT,
            pamec_estado ENUM('planificado','en_progreso','completado') DEFAULT 'planificado',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pemp (empresa_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->core->execute("CREATE TABLE IF NOT EXISTS cal_pamec_equipo (
            equipo_id INT AUTO_INCREMENT PRIMARY KEY,
            pamec_id INT NOT NULL,
            usuario_id INT NOT NULL,
            equipo_rol ENUM('lider','evaluador','experto_tecnico','observador'),
            equipo_acta_url VARCHAR(500),
            equipo_fecha_conformacion DATE,
            INDEX idx_pid (pamec_id),
            INDEX idx_uid (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->core->execute("CREATE TABLE IF NOT EXISTS cal_pamec_autoevaluacion (
            autoeval_id INT AUTO_INCREMENT PRIMARY KEY,
            pamec_id INT NOT NULL,
            autoeval_criterio TEXT NOT NULL,
            autoeval_estandar VARCHAR(250),
            autoeval_calificacion DECIMAL(3,1),
            autoeval_evidencia TEXT,
            autoeval_foto_url VARCHAR(500),
            autoeval_estado ENUM('pendiente','evaluado','aprobado') DEFAULT 'pendiente',
            INDEX idx_pid (pamec_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->core->execute("CREATE TABLE IF NOT EXISTS cal_pamec_auditoria (
            auditoria_id INT AUTO_INCREMENT PRIMARY KEY,
            pamec_id INT NOT NULL,
            auditoria_fecha DATE,
            auditoria_auditor VARCHAR(200),
            auditoria_servicio VARCHAR(100),
            auditoria_hallazgos INT DEFAULT 0,
            auditoria_resultado ENUM('conforme','con_observaciones','no_conforme'),
            INDEX idx_pid (pamec_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->core->execute("CREATE TABLE IF NOT EXISTS cal_rondas_calidad (
            ronda_id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            ronda_servicio VARCHAR(100),
            ronda_mes VARCHAR(7),
            ronda_calificacion DECIMAL(3,1),
            ronda_observaciones TEXT,
            ronda_foto_url VARCHAR(500),
            ronda_usuario_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_emp (empresa_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->core->execute("CREATE TABLE IF NOT EXISTS cal_checklist_items (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            item_servicio VARCHAR(100),
            item_criterio TEXT NOT NULL,
            item_estandar VARCHAR(250),
            item_tipo ENUM('pamec','ronda_calidad','auditoria','general'),
            item_orden INT DEFAULT 0,
            item_activo TINYINT(1) DEFAULT 1,
            INDEX idx_emp (empresa_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function crearPamec(): void {
        $id = $this->safeInsert('cal_pamec_auditorias', [
            'pamec_empresa_id' => (int)$_POST['empresa_id'],
            'pamec_anio' => (int)$_POST['anio'],
            'pamec_tipo' => $_POST['tipo'] ?? 'interna',
            'pamec_estandar' => $_POST['estandar'] ?? 'SUA',
            'pamec_proceso_id' => $_POST['proceso_id'] ? (int)$_POST['proceso_id'] : null,
            'pamec_auditor_lider' => $_POST['auditor_lider'] ?? '',
            'pamec_fecha_programada' => $_POST['fecha'] ?? date('Y-m-d'),
        ]);
        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'crear', 'calidad', 'pamec', $id);
        header('Location: /calidad/pamec?created=1'); exit;
    }

    // ========================================================================
    // PAMEC PROGRAMA COMPLETO
    // ========================================================================
    public function crearPamecPrograma(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $id = $this->safeInsert('cal_pamec_programa', [
            'empresa_id' => $empresaId,
            'pamec_anio' => $_POST['pamec_anio'] ?? date('Y'),
            'pamec_nombre' => $_POST['pamec_nombre'] ?? ('PAMEC ' . ($_POST['pamec_anio'] ?? date('Y'))),
            'pamec_objetivo' => $_POST['pamec_objetivo'] ?? '',
            'pamec_alcance' => $_POST['pamec_alcance'] ?? '',
            'pamec_estado' => 'planificado',
        ]);
        $this->core->logAction(Auth::userId(), 'crear', 'pamec', 'programa', $id);
        header('Location: /calidad/pamec?programa_creado=1'); exit;
    }

    public function crearEquipoPamec(): void {
        $id = $this->safeInsert('cal_pamec_equipo', [
            'pamec_id' => (int)$_POST['pamec_id'],
            'usuario_id' => (int)$_POST['usuario_id'],
            'equipo_rol' => $_POST['equipo_rol'] ?? 'evaluador',
            'equipo_fecha_conformacion' => $_POST['equipo_fecha_conformacion'] ?? date('Y-m-d'),
        ]);
        $this->core->logAction(Auth::userId(), 'crear', 'pamec', 'equipo', $id);
        header('Location: /calidad/pamec?equipo_agregado=1'); exit;
    }

    public function guardarAutoevaluacionPamec(): void {
        $id = $this->safeInsert('cal_pamec_autoevaluacion', [
            'pamec_id' => (int)$_POST['pamec_id'],
            'autoeval_criterio' => $_POST['autoeval_criterio'] ?? '',
            'autoeval_estandar' => $_POST['autoeval_estandar'] ?? '',
            'autoeval_calificacion' => (float)($_POST['autoeval_calificacion'] ?? 0),
            'autoeval_evidencia' => $_POST['autoeval_evidencia'] ?? '',
            'autoeval_estado' => $_POST['autoeval_estado'] ?? 'pendiente',
        ]);
        if (!empty($_FILES['autoeval_foto']['tmp_name'])) {
            $url = $this->handleUpload('autoeval_foto', 'pamec_autoeval_' . $id);
            if ($url) {
                $this->safeUpdate('cal_pamec_autoevaluacion', ['autoeval_foto_url' => $url], 'autoeval_id = ?', [$id]);
            }
        }
        header('Location: /calidad/pamec?autoeval_guardada=1'); exit;
    }

    public function crearAuditoriaPamec(): void {
        $id = $this->safeInsert('cal_pamec_auditoria', [
            'pamec_id' => (int)$_POST['pamec_id'],
            'auditoria_fecha' => $_POST['auditoria_fecha'] ?? date('Y-m-d'),
            'auditoria_auditor' => $_POST['auditoria_auditor'] ?? '',
            'auditoria_servicio' => $_POST['auditoria_servicio'] ?? '',
            'auditoria_hallazgos' => (int)($_POST['auditoria_hallazgos'] ?? 0),
            'auditoria_resultado' => $_POST['auditoria_resultado'] ?? 'con_observaciones',
        ]);
        $this->core->logAction(Auth::userId(), 'crear', 'pamec', 'auditoria', $id);
        header('Location: /calidad/pamec?auditoria_creada=1'); exit;
    }

    public function crearRondaCalidad(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $id = $this->safeInsert('cal_rondas_calidad', [
            'empresa_id' => $empresaId,
            'ronda_servicio' => $_POST['ronda_servicio'] ?? '',
            'ronda_mes' => $_POST['ronda_mes'] ?? date('Y-m'),
            'ronda_calificacion' => (float)($_POST['ronda_calificacion'] ?? 0),
            'ronda_observaciones' => $_POST['ronda_observaciones'] ?? '',
            'ronda_usuario_id' => Auth::userId(),
        ]);
        if (!empty($_FILES['ronda_foto']['tmp_name'])) {
            $url = $this->handleUpload('ronda_foto', 'ronda_quality_' . $id);
            if ($url) {
                $this->safeUpdate('cal_rondas_calidad', ['ronda_foto_url' => $url], 'ronda_id = ?', [$id]);
            }
        }
        header('Location: /calidad/pamec?ronda_creada=1'); exit;
    }

    public function crearChecklistItem(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $id = $this->safeInsert('cal_checklist_items', [
            'empresa_id' => $empresaId,
            'item_servicio' => $_POST['item_servicio'] ?? '',
            'item_criterio' => $_POST['item_criterio'] ?? '',
            'item_estandar' => $_POST['item_estandar'] ?? '',
            'item_tipo' => $_POST['item_tipo'] ?? 'general',
            'item_orden' => (int)($_POST['item_orden'] ?? 0),
            'item_activo' => 1,
        ]);
        header('Location: /calidad/pamec?checklist_creado=1'); exit;
    }

    public function verChecklist(): void {
        $this->initTablesPamec();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $tipos = $this->safeAll("SELECT DISTINCT item_servicio FROM cal_checklist_items WHERE empresa_id=? AND item_activo=1 ORDER BY item_servicio", [$empresaId]);
        $tipo = $_GET['tipo'] ?? '';
        $items = $tipo
            ? $this->safeAll("SELECT * FROM cal_checklist_items WHERE empresa_id=? AND item_servicio=? AND item_activo=1 ORDER BY item_orden", [$empresaId, $tipo])
            : $this->safeAll("SELECT * FROM cal_checklist_items WHERE empresa_id=? AND item_activo=1 ORDER BY item_servicio, item_orden", [$empresaId]);
        $pageTitle = 'Checklist Parametrizable';
        ob_start(); require BASE_PATH . '/templates/calidad/checklist.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    private function handleUpload(string $fieldName, string $prefix): ?string {
        $file = $_FILES[$fieldName] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp','pdf'];
        if (!in_array($ext, $allowed)) return null;
        $filename = $prefix . '_' . date('YmdHis') . '_' . substr(md5(uniqid((string)rand(), true)), 0, 8) . '.' . $ext;
        $uploadDir = $this->core->getConfigValue('upload_dir') . 'calidad/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $dest = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return '/uploads/calidad/' . $filename;
        }
        return null;
    }

    private function ensureColumn(string $table, string $column, string $definition): void {
        try {
            $this->core->execute("ALTER TABLE $table ADD COLUMN $column $definition");
        } catch (\PDOException $e) {
        }
    }

    // ========================================================================
    // ACREDITACIÓN - FLUJO PROFESIONAL
    // ========================================================================
    public function acreditacion(): void {
        $this->ensureColumn('cal_evidencias_acreditacion', 'evidencia_archivo_url', 'VARCHAR(500)');
        $this->initTablesAcreditacion();
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);

        $sectorNombre = $empresa['sector_nombre'] ?? 'General';
        $tiposPorSector = [
            'Salud' => ['SUA','ISO7101','Habilitacion'],
            'Inmobiliario' => ['INMOBILIARIO'],
            'Logística Farmacéutica' => ['LOGFARMA'],
            'Tecnología' => ['TECNOLOGIA'],
            'Manufactura' => ['MANUFACTURA'],
            'General' => ['ISO7101','Habilitacion','INMOBILIARIO','LOGFARMA'],
        ];
        $tiposAplicables = $tiposPorSector[$sectorNombre] ?? ['SUA','ISO7101','Habilitacion'];

        $inParams = [];
        $inPlaceholders = [];
        foreach ($tiposAplicables as $tipo) { $inParams[] = $tipo; $inPlaceholders[] = '?'; }
        $inClause = implode(',', $inPlaceholders);

        $estandares = $this->safeAll(
            "SELECT e.*, ev.evidencia_cumplimiento as ultimo_cumplimiento, ev.evidencia_puntaje as ultimo_puntaje,
                    ev.evidencia_descripcion, ev.evidencia_plan_mejora, ev.evidencia_id
             FROM cal_estandares_acreditacion e
             LEFT JOIN cal_evidencias_acreditacion ev ON e.estandar_id=ev.evidencia_estandar_id AND ev.evidencia_empresa_id=?
             WHERE e.estandar_activo=1 AND e.estandar_tipo IN ($inClause)
             ORDER BY e.estandar_tipo, e.estandar_grupo",
            array_merge([$empresaId], $inParams)
        );

        $cumplen = count(array_filter($estandares, fn($e) => $e['ultimo_cumplimiento'] === 'cumple'));
        $parcial = count(array_filter($estandares, fn($e) => $e['ultimo_cumplimiento'] === 'cumple_parcial'));
        $noCumplen = count(array_filter($estandares, fn($e) => $e['ultimo_cumplimiento'] === 'no_cumple'));
        $total = count($estandares);
        $pctCumplimiento = $total > 0 ? round(($cumplen / $total) * 100, 1) : 0;

        $porTipo = [];
        foreach ($estandares as $e) {
            $tipo = $e['estandar_tipo'];
            if (!isset($porTipo[$tipo])) $porTipo[$tipo] = ['total' => 0, 'cumple' => 0, 'parcial' => 0, 'no_cumple' => 0];
            $porTipo[$tipo]['total']++;
            $cumpl = $e['ultimo_cumplimiento'] ?? 'no_aplica';
            if ($cumpl === 'cumple') $porTipo[$tipo]['cumple']++;
            elseif ($cumpl === 'cumple_parcial') $porTipo[$tipo]['parcial']++;
            elseif ($cumpl === 'no_cumple') $porTipo[$tipo]['no_cumple']++;
        }

        $porGrupo = [];
        foreach ($estandares as $e) {
            $g = $e['estandar_grupo'];
            if (!isset($porGrupo[$g])) $porGrupo[$g] = ['total' => 0, 'cumple' => 0];
            $porGrupo[$g]['total']++;
            if (($e['ultimo_cumplimiento'] ?? '') === 'cumple') $porGrupo[$g]['cumple']++;
        }

        $ciclos = $this->safeAll(
            "SELECT * FROM cal_acreditacion_niveles WHERE nivel_empresa_id = ? ORDER BY nivel_estandar_tipo",
            [$empresaId]
        );

        $actividades = $this->safeAll(
            "SELECT a.*, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as responsable_nombre
             FROM cal_actividades_acreditacion a LEFT JOIN sys_usuarios u ON a.act_responsable_id=u.usuario_id
             WHERE a.act_empresa_id=? ORDER BY a.act_fecha_fin ASC LIMIT 30",
            [$empresaId]
        );

        $visitas = $this->safeAll(
            "SELECT * FROM cal_acreditacion_visitas WHERE empresa_id=? ORDER BY visita_fecha_programada DESC",
            [$empresaId]
        );

        $planesMejora = $this->safeAll(
            "SELECT pm.*, e.estandar_codigo, e.estandar_nombre,
                    CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as responsable_nombre
             FROM cal_planes_mejora pm
             LEFT JOIN cal_estandares_acreditacion e ON pm.estandar_id=e.estandar_id
             LEFT JOIN sys_usuarios u ON pm.plan_responsable_id=u.usuario_id
             WHERE pm.empresa_id=? ORDER BY FIELD(pm.plan_estado,'abierto','en_progreso','vencido','cerrado'), pm.created_at DESC",
            [$empresaId]
        );

        $seguimientos = $this->safeAll(
            "SELECT s.* FROM cal_seguimiento s
             JOIN cal_planes_mejora pm ON s.plan_id=pm.plan_id
             WHERE pm.empresa_id=? ORDER BY s.seguimiento_fecha DESC",
            [$empresaId]
        );

        $suaEstandares = $this->safeAll("SELECT * FROM cal_estandares_sua ORDER BY sua_eje, sua_grupo, sua_numero");

        $usuarios = $this->safeAll("SELECT usuario_id, usuario_nombre, usuario_apellido FROM sys_usuarios WHERE usuario_activo=1 ORDER BY usuario_nombre");

        $this->ensureColumn('cal_evidencias_acreditacion', 'evidencia_escala', "VARCHAR(20) DEFAULT '0-100'");
        $this->ensureColumn('cal_evidencias_acreditacion', 'evidencia_nivel_sua', 'TINYINT NULL');

        $pageTitle = 'Acreditación - Dashboard de Cumplimiento';
        ob_start(); require BASE_PATH . '/templates/calidad/acreditacion.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    private function initTablesAcreditacion(): void {
        $this->core->execute("CREATE TABLE IF NOT EXISTS cal_acreditacion_visitas (
            visita_id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            ciclo_id INT NULL,
            visita_tipo ENUM('autoevaluacion','visita_evaluacion','seguimiento','reacreditacion') DEFAULT 'visita_evaluacion',
            visita_fecha_programada DATE,
            visita_fecha_real DATE,
            visita_evaluador_lider VARCHAR(200),
            visita_evaluadores TEXT,
            visita_hallazgos INT DEFAULT 0,
            visita_no_conformidades INT DEFAULT 0,
            visita_observaciones INT DEFAULT 0,
            visita_informe_url VARCHAR(500),
            visita_estado ENUM('programada','en_curso','completada','cancelada') DEFAULT 'programada',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_empresa_ciclo (empresa_id, ciclo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->core->execute("CREATE TABLE IF NOT EXISTS cal_planes_mejora (
            plan_id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            estandar_id INT,
            visita_id INT NULL,
            plan_accion TEXT NOT NULL,
            plan_responsable_id INT,
            plan_fecha_compromiso DATE,
            plan_fecha_cierre DATE,
            plan_evidencia_cierre TEXT,
            plan_estado ENUM('abierto','en_progreso','cerrado','vencido') DEFAULT 'abierto',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_empresa_estandar (empresa_id, estandar_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->core->execute("CREATE TABLE IF NOT EXISTS cal_seguimiento (
            seguimiento_id INT AUTO_INCREMENT PRIMARY KEY,
            plan_id INT NOT NULL,
            seguimiento_fecha DATE NOT NULL,
            seguimiento_avance DECIMAL(5,1) DEFAULT 0,
            seguimiento_observaciones TEXT,
            seguimiento_evidencia_url VARCHAR(500),
            seguimiento_usuario_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_plan (plan_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->core->execute("CREATE TABLE IF NOT EXISTS cal_estandares_sua (
            sua_id INT AUTO_INCREMENT PRIMARY KEY,
            sua_grupo VARCHAR(100) NOT NULL,
            sua_subgrupo VARCHAR(100),
            sua_codigo VARCHAR(20) NOT NULL,
            sua_numero INT NOT NULL,
            sua_descripcion TEXT NOT NULL,
            sua_tipo ENUM('indispensable','complementario','informacion') DEFAULT 'indispensable',
            sua_eje ENUM('seguridad_paciente','humanizacion','gestion_tecnologia','enfoque_riesgo') DEFAULT 'seguridad_paciente',
            UNIQUE KEY uk_codigo (sua_codigo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function evaluarEstandar(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $estandarId = (int)$_POST['estandar_id'];
        $escala = $_POST['escala'] ?? '0-100';

        $cumplimiento = $_POST['cumplimiento'] ?? 'no_cumple';
        if ($escala === '1-5') {
            $nivelSua = (int)($_POST['nivel_sua'] ?? 1);
            $puntaje = ($nivelSua / 5) * 100;
            $cumplimiento = $nivelSua >= 4 ? 'cumple' : ($nivelSua >= 2 ? 'cumple_parcial' : 'no_cumple');
        } else {
            if ($cumplimiento === 'cumple') $puntaje = 100;
            elseif ($cumplimiento === 'cumple_parcial') $puntaje = (float)($_POST['puntaje'] ?? 50);
            else $puntaje = (float)($_POST['puntaje'] ?? 0);
        }

        $id = $this->safeInsert('cal_evidencias_acreditacion', [
            'evidencia_empresa_id' => $empresaId,
            'evidencia_estandar_id' => $estandarId,
            'evidencia_proceso_id' => $_POST['proceso_id'] ? (int)$_POST['proceso_id'] : null,
            'evidencia_descripcion' => $_POST['evidencia'] ?? '',
            'evidencia_cumplimiento' => $cumplimiento,
            'evidencia_puntaje' => $puntaje,
            'evidencia_plan_mejora' => $_POST['plan_mejora'] ?? '',
            'evidencia_fecha_evaluacion' => date('Y-m-d'),
            'evidencia_evaluador_id' => Auth::userId(),
        ]);

        if (!empty($_FILES['evidencia_archivo']['tmp_name'])) {
            $url = $this->handleUpload('evidencia_archivo', 'acreditacion_evidencia_' . $id);
            if ($url) {
                $this->safeUpdate('cal_evidencias_acreditacion', ['evidencia_archivo_url' => $url], 'evidencia_id = ?', [$id]);
            }
        }

        if ($escala === '1-5') {
            $this->safeUpdate('cal_evidencias_acreditacion', ['evidencia_escala' => '1-5', 'evidencia_nivel_sua' => ($_POST['nivel_sua'] ?? null)], 'evidencia_id = ?', [$id]);
        }

        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'evaluar', 'acreditacion', 'estandar', $id);
        header('Location: /acreditacion?evaluado=1'); exit;
    }

    public function reporteAcreditacion(): void {
        $this->initTablesAcreditacion();
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);

        $sectorNombre = $empresa['sector_nombre'] ?? 'General';
        $tiposPorSector = [
            'Salud' => ['SUA','ISO7101','Habilitacion'],
            'Inmobiliario' => ['INMOBILIARIO'],
            'Logística Farmacéutica' => ['LOGFARMA'],
            'Tecnología' => ['TECNOLOGIA'],
            'Manufactura' => ['MANUFACTURA'],
            'General' => ['ISO7101','Habilitacion','INMOBILIARIO','LOGFARMA'],
        ];
        $tiposAplicables = $tiposPorSector[$sectorNombre] ?? ['SUA','ISO7101','Habilitacion'];

        $inParams = []; $inPlaceholders = [];
        foreach ($tiposAplicables as $tipo) { $inParams[] = $tipo; $inPlaceholders[] = '?'; }
        $inClause = implode(',', $inPlaceholders);

        $estandares = $this->safeAll(
            "SELECT e.*, ev.evidencia_cumplimiento as ultimo_cumplimiento, ev.evidencia_puntaje as ultimo_puntaje,
                    ev.evidencia_descripcion, ev.evidencia_plan_mejora, ev.evidencia_fecha_evaluacion
             FROM cal_estandares_acreditacion e
             LEFT JOIN cal_evidencias_acreditacion ev ON e.estandar_id=ev.evidencia_estandar_id AND ev.evidencia_empresa_id=?
             WHERE e.estandar_activo=1 AND e.estandar_tipo IN ($inClause)
             ORDER BY e.estandar_tipo, e.estandar_grupo",
            array_merge([$empresaId], $inParams)
        );

        $cumplen = count(array_filter($estandares, fn($e) => $e['ultimo_cumplimiento'] === 'cumple'));
        $parcial = count(array_filter($estandares, fn($e) => $e['ultimo_cumplimiento'] === 'cumple_parcial'));
        $noCumplen = count(array_filter($estandares, fn($e) => $e['ultimo_cumplimiento'] === 'no_cumple'));
        $total = count($estandares);
        $pct = $total > 0 ? round(($cumplen / $total) * 100, 1) : 0;
        $pctTotal = 0;
        $puntajes = array_filter(array_map(fn($e) => $e['ultimo_puntaje'], $estandares), fn($v) => $v !== null);
        if (count($puntajes) > 0) $pctTotal = round(array_sum($puntajes) / count($puntajes), 1);

        $ncs = $this->safeAll("SELECT * FROM cal_no_conformidades WHERE nc_empresa_id=? ORDER BY nc_fecha_deteccion DESC", [$empresaId]);
        $pamecData = $this->safeAll("SELECT * FROM cal_pamec_auditorias WHERE pamec_empresa_id=? ORDER BY pamec_fecha_programada DESC", [$empresaId]);
        $riesgos = $this->safeAll("SELECT * FROM cal_riesgos WHERE riesgo_empresa_id=? ORDER BY FIELD(riesgo_nivel,'extremo','alto','medio','bajo')", [$empresaId]);

        $porTipo = [];
        foreach ($estandares as $e) {
            $tipo = $e['estandar_tipo'];
            if (!isset($porTipo[$tipo])) $porTipo[$tipo] = ['total' => 0, 'cumple' => 0, 'parcial' => 0, 'no_cumple' => 0, 'puntaje_total' => 0];
            $porTipo[$tipo]['total']++;
            $c = $e['ultimo_cumplimiento'] ?? '';
            if ($c === 'cumple') $porTipo[$tipo]['cumple']++;
            elseif ($c === 'cumple_parcial') $porTipo[$tipo]['parcial']++;
            elseif ($c === 'no_cumple') $porTipo[$tipo]['no_cumple']++;
            $porTipo[$tipo]['puntaje_total'] += (float)($e['ultimo_puntaje'] ?? 0);
        }

        $heatmapData = [];
        foreach ($estandares as $e) {
            $g = $e['estandar_grupo'];
            if (!isset($heatmapData[$g])) $heatmapData[$g] = ['total' => 0, 'puntaje' => 0, 'cumple' => 0];
            $heatmapData[$g]['total']++;
            $heatmapData[$g]['puntaje'] += (float)($e['ultimo_puntaje'] ?? 0);
            if (($e['ultimo_cumplimiento'] ?? '') === 'cumple') $heatmapData[$g]['cumple']++;
        }

        $planesMejora = $this->safeAll(
            "SELECT pm.*, e.estandar_codigo FROM cal_planes_mejora pm
             LEFT JOIN cal_estandares_acreditacion e ON pm.estandar_id=e.estandar_id
             WHERE pm.empresa_id=? AND pm.plan_estado IN ('abierto','en_progreso')
             ORDER BY pm.created_at DESC",
            [$empresaId]
        );

        $visitas = $this->safeAll(
            "SELECT * FROM cal_acreditacion_visitas WHERE empresa_id=? ORDER BY visita_fecha_programada DESC LIMIT 10",
            [$empresaId]
        );

        require BASE_PATH . '/templates/calidad/acreditacion_reporte.php';
    }

    // ========================================================================
    // GESTIÓN DE VISITAS DE ACREDITACIÓN
    // ========================================================================
    public function crearVisita(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $id = $this->safeInsert('cal_acreditacion_visitas', [
            'empresa_id' => $empresaId,
            'ciclo_id' => $_POST['ciclo_id'] ? (int)$_POST['ciclo_id'] : null,
            'visita_tipo' => $_POST['visita_tipo'] ?? 'visita_evaluacion',
            'visita_fecha_programada' => $_POST['visita_fecha_programada'] ?? date('Y-m-d'),
            'visita_fecha_real' => $_POST['visita_fecha_real'] ?? null,
            'visita_evaluador_lider' => $_POST['visita_evaluador_lider'] ?? '',
            'visita_evaluadores' => $_POST['visita_evaluadores'] ?? '',
            'visita_hallazgos' => (int)($_POST['visita_hallazgos'] ?? 0),
            'visita_no_conformidades' => (int)($_POST['visita_no_conformidades'] ?? 0),
            'visita_observaciones' => (int)($_POST['visita_observaciones'] ?? 0),
            'visita_estado' => $_POST['visita_estado'] ?? 'programada',
        ]);

        if (!empty($_FILES['visita_informe']['tmp_name'])) {
            $url = $this->handleUpload('visita_informe', 'visita_acreditacion_' . $id);
            if ($url) {
                $this->safeUpdate('cal_acreditacion_visitas', ['visita_informe_url' => $url], 'visita_id = ?', [$id]);
            }
        }

        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'crear', 'acreditacion', 'visita', $id);
        header('Location: /acreditacion?visita_creada=1#visitas'); exit;
    }

    // ========================================================================
    // GESTIÓN DE PLANES DE MEJORA ESTRUCTURADOS
    // ========================================================================
    public function crearPlanMejora(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $id = $this->safeInsert('cal_planes_mejora', [
            'empresa_id' => $empresaId,
            'estandar_id' => $_POST['estandar_id'] ? (int)$_POST['estandar_id'] : null,
            'visita_id' => $_POST['visita_id'] ? (int)$_POST['visita_id'] : null,
            'plan_accion' => $_POST['plan_accion'] ?? '',
            'plan_responsable_id' => $_POST['plan_responsable_id'] ? (int)$_POST['plan_responsable_id'] : null,
            'plan_fecha_compromiso' => $_POST['plan_fecha_compromiso'] ?? date('Y-m-d', strtotime('+30 days')),
            'plan_estado' => 'abierto',
        ]);

        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'crear', 'acreditacion', 'plan_mejora', $id);
        header('Location: /acreditacion?plan_creado=1#planes-mejora'); exit;
    }

    public function cerrarPlanMejora(int $planId): void {
        $this->safeUpdate('cal_planes_mejora', [
            'plan_estado' => 'cerrado',
            'plan_fecha_cierre' => date('Y-m-d'),
            'plan_evidencia_cierre' => $_POST['plan_evidencia_cierre'] ?? '',
        ], 'plan_id = ?', [$planId]);

        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'cerrar', 'acreditacion', 'plan_mejora', $planId);
        header('Location: /acreditacion?plan_cerrado=1#planes-mejora'); exit;
    }

    // ========================================================================
    // SEGUIMIENTO DE PLANES
    // ========================================================================
    public function crearSeguimiento(): void {
        $planId = (int)$_POST['plan_id'];
        $id = $this->safeInsert('cal_seguimiento', [
            'plan_id' => $planId,
            'seguimiento_fecha' => $_POST['seguimiento_fecha'] ?? date('Y-m-d'),
            'seguimiento_avance' => (float)($_POST['seguimiento_avance'] ?? 0),
            'seguimiento_observaciones' => $_POST['seguimiento_observaciones'] ?? '',
            'seguimiento_usuario_id' => Auth::userId(),
        ]);

        if (!empty($_FILES['seguimiento_evidencia']['tmp_name'])) {
            $url = $this->handleUpload('seguimiento_evidencia', 'seguimiento_' . $id);
            if ($url) {
                $this->safeUpdate('cal_seguimiento', ['seguimiento_evidencia_url' => $url], 'seguimiento_id = ?', [$id]);
            }
        }

        $avance = (float)($_POST['seguimiento_avance'] ?? 0);
        $newState = $avance >= 100 ? 'cerrado' : ($avance > 0 ? 'en_progreso' : 'abierto');
        $updateData = ['plan_estado' => $newState];
        if ($avance >= 100) { $updateData['plan_fecha_cierre'] = date('Y-m-d'); }
        $this->safeUpdate('cal_planes_mejora', $updateData, 'plan_id = ?', [$planId]);

        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'crear', 'acreditacion', 'seguimiento', $id);
        header('Location: /acreditacion?seguimiento_creado=1#seguimiento'); exit;
    }

    // ========================================================================
    // CARGA DE ESTÁNDARES SUA OFICIALES
    // ========================================================================
    public function cargarEstandaresSUA(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $eje = $_POST['sua_eje'] ?? null;

        $where = '';
        $params = [];
        if ($eje && $eje !== 'todos') {
            $where = ' AND sua_eje = ?';
            $params[] = $eje;
        }

        $suaEstandares = $this->safeAll(
            "SELECT * FROM cal_estandares_sua WHERE 1=1 $where ORDER BY sua_eje, sua_grupo, sua_numero",
            $params
        );

        $insertados = 0;
        foreach ($suaEstandares as $s) {
            $exists = $this->safeCount('cal_estandares_acreditacion', 'estandar_codigo = ?', [$s['sua_codigo']]);
            if ($exists > 0) continue;

            $tipo = ($s['sua_eje'] === 'seguridad_paciente') ? 'SUA' : 'SUA';
            $this->safeInsert('cal_estandares_acreditacion', [
                'estandar_grupo' => $s['sua_grupo'],
                'estandar_codigo' => $s['sua_codigo'],
                'estandar_nombre' => $s['sua_descripcion'],
                'estandar_descripcion' => $s['sua_grupo'] . ' - ' . ($s['sua_subgrupo'] ?? '') . ' | ' . $s['sua_descripcion'],
                'estandar_tipo' => $tipo,
                'estandar_nivel' => $s['sua_tipo'] === 'indispensable' ? 'avanzado' : 'basico',
                'estandar_escala' => '1-5',
                'estandar_activo' => 1,
            ]);
            $insertados++;
        }

        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'cargar', 'acreditacion', 'estandares_sua', $insertados);
        header('Location: /acreditacion?sua_cargados=' . $insertados); exit;
    }

    // ========================================================================
    // CAMBIO DE FASE DEL CICLO DE ACREDITACIÓN (STATE MACHINE)
    // ========================================================================
    public function cambiarFase(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $tipo = $_POST['nivel_estandar_tipo'] ?? 'SUA';
        $nuevaFase = $_POST['nivel_fase'] ?? 'preparacion';

        $valTransitions = [
            'preparacion' => ['autoevaluacion', 'diagnostico'],
            'autoevaluacion' => ['plan_mejora', 'preparacion'],
            'plan_mejora' => ['visita_simulacro', 'implementacion'],
            'implementacion' => ['visita_simulacro', 'plan_mejora'],
            'visita_simulacro' => ['pre_visita', 'plan_mejora'],
            'pre_visita' => ['visita_evaluacion', 'visita_simulacro'],
            'visita_evaluacion' => ['informe_resultados', 'plan_mejora'],
            'informe_resultados' => ['seguimiento', 'acreditado'],
            'seguimiento' => ['reacreditacion', 'acreditado'],
            'reacreditacion' => ['preparacion', 'acreditado'],
        ];

        $ciclo = $this->safeOne(
            "SELECT * FROM cal_acreditacion_niveles WHERE nivel_empresa_id = ? AND nivel_estandar_tipo = ?",
            [$empresaId, $tipo]
        );

        if (!$ciclo) {
            $this->safeInsert('cal_acreditacion_niveles', [
                'nivel_empresa_id' => $empresaId,
                'nivel_estandar_tipo' => $tipo,
                'nivel_puntaje_actual' => 0,
                'nivel_puntaje_objetivo' => 90,
                'nivel_fase' => $nuevaFase,
            ]);
        } else {
            $faseActual = $ciclo['nivel_fase'] ?? 'preparacion';
            $allowed = $valTransitions[$faseActual] ?? [];
            if (!in_array($nuevaFase, $allowed)) {
                header('Location: /acreditacion?fase_error=1');
                exit;
            }
            $this->safeUpdate('cal_acreditacion_niveles', [
                'nivel_fase' => $nuevaFase,
                'nivel_puntaje_objetivo' => (float)($_POST['nivel_puntaje_objetivo'] ?? 90),
            ], 'nivel_empresa_id = ? AND nivel_estandar_tipo = ?', [$empresaId, $tipo]);
        }

        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'cambiar_fase', 'acreditacion', 'ciclo', 0);
        header('Location: /acreditacion?fase_cambiada=1#ciclo'); exit;
    }

    public function evaluarPorServicio(): void {
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $estandarId = (int)$_POST['estandar_id'];
        $servicioId = (int)$_POST['servicio_id'];
        $cumplimiento = $_POST['cumplimiento'] ?? 'sin_evaluar';
        $puntaje = (int)($_POST['puntaje'] ?? 0);
        $observaciones = $_POST['observaciones'] ?? '';
        $archivo = $_FILES['archivo'] ?? null;
        $archivoUrl = '';
        if ($archivo && $archivo['error'] === UPLOAD_ERR_OK) {
            $dest = BASE_PATH . '/public/uploads/acreditacion/';
            if (!is_dir($dest)) mkdir($dest, 0755, true);
            $archivoUrl = '/uploads/acreditacion/' . time() . '_' . basename($archivo['name']);
            move_uploaded_file($archivo['tmp_name'], BASE_PATH . '/public' . $archivoUrl);
        }
        $this->safeInsert('cal_evidencias_acreditacion', [
            'evidencia_estandar_id' => $estandarId,
            'evidencia_servicio_id' => $servicioId,
            'evidencia_empresa_id' => $empresaId,
            'evidencia_cumplimiento' => $cumplimiento,
            'evidencia_puntaje' => $puntaje,
            'evidencia_observaciones' => $observaciones,
            'evidencia_archivo_url' => $archivoUrl,
            'evidencia_usuario_id' => Auth::userId(),
            'evidencia_fecha' => date('Y-m-d H:i:s'),
        ]);
        header('Location: /acreditacion?servicio_evaluado=1'); exit;
    }

    public function apiServicioHeatmap(): void {
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $servicios = $this->safeAll("SELECT * FROM cal_servicios_acreditacion WHERE empresa_id=? AND servicio_activo=1", [$empresaId]);
        $estandares = $this->safeAll("SELECT * FROM cal_estandares_acreditacion WHERE empresa_id=? AND estandar_activo=1", [$empresaId]);
        $evidencias = $this->safeAll(
            "SELECT evidencia_estandar_id, evidencia_servicio_id, evidencia_puntaje FROM cal_evidencias_acreditacion WHERE evidencia_empresa_id=?",
            [$empresaId]
        );
        $heatmap = [];
        foreach ($servicios as $s) {
            $row = ['servicio' => $s['servicio_nombre']];
            foreach ($estandares as $e) {
                $puntaje = 0;
                foreach ($evidencias as $ev) {
                    if ($ev['evidencia_estandar_id'] == $e['estandar_id'] && $ev['evidencia_servicio_id'] == $s['servicio_id']) {
                        $puntaje = (int)$ev['evidencia_puntaje'];
                    }
                }
                $row[$e['estandar_codigo']] = $puntaje;
            }
            $heatmap[] = $row;
        }
        header('Content-Type: application/json');
        echo json_encode(['servicios' => $servicios, 'estandares' => $estandares, 'heatmap' => $heatmap]);
        exit;
    }
}
