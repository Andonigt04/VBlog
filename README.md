# VBlog — Aplicación Web Deliberadamente Vulnerable

VBlog es un blog de ciberseguridad construido con Laravel y Docker diseñado como ejercicio práctico de pentesting. Contiene vulnerabilidades reales e intencionadas que el alumno debe descubrir y explotar para escalar desde usuario anónimo hasta root en el servidor.

## Objetivo

Partir sin credenciales y alcanzar ejecución de comandos como `root`, encadenando las vulnerabilidades de la aplicación.

## Setup

**Requisitos:** Docker y Docker Compose.

```bash
git clone https://github.com/Andonigt04/VBlog.git
cd VBlog
docker compose up --build -d
```

La aplicación queda disponible en `http://localhost`.

## Documentación

| Documento | Descripción |
|---|---|
| [docs/memo.md](docs/memo.md) | Manual del alumno — fases y pistas progresivas |
| [docs/walkthrough.md](docs/walkthrough.md) | Manual del profesor — vulnerabilidades, explotación y parches |
| [docs/arquitecture.md](docs/arquitecture.md) | Arquitectura Docker y superficie de ataque |
