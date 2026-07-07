## Base de datos

Esta carpeta contiene los scripts SQL del backend.

### Orden recomendado

1. Crear esquema y tablas:

```sql
SOURCE Backend/database/import.sql;
```

2. Cargar datos de prueba:

```sql
SOURCE Backend/database/seeds/001_demo_data.sql;
```

Si usas phpMyAdmin, importa primero `Backend/database/import.sql` y luego `Backend/database/seeds/001_demo_data.sql`.

### Usuarios seed

Todos los usuarios de demo usan la contrasena:

```text
password
```

Usuarios incluidos:

- `admin@nexohub.local` con rol `admin`.
- `carlos@nexohub.local`.
- `ana@nexohub.local`.
- `maria@nexohub.local`.

Los seeds incluyen peliculas, reviewers, resenas, respuestas, reputacion por genero y favoritos.
