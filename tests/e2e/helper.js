/**
 * EstrateGIA E2E Test Helper
 * Framework de pruebas end-to-end con Playwright
 * 
 * Basado en el modelo de GestionMD360-IPS v3.0
 * Adaptado para EstrateGIA v2.1
 */

const { chromium } = require('playwright');

// ============================================================================
// CONFIGURACIÓN DE USUARIOS
// ============================================================================

const USERS = {
    ADMIN: { email: 'admin@estrategia.com', password: 'admin123', rol: 'SUPER_ADMIN', rol_id: 9 },
    // Usuarios adicionales se pueden agregar aquí cuando se creen en la BD
};

// ============================================================================
// CONFIGURACIÓN DE MÓDULOS
// ============================================================================

const ALL_MODULES = [
    { path: '/', name: 'SIG Dashboard', icon: 'cubes' },
    { path: '/planeacion', name: 'Planeación', icon: 'bullseye' },
    { path: '/planeacion/crear', name: 'Crear Plan', icon: 'plus' },
    { path: '/indicadores', name: 'Indicadores', icon: 'gauge-high' },
    { path: '/evaluacion', name: 'Evaluación', icon: 'user-check' },
    { path: '/procesos', name: 'Procesos', icon: 'diagram-project' },
    { path: '/calidad', name: 'Calidad', icon: 'certificate' },
    { path: '/calidad/pamec', name: 'PAMEC', icon: 'search' },
    { path: '/calidad/riesgos', name: 'Riesgos', icon: 'bolt' },
    { path: '/nc', name: 'No Conformidades', icon: 'triangle-exclamation' },
    { path: '/proveedores', name: 'Proveedores', icon: 'truck' },
    { path: '/documentos', name: 'Documentos', icon: 'file-lines' },
    { path: '/sst', name: 'SST', icon: 'hard-hat' },
    { path: '/ambiental', name: 'Ambiental', icon: 'leaf' },
    { path: '/soporte', name: 'Soporte', icon: 'headset' },
    { path: '/soporte/tickets', name: 'Tickets', icon: 'ticket' },
    { path: '/formacion', name: 'Formación', icon: 'graduation-cap' },
    { path: '/satisfaccion', name: 'Satisfacción', icon: 'face-smile' },
    { path: '/crm', name: 'CRM', icon: 'plug' },
    { path: '/calendario', name: 'Calendario', icon: 'calendar-alt' },
    { path: '/ia', name: 'IA Asistente', icon: 'brain' },
    { path: '/admin/usuarios', name: 'Usuarios', icon: 'users', admin: true },
    { path: '/admin/roles', name: 'Roles', icon: 'shield-halved', admin: true },
    { path: '/admin/auditoria', name: 'Auditoría', icon: 'history', admin: true },
    { path: '/admin/config', name: 'Configuración', icon: 'gear', admin: true },
    { path: '/tests', name: 'Test Runner', icon: 'flask', admin: true },
];

// ============================================================================
// FUNCIONES DE AUTENTICACIÓN
// ============================================================================

async function login(page, user = USERS.ADMIN) {
    await page.goto('http://100.87.147.122:81/login.php');
    await page.fill('input[name="email"]', user.email);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 }).catch(() => {});
    return page.url().includes('index.php');
}

async function logout(page) {
    await page.goto('http://100.87.147.122:81/login.php?logout=1');
}

// ============================================================================
// VALIDACIÓN DE PÁGINAS
// ============================================================================

const STATUS = {
    OK: 'OK',
    FATAL: 'FATAL',
    WARNING: 'WARNING',
    FORBIDDEN: '403',
    NO_LAYOUT: 'SIN_LAYOUT',
    LOGIN: 'LOGIN',
    NOT_FOUND: '404',
};

async function validatePage(page, module) {
    const url = `http://100.87.147.122:81${module.path}`;
    
    try {
        const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 10000 });
        const statusCode = response ? response.status() : 0;
        const content = await page.content();
        
        // Verificar redirección a login
        if (page.url().includes('login.php')) {
            return { status: STATUS.LOGIN, module: module.name, path: module.path, detail: 'Redirigido a login' };
        }
        
        // Verificar 404
        if (statusCode === 404) {
            return { status: STATUS.NOT_FOUND, module: module.name, path: module.path, detail: 'Ruta no encontrada' };
        }
        
        // Verificar 403
        if (statusCode === 403) {
            return { status: STATUS.FORBIDDEN, module: module.name, path: module.path, detail: 'Acceso denegado' };
        }
        
        // Verificar errores fatales de PHP
        if (content.includes('Fatal error') || content.includes('500 - Error interno')) {
            const errorMatch = content.match(/Fatal error:.*?(?=<|$)/s) || content.match(/Error interno.*?(?=<|$)/s);
            return { status: STATUS.FATAL, module: module.name, path: module.path, detail: errorMatch ? errorMatch[0].substring(0, 200) : 'Error 500' };
        }
        
        // Verificar warnings de PHP
        if (content.includes('Warning:') && !content.includes('sidebar')) {
            const warnMatch = content.match(/Warning:.*?(?=<|$)/s);
            return { status: STATUS.WARNING, module: module.name, path: module.path, detail: warnMatch ? warnMatch[0].substring(0, 200) : 'Warning detectado' };
        }
        
        // Verificar que tiene layout (sidebar con EstrateGIA)
        if (!content.includes('sidebar') && !content.includes('EstrateGIA')) {
            return { status: STATUS.NO_LAYOUT, module: module.name, path: module.path, detail: 'Sin layout/sidebar' };
        }
        
        return { status: STATUS.OK, module: module.name, path: module.path, detail: 'OK' };
        
    } catch (error) {
        return { status: STATUS.FATAL, module: module.name, path: module.path, detail: error.message.substring(0, 200) };
    }
}

// ============================================================================
// SCREENSHOTS
// ============================================================================

async function takeScreenshot(page, module, user) {
    const filename = `${user.rol}_${module.name.replace(/[^a-zA-Z0-9]/g, '_')}.png`;
    const filepath = `tests/e2e/screenshots/${filename}`;
    await page.screenshot({ path: filepath, fullPage: true });
    return filepath;
}

// ============================================================================
// AUDITORÍA COMPLETA
// ============================================================================

async function runAudit(options = {}) {
    const { fast = false, screenshots = false, users = [USERS.ADMIN] } = options;
    
    const browser = await chromium.launch({ headless: true });
    const results = [];
    
    for (const user of users) {
        const context = await browser.newContext();
        const page = await context.newPage();
        
        const loggedIn = await login(page, user);
        if (!loggedIn) {
            results.push({ status: STATUS.FATAL, module: 'LOGIN', path: '/login.php', detail: `No se pudo loguear como ${user.email}` });
            await context.close();
            continue;
        }
        
        const modulesToTest = fast ? ALL_MODULES.slice(0, 10) : ALL_MODULES;
        
        for (const module of modulesToTest) {
            if (module.admin && user.rol_id !== 1 && user.rol_id !== 9 && user.rol_id !== 10) {
                results.push({ status: STATUS.FORBIDDEN, module: module.name, path: module.path, detail: `Rol ${user.rol} no tiene acceso admin` });
                continue;
            }
            
            const result = await validatePage(page, module);
            result.user = user.rol;
            results.push(result);
            
            if (screenshots && result.status === STATUS.OK) {
                await takeScreenshot(page, module, user);
            }
        }
        
        await logout(page);
        await context.close();
    }
    
    await browser.close();
    return results;
}

// ============================================================================
// REPORTE
// ============================================================================

function generateReport(results) {
    const total = results.length;
    const ok = results.filter(r => r.status === STATUS.OK).length;
    const fatal = results.filter(r => r.status === STATUS.FATAL).length;
    const warning = results.filter(r => r.status === STATUS.WARNING).length;
    const forbidden = results.filter(r => r.status === STATUS.FORBIDDEN).length;
    const noLayout = results.filter(r => r.status === STATUS.NO_LAYOUT).length;
    const login = results.filter(r => r.status === STATUS.LOGIN).length;
    const notFound = results.filter(r => r.status === STATUS.NOT_FOUND).length;
    
    let report = `\n${'═'.repeat(60)}\n`;
    report += `  REPORTE DE AUDITORÍA E2E — EstrateGIA v2.1\n`;
    report += `${'═'.repeat(60)}\n\n`;
    report += `  Total: ${total} | OK: ${ok} | FATAL: ${fatal} | WARNING: ${warning}\n`;
    report += `  403: ${forbidden} | SIN_LAYOUT: ${noLayout} | LOGIN: ${login} | 404: ${notFound}\n\n`;
    
    if (fatal > 0) {
        report += `  ERRORES FATALES:\n`;
        report += `  ${'─'.repeat(50)}\n`;
        results.filter(r => r.status === STATUS.FATAL).forEach(r => {
            report += `  💀 ${r.module} (${r.path}): ${r.detail}\n`;
        });
        report += '\n';
    }
    
    if (warning > 0) {
        report += `  WARNINGS:\n`;
        report += `  ${'─'.repeat(50)}\n`;
        results.filter(r => r.status === STATUS.WARNING).forEach(r => {
            report += `  ⚠️  ${r.module} (${r.path}): ${r.detail}\n`;
        });
        report += '\n';
    }
    
    if (noLayout > 0) {
        report += `  SIN LAYOUT:\n`;
        report += `  ${'─'.repeat(50)}\n`;
        results.filter(r => r.status === STATUS.NO_LAYOUT).forEach(r => {
            report += `  📄 ${r.module} (${r.path}): ${r.detail}\n`;
        });
        report += '\n';
    }
    
    report += `${'═'.repeat(60)}\n`;
    report += `  Resultado: ${fatal === 0 && warning === 0 && noLayout === 0 ? '✅ APROBADO' : '❌ REQUIERE ATENCIÓN'}\n`;
    report += `${'═'.repeat(60)}\n`;
    
    return report;
}

// ============================================================================
// EXPORTS
// ============================================================================

module.exports = {
    USERS,
    ALL_MODULES,
    STATUS,
    login,
    logout,
    validatePage,
    takeScreenshot,
    runAudit,
    generateReport,
};
