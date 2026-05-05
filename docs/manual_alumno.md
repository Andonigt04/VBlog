# VBlog — Manual del Alumno

## ¿Qué es esto?

VBlog es una aplicación web de ciberseguridad deliberadamente vulnerable. Está diseñada para que **descubras y exploites vulnerabilidades reales** en un entorno controlado y legal.

**No ataques sistemas reales. Todo lo que hagas aquí está en tu propia máquina.**

---

## Acceso

```bash
sudo docker compose up --build -d
# La app está en http://localhost
```

---

## Tu Misión

**Escalar desde usuario anónimo → admin**, descubriendo cómo la app expone información, rompe controles de acceso y permite manipular datos.

```
Fase 0: Exploración anónima
Fase 1: Usuario registrado
Fase 2: Escalada de privilegios
Fase 3: Acceso a áreas internas
```

**Objetivo final:** Acceder a paneles internos y bases de datos que no deberían ser públicos.

---

## Fase 0 — Reconocimiento (Sin cuenta)

### Herramientas sugeridas

```bash
# Ver cabeceras HTTP
curl -I http://localhost/

# Enumerar rutas con fuerza bruta
gobuster dir -u http://localhost \
  -w /usr/share/wordlists/dirbuster/directory-list-2.3-medium.txt

# Enumerar subdominios
gobuster vhost -u http://localhost \
  -w /usr/share/seclists/Discovery/DNS/subdomains-top1million-5000.txt \
  --append-domain
```

### ¿Estás atrapado?

<details>
<summary>Pista 1</summary>

Las rutas ocultas suelen tener nombres cortos y genéricos. Busca palabras como: `backup`, `debug`, `admin`, `old`, `internal`, `logs`

</details>

<details>
<summary>Pista 2</summary>

Los subdominios ocultos suelen ser:
- Nombres técnicos: `dev`, `admin`, `internal`
- Usados para infraestructura interna, no servicios públicos

Una vez los descubras, deberás agregar una línea en tu `/etc/hosts`

</details>

<details>
<summary>Pista 3</summary>

- Credenciales en texto plano
- Información técnica de la app (versiones, driver BD)
- Documentación interna

</details>

---

## Fase 1 — Usuario Registrado

### Pregunta clave
> ¿Qué puedo ver y cambiar una vez logueado?

### ¿Estás atrapado?

<details>
<summary>Pista 1</summary>

Busca un campo que controle tus permisos. Pista: empieza con la letra "r".

</details>

<details>
<summary>Pista 2</summary>

Los usuarios tienen endpoints de perfil. Busca:
- Un endpoint de actualización (probablemente con PUT)
- Endpoints de API que acepten POST/PUT sin cambiar la URL visible

</details>

---

## Fase 2 — Escalada de Privilegios

### Pregunta clave
> ¿Puedo cambiar mi rol a algo más poderoso sin que nadie lo verifique?

### ¿Estás atrapado?

<details>
<summary>Pista 1</summary>

¿La app te pide permisos especiales para modificar ciertos campos? ¿O confía en lo que envías?

</details>

<details>
<summary>Pista 2</summary>

Si logras ser admin, busca una ruta de administración. Usualmente:
- `/admin`
- `/dashboard`
- `/panel`

</details>

---

## Fase 3 — Acceso Interno

### Pregunta clave
> ¿Hay información sensible en subdominios o archivos internos?

### ¿Estás atrapado?

<details>
<summary>Pista 1</summary>

En Linux/Mac, edita `/etc/hosts` y agrega:

```
127.0.0.1  dev.vblog.local
```

Luego prueba acceder a `http://dev.vblog.local`

</details>

<details>
<summary>Pista 2</summary>

- Archivos HTML que documenten la API
- Logs con trazas de ejecución
- Información técnica del servidor

</details>

---

## Herramientas de Referencia

```bash
# Hacer peticiones HTTP
curl -I http://localhost/                    # Ver cabeceras
curl http://localhost/api/endpoint           # GET
curl -X POST http://localhost/api/endpoint   # POST

# Enumerar rutas
gobuster dir -u http://localhost \
  -w /usr/share/wordlists/common.txt

# Enumerar subdominios
gobuster vhost -u http://localhost \
  -w /usr/share/seclists/Discovery/DNS/subdomains-top1million-5000.txt
```
