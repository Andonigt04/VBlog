# VBlog — Arquitectura

## Stack tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Framework | Laravel | 13 |
| Runtime | PHP-FPM | 8.4 |
| Base de datos | PostgreSQL | latest |
| Frontend | Blade + Tailwind CSS (Vite) | — |
| Proxy | nginx | alpine |

## Diagrama de servicios

```
┌─────────────────────────────────────────────────────────────┐
│                       Host (alumno)                          │
│   http://localhost :80          http://dev.vblog.local :80   │
└───────────────────────────┬─────────────────────────────────┘
                            │ puerto 80
               ┌────────────▼────────────┐
               │          nginx          │
               │  (vblog-nginx:local)    │
               │                         │
               │  server.conf            │  vhost: localhost
               │    → laravel:9000       │  vhost: dev.vblog.local
               │  dev.conf               │    → dev_panel:80
               └──────┬──────────────────┘
                      │ FastCGI :9000
            ┌─────────▼──────────┐        ┌──────────────────┐
            │      laravel       │        │    dev_panel     │
            │   (php:8.4-fpm)    │        │  (nginx:alpine)  │
            │   Laravel 13       │        │  HTML estático   │
            │   API REST + Blade │        │  sin auth        │
            └─────────┬──────────┘        └──────────────────┘
                      │ pgsql :5432
            ┌─────────▼──────────┐
            │    postgresql      │
            │  (postgres:latest) │
            │  DB: vblog         │
            │  User: vblog_adm   │
            └────────────────────┘
```

## Servicios Docker

| Servicio | Imagen | Puerto interno | Rol |
|---|---|---|---|
| `nginx` | `vblog-nginx:local` (build custom) | 80 | Reverse proxy, virtual hosts |
| `laravel` | build desde `Dockerfile` | 9000 (FPM) | App principal, API REST |
| `postgresql` | `postgres:latest` | 5432 | Base de datos |
| `dev_panel` | `nginx:alpine` | 80 | Panel interno del subdominio oculto |

## Volúmenes Docker

| Volumen | Montaje | Propósito |
|---|---|---|
| `vendor_deps` | `/var/www/html/vendor` | Dependencias PHP (generadas en la imagen) |
| `build_assets` | `/var/www/html/public/build` | Assets Vite compilados |
| `pgdata` | `/var/lib/postgresql/data` | Datos persistentes de PostgreSQL |
| `pgcontent` | `/var/www/html/storage` | Storage de Laravel |


## Flujo de una petición HTTP

```
Alumno → GET http://localhost/api/users/1
  │
  ├─ nginx (server.conf)
  │    location /api → proxy_pass fastcgi://laravel:9000
  │
  ├─ Laravel (php-fpm)
  │    routes/api.php → Route::get('/users/{id}', ...)
  │    → UserController::show($id)
  │    → User::find($id)  [sin auth middleware]
  │
  ├─ PostgreSQL
  │    SELECT * FROM users WHERE id = 1
  │
  └─ Respuesta JSON con datos del usuario (IDOR)
```

## Superficie de ataque expuesta

| Endpoint / recurso | Acceso | Vulnerabilidad |
|---|---|---|
| `GET /backup` | Público (sin auth) | Credenciales en texto plano |
| `GET /debug` | Público (sin auth) | Fingerprinting de tecnologías |
| `GET /api/users/{id}` | Público (sin auth) | IDOR — enumeración de usuarios |
| `GET /api/comments` | Público (sin auth) | IDOR — listado completo |
| `PUT /api/update/user/{id}` | Auth requerida | Mass Assignment (campo `role`) |
| `GET /dashboard` | Auth requerida | Broken Access Control (no verifica rol) |
| `POST /api/create/comment` | Auth requerida | Stored XSS (`{!! !!}` sin escapar) |
| `GET /api/admin/file` | Auth + admin | Path Traversal |
| `GET /api/admin/stats` | Auth + admin | SQL Injection |
| `POST /api/admin/upload` | Auth + admin | Insecure File Upload → RCE |
| `/tmp/rootbash` | Sistema | SUID bash → root |
| `sudo find` | Sistema (www-data) | GTFOBins privesc → root |
| `http://dev.vblog.local` | Red (sin auth) | Panel interno expuesto |
