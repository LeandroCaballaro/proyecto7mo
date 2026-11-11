# NexoHub

Plataforma para explorar películas, escribir reseñas y gestionar favoritos. Monorepo TypeScript (Node + React).

| Paquete | Descripción |
| :--- | :--- |
| `apps/api` | Express 5 + Prisma 6 (MySQL). API REST `/api/v1`. |
| `apps/web` | React + Vite + Tailwind v4. |
| `packages/shared` | Esquemas Zod, constantes y tipos DTO usados por cliente y servidor. |

## Requisitos

- Node.js 20.11 o superior
- MySQL 8 / MariaDB (Laragon funciona tal cual)

## Puesta en marcha

```powershell
npm install
copy apps\api\.env.example apps\api\.env   # completar DATABASE_URL y secretos

# Levantar el Backend (API) y el Frontend (Web) simultáneamente:
npm run dev

# O si prefieres levantarlos por separado:
npm run dev:api     # Backend en http://localhost:4000/api/v1
npm run dev:web     # Frontend en http://localhost:5173

# Comandos de base de datos:
npm run db:migrate  # Aplica migraciones pendientes
npm run db:seed     # Géneros, usuarios demo, cuentas personales y catálogo base (65 películas)
npm run db:import-movies -w @nexohub/api                          # 240 películas actuales de TMDB en español
npm run db:import-movies -w @nexohub/api -- --count 300 --years 5 # cantidad y antigüedad a medida
npm run db:accounts -w @nexohub/api                               # solo crea/actualiza las cuentas personales

# Tests y comprobación de tipos:
npm run typecheck
npm run test
```

## Catálogo de películas

| Fuente | Uso | Idioma | Requisito |
| :--- | :--- | :--- | :--- |
| TMDB | Importación masiva y buscador de "Proponer película" | `es-MX`, cae a otra variante de español | `TMDB_API_KEY` (gratis, uso no comercial, atribución en el footer) |
| Cinemeta | Respaldo si no hay `TMDB_API_KEY` | Inglés | Ninguno |
| MyMemory | Traduce al español las sinopsis que solo existen en inglés | en → es | Opcional `TRANSLATE_EMAIL` (sube el cupo de 5.000 a 50.000 caracteres/día) |

Clave de TMDB: crear cuenta en themoviedb.org → Configuración → API → solicitar clave de desarrollador. Pegar la API key (v3) o el token de lectura (v4) en `TMDB_API_KEY` de `apps/api/.env`.

## Cuentas personales

Se definen en `apps/api/.env` (`SUPERADMIN_*` y `TEST_USER_*`) y se crean con `npm run db:seed` o `npm run db:accounts -w @nexohub/api`. La contraseña debe tener 8+ caracteres, mayúscula, minúscula y número. Las cuentas demo usan `SEED_DEMO_PASSWORD` (por defecto `password`).

## Autenticación

| Método | Ruta | Auth | Descripción |
| :--- | :--- | :--- | :--- |
| POST | `/api/v1/auth/register` | No | Crea la cuenta e inicia sesión |
| POST | `/api/v1/auth/login` | No | `identifier` (email o usuario) + `password` |
| POST | `/api/v1/auth/refresh` | Cookie refresh | Rota el refresh token |
| POST | `/api/v1/auth/logout` | No | Revoca el refresh token actual |
| POST | `/api/v1/auth/logout-all` | Sí | Cierra todas las sesiones |
| GET | `/api/v1/auth/me` | Sí | Usuario actual |
| POST | `/api/v1/auth/forgot-password` | No | Envía el enlace de recuperación |
| POST | `/api/v1/auth/reset-password` | No | `token` + nueva contraseña |
| POST | `/api/v1/auth/change-password` | Sí | Cambia la contraseña y cierra las demás sesiones |

- Access token JWT (15 min) en la cookie `nh_at`. Refresh token opaco (7 días) en la cookie `nh_rt`, restringida a `/api/v1/auth`. Ambas son `httpOnly` y `SameSite=Lax`.
- El refresh token se guarda hasheado (SHA-256). Si se reutiliza un token ya rotado, se revoca toda su familia.
- Los hashes bcrypt legacy (`$2y$`) se aceptan y se convierten a argon2id en el primer login.

### Formato de errores

```json
{ "error": { "code": "VALIDATION_ERROR", "message": "Datos inválidos", "details": [{ "path": "body.email", "message": "..." }] } }
```

## Lineamientos de UI (frontend)

- Componentes propios y reutilizables en `apps/web/src/components/ui`. No se usan los controles nativos del navegador cuando hay una versión propia: Combobox, Select, DatePicker y YearPicker, RatingInput, FileDropzone, Tabs, Dialog, Drawer, Toast, Tooltip, Pagination y Skeleton.
- Sin emojis en la interfaz, en los textos ni en el código. Para los iconos se usa solo `lucide-react`.

