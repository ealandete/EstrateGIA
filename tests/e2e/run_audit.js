#!/usr/bin/env node
/**
 * EstrateGIA E2E Audit Runner
 * Ejecuta auditoría completa de todas las rutas con usuarios autenticados
 * 
 * Uso:
 *   node tests/e2e/run_audit.js           — Auditoría completa
 *   node tests/e2e/run_audit.js --fast    — Auditoría rápida (10 módulos)
 *   node tests/e2e/run_audit.js --screens — Con screenshots
 */

const { runAudit, generateReport, USERS } = require('./helper');
const fs = require('fs');
const path = require('path');

async function main() {
    const args = process.argv.slice(2);
    const fast = args.includes('--fast');
    const screenshots = args.includes('--screens');
    
    console.log('\n🔍 Iniciando auditoría E2E de EstrateGIA v2.1...\n');
    console.log(`   Modo: ${fast ? 'RÁPIDO (10 módulos)' : 'COMPLETO (' + require('./helper').ALL_MODULES.length + ' módulos)'}`);
    console.log(`   Screenshots: ${screenshots ? 'SÍ' : 'NO'}`);
    console.log(`   Usuarios: ${Object.keys(USERS).join(', ')}\n`);
    
    const startTime = Date.now();
    
    try {
        const results = await runAudit({ fast, screenshots, users: Object.values(USERS) });
        const report = generateReport(results);
        
        console.log(report);
        
        // Guardar reporte en archivo
        const reportDir = path.join(__dirname, 'reports');
        if (!fs.existsSync(reportDir)) fs.mkdirSync(reportDir, { recursive: true });
        
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').substring(0, 19);
        const reportFile = path.join(reportDir, `audit_${timestamp}.txt`);
        fs.writeFileSync(reportFile, report);
        
        // Guardar JSON para procesamiento posterior
        const jsonFile = path.join(reportDir, `audit_${timestamp}.json`);
        fs.writeFileSync(jsonFile, JSON.stringify(results, null, 2));
        
        const elapsed = ((Date.now() - startTime) / 1000).toFixed(1);
        console.log(`\n   Reporte guardado: ${reportFile}`);
        console.log(`   JSON guardado: ${jsonFile}`);
        console.log(`   Tiempo: ${elapsed}s\n`);
        
        // Exit code basado en resultados
        const fatal = results.filter(r => r.status === 'FATAL').length;
        const noLayout = results.filter(r => r.status === 'SIN_LAYOUT').length;
        
        if (fatal > 0 || noLayout > 0) {
            process.exit(1);
        }
        
    } catch (error) {
        console.error('\n❌ Error ejecutando auditoría:', error.message);
        process.exit(2);
    }
}

main();
