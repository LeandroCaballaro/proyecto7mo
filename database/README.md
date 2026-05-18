## Requisitos Previos

Antes de iniciar el proyecto, asegúrate de tener instalado:

* **Laragon** (Wamp / Laragon Full recomendado)
* **PHP** (Versión 8.x o superior)
* **MySQL** o **MariaDB** (Incluido en Laragon)
* **phpMyAdmin** (Gestionado a través de Laragon)

## Configuración del Entorno Local

1. Abre **Laragon** e inicia todos los servicios (**Start All**).
2. Entra a **phpMyAdmin** desde tu navegador (usualmente `http://localhost/phpmyadmin`) o haz clic derecho en la interfaz de Laragon -> *MySQL* -> *phpMyAdmin*.
3. Crea una nueva base de datos para este proyecto.
4. Clona este repositorio en la carpeta raíz de tu servidor (por defecto `C:/laragon/www/`).

## Configuración del archivo .env

Duplica el archivo `.env.example`, renombralo como `.env` y configura las credenciales de la base de datos de Laragon:

*Nota: Por defecto, el usuario de MySQL en Laragon es `root` y la contraseña se deja vacía.*