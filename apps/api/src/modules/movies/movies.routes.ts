import { Router } from 'express';
import { z } from 'zod';
import {
  createMovieSchema,
  externalIdSchema,
  idParamSchema,
  movieQuerySchema,
} from '@nexohub/shared';
import { authenticate, optionalAuth } from '../../middlewares/auth';
import { externalLimiter } from '../../middlewares/rateLimiter';
import { validate } from '../../middlewares/validate';
import { moviesController as c } from './movies.controller';

export const moviesRouter = Router();

moviesRouter.get('/', optionalAuth, validate({ query: movieQuerySchema }), c.list);
moviesRouter.get('/featured', optionalAuth, c.featured);
moviesRouter.get('/genres', c.genres);
// Proxy a TMDB/Cinemeta: solo usuarios logueados y con límite propio para no agotar el cupo externo.
moviesRouter.get('/external/search', authenticate, externalLimiter, c.searchExternal);
moviesRouter.get(
  '/external/:externalId',
  authenticate,
  externalLimiter,
  validate({ params: z.object({ externalId: externalIdSchema }) }),
  c.getExternalMeta,
);
moviesRouter.get('/:id', optionalAuth, validate({ params: idParamSchema }), c.show);
moviesRouter.post('/', authenticate, validate({ body: createMovieSchema }), c.create);
