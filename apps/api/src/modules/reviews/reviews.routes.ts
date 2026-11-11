import { Router } from 'express';
import {
  idParamSchema,
  movieIdParamSchema,
  reviewResponseSchema,
  reviewSchema,
} from '@nexohub/shared';
import { authenticate, optionalAuth } from '../../middlewares/auth';
import { validate } from '../../middlewares/validate';
import { reviewsController as c } from './reviews.controller';

export const reviewsRouter = Router();

// /api/v1/reviews/movie/:movieId
reviewsRouter.get(
  '/movie/:movieId',
  optionalAuth,
  validate({ params: movieIdParamSchema }),
  c.listByMovie,
);
reviewsRouter.post(
  '/movie/:movieId',
  authenticate,
  validate({ params: movieIdParamSchema, body: reviewSchema }),
  c.addOrUpdate,
);

// /api/v1/reviews/:id/...
const byId = validate({ params: idParamSchema });
reviewsRouter.delete('/:id', authenticate, byId, c.remove);
reviewsRouter.post('/:id/like', authenticate, byId, c.toggleLike);
reviewsRouter.get('/:id/responses', byId, c.listResponses);
reviewsRouter.post(
  '/:id/responses',
  authenticate,
  validate({ params: idParamSchema, body: reviewResponseSchema }),
  c.addResponse,
);
