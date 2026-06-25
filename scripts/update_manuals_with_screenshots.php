<?php
/**
 * Actualiza los manuales HTML para incluir screenshots reales
 */

$docsDir = __DIR__ . '/../public/docs/html';
$screenshotsDir = __DIR__ . '/../public/docs/screenshots';

// Mapeo de screenshots a secciones de manuales
$screenshotMap = [
    'manual_usuario.html' => [
        'login' => ['section' => '2. Acceso al Sistema', 'caption' => 'Pantalla de inicio de sesión de EstrateGIA'],
        'dashboard' => ['section' => '3. Interfaz General', 'caption' => 'Dashboard SIG - Vista principal del sistema'],
        'planeacion' => ['section' => '4. Perfil: Director General', 'caption' => 'Lista de planes estratégicos'],
        'planeacion_crear' => ['section' => '4. Perfil: Director General', 'caption' => 'Formulario de creación de plan estratégico'],
        'indicadores' => ['section' => '5. Perfil: Gerente de Área', 'caption' => 'Dashboard de indicadores KPI'],
        'phva' => ['section' => '5. Perfil: Gerente de Área', 'caption' => 'Ciclo PHVA - Mejora continua'],
        'evaluacion' => ['section' => '5. Perfil: Gerente de Área', 'caption' => 'Evaluación de desempeño'],
        'procesos' => ['section' => '5. Perfil: Gerente de Área', 'caption' => 'Gestión de procesos'],
        'calidad' => ['section' => '6. Perfil: Coordinador Calidad/HSE', 'caption' => 'Dashboard de calidad'],
        'sst' => ['section' => '6. Perfil: Coordinador Calidad/HSE', 'caption' => 'Seguridad y Salud en el Trabajo'],
        'ambiental' => ['section' => '6. Perfil: Coordinador Calidad/HSE', 'caption' => 'Gestión ambiental ISO 14001'],
        'soporte' => ['section' => '7. Perfil: Administrador', 'caption' => 'Sistema de soporte técnico'],
        'admin_usuarios' => ['section' => '7. Perfil: Administrador', 'caption' => 'Administración de usuarios'],
        'admin_roles' => ['section' => '7. Perfil: Administrador', 'caption' => 'Gestión de roles y permisos'],
        'documentacion' => ['section' => '7. Perfil: Administrador', 'caption' => 'Centro de documentación'],
    ],
    'manual_programador.html' => [
        'dashboard' => ['section' => '1. Arquitectura del Sistema', 'caption' => 'Arquitectura general del sistema EstrateGIA'],
        'indicadores' => ['section' => '2. Estándares de Código', 'caption' => 'Ejemplo de dashboard de indicadores'],
        'phva' => ['section' => '3. Estructura de Directorios', 'caption' => 'Módulo PHVA implementado'],
        'admin_usuarios' => ['section' => '4. Base de Datos', 'caption' => 'Gestión de usuarios - ejemplo de CRUD'],
    ],
];

foreach ($screenshotMap as $htmlFile => $screenshots) {
    $htmlPath = $docsDir . '/' . $htmlFile;
    if (!file_exists($htmlPath)) {
        echo "⚠️  Archivo no encontrado: $htmlFile\n";
        continue;
    }
    
    $html = file_get_contents($htmlPath);
    $modified = false;
    
    foreach ($screenshots as $screenshot => $info) {
        $screenshotPath = $screenshotsDir . '/' . $screenshot . '.png';
        if (!file_exists($screenshotPath)) {
            echo "⚠️  Screenshot no encontrado: $screenshot.png\n";
            continue;
        }
        
        // Buscar la sección donde insertar el screenshot
        $sectionPattern = '/<h2[^>]*id="section-\d+"[^>]*>' . preg_quote($info['section'], '/') . '<\/h2>/i';
        
        if (preg_match($sectionPattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
            
            // Crear el bloque del screenshot
            $screenshotBlock = <<<HTML

<div class="screenshot">
    <img src="/docs/screenshots/{$screenshot}.png" alt="{$info['caption']}" loading="lazy">
    <div class="screenshot-caption">{$info['caption']}</div>
</div>
HTML;
            
            // Verificar si ya existe el screenshot
            if (strpos($html, $screenshot . '.png') === false) {
                $html = substr_replace($html, $screenshotBlock, $insertPos, 0);
                $modified = true;
                echo "✅ Agregado screenshot '$screenshot' a $htmlFile en sección '{$info['section']}'\n";
            }
        } else {
            echo "⚠️  Sección no encontrada: '{$info['section']}' en $htmlFile\n";
        }
    }
    
    if ($modified) {
        file_put_contents($htmlPath, $html);
        echo "💾 Guardado: $htmlFile\n\n";
    }
}

echo "🎉 Actualización de screenshots completada\n";
