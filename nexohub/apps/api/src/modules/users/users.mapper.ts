import type { User } from '@prisma/client';
import type { AuthUser, PublicUser } from '@nexohub/shared';

/** Único punto donde un `User` de Prisma se convierte en DTO: nunca se filtra `passwordHash`. */
export function toAuthUser(user: User): AuthUser {
  return {
    id: user.id,
    name: user.name,
    username: user.username,
    email: user.email,
    role: user.role,
    bio: user.bio,
    avatarUrl: user.avatarUrl,
    isPublic: user.isPublic,
    reputation: user.reputation,
    createdAt: user.createdAt.toISOString(),
  };
}

export function toPublicUser(user: User): PublicUser {
  const { email: _email, ...rest } = toAuthUser(user);
  return rest;
}

