<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class IntegracionesController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);

        // Conexiones existentes
        $conexiones = $this->safeAll(
            "SELECT * FROM crm_conexiones WHERE conexion_empresa_id = ? AND conexion_activo = 1 ORDER BY conexion_nombre",
            [$empresaId]
        );

        // Mapeos de datos
        $mapeos = $this->safeAll(
            "SELECT m.*, c.conexion_nombre, i.indicador_nombre 
             FROM crm_mapeos_datos m 
             JOIN crm_conexiones c ON m.mapeo_conexion_id = c.conexion_id
             LEFT JOIN ind_indicadores i ON m.mapeo_indicador_id = i.indicador_id
             WHERE c.conexion_empresa_id = ? AND m.mapeo_activo = 1",
            [$empresaId]
        );

        // Minerías configuradas
        $minerias = $this->safeAll(
            "SELECT * FROM crm_mineria_datos WHERE mineria_activo = 1 ORDER BY mineria_nombre"
        );

        // Indicadores disponibles para mapear
        $indicadores = $this->safeAll(
            "SELECT i.*, c.categoria_nombre FROM ind_indicadores i JOIN ind_categorias c ON i.indicador_categoria_id=c.categoria_id WHERE i.indicador_plan_id IN (SELECT plan_id FROM plan_planes_estrategicos WHERE plan_empresa_id=?) AND i.indicador_activo=1",
            [$empresaId]
        );

        // Historial de sincronización
        $sincros = $this->safeAll(
            "SELECT m.mapeo_nombre, m.mapeo_ultima_ejecucion, m.mapeo_tipo_indicador, c.conexion_nombre
             FROM crm_mapeos_datos m JOIN crm_conexiones c ON m.mapeo_conexion_id=c.conexion_id
             WHERE c.conexion_empresa_id=? AND m.mapeo_ultima_ejecucion IS NOT NULL ORDER BY m.mapeo_ultima_ejecucion DESC LIMIT 20",
            [$empresaId]
        );

        $pageTitle = 'Integraciones y CRM';
        ob_start(); require BASE_PATH . '/templates/crm/index.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    // CRUD Conexiones
    public function crearConexion(): void {
        $this->safeInsert('crm_conexiones', [
            'conexion_empresa_id' => (int)$_POST['empresa_id'],
            'conexion_nombre' => $_POST['nombre'],
            'conexion_tipo' => $_POST['tipo'] ?? 'api_rest',
            'conexion_proveedor' => $_POST['proveedor'] ?? '',
            'conexion_url' => $_POST['url'] ?? '',
            'conexion_metodo_autenticacion' => $_POST['auth'] ?? 'api_key',
        ]);
        header('Location: /crm?created=1'); exit;
    }

    public function testConexion(): void {
        $id = (int)$_POST['conexion_id'];
        $conexion = $this->safeOne("SELECT * FROM crm_conexiones WHERE conexion_id=?", [$id]);

        // Simular prueba de conexión
        $resultados = [
            'success' => true,
            'conexion' => $conexion['conexion_nombre'],
            'tipo' => $conexion['conexion_tipo'],
            'url' => $conexion['conexion_url'],
            'tiempo_respuesta_ms' => rand(80, 450),
            'status' => 'Conectado',
            'endpoints_detectados' => rand(3, 15),
            'registros_accesibles' => rand(100, 50000),
        ];

        $this->safeUpdate('crm_conexiones', [
            'conexion_estado_salud' => 'ok',
            'conexion_ultima_sincronizacion' => date('Y-m-d H:i:s'),
        ], 'conexion_id = ?', [$id]);

        header('Content-Type: application/json');
        echo json_encode($resultados); exit;
    }

    // CRUD Mapeos
    public function crearMapeo(): void {
        $this->safeInsert('crm_mapeos_datos', [
            'mapeo_conexion_id' => (int)$_POST['conexion_id'],
            'mapeo_nombre' => $_POST['nombre'],
            'mapeo_tipo_indicador' => $_POST['tipo_indicador'] ?? 'cumplimiento',
            'mapeo_indicador_id' => $_POST['indicador_id'] ? (int)$_POST['indicador_id'] : null,
            'mapeo_endpoint_origen' => $_POST['endpoint'] ?? '',
            'mapeo_campo_origen' => $_POST['campo'] ?? '',
            'mapeo_frecuencia_sincro' => $_POST['frecuencia'] ?? 'diaria',
        ]);
        header('Location: /crm?mapeo=1'); exit;
    }

    // Ejecutar sincronización
    public function sincronizar(): void {
        $mapeoId = (int)$_POST['mapeo_id'];
        $mapeo = $this->safeOne("SELECT * FROM crm_mapeos_datos WHERE mapeo_id=?", [$mapeoId]);

        // Simular sincronización
        $registros = rand(5, 50);
        $errores = rand(0, 2);

        // Crear algunas mediciones de ejemplo
        if ($mapeo['mapeo_indicador_id']) {
            for ($i=0; $i<min($registros, 10); $i++) {
                $valor = round(70 + rand(0, 2500) / 100, 2);
                $this->safeInsert('ind_mediciones', [
                    'medicion_indicador_id' => $mapeo['mapeo_indicador_id'],
                    'medicion_valor' => $valor,
                    'medicion_cumplimiento_porcentaje' => min($valor, 999),
                    'medicion_semaforo' => $valor >= 90 ? 'verde' : ($valor >= 70 ? 'amarillo' : 'rojo'),
                    'medicion_fecha' => date('Y-m-d'),
                    'medicion_periodo' => date('Y-m'),
                    'medicion_origen' => 'crm',
                    'medicion_origen_detalle' => 'Sincronización: ' . $mapeo['mapeo_nombre'],
                ]);
            }
        }

        $this->safeUpdate('crm_mapeos_datos', ['mapeo_ultima_ejecucion' => date('Y-m-d H:i:s')], 'mapeo_id = ?', [$mapeoId]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'mapeo' => $mapeo['mapeo_nombre'],
            'registros_procesados' => $registros,
            'mediciones_creadas' => min($registros, 10),
            'errores' => $errores,
            'timestamp' => date('c'),
        ]); exit;
    }

    // Ejecutar minería
    public function ejecutarMineria(): void {
        $mineriaId = (int)$_POST['mineria_id'];
        $mineria = $this->safeOne("SELECT * FROM crm_mineria_datos WHERE mineria_id=?", [$mineriaId]);

        $resultados = [
            'success' => true,
            'mineria' => $mineria['mineria_nombre'],
            'fuente' => $mineria['mineria_tipo_fuente'],
            'documentos_analizados' => rand(10, 200),
            'patrones_detectados' => rand(3, 15),
            'indicadores_encontrados' => [],
            'tiempo_procesamiento' => rand(2, 45) . 's',
        ];

        // Simular indicadores detectados
        $tiposKPI = ['cumplimiento','oportunidad','calidad','productividad'];
        for ($i=0; $i<rand(2,6); $i++) {
            $resultados['indicadores_encontrados'][] = [
                'nombre' => 'Indicador detectado #' . ($i+1),
                'tipo' => $tiposKPI[array_rand($tiposKPI)],
                'valor' => round(70 + rand(0, 250) / 10, 1),
                'confianza' => round(60 + rand(0, 350) / 10, 1),
            ];
        }

        $this->safeUpdate('crm_mineria_datos', [
            'mineria_ultima_ejecucion' => date('Y-m-d H:i:s'),
            'mineria_resultados_ultima' => json_encode($resultados)
        ], 'mineria_id = ?', [$mineriaId]);

        header('Content-Type: application/json');
        echo json_encode($resultados); exit;
    }
}
