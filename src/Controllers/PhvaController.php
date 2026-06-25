<?php
declare(strict_types=1);

require_once BASE_PATH . '/lib/SafeQuery.php';

class PhvaController {
    use \SafeQuery;
    
    private $core;
    
    private array $fases = [
        'P' => [
            'nombre' => 'PLANEAR',
            'color' => '#1a73e8',
            'icon' => 'lightbulb',
            'modulos' => [
                'planeacion' => ['nombre' => 'Planeación Estratégica', 'url' => '/planeacion', 'peso' => 30],
                'ia' => ['nombre' => 'IA Asistente', 'url' => '/ia', 'peso' => 10],
                'calendario' => ['nombre' => 'Calendario', 'url' => '/calendario', 'peso' => 5],
            ]
        ],
        'H' => [
            'nombre' => 'HACER',
            'color' => '#28a745',
            'icon' => 'cogs',
            'modulos' => [
                'indicadores' => ['nombre' => 'Indicadores KPI', 'url' => '/indicadores', 'peso' => 20],
                'procesos' => ['nombre' => 'Procesos', 'url' => '/procesos', 'peso' => 15],
                'documentos' => ['nombre' => 'Documentos ISO', 'url' => '/documentos', 'peso' => 10],
                'sst' => ['nombre' => 'SST (Decreto 1072)', 'url' => '/sst', 'peso' => 10],
                'ambiental' => ['nombre' => 'Ambiental (ISO 14001)', 'url' => '/ambiental', 'peso' => 10],
            ]
        ],
        'V' => [
            'nombre' => 'VERIFICAR',
            'color' => '#ffc107',
            'icon' => 'search',
            'modulos' => [
                'evaluacion' => ['nombre' => 'Evaluación Desempeño', 'url' => '/evaluacion', 'peso' => 20],
                'calidad' => ['nombre' => 'Calidad / Acreditación', 'url' => '/calidad', 'peso' => 15],
                'pamec' => ['nombre' => 'PAMEC', 'url' => '/calidad/pamec', 'peso' => 10],
                'satisfaccion' => ['nombre' => 'Satisfacción', 'url' => '/satisfaccion', 'peso' => 10],
            ]
        ],
        'A' => [
            'nombre' => 'ACTUAR',
            'color' => '#dc3545',
            'icon' => 'wrench',
            'modulos' => [
                'nc' => ['nombre' => 'No Conformidades', 'url' => '/nc', 'peso' => 20],
                'riesgos' => ['nombre' => 'Riesgos', 'url' => '/calidad/riesgos', 'peso' => 15],
                'proveedores' => ['nombre' => 'Proveedores', 'url' => '/proveedores', 'peso' => 10],
                'soporte' => ['nombre' => 'Soporte / Mejora', 'url' => '/soporte', 'peso' => 10],
            ]
        ],
    ];
    
    private array $mapeoVerif = [
        'planeacion' => 'plan_tiene_objetivos',
        'indicadores' => 'ind_tiene_mediciones',
        'evaluacion' => 'eval_tiene_datos',
        'calidad' => 'cal_tiene_autoevaluacion',
        'sst' => 'sst_tiene_autoevaluacion',
        'ambiental' => 'amb_tiene_autoevaluacion',
        'nc' => 'nc_tiene_registros',
        'documentos' => 'doc_tiene_registros',
        'procesos' => 'proc_tiene_registros',
    ];
    
    public function __construct() {
        Auth::guard();
        $this->core = EstrateGiaCore::getInstance();
    }
    
    public function index(): void {
        $eid = (int)($_COOKIE['empresa_activa'] ?? 1);
        $planId = (int)($_COOKIE['plan_activo'] ?? 0);
        
        $resumen = [];
        foreach ($this->fases as $faseKey => $fase) {
            $totalPeso = 0;
            $pesoCumplido = 0;
            $modulosData = [];
            
            foreach ($fase['modulos'] as $modKey => $mod) {
                $tieneDatos = $this->verificarModulo($modKey, $eid, $planId);
                $peso = $mod['peso'];
                $totalPeso += $peso;
                if ($tieneDatos) $pesoCumplido += $peso;
                
                $modulosData[] = [
                    'key' => $modKey,
                    'nombre' => $mod['nombre'],
                    'url' => $mod['url'],
                    'peso' => $peso,
                    'tiene_datos' => $tieneDatos,
                    'registros' => $this->contarRegistros($modKey, $eid, $planId),
                ];
            }
            
            $porcentaje = $totalPeso > 0 ? round(($pesoCumplido / $totalPeso) * 100) : 0;
            
            $resumen[$faseKey] = [
                'nombre' => $fase['nombre'],
                'color' => $fase['color'],
                'icon' => $fase['icon'],
                'porcentaje' => $porcentaje,
                'modulos' => $modulosData,
            ];
        }
        
        $porcentajeGeneral = round(array_sum(array_column($resumen, 'porcentaje')) / count($resumen));
        
        $brechas = $this->generarAcciones($resumen, $eid, $planId);
        
        $pageTitle = 'Ciclo PHVA — Mejora Continua';
        ob_start();
        require BASE_PATH . '/templates/phva/index.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }
    
    private function verificarModulo(string $modulo, int $eid, int $planId): bool {
        return $this->contarRegistros($modulo, $eid, $planId) > 0;
    }
    
    private function contarRegistros(string $modulo, int $eid, int $planId): int {
        $tablas = [
            'planeacion' => ["SELECT COUNT(*) FROM plan_planes WHERE plan_empresa_id=? AND plan_estado IN('ejecucion','en_proceso','aprobado','completado')", [$eid]],
            'indicadores' => ["SELECT COUNT(*) FROM ind_indicadores WHERE indicador_plan_id=?", [$planId]],
            'evaluacion' => ["SELECT COUNT(*) FROM ind_evaluaciones_desempeno WHERE evaluacion_usuario_id IN (SELECT usuario_id FROM sys_usuarios WHERE usuario_activo=1)"],
            'calidad' => ["SELECT COUNT(*) FROM cal_autoevaluaciones WHERE id_empresa=?", [$eid]],
            'pamec' => ["SELECT COUNT(*) FROM cal_pamec WHERE id_empresa=?", [$eid]],
            'satisfaccion' => ["SELECT COUNT(*) FROM cal_satisfaccion WHERE id_empresa=?", [$eid]],
            'sst' => ["SELECT COUNT(*) FROM sst_autoevaluaciones WHERE id_empresa=?", [$eid]],
            'ambiental' => ["SELECT COUNT(*) FROM amb_autoevaluaciones WHERE id_empresa=?", [$eid]],
            'nc' => ["SELECT COUNT(*) FROM cal_no_conformidades WHERE id_empresa=?", [$eid]],
            'riesgos' => ["SELECT COUNT(*) FROM cal_riesgos WHERE id_empresa=?", [$eid]],
            'documentos' => ["SELECT COUNT(*) FROM doc_documentos WHERE id_empresa=?", [$eid]],
            'procesos' => ["SELECT COUNT(*) FROM proc_procesos WHERE id_empresa=?", [$eid]],
            'proveedores' => ["SELECT COUNT(*) FROM prov_proveedores WHERE id_empresa=?", [$eid]],
            'soporte' => ["SELECT COUNT(*) FROM soporte_tickets WHERE id_empresa=?", [$eid]],
            'ia' => [0, []],
            'calendario' => [0, []],
        ];
        
        if (!isset($tablas[$modulo])) return 0;
        [$sql, $params] = $tablas[$modulo];
        if ($sql === 0) return 1;
        
        try {
            return (int)$this->safe($sql, $params);
        } catch (\Throwable $e) {
            return 0;
        }
    }
    
    private function generarAcciones(array $resumen, int $eid, int $planId): array {
        $acciones = [];
        
        foreach ($resumen as $faseKey => $fase) {
            foreach ($fase['modulos'] as $mod) {
                if (!$mod['tiene_datos']) {
                    $acciones[] = [
                        'fase' => $fase['nombre'],
                        'color' => $fase['color'],
                        'modulo' => $mod['nombre'],
                        'url' => $mod['url'],
                        'accion' => $this->sugerirAccion($mod['key']),
                        'prioridad' => $faseKey === 'P' ? 'ALTA' : ($faseKey === 'H' ? 'MEDIA' : 'BAJA'),
                    ];
                }
            }
        }
        
        usort($acciones, fn($a, $b) => ['ALTA' => 0, 'MEDIA' => 1, 'BAJA' => 2][$a['prioridad']] - ['ALTA' => 0, 'MEDIA' => 1, 'BAJA' => 2][$b['prioridad']]);
        
        return $acciones;
    }
    
    private function sugerirAccion(string $modulo): string {
        $sugerencias = [
            'planeacion' => 'Crear un plan estratégico con al menos una metodología',
            'indicadores' => 'Registrar indicadores KPI vinculados a objetivos',
            'evaluacion' => 'Realizar evaluación de desempeño del período',
            'calidad' => 'Completar autoevaluación de calidad',
            'pamec' => 'Crear ficha PAMEC con acciones de mejora',
            'satisfaccion' => 'Registrar encuesta de satisfacción',
            'sst' => 'Completar autoevaluación Decreto 1072',
            'ambiental' => 'Completar autoevaluación ISO 14001',
            'nc' => 'Registrar no conformidades detectadas',
            'riesgos' => 'Identificar y calificar riesgos',
            'documentos' => 'Cargar documentos del sistema de gestión',
            'procesos' => 'Documentar macroprocesos y procesos',
            'proveedores' => 'Registrar proveedores y evaluaciones',
            'soporte' => 'Crear ticket de soporte si hay incidencias',
            'ia' => 'Usar IA Asistente para generar contenido estratégico',
            'calendario' => 'Registrar actividades en el calendario',
        ];
        return $sugerencias[$modulo] ?? 'Configurar módulo';
    }
}
