import type { NextFunction, Request, Response } from 'express';
import { Prisma } from '@prisma/client';
import type { ApiErrorBody } from '@nexohub/shared';
import { isProd } from '../config/env';
import { logger } from '../lib/logger';
import { AppError } from '../utils/AppError';

export function notFound(req: Request, _res: Response, next: NextFunction): void {
  next(AppError.notFound(`Ruta no encontrada: ${req.method} ${req.path}`));
}

// Express identifica los error handlers por aridad: los 4 parámetros son obligatorios.
export function errorHandler(err: unknown, req: Request, res: Response, _next: NextFunction): void {
  const appError = toAppError(err);

  if (appError.status >= 500) {
    (req.log ?? logger).error({ err }, 'Error no controlado');
  }

  const body: ApiErrorBody = {
    error: {
      code: appError.code,
      message: appError.status >= 500 && isProd ? 'Error interno del servidor' : appError.message,
      ...(appError.details ? { details: appError.details } : {}),
    },
  };

  res.status(appError.status).json(body);
}

function toAppError(err: unknown): AppError {
  if (err instanceof AppError) return err;

  // Violación de UNIQUE que se escapó de las validaciones previas (condición de carrera).
  if (err instanceof Prisma.PrismaClientKnownRequestError) {
    if (err.code === 'P2002') {
      const target = ([] as string[]).concat((err.meta?.target as string[] | string) ?? []);
      return AppError.conflict(
        'El recurso ya existe',
        target.map((field) => ({ path: field, message: 'Ya está en uso' })),
      );
    }
    if (err.code === 'P2025') return AppError.notFound();
  }

  // Errores de body-parser (JSON mal formado, payload enorme).
  if (typeof err === 'object' && err !== null && 'type' in err) {
    const type = (err as { type?: string }).type;
    if (type === 'entity.parse.failed') return AppError.badRequest('JSON mal formado');
    if (type === 'entity.too.large') {
      return new AppError(413, 'PAYLOAD_TOO_LARGE', 'El cuerpo de la solicitud es demasiado grande');
    }
  }

  return new AppError(
    500,
    'INTERNAL_ERROR',
    err instanceof Error ? err.message : 'Error interno del servidor',
  );
}

