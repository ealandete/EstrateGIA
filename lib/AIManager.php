<?php
/**
 * EstrateGIA - AIManager
 * Gestión de Inteligencia Artificial para recomendaciones,
 * predicciones, análisis y asistencia en planeación estratégica.
 * Soporta OpenAI, Claude, Gemini y modelos locales.
 */

require_once __DIR__ . '/EstrateGiaCore.php';

class AIManager {

    private $core;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    // ========================================================================
    // MODELOS IA
    // ========================================================================

    public function createModelo(array $data): int {
        $required = ['modelo_nombre', 'modelo_tipo', 'modelo_proveedor'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        return $this->core->insert('ia_modelos', [
            'modelo_nombre'             => $data['modelo_nombre'],
            'modelo_tipo'               => $data['modelo_tipo'],
            'modelo_proveedor'          => $data['modelo_proveedor'],
            'modelo_endpoint'           => $data['modelo_endpoint'] ?? null,
            'modelo_configuracion_json' => isset($data['modelo_configuracion_json']) ? json_encode($data['modelo_configuracion_json']) : null
        ]);
    }

    public function getModelos(?string $tipo = null): array {
        $sql = 'SELECT * FROM ia_modelos WHERE modelo_activo = 1';
        $params = [];
        if ($tipo) { $sql .= ' AND modelo_tipo = :tipo'; $params['tipo'] = $tipo; }
        return $this->core->fetchAll($sql, $params);
    }

    public function getModelo(int $id): ?array {
        return $this->core->fetchOne('SELECT * FROM ia_modelos WHERE modelo_id = :id', ['id' => $id]);
    }

    // ========================================================================
    // ASISTENTE IA (Chat/Asistencia en tiempo real)
    // ========================================================================

    /**
     * Procesa una consulta del usuario al asistente IA
     */
    public function procesarAsistencia(int $usuarioId, string $contexto, string $prompt, ?int $modeloId = null): array {
        $modelo = $modeloId
            ? $this->getModelo($modeloId)
            : $this->getDefaultModelo('asistente');

        if (!$modelo) {
            return ['success' => false, 'message' => 'No hay modelos de asistente IA configurados'];
        }

        $startTime = microtime(true);

        // Construir contexto enriquecido
        $systemPrompt = $this->buildSystemPrompt($contexto, $usuarioId);
        $fullPrompt = $this->buildFullPrompt($contexto, $prompt, $usuarioId);

        // Llamar al proveedor de IA
        $respuesta = $this->callIAProvider($modelo, $systemPrompt, $fullPrompt);

        $tiempoMs = (int)((microtime(true) - $startTime) * 1000);

        // Guardar la interacción
        $this->core->insert('ia_asistencias', [
            'asistencia_modelo_id'      => $modelo['modelo_id'],
            'asistencia_usuario_id'     => $usuarioId,
            'asistencia_contexto'       => $contexto,
            'asistencia_prompt'         => $prompt,
            'asistencia_respuesta'      => $respuesta['texto'],
            'asistencia_tokens_entrada' => $respuesta['tokens_in'] ?? null,
            'asistencia_tokens_salida'  => $respuesta['tokens_out'] ?? null,
            'asistencia_tiempo_respuesta_ms' => $tiempoMs,
            'asistencia_metadata_json'  => isset($respuesta['metadata']) ? json_encode($respuesta['metadata']) : null
        ]);

        return [
            'success'     => true,
            'respuesta'   => $respuesta['texto'],
            'contexto'    => $contexto,
            'modelo'      => $modelo['modelo_nombre'],
            'tiempo_ms'   => $tiempoMs
        ];
    }

    private function buildSystemPrompt(string $contexto, int $usuarioId): string {
        $user = $this->core->fetchOne(
            'SELECT * FROM sys_usuarios WHERE usuario_id = :id', ['id' => $usuarioId]
        );

        $basePrompt = "Eres EstrateGIA, un asistente experto en planeación estratégica empresarial. ";
        $basePrompt .= "Ayudas a organizaciones a definir, implementar y dar seguimiento a su estrategia. ";
        $basePrompt .= "Eres conocedor de metodologías como Balanced Scorecard, OKR, Hoshin Kanri, ";
        $basePrompt .= "Planeación por Escenarios y Design Thinking Estratégico. ";
        $basePrompt .= "También eres experto en normas ISO (9001, 14001, 45001, 7101, 41001, 13485, 31000, 27001). ";
        $basePrompt .= "Tu rol es guiar paso a paso, dar recomendaciones accionables y ayudar a construir ";
        $basePrompt .= "los componentes de la planeación estratégica. ";

        if ($user) {
            $basePrompt .= "Estás asistiendo a {$user['usuario_nombre']} {$user['usuario_apellido']}, ";
            $basePrompt .= "quien tiene el cargo de {$user['usuario_cargo']} ";
            $basePrompt .= "en el departamento de {$user['usuario_departamento']}. ";
        }

        return $basePrompt;
    }

    private function buildFullPrompt(string $contexto, string $prompt, int $usuarioId): string {
        $contextoAdicional = '';

        // Enriquecer con datos del sistema según el contexto
        switch ($contexto) {
            case 'planeacion':
                $contextoAdicional = $this->getContextoPlaneacion($usuarioId);
                break;
            case 'procesos':
                $contextoAdicional = $this->getContextoProcesos($usuarioId);
                break;
            case 'indicadores':
                $contextoAdicional = $this->getContextoIndicadores($usuarioId);
                break;
            case 'documentacion':
                $contextoAdicional = $this->getContextoDocumentacion($usuarioId);
                break;
            case 'evaluacion':
                $contextoAdicional = $this->getContextoEvaluacion($usuarioId);
                break;
        }

        if ($contextoAdicional) {
            return "CONTEXTO DEL SISTEMA:\n{$contextoAdicional}\n\nCONSULTA DEL USUARIO:\n{$prompt}";
        }

        return $prompt;
    }

    private function getContextoPlaneacion(int $usuarioId): string {
        $planes = $this->core->fetchAll(
            'SELECT p.plan_nombre, p.plan_estado, p.plan_avance_porcentaje, m.metodologia_nombre
             FROM plan_planes_estrategicos p
             JOIN plan_metodologias m ON p.plan_metodologia_id = m.metodologia_id
             WHERE p.plan_responsable_id = :uid AND p.plan_activo = 1
             ORDER BY p.created_at DESC LIMIT 5',
            ['uid' => $usuarioId]
        );

        if (empty($planes)) return '';

        $ctx = "Planes estratégicos del usuario:\n";
        foreach ($planes as $plan) {
            $ctx .= "- {$plan['plan_nombre']} (Metodología: {$plan['metodologia_nombre']}, Estado: {$plan['plan_estado']}, Avance: {$plan['plan_avance_porcentaje']}%)\n";
        }
        return $ctx;
    }

    private function getContextoProcesos(int $usuarioId): string {
        $procesos = $this->core->fetchAll(
            'SELECT p.proceso_nombre, p.proceso_estado, m.macro_nombre
             FROM proc_procesos p
             JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id
             WHERE p.proceso_responsable_id = :uid AND p.proceso_activo = 1
             LIMIT 10',
            ['uid' => $usuarioId]
        );
        if (empty($procesos)) return '';
        $ctx = "Procesos del usuario:\n";
        foreach ($procesos as $p) {
            $ctx .= "- {$p['proceso_nombre']} (Macroproceso: {$p['macro_nombre']}, Estado: {$p['proceso_estado']})\n";
        }
        return $ctx;
    }

    private function getContextoIndicadores(int $usuarioId): string {
        $indicadores = $this->core->fetchAll(
            'SELECT i.indicador_nombre, c.categoria_tipo, c.categoria_nombre,
                    (SELECT medicion_valor FROM ind_mediciones WHERE medicion_indicador_id = i.indicador_id ORDER BY medicion_fecha DESC LIMIT 1) as ultimo_valor
             FROM ind_indicadores i
             JOIN ind_categorias c ON i.indicador_categoria_id = c.categoria_id
             WHERE i.indicador_responsable_id = :uid AND i.indicador_activo = 1
             LIMIT 10',
            ['uid' => $usuarioId]
        );
        if (empty($indicadores)) return '';
        $ctx = "Indicadores del usuario:\n";
        foreach ($indicadores as $ind) {
            $ctx .= "- {$ind['indicador_nombre']} ({$ind['categoria_nombre']}, Último valor: {$ind['ultimo_valor']})\n";
        }
        return $ctx;
    }

    private function getContextoDocumentacion(int $usuarioId): string {
        return ''; // Placeholder
    }

    private function getContextoEvaluacion(int $usuarioId): string {
        $eval = $this->core->fetchOne(
            'SELECT * FROM ind_evaluaciones_desempeno WHERE evaluacion_usuario_id = :uid ORDER BY evaluacion_periodo DESC LIMIT 1',
            ['uid' => $usuarioId]
        );
        if (!$eval) return '';
        return "Última evaluación: Periodo {$eval['evaluacion_periodo']}, Puntaje total: {$eval['evaluacion_puntaje_total']}%\n";
    }

    private function getDefaultModelo(string $tipo): ?array {
        return $this->core->fetchOne(
            'SELECT * FROM ia_modelos WHERE modelo_tipo = :tipo AND modelo_activo = 1 ORDER BY modelo_id ASC LIMIT 1',
            ['tipo' => $tipo]
        );
    }

    /**
     * Llama al proveedor de IA (OpenAI, Claude, etc.)
     */
    private function callIAProvider(array $modelo, string $systemPrompt, string $userPrompt): array {
        $config = json_decode($modelo['modelo_configuracion_json'] ?? '{}', true);
        $apiKey = $config['api_key'] ?? getenv('IA_API_KEY') ?? '';

        // Si no hay API key o es modo simulado, usar generador local
        if (($config['simulado'] ?? false) || empty($apiKey)) {
            return $this->callLocalGenerator($modelo, $systemPrompt, $userPrompt);
        }

        switch ($modelo['modelo_proveedor']) {
            case 'OpenAI':
                return $this->callOpenAI($modelo, $apiKey, $systemPrompt, $userPrompt);
            case 'Claude':
                return $this->callClaude($modelo, $apiKey, $systemPrompt, $userPrompt);
            case 'Gemini':
                return $this->callGemini($modelo, $apiKey, $systemPrompt, $userPrompt);
            default:
                return $this->callLocalGenerator($modelo, $systemPrompt, $userPrompt);
        }
    }

    private function callOpenAI(array $modelo, string $apiKey, string $system, string $user): array {
        $model = $config['model'] ?? 'gpt-4';
        $endpoint = $modelo['modelo_endpoint'] ?? 'https://api.openai.com/v1/chat/completions';

        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000
        ]);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'texto'      => $data['choices'][0]['message']['content'] ?? '',
                'tokens_in'  => $data['usage']['prompt_tokens'] ?? 0,
                'tokens_out' => $data['usage']['completion_tokens'] ?? 0,
                'metadata'   => ['model' => $model, 'provider' => 'OpenAI']
            ];
        }

        return ['texto' => "Lo siento, no pude procesar tu consulta en este momento. Error: HTTP {$httpCode}"];
    }

    private function callClaude(array $modelo, string $apiKey, string $system, string $user): array {
        $modelName = $config['model'] ?? 'claude-3-opus-20240229';
        $endpoint = $modelo['modelo_endpoint'] ?? 'https://api.anthropic.com/v1/messages';

        $payload = json_encode([
            'model' => $modelName,
            'max_tokens' => 2000,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $user]]
        ]);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'texto'      => $data['content'][0]['text'] ?? '',
                'tokens_in'  => $data['usage']['input_tokens'] ?? 0,
                'tokens_out' => $data['usage']['output_tokens'] ?? 0,
                'metadata'   => ['model' => $modelName, 'provider' => 'Claude']
            ];
        }

        return ['texto' => "Lo siento, no pude procesar tu consulta en este momento."];
    }

    private function callGemini(array $modelo, string $apiKey, string $system, string $user): array {
        $modelName = $config['model'] ?? 'gemini-pro';
        $endpoint = $modelo['modelo_endpoint'] ?? "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";

        $payload = json_encode([
            'contents' => [
                ['parts' => [['text' => $system . "\n\n" . $user]]]
            ]
        ]);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return [
            'texto' => $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sin respuesta',
            'metadata' => ['model' => $modelName, 'provider' => 'Gemini']
        ];
    }

    private function callLocalGenerator(array $modelo, string $systemPrompt, string $userPrompt): array {
        // Analizar el prompt para determinar qué tipo de contenido generar
        $texto = '';

        if (str_contains($userPrompt, 'MISIÓN')) {
            $texto = $this->generarMisionLocal($userPrompt);
        } elseif (str_contains($userPrompt, 'VISIÓN')) {
            $texto = $this->generarVisionLocal($userPrompt);
        } elseif (str_contains($userPrompt, 'VALORES')) {
            $texto = $this->generarValoresLocal($userPrompt);
        } elseif (str_contains($userPrompt, 'FODA')) {
            $texto = $this->generarFODALocal($userPrompt);
        } elseif (str_contains($userPrompt, 'relaciones causa-efecto') || str_contains($userPrompt, 'Analiza los siguientes objetivos') || str_contains($userPrompt, 'relacion')) {
            $texto = $this->generarBSCRelacionesLocal($userPrompt);
        } elseif (str_contains($userPrompt, 'Evalua el estado') || str_contains($userPrompt, 'Analiza este plan estratégico') || str_contains($userPrompt, 'evaluacion')) {
            $texto = $this->generarEvaluacionLocal($userPrompt);
        } elseif (str_contains($userPrompt, 'KPIs') || str_contains($userPrompt, 'indicadores') || str_contains($userPrompt, 'Indicadores')) {
            $texto = $this->generarIndicadoresKpiLocal($userPrompt);
        } elseif (str_contains($userPrompt, 'iniciativas') || str_contains($userPrompt, 'Iniciativas')) {
            $texto = $this->generarIniciativasLocal($userPrompt);
        } elseif (str_contains($userPrompt, 'BSC') || str_contains($userPrompt, 'Balanced Scorecard') || str_contains($userPrompt, 'Perspectiva: Financiera')) {
            $texto = $this->generarBSCLocal($userPrompt);
        } elseif (str_contains($userPrompt, 'Dashboard') || str_contains($userPrompt, 'tendencias')) {
            $texto = "DASHBOARD DE MONITOREO DE TENDENCIAS\n\nIndicadores a monitorear:\n1. Índice de regulación sectorial - Fuente: Diario Oficial, MinSalud - Frecuencia: Mensual\n2. Tasa de crecimiento del PIB salud - Fuente: DANE - Frecuencia: Trimestral\n3. Cobertura de aseguramiento - Fuente: MinSalud - Frecuencia: Semestral\n4. Satisfacción del paciente (NPS) - Fuente: Encuestas internas - Frecuencia: Mensual\n5. Tasa de ocupación de servicios - Fuente: Sistema de información hospitalario - Frecuencia: Mensual\n\nVisualización: Gráficos de tendencia con línea de meta, semáforo verde/amarillo/rojo, tabla de últimos 12 meses.";
        } elseif (str_contains($userPrompt, 'alertas') || str_contains($userPrompt, 'tempranas')) {
            $texto = "SISTEMA DE ALERTAS TEMPRANAS\n\nAlertas configuradas:\n1. ALERTA ROJA: Cambio regulatorio mayor - Disparador: Publicación de nueva ley/decreto en Diario Oficial - Acción: Convocar comité estratégico en 48h\n2. ALERTA NARANJA: Tendencia de mercado a la baja 2 trimestres consecutivos - Disparador: Reporte DANE con decrecimiento >3% - Acción: Activar plan de contingencia financiera\n3. ALERTA AMARILLA: Entrada de nuevo competidor - Disparador: Anuncio público de apertura - Acción: Análisis competitivo en 1 semana\n\nProtocolo de escalamiento: Nivel 1 (Coordinador) → Nivel 2 (Gerente) → Nivel 3 (Dirección General).";
        } elseif (str_contains($userPrompt, 'Revisiones') || str_contains($userPrompt, 'periódicas')) {
            $texto = "REVISIONES PERIÓDICAS DE ESCENARIOS\n\nCalendario de revisión:\n- Revisión trimestral: Evaluar señales tempranas y tendencias. Actualizar probabilidades de escenarios.\n- Revisión semestral: Análisis profundo de cada escenario. Ajustar estrategias si es necesario.\n- Revisión anual: Reevaluación completa de la matriz 2×2. ¿Siguen siendo válidas las incertidumbres?\n\nResponsables: Director de Planeación + Comité Estratégico\n\nActa tipo: Fecha, asistentes, escenario más probable, cambios en señales, estrategias ajustadas, próximos pasos.";
        } elseif (str_contains($userPrompt, 'OBJETIVOS SMART') || str_contains($userPrompt, 'objetivos')) {
            $texto = $this->generarObjetivosLocal($userPrompt);
        } elseif (str_contains($userPrompt, 'KPIs') || str_contains($userPrompt, 'indicadores')) {
            $texto = $this->generarIndicadoresKpiLocal($userPrompt);
        } elseif (str_contains($userPrompt, 'escenarios') || str_contains($userPrompt, 'incertidumbres')) {
        } elseif (str_contains($userPrompt, 'proceso') || str_contains($userPrompt, 'documentar')) {
            $texto = $this->generarProcesoLocal($userPrompt);
        } else {
            $texto = $this->generarRespuestaGeneral($userPrompt);
        }

        return [
            'texto' => $texto,
            'tokens_in' => 0,
            'tokens_out' => 0,
            'metadata' => ['model' => 'local-generator', 'provider' => 'Local']
        ];
    }

    private function extraerContexto(string $prompt): array {
        $data = json_decode(substr($prompt, strpos($prompt, '{') ?: 0), true);
        if (!$data) {
            // Extraer de texto: "Empresa: X, Sector: Y"
            preg_match('/Empresa:\s*([^,]+)/', $prompt, $emp);
            preg_match('/Sector:\s*([^,\n]+)/', $prompt, $sec);
            return ['empresa' => $emp[1]??'Organización', 'sector' => $sec[1]??'General'];
        }
        return $data;
    }

    private function generarMisionLocal(string $prompt): string {
        $ctx = json_decode(substr($prompt, strpos($prompt, '{')), true) ?? [];
        $empresa = $ctx['empresa'] ?? 'la organización';
        $sector = $ctx['sector'] ?? 'General';

        $misiones = [
            'Salud' => "Brindar servicios de salud integrales, humanizados y seguros, centrados en el paciente y su familia, mediante un equipo competente, tecnología de vanguardia y mejora continua, contribuyendo al bienestar de la comunidad.",
            'Inmobiliario' => "Facilitar el acceso a soluciones inmobiliarias de calidad, ofreciendo servicios de compra, venta, arriendo y administración de propiedades con transparencia, profesionalismo e innovación, generando valor para nuestros clientes y la comunidad.",
            'Logística Farmacéutica' => "Garantizar la disponibilidad y seguridad de medicamentos y dispositivos médicos mediante una cadena de suministro eficiente, cumpliendo con las más altas normas de calidad, cadena de frío y buenas prácticas de distribución.",
            'Tecnología' => "Impulsar la transformación digital de las organizaciones mediante soluciones tecnológicas innovadoras, servicios de consultoría especializada y desarrollo de software a la medida, generando ventajas competitivas sostenibles.",
            'Manufactura' => "Producir bienes de calidad superior mediante procesos eficientes, sostenibles e innovadores, satisfaciendo las necesidades de nuestros clientes y contribuyendo al desarrollo económico del país.",
            'General' => "Proveer soluciones de excelencia a nuestros clientes mediante un equipo comprometido, procesos optimizados y mejora continua, generando valor sostenible para todas nuestras partes interesadas.",
        ];
        return "MISIÓN DE {$empresa}\n\n" . ($misiones[$sector] ?? $misiones['General']);
    }

    private function generarVisionLocal(string $prompt): string {
        $ctx = $this->extraerContexto($prompt);
        $empresa = $ctx['empresa'];
        $sector = $ctx['sector'];

        $visiones = [
            'Salud' => "Ser reconocidos en 2028 como una institución de salud de referencia nacional, acreditada en estándares internacionales de calidad, destacando por nuestra excelencia clínica, innovación tecnológica y compromiso con la seguridad del paciente.",
            'Inmobiliario' => "Consolidarnos en 2028 como la empresa inmobiliaria líder en la región, reconocida por nuestra integridad, innovación digital y capacidad de transformar la experiencia de bienes raíces para nuestros clientes.",
            'Logística Farmacéutica' => "Ser el operador logístico farmacéutico de referencia en el país para 2028, destacando por nuestra infraestructura certificada, excelencia operacional y contribución a la salud pública mediante una distribución segura y oportuna.",
            'General' => "Ser en 2028 una organización de clase mundial, reconocida por nuestra capacidad de innovación, excelencia operacional y contribución significativa al desarrollo sostenible de nuestra industria y comunidad.",
        ];
        return "VISIÓN DE {$empresa}\n\n" . ($visiones[$sector] ?? $visiones['General']);
    }

    private function generarValoresLocal(string $prompt): string {
        return "VALORES CORPORATIVOS\n\n1. Integridad - Actuamos con transparencia, honestidad y ética en todas nuestras decisiones.\n2. Excelencia - Buscamos la mejora continua y los más altos estándares en todo lo que hacemos.\n3. Compromiso - Nos dedicamos con pasión al cumplimiento de nuestra misión y objetivos.\n4. Innovación - Fomentamos la creatividad y adoptamos nuevas tecnologías para generar valor.\n5. Trabajo en Equipo - Colaboramos de manera efectiva, valorando la diversidad de ideas.\n6. Responsabilidad Social - Contribuimos al desarrollo sostenible de nuestra comunidad.\n7. Orientación al Cliente - El cliente es el centro de nuestras acciones y decisiones.";
    }

    private function generarBSCLocal(string $prompt): string {
        $pool = [
            'financiera' => [
                'Incrementar la rentabilidad neta', 'Aumentar ingresos por nuevos servicios',
                'Optimizar estructura de costos', 'Reducir gastos administrativos',
                'Mejorar retorno sobre activos', 'Diversificar fuentes de ingreso',
                'Mejorar flujo de caja operativo', 'Reducir nivel de endeudamiento',
                'Aumentar margen bruto por línea', 'Maximizar valor para accionistas',
                'Incrementar ingresos recurrentes', 'Optimizar carga fiscal',
                'Reducir costos de operación', 'Aumentar productividad del capital',
                'Mejorar rotación de inventarios', 'Incrementar precio promedio de venta',
            ],
            'cliente' => [
                'Mejorar experiencia del cliente', 'Reducir tiempos de espera',
                'Aumentar cobertura de servicios', 'Incrementar retención de clientes',
                'Mejorar comunicación omnicanal', 'Ampliar participación de mercado',
                'Aumentar satisfacción postventa', 'Reducir quejas y reclamos',
                'Fidelizar clientes estratégicos', 'Personalizar oferta de valor',
                'Expandir a nuevos segmentos', 'Mejorar reputación de marca',
                'Aumentar referidos de clientes', 'Reducir tasa de cancelación',
                'Optimizar precios por segmento', 'Crear programa de lealtad',
            ],
            'procesos' => [
                'Digitalizar procesos administrativos', 'Reducir eventos adversos',
                'Optimizar cadena de suministros', 'Certificar procesos en ISO 9001',
                'Reducir tiempo de ciclo de facturación', 'Automatizar reportes de gestión',
                'Estandarizar procedimientos operativos', 'Mejorar eficiencia energética',
                'Implementar mejora continua', 'Reducir desperdicios operativos',
                'Optimizar uso de capacidad instalada', 'Integrar sistemas de información',
                'Reducir tiempos de entrega', 'Mejorar trazabilidad de productos',
                'Implementar control estadístico de procesos', 'Fortalecer ciberseguridad operativa',
            ],
            'aprendizaje' => [
                'Reducir rotación de personal', 'Certificar competencias del equipo',
                'Fortalecer cultura de innovación', 'Mejorar clima laboral',
                'Implementar plan de carrera', 'Formación en liderazgo',
                'Fomentar transformación digital', 'Fortalecer gestión del conocimiento',
                'Aumentar diversidad e inclusión', 'Desarrollar habilidades blandas',
                'Atraer talento especializado', 'Implementar modelo de competencias',
                'Fortalecer trabajo colaborativo', 'Crear centro de excelencia interna',
                'Mejorar evaluación de desempeño', 'Impulsar intraemprendimiento',
            ],
        ];
        $labels = ['financiera'=>'Financiera','cliente'=>'Cliente','procesos'=>'Procesos Internos','aprendizaje'=>'Aprendizaje'];
        $kpiDefaults = [
            'financiera' => ['KPI: Variación % anual, Meta: >10%', 'KPI: Cumplimiento presupuestal, Meta: >90%', 'KPI: Crecimiento neto, Meta: >15%'],
            'cliente' => ['KPI: NPS o CSAT, Meta: >80', 'KPI: Tasa de retención, Meta: >70%', 'KPI: Crecimiento de base, Meta: >12%'],
            'procesos' => ['KPI: Eficiencia del proceso, Meta: >85%', 'KPI: % automatización, Meta: >75%', 'KPI: Tiempo de ciclo, Meta: <30%'],
            'aprendizaje' => ['KPI: % cumplimiento plan, Meta: >80%', 'KPI: Tasa de certificación, Meta: >60%', 'KPI: Satisfacción interna, Meta: >85%'],
        ];
        $out = '';
        foreach ($pool as $persp => $objs) {
            $keys = array_rand($objs, 6);
            $out .= 'Perspectiva: ' . $labels[$persp] . "\n";
            foreach ($keys as $i => $k) {
                $kpi = $kpiDefaults[$persp][$i % 3];
                $out .= '- Objetivo: ' . $objs[$k] . ' (' . $kpi . ')' . "\n";
            }
            $out .= "\n";
        }
        return trim($out);
    }

    private function generarBSCRelacionesLocal(string $prompt): string {
        $pool = [
            ["causa"=>"Fortalecer cultura de innovación","efecto"=>"Digitalizar procesos administrativos","intensidad"=>"Fuerte","justificacion"=>"La innovación habilita la digitalización"],
            ["causa"=>"Certificar competencias del equipo","efecto"=>"Reducir eventos adversos","intensidad"=>"Fuerte","justificacion"=>"Personal certificado reduce errores"],
            ["causa"=>"Reducir rotación de personal","efecto"=>"Mejorar experiencia del cliente","intensidad"=>"Media","justificacion"=>"Equipo estable conoce mejor a los clientes"],
            ["causa"=>"Digitalizar procesos administrativos","efecto"=>"Optimizar estructura de costos","intensidad"=>"Fuerte","justificacion"=>"Digitalización reduce costos operativos"],
            ["causa"=>"Reducir eventos adversos","efecto"=>"Mejorar experiencia del cliente","intensidad"=>"Fuerte","justificacion"=>"Seguridad clínica impacta satisfacción"],
            ["causa"=>"Optimizar cadena de suministros","efecto"=>"Optimizar estructura de costos","intensidad"=>"Media","justificacion"=>"Cadena eficiente reduce desperdicios"],
            ["causa"=>"Mejorar experiencia del cliente","efecto"=>"Incrementar ingresos recurrentes","intensidad"=>"Media","justificacion"=>"Clientes satisfechos demandan más servicios"],
            ["causa"=>"Aumentar ingresos por nuevos servicios","efecto"=>"Incrementar la rentabilidad neta","intensidad"=>"Fuerte","justificacion"=>"Nuevas líneas de ingreso mejoran EBITDA"],
            ["causa"=>"Automatizar reportes de gestión","efecto"=>"Mejorar retorno sobre activos","intensidad"=>"Media","justificacion"=>"Decisiones basadas en datos mejoran ROA"],
            ["causa"=>"Implementar plan de carrera","efecto"=>"Reducir rotación de personal","intensidad"=>"Fuerte","justificacion"=>"Plan de carrera retiene talento"],
            ["causa"=>"Fortalecer gestión del conocimiento","efecto"=>"Estandarizar procedimientos operativos","intensidad"=>"Fuerte","justificacion"=>"Conocimiento documentado estandariza operaciones"],
            ["causa"=>"Mejorar clima laboral","efecto"=>"Mejorar experiencia del cliente","intensidad"=>"Media","justificacion"=>"Personal motivado atiende mejor"],
            ["causa"=>"Integrar sistemas de información","efecto"=>"Mejorar eficiencia energética","intensidad"=>"Débil","justificacion"=>"Datos integrados optimizan consumo"],
            ["causa"=>"Formación en liderazgo","efecto"=>"Implementar mejora continua","intensidad"=>"Fuerte","justificacion"=>"Líderes formados impulsan Kaizen"],
            ["causa"=>"Certificar procesos en ISO 9001","efecto"=>"Aumentar satisfacción postventa","intensidad"=>"Media","justificacion"=>"Calidad certificada genera confianza"],
            ["causa"=>"Fomentar transformación digital","efecto"=>"Aumentar cobertura de servicios","intensidad"=>"Fuerte","justificacion"=>"Digitalización expande alcance"],
            ["causa"=>"Diversificar fuentes de ingreso","efecto"=>"Reducir nivel de endeudamiento","intensidad"=>"Media","justificacion"=>"Ingresos diversificados reducen dependencia crediticia"],
            ["causa"=>"Reducir desperdicios operativos","efecto"=>"Optimizar estructura de costos","intensidad"=>"Fuerte","justificacion"=>"Menos desperdicio = menores costos"],
            ["causa"=>"Ampliar participación de mercado","efecto"=>"Aumentar ingresos por nuevos servicios","intensidad"=>"Fuerte","justificacion"=>"Más mercado habilita nuevos servicios"],
            ["causa"=>"Atraer talento especializado","efecto"=>"Fortalecer cultura de innovación","intensidad"=>"Fuerte","justificacion"=>"Talento especializado trae nuevas ideas"],
            ["causa"=>"Reducir tiempos de entrega","efecto"=>"Mejorar experiencia del cliente","intensidad"=>"Fuerte","justificacion"=>"Entregas rápidas mejoran satisfacción"],
            ["causa"=>"Implementar control estadístico de procesos","efecto"=>"Reducir eventos adversos","intensidad"=>"Fuerte","justificacion"=>"Control estadístico previene fallas"],
            ["causa"=>"Crear programa de lealtad","efecto"=>"Incrementar retención de clientes","intensidad"=>"Fuerte","justificacion"=>"Programa de lealtad fideliza"],
            ["causa"=>"Optimizar precios por segmento","efecto"=>"Aumentar margen bruto por línea","intensidad"=>"Fuerte","justificacion"=>"Precios óptimos mejoran margen"],
        ];
        $keys = array_rand($pool, min(6, count($pool)));
        $rels = [];
        foreach ($keys as $k) { $rels[] = $pool[$k]; }
        return json_encode($rels, JSON_UNESCAPED_UNICODE);
    }

    private function generarFODALocal(string $prompt): string {
        $ctx = $this->extraerContexto($prompt);
        $sector = $ctx['sector'];

        $fodas = [
            'Salud' => [
                'fortalezas' => ['Equipo médico especializado y certificado', 'Tecnología diagnóstica de última generación', 'Infraestructura hospitalaria moderna', 'Alianzas estratégicas con aseguradoras', 'Procesos clínicos estandarizados'],
                'debilidades' => ['Alta rotación de personal asistencial en turnos nocturnos', 'Tiempos de espera en consulta externa superiores al promedio', 'Sistema de información con módulos no integrados', 'Capacidad instalada subutilizada en algunas especialidades'],
                'oportunidades' => ['Crecimiento del turismo de salud en la región', 'Nuevos modelos de atención ambulatoria y telemedicina', 'Incentivos gubernamentales para acreditación en calidad', 'Expansión de convenios con EPS del régimen contributivo'],
                'amenazas' => ['Cambios regulatorios en el sistema de salud', 'Incremento de costos de insumos y medicamentos', 'Competencia de nuevas clínicas especializadas', 'Escasez de profesionales en áreas críticas']
            ],
            'Inmobiliario' => [
                'fortalezas' => ['Amplio portafolio de propiedades', 'Equipo de agentes con certificación lonja', 'Plataforma digital de gestión de propiedades', 'Más de 10 años de trayectoria en el mercado'],
                'debilidades' => ['Baja presencia en zonas rurales', 'Procesos de cierre de ventas prolongados', 'Limitada inversión en marketing digital'],
                'oportunidades' => ['Crecimiento del mercado de vivienda de interés social', 'Auge de plataformas de arriendo a corto plazo', 'Nuevas zonas de desarrollo urbano', 'Digitalización de trámites notariales'],
                'amenazas' => ['Volatilidad de tasas de interés hipotecario', 'Incertidumbre económica que afecta la inversión', 'Regulaciones de control de precios de arriendo', 'Competencia de plataformas digitales internacionales']
            ],
            'Logística Farmacéutica' => [
                'fortalezas' => ['Certificación BPA/BPE vigente', 'Flota de vehículos con cadena de frío', 'Sistema de trazabilidad y monitoreo de temperatura', 'Talento humano capacitado en normatividad farmacéutica'],
                'debilidades' => ['Dependencia de pocos proveedores de última milla', 'Capacidad de almacenamiento cercana al límite', 'Procesos de devolución con tiempo de respuesta lento'],
                'oportunidades' => ['Expansión de la demanda de medicamentos biológicos', 'Externalización logística por parte de laboratorios', 'Crecimiento del e-commerce farmacéutico regulado', 'Nuevas rutas de distribución regional'],
                'amenazas' => ['Endurecimiento de requisitos regulatorios INVIMA', 'Fluctuaciones en precio de combustibles', 'Riesgos de falsificación en la cadena de suministro', 'Cambios en políticas de importación de medicamentos']
            ]
        ];

        $foda = $fodas[$sector] ?? $fodas['Salud'];
        return json_encode($foda, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function generarEscenariosLocal(string $prompt): string {
        return json_encode([
            'incertidumbres' => [
                ['eje'=>'x','nombre'=>'Regulación gubernamental','bajo'=>'Estable / Flexible','alto'=>'Restrictiva / Exigente'],
                ['eje'=>'y','nombre'=>'Demanda del mercado','bajo'=>'Decrecimiento','alto'=>'Expansión / Crecimiento']
            ],
            'escenarios' => [
                ['nombre'=>'Crecimiento Regulado','x'=>1,'y'=>1,'desc'=>'Mercado en expansión con alta regulación. Las empresas deben invertir en compliance y certificaciones para operar.','prob'=>30,'color'=>'rgba(144,238,144,0.2)','estrategia'=>'Invertir en compliance, certificaciones y tecnología para diferenciarse.'],
                ['nombre'=>'Auge Liberal','x'=>0,'y'=>1,'desc'=>'Mercado crece con poca intervención. Oportunidad para expandir agresivamente con nuevos servicios.','prob'=>25,'color'=>'rgba(173,216,230,0.2)','estrategia'=>'Expandir agresivamente: nuevos canales, innovación sin restricciones.'],
                ['nombre'=>'Estancamiento Controlado','x'=>1,'y'=>0,'desc'=>'Mercado estancado con alta regulación. Enfoque en eficiencia y control de costos.','prob'=>25,'color'=>'rgba(255,218,185,0.2)','estrategia'=>'Optimizar costos, estandarizar procesos, mantener calidad.'],
                ['nombre'=>'Crisis','x'=>0,'y'=>0,'desc'=>'Mercado en contracción y baja regulación. Supervivencia: preservar caja y proteger el negocio central.','prob'=>20,'color'=>'rgba(255,182,193,0.2)','estrategia'=>'Preservar caja, reducir gastos no esenciales, renegociar contratos.']
            ],
            'early_warnings' => [
                ['señal'=>'Cambios en regulación','indicador'=>'Hacia regulación restrictiva','fuente'=>'Diario oficial, gremios'],
                ['señal'=>'Tasa de crecimiento','indicador'=>'Expansión / Contracción','fuente'=>'DANE, gremios'],
                ['señal'=>'Movimientos competidores','indicador'=>'Entrada/Salida de jugadores','fuente'=>'Noticias, reportes anuales']
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function generarObjetivosLocal(string $prompt): string {
        return "Incrementar la rentabilidad neta\nAumentar la participación de mercado\nMejorar la satisfacción del cliente\nOptimizar la eficiencia operativa\nFortalecer la cultura organizacional";
    }

    private function generarIndicadoresKpiLocal(string $prompt): string {
        return json_encode([
            ['perspectiva'=>'financiera','nombre'=>'Margen EBITDA','formula'=>'EBITDA / Ingresos Totales * 100','unidad'=>'%','meta'=>18,'fuente'=>'ERP Financiero'],
            ['perspectiva'=>'financiera','nombre'=>'Retorno sobre Activos (ROA)','formula'=>'Utilidad Neta / Activos Totales * 100','unidad'=>'%','meta'=>10,'fuente'=>'Balance General'],
            ['perspectiva'=>'financiera','nombre'=>'Crecimiento de Ingresos','formula'=>'(Ingresos_Periodo - Ingresos_Anterior) / Ingresos_Anterior * 100','unidad'=>'%','meta'=>12,'fuente'=>'Estado de Resultados'],
            ['perspectiva'=>'financiera','nombre'=>'Flujo de Caja Operativo','formula'=>'EBITDA - CAPEX - Impuestos','unidad'=>'$M','meta'=>5,'fuente'=>'Flujo de Caja'],
            ['perspectiva'=>'financiera','nombre'=>'Margen Bruto','formula'=>'(Ingresos - Costo_Ventas) / Ingresos * 100','unidad'=>'%','meta'=>40,'fuente'=>'Estado de Resultados'],
            ['perspectiva'=>'financiera','nombre'=>'Rotación de Activos','formula'=>'Ingresos / Activos Totales','unidad'=>'veces','meta'=>1.5,'fuente'=>'Balance General'],
            ['perspectiva'=>'financiera','nombre'=>'Razón Corriente','formula'=>'Activo_Corriente / Pasivo_Corriente','unidad'=>'ratio','meta'=>2.0,'fuente'=>'Balance General'],
            ['perspectiva'=>'financiera','nombre'=>'Deuda Neta / EBITDA','formula'=>'Deuda_Neta / EBITDA','unidad'=>'veces','meta'=>2.5,'fuente'=>'ERP Financiero'],
            ['perspectiva'=>'cliente','nombre'=>'Net Promoter Score (NPS)','formula'=>'%_Promotores - %_Detractores','unidad'=>'puntos','meta'=>75,'fuente'=>'Encuesta NPS'],
            ['perspectiva'=>'cliente','nombre'=>'Tasa de Retención','formula'=>'Clientes_Final - Clientes_Nuevos / Clientes_Inicial * 100','unidad'=>'%','meta'=>85,'fuente'=>'CRM'],
            ['perspectiva'=>'cliente','nombre'=>'Satisfacción (CSAT)','formula'=>'Suma_Puntuaciones / Total_Encuestas / Escala_Max * 100','unidad'=>'%','meta'=>90,'fuente'=>'Encuestas'],
            ['perspectiva'=>'cliente','nombre'=>'Participación de Mercado','formula'=>'Ventas_Empresa / Ventas_Total_Sector * 100','unidad'=>'%','meta'=>35,'fuente'=>'Estudios de Mercado'],
            ['perspectiva'=>'cliente','nombre'=>'Costo de Adquisición (CAC)','formula'=>'Gasto_Marketing+Ventas / Nuevos_Clientes','unidad'=>'$','meta'=>50,'fuente'=>'CRM + ERP'],
            ['perspectiva'=>'cliente','nombre'=>'Valor de Vida del Cliente (LTV)','formula'=>'Ticket_Promedio * Compras_Año * Vida_Cliente','unidad'=>'$','meta'=>5000,'fuente'=>'CRM'],
            ['perspectiva'=>'cliente','nombre'=>'Tasa de Conversión','formula'=>'Clientes_Concretados / Leads_Calificados * 100','unidad'=>'%','meta'=>25,'fuente'=>'CRM'],
            ['perspectiva'=>'cliente','nombre'=>'Tiempo Promedio de Respuesta','formula'=>'Suma_Tiempos_Respuesta / Total_Solicitudes','unidad'=>'minutos','meta'=>30,'fuente'=>'Helpdesk'],
            ['perspectiva'=>'procesos','nombre'=>'Eficiencia del Proceso','formula'=>'Tiempo_Estandar / Tiempo_Real * 100','unidad'=>'%','meta'=>85,'fuente'=>'BPM / WMS'],
            ['perspectiva'=>'procesos','nombre'=>'% Procesos Digitalizados','formula'=>'Procesos_Digitalizados / Total_Procesos * 100','unidad'=>'%','meta'=>80,'fuente'=>'BPM'],
            ['perspectiva'=>'procesos','nombre'=>'Tasa de Defectos','formula'=>'Unidades_Defectuosas / Total_Unidades * 100','unidad'=>'%','meta'=>2,'fuente'=>'Control de Calidad'],
            ['perspectiva'=>'procesos','nombre'=>'Tiempo de Ciclo Promedio','formula'=>'Fecha_Entrega - Fecha_Inicio','unidad'=>'dias','meta'=>15,'fuente'=>'ERP / BPM'],
            ['perspectiva'=>'procesos','nombre'=>'Cumplimiento de Entregas (OTIF)','formula'=>'Pedidos_Completos_a_Tiempo / Total_Pedidos * 100','unidad'=>'%','meta'=>95,'fuente'=>'WMS / Logística'],
            ['perspectiva'=>'procesos','nombre'=>'Capacidad Utilizada','formula'=>'Produccion_Real / Capacidad_Instalada * 100','unidad'=>'%','meta'=>85,'fuente'=>'MES / ERP'],
            ['perspectiva'=>'procesos','nombre'=>'Costo por Unidad Producida','formula'=>'Costos_Totales_Produccion / Unidades_Producidas','unidad'=>'$','meta'=>25,'fuente'=>'ERP Producción'],
            ['perspectiva'=>'procesos','nombre'=>'Tasa de Automatización','formula'=>'Tareas_Automatizadas / Total_Tareas * 100','unidad'=>'%','meta'=>60,'fuente'=>'BPM'],
            ['perspectiva'=>'aprendizaje','nombre'=>'Rotación de Personal','formula'=>'Bajas_Periodo / Promedio_Empleados * 100','unidad'=>'%','meta'=>10,'fuente'=>'HRMS'],
            ['perspectiva'=>'aprendizaje','nombre'=>'% Personal Certificado','formula'=>'Personal_Certificado / Total_Personal * 100','unidad'=>'%','meta'=>60,'fuente'=>'HRMS'],
            ['perspectiva'=>'aprendizaje','nombre'=>'Horas de Formación por Empleado','formula'=>'Total_Horas_Formacion / Total_Empleados','unidad'=>'horas/año','meta'=>40,'fuente'=>'LMS / HRMS'],
            ['perspectiva'=>'aprendizaje','nombre'=>'Clima Laboral','formula'=>'Puntuacion_Promedio_Encuesta / Escala_Max * 100','unidad'=>'%','meta'=>85,'fuente'=>'Encuesta de Clima'],
            ['perspectiva'=>'aprendizaje','nombre'=>'Tasa de Promoción Interna','formula'=>'Ascensos_Internos / Total_Vacantes_Cubiertas * 100','unidad'=>'%','meta'=>60,'fuente'=>'HRMS'],
            ['perspectiva'=>'aprendizaje','nombre'=>'Índice de Innovación','formula'=>'Ideas_Implementadas / Total_Ideas_Recibidas * 100','unidad'=>'%','meta'=>30,'fuente'=>'Plataforma de Ideas'],
            ['perspectiva'=>'aprendizaje','nombre'=>'Tiempo de Cobertura de Vacantes','formula'=>'Fecha_Contratacion - Fecha_Apertura_Vacante','unidad'=>'dias','meta'=>30,'fuente'=>'HRMS / ATS'],
            ['perspectiva'=>'aprendizaje','nombre'=>'Inversión en Capacitación por Empleado','formula'=>'Presupuesto_Capacitacion / Total_Empleados','unidad'=>'$/año','meta'=>2000,'fuente'=>'ERP + HRMS'],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function generarIniciativasLocal(string $prompt): string {
        $pool = [
            ['nombre'=>'Programa de Transformación Digital','tipo'=>'innovacion','prioridad'=>'critico','presupuesto'=>150000,'descripcion'=>'Digitalizar procesos core en 18 meses. Incluye automatización RPA, integración de sistemas y capacitación del personal.'],
            ['nombre'=>'Plan de Certificación ISO 9001','tipo'=>'ofensiva','prioridad'=>'alto','presupuesto'=>80000,'descripcion'=>'Implementar sistema de gestión de calidad y obtener certificación en 12 meses. Incluye consultoría especializada.'],
            ['nombre'=>'Rediseño de Experiencia del Cliente','tipo'=>'crecimiento','prioridad'=>'alto','presupuesto'=>60000,'descripcion'=>'Implementar CRM omnicanal, rediseñar journey map y establecer programa de lealtad para aumentar NPS.'],
            ['nombre'=>'Programa de Retención de Talento','tipo'=>'defensiva','prioridad'=>'alto','presupuesto'=>45000,'descripcion'=>'Plan de carrera, beneficios flexibles y programa de reconocimiento para reducir rotación.'],
            ['nombre'=>'Optimización de Cadena de Suministro','tipo'=>'ofensiva','prioridad'=>'medio','presupuesto'=>35000,'descripcion'=>'Implementar WMS y optimizar rutas de distribución para mejorar OTIF de 82% a >95%.'],
            ['nombre'=>'Expansión a Nuevos Segmentos','tipo'=>'crecimiento','prioridad'=>'alto','presupuesto'=>120000,'descripcion'=>'Investigación de mercado, nueva línea de servicios y campaña de lanzamiento en 2 regiones.'],
            ['nombre'=>'Automatización de Reportes de Gestión','tipo'=>'innovacion','prioridad'=>'medio','presupuesto'=>25000,'descripcion'=>'Implementar BI con dashboards en tiempo real. Reducir generación de informes de 5 días a 1 hora.'],
            ['nombre'=>'Programa de Eficiencia Energética','tipo'=>'ofensiva','prioridad'=>'medio','presupuesto'=>40000,'descripcion'=>'Auditoría energética, paneles solares y automatización para reducir consumo en 15%.'],
            ['nombre'=>'Centro de Excelencia en Innovación','tipo'=>'innovacion','prioridad'=>'medio','presupuesto'=>55000,'descripcion'=>'Espacio de co-creación, intraemprendimiento y fondos semilla para ideas del equipo.'],
            ['nombre'=>'Plan de Continuidad Operativa','tipo'=>'defensiva','prioridad'=>'critico','presupuesto'=>70000,'descripcion'=>'BCP/DRP, backup cloud, simulacros trimestrales. Garantizar RTO <4h y RPO <1h.'],
            ['nombre'=>'Migración a Infraestructura Cloud','tipo'=>'innovacion','prioridad'=>'alto','presupuesto'=>95000,'descripcion'=>'Migrar servidores on-premise a cloud híbrida. Reducir costos de infraestructura en 30% y mejorar escalabilidad.'],
            ['nombre'=>'Desarrollo de App Móvil para Clientes','tipo'=>'crecimiento','prioridad'=>'alto','presupuesto'=>75000,'descripcion'=>'App nativa iOS/Android con portal de autogestión, agenda de citas y notificaciones. Meta: 5000 descargas en 6 meses.'],
            ['nombre'=>'Programa de Diversidad e Inclusión','tipo'=>'ofensiva','prioridad'=>'medio','presupuesto'=>20000,'descripcion'=>'Política de equidad, comité de diversidad, mentorías y meta de 30% mujeres en liderazgo en 2 años.'],
            ['nombre'=>'Alianza Estratégica con Universidad','tipo'=>'crecimiento','prioridad'=>'medio','presupuesto'=>30000,'descripcion'=>'Convenio de investigación aplicada, pasantías y capacitación especializada con universidad del sector.'],
            ['nombre'=>'Rediseño de Modelo de Atención','tipo'=>'ofensiva','prioridad'=>'alto','presupuesto'=>110000,'descripcion'=>'Pasar de modelo reactivo a preventivo. Incluye telemedicina, monitoreo remoto y rutas integradas de atención.'],
            ['nombre'=>'Centralización de Compras y Contratación','tipo'=>'ofensiva','prioridad'=>'alto','presupuesto'=>50000,'descripcion'=>'Unificar compras en un solo departamento. Negociar contratos marco para reducir costos de adquisición en 18%.'],
            ['nombre'=>'Sistema de Gestión de Riesgos','tipo'=>'defensiva','prioridad'=>'critico','presupuesto'=>65000,'descripcion'=>'Implementar ERM con matriz de riesgos, controles clave y comité trimestral. Alineado con COSO e ISO 31000.'],
            ['nombre'=>'Programa de Bienestar Laboral','tipo'=>'defensiva','prioridad'=>'medio','presupuesto'=>28000,'descripcion'=>'Salud mental, pausas activas, horarios flexibles y gimnasio corporativo. Reducir ausentismo en 25%.'],
            ['nombre'=>'Laboratorio de Analítica de Datos','tipo'=>'innovacion','prioridad'=>'alto','presupuesto'=>90000,'descripcion'=>'Equipo de data science, plataforma de ML y modelos predictivos para optimizar operación y personalización.'],
            ['nombre'=>'Rediseño de Procesos con Lean Six Sigma','tipo'=>'ofensiva','prioridad'=>'alto','presupuesto'=>48000,'descripcion'=>'Certificar 5 black belts internos, ejecutar 15 proyectos de mejora en 12 meses. Meta: reducir costos en $100K.'],
            ['nombre'=>'Programa de Fidelización y Lealtad','tipo'=>'crecimiento','prioridad'=>'alto','presupuesto'=>55000,'descripcion'=>'Programa de puntos, beneficios exclusivos y comunicaciones personalizadas. Meta: aumentar LTV en 30%.'],
            ['nombre'=>'Ciberseguridad y Protección de Datos','tipo'=>'defensiva','prioridad'=>'critico','presupuesto'=>85000,'descripcion'=>'Implementar ISO 27001, pentesting trimestral, capacitación anti-phishing y seguro de ciberriesgos.'],
            ['nombre'=>'Programa de Mentoring y Liderazgo','tipo'=>'ofensiva','prioridad'=>'medio','presupuesto'=>22000,'descripcion'=>'Emparejar 20 líderes senior con talento joven. Formación en habilidades directivas. Retener alto potencial.'],
            ['nombre'=>'Marketplace de Servicios Digitales','tipo'=>'innovacion','prioridad'=>'medio','presupuesto'=>65000,'descripcion'=>'Plataforma propia de e-commerce para servicios y productos complementarios. Nuevo canal de ingresos directo.'],
        ];
        $keys = array_rand($pool, min(7, count($pool)));
        $rels = [];
        foreach ($keys as $k) { $rels[] = $pool[$k]; }
        return json_encode($rels, JSON_UNESCAPED_UNICODE);
    }

    private function generarEvaluacionLocal(string $prompt): string {
        return json_encode([
            ['titulo'=>'Objetivos sin KPIs','descripcion'=>'Varios objetivos tienen menos de 2 indicadores. Cada objetivo estratégico debe tener al menos 2 KPIs para medir su avance de forma balanceada. Prioriza los objetivos en estado crítico.','tipo'=>'critico','accion'=>'crear_indicador','perspectiva'=>'Todas'],
            ['titulo'=>'Faltan iniciativas para ejecutar','descripcion'=>'Hay objetivos sin ninguna iniciativa asociada. Sin iniciativas concretas, los objetivos se quedan en papel. Asigna al menos 1 iniciativa con presupuesto y responsable a cada objetivo.','tipo'=>'critico','accion'=>'crear_iniciativa','perspectiva'=>'Todas'],
            ['titulo'=>'Metas sin valores numéricos','descripcion'=>'Revisa que cada KPI tenga una meta numérica clara (rango máximo) para poder evaluar cumplimiento. Un KPI sin meta es solo un nombre.','tipo'=>'mejora','accion'=>'ajustar_objetivo','perspectiva'=>'Todas'],
            ['titulo'=>'Balance entre perspectivas','descripcion'=>'Revisa la distribución de indicadores entre las 4 perspectivas del BSC. Si una perspectiva tiene menos KPIs, el tablero queda desbalanceado. Todas deben tener al menos 4 KPIs.','tipo'=>'mejora','accion'=>'crear_indicador','perspectiva'=>'Todas'],
            ['titulo'=>'Frecuencia de medición','descripcion'=>'Verifica que los indicadores tengan definida la frecuencia de medición (mensual, trimestral, etc.). Sin frecuencia definida no hay seguimiento real.','tipo'=>'info','accion'=>'ninguna','perspectiva'=>'Todas'],
            ['titulo'=>'Responsables asignados','descripcion'=>'Cada objetivo e iniciativa debe tener un responsable claro. Sin dueño, nadie rinde cuentas. Asigna responsables a los elementos que aún no tengan.','tipo'=>'mejora','accion'=>'ninguna','perspectiva'=>'Todas'],
            ['titulo'=>'Presupuesto de iniciativas','descripcion'=>'Las iniciativas deben tener presupuesto estimado para evaluar viabilidad. Una iniciativa sin presupuesto es una idea, no un plan.','tipo'=>'info','accion'=>'ajustar_objetivo','perspectiva'=>'Todas'],
            ['titulo'=>'Documentar lecciones aprendidas','descripcion'=>'Al finalizar el ciclo de planeación, documenta qué funcionó, qué no y por qué. Esta información es oro para el próximo ciclo.','tipo'=>'info','accion'=>'ninguna','perspectiva'=>'Todas'],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function generarIndicadoresLocal(string $prompt): string {
        return "1. Tasa de Cumplimiento de Metas - (Metas Alcanzadas / Metas Programadas) × 100 - Mensual\n2. Índice de Satisfacción del Cliente (NPS) - Promedio ponderado de encuestas - Trimestral\n3. Eficiencia Operativa - (Output / Input) × 100 - Mensual\n4. Tiempo Promedio de Entrega - Suma de tiempos / Total de entregas - Mensual\n5. Tasa de No Conformidades - (NC detectadas / Total auditorías) × 100 - Trimestral";
    }

    private function generarProcesoLocal(string $prompt): string {
        return "DOCUMENTACIÓN DEL PROCESO\n\nOBJETIVO: Establecer las actividades necesarias para ejecutar el proceso de manera controlada, asegurando el cumplimiento de los requisitos del cliente, normativos y organizacionales.\n\nALCANCE: Aplica desde la recepción de la solicitud hasta la entrega del resultado, incluyendo todas las áreas involucradas.\n\nRESPONSABLE: Dueño del proceso designado por la dirección.\n\nENTRADAS: Requisitos del cliente, recursos asignados, información documentada.\n\nACTIVIDADES PRINCIPALES:\n1. Recepción y análisis de la solicitud\n2. Planificación de actividades\n3. Ejecución controlada\n4. Verificación de resultados\n5. Entrega y cierre\n\nSALIDAS: Producto/servicio conforme, registros de calidad, indicadores de desempeño.\n\nINDICADORES SUGERIDOS: Cumplimiento del plan, Oportunidad en la entrega, Calidad del resultado.\n\nRIESGOS: Desviaciones en tiempos, recursos insuficientes, cambios en requisitos.";
    }

    private function generarRespuestaGeneral(string $prompt): string {
        // Extraer contexto del prompt para dar respuesta relevante
        $ctx = $this->extraerContexto($prompt);

        // Si el prompt contiene un paso específico, generar contenido contextual
        if (str_contains($prompt, 'Paso') || str_contains($prompt, 'paso')) {
            preg_match('/Paso\s*(\d+)[:\s]*([^\n]+)/', $prompt, $m);
            $numPaso = $m[1] ?? '?';
            $descPaso = $m[2] ?? '';

            if (str_contains(strtolower($descPaso), 'identificar fuerzas') || str_contains(strtolower($descPaso), 'fuerzas impulsoras')) {
                return "FUERZAS IMPULSORAS IDENTIFICADAS\n\n1. Regulación gubernamental - Impacto: Alto, Incertidumbre: Alta\n   • Posibles cambios en políticas de habilitación y acreditación\n   • Nuevas normativas de seguridad del paciente\n\n2. Tecnología e innovación - Impacto: Alto, Incertidumbre: Media\n   • Adopción de IA en diagnóstico y telemedicina\n   • Automatización de procesos administrativos\n\n3. Demografía y epidemiología - Impacto: Medio, Incertidumbre: Baja\n   • Envejecimiento poblacional aumenta demanda de servicios crónicos\n   • Cambios en perfil epidemiológico post-pandemia\n\n4. Financiamiento del sistema - Impacto: Alto, Incertidumbre: Alta\n   • Sostenibilidad del sistema de aseguramiento\n   • Presión sobre tarifas y modelos de pago\n\n5. Talento humano en salud - Impacto: Alto, Incertidumbre: Media\n   • Escasez de especialistas en áreas críticas\n   • Migración de profesionales al extranjero";
            }

            if (str_contains(strtolower($descPaso), 'clasificar por impacto') || str_contains(strtolower($descPaso), 'impacto e incertidumbre')) {
                return "MATRIZ DE CLASIFICACIÓN DE INCERTIDUMBRES\n\nAlto Impacto + Alta Incertidumbre (Priorizar):\n• Regulación gubernamental - Monitorear cambios legislativos\n• Financiamiento del sistema - Preparar escenarios de ajuste\n\nAlto Impacto + Baja Incertidumbre (Planificar):\n• Envejecimiento poblacional - Expandir servicios geriátricos\n• Tecnología - Invertir en transformación digital\n\nBajo Impacto + Alta Incertidumbre (Monitorear):\n• Nuevos competidores - Observar tendencias de mercado\n\nBajo Impacto + Baja Incertidumbre (Registrar):\n• Cambios en preferencias menores - Mantener seguimiento";
            }

            if (str_contains(strtolower($descPaso), 'seleccionar incertidumbres')) {
                return "INCERTIDUMBRES CRÍTICAS SELECCIONADAS\n\nEje X: Regulación gubernamental (Estable ↔ Restrictiva)\n• Justificación: Determina las reglas de operación del sector\n• Impacto directo en costos de cumplimiento y modelos de atención\n\nEje Y: Crecimiento del mercado (Decrecimiento ↔ Expansión)\n• Justificación: Define la demanda de servicios y capacidad de inversión\n• Impacto en ingresos, expansión y contratación\n\nEstas dos incertidumbres capturan >70% de la variabilidad del entorno y son independientes entre sí, lo que las hace ideales para la matriz 2x2.";
            }

            if (str_contains(strtolower($descPaso), 'implicaciones') || str_contains(strtolower($descPaso), 'desarrollar opciones')) {
                return "ESTRATEGIAS POR ESCENARIO\n\nEscenario A - Crecimiento Regulado:\n• Invertir en certificaciones de calidad y acreditación internacional\n• Desarrollar servicios diferenciados de alto valor\n• Fortalecer equipo de compliance y gestión de riesgo\n\nEscenario B - Auge Liberal:\n• Expandir agresivamente a nuevas ubicaciones\n• Adquirir competidores más pequeños\n• Invertir en marketing y posicionamiento de marca\n\nEscenario C - Estancamiento Controlado:\n• Optimizar costos operativos (meta: -15%)\n• Automatizar procesos administrativos\n• Fidelizar pacientes actuales con programas de lealtad\n\nEscenario D - Crisis:\n• Reducir gastos no esenciales inmediatamente\n• Renegociar contratos con proveedores\n• Enfocar recursos en servicios core de mayor margen\n• Evaluar alianzas estratégicas para compartir costos";
            }

            if (str_contains(strtolower($descPaso), 'dashboard') || str_contains(strtolower($descPaso), 'monitoreo de tendencias')) {
                return "DASHBOARD DE MONITOREO DE SEÑALES\n\nIndicadores a monitorear:\n1. Índice de regulación sectorial - Fuente: Diario Oficial, MinSalud - Frecuencia: Mensual\n2. Tasa de crecimiento del PIB salud - Fuente: DANE - Frecuencia: Trimestral\n3. Inversión en healthtech - Fuente: Reportes sectoriales - Frecuencia: Semestral\n4. Tasa de ocupación hospitalaria - Fuente: Datos internos - Frecuencia: Semanal\n5. Movimientos de competidores - Fuente: Noticias, licitaciones - Frecuencia: Quincenal\n\nUmbrales de alerta:\n• Verde: Indicadores dentro de lo esperado\n• Amarillo: Desviación >10% requiere análisis\n• Rojo: Desviación >25% activa plan de contingencia";
            }
        }

        // Respuesta por defecto mejorada: extrae fase y paso del contexto
        $faseName = '';
        $pasoName = '';
        if (preg_match('/Fase[:\s]+([^.]+)/i', $prompt, $fm)) $faseName = trim($fm[1]);
        if (preg_match('/Paso\s*(\d+)[:\s-]*([^\n.]+)/i', $prompt, $pm)) {
            $pasoNum = $pm[1];
            $pasoName = trim($pm[2]);
        }
        
        $templates = [
            'Indicadores KPIs' => [
                'Definir KPIs' => "INDICADORES CLAVE DE DESEMPEÑO (KPIs)\n\nKPI sugeridos por perspectiva:\n\nFinanciera:\n• Margen EBITDA (%) - Meta: >18% - Frecuencia: Trimestral\n• ROA (%) - Meta: >10% - Frecuencia: Trimestral\n• Crecimiento de ingresos (%) - Meta: >12% - Frecuencia: Mensual\n\nCliente:\n• NPS (Net Promoter Score) - Meta: >75 - Frecuencia: Trimestral\n• Tasa de retención (%) - Meta: >85% - Frecuencia: Mensual\n• Participación de mercado (%) - Meta: 35% - Frecuencia: Semestral\n\nProcesos:\n• % procesos digitalizados - Meta: 80% - Frecuencia: Mensual\n• Tiempo de ciclo promedio (días) - Meta: <15 - Frecuencia: Mensual\n• Tasa de defectos (%) - Meta: <2% - Frecuencia: Semanal\n\nAprendizaje:\n• Rotación de personal (%) - Meta: <10% - Frecuencia: Mensual\n• % personal certificado - Meta: 60% - Frecuencia: Trimestral\n• Horas de formación por empleado - Meta: >40/año - Frecuencia: Trimestral",
                'Establecer metas' => "METAS POR OBJETIVO ESTRATÉGICO\n\n1. Incrementar la rentabilidad neta: Meta >18% margen EBITDA para Q4\n2. Mejorar experiencia del cliente: Meta NPS >75 para fin de año\n3. Digitalizar procesos: Meta 80% de procesos digitalizados en 12 meses\n4. Reducir rotación: Meta <10% rotación anual en 18 meses\n\nCada meta debe ser SMART:\n• Specific (Específica): ¿Qué exactamente?\n• Measurable (Medible): ¿Con qué indicador?\n• Achievable (Alcanzable): ¿Es realista?\n• Relevant (Relevante): ¿Aporta al objetivo?\n• Time-bound (Temporal): ¿Para cuándo?",
                'Diseñar medición' => "DISEÑO DEL SISTEMA DE MEDICIÓN\n\n1. Frecuencia de medición:\n• KPIs operativos: Semanal con tablero de control\n• KPIs tácticos: Mensual con reporte gerencial\n• KPIs estratégicos: Trimestral con comité directivo\n\n2. Fuentes de datos:\n• Financieros: ERP / Sistema contable\n• Cliente: CRM / Encuestas NPS\n• Procesos: Sistema de gestión de calidad\n• Aprendizaje: HRMS / Plataforma de formación\n\n3. Responsables de medición:\n• Cada KPI debe tener un dueño (persona o área)\n• El dueño recolecta, valida y reporta el dato\n\n4. Visualización:\n• Semáforo: Verde (≥90% meta), Amarillo (70-89%), Rojo (<70%)\n• Gráficos de tendencia de 12 meses\n• Tablero de mando integral ( Balanced Scorecard )",
            ],
            'Iniciativas' => [
                'Identificar' => "IDENTIFICACIÓN DE INICIATIVAS ESTRATÉGICAS\n\n1. Análisis de brechas: Objetivo actual vs meta deseada\n2. Iniciativas propuestas:\n• Programa de transformación digital (presupuesto estimado: $150K)\n• Plan de certificación de calidad ISO 9001 (presupuesto: $80K)\n• Rediseño de experiencia del cliente (presupuesto: $60K)\n3. Criterios de selección: Impacto estratégico, Factibilidad, Costo-beneficio, Tiempo de implementación\n4. Priorización: Matriz Esfuerzo vs Impacto",
                'Priorizar' => "PRIORIZACIÓN DE INICIATIVAS (MATRIZ ESFUERZO-IMPACTO)\n\nQuick Wins (Alto Impacto, Bajo Esfuerzo):\n• Automatizar reportes de gestión (3 meses, $20K)\n• Programa de reconocimiento al empleado (1 mes, $5K)\n\nProyectos Mayores (Alto Impacto, Alto Esfuerzo):\n• Transformación digital integral (18 meses, $150K)\n• Certificación ISO 9001 (12 meses, $80K)\n\nRellenos (Bajo Impacto, Bajo Esfuerzo):\n• Actualizar manual de procedimientos (2 meses, $10K)\n\nTareas Innecesarias (Bajo Impacto, Alto Esfuerzo):\n• Rediseñar informes no estratégicos → ELIMINAR",
                'Planificar' => "PLAN DE INICIATIVAS ESTRATÉGICAS\n\nIniciativa: Transformación Digital\n• Objetivo: Digitalizar 80% de procesos\n• Hitos: Mes 3 - Diagnóstico | Mes 6 - Piloto | Mes 12 - Rollout | Mes 18 - Cierre\n• Responsable: Director de TI\n• Presupuesto: $150,000\n• Riesgos: Resistencia al cambio, integración de sistemas\n\nIniciativa: Certificación ISO 9001\n• Hitos: Mes 1 - Gap Analysis | Mes 4 - Documentación | Mes 8 - Auditoría interna | Mes 12 - Certificación\n• Responsable: Director de Calidad\n• Presupuesto: $80,000",
            ],
            'Evaluación' => [
                'Evaluar' => "EVALUACIÓN DEL PLAN ESTRATÉGICO\n\n1. Cumplimiento de objetivos:\n• % de objetivos con avance >80%: ___%\n• % de KPIs dentro de meta: ___%\n\n2. Lecciones aprendidas:\n• ¿Qué funcionó bien?\n• ¿Qué se puede mejorar?\n• ¿Qué factores externos afectaron?\n\n3. Ajustes recomendados:\n• Objetivos que requieren revisión de meta\n• Iniciativas que necesitan más recursos\n• Nuevas prioridades estratégicas\n\n4. Próximo ciclo de planeación: Iniciar en [fecha]",
            ],
            'OKR' => [
                'check-in' => "CHECK-IN SEMANAL DE OKRs\n\nObjetivo: [Nombre del objetivo]\n\nKey Results:\n1. [KR1]: Avance __% | Confianza: On Track\n2. [KR2]: Avance __% | Confianza: At Risk\n3. [KR3]: Avance __% | Confianza: On Track\n\nBloqueos identificados:\n• [Bloqueo 1]: Acción requerida - [Responsable]\n\nPróximos pasos esta semana:\n1. [Acción concreta 1]\n2. [Acción concreta 2]\n\nActualizado: [Fecha]",
                'retrospectiva' => "RETROSPECTIVA DE CICLO OKR\n\n1. Scoring final:\n• Objetivo 1: 0.85 - Cumplimiento significativo\n• Objetivo 2: 0.60 - Parcialmente alcanzado\n• Objetivo 3: 0.95 - Excedido\n\n2. ¿Qué funcionó bien?\n• Los check-ins semanales mantuvieron el foco\n• La transparencia de avances motivó al equipo\n\n3. ¿Qué mejorar?\n• Los KRs necesitan métricas más objetivas\n• Algunos equipos necesitan más autonomía\n\n4. Lecciones para el próximo ciclo:\n• Definir KRs con dueño claro desde el inicio\n• Establecer rango de puntuación 0.0-1.0 al definir cada KR",
            ],
            'Design Thinking' => [
                'empatizar' => "EMPATIZAR — INVESTIGACIÓN CON USUARIOS\n\n1. Entrevistas realizadas: 8 stakeholders clave\n2. Hallazgos principales:\n• Los usuarios necesitan reducir tiempos de espera\n• La comunicación entre áreas es el principal punto de fricción\n• Hay desconocimiento de todos los servicios disponibles\n3. Mapa de empatía:\n• ¿Qué piensa y siente? Preocupación por la calidad del servicio\n• ¿Qué ve? Procesos lentos, filas, papeleo\n• ¿Qué dice y hace? Pregunta constantemente por el estado de su trámite\n• ¿Qué oye? Quejas de otros usuarios, recomendaciones informales\n4. Insights clave: La percepción de calidad está más ligada a la experiencia que al resultado clínico.",
                'definir' => "DEFINIR — PROBLEMA Y OPORTUNIDAD\n\n1. Problema central:\nLos usuarios experimentan una desconexión entre sus expectativas de servicio ágil y la realidad de procesos administrativos fragmentados.\n\n2. Point of View (POV):\n[Usuario] necesita [necesidad] porque [insight].\n\n3. Pregunta generadora (How Might We):\n• ¿Cómo podríamos reducir la fricción administrativa sin perder calidad?\n• ¿Cómo podríamos hacer que cada interacción sea una oportunidad de fidelización?\n• ¿Cómo podríamos anticiparnos a las necesidades antes de que el usuario pregunte?\n\n4. Alcance del reto: Rediseñar la experiencia del usuario en los puntos de contacto administrativos.",
                'idear' => "IDEAR — GENERACIÓN DE SOLUCIONES\n\nTécnica: Brainstorming estructurado (15 min)\n\nIdeas generadas (top 10):\n1. App móvil de autogestión con notificaciones proactivas\n2. Quioscos de autoservicio en puntos de alta afluencia\n3. Sistema de turnos virtual con estimación de tiempos\n4. Chatbot 24/7 para consultas frecuentes\n5. Portal unificado de historia clínica y trámites\n6. Programa de acompañamiento para primera visita\n7. Encuesta de satisfacción en tiempo real post-interacción\n8. Gamificación de procesos administrativos\n9. Oficina de experiencia del usuario (UX Office)\n10. Integración con WhatsApp Business para notificaciones\n\nSelección: Votación por impacto (●) y factibilidad (○)",
                'prototipar' => "PROTOTIPAR — MVP Y PRUEBAS\n\nPrototipo seleccionado: App móvil de autogestión\n\n1. Funcionalidades del MVP:\n• Consulta de turnos y estado de trámites\n• Notificaciones push de recordatorios\n• Chat básico con asistente virtual\n• Calendario de citas\n\n2. Materiales:\n• Wireframes en Figma (5 pantallas principales)\n• Prototipo clickable para pruebas de usabilidad\n• Guión de prueba con 3 escenarios\n\n3. Plan de prueba:\n• 5 usuarios reales del hospital\n• Tareas: agendar cita, consultar estado, cancelar turno\n• Métricas: tiempo por tarea, errores, satisfacción (SUS)\n\n4. Iteraciones esperadas: 2-3 antes de desarrollo",
                'testear' => "TESTEAR — VALIDACIÓN CON USUARIOS\n\n1. Resultados de pruebas de usabilidad:\n• Tarea 1 (Agendar cita): Éxito 80%, tiempo promedio 2:15 min\n• Tarea 2 (Consultar estado): Éxito 100%, tiempo promedio 0:45 min\n• Tarea 3 (Cancelar turno): Éxito 60%, tiempo promedio 3:10 min ← requiere ajuste\n\n2. Feedback cualitativo:\n• \"Me gusta que me avise cuando mi turno está cerca\"\n• \"No entendí cómo cancelar, tuve que buscar ayuda\"\n• \"Los colores son amigables, se siente moderno\"\n\n3. Ajustes necesarios:\n• Rediseñar flujo de cancelación (más visible)\n• Agregar confirmación visual después de cada acción\n• Incluir tutorial de primera vez (onboarding)\n\n4. Puntuación SUS (System Usability Scale): 78/100 (Bueno, objetivo >80)",
            ],
            'Hoshin Kanri' => [
                'catchball' => "DESPLIEGUE EN CATCHBALL\n\n1. Objetivo Anual (Hoshin):\n• [Objetivo de la alta dirección]\n\n2. Negociación Catchball:\nNivel 1 (Dirección → Gerencias):\n• Propuesta: [Meta anual]\n• Contrapropuesta: [Ajuste según capacidad]\n• Acuerdo: [Meta consensuada]\n\nNivel 2 (Gerencias → Jefaturas):\n• Propuesta: [Despliegue táctico]\n• Contrapropuesta: [Recursos necesarios]\n• Acuerdo: [Plan de acción]\n\n3. Matriz de despliegue:\n• Objetivo → Indicador → Meta → Responsable → Plazo\n• [Fila por cada nivel de despliegue]\n\n4. Acuerdos registrados: [Número] de [Número] equipos alineados",
                'control' => "CONTROL DIARIO — GESTIÓN VISUAL\n\n1. Tablero de Control Diario:\n• Indicador 1: [Nombre] — Actual: [Valor] vs Meta: [Valor] — [Semáforo]\n• Indicador 2: [Nombre] — Actual: [Valor] vs Meta: [Valor] — [Semáforo]\n• Indicador 3: [Nombre] — Actual: [Valor] vs Meta: [Valor] — [Semáforo]\n\n2. Reunión diaria (15 min):\n• ¿Qué se logró ayer?\n• ¿Qué se hará hoy?\n• ¿Hay algún bloqueo?\n\n3. Registro de anomalías:\n• [Fecha] - [Anomalía] - [Causa raíz] - [Acción] - [Responsable]\n\n4. Pareto de problemas (última semana):\n• [Problema 1]: [Frecuencia] ocurrencias - [%] del total\n• [Problema 2]: [Frecuencia] ocurrencias - [%] del total",
                'revision' => "REVISIÓN DEL PRESIDENTE — DIAGNÓSTICO ESTRATÉGICO\n\n1. Cumplimiento de Hoshin:\n• Objetivos anuales: [N]/[N] en verde, [N] en amarillo, [N] en rojo\n• Indicadores clave: [%] dentro de meta\n\n2. Análisis de brechas:\n• Brecha 1: [Descripción] — Causa: [Causa] — Plan: [Acción]\n• Brecha 2: [Descripción] — Causa: [Causa] — Plan: [Acción]\n\n3. Decisiones estratégicas:\n• [Decisión 1]\n• [Decisión 2]\n\n4. Próximo período:\n• Ajustes al plan Hoshin para siguiente ciclo\n• Recursos adicionales requeridos\n• Fecha de próxima revisión: [Fecha]",
            ],
        ];

        foreach ($templates as $fKey => $pasos) {
            if (stripos($faseName, $fKey) !== false || stripos($pasoName, $fKey) !== false || stripos($prompt, $fKey) !== false) {
                foreach ($pasos as $pKey => $content) {
                    if (stripos($faseName . ' ' . $pasoName . ' ' . $prompt, $pKey) !== false) {
                        return $content;
                    }
                }
                return reset($pasos);
            }
        }

        return "ANÁLISIS Y RECOMENDACIONES\n\nFase: {$faseName}\nPaso: {$pasoNum} - {$pasoName}\n\nBasado en el contexto proporcionado, te sugiero el siguiente desarrollo:\n\n1. Comienza por analizar los insumos de las fases anteriores del plan estratégico.\n2. Documenta los hallazgos específicos para '{$pasoName}' en el contexto de '{$faseName}'.\n3. Identifica implicaciones prácticas y define criterios de éxito medibles.\n4. Establece actividades concretas con responsables y fechas.\n\nPuedes editar este contenido directamente en el editor. Si necesitas un enfoque diferente, vuelve a hacer clic en 'Sugerir con IA'.";
    }

    // ========================================================================
    // RECOMENDACIONES IA
    // ========================================================================

    /**
     * Genera recomendaciones IA para un elemento de planeación
     */
    public function generarRecomendacion(string $contexto, int $contextoId, string $tipo = 'sugerencia'): ?int {
        $data = $this->getContextData($contexto, $contextoId);

        if (!$data) return null;

        // Construir prompt según el contexto
        $prompt = $this->buildRecommendationPrompt($contexto, $data);

        // Obtener modelo de recomendaciones
        $modelo = $this->getDefaultModelo('recomendacion');
        if (!$modelo) return null;

        $respuesta = $this->callIAProvider($modelo, $this->buildSystemPrompt($contexto, 0), $prompt);

        return $this->core->insert('ia_recomendaciones', [
            'recomendacion_modelo_id'   => $modelo['modelo_id'],
            'recomendacion_contexto'    => $contexto,
            'recomendacion_contexto_id' => $contextoId,
            'recomendacion_contenido'   => $respuesta['texto'],
            'recomendacion_tipo'        => $tipo,
            'recomendacion_prioridad'   => 'media',
            'recomendacion_metadata_json'=> isset($respuesta['metadata']) ? json_encode($respuesta['metadata']) : null
        ]);
    }

    private function getContextData(string $contexto, int $id): ?array {
        switch ($contexto) {
            case 'plan':
                return $this->core->fetchOne(
                    'SELECT p.*, e.empresa_nombre, m.metodologia_nombre
                     FROM plan_planes_estrategicos p
                     JOIN plan_empresas e ON p.plan_empresa_id = e.empresa_id
                     JOIN plan_metodologias m ON p.plan_metodologia_id = m.metodologia_id
                     WHERE p.plan_id = :id', ['id' => $id]
                );
            case 'objetivo':
                return $this->core->fetchOne(
                    'SELECT o.*, p.plan_nombre FROM plan_objetivos o
                     JOIN plan_planes_estrategicos p ON o.objetivo_plan_id = p.plan_id
                     WHERE o.objetivo_id = :id', ['id' => $id]
                );
            case 'estrategia':
                return $this->core->fetchOne(
                    'SELECT e.*, o.objetivo_nombre FROM plan_estrategias e
                     JOIN plan_objetivos o ON e.estrategia_objetivo_id = o.objetivo_id
                     WHERE e.estrategia_id = :id', ['id' => $id]
                );
            case 'proceso':
                return $this->core->fetchOne(
                    'SELECT p.*, m.macro_nombre FROM proc_procesos p
                     JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id
                     WHERE p.proceso_id = :id', ['id' => $id]
                );
            case 'indicador':
                return $this->core->fetchOne(
                    'SELECT i.*, c.categoria_nombre FROM ind_indicadores i
                     JOIN ind_categorias c ON i.indicador_categoria_id = c.categoria_id
                     WHERE i.indicador_id = :id', ['id' => $id]
                );
            default:
                return null;
        }
    }

    private function buildRecommendationPrompt(string $contexto, array $data): string {
        $prompt = "Analiza el siguiente elemento de planeación estratégica y proporciona ";
        $prompt .= "3-5 recomendaciones concretas, accionables y específicas para mejorarlo. ";
        $prompt .= "Considera las mejores prácticas en gestión estratégica.\n\n";
        $prompt .= "DATOS DEL ELEMENTO:\n";
        $prompt .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $prompt;
    }

    public function getRecomendaciones(string $contexto, int $contextoId): array {
        return $this->core->fetchAll(
            'SELECT r.*, m.modelo_nombre
             FROM ia_recomendaciones r
             LEFT JOIN ia_modelos m ON r.recomendacion_modelo_id = m.modelo_id
             WHERE r.recomendacion_contexto = :ctx AND r.recomendacion_contexto_id = :cid
             ORDER BY r.created_at DESC',
            ['ctx' => $contexto, 'cid' => $contextoId]
        );
    }

    public function aplicarRecomendacion(int $recomendacionId): bool {
        return $this->core->update('ia_recomendaciones', [
            'recomendacion_aplicada' => 1
        ], 'recomendacion_id = :id', ['id' => $recomendacionId]) > 0;
    }

    public function feedbackRecomendacion(int $recomendacionId, string $feedback): bool {
        return $this->core->update('ia_recomendaciones', [
            'recomendacion_feedback' => $feedback
        ], 'recomendacion_id = :id', ['id' => $recomendacionId]) > 0;
    }

    // ========================================================================
    // PREDICCIONES IA
    // ========================================================================

    /**
     * Genera predicción de tendencia de un indicador
     */
    public function predecirIndicador(int $indicadorId, int $periodosFuturos = 3): ?array {
        $indicador = $this->core->fetchOne(
            'SELECT i.*, c.categoria_tipo FROM ind_indicadores i
             JOIN ind_categorias c ON i.indicador_categoria_id = c.categoria_id
             WHERE i.indicador_id = :id', ['id' => $indicadorId]
        );
        if (!$indicador) return null;

        // Obtener serie histórica
        $historico = $this->core->fetchAll(
            'SELECT medicion_valor, medicion_fecha, medicion_periodo
             FROM ind_mediciones WHERE medicion_indicador_id = :iid
             ORDER BY medicion_fecha ASC LIMIT 24',
            ['iid' => $indicadorId]
        );

        if (count($historico) < 3) return null;

        // Obtener modelo de predicción
        $modelo = $this->getDefaultModelo('prediccion');
        if (!$modelo) return null;

        // Construir prompt para predicción
        $prompt = "Basado en la siguiente serie histórica, predice el valor del indicador ";
        $prompt .= "para los próximos {$periodosFuturos} períodos. ";
        $prompt .= "Proporciona el valor previsto, un intervalo de confianza y los factores clave que influyen.\n\n";
        $prompt .= "Indicador: {$indicador['indicador_nombre']}\n";
        $prompt .= "Unidad: {$indicador['indicador_unidad_medida']}\n";
        $prompt .= "Tendencia esperada: {$indicador['indicador_tendencia_esperada']}\n\n";
        $prompt .= "SERIE HISTÓRICA:\n";
        foreach ($historico as $h) {
            $prompt .= "- {$h['medicion_fecha']}: {$h['medicion_valor']}\n";
        }

        $prompt .= "\nResponde en formato JSON: {\"predicciones\": [{\"periodo\": \"YYYY-MM\", \"valor\": 0.0, \"confianza_min\": 0.0, \"confianza_max\": 0.0}], \"factores\": [\"factor1\"]}";

        $respuesta = $this->callIAProvider($modelo, 'Eres un experto en análisis predictivo de indicadores de gestión.', $prompt);

        // Guardar predicción
        $prediccionData = json_decode($respuesta['texto'], true);
        $prediccionId = $this->core->insert('ia_predicciones', [
            'prediccion_modelo_id'      => $modelo['modelo_id'],
            'prediccion_indicador_id'   => $indicadorId,
            'prediccion_contexto'       => $indicador['categoria_tipo'],
            'prediccion_valor_previsto' => $prediccionData['predicciones'][0]['valor'] ?? null,
            'prediccion_intervalo_confianza_json' => isset($prediccionData['predicciones'][0])
                ? json_encode($prediccionData['predicciones'][0]) : null,
            'prediccion_horizonte'      => 'corto_plazo',
            'prediccion_fecha_prediccion' => date('Y-m-d'),
            'prediccion_factores_json'  => isset($prediccionData['factores'])
                ? json_encode($prediccionData['factores']) : null
        ]);

        return $this->core->fetchOne(
            'SELECT * FROM ia_predicciones WHERE prediccion_id = :id', ['id' => $prediccionId]
        );
    }

    public function getPredicciones(int $indicadorId): array {
        return $this->core->fetchAll(
            'SELECT * FROM ia_predicciones WHERE prediccion_indicador_id = :iid ORDER BY prediccion_fecha_prediccion DESC LIMIT 5',
            ['iid' => $indicadorId]
        );
    }

    // ========================================================================
    // GENERACIÓN DE CONTENIDO ESTRATÉGICO
    // ========================================================================

    /**
     * Genera contenido borrador para componentes de planeación
     */
    public function generarContenido(string $tipo, array $contexto): array {
        $modelo = $this->getDefaultModelo('generacion');
        if (!$modelo) {
            return ['success' => false, 'message' => 'No hay modelo de generación configurado'];
        }

        $prompt = $this->buildGeneracionPrompt($tipo, $contexto);
        $systemPrompt = "Eres un experto en planeación estratégica y gestión empresarial. Generas contenido profesional, estructurado y listo para usar en documentos de planeación estratégica.";

        $respuesta = $this->callIAProvider($modelo, $systemPrompt, $prompt);

        return [
            'success'   => true,
            'contenido' => $respuesta['texto'],
            'tipo'      => $tipo,
            'metadata'  => $respuesta['metadata'] ?? []
        ];
    }

    private function buildGeneracionPrompt(string $tipo, array $contexto): string {
        switch ($tipo) {
            case 'mision':
                return "Genera una declaración de MISIÓN empresarial para una empresa del sector {$contexto['sector']} llamada '{$contexto['empresa']}'. La misión debe ser concisa (1-2 frases), inspiradora y responder: ¿qué hace la empresa?, ¿para quién?, ¿cómo lo hace?";
            case 'vision':
                return "Genera una declaración de VISIÓN empresarial para '{$contexto['empresa']}' del sector {$contexto['sector']}. Debe ser ambiciosa, orientada al futuro (3-5 años) y responder: ¿qué queremos llegar a ser?";
            case 'valores':
                return "Genera 5-7 VALORES CORPORATIVOS para una empresa del sector {$contexto['sector']}. Cada valor debe tener una breve descripción de lo que significa en la práctica.";
            case 'objetivos':
                return "Genera 3-5 OBJETIVOS ESTRATÉGICOS SMART para {$contexto['empresa']} del sector {$contexto['sector']} usando la metodología {$contexto['metodologia']}. Cada objetivo debe ser Específico, Medible, Alcanzable, Relevante y Temporal.";
            case 'foda':
                return "Genera un análisis FODA (Fortalezas, Oportunidades, Debilidades, Amenazas) para {$contexto['empresa']} del sector {$contexto['sector']}. Proporciona al menos 5 elementos por cada cuadrante. Formato JSON: {\"fortalezas\":[],\"oportunidades\":[],\"debilidades\":[],\"amenazas\":[]}";
            case 'escenarios':
                return "Genera 4 escenarios futuros para {$contexto['empresa']} del sector {$contexto['sector']} usando 2 incertidumbres críticas (ejes X e Y). Para cada escenario proporciona: nombre creativo, probabilidad estimada (%), descripción de 2-3 frases, y estrategia recomendada. Incluye también 3 señales tempranas (early warnings) con indicador y fuente. Formato JSON.";
            case 'pestel':
                return "Genera un análisis PESTEL para {$contexto['empresa']} del sector {$contexto['sector']}. Para cada una de las 6 dimensiones (Político, Económico, Social, Tecnológico, Ecológico, Legal) proporciona 3-4 factores concretos y actuales que afectan a la organización.";
            case 'bsc':
                return "Genera 6 objetivos estratégicos BALANCED SCORECARD para CADA una de las 4 perspectivas de {$contexto['empresa']} del sector {$contexto['sector']}. Perspectivas: Financiera (rentabilidad, crecimiento, eficiencia), Cliente (satisfacción, retención, mercado), Procesos Internos (calidad, eficiencia, innovación), Aprendizaje (talento, tecnología, cultura). Para cada objetivo incluye: nombre, indicador sugerido y meta. Formato: Perspectiva: Financiera\n- Objetivo: ... (KPI: ..., Meta: ...)\n- Objetivo: ... (KPI: ..., Meta: ...)\n\nPerspectiva: Cliente\n...";
            case 'bsc-relaciones':
                $objs = $contexto['objetivos'] ?? 'Objetivos de las 4 perspectivas';
                return "Analiza los siguientes objetivos estratégicos BSC y sugiere 5-8 relaciones causa-efecto entre ellos:\n\n{$objs}\n\nIdentifica cómo un objetivo de una perspectiva causa o habilita otro en una perspectiva diferente. Las relaciones deben seguir la lógica: Aprendizaje → Procesos → Cliente → Financiera. Formato JSON sin markdown, solo el array: [{\"causa\": \"nombre del objetivo causa\", \"efecto\": \"nombre del objetivo efecto\", \"intensidad\": \"Fuerte|Media|Débil\", \"justificacion\": \"breve explicación\"}]";
            case 'indicadores':
                return "Genera 4 indicadores KPIs para cada perspectiva del BSC (Financiera, Cliente, Procesos, Aprendizaje) para {$contexto['empresa']} del sector {$contexto['sector']}. Cada indicador debe incluir: nombre, fórmula de cálculo, unidad de medida, frecuencia de medición y meta sugerida. Formato JSON sin markdown: {\"nombre\":\"...\",\"formula\":\"...\",\"unidad\":\"...\",\"meta\":...}";
            case 'iniciativas':
                return "Genera 3-4 iniciativas estratégicas concretas para {$contexto['empresa']} del sector {$contexto['sector']}. Para cada iniciativa incluye: nombre, tipo (ofensiva/defensiva/innovacion/crecimiento), prioridad (critico/alto/medio/bajo), presupuesto estimado en $, descripción de 2 frases. Formato JSON: [{\"nombre\":\"...\",\"tipo\":\"...\",\"prioridad\":\"...\",\"presupuesto\":...}]";
            case 'evaluacion':
                return "Analiza este plan estratégico: {$contexto['objetivo']}. Sugiere 5-8 mejoras concretas. Para cada una indica: titulo, descripcion breve, tipo (critico/mejora/info), accion (crear_indicador/crear_iniciativa/ajustar_objetivo/ninguna), perspectiva afectada. Formato JSON: [{\"titulo\":\"...\",\"descripcion\":\"...\",\"tipo\":\"...\",\"accion\":\"...\",\"perspectiva\":\"...\"}]";
            case 'proceso':
                return "Contexto: {$contexto['objetivo']} para {$contexto['empresa']} del sector {$contexto['sector']} usando metodología {$contexto['metodologia']}. Genera contenido profesional y específico para este paso de planeación estratégica. Incluye: metas concretas, actividades detalladas, indicadores y recomendaciones.";
            default:
                return "Genera contenido profesional para {$tipo} con el siguiente contexto: " . json_encode($contexto);
        }
    }

    /**
     * Genera el paso a paso guiado de una fase de planeación
     */
    public function generarGuiaPasoAPaso(int $faseId): array {
        $fase = $this->core->fetchOne(
            'SELECT f.*, p.plan_nombre, m.metodologia_nombre, e.empresa_nombre,
                    (SELECT sector_nombre FROM doc_sectores WHERE sector_id = e.empresa_sector_id) as sector
             FROM plan_fases f
             JOIN plan_planes_estrategicos p ON f.fase_plan_id = p.plan_id
             JOIN plan_metodologias m ON p.plan_metodologia_id = m.metodologia_id
             JOIN plan_empresas e ON p.plan_empresa_id = e.empresa_id
             WHERE f.fase_id = :id', ['id' => $faseId]
        );

        if (!$fase) return ['success' => false, 'message' => 'Fase no encontrada'];

        $modelo = $this->getDefaultModelo('generacion');
        if (!$modelo) return ['success' => false, 'message' => 'No hay modelo configurado'];

        $prompt = "Genera una guía detallada paso a paso para completar la fase '{$fase['fase_nombre']}' ";
        $prompt .= "de un plan estratégico usando metodología {$fase['metodologia_nombre']} ";
        $prompt .= "para la empresa {$fase['empresa_nombre']} del sector {$fase['sector']}. ";
        $prompt .= "La guía debe incluir: 5-8 pasos concretos, entregables esperados por paso, ";
        $prompt .= "recomendaciones prácticas, errores comunes a evitar, y estimación de tiempo por paso. ";
        $prompt .= "Formato JSON: {\"pasos\": [{\"numero\": 1, \"titulo\": \"...\", \"descripcion\": \"...\", \"entregable\": \"...\", \"tiempo_estimado_horas\": 0, \"consejos\": []}]}";

        $respuesta = $this->callIAProvider($modelo,
            'Eres un consultor experto en planeación estratégica que guía equipos paso a paso.',
            $prompt
        );

        $guia = json_decode($respuesta['texto'], true);

        // Guardar la guía en la fase
        if ($guia) {
            $this->core->update('plan_fases', [
                'fase_guia_paso_a_paso' => json_encode($guia)
            ], 'fase_id = :id', ['id' => $faseId]);
        }

        return [
            'success' => true,
            'fase'    => $fase['fase_nombre'],
            'guia'    => $guia ?? ['pasos' => []],
            'raw'     => $respuesta['texto']
        ];
    }

    // ========================================================================
    // HISTORIAL DE ASISTENCIAS
    // ========================================================================

    public function getHistorialAsistencias(int $usuarioId, ?string $contexto = null, int $limit = 50): array {
        $sql = 'SELECT a.*, m.modelo_nombre
                FROM ia_asistencias a
                LEFT JOIN ia_modelos m ON a.asistencia_modelo_id = m.modelo_id
                WHERE a.asistencia_usuario_id = :uid';
        $params = ['uid' => $usuarioId];
        if ($contexto) { $sql .= ' AND a.asistencia_contexto = :ctx'; $params['ctx'] = $contexto; }
        $sql .= ' ORDER BY a.created_at DESC LIMIT :limit';
        $params['limit'] = $limit;
        return $this->core->fetchAll($sql, $params);
    }

    public function getUsageStats(): array {
        return [
            'total_asistencias' => $this->core->fetchColumn('SELECT COUNT(*) FROM ia_asistencias'),
            'total_recomendaciones' => $this->core->fetchColumn('SELECT COUNT(*) FROM ia_recomendaciones'),
            'recomendaciones_aplicadas' => $this->core->fetchColumn('SELECT COUNT(*) FROM ia_recomendaciones WHERE recomendacion_aplicada = 1'),
            'total_predicciones' => $this->core->fetchColumn('SELECT COUNT(*) FROM ia_predicciones'),
            'tasa_aplicacion' => $this->core->fetchColumn(
                'SELECT ROUND((SUM(CASE WHEN recomendacion_aplicada = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 2) FROM ia_recomendaciones'
            )
        ];
    }
}
