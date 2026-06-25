# EstrateGIA v1.0 - Contexto de Proyecto

## Acceso
- **URL**: http://100.87.147.122:81
- **Login**: admin@estrategia.com / admin123
- **BD**: MySQL `estrategia_v1` (user: emilio, pass: s1gma)
- **Servidor**: PHP built-in en puerto 8081, Nginx proxy en puerto 81
- **Iniciar**: `cd /home/emilio/EstrateGIA/workspace/public && nohup php -S 127.0.0.1:8081 -t . > /tmp/php.log 2>&1 &`

## Arquitectura
- **68 archivos PHP**, **52 tablas**, **6 triggers**, **15 controladores**
- Patrón: Front Controller (public/index.php) + Router + Auth + Controladores + Templates
- Layout: sidebar con 5 grupos colapsables (Estratégico, Operativo, Calidad, Integraciones, Sistema)
- Selectores empresa/plan en header con cookies

## Módulos (20+)

### 📊 Estratégico
- **Planeación** - 5 metodologías (BSC, OKR, Hoshin Kanri, Escenarios, Design Thinking). Wizard por fase con herramientas (PESTEL, BSC Map, OKR Builder, Canvas Escenarios, Visión/Misión, Guía Paso a Paso)
- **SIG** - Dashboard integrado con KPIs de todos los módulos, ecosistema visual, timeline unificada
- **Calendario** - Vista año/mes/semana/día, tareas de todos los módulos, alertas vencidas, click→modal
- **Evaluación** - Por niveles: macroproceso→proceso→colaboradores, ranking con 4 variantes
- **IA Asistente** - Chat contextual + generador local con plantillas sectoriales

### ⚙️ Operativo
- **Procesos** - CRUD macroprocesos, procesos, procedimientos, tareas. Mapa por 4 tipos (Estratégico, Misional, Apoyo, Evaluación)
- **Indicadores** - 4 variantes (Cumplimiento, Oportunidad, Calidad, Productividad). Semáforos, tendencias
- **Mediciones** - Registro manual, plantilla CSV descargable/subible, últimas mediciones con semáforo
- **Documentos ISO** - Árbol jerárquico (tipo→macroproceso→proceso→documentos). Codificación automática, versiones, upload

### ✅ Calidad
- **Acreditación** - 3 ciclos (SUA 72%, ISO7101 45%, Habilitación 91%). 24 estándares, evidencias con puntaje, autoevaluación
- **PAMEC** - Auditorías programadas con año, tipo, estándar, proceso, auditor, estado
- **NC** - 6 orígenes (Auditoría, Externa, Queja, Incidente, Revisión, Otro). 4 metodologías resolución (5 Porqués, Ishikawa 6M, 8D, PHVA)
- **Riesgos** - Matriz 5×5 (probabilidad×impacto), nivel automático, mapa de calor, IA
- **Proveedores** - Lista de chequeo por tipo con Pareto, pesos, evidencia, comparativo histórico
- **Formación** - Cronograma por meses, estadísticas, CRUD con horas/instructor/calificación
- **Satisfacción** - NPS con gráfico tendencia, registro de encuestas por proceso
- **SST** - ISO 45001. 6 indicadores, 4 peligros, reporte incidentes, 4 informes normativos, IA
- **Ambiental** - ISO 14001. 5 indicadores, 6 aspectos, registro mediciones, 4 informes normativos, IA

### 🔌 Integraciones
- **CRM/Datos** - Conexiones API/BD/ERP. Mapeos, sincronización, consola resultados
- **Minería** - Asistente guiado, ejecución minería, detección patrones

### 🔧 Sistema
- **Usuarios** - CRUD + asignación multiempresa con rol por empresa
- **Permisos** - Selector de rol con botones toggle por módulo/acción
- **Auditoría** - Logs con filtros (fecha, módulo, acción, usuario, búsqueda). Paginación. Todos los módulos registran
- **Configuración** - CRUD empresas, estadísticas sistema, versión

## Datos Demo - Hospital Central (empresa 2)
- 8 objetivos BSC, 5 estrategias, 6 actividades completadas al 100%
- 8 indicadores con 15 mediciones (3 meses: marzo-mayo 2026)
- 15 documentos ISO organizados por proceso
- 11 procesos en 6 macroprocesos (4 tipos)
- 9 evidencias de acreditación con puntajes
- 5 riesgos en matriz con controles
- 4 proveedores con evaluaciones históricas
- 3 NCs (abierta, análisis, cerrada)
- 4 formaciones registradas
- 4 períodos NPS
- 17 tareas en calendario (con subtareas y microtareas)

## Principales endpoints
GET: / /planeacion /procesos /indicadores /mediciones /documentos /evaluacion /ia /crm /nc /calidad /sst /ambiental /calendario /admin/*
POST: /procesos/crear-* /indicadores/crear /mediciones/registrar /nc/crear /nc/actualizar/* /proveedores/evaluar /sst/incidente /ambiental/registrar

## CSS
- Última versión: app.css?v=20
- Menú compacto: padding 3px, gap 6px, font 0.8rem
- Sidebar con localStorage para mantener grupos colapsados

## Pendientes / Mejoras futuras
1. App móvil offline completa (React Native + SQLite)
2. API REST documentada con Swagger UI
3. Tests PHPUnit más completos
4. Exportación PDF de todos los informes
5. Notificaciones push/email para tareas vencidas
6. Dashboard BI con filtros cruzados y drill-down
7. Módulo de encuestas de satisfacción avanzado
8. Integración real con APIs externas (Salesforce, HubSpot, SAP)

## Para continuar rápidamente
```bash
cd /home/emilio/EstrateGIA/workspace/public
nohup php -S 127.0.0.1:8081 -t . > /tmp/php.log 2>&1 &
# Verificar: curl http://localhost:81/login.php
```


## Movil

**Tipo:** PHP PWA
**Estrategia:** Agregar manifest.json + service worker. Planeacion offline. Sincroniza al reconectar.
**Launcher ecosistema:** APK disponible en `Proyectos/MobileLauncher/GMD360-Launcher.apk`
**Instalacion:** Transferir APK al dispositivo, permitir fuentes desconocidas, instalar.
**Publicacion:** Google Play Console → app bundle firmado con key propia (no debug).

