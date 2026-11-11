import type { Role } from '../constants/roles';

/** Usuario autenticado (respuesta de /auth/me, /auth/login, /auth/register). */
export interface AuthUser {
  id: number;
  name: string;
  username: string;
  email: string;
  role: Role;
  bio: string | null;
  avatarUrl: string | null;
  isPublic: boolean;
  reputation: number;
  createdAt: string;
}

/** Perfil público (lo que ve cualquier visitante). Sin email. */
export type PublicUser = Omit<AuthUser, 'email'>;

