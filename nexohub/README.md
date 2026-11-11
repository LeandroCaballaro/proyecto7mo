# NexoHub (stack moderno)

Migración de NexoHub (PHP 8 + PDO) a un monorepo TypeScript.

| Paquete | Descripción |
| :--- | :--- |
| `apps/api` | Express 5 + Prisma 6 (MySQL). API REST `/api/v1`. |
| `apps/web` | React + Vite + Tailwind v4 (fase 4). |
| `packages/shared` | Esquemas Zod, constantes y tipos DTO usados por cliente y servidor. |

## Requisitos

- Node.js 20.11 o superior
- pnpm 9 (`corepack enable`, o bien `corepack pnpm@9.15.0 <comando>`)
- MySQL 8 / MariaDB (Laragon funciona tal cual)

## Puesta en marcha

```powershell
# Levantar el Backend (API) y el Frontend (Web) simultáneamente:
npm run dev

# O si prefieres levantarlos por separado:
npm run dev:api     # Backend en http://localhost:4000/api/v1
npm run dev:web     # Frontend en http://localhost:5173

# Comandos de base de datos:
npm run db:migrate  # Aplica migraciones pendientes
npm run db:seed     # Siembra el catálogo completo (65 películas, géneros y usuarios demo)

# Tests y comprobación de tipos:
npm run typecheck
npm run test
```

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
- Los hashes bcrypt de PHP (`$2y$`) se aceptan y se convierten a argon2id en el primer login.

### Formato de errores

```json
{ "error": { "code": "VALIDATION_ERROR", "message": "Datos inválidos", "details": [{ "path": "body.email", "message": "..." }] } }
```

## Lineamientos de UI (frontend)

- Componentes propios y reutilizables en `apps/web/src/components/ui`. No se usan los controles nativos del navegador cuando hay una versión propia: Combobox, Select, DatePicker y YearPicker, RatingInput, FileDropzone, Tabs, Dialog, Drawer, Toast, Tooltip, Pagination y Skeleton.
- Sin emojis en la interfaz, en los textos ni en el código. Para los iconos se usa solo `lucide-react`.

