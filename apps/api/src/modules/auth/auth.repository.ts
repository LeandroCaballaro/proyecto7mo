import type { Prisma } from '@prisma/client';
import { prisma, type TxClient } from '../../lib/prisma';

/** Capa de acceso a datos de auth. Sin reglas de negocio: solo queries. */
export const authRepository = {
  // ───────────── Usuarios ─────────────

  findUserById(id: number) {
    return prisma.user.findUnique({ where: { id } });
  },

  findUserByEmail(email: string) {
    return prisma.user.findUnique({ where: { email } });
  },

  /** Login por email o username (MySQL compara case-insensitive con la collation por defecto). */
  findUserByIdentifier(identifier: string) {
    return identifier.includes('@')
      ? prisma.user.findUnique({ where: { email: identifier.toLowerCase() } })
      : prisma.user.findUnique({ where: { username: identifier } });
  },

  async findTakenFields(email: string, username: string) {
    const rows = await prisma.user.findMany({
      where: { OR: [{ email }, { username }] },
      select: { email: true, username: true },
    });
    return {
      email: rows.some((r) => r.email.toLowerCase() === email.toLowerCase()),
      username: rows.some((r) => r.username.toLowerCase() === username.toLowerCase()),
    };
  },

  createUser(data: Prisma.UserCreateInput) {
    return prisma.user.create({ data });
  },

  updatePasswordHash(userId: number, passwordHash: string, db: TxClient = prisma) {
    return db.user.update({ where: { id: userId }, data: { passwordHash } });
  },

  // ───────────── Refresh tokens ─────────────

  createRefreshToken(data: Prisma.RefreshTokenUncheckedCreateInput, db: TxClient = prisma) {
    return db.refreshToken.create({ data });
  },

  findRefreshTokenByHash(tokenHash: string) {
    return prisma.refreshToken.findUnique({ where: { tokenHash }, include: { user: true } });
  },

  /** Rotación condicional: solo revoca si nadie lo rotó antes. Devuelve { count }. */
  markRefreshTokenRotated(id: string, replacedBy: string, db: TxClient = prisma) {
    return db.refreshToken.updateMany({
      where: { id, revokedAt: null },
      data: { revokedAt: new Date(), replacedBy },
    });
  },

  revokeRefreshToken(tokenHash: string) {
    return prisma.refreshToken.updateMany({
      where: { tokenHash, revokedAt: null },
      data: { revokedAt: new Date() },
    });
  },

  revokeFamily(family: string) {
    return prisma.refreshToken.updateMany({
      where: { family, revokedAt: null },
      data: { revokedAt: new Date() },
    });
  },

  revokeAllForUser(userId: number, db: TxClient = prisma) {
    return db.refreshToken.updateMany({
      where: { userId, revokedAt: null },
      data: { revokedAt: new Date() },
    });
  },

  deleteExpiredRefreshTokens(before: Date) {
    return prisma.refreshToken.deleteMany({ where: { expiresAt: { lt: before } } });
  },

  // ───────────── Reset de contraseña ─────────────

  /** Crea un token nuevo e invalida los anteriores no usados del mismo usuario. */
  replacePasswordResetToken(userId: number, tokenHash: string, expiresAt: Date) {
    return prisma.$transaction([
      prisma.passwordResetToken.deleteMany({ where: { userId, usedAt: null } }),
      prisma.passwordResetToken.create({ data: { userId, tokenHash, expiresAt } }),
    ]);
  },

  findValidPasswordResetToken(tokenHash: string) {
    return prisma.passwordResetToken.findFirst({
      where: { tokenHash, usedAt: null, expiresAt: { gt: new Date() } },
    });
  },

  /** Marca el token como usado solo si nadie lo usó antes (evita doble uso concurrente). */
  consumePasswordResetToken(id: number, db: TxClient = prisma) {
    return db.passwordResetToken.updateMany({
      where: { id, usedAt: null },
      data: { usedAt: new Date() },
    });
  },
};
