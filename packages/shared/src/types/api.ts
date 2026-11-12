/** Formato uniforme de respuestas de la API v1. */

export interface ApiSuccess<T> {
  data: T;
}

export interface ApiPaginated<T> {
  data: T[];
  meta: {
    page: number;
    pageSize: number;
    total: number;
    totalPages: number;
  };
}

export interface ApiFieldIssue {
  path: string;
  message: string;
}

export const API_ERROR_CODES = [
  'BAD_REQUEST',
  'VALIDATION_ERROR',
  'UNAUTHENTICATED',
  'INVALID_CREDENTIALS',
  'TOKEN_EXPIRED',
  'TOKEN_INVALID',
  'REFRESH_INVALID',
  'REFRESH_REUSED',
  /** Otra pestaña ya renovó la sesión: el cliente debe reintentar, no cerrar sesión. */
  'REFRESH_RACE',
  'FORBIDDEN',
  'NOT_FOUND',
  'CONFLICT',
  'PAYLOAD_TOO_LARGE',
  'RATE_LIMITED',
  /** La fuente externa de películas (TMDB/Cinemeta) no respondió. */
  'EXTERNAL_UNAVAILABLE',
  'INTERNAL_ERROR',
] as const;

export type ApiErrorCode = (typeof API_ERROR_CODES)[number];

export interface ApiErrorBody {
  error: {
    code: ApiErrorCode;
    message: string;
    details?: ApiFieldIssue[];
  };
}
