# Base De Datos

Scripts para crear y poblar la base `proyecto7mo`.

## Orden De Ejecucion

1. Crear tablas:

```sql
SOURCE Backend/database/001_schema.sql;
```

2. Crear usuarios base:

```sql
SOURCE Backend/database/002_seed_users.sql;
```

3. Cargar peliculas desde API:

```powershell
php Backend/database/003_seed_movies_from_api.php
```

4. Crear resenas, respuestas, reputacion y favoritos para esas peliculas:

```powershell
php Backend/database/004_seed_reviews_for_api_movies.php
```

## Catalogo completo por genero

Para cargar un catalogo demo amplio con 5 peliculas por cada categoria, 1 reseña por pelicula, 3 comentarios por pelicula y corazones suficientes para ranking:

```powershell
php Backend/database/005_seed_full_catalog.php
```

Este seed usa `external_source = 'nexohub_seed'`, es idempotente y no depende de internet.

## Seeds

`002_seed_users.sql` crea usuarios y reviewers base. Todos usan:

```text
password
```

Usuarios importantes:

- `admin` / `admin@nexohub.local`: rol `admin`.
- `superadmin` / `superadmin@nexohub.local`: rol `superadmin`.
- `carlosp`, `anagomez`, `marial`: usuarios normales.

`003_seed_movies_from_api.php` consulta Cinemeta, inserta peliculas reales y descarga posters en `Frontend/public/covers`.

Variables opcionales:

```powershell
$env:MOVIE_SEED_LIMIT=20
php Backend/database/003_seed_movies_from_api.php
```

`004_seed_reviews_for_api_movies.php` toma las peliculas con `external_source = 'cinemeta'` y crea datos sociales demo. Es idempotente: se puede ejecutar mas de una vez sin duplicar resenas principales.

Variable opcional:

```powershell
$env:REVIEW_SEED_MOVIE_LIMIT=20
php Backend/database/004_seed_reviews_for_api_movies.php
```
