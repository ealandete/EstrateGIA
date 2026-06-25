# PROMPT DE PROTECCION DE CODIGO — Multiaplicacion
# Incluir este bloque en el contexto de CADA sesion (GestionMX360, AgroP, estrateGIA, AnalisisPGP)

## REGLA CRITICA: Proteccion de codigo en staging

### Principio
- **Local (desarrollo)**: El codigo se mantiene en `.py` puro. NUNCA compilar en local.
- **Staging (publicacion)**: Al sincronizar, compilar los modulos criticos con Cython a `.so`.

### Script de proteccion
Ejecutar ANTES de cada sync a staging:
```bash
bash /home/emilio/gestionmx360/app/protect.sh [nombre-app]
```

Donde `nombre-app` puede ser: `gestionmx360`, `agrop`, `estrategia`, `analisispgp`, o `todas`.

### Modulos criticos a proteger (por app)
```
app/models.py
app/auth.py
app/routers/dispensar_express.py
app/routers/integral.py
app/routers/admin_panel.py
app/routers/seguridad.py
app/routers/configuracion.py
app/routers/reportes_financieros.py
app/routers/ia_engine.py
app/routers/extraccion_datos.py
```

### Verificacion
Despues del sync, verificar en: `GET /api/sistema/build-info`
Debe mostrar `modulos_protegidos > 0` y `ambiente: "staging-protegido"`.

### URL Masking
- La pagina de entrada es `/app.html` (iframe wrapper)
- Despues del login, redirigir a `/app.html`, NO a `/cadena.html` ni `/dashboard.html`
- El `switchProfileNav` debe navegar via `postMessage` al iframe padre

### Middleware de seguridad
Registrar en `main.py`:
```python
from app.security_middleware import SecurityMiddleware
app.add_middleware(SecurityMiddleware)
```
Esto bloquea acceso directo a paginas HTML sin Referer (403).

### Endpoints de verificacion
| Endpoint | Proposito |
|---|---|
| `GET /api/sistema/build-info` | Estado de compilacion |
| `GET /build-info.html` | Pagina publica de estado |
| `GET /api/health` | Health check (no requiere auth) |

### Check en cada sync
1. `protect.sh` se ejecuto sin errores
2. `/api/sistema/build-info` retorna modulos_protegidos > 0
3. `app.html` carga correctamente con iframe
4. El login redirige a `/app.html`
5. Acceso directo a `.html` retorna 403
