import type { Request } from 'express';
import { ipKeyGenerator, rateLimit, type Options } from 'express-rate-limit';
import type { ApiErrorBody } from '@nexohub/shared';
import { isTest } from '../config/env';

const tooMany = (message: string): ApiErrorBody => ({ error: { code: 'RATE_LIMITED', message } });

const common: Partial<Options> = {
  standardHeaders: 'draft-8',
  legacyHeaders: false,
  skip: () => isTest,
};

/** Límite general para toda la API. */
export const globalLimiter = rateLimit({
  ...common,
  windowMs: 15 * 60 * 1000,
  limit: 600,
  message: tooMany('Demasiadas solicitudes. Probá de nuevo en unos minutos.'),
});

/**
 * Login: por IP + identificador, para frenar fuerza bruta sobre una cuenta
 * sin bloquear a todos los usuarios detrás de la misma IP (NAT, universidad).
 * Solo cuenta los intentos fallidos.
 */
export const loginLimiter = rateLimit({
  ...common,
  windowMs: 15 * 60 * 1000,
  limit: 8,
  skipSuccessfulRequests: true,
  keyGenerator: (req: Request) => {
    const identifier = String((req.body as { identifier?: unknown })?.identifier ?? '').toLowerCase();
    // ipKeyGenerator agrupa IPv6 por subred /56 para que no se pueda rotar la IP y evadir el límite.
    return `${ipKeyGenerator(req.ip ?? '')}:${identifier}`;
  },
  message: tooMany('Demasiados intentos de inicio de sesión. Esperá 15 minutos.'),
});

export const registerLimiter = rateLimit({
  ...common,
  windowMs: 60 * 60 * 1000,
  limit: 10,
  message: tooMany('Demasiados registros desde esta red. Probá más tarde.'),
});

export const passwordResetLimiter = rateLimit({
  ...common,
  windowMs: 60 * 60 * 1000,
  limit: 5,
  message: tooMany('Demasiados pedidos de recuperación. Probá en una hora.'),
});

export const refreshLimiter = rateLimit({
  ...common,
  windowMs: 15 * 60 * 1000,
  limit: 120,
  message: tooMany('Demasiadas renovaciones de sesión.'),
});
