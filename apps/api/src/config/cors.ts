import type { CorsOptions } from 'cors';
import { env } from './env';

export const isAllowedOrigin = (origin: string | undefined): boolean =>
  origin !== undefined && env.CLIENT_URLS.includes(origin.replace(/\/$/, ''));

export const corsOptions: CorsOptions = {
  origin(origin, callback) {
    // Requests sin Origin (curl, server-to-server, health checks) no son cross-site.
    if (!origin || isAllowedOrigin(origin)) return callback(null, true);
    return callback(null, false);
  },
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization'],
  maxAge: 600,
};

