import type { Role } from '@prisma/client';

declare global {
  namespace Express {
    interface Request {
      /** Presente tras `authenticate` (o `optionalAuth` si hay sesión válida). */
      auth?: {
        userId: number;
        /** Rol según el JWT. Para permisos sensibles usar `requireRole`, que consulta la BD. */
        role: Role;
      };
    }
  }
}

export {};

