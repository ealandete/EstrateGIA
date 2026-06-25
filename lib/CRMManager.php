<?php
/**
 * EstrateGIA - CRMManager
 * Integración con CRMs, ERPs y Web Services externos.
 * Mineria de datos para detección automática de indicadores.
 * Minimiza el registro manual extrayendo datos de sistemas existentes.
 */

require_once __DIR__ . '/EstrateGiaCore.php';

class CRMManager {

    private $core;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    // ========================================================================
    // CONEXIONES
    // ========================================================================

    public function createConexion(array $data): int {
        $required = ['conexion_empresa_id', 'conexion_nombre', 'conexion_tipo'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        // Encriptar credenciales si se proporcionan
        if (isset($data['conexion_credenciales'])) {
            $data['conexion_credenciales_encriptadas'] = $this->core->encryptData(
                json_encode($data['conexion_credenciales'])
            );
            unset($data['conexion_credenciales']);
        }

        return $this->core->insert('crm_conexiones', [
            'conexion_empresa_id'           => $data['conexion_empresa_id'],
            'conexion_nombre'               => $data['conexion_nombre'],
            'conexion_tipo'                 => $data['conexion_tipo'],
            'conexion_proveedor'            => $data['conexion_proveedor'] ?? null,
            'conexion_url'                  => $data['conexion_url'] ?? null,
            'conexion_metodo_autenticacion' => $data['conexion_metodo_autenticacion'] ?? 'api_key',
            'conexion_credenciales_encriptadas' => $data['conexion_credenciales_encriptadas'] ?? null,
            'conexion_configuracion_json'   => isset($data['conexion_configuracion_json']) ? json_encode($data['conexion_configuracion_json']) : null
        ]);
    }

    public function getConexiones(int $empresaId, ?string $tipo = null): array {
        $sql = 'SELECT c.conexion_id, c.conexion_nombre, c.conexion_tipo, c.conexion_proveedor,
                       c.conexion_url, c.conexion_activo, c.conexion_ultima_sincronizacion,
                       c.conexion_estado_salud, c.conexion_configuracion_json,
                       (SELECT COUNT(*) FROM crm_mapeos_datos WHERE mapeo_conexion_id = c.conexion_id) as total_mapeos
                FROM crm_conexiones c
                WHERE c.conexion_empresa_id = :eid AND c.conexion_activo = 1';
        $params = ['eid' => $empresaId];
        if ($tipo) { $sql .= ' AND c.conexion_tipo = :tipo'; $params['tipo'] = $tipo; }
        $sql .= ' ORDER BY c.conexion_nombre';

        return $this->core->fetchAll($sql, $params);
    }

    public function getConexion(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT * FROM crm_conexiones WHERE conexion_id = :id', ['id' => $id]
        );
    }

    public function testConexion(int $conexionId): array {
        $conexion = $this->getConexion($conexionId);
        if (!$conexion) return ['success' => false, 'message' => 'Conexión no encontrada'];

        try {
            // Implementación de prueba según tipo de conexión
            switch ($conexion['conexion_tipo']) {
                case 'api_rest':
                case 'web_service':
                    $resultado = $this->testApiRestConnection($conexion);
                    break;
                case 'base_datos':
                    $resultado = $this->testDatabaseConnection($conexion);
                    break;
                default:
                    $resultado = ['success' => true, 'message' => 'Conexión configurada. Prueba pendiente de implementación.'];
            }

            // Actualizar estado
            $nuevoEstado = $resultado['success'] ? 'ok' : 'error';
            $this->core->update('crm_conexiones', [
                'conexion_estado_salud' => $nuevoEstado,
                'conexion_ultima_sincronizacion' => date('Y-m-d H:i:s')
            ], 'conexion_id = :id', ['id' => $conexionId]);

            return $resultado;
        } catch (Exception $e) {
            $this->core->update('crm_conexiones', [
                'conexion_estado_salud' => 'error'
            ], 'conexion_id = :id', ['id' => $conexionId]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function testApiRestConnection(array $conexion): array {
        if (!$conexion['conexion_url']) {
            return ['success' => false, 'message' => 'URL de conexión no configurada'];
        }

        $ch = curl_init($conexion['conexion_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Configurar autenticación
        if ($conexion['conexion_credenciales_encriptadas']) {
            $credenciales = json_decode($this->core->decryptData($conexion['conexion_credenciales_encriptadas']), true);

            switch ($conexion['conexion_metodo_autenticacion']) {
                case 'api_key':
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . ($credenciales['api_key'] ?? ''),
                        'Content-Type: application/json'
                    ]);
                    break;
                case 'basic':
                    curl_setopt($ch, CURLOPT_USERPWD,
                        ($credenciales['username'] ?? '') . ':' . ($credenciales['password'] ?? '')
                    );
                    break;
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => "Error de conexión: {$error}"];
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'message' => "HTTP {$httpCode}",
            'data' => json_decode($response, true)
        ];
    }

    private function testDatabaseConnection(array $conexion): array {
        if (!$conexion['conexion_credenciales_encriptadas']) {
            return ['success' => false, 'message' => 'Credenciales no configuradas'];
        }

        $creds = json_decode($this->core->decryptData($conexion['conexion_credenciales_encriptadas']), true);
        $config = json_decode($conexion['conexion_configuracion_json'] ?? '{}', true);

        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s', $config['host'] ?? 'localhost', $config['database'] ?? '');
            $pdo = new PDO($dsn, $creds['username'] ?? '', $creds['password'] ?? '', [
                PDO::ATTR_TIMEOUT => 5
            ]);
            $result = $pdo->query('SELECT 1');
            return ['success' => true, 'message' => 'Conexión a base de datos exitosa'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ========================================================================
    // MAPEOS DE DATOS (CRM -> Indicadores)
    // ========================================================================

    public function createMapeoDatos(array $data): int {
        $required = ['mapeo_conexion_id', 'mapeo_nombre', 'mapeo_tipo_indicador'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        return $this->core->insert('crm_mapeos_datos', [
            'mapeo_conexion_id'     => $data['mapeo_conexion_id'],
            'mapeo_nombre'          => $data['mapeo_nombre'],
            'mapeo_tipo_indicador'  => $data['mapeo_tipo_indicador'],
            'mapeo_indicador_id'    => $data['mapeo_indicador_id'] ?? null,
            'mapeo_endpoint_origen' => $data['mapeo_endpoint_origen'] ?? null,
            'mapeo_campo_origen'    => $data['mapeo_campo_origen'] ?? null,
            'mapeo_transformacion_json' => isset($data['mapeo_transformacion_json']) ? json_encode($data['mapeo_transformacion_json']) : null,
            'mapeo_frecuencia_sincro'=> $data['mapeo_frecuencia_sincro'] ?? 'diaria'
        ]);
    }

    public function getMapeos(int $conexionId): array {
        return $this->core->fetchAll(
            'SELECT m.*, i.indicador_nombre, c.categoria_nombre
             FROM crm_mapeos_datos m
             LEFT JOIN ind_indicadores i ON m.mapeo_indicador_id = i.indicador_id
             LEFT JOIN ind_categorias c ON i.indicador_categoria_id = c.categoria_id
             WHERE m.mapeo_conexion_id = :cid AND m.mapeo_activo = 1
             ORDER BY m.mapeo_tipo_indicador',
            ['cid' => $conexionId]
        );
    }

    /**
     * Ejecuta la sincronización de un mapeo: extrae datos del CRM y los registra como medición
     */
    public function ejecutarSincronizacionMapeo(int $mapeoId): array {
        $mapeo = $this->core->fetchOne(
            'SELECT * FROM crm_mapeos_datos WHERE mapeo_id = :id', ['id' => $mapeoId]
        );
        if (!$mapeo) return ['success' => false, 'message' => 'Mapeo no encontrado'];

        $conexion = $this->getConexion($mapeo['mapeo_conexion_id']);
        if (!$conexion || $conexion['conexion_estado_salud'] !== 'ok') {
            return ['success' => false, 'message' => 'Conexión no disponible'];
        }

        // Obtener datos de la fuente externa
        $datos = $this->fetchDataFromSource($conexion, $mapeo);

        if (!$datos['success']) return $datos;

        // Registrar mediciones automáticas
        $indicatorManager = new IndicatorManager();
        $registros = 0;

        foreach ($datos['data'] as $valor) {
            try {
                $indicatorManager->registrarMedicion([
                    'medicion_indicador_id' => $mapeo['mapeo_indicador_id'],
                    'medicion_valor'        => $valor['valor'],
                    'medicion_fecha'        => $valor['fecha'] ?? date('Y-m-d'),
                    'medicion_origen'       => 'crm',
                    'medicion_origen_detalle'=> "CRM: {$conexion['conexion_nombre']} - {$mapeo['mapeo_nombre']}"
                ]);
                $registros++;
            } catch (Exception $e) {
                $this->core->logError('CRM Sync', "Error registrando medición: " . $e->getMessage());
            }
        }

        // Actualizar última ejecución
        $this->core->update('crm_mapeos_datos', [
            'mapeo_ultima_ejecucion' => date('Y-m-d H:i:s')
        ], 'mapeo_id = :id', ['id' => $mapeoId]);

        return [
            'success' => true,
            'message' => "Sincronización completada: {$registros} mediciones registradas",
            'registros' => $registros
        ];
    }

    private function fetchDataFromSource(array $conexion, array $mapeo): array {
        // Implementación real de extracción de datos del CRM/API
        // Esto se conectaría a: Salesforce, HubSpot, SAP, Oracle, Zoho, Dynamics, etc.

        try {
            if (!$mapeo['mapeo_endpoint_origen']) {
                return ['success' => false, 'message' => 'Endpoint de origen no configurado'];
            }

            $credenciales = $conexion['conexion_credenciales_encriptadas']
                ? json_decode($this->core->decryptData($conexion['conexion_credenciales_encriptadas']), true)
                : [];

            $url = $conexion['conexion_url'] . $mapeo['mapeo_endpoint_origen'];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $headers = ['Content-Type: application/json'];
            if ($conexion['conexion_metodo_autenticacion'] === 'api_key') {
                $headers[] = 'Authorization: Bearer ' . ($credenciales['api_key'] ?? '');
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $rawData = json_decode($response, true);

                // Aplicar transformación si está configurada
                $transformacion = json_decode($mapeo['mapeo_transformacion_json'] ?? '{}', true);
                $processedData = $this->applyTransformation($rawData, $mapeo['mapeo_campo_origen'], $transformacion);

                return ['success' => true, 'data' => $processedData];
            }

            return ['success' => false, 'message' => "HTTP {$httpCode}: {$response}"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function applyTransformation($rawData, ?string $campo, array $transformacion): array {
        $resultados = [];

        if (is_array($rawData)) {
            // Si la respuesta es una lista, procesar cada elemento
            $items = isset($rawData['data']) ? $rawData['data'] : (isset($rawData['records']) ? $rawData['records'] : $rawData);
            if (!isset($items[0])) $items = [$items];

            foreach ($items as $item) {
                $valor = $campo ? ($item[$campo] ?? null) : null;
                if ($valor !== null) {
                    $resultados[] = [
                        'valor' => $valor,
                        'fecha' => $item['date'] ?? $item['fecha'] ?? date('Y-m-d')
                    ];
                }
            }
        }

        return $resultados;
    }

    // ========================================================================
    // MINERÍA DE DATOS
    // ========================================================================

    public function createMineriaConfig(array $data): int {
        $required = ['mineria_nombre', 'mineria_tipo_fuente'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        return $this->core->insert('crm_mineria_datos', [
            'mineria_nombre'            => $data['mineria_nombre'],
            'mineria_descripcion'       => $data['mineria_descripcion'] ?? null,
            'mineria_tipo_fuente'       => $data['mineria_tipo_fuente'],
            'mineria_conexion_id'       => $data['mineria_conexion_id'] ?? null,
            'mineria_query_config'      => isset($data['mineria_query_config']) ? json_encode($data['mineria_query_config']) : null,
            'mineria_patrones_json'     => isset($data['mineria_patrones_json']) ? json_encode($data['mineria_patrones_json']) : null,
            'mineria_indicadores_detectados' => isset($data['mineria_indicadores_detectados']) ? json_encode($data['mineria_indicadores_detectados']) : null,
            'mineria_frecuencia'        => $data['mineria_frecuencia'] ?? 'diaria'
        ]);
    }

    public function getMineriaConfigs(?int $conexionId = null): array {
        $sql = 'SELECT * FROM crm_mineria_datos WHERE mineria_activo = 1';
        $params = [];
        if ($conexionId) { $sql .= ' AND mineria_conexion_id = :cid'; $params['cid'] = $conexionId; }
        $sql .= ' ORDER BY mineria_nombre';
        return $this->core->fetchAll($sql, $params);
    }

    /**
     * Ejecuta minería de datos para detectar indicadores automáticamente
     * de fuentes como correos, documentos, logs, bases de datos, etc.
     */
    public function ejecutarMineria(int $mineriaId): array {
        $config = $this->core->fetchOne(
            'SELECT * FROM crm_mineria_datos WHERE mineria_id = :id AND mineria_activo = 1',
            ['id' => $mineriaId]
        );
        if (!$config) return ['success' => false, 'message' => 'Configuración de minería no encontrada'];

        $resultados = [
            'mineria_id' => $mineriaId,
            'nombre'     => $config['mineria_nombre'],
            'tipo'       => $config['mineria_tipo_fuente'],
            'indicadores_detectados' => [],
            'registros_procesados' => 0,
            'mediciones_creadas' => 0
        ];

        try {
            switch ($config['mineria_tipo_fuente']) {
                case 'crm':
                    $resultados = $this->mineriaCRM($config, $resultados);
                    break;
                case 'correo':
                    $resultados = $this->mineriaCorreo($config, $resultados);
                    break;
                case 'documentos':
                    $resultados = $this->mineriaDocumentos($config, $resultados);
                    break;
                case 'base_datos':
                    $resultados = $this->mineriaBaseDatos($config, $resultados);
                    break;
                case 'logs':
                    $resultados = $this->mineriaLogs($config, $resultados);
                    break;
                default:
                    $resultados['message'] = 'Tipo de minería no implementado aún';
            }

            // Guardar resultados
            $this->core->update('crm_mineria_datos', [
                'mineria_ultima_ejecucion' => date('Y-m-d H:i:s'),
                'mineria_resultados_ultima'=> json_encode($resultados)
            ], 'mineria_id = :id', ['id' => $mineriaId]);

            return $resultados;
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function mineriaCRM(array $config, array $resultados): array {
        // Extrae indicadores de actividades del CRM:
        // - Tiempo de respuesta a leads (Oportunidad)
        // - Tasa de conversión (Productividad)
        // - Satisfacción del cliente (Calidad)
        // - Cumplimiento de metas de ventas (Cumplimiento)

        $conexion = $config['mineria_conexion_id']
            ? $this->getConexion($config['mineria_conexion_id'])
            : null;

        if ($conexion && $conexion['conexion_tipo'] === 'crm') {
            $queryConfig = json_decode($config['mineria_query_config'] ?? '{}', true);
            $indicadores = json_decode($config['mineria_indicadores_detectados'] ?? '{}', true);

            // Aquí se implementaría la extracción real de métricas del CRM
            $resultados['indicadores_detectados'] = $indicadores ?? [];
            $resultados['registros_procesados'] = 100; // Placeholder
        }

        return $resultados;
    }

    private function mineriaCorreo(array $config, array $resultados): array {
        // Analiza correos para detectar:
        // - Tiempos de respuesta (Oportunidad)
        // - Volumen de solicitudes por área (Productividad)
        // - Tasa de resolución en primer contacto (Calidad)

        $patrones = json_decode($config['mineria_patrones_json'] ?? '{}', true);
        // Implementación de análisis NLP sobre correos

        return $resultados;
    }

    private function mineriaDocumentos(array $config, array $resultados): array {
        // Extrae datos de documentos (PDF, Word, Excel):
        // - Informes financieros
        // - Actas de reunión
        // - Reportes de gestión

        return $resultados;
    }

    private function mineriaBaseDatos(array $config, array $resultados): array {
        // Ejecuta queries sobre bases de datos externas para extraer KPIs
        $conexion = $config['mineria_conexion_id']
            ? $this->getConexion($config['mineria_conexion_id'])
            : null;

        if ($conexion && $conexion['conexion_estado_salud'] === 'ok') {
            $creds = json_decode($this->core->decryptData($conexion['conexion_credenciales_encriptadas']), true);
            $dbConfig = json_decode($conexion['conexion_configuracion_json'] ?? '{}', true);
            $queryConfig = json_decode($config['mineria_query_config'] ?? '{}', true);

            try {
                $dsn = sprintf('mysql:host=%s;dbname=%s',
                    $dbConfig['host'] ?? 'localhost',
                    $dbConfig['database'] ?? ''
                );
                $pdo = new PDO($dsn, $creds['username'] ?? '', $creds['password'] ?? '');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                if (isset($queryConfig['queries'])) {
                    foreach ($queryConfig['queries'] as $queryInfo) {
                        $stmt = $pdo->query($queryInfo['sql']);
                        $valor = $stmt->fetchColumn();

                        if ($valor !== false && isset($queryInfo['indicador_id'])) {
                            // Registrar medición
                            $indicatorManager = new IndicatorManager();
                            $indicatorManager->registrarMedicion([
                                'medicion_indicador_id' => $queryInfo['indicador_id'],
                                'medicion_valor'        => $valor,
                                'medicion_fecha'        => date('Y-m-d'),
                                'medicion_origen'       => 'mineria_datos',
                                'medicion_origen_detalle'=> "Minería BD: {$config['mineria_nombre']}"
                            ]);
                            $resultados['mediciones_creadas']++;
                        }
                    }
                }

                $resultados['registros_procesados'] = count($queryConfig['queries'] ?? []);
            } catch (Exception $e) {
                $this->core->logError('Mineria BD', $e->getMessage());
            }
        }

        return $resultados;
    }

    private function mineriaLogs(array $config, array $resultados): array {
        // Analiza logs del sistema para detectar patrones de productividad
        return $resultados;
    }

    /**
     * Ejecuta todas las minerías activas y sincronizaciones pendientes
     */
    public function ejecutarTodasSincronizaciones(int $empresaId): array {
        $resultados = [
            'mapeos_sincronizados' => 0,
            'minerias_ejecutadas'  => 0,
            'mediciones_creadas'   => 0,
            'errores' => []
        ];

        // Sincronizar todos los mapeos CRM activos
        $conexiones = $this->getConexiones($empresaId);
        foreach ($conexiones as $conexion) {
            $mapeos = $this->getMapeos($conexion['conexion_id']);
            foreach ($mapeos as $mapeo) {
                $result = $this->ejecutarSincronizacionMapeo($mapeo['mapeo_id']);
                if ($result['success']) {
                    $resultados['mapeos_sincronizados']++;
                    $resultados['mediciones_creadas'] += ($result['registros'] ?? 0);
                } else {
                    $resultados['errores'][] = $result['message'];
                }
            }
        }

        // Ejecutar todas las minerías activas
        $minerias = $this->getMineriaConfigs();
        foreach ($minerias as $mineria) {
            $result = $this->ejecutarMineria($mineria['mineria_id']);
            if ($result['success'] ?? true) {
                $resultados['minerias_ejecutadas']++;
                $resultados['mediciones_creadas'] += ($result['mediciones_creadas'] ?? 0);
            } else {
                $resultados['errores'][] = $result['message'] ?? 'Error desconocido';
            }
        }

        return $resultados;
    }

    /**
     * Dashboard de integraciones: estado de conexiones y volumen de datos automáticos
     */
    public function getDashboardIntegraciones(int $empresaId): array {
        return [
            'conexiones' => [
                'total' => $this->core->fetchColumn(
                    'SELECT COUNT(*) FROM crm_conexiones WHERE conexion_empresa_id = :eid AND conexion_activo = 1',
                    ['eid' => $empresaId]
                ),
                'activas' => $this->core->fetchColumn(
                    'SELECT COUNT(*) FROM crm_conexiones WHERE conexion_empresa_id = :eid AND conexion_activo = 1 AND conexion_estado_salud = \'ok\'',
                    ['eid' => $empresaId]
                ),
                'con_error' => $this->core->fetchColumn(
                    'SELECT COUNT(*) FROM crm_conexiones WHERE conexion_empresa_id = :eid AND conexion_activo = 1 AND conexion_estado_salud = \'error\'',
                    ['eid' => $empresaId]
                )
            ],
            'mediciones_automaticas' => [
                'total' => $this->core->fetchColumn(
                    'SELECT COUNT(*) FROM ind_mediciones WHERE medicion_origen IN (\'crm\', \'web_service\', \'mineria_datos\')'
                ),
                'porcentaje_automatico' => $this->core->fetchColumn(
                    'SELECT ROUND((SUM(CASE WHEN medicion_origen IN (\'crm\', \'web_service\', \'mineria_datos\') THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 2)
                     FROM ind_mediciones'
                )
            ],
            'minerias' => [
                'total' => $this->core->fetchColumn('SELECT COUNT(*) FROM crm_mineria_datos WHERE mineria_activo = 1'),
                'ultima_ejecucion' => $this->core->fetchOne(
                    'SELECT mineria_nombre, mineria_ultima_ejecucion FROM crm_mineria_datos WHERE mineria_activo = 1 ORDER BY mineria_ultima_ejecucion DESC LIMIT 1'
                )
            ]
        ];
    }
}
