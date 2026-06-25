# Manual de Base de Datos — EstrateGIA v2.0

> **Versión:** 1.0 | **Fecha:** 2026-06-11

---

## 1. Esquema General

- **Motor**: MariaDB / MySQL 8+
- **Base de datos**: `estrategia_v1`
- **Tablas**: 91
- **Foreign Keys**: 123
- **Charset**: UTF-8 (utf8mb4)

### 1.1 Prefijos de tabla por módulo

| Prefijo | Módulo | Tablas |
|---------|--------|--------|
| `plan_` | Planeación Estratégica | 10 |
| `ind_` | Indicadores KPIs | 5 |
| `cal_` | Calidad y Acreditación | 6 |
| `amb_` | Gestión Ambiental | 8 |
| `sst_` | Seguridad y Salud en el Trabajo | 14 |
| `soporte_` | Soporte y Tickets | 2 |
| `sys_` | Sistema (usuarios, roles, permisos) | 10 |
| `doc_` | Documentos y Normas ISO | 5 |
| `proc_` | Procesos | 6 |
| `ia_` | Inteligencia Artificial | 4 |
| `prov_` | Proveedores | 3 |
| `crm_` | CRM | 3 |
| `sector_` | Sectores económicos | 3 |
| `dash_` | Dashboard | 2 |
| `sugerencias_` | Sugerencias | 1 |

---

## 2. Tablas Principales

### 2.1 plan_planes_estrategicos
Plan estratégico (cabecera)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| plan_id | INT PK | ID autoincremental |
| plan_empresa_id | INT FK | Empresa |
| plan_metodologia_id | INT FK | Metodología (BSC, OKR, etc.) |
| plan_nombre | VARCHAR(300) | Nombre del plan |
| plan_estado | ENUM | borrador, en_proceso, ejecucion, completado |
| plan_fecha_inicio | DATE | Fecha inicio |
| plan_fecha_fin | DATE | Fecha fin |

### 2.2 plan_fases
Fases del plan (creadas automáticamente por metodología)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| fase_id | INT PK | ID |
| fase_plan_id | INT FK | Plan |
| fase_nombre | VARCHAR(300) | Nombre de la fase |
| fase_orden | INT | Orden secuencial |
| fase_estado | ENUM | pendiente, en_proceso, completada |
| fase_guia_paso_a_paso | JSON | Pasos guiados y contenido |

### 2.3 plan_objetivos
Objetivos estratégicos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| objetivo_id | INT PK | ID |
| objetivo_plan_id | INT FK | Plan |
| objetivo_fase_id | INT FK | Fase (opcional) |
| objetivo_nombre | VARCHAR(300) | Nombre |
| objetivo_perspectiva | ENUM | financiera, cliente, procesos, aprendizaje |
| objetivo_avance_porcentaje | DECIMAL(5,2) | % avance |

### 2.4 plan_estrategias
Iniciativas estratégicas

| Campo | Tipo | Descripción |
|-------|------|-------------|
| estrategia_id | INT PK | ID |
| estrategia_objetivo_id | INT FK | Objetivo |
| estrategia_nombre | VARCHAR(300) | Nombre |
| estrategia_tipo | ENUM | ofensiva, defensiva, adaptativa, etc. |
| estrategia_presupuesto | DECIMAL(18,2) | Presupuesto |
| estrategia_avance_porcentaje | DECIMAL(5,2) | % avance |

### 2.5 ind_indicadores
Indicadores KPIs

| Campo | Tipo | Descripción |
|-------|------|-------------|
| indicador_id | INT PK | ID |
| indicador_plan_id | INT FK | Plan |
| indicador_objetivo_id | INT FK | Objetivo |
| indicador_proceso_id | INT FK | Proceso |
| indicador_nombre | VARCHAR(300) | Nombre |
| indicador_formula | TEXT | Fórmula de cálculo |
| indicador_unidad_medida | VARCHAR(50) | % , $, horas, etc. |
| indicador_rango_maximo | DECIMAL(12,4) | Meta |
| indicador_frecuencia_medicion | ENUM | diaria a anual |

### 2.6 ind_mediciones
Mediciones periódicas de indicadores

| Campo | Tipo | Descripción |
|-------|------|-------------|
| medicion_id | INT PK | ID |
| medicion_indicador_id | INT FK | Indicador |
| medicion_periodo | VARCHAR(7) | YYYY-MM |
| medicion_valor | DECIMAL | Valor medido |
| medicion_semaforo | VARCHAR(20) | verde, amarillo, rojo |

### 2.7 hse_autoevaluaciones (JSON en plan_empresas)
Autoevaluaciones ambientales y SST

| Campo | Tipo | Descripción |
|-------|------|-------------|
| empresa_autoeval_ambiental_json | LONGTEXT | Valores ISO 14001 |
| empresa_autoeval_ambiental_historial_json | LONGTEXT | Historial periódico |
| empresa_autoeval_sst_json | LONGTEXT | Valores Decreto 1072 |
| empresa_autoeval_sst_historial_json | LONGTEXT | Historial periódico |

### 2.8 soporte_tickets
Sistema de tickets de soporte

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT PK | ID |
| modulo_afectado | VARCHAR(100) | Módulo |
| asunto | VARCHAR(300) | Título |
| prioridad | ENUM | CRITICA, ALTA, MEDIA, BAJA |
| estado | ENUM | ABIERTO, EN_PROGRESO, RESUELTO, CERRADO |
| sla_tipo | ENUM | N1_2H, N2_8H, N3_24H |

---

## 3. Relaciones Clave (Diagrama ER simplificado)

```
plan_empresas 1──N plan_planes_estrategicos 1──N plan_fases
plan_planes_estrategicos 1──N plan_objetivos 1──N plan_estrategias
plan_objetivos 1──N ind_indicadores 1──N ind_mediciones
plan_objetivos 1──N ind_indicadores N──1 ind_categorias
plan_planes_estrategicos 1──N ind_indicadores
```

---

## 4. JSON Columns

Varias tablas usan columnas JSON para datos flexibles:
- `plan_fases.fase_guia_paso_a_paso` — Pasos guiados y contenido de la fase
- `plan_analisis_contexto.analisis_contenido` — Datos FODA/PESTEL
- `ind_indicadores.indicador_semaforo_json` — Configuración de semáforo
- `doc_normas_iso.norma_requisitos_json` — Requisitos de la norma
- `cal_estandares_acreditacion.estandar_requisitos_json` — Requisitos del estándar

---

**EstrateGIA v2.0** — Documento generado el 11 de junio de 2026
