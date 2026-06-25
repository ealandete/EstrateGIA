#!/usr/bin/env node
/**
 * Captura de Screenshots del Sistema EstrateGIA
 * Toma screenshots de las páginas principales para documentación
 */

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE_URL = 'http://100.87.147.122:81';
const SCREENSHOTS_DIR = path.join(__dirname, '../public/docs/screenshots');

// Crear directorio si no existe
if (!fs.existsSync(SCREENSHOTS_DIR)) {
    fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
}

const PAGES = [
    { name: 'login', path: '/login.php', desc: 'Pantalla de inicio de sesión' },
    { name: 'dashboard', path: '/', desc: 'Dashboard SIG principal' },
    { name: 'planeacion', path: '/planeacion', desc: 'Lista de planes estratégicos' },
    { name: 'planeacion_crear', path: '/planeacion/crear', desc: 'Formulario de creación de plan' },
    { name: 'indicadores', path: '/indicadores', desc: 'Dashboard de indicadores KPI' },
    { name: 'phva', path: '/phva', desc: 'Ciclo PHVA - Mejora continua' },
    { name: 'evaluacion', path: '/evaluacion', desc: 'Evaluación de desempeño' },
    { name: 'procesos', path: '/procesos', desc: 'Gestión de procesos' },
    { name: 'calidad', path: '/calidad', desc: 'Dashboard de calidad' },
    { name: 'sst', path: '/sst', desc: 'Seguridad y Salud en el Trabajo' },
    { name: 'ambiental', path: '/ambiental', desc: 'Gestión ambiental ISO 14001' },
    { name: 'soporte', path: '/soporte', desc: 'Sistema de soporte técnico' },
    { name: 'admin_usuarios', path: '/admin/usuarios', desc: 'Administración de usuarios' },
    { name: 'admin_roles', path: '/admin/roles', desc: 'Gestión de roles y permisos' },
    { name: 'documentacion', path: '/documentacion', desc: 'Centro de documentación' },
];

async function takeScreenshots() {
    console.log('📸 Iniciando captura de screenshots...\n');
    
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();
    
    // Login
    console.log('🔐 Iniciando sesión...');
    await page.goto(`${BASE_URL}/login.php`);
    await page.fill('input[name="email"]', 'admin@estrategia.com');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });
    console.log('✅ Sesión iniciada\n');
    
    // Tomar screenshots
    let success = 0;
    let failed = 0;
    
    for (const p of PAGES) {
        try {
            console.log(`📷 Capturando: ${p.name} (${p.desc})`);
            await page.goto(`${BASE_URL}${p.path}`, { waitUntil: 'networkidle' });
            await page.waitForTimeout(1000); // Esperar a que cargue todo
            
            const filename = `${p.name}.png`;
            const filepath = path.join(SCREENSHOTS_DIR, filename);
            
            await page.screenshot({
                path: filepath,
                fullPage: false
            });
            
            console.log(`   ✅ Guardado: ${filename}\n`);
            success++;
        } catch (error) {
            console.log(`   ❌ Error: ${error.message}\n`);
            failed++;
        }
    }
    
    await browser.close();
    
    console.log('═'.repeat(60));
    console.log(`📊 Resumen: ${success} exitosos, ${failed} fallidos`);
    console.log(`📁 Ubicación: ${SCREENSHOTS_DIR}`);
    console.log('═'.repeat(60));
}

takeScreenshots().catch(console.error);
