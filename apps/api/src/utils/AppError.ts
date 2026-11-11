import type { ApiErrorCode, ApiFieldIssue } from '@nexohub/shared';

export class AppError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: ApiErrorCode,
    message: string,
    public readonly details?: ApiFieldIssue[],
  ) {
    super(message);
    this.name = 'AppError';
  }

  static badRequest(message = 'Solicitud inválida', details?: ApiFieldIssue[]) {
    return new AppError(400, 'BAD_REQUEST', message, details);
  }
  static validation(details: ApiFieldIssue[], message = 'Datos inválidos') {
    return new AppError(422, 'VALIDATION_ERROR', message, details);
  }
  static unauthenticated(message = 'Necesitás iniciar sesión', code: ApiErrorCode = 'UNAUTHENTICATED') {
    return new AppError(401, code, message);
  }
  static forbidden(message = 'No tenés permisos para esta acción') {
    return new AppError(403, 'FORBIDDEN', message);
  }
  static notFound(message = 'Recurso no encontrado') {
    return new AppError(404, 'NOT_FOUND', message);
  }
  static conflict(message: string, details?: ApiFieldIssue[]) {
    return new AppError(409, 'CONFLICT', message, details);
  }
}

