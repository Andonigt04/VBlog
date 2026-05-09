# VBlog — Arkitektura

## Teknologia-pila

| Geruza | Teknologia | Bertsioa |
|---|---|---|
| Framework | Laravel | 13 |
| Exekuzio-ingurunea | PHP-FPM | 8.4 |
| Datu-basea | PostgreSQL | latest |
| Frontend | Blade + Tailwind CSS (Vite) | — |
| Proxy | nginx | alpine |

## Zerbitzuen diagrama

```
┌─────────────────────────────────────────────────────────────┐
│                       Hostalaria (ikaslea)                   │
│   http://localhost :80          http://dev.vblog.local :80   │
└───────────────────────────┬─────────────────────────────────┘
                            │ 80 portua
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
            │   Laravel 13       │        │  HTML estatikoa  │
            │   REST API + Blade │        │  auth gabe       │
            └─────────┬──────────┘        └──────────────────┘
                      │ pgsql :5432
            ┌─────────▼──────────┐
            │    postgresql      │
            │  (postgres:latest) │
            │  DB: vblog         │
            │  User: vblog_adm   │
            └────────────────────┘
```

## Docker zerbitzuak

| Zerbitzua | Irudia | Barne-portua | Rola |
|---|---|---|---|
| `nginx` | `vblog-nginx:local` (eraikuntza pertsonalizatua) | 80 | Alderantzizko proxy, virtual hostak |
| `laravel` | `Dockerfile`-tik eraikia | 9000 (FPM) | Aplikazio nagusia, REST API |
| `postgresql` | `postgres:latest` | 5432 | Datu-basea |
| `dev_panel` | `nginx:alpine` | 80 | Azpidomenu ezkutuaren barne-panela |

## Docker bolumenak

| Bolumena | Muntaketa | Helburua |
|---|---|---|
| `vendor_deps` | `/var/www/html/vendor` | PHP menpekotasunak (iruditik sortutakoak) |
| `build_assets` | `/var/www/html/public/build` | Vite-k konpilatutako aktiboak |
| `pgdata` | `/var/lib/postgresql/data` | PostgreSQL datu iraunkorrak |
| `pgcontent` | `/var/www/html/storage` | Laravel-en biltegiratzea |

## HTTP eskaeraren fluxua

```
Ikaslea → GET http://localhost/api/users/1
  │
  ├─ nginx (server.conf)
  │    location /api → proxy_pass fastcgi://laravel:9000
  │
  ├─ Laravel (php-fpm)
  │    routes/api.php → Route::get('/users/{id}', ...)
  │    → UserController::show($id)
  │    → User::find($id)  [auth middleware gabe]
  │
  ├─ PostgreSQL
  │    SELECT * FROM users WHERE id = 1
  │
  └─ Erabiltzailearen datuekin JSON erantzuna (IDOR)
```

## Eraso-azalera espostua

| Endpoint / baliabidea | Sarbidea | Ahultasuna |
|---|---|---|
| `GET /backup` | Publikoa (auth gabe) | Kredentzialak testu arruntean |
| `GET /debug` | Publikoa (auth gabe) | Teknologia-hatz-marka |
| `GET /api/users/{id}` | Publikoa (auth gabe) | IDOR — erabiltzaileen enumerazioa |
| `GET /api/comments` | Publikoa (auth gabe) | Iruzkin guztien zerrenda (IDOR) |
| `PUT /api/update/user/{id}` | Auth beharrezkoa | Mass Assignment (`role` eremua) |
| `GET /dashboard` | Auth beharrezkoa | Sarbide-kontrol apurtua (ez du rola egiaztatzen) |
| `POST /api/create/comment` | Auth beharrezkoa | Stored XSS (`{!! !!}` ihes egin gabe) |
| `GET /api/admin/file` | Auth + admin | Path Traversal |
| `GET /api/admin/stats` | Auth + admin | SQL Injection |
| `POST /api/admin/upload` | Auth + admin | Fitxategi igoera ez-segurua → RCE |
| `/tmp/rootbash` | Sistema | SUID bash → root |
| `sudo find` | Sistema (www-data) | GTFOBins pribilegio-eskalada → root |
| `http://dev.vblog.local` | Sarea (auth gabe) | Barne-panela espostua |
