# VBlog — Manual del Alumno

## ¿Qué es esto?

VBlog es una aplicación web de ciberseguridad deliberadamente vulnerable.

---

## Acceso

```bash
sudo docker compose up --build -d
# La app está en http://localhost
```

---

## Tu Misión

**Escalar desde usuario anónimo → root**, siguiendo una cadena de vulnerabilidades.

**Objetivo final:** Ejecutar comandos como `root` en el servidor.

---

## Fase 0 — Reconocimiento (Sin cuenta)



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

## Fase 3 — Panel de Administración

<details>
<summary>Pista 1</summary>

Prueba enumerar rutas de la API con tu sesión de admin. Hay endpoints bajo `/api/admin/` que no están documentados públicamente.

</details>

<details>
<summary>Pista 2</summary>

Uno de los endpoints acepta un parámetro `path` para leer ficheros del servidor. ¿Qué pasa si pones `../../etc/passwd`?

</details>

<details>
<summary>Pista 3</summary>

Otro endpoint acepta un `filter` de texto para buscar posts. Prueba a añadir una comilla simple `'` al final del valor. ¿Ves algo inusual en la respuesta?

</details>

<details>
<summary>Pista 4</summary>

Hay un endpoint de subida de ficheros. El servidor guarda el fichero con el nombre que tú elijas. ¿Qué pasa si subes un fichero `.php`?

</details>

---

## Fase 4 — Escalada al Sistema (root)

<details>
<summary>Pista 1</summary>

Ejecuta `sudo -l` desde tu shell. ¿Hay algún binario que puedas ejecutar como root?

</details>

<details>
<summary>Pista 2</summary>

Busca en GTFOBins el binario que encontraste. La técnica de escalada con `sudo` suele usar `-exec`.

</details>

<details>
<summary>Pista 3</summary>

¿Hay algún binario con el bit `SUID` activo en `/tmp`? Prueba `ls -la /tmp/`.

</details>
