import type { Request, Response } from 'express';
import { rankingService } from './ranking.service';

export const rankingController = {
  async index(req: Request, res: Response) {
    const genre = req.query.genre as string | undefined;
    const limit = req.query.limit ? Number(req.query.limit) : 20;

    const data = genre
      ? await rankingService.getByGenre(genre.toLowerCase(), limit)
      : await rankingService.getGlobal(limit);

    res.json({ data });
  },
};

