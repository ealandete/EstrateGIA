<?php
declare(strict_types=1);

/**
 * Generador Automático de KPIs
 * Genera indicadores para objetivos que tienen <2 KPIs
 */

require_once __DIR__ . '/../lib/EstrateGiaCore.php';

function generarKPIsFaltantes(): array {
    $core = EstrateGiaCore::getInstance();
    $planId = 5; // Plan activo
    
    // Obtener objetivos con <2 KPIs
    $objetivos = $core->fetchAll("
        SELECT 
            o.objetivo_id,
            o.objetivo_nombre,
            o.objetivo_perspectiva,
            COUNT(i.indicador_id) as kpi_count
        FROM plan_objetivos o
        LEFT JOIN ind_indicadores i ON o.objetivo_id = i.indicador_objetivo_id
        WHERE o.objetivo_plan_id = :planId
        GROUP BY o.objetivo_id
        HAVING kpi_count < 2
        ORDER BY kpi_count ASC, o.objetivo_id
    ", ['planId' => $planId]);
    
    $generados = [];
    
    foreach ($objetivos as $obj) {
        $kpisNecesarios = 2 - $obj['kpi_count'];
        
        for ($i = 0; $i < $kpisNecesarios; $i++) {
            $kpi = generarKPIParaObjetivo($obj, $i);
            
            if ($kpi) {
                $core->insert('ind_indicadores', [
                    'indicador_categoria_id' => 1, // Cumplimiento de Metas
                    'indicador_plan_id' => $planId,
                    'indicador_objetivo_id' => $obj['objetivo_id'],
                    'indicador_nombre' => $kpi['nombre'],
                    'indicador_formula' => $kpi['formula'],
                    'indicador_unidad_medida' => $kpi['unidad'],
                    'indicador_frecuencia_medicion' => $kpi['frecuencia'],
                    'indicador_rango_minimo' => $kpi['minimo'],
                    'indicador_rango_maximo' => $kpi['maximo'],
                    'indicador_tendencia_esperada' => $kpi['tendencia'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                
                $generados[] = [
                    'objetivo' => $obj['objetivo_nombre'],
                    'kpi' => $kpi['nombre'],
                ];
            }
        }
    }
    
    return $generados;
}

function generarKPIParaObjetivo(array $obj, int $indice): ?array {
    $nombre = strtolower($obj['objetivo_nombre']);
    $perspectiva = $obj['objetivo_perspectiva'];
    $contexto = mb_substr($obj['objetivo_nombre'], 0, 25, 'UTF-8');
    
    // Mapeo de palabras clave a KPIs sugeridos
    $mapeoKPIs = [
        // Financiera
        'ingreso' => [
            ['nombre' => 'Ingresos - ' . $contexto, 'formula' => 'Suma de ingresos en el período', 'unidad' => 'Millones COP', 'frecuencia' => 'anual', 'minimo' => 0, 'maximo' => 10000, 'tendencia' => 'ascendente'],
            ['nombre' => 'Crecimiento ingresos - ' . $contexto, 'formula' => '((Ingresos actuales - Ingresos anteriores) / Ingresos anteriores) × 100', 'unidad' => '%', 'frecuencia' => 'anual', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
        ],
        'diversificar' => [
            ['nombre' => 'Fuentes ingreso - ' . $contexto, 'formula' => 'Conteo de líneas de negocio activas', 'unidad' => 'Cantidad', 'frecuencia' => 'trimestral', 'minimo' => 1, 'maximo' => 20, 'tendencia' => 'ascendente'],
            ['nombre' => '% Ingresos diversificados - ' . $contexto, 'formula' => '(Ingresos de nuevas fuentes / Ingresos totales) × 100', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
        ],
        'productividad' => [
            ['nombre' => 'ROI - ' . $contexto, 'formula' => '(Utilidad neta / Capital invertido) × 100', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
            ['nombre' => 'Productividad - ' . $contexto, 'formula' => 'Ingresos totales / Número de empleados', 'unidad' => 'Millones COP/emp', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 500, 'tendencia' => 'ascendente'],
        ],
        
        // Cliente
        'quejas' => [
            ['nombre' => 'Tasa quejas - ' . $contexto, 'formula' => '(Número de quejas / Total de clientes) × 1000', 'unidad' => 'Por mil', 'frecuencia' => 'mensual', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'descendente'],
            ['nombre' => 'Tiempo resolución - ' . $contexto, 'formula' => 'Suma de días de resolución / Número de quejas', 'unidad' => 'Días', 'frecuencia' => 'mensual', 'minimo' => 0, 'maximo' => 30, 'tendencia' => 'descendente'],
        ],
        'satisfacción' => [
            ['nombre' => 'Satisfacción cliente - ' . $contexto, 'formula' => 'Promedio de encuestas de satisfacción', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
            ['nombre' => 'NPS - ' . $contexto, 'formula' => '% Promotores - % Detractores', 'unidad' => 'Puntos', 'frecuencia' => 'trimestral', 'minimo' => -100, 'maximo' => 100, 'tendencia' => 'ascendente'],
        ],
        'personalizar' => [
            ['nombre' => '% Personalización - ' . $contexto, 'formula' => '(Ofertas personalizadas aceptadas / Total ofertas) × 100', 'unidad' => '%', 'frecuencia' => 'mensual', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
            ['nombre' => 'Segmentos atendidos - ' . $contexto, 'formula' => 'Conteo de segmentos con oferta personalizada', 'unidad' => 'Cantidad', 'frecuencia' => 'trimestral', 'minimo' => 1, 'maximo' => 20, 'tendencia' => 'ascendente'],
        ],
        'expandir' => [
            ['nombre' => 'Ingresos nuevos segmentos - ' . $contexto, 'formula' => 'Suma de ingresos de segmentos nuevos', 'unidad' => 'Millones COP', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 5000, 'tendencia' => 'ascendente'],
            ['nombre' => '% Penetración mercado - ' . $contexto, 'formula' => '(Clientes nuevos / Total mercado objetivo) × 100', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
        ],
        
        // Procesos
        'mejora continua' => [
            ['nombre' => 'Mejoras implementadas - ' . $contexto, 'formula' => 'Conteo de mejoras implementadas en el período', 'unidad' => 'Cantidad', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
            ['nombre' => '% Éxito mejoras - ' . $contexto, 'formula' => '(Mejoras con impacto positivo / Total mejoras) × 100', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
        ],
        'eficiencia energética' => [
            ['nombre' => 'Consumo energético - ' . $contexto, 'formula' => 'Total kWh / Unidades producidas', 'unidad' => 'kWh/unidad', 'frecuencia' => 'mensual', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'descendente'],
            ['nombre' => 'Reducción consumo - ' . $contexto, 'formula' => '((Consumo anterior - Consumo actual) / Consumo anterior) × 100', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
        ],
        'desperdicios' => [
            ['nombre' => 'Tasa desperdicios - ' . $contexto, 'formula' => '(Material desperdiciado / Total material) × 100', 'unidad' => '%', 'frecuencia' => 'mensual', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'descendente'],
            ['nombre' => 'Costo desperdicios - ' . $contexto, 'formula' => 'Valor monetario de materiales desperdiciados', 'unidad' => 'Millones COP', 'frecuencia' => 'mensual', 'minimo' => 0, 'maximo' => 1000, 'tendencia' => 'descendente'],
        ],
        'ciberseguridad' => [
            ['nombre' => 'Incidentes seguridad - ' . $contexto, 'formula' => 'Conteo de incidentes de ciberseguridad', 'unidad' => 'Cantidad', 'frecuencia' => 'mensual', 'minimo' => 0, 'maximo' => 50, 'tendencia' => 'descendente'],
            ['nombre' => 'Tiempo respuesta - ' . $contexto, 'formula' => 'Suma de tiempos de respuesta / Número de incidentes', 'unidad' => 'Horas', 'frecuencia' => 'mensual', 'minimo' => 0, 'maximo' => 24, 'tendencia' => 'descendente'],
        ],
        
        // Aprendizaje
        'diversidad' => [
            ['nombre' => 'Índice diversidad - ' . $contexto, 'formula' => '(Empleados de grupos subrepresentados / Total empleados) × 100', 'unidad' => '%', 'frecuencia' => 'semestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
            ['nombre' => 'Brecha salarial - ' . $contexto, 'formula' => '((Salario promedio hombres - Salario promedio mujeres) / Salario promedio hombres) × 100', 'unidad' => '%', 'frecuencia' => 'anual', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'descendente'],
        ],
        'capacitación' => [
            ['nombre' => 'Horas capacitación - ' . $contexto, 'formula' => 'Total horas capacitación / Número de empleados', 'unidad' => 'Horas/emp', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
            ['nombre' => '% Completitud capacitación - ' . $contexto, 'formula' => '(Empleados capacitados / Total empleados) × 100', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
        ],
        'retención' => [
            ['nombre' => 'Rotación personal - ' . $contexto, 'formula' => '(Empleados que salieron / Promedio de empleados) × 100', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'descendente'],
            ['nombre' => 'Satisfacción laboral - ' . $contexto, 'formula' => 'Promedio de encuestas de clima laboral', 'unidad' => '%', 'frecuencia' => 'semestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
        ],
    ];
    
    // Buscar KPIs relevantes
    foreach ($mapeoKPIs as $keyword => $kpis) {
        if (strpos($nombre, $keyword) !== false) {
            return $kpis[$indice % count($kpis)];
        }
    }
    
    // KPIs genéricos por perspectiva
    $genericos = [
        'financiera' => [
            ['nombre' => 'ROI - ' . mb_substr($obj['objetivo_nombre'], 0, 30, 'UTF-8'), 'formula' => '(Ganancia neta / Inversión) × 100', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
            ['nombre' => 'Margen - ' . mb_substr($obj['objetivo_nombre'], 0, 30, 'UTF-8'), 'formula' => '(Utilidad neta / Ingresos) × 100', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
        ],
        'cliente' => [
            ['nombre' => 'Retención - ' . mb_substr($obj['objetivo_nombre'], 0, 30, 'UTF-8'), 'formula' => '(Clientes activos al final / Clientes al inicio) × 100', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
            ['nombre' => 'Atención - ' . mb_substr($obj['objetivo_nombre'], 0, 30, 'UTF-8'), 'formula' => 'Suma de tiempos de atención / Número de atenciones', 'unidad' => 'Minutos', 'frecuencia' => 'mensual', 'minimo' => 0, 'maximo' => 60, 'tendencia' => 'descendente'],
        ],
        'procesos' => [
            ['nombre' => 'Eficiencia - ' . mb_substr($obj['objetivo_nombre'], 0, 30, 'UTF-8'), 'formula' => '(Output real / Output esperado) × 100', 'unidad' => '%', 'frecuencia' => 'mensual', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
            ['nombre' => 'Cumplimiento - ' . mb_substr($obj['objetivo_nombre'], 0, 30, 'UTF-8'), 'formula' => '(Proyectos entregados a tiempo / Total proyectos) × 100', 'unidad' => '%', 'frecuencia' => 'mensual', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
        ],
        'aprendizaje' => [
            ['nombre' => 'Adopción - ' . mb_substr($obj['objetivo_nombre'], 0, 30, 'UTF-8'), 'formula' => '(Usuarios activos de nueva tecnología / Total usuarios) × 100', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
            ['nombre' => 'Innovación - ' . mb_substr($obj['objetivo_nombre'], 0, 30, 'UTF-8'), 'formula' => '(Proyectos innovadores / Total proyectos) × 100', 'unidad' => '%', 'frecuencia' => 'trimestral', 'minimo' => 0, 'maximo' => 100, 'tendencia' => 'ascendente'],
        ],
    ];
    
    $perspKPIs = $genericos[$perspectiva] ?? $genericos['procesos'];
    return $perspKPIs[$indice % count($perspKPIs)];
}

// Ejecutar
echo "🤖 Generador Automático de KPIs — EstrateGIA v2.1\n\n";
echo "Buscando objetivos con <2 KPIs...\n\n";

$generados = generarKPIsFaltantes();

if (empty($generados)) {
    echo "✅ Todos los objetivos tienen al menos 2 KPIs\n";
} else {
    echo "✅ Se generaron " . count($generados) . " KPIs:\n\n";
    foreach ($generados as $g) {
        echo "  • Objetivo: {$g['objetivo']}\n";
        echo "    KPI: {$g['kpi']}\n\n";
    }
}

echo "\nEjecutando simulador para verificar...\n";
