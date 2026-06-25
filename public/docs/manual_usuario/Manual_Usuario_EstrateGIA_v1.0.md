# Manual de Usuario — EstrateGIA v2.0

> **Versión:** 2.0 | **Fecha:** 2026-06-11 | **Perfil:** Planeador Estratégico
>
> Guía completa paso a paso del Sistema Integrado de Planeación Estratégica

---

## ÍNDICE

1. [Primeros Pasos](#1-primeros-pasos)
2. [Crear un Plan Estratégico](#2-crear-un-plan-estratégico)
3. [Metodología BSC — Guía Completa](#3-metodología-bsc--guía-completa)
4. [Metodología OKR](#4-metodología-okr)
5. [Metodología Hoshin Kanri](#5-metodología-hoshin-kanri)
6. [Metodología Escenarios](#6-metodología-escenarios)
7. [Metodología Design Thinking](#7-metodología-design-thinking)
8. [Indicadores KPIs](#8-indicadores-kpis)
9. [Seguimiento del Plan](#9-seguimiento-del-plan)
10. [Reporte Ejecutivo](#10-reporte-ejecutivo)
11. [Evaluación y Ajuste](#11-evaluación-y-ajuste)
12. [Documentos ISO y Calidad](#12-documentos-iso-y-calidad)
13. [Gestión Ambiental (ISO 14001)](#13-gestión-ambiental-iso-14001)
14. [SST (Decreto 1072)](#14-sst-decreto-1072)

---

## 1. Primeros Pasos

### 1.1 Acceso al sistema
Abra su navegador y escriba la URL del servidor con el puerto 81:
```
http://[direccion-del-servidor]:81
```
Ingrese sus credenciales (usuario y contraseña). Si no tiene acceso, solicítelo al administrador.

### 1.2 Pantalla principal
Al ingresar verá:
- **Barra superior**: selector de Empresa (🏢) y Plan (📋). Estos selectores determinan en qué empresa y plan está trabajando.
- **Menú lateral izquierdo**: organizado en 4 grupos desplegables.
- **Área central**: contenido del módulo activo.

### 1.3 Seleccionar empresa y plan
En la barra superior:
1. **Empresa**: seleccione la organización (ej: "Hospital Central")
2. **Plan**: seleccione el plan estratégico activo (ej: "Prueba BSC")

El sistema recordará su selección para la próxima sesión.

### 1.4 Navegación por el menú

| Grupo | Módulo | ¿Qué hace? |
|-------|--------|------------|
| 📊 Estratégico | Planeación | Crear y gestionar planes |
| 📊 Estratégico | Evaluación | Evaluar desempeño por colaborador |
| 📊 Estratégico | IA Asistente | Consultas a inteligencia artificial |
| ⚙️ Operativo | Procesos | Gestión de procesos y procedimientos |
| ⚙️ Operativo | Indicadores | Panel de indicadores KPIs |
| ⚙️ Operativo | Mediciones | Registro de mediciones |
| ⚙️ Operativo | Documentos ISO | Gestión documental con normas ISO |
| ✅ Calidad | Acreditación | Estándares de acreditación |
| ✅ Calidad | PAMEC | Auditorías de mejoramiento |
| ✅ Calidad | NC | No conformidades |
| ✅ Calidad | Riesgos | Matriz de riesgos |
| ✅ Calidad | Proveedores | Gestión de proveedores |
| ✅ Calidad | SST | Seguridad y Salud en el Trabajo |
| ✅ Calidad | Ambiental | Gestión Ambiental ISO 14001 |

---

## 2. Crear un Plan Estratégico

### 2.1 Iniciar
1. Vaya al menú **📊 Estratégico → Planeación**
2. Verá la lista de planes existentes (si los hay)
3. Click en el botón **Nuevo Plan Estratégico** (parte superior)

### 2.2 Formulario de creación
Complete los campos:
- **Empresa**: seleccione la organización
- **Nombre del plan**: ej. "Plan Estratégico 2026-2030"
- **Metodología**: elija una de las 5 disponibles:
  - Balanced Scorecard (BSC) — 7 fases, mapa causa-efecto con 4 perspectivas
  - OKR — 6 fases, objetivos ambiciosos con Key Results medibles
  - Hoshin Kanri — 6 fases, despliegue en cascada con catchball
  - Planeación por Escenarios — 5 fases, matriz 2×2 de incertidumbres
  - Design Thinking — 5 fases, innovación centrada en el usuario
- **Período**: fechas de inicio y fin
- **Descripción**: breve resumen del plan (opcional)

### 2.3 Después de crear
El sistema automáticamente:
1. Crea las fases según la metodología elegida
2. Asigna la primera fase como "en_proceso"
3. Lo redirige al detalle del plan

En el detalle del plan verá:
- **Resumen**: objetivos, indicadores, iniciativas, presupuesto
- **Ruta Crítica**: las fases en orden con su estado
- **Seguimiento**: 4 cards de acceso rápido
- **Mapa BSC** (si la metodología es BSC)

---

## 3. Metodología BSC — Guía Completa

El Balanced Scorecard tiene 7 fases secuenciales. Cada fase se desbloquea al completar la anterior.

### Fase 1: Análisis del Entorno (PESTEL)

**Objetivo**: Analizar el entorno externo de la organización en 6 dimensiones.

**Cómo completarla**:
1. Desde el detalle del plan, haga click en **Fase 1: Análisis del Entorno**
2. Se abre el **Workbench** (banco de trabajo) con la herramienta PESTEL
3. En el panel izquierdo verá:
   - **Asistente IA**: botón "Sugerir análisis PESTEL"
   - **Dimensiones**: Político, Económico, Social, Tecnológico, Ecológico, Legal
4. Click en **🧠 Sugerir análisis PESTEL** para que la IA genere los factores
5. Revise y edite cada dimensión (hasta 4 factores por dimensión)
6. Click en **💾 Guardar avance**
7. Click en **✅ Completar fase** (botón verde abajo)

**Qué obtiene**: Análisis documentado del entorno con factores que afectan a la organización. Este análisis alimenta el FODA y los objetivos.

### Fase 2: Definición de Visión y Misión

**Objetivo**: Establecer la identidad estratégica de la organización.

**Cómo completarla**:
1. Click en **Fase 2: Definición de Visión y Misión** en la ruta crítica
2. El Workbench muestra el **Vision Builder**
3. Use **🧠 Sugerir Misión** y **🧠 Sugerir Visión** para que la IA proponga borradores
4. Edite el texto directamente en el editor
5. También puede definir **Valores corporativos** (hasta 7)
6. Click en **💾 Guardar** y luego **✅ Completar fase**

**Qué obtiene**: Misión, Visión y Valores documentados que guiarán todo el plan.

### Fase 3: Mapa Estratégico

**Objetivo**: Construir el mapa de relaciones causa-efecto entre las 4 perspectivas del BSC.

**Cómo completarla**:
1. Click en **Fase 3: Mapa Estratégico**
2. El Workbench abre el **Mapa Estratégico BSC**
3. **Lienzo del mapa**: muestra las 4 perspectivas (Financiera, Cliente, Procesos, Aprendizaje)
4. **Crear objetivos**:
   - Click en **+ Nuevo objetivo** (panel izquierdo)
   - O use el botón **+** en cada fila de perspectiva
   - En el modal: seleccione la perspectiva y escriba el nombre del objetivo
5. **🧠 Sugerir objetivos por perspectiva**: la IA genera 6 objetivos por cada perspectiva (24 total)
   - Puede hacer click varias veces para obtener más (sin repetir los existentes)
   - Cada objetivo incluye KPI sugerido y meta
6. **Editar/eliminar**: cada objetivo tiene botones ✎ (editar) y ✕ (eliminar)
7. **Matriz de Relaciones**: abajo, construya relaciones causa-efecto entre objetivos
   - Click en **Añadir relación** o **🧠 Sugerir relaciones** (IA)
   - Cada relación tiene: Causa → Efecto con intensidad (Fuerte/Media/Débil)
8. Click en **💾 Guardar mapa** y luego **✅ Completar fase**

**Qué obtiene**: Mapa estratégico con 24 objetivos distribuidos en 4 perspectivas y relaciones causa-efecto.

### Fase 4: Objetivos por Perspectiva

**Objetivo**: Refinar y priorizar los objetivos definidos en el mapa.

**Cómo completarla**:
1. Click en **Fase 4: Objetivos por Perspectiva** (usa el mismo BSC Builder que la fase 3)
2. Revise, edite o agregue más objetivos según necesite
3. Asegure que cada perspectiva tenga al menos 2 objetivos
4. Complete la fase

### Fase 5: Indicadores KPIs y Metas

**Objetivo**: Definir los indicadores de desempeño para medir cada objetivo.

**Cómo completarla**:
1. Click en **Fase 5: Indicadores KPIs y Metas**
2. El Workbench abre el **Indicadores Builder** con:
   - **Panel izquierdo**: Asistente IA y Pareto de Impacto
   - **Panel derecho**: KPIs agrupados por perspectiva (Financiera, Cliente, Procesos, Aprendizaje)
3. **🧠 Sugerir KPIs por perspectiva**: genera 32 indicadores (8 por perspectiva) con:
   - Nombre del indicador
   - Fórmula de cálculo (ej: `EBITDA / Ingresos_Totales * 100`)
   - Unidad de medida (%, $, horas, etc.)
   - Meta numérica
   - Frecuencia de medición (mensual, trimestral, etc.)
4. **Crear manualmente**: botón **+ Nuevo indicador** o los **+** en cada fila de perspectiva
   - Modal con: nombre, objetivo asociado, fórmula, unidad, rango mín, meta (rango máx), fuente, semáforo
5. **Editar/eliminar**: ✎ y ✕ en cada fila
6. **Pareto de Impacto**: gráfico de barras que muestra los 10 KPIs con mayor meta
7. Click en **💾 Guardar** y **✅ Completar fase**

**Qué obtiene**: 32 KPIs con fórmulas, metas y unidades, vinculados a objetivos por perspectiva.

### Fase 6: Iniciativas Estratégicas

**Objetivo**: Definir los proyectos e iniciativas para ejecutar los objetivos.

**Cómo completarla**:
1. Click en **Fase 6: Iniciativas Estratégicas**
2. El Workbench abre el **Iniciativas Builder** con:
   - Iniciativas agrupadas por objetivo
   - Panel lateral con conteo por tipo y presupuesto total
3. **🧠 Sugerir iniciativas**: la IA genera 10 iniciativas con:
   - Nombre, tipo (ofensiva, defensiva, innovación, crecimiento), prioridad, presupuesto, descripción
   - Puede generar más en clicks sucesivos (pool de 24)
4. **Crear manualmente**: botón **+ Nueva iniciativa**
   - Modal con: nombre, objetivo, tipo, prioridad, estado, presupuesto ($), avance (%), descripción
5. Cada iniciativa muestra: nombre, tipo, prioridad, presupuesto, barra de avance, estado
6. Complete la fase

**Qué obtiene**: Cartera de iniciativas con presupuesto total asignado, priorizadas y vinculadas a objetivos.

### Fase 7: Evaluación y Ajuste

**Objetivo**: Evaluar el plan completo y generar recomendaciones de mejora.

**Cómo completarla**:
1. Click en **Fase 7: Evaluación y Ajuste**
2. El Workbench muestra el **Dashboard de Evaluación** con:
   - **Resumen**: objetivos, KPIs, iniciativas, presupuesto, avance del plan
   - **Tabla por perspectiva**: cada objetivo con conteo de KPIs, iniciativas y salud (Sólido/En desarrollo/Requiere atención)
   - **🧠 Sugerir mejoras**: modal con checklist de sugerencias IA
3. **Usar el asistente de mejoras**:
   - Click en **🧠 Sugerir mejoras**
   - Se abre un modal con todas las sugerencias detectadas
   - **Filtrar por perspectiva** (botones ● Financiera, ● Cliente, etc.)
   - Marque las sugerencias que quiera aplicar
   - Click en **✅ Aplicar seleccionadas**: crea automáticamente los KPIs o iniciativas faltantes
4. **Ajustar objetivos**: botón **Ajustar** en cada fila para editar nombre o perspectiva
5. Complete la fase

**Qué obtiene**: Plan completo y balanceado con todos los objetivos cubiertos por KPIs e iniciativas.

### 3.1 Ver el resultado final

Al completar las 7 fases:
1. Vaya al detalle del plan (click en **Planes → Nombre del plan**)
2. Verá el banner **"Plan Estratégico Completado"**
3. Use los 4 cards de **Seguimiento** para ver:
   - **Objetivos y Estrategias**: modal con los 48 objetivos por perspectiva con sus KPIs e iniciativas
   - **Indicadores y Metas**: tabla de KPIs con fórmulas completas
   - **Evaluar Desempeño**: evaluación por colaborador
   - **Despliegue BSC**: las 4 perspectivas con objetivos conectados y sus KPIs

---

## 8. Indicadores KPIs

### 8.1 Acceder al módulo
Hay dos formas de acceder:
- **Menú lateral**: ⚙️ Operativo → Indicadores
- **Desde el plan**: en el detalle, card "Indicadores y Metas"

### 8.2 Panel de indicadores
Al ingresar verá:
- **Selector de plan**: elija el plan cuyos indicadores quiere ver
- **Resumen**: total KPIs, con meta, procesos
- **Filtros**: botones de perspectiva (● Fin, ● Cli, ● Pro, ● Apr) y dropdown de objetivo
- **Tabla principal**: agrupada por perspectiva con columnas:
  - Indicador (nombre)
  - Perspectiva (● coloreada)
  - Objetivo asociado
  - Proceso vinculado
  - Fórmula de cálculo (completa, sin truncar)
  - Unidad de medida
  - Meta numérica

### 8.3 Filtrar indicadores
- **Por perspectiva**: click en los botones de la barra superior (● Financiera, ● Cliente, etc.). Se pueden combinar con el filtro de objetivo.
- **Por objetivo**: seleccione del dropdown "Todos los objetivos"
- **Por texto**: escriba en el campo "Buscar..."

### 8.4 Ver detalle de un indicador
- Click en **cualquier parte de la fila** del indicador
- Se abre la vista de detalle con:
  - Datos completos del indicador
  - Historial de mediciones (tabla y gráfico de tendencia)
  - Metas por período
  - Botones para editar, nueva medición, exportar

### 8.5 Crear un indicador manualmente
1. Click en **Nuevo** (botón verde arriba)
2. Complete el modal:
   - **Nombre**: ej. "Margen EBITDA"
   - **Categoría**: Cumplimiento, Oportunidad, Calidad o Productividad
   - **Objetivo**: seleccione el objetivo del plan al que pertenece
   - **Proceso**: seleccione el proceso organizacional (opcional)
   - **Frecuencia**: cada cuánto se mide (mensual, trimestral...)
   - **Fórmula**: `(Ingresos - Costos) / Ingresos * 100`
   - **Unidad**: %, $, horas, etc.
   - **Rango mínimo**: valor mínimo aceptable
   - **Rango máximo (Meta)**: valor objetivo a alcanzar
   - **Fuente**: de dónde salen los datos (ERP, CRM, manual)
3. Click en **Crear**

### 8.6 Carga masiva de mediciones (Excel)
Para registrar mediciones de muchos indicadores a la vez:

1. Click en **📄 Excel** (botón verde) — descarga la plantilla XLSX
2. La plantilla contiene:
   - **Columnas de referencia** (no editar): ID, Indicador, Fórmula, Meta, Unidad
   - **Columnas a llenar**: Mes (1-12), Año (YYYY), Valor (número con coma decimal)
3. Complete los valores en Excel:
   - Cada fila = un indicador
   - Puede dejar filas vacías si ese indicador no se midió este mes
4. Guarde el archivo
5. Click en **📤 Subir XLSX** — se abre el modal de carga
6. Seleccione el archivo y click en **Subir Excel**
7. El sistema:
   - Lee el archivo
   - Calcula automáticamente el semáforo (verde ≥ meta, amarillo ≥ 70%, rojo < 70%)
   - Crea las mediciones en la base de datos
   - Muestra cuántas se crearon

### 8.7 Exportar indicadores
Click en **CSV** para descargar la tabla visible como archivo CSV.

---

## 9. Seguimiento del Plan

### 9.1 Vista de detalle del plan
Desde **Planeación → click en el nombre del plan**, verá:

**Sección superior**:
- Barra con estado, botones Editar/Eliminar/Reporte
- 6 cards de resumen: Avance, Objetivos, Indicadores, Iniciativas, Presupuesto, Fases
- 4 cards de KPIs por perspectiva (click en cualquier card abre el modal de indicadores)

**Sección BSC**:
- Mapa Estratégico con objetivos agrupados por las 4 perspectivas
- Cada objetivo muestra su avance y tiene botón de edición

**Ruta Crítica**:
- Las 7 fases en orden secuencial
- Fases completadas muestran ✓ verde y 100%
- Fase actual muestra su estado (en_proceso, pendiente)
- Fases bloqueadas muestran 🔒

### 9.2 Cards de Seguimiento

| Card | ¿Qué abre? |
|------|-----------|
| **1. Objetivos y Estrategias** | Modal con 48 objetivos por perspectiva, cada uno con sus KPIs e iniciativas desplegadas |
| **2. Indicadores y Metas** | Modal con tabla de KPIs por perspectiva: nombre, fórmula, unidad, meta |
| **3. Evaluar Desempeño** | Redirige a la evaluación por colaborador |
| **4. Despliegue BSC** | Modal con el mapa BSC completo: 4 perspectivas con flechas causa-efecto, cada objetivo con sus KPIs |

### 9.3 "Por dónde continuar"
Si el plan no está completo, verá una tarjeta azul indicando:
- Fases completadas / total
- Siguiente fase pendiente con botón **Continuar Fase N**

---

## 10. Reporte Ejecutivo

### 10.1 Acceder
- Desde el detalle del plan: botón **Reporte** (barra superior)
- O desde el card de Seguimiento: **4. Reporte Ejecutivo**

### 10.2 Contenido del reporte
El reporte muestra toda la información del plan en formato imprimible:

1. **Encabezado**: nombre del plan, empresa, metodología, período, estado
2. **Resumen**: 4 KPIs principales
3. **Misión y Visión**: texto completo con indicador ✓/⚠
4. **Análisis PESTEL**: 6 dimensiones con factores documentados
5. **Despliegue BSC**: 4 perspectivas con objetivos, KPIs e iniciativas
6. **Sistema de Indicadores**: agrupado por perspectiva → objetivo → tabla de KPIs con fórmula, unidad, meta, frecuencia
7. **Iniciativas**: agrupadas por perspectiva → objetivo con tipo, prioridad, presupuesto, avance, estado
8. **Detalle por Fases**: las 7 fases con estado, progreso y entregable real

### 10.3 Imprimir
- Click en **Imprimir** para versión optimizada para papel
- O use **Ctrl+P** del navegador

---

## 11. Evaluación y Ajuste

### 11.1 Acceder
- Desde el menú: 📊 Estratégico → Evaluación
- O desde el detalle del plan: card "Evaluar Desempeño"

### 11.2 Dashboard de evaluación
Muestra para cada objetivo:
- **Salud**: Sólido (≥4 KPIs + ≥2 iniciativas), En desarrollo, Requiere atención
- **Conteo de KPIs e iniciativas**
- **Botón Ajustar**: abre modal para editar nombre y perspectiva del objetivo

### 11.3 Sugerencias IA
1. Click en **🧠 Sugerir mejoras** (banner azul)
2. Se abre un modal con:
   - Lista de sugerencias con checkbox
   - Filtros por perspectiva (● Financiera, ● Cliente, etc.)
   - Cada sugerencia muestra:
     - Tipo (Crítico/Mejora/Info)
     - Objetivo afectado
     - Problema detectado (ej: "Tiene solo 1 KPI")
     - Solución propuesta (ej: "Crear: Margen Bruto, Rotación de Activos")
   - Botón **✅ Aplicar seleccionadas**: crea automáticamente los KPIs o iniciativas
3. Puede aplicar todas o seleccionar solo algunas

---

## 12. Documentos ISO y Calidad

### 12.1 Documentos
- **Menú**: ⚙️ Operativo → Documentos ISO
- Permite crear, editar y publicar documentos del sistema de gestión
- Cada documento tiene: código, título, tipo, versión, estado, contenido HTML, archivo adjunto
- Codificación: `[TIPO]-[PROCESO]-[CONSECUTIVO]` (ej: MC-GC-001)
- Flujo de aprobación: Borrador → Revisión → Aprobado → Publicado

### 12.2 Acreditación
- **Menú**: ✅ Calidad → Acreditación
- Estándares de acreditación con autoevaluación
- Matriz de requisitos por nivel

### 12.3 No Conformidades (NC)
- Registro de hallazgos, origen, descripción, estado
- Seguimiento de acciones correctivas

### 12.4 Riesgos
- Matriz 5×5 (probabilidad × impacto)
- Controles y responsables

---

## 13. Gestión Ambiental (ISO 14001)

### 13.1 Acceder
- **Menú**: ✅ Calidad → Ambiental

### 13.2 Dashboard
Muestra:
- Indicadores de consumo (agua, energía, residuos, reciclaje)
- Gráficos de tendencia anual
- Asistente IA con sugerencias

### 13.3 Autoevaluación ISO 14001
1. Click en la pestaña **Autoevaluación** (si está configurada)
2. Evalúe 21 requisitos de la norma ISO 14001:2015
3. Cada requisito: Cumple totalmente (2.5) / Parcialmente (1.5) / No cumple (0)
4. El sistema calcula automáticamente el puntaje (máx 52.5)
5. Clasificación: Aceptable (≥86%), Moderado (≥61%), Crítico (<61%)
6. **Guardar Autoevaluación**: se registra en el historial

---

## 14. SST (Decreto 1072/2015)

### 14.1 Acceder
- **Menú**: ✅ Calidad → SST

### 14.2 Dashboard
Muestra indicadores de seguridad y salud laboral.

### 14.3 Autoevaluación SG-SST
1. Evalúe 14 artículos del Decreto 1072/2015
2. Puntaje máximo: 35 puntos
3. Clasificación: Aceptable (≥86%), Moderado (≥61%), Crítico (<61%)
4. **Guardar**: se almacena en el historial

### 14.4 Módulos adicionales
- **Peligros**: matriz de identificación y valoración
- **Incidentes**: reporte e investigación de accidentes
- **Ausentismo**: registro y estadísticas
- **Capacitaciones**: plan de formación SST
- **Exámenes**: evaluaciones médicas ocupacionales
- **Inspecciones**: registros de seguridad
- **Emergencias**: plan de respuesta
- **Plan de trabajo**: programa anual SST

---

## Atajos de Teclado

| Combinación | Acción |
|-------------|--------|
| `Ctrl + S` | Guardar formulario activo |
| `Ctrl + N` | Nuevo elemento |
| `Escape` | Cerrar modal abierto |

---

**EstrateGIA v2.0** — Documento generado el 11 de junio de 2026
