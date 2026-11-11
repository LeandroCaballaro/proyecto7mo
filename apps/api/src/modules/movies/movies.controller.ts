import type { Request, Response } from 'express';
import { isStaff, type CreateMovieInput, type MovieQueryInput } from '@nexohub/shared';
import { moviesService } from './movies.service';

const FEATURED_MAX = 30;

export const moviesController = {
  async list(req: Request, res: Response) {
    const result = await moviesService.listMovies(
      req.query as unknown as MovieQueryInput,
      req.auth?.userId,
    );
    res.json(result);
  },

  async featured(req: Request, res: Response) {
    const raw = Number(req.query.limit);
    const limit = Number.isInteger(raw) && raw > 0 ? Math.min(raw, FEATURED_MAX) : 10;
    const data = await moviesService.getFeatured(limit, req.auth?.userId);
    res.json({ data });
  },

  async searchExternal(req: Request, res: Response) {
    const data = await moviesService.searchExternal(String(req.query.q ?? '').slice(0, 100));
    res.json({ data });
  },

  async getExternalMeta(req: Request, res: Response) {
    const data = await moviesService.getExternalMeta(req.params.externalId as string);
    res.json({ data });
  },

  async show(req: Request, res: Response) {
    const id = (req.params as unknown as { id: number }).id;
    const data = await moviesService.getById(id, {
      userId: req.auth?.userId,
      role: req.auth?.role,
    });
    res.json({ data });
  },

  async create(req: Request, res: Response) {
    const userId = req.auth!.userId;
    const result = await moviesService.createMovie(
      req.body as CreateMovieInput,
      userId,
      isStaff(req.auth!.role),
    );
    res.status(result.created ? 201 : 200).json({ data: result });
  },

  async genres(_req: Request, res: Response) {
    res.json({ data: await moviesService.getGenres() });
  },
};
