# VBlog — Student Guide

## What is this?

VBlog is an intentionally vulnerable cybersecurity web application.

---

## Access

```bash
sudo docker compose up --build -d
# The app is available at http://localhost
```

---

## Your Mission

**Escalate from anonymous user → root**, chaining the application's vulnerabilities.

**Final objective:** Execute commands as `root` on the server.

---

## Phase 0 — Reconnaissance (No account)

<details>
<summary>Hint 1</summary>

Hidden routes usually have short, generic names. Look for words like: `backup`, `debug`, `admin`, `old`, `internal`, `logs`

</details>

<details>
<summary>Hint 2</summary>

Hidden subdomains are usually technical names:
- `dev`, `admin`, `internal`
- Used for internal infrastructure, not public services

Once you find one, you will need to add a line to your `/etc/hosts` file.

</details>

<details>
<summary>Hint 3</summary>

Look for:
- Plaintext credentials
- Technical information about the app (versions, DB driver)
- Internal documentation

</details>

---

## Phase 1 — Registered User

### Key question
> What can I see and change once I am logged in?

### Stuck?

<details>
<summary>Hint 1</summary>

Look for a field that controls your permissions. Hint: it starts with the letter "r".

</details>

<details>
<summary>Hint 2</summary>

Users have profile endpoints. Look for:
- An update endpoint (probably using PUT)
- API endpoints that accept POST/PUT without changing the visible URL

</details>

---

## Phase 2 — Privilege Escalation

<details>
<summary>Hint 1</summary>

Does the app require special permissions to modify certain fields? Or does it trust whatever you send?

</details>

<details>
<summary>Hint 2</summary>

If you manage to become admin, look for an administration route. Usually:
- `/admin`
- `/dashboard`
- `/panel`

</details>

---

## Phase 3 — Internal Access

<details>
<summary>Hint 1</summary>

On Linux/Mac, edit `/etc/hosts` and add:

```
127.0.0.1  dev.vblog.local
```

Then try to access `http://dev.vblog.local`.

</details>

<details>
<summary>Hint 2</summary>

Look for:
- HTML files documenting the API
- Logs with execution traces
- Technical server information

</details>

---

## Phase 3 — Admin Panel

<details>
<summary>Hint 1</summary>

Try enumerating API routes with your admin session. There are endpoints under `/api/admin/` that are not publicly documented.

</details>

<details>
<summary>Hint 2</summary>

One endpoint accepts a `path` parameter to read files from the server. What happens if you put `../../etc/passwd`?

</details>

<details>
<summary>Hint 3</summary>

Another endpoint accepts a `filter` text parameter to search posts. Try adding a single quote `'` at the end of the value. Do you see anything unusual in the response?

</details>

<details>
<summary>Hint 4</summary>

There is a file upload endpoint. The server saves the file with the name you choose. What happens if you upload a `.php` file?

</details>

---

## Phase 4 — System Escalation (root)

<details>
<summary>Hint 1</summary>

Run `sudo -l` from your shell. Is there any binary you can execute as root?

</details>

<details>
<summary>Hint 2</summary>

Look up the binary you found on GTFOBins. The `sudo` escalation technique usually uses `-exec`.

</details>

<details>
<summary>Hint 3</summary>

Is there any binary with the `SUID` bit set in `/tmp`? Try `ls -la /tmp/`.

</details>
