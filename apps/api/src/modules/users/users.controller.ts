import type { Request, Response } from 'express';
import { clearAuthCookies } from '../../lib/cookies';
import { usersService } from './users.service';

export const usersController = {
  async profile(req: Request, res: Response) {
    const username = req.params.username as string;
    const data = await usersService.getPublicProfile(username);
    res.json({ data });
  },

  async myDashboard(req: Request, res: Response) {
    const userId = req.auth!.userId;
    const data = await usersService.getMyProfileDashboard(userId);
    res.json({ data });
  },

  async updateMe(req: Request, res: Response) {
    const userId = req.auth!.userId;
    const user = await usersService.updateProfile(userId, req.body);
    res.json({ data: { user } });
  },

  async myFavorites(req: Request, res: Response) {
    const userId = req.auth!.userId;
    const data = await usersService.listFavorites(userId);
    res.json({ data });
  },

  async addFavorite(req: Request, res: Response) {
    const userId = req.auth!.userId;
    const movieId = Number(req.params.movieId);
    const data = await usersService.addFavorite(userId, movieId);
    res.json({ data });
  },

  async removeFavorite(req: Request, res: Response) {
    const userId = req.auth!.userId;
    const movieId = Number(req.params.movieId);
    const data = await usersService.removeFavorite(userId, movieId);
    res.json({ data });
  },

  async deleteMe(req: Request, res: Response) {
    const userId = req.auth!.userId;
    await usersService.deleteAccount(userId);
    clearAuthCookies(res);
    res.json({ data: { success: true, message: 'Cuenta eliminada exitosamente' } });
  },
};
