<?php
declare(strict_types=1);

require_once BASE_PATH . '/lib/SafeQuery.php';

class ProcesosController {
    use \SafeQuery;
    private $core;

    public function __construct() { $this->core = EstrateGiaCore::getInstance(); Auth::guard(); }

    // ========================================================================
    // MAPA DE PROCESOS
    // ========================================================================
    public function index(): void {
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);

        $macroprocesos = $this->safeAll(
            "SELECT m.*, 
                    (SELECT COUNT(*) FROM proc_procesos p WHERE p.proceso_macro_id = m.macro_id AND p.proceso_activo = 1) as total_procesos,
                    (SELECT COUNT(*) FROM proc_tareas t JOIN proc_procesos p ON t.tarea_proceso_id = p.proceso_id WHERE p.proceso_macro_id = m.macro_id AND t.tarea_activo = 1) as total_tareas,
                    (SELECT COUNT(*) FROM doc_documentos d JOIN proc_procesos p ON d.documento_proceso_id = p.proceso_id WHERE p.proceso_macro_id = m.macro_id AND d.documento_activo = 1) as total_documentos
             FROM proc_macroprocesos m 
             WHERE m.macro_empresa_id = ? AND m.macro_activo = 1
             ORDER BY FIELD(m.macro_tipo, 'estrategico','misional','apoyo','evaluacion'), m.macro_orden",
            [$empresaId]
        );

        // Cargar procesos de cada macroproceso
        foreach ($macroprocesos as &$mp) {
            $mp['procesos'] = $this->safeAll(
                "SELECT p.*, 
                        (SELECT COUNT(*) FROM proc_procedimientos pr WHERE pr.procedimiento_proceso_id = p.proceso_id) as total_procedimientos,
                        (SELECT COUNT(*) FROM proc_tareas t WHERE t.tarea_proceso_id = p.proceso_id AND t.tarea_activo = 1) as total_tareas,
                        (SELECT COUNT(*) FROM doc_documentos d WHERE d.documento_proceso_id = p.proceso_id AND d.documento_activo = 1) as total_documentos,
                        CONCAT(u.usuario_nombre, ' ', u.usuario_apellido) as responsable_nombre
                 FROM proc_procesos p
                 LEFT JOIN sys_usuarios u ON p.proceso_responsable_id = u.usuario_id
                 WHERE p.proceso_macro_id = ? AND p.proceso_activo = 1
                 ORDER BY p.proceso_nombre",
                [$mp['macro_id']]
            );
        }

        $pageTitle = 'Mapa de Procesos';
        ob_start(); require BASE_PATH . '/templates/procesos/index.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    // ========================================================================
    // CRUD MACROPROCESO
    // ========================================================================
    public function crearMacroproceso(): void {
        $pm = new PlanManager();
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));

        $this->safeInsert('proc_macroprocesos', [
            'macro_empresa_id' => $empresaId,
            'macro_codigo' => $_POST['codigo'] ?? '',
            'macro_nombre' => $_POST['nombre'],
            'macro_descripcion' => $_POST['descripcion'] ?? '',
            'macro_tipo' => $_POST['tipo'] ?? 'misional',
            'macro_orden' => (int)($_POST['orden'] ?? 0),
        ]);
        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'crear', 'procesos', 'macroproceso', EstrateGiaCore::getInstance()->getPDO()->lastInsertId()); header('Location: /procesos?created=mp&empresa_id=' . $empresaId);
        exit;
    }

    public function editarMacroproceso(): void {
        $id = (int)$_POST['macro_id'];
        $empresaId = (int)($_POST['empresa_id'] ?? 2);

        $this->safeUpdate('proc_macroprocesos', [
            'macro_codigo' => $_POST['codigo'] ?? '',
            'macro_nombre' => $_POST['nombre'],
            'macro_descripcion' => $_POST['descripcion'] ?? '',
            'macro_tipo' => $_POST['tipo'] ?? 'misional',
            'macro_orden' => (int)($_POST['orden'] ?? 0),
        ], 'macro_id = ?', [$id]);
        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'editar', 'procesos', 'macroproceso', $id); header('Location: /procesos?updated=mp&empresa_id=' . $empresaId);
        exit;
    }

    public function eliminarMacroproceso(): void {
        $id = (int)$_POST['macro_id'];
        $this->safeUpdate('proc_macroprocesos', ['macro_activo' => 0], 'macro_id = ?', [$id]);
        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'eliminar', 'procesos', 'macroproceso', $id); header('Location: /procesos?deleted=mp');
        exit;
    }

    // ========================================================================
    // CRUD PROCESO
    // ========================================================================
    public function crearProceso(): void {
        $macroId = (int)($_POST['macro_id'] ?? 0);
        $empresaId = (int)($_POST['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));

        if ($macroId <= 0) {
            $macroId = $this->autoCrearMacroprocesoDefault($empresaId);
        }

        $id = $this->safeInsert('proc_procesos', [
            'proceso_macro_id' => $macroId,
            'proceso_codigo' => $_POST['codigo'] ?? '',
            'proceso_nombre' => $_POST['nombre'],
            'proceso_descripcion' => $_POST['descripcion'] ?? '',
            'proceso_objetivo' => $_POST['objetivo'] ?? '',
            'proceso_alcance' => $_POST['alcance'] ?? '',
            'proceso_tipo' => $_POST['tipo'] ?? 'misional',
            'proceso_responsable_id' => $_POST['responsable_id'] ? (int)$_POST['responsable_id'] : null,
        ]);
        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'crear', 'procesos', 'proceso', $id); header('Location: /procesos/ver/' . $id . '?created=p');
        exit;
    }

    private function autoCrearMacroprocesoDefault(int $empresaId): int {
        $existing = $this->safeOne(
            "SELECT macro_id FROM proc_macroprocesos WHERE macro_empresa_id = ? AND macro_activo = 1 ORDER BY macro_orden ASC LIMIT 1",
            [$empresaId]
        );
        if ($existing) {
            return (int)$existing['macro_id'];
        }

        $count = (int)($this->safe("SELECT COALESCE(MAX(macro_orden),0) FROM proc_macroprocesos WHERE macro_empresa_id = ?", [$empresaId]) ?? 0);
        $macroId = $this->safeInsert('proc_macroprocesos', [
            'macro_empresa_id' => $empresaId,
            'macro_codigo' => 'MP-GEN' . ($count + 1),
            'macro_nombre' => 'Procesos Generales',
            'macro_descripcion' => 'Macroproceso creado automáticamente',
            'macro_tipo' => 'misional',
            'macro_orden' => $count + 1,
        ]);
        return $macroId ?? 0;
    }

    public function verProceso(int $id): void {
        $proceso = $this->safeOne(
            "SELECT p.*, m.macro_nombre, m.macro_tipo,
                    CONCAT(u.usuario_nombre, ' ', u.usuario_apellido) as responsable_nombre
             FROM proc_procesos p
             JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id
             LEFT JOIN sys_usuarios u ON p.proceso_responsable_id = u.usuario_id
             WHERE p.proceso_id = ?", [$id]
        );
        if (!$proceso) { http_response_code(404); echo 'No encontrado'; return; }

        $procedimientos = $this->safeAll(
            'SELECT * FROM proc_procedimientos WHERE procedimiento_proceso_id = ? AND procedimiento_activo = 1 ORDER BY procedimiento_orden',
            [$id]
        );
        $tareas = $this->safeAll(
            'SELECT t.*, CONCAT(u.usuario_nombre, " ", u.usuario_apellido) as responsable_nombre
             FROM proc_tareas t LEFT JOIN sys_usuarios u ON t.tarea_responsable_id = u.usuario_id
             WHERE t.tarea_proceso_id = ? AND t.tarea_activo = 1 ORDER BY t.tarea_orden',
            [$id]
        );
        $documentos = $this->safeAll(
            'SELECT * FROM doc_documentos WHERE documento_proceso_id = ? AND documento_activo = 1',
            [$id]
        );
        $indicadores = $this->safeAll(
            'SELECT i.*, c.categoria_nombre FROM ind_indicadores i JOIN ind_categorias c ON i.indicador_categoria_id = c.categoria_id WHERE i.indicador_proceso_id = ?',
            [$id]
        );

        $pm = new PlanManager();
        $usuarios = $this->safeAll('SELECT usuario_id, usuario_nombre, usuario_apellido FROM sys_usuarios WHERE usuario_activo = 1');

        $pageTitle = htmlspecialchars($proceso['proceso_nombre']);
        ob_start(); require BASE_PATH . '/templates/procesos/detail.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function editarProceso(): void {
        $id = (int)$_POST['proceso_id'];
        $this->safeUpdate('proc_procesos', [
            'proceso_nombre' => $_POST['nombre'],
            'proceso_descripcion' => $_POST['descripcion'] ?? '',
            'proceso_objetivo' => $_POST['objetivo'] ?? '',
            'proceso_alcance' => $_POST['alcance'] ?? '',
            'proceso_responsable_id' => $_POST['responsable_id'] ? (int)$_POST['responsable_id'] : null,
        ], 'proceso_id = ?', [$id]);
        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'editar', 'procesos', 'proceso', $id); header('Location: /procesos/ver/' . $id . '?updated=p');
        exit;
    }

    // ========================================================================
    // CRUD PROCEDIMIENTO
    // ========================================================================
    public function crearProcedimiento(): void {
        $procesoId = (int)$_POST['proceso_id'];
        $this->safeInsert('proc_procedimientos', [
            'procedimiento_proceso_id' => $procesoId,
            'procedimiento_codigo' => $_POST['codigo'] ?? '',
            'procedimiento_nombre' => $_POST['nombre'],
            'procedimiento_descripcion' => $_POST['descripcion'] ?? '',
            'procedimiento_objetivo' => $_POST['objetivo'] ?? '',
            'procedimiento_orden' => (int)($_POST['orden'] ?? 0),
        ]);
        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'crear', 'procesos', 'procedimiento', EstrateGiaCore::getInstance()->getPDO()->lastInsertId()); header('Location: /procesos/ver/' . $procesoId . '?created=pr');
        exit;
    }

    // ========================================================================
    // CRUD TAREA
    // ========================================================================
    public function crearTarea(): void {
        $procesoId = (int)$_POST['proceso_id'];
        $this->safeInsert('proc_tareas', [
            'tarea_procedimiento_id' => $_POST['procedimiento_id'] ? (int)$_POST['procedimiento_id'] : null,
            'tarea_proceso_id' => $procesoId,
            'tarea_codigo' => $_POST['codigo'] ?? '',
            'tarea_nombre' => $_POST['nombre'],
            'tarea_descripcion' => $_POST['descripcion'] ?? '',
            'tarea_orden' => (int)($_POST['orden'] ?? 0),
            'tarea_tipo' => $_POST['tipo_tarea'] ?? 'manual',
            'tarea_tiempo_estimado_minutos' => $_POST['tiempo_estimado'] ? (int)$_POST['tiempo_estimado'] : null,
            'tarea_responsable_id' => $_POST['responsable_id'] ? (int)$_POST['responsable_id'] : null,
        ]);
        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'crear', 'procesos', 'tarea', EstrateGiaCore::getInstance()->getPDO()->lastInsertId()); header('Location: /procesos/ver/' . $procesoId . '?created=t');
        exit;
    }

    public function eliminarTarea(): void {
        $id = (int)$_POST['tarea_id'];
        $procesoId = (int)$_POST['proceso_id'];
        $this->safeUpdate('proc_tareas', ['tarea_activo' => 0], 'tarea_id = ?', [$id]);
        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'eliminar', 'procesos', 'tarea', $id); header('Location: /procesos/ver/' . $procesoId . '?deleted=t');
        exit;
    }

    // ========================================================================
    // WORKFLOWS
    // ========================================================================
    public function workflows(): void {
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);

        $workflows = $this->safeAll(
            "SELECT w.*, p.proceso_nombre, p.proceso_codigo,
                    m.macro_nombre, m.macro_tipo
             FROM proc_workflows w
             JOIN proc_procesos p ON w.workflow_proceso_id = p.proceso_id
             JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id
             WHERE w.workflow_activo = 1 AND m.macro_empresa_id = ?
             ORDER BY m.macro_tipo, p.proceso_nombre, w.workflow_nombre",
            [$empresaId]
        );

        $pageTitle = 'Workflows de Procesos';
        ob_start(); require BASE_PATH . '/templates/procesos/workflows.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }
}
