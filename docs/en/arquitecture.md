# VBlog — Architecture

## Technology Stack

| Layer | Technology | Version |
|---|---|---|
| Framework | Laravel | 13 |
| Runtime | PHP-FPM | 8.4 |
| Database | PostgreSQL | latest |
| Frontend | Blade + Tailwind CSS (Vite) | — |
| Proxy | nginx | alpine |

## Services Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                        Host (student)                        │
│   http://localhost :80          http://dev.vblog.local :80   │
└───────────────────────────┬─────────────────────────────────┘
                            │ port 80
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
            │   Laravel 13       │        │  static HTML     │
            │   REST API + Blade │        │  no auth         │
            └─────────┬──────────┘        └──────────────────┘
                      │ pgsql :5432
            ┌─────────▼──────────┐
            │    postgresql      │
            │  (postgres:latest) │
            │  DB: vblog         │
            │  User: vblog_adm   │
            └────────────────────┘
```

## Docker Services

| Service | Image | Internal Port | Role |
|---|---|---|---|
| `nginx` | `vblog-nginx:local` (custom build) | 80 | Reverse proxy, virtual hosts |
| `laravel` | built from `Dockerfile` | 9000 (FPM) | Main application, REST API |
| `postgresql` | `postgres:latest` | 5432 | Database |
| `dev_panel` | `nginx:alpine` | 80 | Internal panel for the hidden subdomain |

## Docker Volumes

| Volume | Mount | Purpose |
|---|---|---|
| `vendor_deps` | `/var/www/html/vendor` | PHP dependencies (seeded from image) |
| `build_assets` | `/var/www/html/public/build` | Compiled Vite assets |
| `pgdata` | `/var/lib/postgresql/data` | Persistent PostgreSQL data |
| `pgcontent` | `/var/www/html/storage` | Laravel storage |

## HTTP Request Flow

```
Student → GET http://localhost/api/users/1
  │
  ├─ nginx (server.conf)
  │    location /api → proxy_pass fastcgi://laravel:9000
  │
  ├─ Laravel (php-fpm)
  │    routes/api.php → Route::get('/users/{id}', ...)
  │    → UserController::show($id)
  │    → User::find($id)  [no auth middleware]
  │
  ├─ PostgreSQL
  │    SELECT * FROM users WHERE id = 1
  │
  └─ JSON response with user data (IDOR)
```

## Exposed Attack Surface

| Endpoint / resource | Access | Vulnerability |
|---|---|---|
| `GET /backup` | Public (no auth) | Plaintext credentials |
| `GET /debug` | Public (no auth) | Technology fingerprinting |
| `GET /api/users/{id}` | Public (no auth) | IDOR — user enumeration |
| `GET /api/comments` | Public (no auth) | IDOR — full comment listing |
| `PUT /api/update/user/{id}` | Auth required | Mass Assignment (`role` field) |
| `GET /dashboard` | Auth required | Broken Access Control (no role check) |
| `POST /api/create/comment` | Auth required | Stored XSS (`{!! !!}` unescaped) |
| `GET /api/admin/file` | Auth + admin | Path Traversal |
| `GET /api/admin/stats` | Auth + admin | SQL Injection |
| `POST /api/admin/upload` | Auth + admin | Insecure File Upload → RCE |
| `/tmp/rootbash` | System | SUID bash → root |
| `sudo find` | System (www-data) | GTFOBins privesc → root |
| `http://dev.vblog.local` | Network (no auth) | Exposed internal panel |
