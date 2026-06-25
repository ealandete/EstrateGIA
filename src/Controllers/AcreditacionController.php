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

        $pageTitle = 'Acreditación - Dashboard de Cumplimiento';
        ob_start(); require BASE_PATH . '/templates/calidad/acreditacion.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function evaluarEstandar(): void {
        $empresaId = (int)$_POST['empresa_id'];
        $estandarId = (int)$_POST['estandar_id'];

        $puntaje = 0;
        $cumplimiento = $_POST['cumplimiento'] ?? 'no_cumple';
        if ($cumplimiento === 'cumple') $puntaje = 100;
        elseif ($cumplimiento === 'cumple_parcial') $puntaje = (float)($_POST['puntaje'] ?? 50);

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

        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'evaluar', 'acreditacion', 'estandar', $id);
        header('Location: /acreditacion?evaluado=1'); exit;
    }

    public function reporteAcreditacion(): void {
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

        require BASE_PATH . '/templates/calidad/acreditacion_reporte.php';
    }
}
