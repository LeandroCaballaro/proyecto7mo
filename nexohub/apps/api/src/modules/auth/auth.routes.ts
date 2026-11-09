import { Router } from 'express';
import {
  changePasswordSchema,
  forgotPasswordSchema,
  loginSchema,
  registerSchema,
  resetPasswordSchema,
} from '@nexohub/shared';
import { authenticate } from '../../middlewares/auth';
import {
  loginLimiter,
  passwordResetLimiter,
  refreshLimiter,
  registerLimiter,
} from '../../middlewares/rateLimiter';
import { validate } from '../../middlewares/validate';
import { authController as c } from './auth.controller';

export const authRouter = Router();

authRouter.post('/register', registerLimiter, validate({ body: registerSchema }), c.register);
authRouter.post('/login', validate({ body: loginSchema }), loginLimiter, c.login);
authRouter.post('/refresh', refreshLimiter, c.refresh);
authRouter.post('/logout', c.logout);
authRouter.post('/logout-all', authenticate, c.logoutAll);
authRouter.get('/me', authenticate, c.me);

authRouter.post(
  '/forgot-password',
  passwordResetLimiter,
  validate({ body: forgotPasswordSchema }),
  c.forgotPassword,
);
authRouter.post(
  '/reset-password',
  passwordResetLimiter,
  validate({ body: resetPasswordSchema }),
  c.resetPassword,
);
authRouter.post(
  '/change-password',
  authenticate,
  validate({ body: changePasswordSchema }),
  c.changePassword,
);

