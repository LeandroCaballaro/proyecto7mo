# Base de datos

Scripts para crear y poblar la base `proyecto7mo`.

## Nota para Windows / Laragon

Si en PowerShell aparece el error `php no se reconoce`, significa que PHP no esta en la ruta del sistema. En ese caso puedes usar el terminal de Laragon o ejecutar PHP con la ruta completa del ejecutable.

Ejemplo:

```powershell
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" Backend/database/005_seed_full_catalog.php
```

Desde la raiz del proyecto:

```powershell
php Backend/database/005_seed_full_catalog.php
```

## Orden recomendado

1. Crear tablas:

```sql
SOURCE Backend/database/001_schema.sql;
```

2. Crear usuarios base:

```sql
SOURCE Backend/database/002_seed_users.sql;
```

3. Cargar el catalogo real completo:

```powershell
php Backend/database/005_seed_full_catalog.php
```

## Seed principal

`005_seed_full_catalog.php` es el seed recomendado para dejar la app lista.

Hace lo siguiente:

- Carga 65 peliculas reales: 5 por cada genero existente.
- Usa un catalogo curado de IDs de IMDb.
- Consulta Cinemeta para traer titulo, descripcion, anio, director y poster.
- Usa anio y descripcion curados como respaldo si la API no devuelve metadata suficiente.
- Si Cinemeta no entrega un poster directo, usa Metahub como fallback.
- Descarga las portadas en `Frontend/public/covers/{movie_id}.{ext}` usando la extension real del archivo (`jpg`, `webp`, `png` o `gif`).
- Crea 1 resena, 3 comentarios y corazones por pelicula.
- Deja `external_source = 'nexohub_real'`.
- Borra y reemplaza solamente datos demo de `nexohub_seed` y `nexohub_real`.

Este seed necesita internet para traer metadata y portadas. Si una descarga falla de forma temporal, la pelicula igual queda con `poster_url` guardado en la base.

## Seeds alternativos

`003_seed_movies_from_api.php` consulta Cinemeta de forma mas abierta e inserta peliculas con `external_source = 'cinemeta'`.

Variables opcionales:

```powershell
$env:MOVIE_SEED_LIMIT=20
php Backend/database/003_seed_movies_from_api.php
```

Para descargar todas las peliculas disponibles desde Cinemeta:

```powershell
$env:MOVIE_SEED_LIMIT=0
php Backend/database/003_seed_movies_from_api.php
```

`004_seed_reviews_for_api_movies.php` toma peliculas con `external_source = 'cinemeta'` y crea actividad social demo.

Variables opcionales:

```powershell
$env:REVIEW_SEED_MOVIE_LIMIT=20
php Backend/database/004_seed_reviews_for_api_movies.php
```

Para generar resenas y respuestas para todas las peliculas traidas por Cinemeta:

```powershell
$env:REVIEW_SEED_MOVIE_LIMIT=0
php Backend/database/004_seed_reviews_for_api_movies.php
```

## Usuarios

`002_seed_users.sql` crea usuarios y reviewers base. Todos usan:

```text
password
```

Usuarios importantes:

- `admin` / `admin@nexohub.local`: rol `admin`.
- `superadmin` / `superadmin@nexohub.local`: rol `superadmin`.
- `carlosp`, `anagomez`, `marial`: usuarios normales.
