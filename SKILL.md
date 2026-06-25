---
name: estrategia
description: Contexto de desarrollo para EstrateGIA, sistema de planeacion estrategica y gestion de calidad. Usar al trabajar en modulos de planeacion, procesos, indicadores, riesgos, SST, ambiental o cualquier modulo del sistema.
version: "1.0"
---

# opencode SKILL — EstrateGIA

**Stack:** PHP 8.5 vanilla MVC + MariaDB + Bootstrap 5 + Chart.js
**DB:** MySQL/MariaDB (emilio/s1gma), base: `estrategia_v1` (91 tablas, 123 FKs)
**Puerto:** 81 (Nginx proxy → PHP built-in :8081)
**Modulos:** 20+ en 5 grupos (Estrategico, Operativo, Calidad, Integraciones, Sistema)
**Politicas:** 00_MAESTRO.md → 02_BACKEND.md → 01_BD.md → 04_SEGURIDAD.md

## Arquitectura
- Front Controller: `public/index.php` (199 lineas, 163 rutas)
- Core: `lib/EstrateGiaCore.php` (488 lineas) — Singleton PDO + Auth + JWT + Cache + Encryption
- Managers: 17 archivos en `lib/` (PlanManager, SSTManager, AmbientalManager, AIManager...)
- Controladores: 25 en `src/Controllers/`
- Templates: 82 archivos en 20 subdirectorios bajo `templates/`
- Layout: SPA hash-routing con fetch + history.pushState, sidebar 5 grupos colapsables
- Auth: JWT HS256 (8h) + 2FA TOTP soportado

## Reglas del proyecto
1. DB prefijos obligatorios: plan_, cal_, sst_, amb_, ind_, proc_, sys_, doc_, ia_, prov_, crm_
2. Manager pattern: logica de negocio en Managers, NO en Controllers
3. BaseHSEManager como clase base para SSTManager y AmbientalManager
4. Cache estatico en Managers para evitar consultas repetidas
5. Audit trail: sys_logs_sistema para toda accion sensible
6. Datos demo realistas: Hospital Central (empresa 2) con narrativa coherente
7. NO hardcodear credenciales — EstrateGiaCore tiene fallbacks pero deben eliminarse

## Como probar
- Iniciar: `cd workspace/public && php -S 127.0.0.1:8081 -t .`
- Verificar: `curl http://localhost:81/login.php`
- Tests: `cd workspace && phpunit` (4 suites)
- PHPStan: `vendor/bin/phpstan analyse` (level 2, objetivo level 5)

## Como desplegar
- Staging: `bash deploy-staging.sh` (rsync a 200.21.254.11:6623)
- DB: `database/estrategia_v1_completo.sql` (1325 lineas, incluye schema + seed)

## Referencias
- Contexto: `/home/emilio/EstrateGIA/CONTEXTO.md`
- Runbook: `workspace/.claude/napkin.md`
- Politicas: `/home/emilio/ContextoGeneral/00_MAESTRO.md`
