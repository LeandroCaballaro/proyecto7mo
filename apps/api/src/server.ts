import { createApp } from './app';
import { env, isTest } from './config/env';
import { pruneExpiredCache } from './lib/cache';
import { logger } from './lib/logger';
import { prisma } from './lib/prisma';
import { authRepository } from './modules/auth/auth.repository';
import { scheduleDailyImport } from './modules/movies/catalog-import.service';

const app = createApp();

const server = app.listen(env.PORT, () => {
  logger.info(`API NexoHub escuchando en http://localhost:${env.PORT}/api/v1`);
});

// Limpieza periódica de refresh tokens y caché externa vencidos (cada 6 h).
const cleanup = setInterval(
  () => {
    authRepository
      .deleteExpiredRefreshTokens(new Date())
      .then(
        ({ count }) => count > 0 && logger.info({ count }, 'Refresh tokens vencidos eliminados'),
      )
      .catch((err) => logger.error({ err }, 'Fallo la limpieza de refresh tokens'));
    pruneExpiredCache()
      .then(({ count }) => count > 0 && logger.info({ count }, 'Caché externa vencida eliminada'))
      .catch((err) => logger.error({ err }, 'Falló la limpieza de la caché externa'));
  },
  6 * 60 * 60 * 1000,
);
cleanup.unref();

const importTimer =
  env.AUTO_IMPORT_MOVIES > 0 && !isTest
    ? scheduleDailyImport(env.AUTO_IMPORT_MOVIES, env.AUTO_IMPORT_YEARS)
    : undefined;
if (importTimer) {
  logger.info(`Importación automática: ${env.AUTO_IMPORT_MOVIES} películas nuevas por día`);
}

let shuttingDown = false;
async function shutdown(signal: string) {
  if (shuttingDown) return;
  shuttingDown = true;
  logger.info(`${signal} recibido, cerrando servidor...`);

  clearInterval(cleanup);
  if (importTimer) clearInterval(importTimer);
  server.close(async () => {
    await prisma.$disconnect();
    logger.info('Servidor cerrado');
    process.exit(0);
  });

  // Si quedan conexiones colgadas, forzar salida.
  setTimeout(() => process.exit(1), 10_000).unref();
}

process.on('SIGINT', () => void shutdown('SIGINT'));
process.on('SIGTERM', () => void shutdown('SIGTERM'));
process.on('unhandledRejection', (reason) =>
  logger.error({ reason }, 'Promesa rechazada sin manejar'),
);
