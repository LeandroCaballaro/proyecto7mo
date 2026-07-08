# NexoHub - Proyecto 7mo

NexoHub es una plataforma PHP para explorar peliculas, escribir resenas, responder comentarios, marcar favoritas y administrar usuarios/peliculas desde un panel interno.

## Requisitos

- Laragon o stack equivalente con Apache/Nginx, PHP 8.x y MySQL/MariaDB.
- Extension PDO MySQL habilitada.
- Acceso a internet para ejecutar los seeds que consultan APIs externas.

## Estructura principal

```text
proyecto7mo/
|-- Backend/
|   |-- config/          Configuracion de BD y variables .env
|   |-- controllers/     Controladores HTTP de la API
|   |-- database/        Schema y seeds
|   |-- models/          Acceso a datos
|   |-- public/          Entrada API: api.php
|   `-- services/        Logica de autenticacion y peliculas
|-- Frontend/
|   |-- api/             Endpoints auxiliares usados por vistas
|   |-- assets/css/      Estilos
|   |-- components/      Header, footer y secciones reutilizables
|   |-- public/          Imagenes publicas y posters
|   |-- uploads/         Fotos de perfil subidas localmente
|   `-- *.php            Paginas visibles
`-- index.php            Entrada publica principal
```

## Configuracion local

1. Colocar el proyecto en `C:\laragon\www\proyecto7mo`.
2. Iniciar Laragon.
3. Crear la base `proyecto7mo`.
4. Importar el schema:

```powershell
mysql -u root proyecto7mo < Backend/database/001_schema.sql
```

5. Cargar usuarios demo:

```powershell
mysql -u root proyecto7mo < Backend/database/002_seed_users.sql
```

6. Cargar el catalogo real recomendado:

```powershell
php Backend/database/005_seed_full_catalog.php
```

Entrada web:

```text
http://localhost/proyecto7mo/index.php
```

## Seeds recomendados

El seed principal es `Backend/database/005_seed_full_catalog.php`.

Ese seed carga 65 peliculas reales, 5 por cada genero del proyecto, usando IDs de IMDb y metadata de Cinemeta. Tambien crea 1 resena principal, 3 comentarios y corazones por pelicula para que el ranking y la actividad tengan datos desde el inicio.

El seed reemplaza automaticamente los datos demo anteriores de `external_source = 'nexohub_seed'` y `external_source = 'nexohub_real'`. No borra peliculas creadas por usuarios ni peliculas de otras fuentes.

Los scripts `003_seed_movies_from_api.php` y `004_seed_reviews_for_api_movies.php` quedan disponibles como alternativa incremental, pero para dejar el proyecto completo y consistente conviene ejecutar `005_seed_full_catalog.php`.

## Usuarios demo

Todos usan contrasena:

```text
password
```

- `admin` o `admin@nexohub.local`: administrador normal.
- `superadmin` o `superadmin@nexohub.local`: puede gestionar cuentas administradoras.
- `carlosp`, `anagomez`, `marial`: usuarios normales.

El login acepta correo o nombre de usuario.

## API interna

Entrada:

```text
http://localhost/proyecto7mo/Backend/public/api.php
```

Rutas utiles:

```text
GET  ?route=movies
GET  ?route=movies/featured
GET  ?route=movies/genres
GET  ?route=movies/genre/{genero}
GET  ?route=movies/{id}
POST ?route=auth/login
POST ?route=auth/register
POST ?route=reviews
POST ?route=reviews/{id}/responses
```

## Notas de almacenamiento

Actualmente las fotos de perfil se guardan en `Frontend/uploads` y la BD guarda la URL publica. Para produccion conviene moverlas fuera del repositorio o usar un storage externo. Ver `Backend/README.md` para opciones.
