import type { Request, Response } from 'express';
import { adminService } from './admin.service';

export const adminController = {
  async pendingMovies(_req: Request, res: Response) {
    const data = await adminService.listPendingMovies();
    res.json({ data });
  },

  async approveMovie(req: Request, res: Response) {
    const movieId = Number(req.params.id);
    const moderatorId = req.auth!.userId;
    const data = await adminService.approveMovie(movieId, moderatorId);
    res.json({ data });
  },

  async rejectMovie(req: Request, res: Response) {
    const movieId = Number(req.params.id);
    const moderatorId = req.auth!.userId;
    const { reason } = req.body;
    const data = await adminService.rejectMovie(movieId, moderatorId, reason);
    res.json({ data });
  },

  async users(_req: Request, res: Response) {
    const data = await adminService.listUsers();
    res.json({ data });
  },

  async updateUserRole(req: Request, res: Response) {
    const targetUserId = Number(req.params.id);
    const actorUserId = req.auth!.userId;
    const { role } = req.body;
    const user = await adminService.updateUserRole(targetUserId, role, actorUserId);
    res.json({ data: { user } });
  },
};

