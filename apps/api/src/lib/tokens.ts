import { createHash, randomBytes, randomUUID } from 'node:crypto';
import jwt from 'jsonwebtoken';
import type { Role } from '@prisma/client';
import { env } from '../config/env';

const ISSUER = 'nexohub-api';
const AUDIENCE = 'nexohub-web';

export interface AccessTokenPayload {
  sub: string;
  role: Role;
}

export function signAccessToken(userId: number, role: Role): string {
  return jwt.sign({ role }, env.JWT_ACCESS_SECRET, {
    algorithm: 'HS256',
    subject: String(userId),
    issuer: ISSUER,
    audience: AUDIENCE,
    expiresIn: env.ACCESS_TOKEN_TTL_SECONDS,
  });
}

export type AccessTokenResult =
  | { ok: true; userId: number; role: Role }
  | { ok: false; reason: 'expired' | 'invalid' };

export function verifyAccessToken(token: string): AccessTokenResult {
  try {
    const payload = jwt.verify(token, env.JWT_ACCESS_SECRET, {
      algorithms: ['HS256'],
      issuer: ISSUER,
      audience: AUDIENCE,
    }) as jwt.JwtPayload & { role?: Role };

    const userId = Number(payload.sub);
    if (!Number.isInteger(userId) || !payload.role) return { ok: false, reason: 'invalid' };
    return { ok: true, userId, role: payload.role };
  } catch (err) {
    if (err instanceof jwt.TokenExpiredError) return { ok: false, reason: 'expired' };
    return { ok: false, reason: 'invalid' };
  }
}

/** Token opaco aleatorio (refresh / reset). 48 bytes = 64 caracteres base64url. */
export const generateOpaqueToken = (): string => randomBytes(48).toString('base64url');

export const sha256 = (value: string): string => createHash('sha256').update(value).digest('hex');

export const newTokenFamily = (): string => randomUUID();

