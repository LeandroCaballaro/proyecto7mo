# NexoHub — Proyecto 7mo

Plataforma de reseñas de películas con sistema de reputación por género. El repositorio separa **Frontend** (vistas PHP), **Backend** (API JSON) y **database** (scripts SQL de referencia).

## Tecnologías

| Área | Tecnología |
|------|------------|
| Servidor local | [Laragon](https://laragon.org/) (Apache/Nginx + PHP + MySQL) |
| Backend | PHP 8.x, PDO, arquitectura MVC ligera (controllers / services / models) |
| Base de datos | MySQL / MariaDB (soporte opcional SQLite en código) |
| Frontend | PHP (includes), HTML, CSS, [Tailwind CSS 2](https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/) vía CDN |
| Control de versiones | Git + GitHub |
| Variables de entorno | Archivo `.env` (ver [Configuración](#configuración-inicial)) |

No se usa Composer, framework PHP (Laravel/Symfony) ni bundler de JavaScript por ahora.

## Estructura del proyecto

```
proyecto7mo/
├── Backend/
│   ├── config/config.php      # Conexión a BD (lee variables de entorno)
│   ├── controllers/           # Respuestas HTTP / JSON
│   ├── services/                # Lógica de negocio
│   ├── models/                  # Acceso a datos (PDO)
│   └── public/api.php           # Punto de entrada de la API
├── Frontend/                    # Páginas y secciones del sitio
├── database/
│   ├── import.sql               # Esquema + datos de ejemplo
│   └── README.md                # Guía de Laragon y phpMyAdmin
├── public/                      # Assets estáticos (favicon, etc.)
└── README.md                    # Este archivo
```

## Configuración inicial

### 1. Clonar e instalar en Laragon

1. Clona el repo en la carpeta de sitios de Laragon (por defecto `C:\laragon\www\`).
2. En Laragon: **Start All**.
3. Crea la base de datos e importa el SQL:
   - Abre phpMyAdmin (`http://localhost/phpmyadmin`).
   - Importa `database/import.sql` **o** deja que el backend cree tablas al primer uso (ver nota en [Backend](#backend-estado-y-pendientes)).

### 2. Variables de entorno

```bash
cd Backend
copy .env.example .env
```

Edita `Backend/.env` con tus credenciales. **Importante:** `config.php` espera estos nombres (no los de `.env.example` tal cual):

| Variable en `.env` | Usada por `config.php` |
|--------------------|-------------------------|
| `DB_HOST` | Sí |
| `DB_NAME` o renombra `DB_DATABASE` → `DB_NAME` | `DB_NAME` |
| `DB_USER` o renombra `DB_USERNAME` → `DB_USER` | `DB_USER` |
| `DB_PASS` o deja vacío `DB_PASSWORD` → `DB_PASS` | `DB_PASS` |

PHP **no carga** el archivo `.env` automáticamente. En Laragon puedes:

- Definir variables en la configuración del virtual host, o
- Añadir más adelante un cargador (p. ej. `vlucas/phpdotenv` con Composer).

Si no defines variables, se usan los valores por defecto de `config.php` (`127.0.0.1`, `proyecto7mo`, `root`, contraseña vacía).

### 3. Probar la API

Con el proyecto servido por Laragon (ajusta la URL a tu virtual host):

```
GET http://proyecto7mo.test/Backend/public/api.php
GET http://proyecto7mo.test/Backend/public/api.php?route=movies
GET http://proyecto7mo.test/Backend/public/api.php?route=movies/featured
GET http://proyecto7mo.test/Backend/public/api.php?route=movies/genres
GET http://proyecto7mo.test/Backend/public/api.php?route=movies/genre/Aventura
```

Respuesta esperada: JSON con listas de películas o géneros.

### 4. Frontend

Abre en el navegador la carpeta `Frontend` según cómo tengas configurado el docroot en Laragon (p. ej. `http://proyecto7mo.test/Frontend/index.php`).

---

## Backend: estado y pendientes

### Lo que ya está implementado

- Conexión PDO (singleton) con MySQL y fallback SQLite.
- Creación automática de tablas `movies` y `reviewers` + datos de ejemplo si están vacías.
- API REST **solo lectura** para películas:
  - Listado completo
  - Destacadas (`featured = 1`)
  - Listado de géneros
  - Filtro por género
- Capas: `MoviesController` → `MovieService` → `Movie`.
- Cabeceras CORS básicas en `api.php`.

### Lo que falta o conviene agregar

| Prioridad | Tema | Detalle |
|-----------|------|---------|
| Alta | Cargar `.env` | Sin cargador, `getenv()` no lee `Backend/.env`. Unificar nombres con `.env.example`. |
| Alta | API de reseñadores | Existe tabla `reviewers` pero no hay endpoints; el frontend muestra datos fijos. |
| Alta | Conectar Frontend ↔ API | Secciones como películas destacadas y ranking no consumen `api.php` aún. |
| Alta | Autenticación | Registro / login (antes `sing-up.php`, eliminado). Botones en header sin backend. |
| Alta | Reseñas y reputación | No hay tablas `reviews`, `users` ni lógica de reputación por género (objetivo del producto). |
| Media | Esquema unificado | `import.sql` incluye `poster_url`, `director`, `created_at`; `Database.php` crea un esquema más simple. Riesgo de columnas faltantes si solo se usa el auto-schema. |
| Media | CRUD / POST | CORS permite POST pero no hay rutas de creación/edición. |
| Media | Película por ID, búsqueda, paginación | Solo listados y filtro por género. |
| Media | Manejo de errores | Respuestas JSON uniformes en fallos de BD; hoy pueden verse errores PHP en crudo. |
| Baja | `OPTIONS` preflight | Declarado en CORS pero no manejado explícitamente en el router. |
| Baja | URLs limpias | Rutas vía `?route=`; opcional `.htaccess` o router central. |
| Baja | Composer + autoload | Hoy todo es `require_once` manual. |
| Baja | Tests | No hay PHPUnit ni pruebas de API. |

### Endpoints actuales (referencia)

| Método | Ruta (`?route=`) | Descripción |
|--------|------------------|-------------|
| GET | *(vacío)* | Health check: `{"status":"ok",...}` |
| GET | `movies` | Todas las películas |
| GET | `movies/featured` | Películas destacadas |
| GET | `movies/genres` | Géneros distintos |
| GET | `movies/genre/{nombre}` | Películas por género (URL-encoded) |

---

## Trabajo con Git y ramas

Repositorio remoto: `https://github.com/LeandroCaballaro/proyecto7mo.git`

Ramas habituales:

- `main` — código integrado y estable.
- `feature/backend` — trabajo del backend (u otras `feature/...` por funcionalidad).

### Flujo recomendado para una nueva tarea

```bash
# 1. Actualizar main
git checkout main
git pull origin main

# 2. Crear rama desde main
git checkout -b feature/nombre-de-la-tarea

# 3. Trabajar, revisar cambios
git status
git diff

# 4. Guardar commits (mensajes claros en español o inglés)
git add .
git commit -m "feat: descripción breve del cambio"

# 5. Subir la rama al remoto
git push -u origin feature/nombre-de-la-tarea
```

### Integrar en main (Pull Request)

1. En GitHub: **Compare & pull request** desde tu rama hacia `main`.
2. Revisión por otro integrante del equipo.
3. **Merge** en GitHub cuando esté aprobado.
4. En local, volver a main actualizado:

```bash
git checkout main
git pull origin main
```

### Subir cambios si ya estás en una rama existente

```bash
git add .
git commit -m "fix: corrige conexión a BD"
git push
```

Si es la primera vez que subes esa rama: `git push -u origin nombre-de-la-rama`.

### Buenas prácticas del equipo

- **No subas** `Backend/.env` (está en `.gitignore`). Solo `.env.example`.
- Commits pequeños y frecuentes; un commit = un cambio lógico.
- Antes de abrir PR: `git pull origin main` en tu rama y resolver conflictos.
- Prefijos útiles en mensajes: `feat:`, `fix:`, `refactor:`, `docs:`.

### Comandos útiles

```bash
git branch -a              # Ver ramas locales y remotas
git log --oneline -10      # Historial reciente
git stash                  # Guardar cambios temporales sin commit
git stash pop              # Recuperar cambios guardados
```

---

## Próximos pasos sugeridos (roadmap)

1. Unificar `.env` y cargarlo en `config.php`.
2. Endpoints `reviewers` y conectar `top-reviewers-section.php`.
3. Consumir API desde Frontend (fetch o PHP server-side).
4. Modelo de usuarios, registro/login y tabla de reseñas.
5. Completar página `explorar.php` con filtros usando la API.
6. Alinear `import.sql` con el esquema que usa el código.

---

## Licencia y equipo

Proyecto académico / equipo 7mo. Consulta con el docente o el repositorio en GitHub para convenciones adicionales del curso.
