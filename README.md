# NexoHub - Proyecto 7mo

Plataforma de resenas de peliculas con sistema de reputacion por genero. El proyecto separa el frontend PHP de la API backend y deja los scripts SQL dentro del backend.

## Tecnologias

| Area | Tecnologia |
|------|------------|
| Servidor local | Laragon, Apache/Nginx, PHP y MySQL/MariaDB |
| Backend | PHP 8.x, PDO, MVC ligero |
| Frontend | PHP, HTML, CSS y Tailwind CSS via CDN |
| Base de datos | MySQL/MariaDB |

## Estructura

```text
proyecto7mo/
├── Backend/
│   ├── config/
│   ├── controllers/
│   ├── database/
│   │   ├── import.sql
│   │   ├── seeds/
│   │   │   └── 001_demo_data.sql
│   │   └── README.md
│   ├── models/
│   ├── public/
│   │   └── api.php
│   └── services/
├── Frontend/
│   ├── public/
│   │   ├── covers/
│   │   ├── backgroung.jpg
│   │   ├── NexoHub Logo.png
│   │   └── nhlogo.png
│   ├── style/
│   └── *.php
├── index.php
└── README.md
```

## Configuracion local

1. Clona el proyecto dentro de `C:\laragon\www\`.
2. Inicia Laragon con **Start All**.
3. Crea la base de datos `proyecto7mo`.
4. Importa `Backend/database/import.sql`.
5. Opcionalmente importa `Backend/database/seeds/001_demo_data.sql` para datos de prueba.

Si no importas el SQL, `Backend/models/Database.php` crea las tablas principales al primer uso.

## Seeds

El archivo `Backend/database/seeds/001_demo_data.sql` carga usuarios, peliculas, reviewers, resenas, respuestas, reputacion por genero y favoritos.

Todos los usuarios seed usan la contrasena:

```text
password
```

Usuarios incluidos:

- `admin@nexohub.local` con rol `admin`.
- `carlos@nexohub.local`.
- `ana@nexohub.local`.
- `maria@nexohub.local`.

## API

```text
GET http://localhost/proyecto7mo/Backend/public/api.php
GET http://localhost/proyecto7mo/Backend/public/api.php?route=movies
GET http://localhost/proyecto7mo/Backend/public/api.php?route=movies/featured
GET http://localhost/proyecto7mo/Backend/public/api.php?route=movies/genres
GET http://localhost/proyecto7mo/Backend/public/api.php?route=movies/genre/Aventura
```

## Frontend

La entrada principal es:

```text
http://localhost/proyecto7mo/index.php
```

Los assets estaticos quedaron en `Frontend/public`.
