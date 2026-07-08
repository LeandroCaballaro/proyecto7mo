# NexoHub - Proyecto 7mo

NexoHub es una plataforma PHP para explorar peliculas, escribir resenas, responder comentarios, marcar favoritas y administrar usuarios/peliculas desde un panel interno.

## Requisitos

- Laragon o stack equivalente con Apache/Nginx, PHP 8.x y MySQL/MariaDB.
- Extension PDO MySQL habilitada.
- Acceso a internet solo para ejecutar el seed de peliculas desde API.

## Estructura Principal

```text
proyecto7mo/
├── Backend/
│   ├── config/          Configuracion de BD y variables .env
│   ├── controllers/     Controladores HTTP de la API
│   ├── database/        Schema y seeds
│   ├── models/          Acceso a datos
│   ├── public/          Entrada API: api.php
│   └── services/        Logica de autenticacion y peliculas
├── Frontend/
│   ├── api/             Endpoints auxiliares usados por vistas
│   ├── assets/css/      Estilos
│   ├── components/      Header, footer y secciones reutilizables
│   ├── public/          Imagenes publicas y posters
│   ├── uploads/         Fotos de perfil subidas localmente
│   └── *.php            Paginas visibles
└── index.php            Entrada publica principal
```

## Configuracion Local

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

6. Cargar peliculas reales desde API y luego comentarios demo:

```powershell
php Backend/database/003_seed_movies_from_api.php
php Backend/database/004_seed_reviews_for_api_movies.php
```

Entrada web:

```text
http://localhost/proyecto7mo/index.php
```

## Usuarios Demo

Todos usan contrasena:

```text
password
```

- `admin` o `admin@nexohub.local`: administrador normal.
- `superadmin` o `superadmin@nexohub.local`: puede gestionar cuentas administradoras.
- `carlosp`, `anagomez`, `marial`: usuarios normales.

El login acepta correo o nombre de usuario.

## API Interna

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

## Notas De Almacenamiento

Actualmente las fotos de perfil se guardan en `Frontend/uploads` y la BD guarda la URL publica. Para produccion conviene moverlas fuera del repositorio o a un storage externo. Ver [Backend/README.md](Backend/README.md) para opciones.
