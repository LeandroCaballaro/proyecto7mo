import { Router } from 'express';
import { rejectMovieSchema, updateUserRoleSchema } from '@nexohub/shared';
import { authenticate, requireRole } from '../../middlewares/auth';
import { validate } from '../../middlewares/validate';
import { adminController as c } from './admin.controller';

export const adminRouter = Router();

// Todas las rutas de administración requieren al menos rol ADMIN
adminRouter.use(authenticate, requireRole('ADMIN', 'SUPERADMIN'));

adminRouter.get('/movies/pending', c.pendingMovies);
adminRouter.patch('/movies/:id/approve', c.approveMovie);
adminRouter.patch('/movies/:id/reject', validate({ body: rejectMovieSchema }), c.rejectMovie);

adminRouter.get('/users', c.users);
// Exclusivo para SUPERADMIN:
adminRouter.patch(
  '/users/:id/role',
  requireRole('SUPERADMIN'),
  validate({ body: updateUserRoleSchema }),
  c.updateUserRole,
);

