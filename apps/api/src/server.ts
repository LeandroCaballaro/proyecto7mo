import { createApp } from './app';
import { env } from './config/env';
import { logger } from './lib/logger';
import { prisma } from './lib/prisma';
import { authRepository } from './modules/auth/auth.repository';

const app = createApp();

const server = app.listen(env.PORT, () => {
  logger.info(`API NexoHub escuchando en http://localhost:${env.PORT}/api/v1`);
});

// Limpieza periódica de refresh tokens vencidos (cada 6 h).
const cleanup = setInterval(
  () => {
    authRepository
      .deleteExpiredRefreshTokens(new Date())
      .then(({ count }) => count > 0 && logger.info({ count }, 'Refresh tokens vencidos eliminados'))
      .catch((err) => logger.error({ err }, 'Fallo la limpieza de refresh tokens'));
  },
  6 * 60 * 60 * 1000,
);
cleanup.unref();

let shuttingDown = false;
async function shutdown(signal: string) {
  if (shuttingDown) return;
  shuttingDown = true;
  logger.info(`${signal} recibido, cerrando servidor...`);

  clearInterval(cleanup);
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
process.on('unhandledRejection', (reason) => logger.error({ reason }, 'Promesa rechazada sin manejar'));

