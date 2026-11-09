import type { Request, Response } from 'express';
import { isStaff } from '@nexohub/shared';
import { reviewsService } from './reviews.service';

export const reviewsController = {
  async listByMovie(req: Request, res: Response) {
    const movieId = Number(req.params.movieId);
    const currentUserId = req.auth?.userId;
    const data = await reviewsService.listByMovie(movieId, currentUserId);
    res.json({ data });
  },

  async addOrUpdate(req: Request, res: Response) {
    const movieId = Number(req.params.movieId);
    const userId = req.auth!.userId;
    const data = await reviewsService.addOrUpdateReview(userId, movieId, req.body);
    res.status(201).json({ data });
  },

  async remove(req: Request, res: Response) {
    const reviewId = Number(req.params.id);
    const userId = req.auth!.userId;
    const isStaffUser = isStaff(req.auth!.role);
    await reviewsService.deleteReview(userId, reviewId, isStaffUser);
    res.status(204).end();
  },

  async toggleLike(req: Request, res: Response) {
    const reviewId = Number(req.params.id);
    const userId = req.auth!.userId;
    const data = await reviewsService.toggleLike(userId, reviewId);
    res.json({ data });
  },

  async addResponse(req: Request, res: Response) {
    const reviewId = Number(req.params.id);
    const userId = req.auth!.userId;
    const data = await reviewsService.addResponse(userId, reviewId, req.body);
    res.status(201).json({ data });
  },

  async listResponses(req: Request, res: Response) {
    const reviewId = Number(req.params.id);
    const data = await reviewsService.listResponses(reviewId);
    res.json({ data });
  },
};

