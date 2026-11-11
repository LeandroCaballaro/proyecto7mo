import type { ApiErrorBody } from '@nexohub/shared';

let isRefreshing = false;
let refreshSubscribers: ((ok: boolean) => void)[] = [];

function onRefreshed(ok: boolean) {
  refreshSubscribers.forEach((cb) => cb(ok));
  refreshSubscribers = [];
}

export class ApiError extends Error {
  constructor(
    public status: number,
    public code: string,
    message: string,
    public details?: { path: string; message: string }[],
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

export async function apiClient<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const url = endpoint.startsWith('/api') ? endpoint : `/api/v1${endpoint.startsWith('/') ? '' : '/'}${endpoint}`;

  const config: RequestInit = {
    ...options,
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      ...options.headers,
    },
  };

  let res = await fetch(url, config);

  // Intentar refresco automático transparente si vence el access token y no estamos ya en auth
  if (res.status === 401 && !url.includes('/auth/login') && !url.includes('/auth/refresh') && !url.includes('/auth/register')) {
    if (!isRefreshing) {
      isRefreshing = true;
      try {
        const refreshRes = await fetch('/api/v1/auth/refresh', {
          method: 'POST',
          credentials: 'include',
        });
        const ok = refreshRes.ok;
        isRefreshing = false;
        onRefreshed(ok);
        if (ok) {
          res = await fetch(url, config);
        }
      } catch {
        isRefreshing = false;
        onRefreshed(false);
      }
    } else {
      // Esperar al refresco en curso
      const ok = await new Promise<boolean>((resolve) => refreshSubscribers.push(resolve));
      if (ok) {
        res = await fetch(url, config);
      }
    }
  }

  if (!res.ok) {
    let errorData: ApiErrorBody | null = null;
    try {
      errorData = (await res.json()) as ApiErrorBody;
    } catch {
      // Body no era JSON
    }

    const message = errorData?.error?.message || `Error en la solicitud (${res.status})`;
    const code = errorData?.error?.code || 'UNKNOWN_ERROR';
    const details = errorData?.error?.details;

    throw new ApiError(res.status, code, message, details);
  }

  if (res.status === 204) {
    return {} as T;
  }

  return (await res.json()) as T;
}

