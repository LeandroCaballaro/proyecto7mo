import { Router } from 'express';
import { movieIdParamSchema, updateProfileSchema } from '@nexohub/shared';
import { authenticate } from '../../middlewares/auth';
import { validate } from '../../middlewares/validate';
import { usersController as c } from './users.controller';

export const usersRouter = Router();

usersRouter.get('/profile/:username', c.profile);

usersRouter.get('/me/dashboard', authenticate, c.myDashboard);
usersRouter.patch('/me', authenticate, validate({ body: updateProfileSchema }), c.updateMe);
usersRouter.get('/me/favorites', authenticate, c.myFavorites);
usersRouter.put(
  '/me/favorites/:movieId',
  authenticate,
  validate({ params: movieIdParamSchema }),
  c.addFavorite,
);
usersRouter.delete(
  '/me/favorites/:movieId',
  authenticate,
  validate({ params: movieIdParamSchema }),
  c.removeFavorite,
);
usersRouter.delete('/me', authenticate, c.deleteMe);
