import type { NextFunction, Request, Response } from 'express';
import type { Role } from '@prisma/client';
import { ACCESS_COOKIE } from '../lib/cookies';
import { prisma } from '../lib/prisma';
import { verifyAccessToken } from '../lib/tokens';
import { AppError } from '../utils/AppError';

function extractToken(req: Request): string | undefined {
  const cookie = req.cookies?.[ACCESS_COOKIE] as string | undefined;
  if (cookie) return cookie;
  // Soporte Bearer para clientes no-navegador (tests, apps móviles, Postman).
  const header = req.headers.authorization;
  if (header?.startsWith('Bearer ')) return header.slice(7).trim();
  return undefined;
}

/** Exige un access token válido. Responde TOKEN_EXPIRED para que el cliente haga refresh. */
export function authenticate(req: Request, _res: Response, next: NextFunction): void {
  const token = extractToken(req);
  if (!token) throw AppError.unauthenticated();

  const result = verifyAccessToken(token);
  if (!result.ok) {
    throw result.reason === 'expired'
      ? AppError.unauthenticated('La sesión expiró', 'TOKEN_EXPIRED')
      : AppError.unauthenticated('Token inválido', 'TOKEN_INVALID');
  }

  req.auth = { userId: result.userId, role: result.role };
  next();
}

/** Adjunta `req.auth` si hay un token válido; si no, continúa como visitante. */
export function optionalAuth(req: Request, _res: Response, next: NextFunction): void {
  const token = extractToken(req);
  if (token) {
    const result = verifyAccessToken(token);
    if (result.ok) req.auth = { userId: result.userId, role: result.role };
  }
  next();
}

/**
 * Autoriza por rol consultando la BD (no confía en el rol del JWT, que puede estar
 * desactualizado hasta 15 min tras un cambio de rol). Debe ir después de `authenticate`.
 */
export function requireRole(...roles: Role[]) {
  return async (req: Request, _res: Response, next: NextFunction): Promise<void> => {
    if (!req.auth) throw AppError.unauthenticated();

    const user = await prisma.user.findUnique({
      where: { id: req.auth.userId },
      select: { role: true },
    });
    if (!user) throw AppError.unauthenticated('La cuenta ya no existe');
    if (!roles.includes(user.role)) throw AppError.forbidden();

    req.auth.role = user.role;
    next();
  };
}

