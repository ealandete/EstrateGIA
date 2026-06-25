# EstrateGIA - Arquitectura de Inteligencia Artificial

## 1. Visión General

EstrateGIA integra IA como asistente activo en cada etapa de la planeación estratégica. La clase `AIManager` (`lib/AIManager.php`, ~1080 líneas) centraliza todas las capacidades.

```
┌────────────────────────────────────────────────────┐
│                   AIManager                         │
│                                                     │
│  ┌──────────┐  ┌────────────┐  ┌────────────────┐  │
│  │ Asistente│  │Recomendac. │  │ Predicciones   │  │
│  │ (Chat)   │  │(Sugerencias│  │ (Tendencias)   │  │
│  └──────────┘  └────────────┘  └────────────────┘  │
│                                                     │
│  ┌──────────────────────────────────────────────┐  │
│  │     Generación de Contenido                   │  │
│  │  Misión │ Visión │ FODA │ BSC │ OKR │ KPIs   │  │
│  └──────────────────────────────────────────────┘  │
│                                                     │
│  ┌──────────────────────────────────────────────┐  │
│  │  Proveedores IA (configurables)               │  │
│  │  OpenAI │ Claude │ Gemini │ Local Generator   │  │
│  └──────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────┘
```

## 2. Capacidades del AIManager

### 2.1 Asistente IA (Chat Contextual)

**Método**: `procesarAsistencia($usuarioId, $contexto, $prompt, $modeloId)`

El asistente responde consultas en lenguaje natural, enriqueciendo el prompt con datos reales del sistema según el contexto:

| Contexto | Datos Inyectados |
|----------|-----------------|
| `planeacion` | Planes activos, metodología, estado, avance % |
| `procesos` | Procesos del usuario, macroprocesos, estados |
| `indicadores` | Indicadores del usuario, últimos valores, categorías |
| `documentacion` | (placeholder) |
| `evaluacion` | Última evaluación del usuario, puntaje, período |

**System Prompt base**:
```
Eres EstrateGIA, un asistente experto en planeación estratégica empresarial.
Ayudas a organizaciones a definir, implementar y dar seguimiento a su estrategia.
Eres conocedor de metodologías como Balanced Scorecard, OKR, Hoshin Kanri,
Planeación por Escenarios y Design Thinking Estratégico.
También eres experto en normas ISO (9001, 14001, 45001, 7101, 41001, 13485, 31000, 27001).
```

### 2.2 Recomendaciones IA

**Método**: `generarRecomendacion($contexto, $contextoId, $tipo)`

Genera recomendaciones accionables para elementos específicos:

| Contexto | Elemento | Datos analizados |
|----------|----------|-----------------|
| `plan` | Plan estratégico | Nombre, empresa, metodología, estado |
| `objetivo` | Objetivo BSC | Nombre, perspectiva, avance |
| `estrategia` | Estrategia | Nombre, objetivo padre |
| `proceso` | Proceso | Nombre, macroproceso, estado |
| `indicador` | Indicador KPI | Nombre, fórmula, categoría |

**Almacenamiento**: `ia_recomendaciones` con soporte para aplicar/feedback.

### 2.3 Predicciones de Indicadores

**Método**: `predecirIndicador($indicadorId, $periodosFuturos)`

Analiza la serie histórica de un indicador (últimos 24 períodos) y predice valores futuros usando IA:

- Requiere al menos 3 mediciones históricas
- Retorna JSON: `[{periodo, valor, confianza_min, confianza_max}]` + factores
- Almacena en `ia_predicciones`
- Horizonte: `corto_plazo` (por defecto 3 períodos)

### 2.4 Generación de Contenido

**Método**: `generarContenido($tipo, $contexto)`

Genera borradores profesionales listos para usar:

| Tipo | Descripción | Formato |
|------|-------------|---------|
| `mision` | Declaración de misión empresarial | Texto |
| `vision` | Declaración de visión a futuro | Texto |
| `valores` | 5-7 valores corporativos | Texto |
| `objetivos` | Objetivos SMART según metodología | Texto |
| `foda` | Análisis FODA (5+ items por cuadrante) | JSON |
| `pestel` | Análisis PESTEL (6 dimensiones) | JSON |
| `bsc` | 6 objetivos por cada perspectiva BSC | Texto |
| `bsc-relaciones` | Relaciones causa-efecto entre objetivos | JSON |
| `indicadores` | 4 KPIs por perspectiva BSC | JSON |
| `iniciativas` | Iniciativas con presupuesto y prioridad | JSON |
| `evaluacion` | Recomendaciones de mejora del plan | JSON |
| `escenarios` | 4 escenarios futuros con estrategia | JSON |

### 2.5 Guía Paso a Paso

**Método**: `generarGuiaPasoAPaso($faseId)`

Para una fase específica del plan estratégico, genera:
- 5-8 pasos concretos con descripción
- Entregables esperados por paso
- Tiempo estimado por paso (horas)
- Consejos prácticos
- Guarda el resultado en `plan_fases.fase_guia_paso_a_paso`

## 3. Modelos Soportados

Configurados en tabla `ia_modelos`:

| Proveedor | Tipo de Modelo | Tipos Soportados |
|-----------|---------------|-----------------|
| **OpenAI** | GPT-4, GPT-3.5 | `asistente`, `recomendacion`, `prediccion`, `generacion` |
| **Claude** | Claude 3 Opus/Sonnet | `asistente`, `recomendacion`, `prediccion`, `generacion` |
| **Gemini** | Gemini Pro | `asistente`, `recomendacion`, `prediccion`, `generacion` |
| **Local** | Generador local | Todos (sin API key) |

### Configuración de Modelo

```json
// ia_modelos.modelo_configuracion_json
{
  "api_key": "sk-...",
  "model": "gpt-4",
  "simulado": false,
  "temperature": 0.7,
  "max_tokens": 2000
}
```

### Modelo por Defecto

Si no se especifica `modelo_id`, el sistema selecciona el primer modelo activo del tipo requerido:

```php
$modelo = $this->getDefaultModelo('asistente');
// SELECT * FROM ia_modelos WHERE modelo_tipo = 'asistente' AND modelo_activo = 1 ORDER BY modelo_id LIMIT 1
```

## 4. Modo Local / Simulado

Cuando no hay API key configurada o `simulado = true`, el sistema usa un generador local basado en plantillas (`callLocalGenerator`).

### Cómo Funciona

El generador local analiza el prompt del usuario para detectar palabras clave y devuelve contenido predefinido según el sector (Salud, Inmobiliario, Logística Farmacéutica, etc.):

```php
if (str_contains($userPrompt, 'MISIÓN')) {
    return $this->generarMisionLocal($userPrompt);
}
if (str_contains($userPrompt, 'VISIÓN')) {
    return $this->generarVisionLocal($userPrompt);
}
if (str_contains($userPrompt, 'FODA')) {
    return $this->generarFODALocal($userPrompt);
}
// ... etc
```

### Sectores con Plantillas

- **Salud**: Acreditación, seguridad del paciente, ISO 7101
- **Inmobiliario**: ISO 41001, gestión de propiedad, Ley 820
- **Logística Farmacéutica**: ISO 13485, cadena de frío, BPA/BPD
- **General**: Plantillas genéricas para cualquier sector

### Limitaciones del Modo Local

- Respuestas predefinidas, no dinámicas
- `tokens_in` y `tokens_out` reportan 0
- Metadata: `{"model": "local-generator", "provider": "Local"}`
- Menor personalización que modelos reales

## 5. Prompt Engineering

### Principios

1. **Contexto enriquecido**: Inyectar datos reales del sistema antes de enviar al modelo
2. **System prompt específico**: Definir rol, expertise y tono
3. **Formato estructurado**: Solicitar JSON cuando sea posible para parseo automático
4. **Instrucciones claras**: Número exacto de elementos, formato requerido

### Ejemplo: Prompt para FODA

```php
// System
"Eres un experto en planeación estratégica y gestión empresarial."

// User
"Genera un análisis FODA para 'Hospital Central' del sector 'Salud'.
Proporciona al menos 5 elementos por cada cuadrante.
Formato JSON: {\"fortalezas\":[],\"oportunidades\":[],\"debilidades\":[],\"amenazas\":[]}"
```

### Ejemplo: Prompt para BSC Relaciones

```php
"Analiza los siguientes objetivos estratégicos BSC y sugiere 5-8 relaciones causa-efecto.
Las relaciones deben seguir la lógica: Aprendizaje → Procesos → Cliente → Financiera.
Formato JSON: [{\"causa\": \"...\", \"efecto\": \"...\", \"intensidad\": \"Fuerte|Media|Débil\", \"justificacion\": \"...\"}]"
```

### Estrategia de Fallback

Si el modelo real falla (error HTTP, timeout), se usa el generador local:

```php
if (($config['simulado'] ?? false) || empty($apiKey)) {
    return $this->callLocalGenerator($modelo, $systemPrompt, $userPrompt);
}
```

## 6. Base de Datos IA

### Tablas

| Tabla | Propósito |
|-------|-----------|
| `ia_modelos` | Configuración de modelos IA (nombre, tipo, proveedor, endpoint) |
| `ia_asistencias` | Historial de consultas al asistente (prompt, respuesta, tokens, tiempo) |
| `ia_recomendaciones` | Recomendaciones generadas (contexto, tipo, prioridad, aplicada, feedback) |
| `ia_predicciones` | Predicciones de indicadores (valor, intervalo de confianza, factores) |

### Estadísticas de Uso

```php
$ai = new AIManager();
$stats = $ai->getUsageStats();
// → total_asistencias, total_recomendaciones, recomendaciones_aplicadas,
//   total_predicciones, tasa_aplicacion (%)
```

## 7. Endpoints API

Ver `docs/API.md` sección 5.10 para la referencia completa de endpoints.

### Ejemplo: Generar Misión

```bash
POST /api/ia/generar/mision
Authorization: Bearer <token>
Content-Type: application/json

{
  "empresa": "Hospital Central",
  "sector": "Salud"
}
```

### Ejemplo: Asistente IA

```bash
POST /api/ia/asistencia
Authorization: Bearer <token>
Content-Type: application/json

{
  "contexto": "planeacion",
  "prompt": "¿Cómo puedo mejorar el avance de mi plan estratégico?"
}
```
