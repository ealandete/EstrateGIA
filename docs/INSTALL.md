# EstrateGIA v2.1 - Guía de Instalación

## 1. Requisitos del Servidor

| Componente | Mínimo | Recomendado |
|---|---|---|
| **PHP** | 8.0 | 8.2+ |
| **MySQL** | 8.0 | 8.0+ |
| **MariaDB** | 10.2 | 10.6+ |
| **Servidor Web** | Apache 2.4 o Nginx 1.18 | Última versión estable |
| **Espacio en disco** | 500 MB | 2 GB+ |
| **Memoria RAM** | 512 MB | 2 GB+ |

### Extensiones PHP requeridas

```bash
sudo apt-get install php-mysql php-pdo php-mbstring php-xml php-json php-curl php-openssl php-gd php-zip
```

Para Pandoc (exportación PDF):
```bash
sudo apt-get install pandoc texlive-xetex texlive-latex-extra
```

### Extensiones opcionales
- `php-redis` — Para caché distribuida
- `php-xdebug` — Solo desarrollo

---

## 2. Instalación Paso a Paso

### 2.1 Clonar el repositorio

```bash
mkdir -p /var/www
cd /var/www
git clone <repo-url> estrategia
cd estrategia/workspace
```

### 2.2 Permisos de directorios

```bash
# El servidor web debe poder escribir en estos directorios
chown -R www-data:www-data uploads/ logs/ cache/
chmod -R 755 uploads/ logs/ cache/
```

### 2.3 Configurar la base de datos

```bash
# Opción A: Schema completo (instalación nueva, recomendada)
mysql -u root -p < database/estrategia_v1_completo.sql

# Opción B: Solo migraciones adicionales (sobre BD existente)
mysql -u root -p < database/migration_unificacion_usuarios.sql
mysql -u root -p < database/migration_comercial.sql
mysql -u root -p < scripts/migracion_ambiental_v2.sql
```

### 2.4 Configurar credenciales de BD

Editar `lib/EstrateGiaCore.php` y ajustar las líneas 17-20:

```php
'db_host'     => 'localhost',
'db_name'     => 'estrategia_v1',
'db_user'     => 'TU_USUARIO',
'db_pass'     => 'TU_PASSWORD',
```

### 2.5 Configurar servidor web

#### Apache + mod_rewrite (.htaccess)

Crear `public/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?__route=$1 [QSA,L]
</IfModule>

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

VirtualHost Apache:

```apache
<VirtualHost *:80>
    ServerName estrategia.local
    DocumentRoot /var/www/estrategia/workspace/public
    
    <Directory /var/www/estrategia/workspace/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/estrategia_error.log
    CustomLog ${APACHE_LOG_DIR}/estrategia_access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name estrategia.local;
    root /var/www/estrategia/workspace/public;
    index index.php;

    # Seguridad: bloquear acceso a archivos sensibles
    location ~ /\. { deny all; }
    location ~* /(lib|src|database|scripts|templates)/.*\.php$ { deny all; }
    location ~* /(composer\.(json|lock)|\.env|\.git) { deny all; }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param QUERY_STRING $query_string;
        include fastcgi_params;
    }

    location /uploads/ {
        alias /var/www/estrategia/workspace/uploads/;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location /api/ {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

---

## 3. Asistente de Primera Configuración (Setup Wizard)

Al acceder por primera vez a `http://tudominio.com/setup`, se ejecuta el asistente de configuración:

| Paso | Ruta | Descripción |
|---|---|---|
| 1 | `/setup` | Pantalla de bienvenida e instrucciones |
| 2 | `/setup/requisitos` | Verificación de requisitos del servidor |
| 3 | `/setup/empresa` | Registro de la empresa (NIT, razón social, sector) |
| 4 | `/setup/usuario` | Creación del usuario administrador |
| 5 | `/setup/finalizar` | Confirmación y finalización |

### Roles predefinidos

| Rol | Permisos |
|---|---|
| **SUPER_ADMIN** | Control total del sistema, todas las empresas, backups |
| **Director General** | Definir planes estratégicos, revisar dashboards ejecutivos |
| **Gerente de Área** | Gestión táctica, objetivos, indicadores de su área |
| **Coordinador** | Asignar actividades, seguimiento de equipo |
| **Analista** | Registrar mediciones, generar reportes |
| **Colaborador** | Visualizar y actualizar actividades asignadas |
| **Auditor Externo** | Solo lectura para auditorías ISO |
| **Cliente/Invitado** | Dashboards compartidos, acceso limitado |

---

## 4. Verificar la Instalación

### Health check

```bash
curl http://localhost:81/api/health
```

Respuesta esperada:
```json
{
  "status": "ok",
  "app": "EstrateGIA",
  "version": "1.0",
  "db_tables": 45,
  "timestamp": "2026-06-25T10:00:00-05:00"
}
```

### Login API

```bash
curl -X POST http://localhost:81/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@tudominio.com","password":"tu_password"}'
```

### Documentación Swagger

Abrir `http://tudominio.com/docs` para ver la documentación interactiva de la API.

---

## 5. Solución de Problemas

### Error de conexión a base de datos

```
Error: SQLSTATE[HY000] [1045] Access denied
```
- Verificar credenciales en `lib/EstrateGiaCore.php`
- Confirmar que el usuario de BD tiene permisos sobre `estrategia_v1`
- Verificar que MySQL está corriendo: `sudo systemctl status mysql`

### Error 500 al cargar páginas

- Activar debug temporal: `define('APP_DEBUG', true);` en `public/index.php`
- Revisar logs: `tail -f logs/error.log` y `/var/log/apache2/estrategia_error.log`
- Verificar permisos de escritura en `logs/`

### Las rutas no funcionan (404)

- Apache: Verificar que `mod_rewrite` esté activo (`sudo a2enmod rewrite && sudo systemctl restart apache2`)
- Nginx: Verificar la directiva `try_files` en la configuración del server block
- Confirmar que `.htaccess` o la configuración de Nginx apuntan correctamente a `public/index.php`

### El setup wizard no carga

- Verificar que no existan datos previos en las tablas `plan_empresas` o `sys_usuarios`
- Limpiar la BD con: `TRUNCATE TABLE plan_empresas; TRUNCATE TABLE sys_usuarios;`

### Exportación PDF no funciona

- Instalar Pandoc y XeLaTeX: `sudo apt-get install pandoc texlive-xetex`
- Verificar permisos de escritura en `/tmp`

### App móvil no conecta

- Verificar CORS en `public/api.php` (cabecera `Access-Control-Allow-Origin`)
- Confirmar que la URL del API en la app móvil coincide con el servidor
- Asegurar que el token JWT se envía como `Authorization: Bearer <token>`

### CRON para alertas automáticas

Para habilitar las alertas proactivas automáticas:

```bash
# Ejecutar cada 6 horas
0 */6 * * * curl -s http://localhost:81/api/alertas/vencimientos > /dev/null 2>&1
```

### Respaldos automáticos

```bash
# Backup diario a las 2 AM
0 2 * * * bash /var/www/estrategia/workspace/scripts/backup.sh
```

---

## 6. Actualización

```bash
cd /var/www/estrategia/workspace
git pull origin main

# Ejecutar migraciones nuevas si las hay
mysql -u root -p < scripts/migracion_ambiental_v2.sql

# Limpiar caché
rm -rf cache/*
```

---

## 7. Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.0+ (PDO, sin framework) |
| Router | Clase Router propia con regex |
| Base de Datos | MySQL 8.0+ / MariaDB 10.2+ |
| Autenticación | Sesiones PHP + JWT para API |
| Seguridad | CSRF tokens, Rate Limiting, RBAC |
| IA | OpenAI, Claude, Gemini (configurable) |
| Frontend Web | Bootstrap 5 + Chart.js + ScriptCase templates |
| App Móvil | React Native + React Native Paper |
| API Docs | Swagger UI 5 + OpenAPI 3.0 |
