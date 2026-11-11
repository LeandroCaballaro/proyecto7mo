import { Router } from 'express';
import { createMovieSchema, movieQuerySchema } from '@nexohub/shared';
import { authenticate, optionalAuth } from '../../middlewares/auth';
import { validate } from '../../middlewares/validate';
import { moviesController as c } from './movies.controller';

export const moviesRouter = Router();

moviesRouter.get('/', optionalAuth, validate({ query: movieQuerySchema }), c.list);
moviesRouter.get('/featured', optionalAuth, c.featured);
moviesRouter.get('/genres', c.genres);
moviesRouter.get('/external/search', c.searchExternal);
moviesRouter.get('/external/:imdbId', c.getExternalMeta);
moviesRouter.get('/:id', optionalAuth, c.show);
moviesRouter.post('/', authenticate, validate({ body: createMovieSchema }), c.create);
