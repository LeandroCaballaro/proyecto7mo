import type { Request, Response } from 'express';
import type { ApiSuccess, AuthUser } from '@nexohub/shared';
import { REFRESH_COOKIE, clearAuthCookies, setAuthCookies } from '../../lib/cookies';
import { AppError } from '../../utils/AppError';
import { toAuthUser } from '../users/users.mapper';
import { authService, type ClientMeta, type Session } from './auth.service';

const metaOf = (req: Request): ClientMeta => ({
  ip: req.ip,
  userAgent: req.get('user-agent'),
});

const refreshTokenOf = (req: Request): string | undefined =>
  req.cookies?.[REFRESH_COOKIE] as string | undefined;

function respondWithSession(res: Response, session: Session, status = 200): void {
  setAuthCookies(res, session.accessToken, session.refreshToken);
  const body: ApiSuccess<{ user: AuthUser }> = { data: { user: toAuthUser(session.user) } };
  res.status(status).json(body);
}

const userId = (req: Request): number => {
  if (!req.auth) throw AppError.unauthenticated();
  return req.auth.userId;
};

/** Controllers finos: traducen HTTP <-> service. Express 5 propaga los errores async. */
export const authController = {
  async register(req: Request, res: Response) {
    respondWithSession(res, await authService.register(req.body, metaOf(req)), 201);
  },

  async login(req: Request, res: Response) {
    respondWithSession(res, await authService.login(req.body, metaOf(req)));
  },

  async refresh(req: Request, res: Response) {
    try {
      respondWithSession(res, await authService.refresh(refreshTokenOf(req), metaOf(req)));
    } catch (err) {
      // Si el refresh falla las cookies ya no sirven, salvo en una carrera entre pestañas:
      // ahí el navegador ya tiene las cookies nuevas y no hay que pisarlas.
      if (!(err instanceof AppError && err.code === 'REFRESH_RACE')) clearAuthCookies(res);
      throw err;
    }
  },

  async logout(req: Request, res: Response) {
    await authService.logout(refreshTokenOf(req));
    clearAuthCookies(res);
    res.status(204).end();
  },

  async logoutAll(req: Request, res: Response) {
    await authService.logoutAll(userId(req));
    clearAuthCookies(res);
    res.status(204).end();
  },

  async me(req: Request, res: Response) {
    const user = await authService.me(userId(req));
    const body: ApiSuccess<{ user: AuthUser }> = { data: { user: toAuthUser(user) } };
    res.json(body);
  },

  async forgotPassword(req: Request, res: Response) {
    await authService.forgotPassword(req.body.email);
    res.json({
      data: { message: 'Si el email está registrado, te enviamos un enlace para restablecer la contraseña.' },
    });
  },

  async resetPassword(req: Request, res: Response) {
    await authService.resetPassword(req.body);
    res.json({ data: { message: 'Contraseña actualizada. Ya podés iniciar sesión.' } });
  },

  async changePassword(req: Request, res: Response) {
    respondWithSession(res, await authService.changePassword(userId(req), req.body, metaOf(req)));
  },
};
