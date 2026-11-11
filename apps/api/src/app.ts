import cookieParser from 'cookie-parser';
import cors from 'cors';
import express from 'express';
import helmet from 'helmet';
import { pinoHttp } from 'pino-http';
import { corsOptions } from './config/cors';
import { env } from './config/env';
import { logger } from './lib/logger';
import { errorHandler, notFound } from './middlewares/errorHandler';
import { originGuard } from './middlewares/originGuard';
import { globalLimiter } from './middlewares/rateLimiter';
import { apiRouter } from './routes';

export function createApp() {
  const app = express();

  // Necesario para que req.ip sea la IP real detrás de un proxy (rate limiting por IP).
  app.set('trust proxy', env.TRUST_PROXY);
  app.disable('x-powered-by');

  app.use(helmet());
  app.use(cors(corsOptions));
  app.use(express.json({ limit: '100kb' }));
  app.use(cookieParser());
  app.use(
    pinoHttp({
      logger,
      autoLogging: { ignore: (req) => req.url === '/api/v1/health' },
      customLogLevel: (_req, res, err) =>
        err || res.statusCode >= 500 ? 'error' : res.statusCode >= 400 ? 'warn' : 'info',
    }),
  );

  app.use('/api', globalLimiter, originGuard);
  app.use('/api/v1', apiRouter);

  app.use(notFound);
  app.use(errorHandler);

  return app;
}

