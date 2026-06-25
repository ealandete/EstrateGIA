<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class AcreditacionController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

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
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);
        $pamec = $this->safeAll(
            "SELECT pa.*, p.proceso_nombre FROM cal_pamec_auditorias pa LEFT JOIN proc_procesos p ON pa.pamec_proceso_id=p.proceso_id WHERE pa.pamec_empresa_id=? ORDER BY pa.pamec_fecha_programada DESC",
            [$empresaId]
        );
        $procesos = $this->safeAll('SELECT p.proceso_id, p.proceso_nombre FROM proc_procesos p JOIN proc_macroprocesos m ON p.proceso_macro_id=m.macro_id WHERE m.macro_empresa_id=? AND p.proceso_activo=1', [$empresaId]);
        $pageTitle = 'PAMEC - Auditorías';
        ob_start(); require BASE_PATH . '/templates/calidad/pamec.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
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
}
