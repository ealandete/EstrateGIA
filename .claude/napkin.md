# Napkin Runbook - EstrateGIA

## Architecture
- PHP 8.5 vanilla MVC (Front Controller + Router + Managers + Controllers + Templates)
- Path: /home/emilio/estrategia/workspace/
- Entry: public/index.php (199 lines, 163 routes)
- DB: MySQL/MariaDB (emilio/s1gma), base: estrategia_v1 (91 tablas, 123 FKs)
- Core: lib/EstrateGiaCore.php (488 lines, Singleton PDO + Auth + JWT + Cache)
- Managers: 17 archivos en lib/ (PlanManager 685L, AIManager 800L, SSTManager, AmbientalManager...)
- Controllers: 25 en src/Controllers/
- Templates: 82 archivos en 20 subdirectorios
- Local: php -S 127.0.0.1:8081 -t public/ (mejor usar PHP-FPM)
- Nginx proxy: puerto 81 → 8081
- Staging: 200.21.254.11:6611 (deploy via deploy-staging.sh)

## Key Modules (20+)
- Planeacion: 5 metodologias (BSC, OKR, Hoshin Kanri, Escenarios, Design Thinking) + Wizard
- Workbench: 10+ herramientas (PESTEL, FODA, BSC Map, OKR Builder, Canvas, Vision/Mision)
- Procesos: macroprocesos, procesos, procedimientos, tareas con mapa 4 tipos
- Indicadores: 4 variantes (Cumplimiento, Oportunidad, Calidad, Productividad) + semaforos
- Calidad: Acreditacion (SUA/ISO7101/Habilitacion), PAMEC, NC (5W/8D/PHVA/Ishikawa)
- Riesgos: Matriz 5x5, mapa calor, calor automatico, IA
- SST: ISO 45001, 6 indicadores, 4 peligros, incidentes, informes normativos
- Ambiental: ISO 14001, 5 indicadores, 6 aspectos, registros, IA
- Proveedores: checklist por tipo, Pareto, pesos, comparativo historico
- Calendario: vista año/mes/semana/dia, tareas multiorigen, alertas vencidas
- IA Asistente: chat contextual + generador local con plantillas sectoriales
- SIG: dashboard integrado con KPIs todos los modulos, timeline unificada
- Evaluacion: niveles macroproceso→proceso→colaborador, ranking 4 variantes
- Documentos ISO: arbol jerarquico, codificacion automatica, versiones
- Admin: usuarios multi-empresa, permisos toggle por modulo/accion, auditoria, config

## Conventions
- PHP: strict_types=1, camelCase metodos/variables, PascalCase clases, UPPER_SNAKE constantes
- DB: prefijos (plan_, cal_, sst_, amb_, ind_, proc_, sys_, doc_, ia_, prov_, crm_, soporte_)
- Auth: authenticateUser() en EstrateGiaCore, JWT HS256 (8h), 2FA TOTP
- Templates: layout.php con sidebar 5 grupos colapsables, SPA hash-routing fetch+pushState
- CSS: app.css?v=20, menu compacto (padding 3px, gap 6px, font 0.8rem)
- Politicas: 00_MAESTRO.md → 02_BACKEND.md → 01_BD.md → 04_SEGURIDAD.md
- PHPStan level 5 (phpstan.neon: lib/ + src/, exclude templates/ public/)

## Bug Patterns
- Rutas genericas deben definirse ANTES de dispatch (no despues)
- require sin 'return ""' causa comportamiento impredecible en handlers de ruta
- Columnas con nombres en plural vs singular: verificar DESCRIBE antes de queries
- NO hardcodear credenciales, usar .env (EstrateGiaCore tiene fallbacks)

## Do Instead
- Iniciar: cd workspace/public && nohup php -S 127.0.0.1:8081 -t . > /tmp/php.log 2>&1 &
- Verificar: curl http://localhost:81/login.php (debe devolver 200)
- Tests: phpunit desde workspace/ (solo 4 tests, coverage minima)
- Deploy staging: bash deploy-staging.sh (rsync a 200.21.254.11:6623)
- PHPStan: vendor/bin/phpstan analyse
- Backup ejecutar: bash scripts/backup.sh
- Backup verificar: bash scripts/backup_verify.sh
- Backup restaurar: bash scripts/restore.sh
- Refs: /home/emilio/ContextoGeneral/00_MAESTRO.md, /home/emilio/EstrateGIA/CONTEXTO.md
