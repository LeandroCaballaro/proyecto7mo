import { Router } from 'express';
import { prisma } from './lib/prisma';
import { isTmdbEnabled } from './lib/tmdb';
import { adminRouter } from './modules/admin/admin.routes';
import { authRouter } from './modules/auth/auth.routes';
import { moviesRouter } from './modules/movies/movies.routes';
import { rankingRouter } from './modules/ranking/ranking.routes';
import { reviewsRouter } from './modules/reviews/reviews.routes';
import { usersRouter } from './modules/users/users.routes';

export const apiRouter = Router();

apiRouter.get('/health', async (_req, res) => {
  await prisma.$queryRaw`SELECT 1`;
  res.json({ data: { status: 'ok', uptime: Math.round(process.uptime()) } });
});

/** Configuración pública que el frontend necesita (p. ej. qué fuente de películas citar). */
apiRouter.get('/config', (_req, res) => {
  res.json({ data: { movieSource: isTmdbEnabled() ? 'tmdb' : 'cinemeta' } });
});

apiRouter.get('/stats/summary', async (_req, res) => {
  const [usersCount, reviewsCount, moviesCount, genresCount] = await Promise.all([
    prisma.user.count(),
    prisma.review.count(),
    prisma.movie.count({ where: { status: 'APPROVED' } }),
    prisma.genre.count(),
  ]);

  res.json({
    data: {
      usersCount,
      reviewsCount,
      moviesCount,
      genresCount,
    },
  });
});

apiRouter.use('/auth', authRouter);
apiRouter.use('/movies', moviesRouter);
apiRouter.use('/reviews', reviewsRouter);
apiRouter.use('/ranking', rankingRouter);
apiRouter.use('/users', usersRouter);
apiRouter.use('/admin', adminRouter);
