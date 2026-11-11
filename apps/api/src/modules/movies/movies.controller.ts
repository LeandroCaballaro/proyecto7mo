import type { Request, Response } from 'express';
import { isStaff } from '@nexohub/shared';
import { moviesService } from './movies.service';

export const moviesController = {
  async list(req: Request, res: Response) {
    const currentUserId = req.auth?.userId;
    const result = await moviesService.listMovies(req.query as any, currentUserId);
    res.json(result);
  },

  async featured(req: Request, res: Response) {
    const currentUserId = req.auth?.userId;
    const limit = req.query.limit ? Number(req.query.limit) : 10;
    const data = await moviesService.getFeatured(limit, currentUserId);
    res.json({ data });
  },

  async searchExternal(req: Request, res: Response) {
    const q = String(req.query.q || '');
    const data = await moviesService.searchExternal(q);
    res.json({ data });
  },

  async getExternalMeta(req: Request, res: Response) {
    const imdbId = String(req.params.imdbId || '');
    const data = await moviesService.getExternalMeta(imdbId);
    res.json({ data });
  },

  async show(req: Request, res: Response) {
    const id = Number(req.params.id);
    const currentUserId = req.auth?.userId;
    const movie = await moviesService.getById(id, currentUserId);
    res.json({ data: movie });
  },

  async create(req: Request, res: Response) {
    const userId = req.auth!.userId;
    const staff = isStaff(req.auth!.role);
    const result = await moviesService.createMovie(req.body, userId, staff);
    res.status(201).json({ data: result });
  },

  async genres(_req: Request, res: Response) {
    const data = await moviesService.getGenres();
    res.json({ data });
  },
};
