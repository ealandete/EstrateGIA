<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class CalendarioController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);

        $mes = (int)($_GET['mes'] ?? date('m'));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        $modulo = $_GET['modulo'] ?? '';
        $vista = $_GET['vista'] ?? 'mes';

        // Rango amplio para cubrir el año completo
        $primerDia = strtotime("$anio-$mes-01");
        $diasEnMes = date('t', $primerDia);
        $diaSemanaInicio = date('N', $primerDia);
        $desde = "$anio-01-01";
        $hasta = "$anio-12-31";

        // Tareas del año completo (para cubrir todas las vistas)
        $desde = "$anio-01-01";
        $hasta = "$anio-12-31";
        $sql = "SELECT t.*, CONCAT(u.usuario_nombre,' ',u.usuario_apellido) as responsable_nombre,
                    pt.tarea_titulo as padre_titulo
             FROM cal_tareas t
             LEFT JOIN sys_usuarios u ON t.tarea_responsable_id=u.usuario_id
             LEFT JOIN cal_tareas pt ON t.tarea_padre_id=pt.tarea_id
             WHERE t.tarea_empresa_id=?
               AND t.tarea_fecha_inicio <= ?
               AND t.tarea_fecha_fin >= ?";
        $tareas = $this->safeAll($sql, [$empresaId, $desde, $hasta]);
        if ($modulo) $tareas = array_filter($tareas, fn($t)=>$t['tarea_modulo']===$modulo);

        // Agrupar por fecha para el calendario
        $porFecha = [];
        foreach ($tareas as $t) {
            $fecha = $t['tarea_fecha_inicio'];
            if ($fecha) $porFecha[$fecha][] = $t;
        }

        // Agrupar tareas principales con sus subtareas
        $tareasPrincipales = array_filter($tareas, fn($t)=>$t['tarea_nivel']==1);
        foreach ($tareasPrincipales as &$tp) {
            $tp['subtareas'] = array_filter($tareas, fn($t)=>$t['tarea_padre_id']==$tp['tarea_id']);
        }

        // Navegación
        $mesAnterior = mktime(0,0,0,$mes-1,1,$anio);
        $mesSiguiente = mktime(0,0,0,$mes+1,1,$anio);

        // Estadísticas por mes para vista año
        $statsAnual = [];
        for ($m=1; $m<=12; $m++) {
            $minicio = "$anio-".str_pad((string)$m,2,'0',STR_PAD_LEFT)."-01";
            $mfin = date('Y-m-t', strtotime($minicio));
            $statsAnual[$m] = [
                'total' => count(array_filter($tareas, fn($t)=> ($t['tarea_fecha_inicio']??'')<=$mfin && ($t['tarea_fecha_fin']??'')>=$minicio)),
                'completadas' => count(array_filter($tareas, fn($t)=> $t['tarea_estado']==='completada' && ($t['tarea_fecha_inicio']??'')<=$mfin && ($t['tarea_fecha_fin']??'')>=$minicio)),
                'vencidas' => count(array_filter($tareas, fn($t)=> $t['tarea_estado']==='vencida' && ($t['tarea_fecha_fin']??'')<=$mfin && ($t['tarea_fecha_fin']??'')>=$minicio)),
            ];
        }

        $moduloColors = ['acreditacion'=>'#1a73e8','pamec'=>'#6f42c1','nc'=>'#dc3545','reportes'=>'#ffc107','riesgos'=>'#fd7e14','general'=>'#888'];
        $moduloIcons = ['acreditacion'=>'certificate','pamec'=>'search','nc'=>'triangle-exclamation','reportes'=>'file-signature','riesgos'=>'bolt','general'=>'circle'];

        $pageTitle = 'Programador';
        ob_start(); require BASE_PATH . '/templates/calendario/index.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }
}
