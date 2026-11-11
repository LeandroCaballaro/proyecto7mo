import type { NextFunction, Request, Response } from 'express';
import { isAllowedOrigin } from '../config/cors';
import { AppError } from '../utils/AppError';

const SAFE_METHODS = new Set(['GET', 'HEAD', 'OPTIONS']);

/**
 * Defensa CSRF complementaria a SameSite=Lax: en métodos que modifican estado,
 * si el navegador envía `Origin` (siempre lo hace en POST/PUT/PATCH/DELETE)
 * debe pertenecer a la lista blanca. Clientes sin Origin (curl, tests) pasan.
 */
export function originGuard(req: Request, _res: Response, next: NextFunction): void {
  if (SAFE_METHODS.has(req.method)) return next();
  const origin = req.headers.origin;
  if (origin && !isAllowedOrigin(origin)) throw AppError.forbidden('Origen no permitido');
  next();
}

