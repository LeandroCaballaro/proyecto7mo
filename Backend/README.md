# Backend

Backend PHP con un front controller simple en `Backend/public/api.php`, controladores en `controllers`, modelos en `models` y servicios en `services`.

## Configuracion

La conexion se define en `Backend/config/config.php`. Puede leer variables desde `Backend/.env`:

```env
DB_HOST=127.0.0.1
DB_NAME=proyecto7mo
DB_USER=root
DB_PASS=
```

Si no existe `.env`, usa valores por defecto compatibles con Laragon.

## Base De Datos Y Seeds

Orden recomendado:

```powershell
mysql -u root proyecto7mo < Backend/database/001_schema.sql
mysql -u root proyecto7mo < Backend/database/002_seed_users.sql
php Backend/database/003_seed_movies_from_api.php
php Backend/database/004_seed_reviews_for_api_movies.php
```

`003_seed_movies_from_api.php` consulta Cinemeta y guarda `external_source`, `external_id` y `poster_url` para no depender de peliculas hardcodeadas.

## Roles

- `user`: puede editar su perfil, escribir resenas, responder y marcar favoritas.
- `admin`: puede entrar al panel de administracion y gestionar usuarios comunes/peliculas/comentarios.
- `superadmin`: ademas puede gestionar cuentas administradoras.

Las cuentas `admin` y `superadmin` no pueden borrarse desde el perfil. Una cuenta administradora solo puede eliminarse desde administracion por un `superadmin`.

## Fotos De Perfil

Hoy se guardan localmente en `Frontend/uploads`. Para no guardar imagenes dentro del proyecto hay tres caminos buenos:

1. Guardarlas fuera del repo, por ejemplo `C:\laragon\data\nexohub_uploads`, y servirlas con un alias de Apache/Nginx.
2. Guardarlas en un bucket externo, como S3, Cloudinary o Firebase Storage, y guardar solo la URL en `users.profile_image`.
3. Guardarlas como archivos privados fuera del webroot y crear un endpoint PHP que las entregue validando permisos.

Para este proyecto local, mover `Frontend/uploads` fuera del repo con un alias del servidor es la opcion mas simple.
