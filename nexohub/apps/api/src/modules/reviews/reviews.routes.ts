import { Router } from 'express';
import { reviewResponseSchema, reviewSchema } from '@nexohub/shared';
import { authenticate, optionalAuth } from '../../middlewares/auth';
import { validate } from '../../middlewares/validate';
import { reviewsController as c } from './reviews.controller';

export const reviewsRouter = Router();

// /api/v1/reviews/movie/:movieId
reviewsRouter.get('/movie/:movieId', optionalAuth, c.listByMovie);
reviewsRouter.post(
  '/movie/:movieId',
  authenticate,
  validate({ body: reviewSchema }),
  c.addOrUpdate,
);

// /api/v1/reviews/:id/...
reviewsRouter.delete('/:id', authenticate, c.remove);
reviewsRouter.post('/:id/like', authenticate, c.toggleLike);
reviewsRouter.get('/:id/responses', c.listResponses);
reviewsRouter.post(
  '/:id/responses',
  authenticate,
  validate({ body: reviewResponseSchema }),
  c.addResponse,
);

