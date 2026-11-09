import type { User } from '@prisma/client';
import type {
  ChangePasswordInput,
  LoginInput,
  RegisterInput,
  ResetPasswordInput,
} from '@nexohub/shared';
import { env } from '../../config/env';
import { logger } from '../../lib/logger';
import { sendPasswordResetEmail } from '../../lib/mailer';
import { burnPasswordCheck, hashPassword, verifyPassword } from '../../lib/password';
import { prisma } from '../../lib/prisma';
import { generateOpaqueToken, newTokenFamily, sha256, signAccessToken } from '../../lib/tokens';
import { AppError } from '../../utils/AppError';
import { authRepository as repo } from './auth.repository';

export interface ClientMeta {
  ip?: string;
  userAgent?: string;
}

export interface Session {
  user: User;
  accessToken: string;
  refreshToken: string;
}

/**
 * Ventana en la que un refresh token recién rotado puede volver a llegar sin
 * considerarse robo (dos pestañas refrescando a la vez).
 */
const ROTATION_GRACE_MS = 15_000;

const refreshExpiry = () => new Date(Date.now() + env.REFRESH_TOKEN_TTL_DAYS * 86_400_000);

async function issueSession(user: User, meta: ClientMeta, family = newTokenFamily()): Promise<Session> {
  const refreshToken = generateOpaqueToken();
  await repo.createRefreshToken({
    tokenHash: sha256(refreshToken),
    family,
    userId: user.id,
    ip: meta.ip?.slice(0, 45),
    userAgent: meta.userAgent?.slice(0, 255),
    expiresAt: refreshExpiry(),
  });
  return { user, accessToken: signAccessToken(user.id, user.role), refreshToken };
}

export const authService = {
  async register(input: RegisterInput, meta: ClientMeta): Promise<Session> {
    const taken = await repo.findTakenFields(input.email, input.username);
    if (taken.email || taken.username) {
      throw AppError.conflict('Hay datos que ya están en uso', [
        ...(taken.email ? [{ path: 'email', message: 'El email ya está registrado' }] : []),
        ...(taken.username ? [{ path: 'username', message: 'El nombre de usuario ya está en uso' }] : []),
      ]);
    }

    const user = await repo.createUser({
      name: input.name,
      username: input.username,
      email: input.email,
      passwordHash: await hashPassword(input.password),
    });

    return issueSession(user, meta);
  },

  async login(input: LoginInput, meta: ClientMeta): Promise<Session> {
    const user = await repo.findUserByIdentifier(input.identifier);

    if (!user) {
      await burnPasswordCheck(input.password);
      throw AppError.unauthenticated('Credenciales incorrectas', 'INVALID_CREDENTIALS');
    }

    const { valid, needsRehash } = await verifyPassword(user.passwordHash, input.password);
    if (!valid) throw AppError.unauthenticated('Credenciales incorrectas', 'INVALID_CREDENTIALS');

    // Migración transparente: bcrypt de PHP -> argon2id en el primer login exitoso.
    if (needsRehash) {
      await repo.updatePasswordHash(user.id, await hashPassword(input.password));
      logger.info({ userId: user.id }, 'Hash de contraseña actualizado a argon2id');
    }

    return issueSession(user, meta);
  },

  /**
   * Rotación de refresh token con detección de reutilización:
   * - token válido: se revoca y se emite uno nuevo en la misma familia.
   * - token ya rotado hace menos de ROTATION_GRACE_MS: carrera benigna, 401 sin castigo.
   * - token ya rotado/revocado fuera de la ventana: posible robo, se revoca toda la familia.
   */
  async refresh(rawToken: string | undefined, meta: ClientMeta): Promise<Session> {
    if (!rawToken) throw AppError.unauthenticated('Sesión no encontrada', 'REFRESH_INVALID');

    const stored = await repo.findRefreshTokenByHash(sha256(rawToken));
    if (!stored) throw AppError.unauthenticated('Sesión inválida', 'REFRESH_INVALID');

    if (stored.revokedAt) {
      const benignRace =
        stored.replacedBy !== null && Date.now() - stored.revokedAt.getTime() < ROTATION_GRACE_MS;
      if (!benignRace) {
        await repo.revokeFamily(stored.family);
        logger.warn({ userId: stored.userId, family: stored.family }, 'Reutilización de refresh token');
        throw AppError.unauthenticated('La sesión fue cerrada por seguridad', 'REFRESH_REUSED');
      }
      throw AppError.unauthenticated('Sesión renovada en otra pestaña', 'REFRESH_RACE');
    }

    if (stored.expiresAt <= new Date()) {
      throw AppError.unauthenticated('La sesión expiró', 'REFRESH_INVALID');
    }

    const nextToken = generateOpaqueToken();
    await prisma.$transaction(async (tx) => {
      const created = await repo.createRefreshToken(
        {
          tokenHash: sha256(nextToken),
          family: stored.family,
          userId: stored.userId,
          ip: meta.ip?.slice(0, 45),
          userAgent: meta.userAgent?.slice(0, 255),
          expiresAt: refreshExpiry(),
        },
        tx,
      );
      // Condicional: si otro request rotó este token en paralelo, no rotamos dos veces.
      const { count } = await repo.markRefreshTokenRotated(stored.id, created.id, tx);
      if (count === 0) throw AppError.unauthenticated('Sesión renovada en otra pestaña', 'REFRESH_RACE');
    });

    return {
      user: stored.user,
      accessToken: signAccessToken(stored.user.id, stored.user.role),
      refreshToken: nextToken,
    };
  },

  async logout(rawToken: string | undefined): Promise<void> {
    if (rawToken) await repo.revokeRefreshToken(sha256(rawToken));
  },

  async logoutAll(userId: number): Promise<void> {
    await repo.revokeAllForUser(userId);
  },

  async me(userId: number): Promise<User> {
    const user = await repo.findUserById(userId);
    if (!user) throw AppError.unauthenticated('La cuenta ya no existe');
    return user;
  },

  /** Siempre responde igual exista o no la cuenta (no permite enumerar emails). */
  async forgotPassword(email: string): Promise<void> {
    const user = await repo.findUserByEmail(email);
    if (!user) return;

    const token = generateOpaqueToken();
    const expiresAt = new Date(Date.now() + env.PASSWORD_RESET_TTL_MINUTES * 60_000);
    await repo.replacePasswordResetToken(user.id, sha256(token), expiresAt);

    const link = `${env.APP_URL.replace(/\/$/, '')}/restablecer/${token}`;
    // No bloquea la respuesta ni revela fallos de SMTP al cliente.
    sendPasswordResetEmail(user.email, user.name, link, env.PASSWORD_RESET_TTL_MINUTES).catch((err) =>
      logger.error({ err, userId: user.id }, 'No se pudo enviar el email de recuperación'),
    );
  },

  async resetPassword(input: ResetPasswordInput): Promise<void> {
    const record = await repo.findValidPasswordResetToken(sha256(input.token));
    if (!record) throw AppError.badRequest('El enlace es inválido o ya venció');

    const passwordHash = await hashPassword(input.password);

    await prisma.$transaction(async (tx) => {
      const { count } = await repo.consumePasswordResetToken(record.id, tx);
      if (count === 0) throw AppError.badRequest('El enlace ya fue utilizado');
      await repo.updatePasswordHash(record.userId, passwordHash, tx);
      // Cerrar todas las sesiones abiertas: si alguien tenía la cuenta, la pierde.
      await repo.revokeAllForUser(record.userId, tx);
    });
  },

  /** Cambia la contraseña y cierra las demás sesiones, conservando la actual. */
  async changePassword(userId: number, input: ChangePasswordInput, meta: ClientMeta): Promise<Session> {
    const user = await repo.findUserById(userId);
    if (!user) throw AppError.unauthenticated('La cuenta ya no existe');

    const { valid } = await verifyPassword(user.passwordHash, input.currentPassword);
    if (!valid) {
      throw AppError.validation([{ path: 'body.currentPassword', message: 'La contraseña actual no es correcta' }]);
    }

    const passwordHash = await hashPassword(input.password);
    await prisma.$transaction(async (tx) => {
      await repo.updatePasswordHash(userId, passwordHash, tx);
      await repo.revokeAllForUser(userId, tx);
    });

    return issueSession({ ...user, passwordHash }, meta);
  },
};
