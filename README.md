# VBlog (Vulnerability Blog)
Not for official use. 


FastApi:
- ¿artículos divididos por tags? pagina de noticias de vulnerabilidades, análisis técnicos, herramientas, buenas practicas

- subdominios oculto (como hacer los)

- roles de usuario
- endpoints autenticados (limitar el acceso a ciertas rutas)
- autenticación débil
- cookies inseguras
*- headers ausentes

- exposición de información ¿exponer infomacion de users?
- stored xss (al crear los comentarios que puedas poner xss pero con sinbolos en hexadecimal)
- api mal protegida (si información de users expuesta se podría categorizar como hecho)
- IDOR

- rutas(blade):
  - /login
  - /signup
  - /dashboard (privada por autenticación; y que solo sea accesible por escalado de privilegios en conjunto a un subdominio (admin.blog.local) + estar autenticado con admin precreado en la db con rol de admin)
  - /profile (vulnerabilidad al estar logeado como admin al tener esos permisos puedes hacer subir archivo desde el perfil y que hacer un reverse Shell para ganar acceso ; privada por autentizacion)
  - /admin (sin requerimiento de auth, pero solo accesible desde un subdominio especifico(admin.blog.local); panel de administrador)
  - /posts
  - /posts/{id} (créate, edit, delete)

FastApi:
- /api(res -> json, status code):
  - /login
  - /signup
  - /posts (GET,POST,PUT)
  - /posts/{id} (GET,POST,PUT,DELETE) [code, {title, tags[], text}] (texto incluye formato para que en el Blade tenga en cuenta cual es el memo y donde poner fotos, es decir como en un md pones la ruta de la foto y que lo ponga) ¿lista de tags estatica (problablemente)?
  - /comments (GET,POST,PUT)
  - /comments (GET,POST,PUT,DELETE)
  - /me ¿para el Blade de profile? [code, {user (como obj), timestamp}]
  - /status -> [code, {UP/DOWN, dbConnection, timestamp}]

Docker:
 - contenedor laravel/web
 - segundo FastAPI/api
 - tercer contenedor para db (por defecto tiene que tener contenido para no estar vacio al hacer docker compose up --build)
 - volumen para imagenes (imagenes si da tiempo o apetece)

Objects:
 - User(id, name, role)
 - Post(id, title, tags[], text, userId, created_at, updated_at)
 - Comments(id, postId, text(imagenes renderizables por url como en los posts), userId, created_at, updated_at)
Enums:
 - tags[pagina de noticias de vulnerabilidades, análisis técnicos, herramientas, buenas practicas]
 - role[client, editor, admin]
