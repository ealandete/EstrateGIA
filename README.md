# EstrateGIA v1.0

## Sistema de Gestión de Planeación Estratégica con Inteligencia Artificial

EstrateGIA es un sistema integral de gestión de planeación estratégica que guía a las organizaciones paso a paso en la definición, implementación y seguimiento de su estrategia, apoyándose en inteligencia artificial para recomendaciones y construcción de todos los componentes.

### Capacidades Principales

- **Guía paso a paso** en 5 metodologías de planeación (BSC, OKR, Hoshin Kanri, Escenarios, Design Thinking)
- **IA integrada** para recomendaciones, generación de contenido y predicciones
- **4 variantes de seguimiento**: Cumplimiento, Oportunidad, Calidad y Productividad
- **Trazabilidad atómica** hasta nivel de colaborador con mapeo de tiempos
- **Integración automática** con CRMs, ERPs y Web Services externos
- **Minería de datos** para minimizar el registro manual de indicadores
- **Gestión documental ISO** con soporte sectorial (Salud, Inmobiliario, Logística Farmacéutica)
- **Dashboards visuales** con semáforos, tendencias y rankings
- **Dos entornos**: Vista de Planeación y Vista de Procesos

### Stack Tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 7.4+ con PDO |
| Base de Datos | MySQL 5.7+ / MariaDB 10.2+ |
| Frontend Móvil | React Native + React Native Paper |
| Web | ScriptCase 9 Templates |
| IA | OpenAI, Claude, Gemini (configurable) |
| Seguridad | JWT + AES-256 + RBAC |

### Requisitos

- PHP >= 7.4
- MySQL >= 5.7 o MariaDB >= 10.2
- Node.js >= 16 (para app móvil)
- Extensiones PHP: PDO, mysql, openssl, curl, json, mbstring
- Composer (opcional, para futuras dependencias)

### Instalación Rápida

```bash
# 1. Clonar el repositorio
git clone <repo-url> estrategia
cd estrategia/workspace

# 2. Crear la base de datos
mysql -u root -p < database/estrategia_v1_completo.sql

# 3. Configurar la conexión
# Editar lib/EstrateGiaCore.php y ajustar credenciales de BD

# 4. Configurar el servidor web
# Apuntar Apache/Nginx al directorio workspace/

# 5. Instalar app móvil
cd mobile_app
npm install
npx react-native run-android  # o run-ios
```

### Estructura del Proyecto

```
estrategia/
├── workspace/
│   ├── database/
│   │   └── estrategia_v1_completo.sql    # Schema completo
│   ├── docs/
│   │   ├── Data_Flow_Architecture.md     # Arquitectura de datos
│   │   └── Manual_Usuario.md            # Manual de usuario
│   ├── lib/
│   │   ├── EstrateGiaCore.php           # Core del sistema (Singleton + PDO)
│   │   ├── PlanManager.php              # Gestión de planeación estratégica
│   │   ├── ProcessManager.php           # Gestión de procesos
│   │   ├── IndicatorManager.php         # KPIs y evaluaciones
│   │   ├── DocManager.php               # Documentación ISO
│   │   ├── CRMManager.php               # Integración CRM/WS
│   │   ├── AIManager.php                # IA: recomendaciones, predicciones
│   │   └── SystemIntegrator.php         # Orquestador central
│   ├── mobile_app/
│   │   └── src/                         # React Native app
│   ├── scriptcase/
│   │   ├── dashboards/                  # Dashboards web
│   │   └── templates/                   # Formularios y grids
│   └── samples/
│       └── sample_reports.php           # Reportes de ejemplo
├── .MGXEnv.json                         # Configuración MetaGPT
├── .gitignore
└── README.md
```

### Módulos del Sistema

| Módulo | Descripción | Prefijo DB |
|--------|-------------|------------|
| Sistema Base | Usuarios, roles, permisos, logs | `sys_` |
| Planeación | Metodologías, planes, fases, objetivos | `plan_` |
| Procesos | Macroprocesos, procesos, tareas, workflows | `proc_` |
| Indicadores | KPIs, mediciones, metas, evaluaciones | `ind_` |
| CRM/WS | Conexiones, mapeos, minería de datos | `crm_` |
| Documental | Normas ISO, plantillas, auditorías | `doc_` |
| IA | Modelos, recomendaciones, predicciones | `ia_` |
| Dashboards | Tableros, widgets, visualizaciones | `dash_` |

### Metodologías de Planeación Soportadas

1. **Balanced Scorecard (BSC)** - Kaplan y Norton
2. **OKR** - Objectives and Key Results (Google/Intel)
3. **Hoshin Kanri** - Despliegue de Políticas
4. **Planeación por Escenarios**
5. **Design Thinking Estratégico**

### Las 4 Variantes de Indicadores

| Variante | Descripción | Color |
|----------|-------------|-------|
| Cumplimiento | Grado de consecución de objetivos y metas | Verde |
| Oportunidad | Puntualidad en ejecución de actividades | Amarillo |
| Calidad | Nivel de calidad de entregables y resultados | Azul |
| Productividad | Eficiencia en uso de recursos | Púrpura |

### Sectores con Soporte Especializado

- **Salud**: ISO 7101, ISO 15189, Resolución 3100, habilitación, seguridad del paciente
- **Inmobiliario**: ISO 41001, ISO 19650, Ley 820, NTC 6047, gestión de propiedad horizontal
- **Logística Farmacéutica**: ISO 13485, GDP/BPD, ISO 28000, cadena de frío, BPA/BPT, INVIMA

### Licencia

Propietario. Todos los derechos reservados.

### Versión

1.0.0 - Mayo 2026
