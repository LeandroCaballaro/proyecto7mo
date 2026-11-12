import { LIKES_PER_POINT, type ReviewDto, type ReviewInput, type ReviewResponseDto, type ReviewResponseInput } from '@nexohub/shared';
import { prisma } from '../../lib/prisma';
import { AppError } from '../../utils/AppError';
import { toPublicUser } from '../users/users.mapper';

export const reviewsService = {
  async listByMovie(movieId: number, currentUserId?: number): Promise<ReviewDto[]> {
    const reviews = await prisma.review.findMany({
      where: { movieId },
      orderBy: [{ likesCount: 'desc' }, { createdAt: 'desc' }],
      include: {
        user: true,
        likes: currentUserId ? { where: { userId: currentUserId } } : false,
        responses: {
          take: 3,
          orderBy: { createdAt: 'asc' },
          include: { user: true },
        },
      },
    });

    return reviews.map((r) => ({
      id: r.id,
      rating: r.rating,
      content: r.content,
      imageUrl: r.imageUrl,
      likesCount: r.likesCount,
      hasLiked: currentUserId ? (r.likes?.length ?? 0) > 0 : false,
      createdAt: r.createdAt.toISOString(),
      user: toPublicUser(r.user),
      movieId: r.movieId,
      responses: r.responses.map((resp) => ({
        id: resp.id,
        content: resp.content,
        createdAt: resp.createdAt.toISOString(),
        user: toPublicUser(resp.user),
        parentId: resp.parentId,
      })),
    }));
  },

  async addOrUpdateReview(userId: number, movieId: number, input: ReviewInput): Promise<ReviewDto> {
    const movie = await prisma.movie.findUnique({
      where: { id: movieId },
      select: { id: true, status: true },
    });
    if (!movie || movie.status !== 'APPROVED') {
      throw AppError.notFound('Película no disponible para reseñar');
    }

    const review = await prisma.$transaction(async (tx) => {
      const saved = await tx.review.upsert({
        where: {
          userId_movieId: { userId, movieId },
        },
        create: {
          userId,
          movieId,
          rating: input.rating,
          content: input.content || null,
          imageUrl: input.imageUrl || null,
        },
        update: {
          rating: input.rating,
          content: input.content || null,
          imageUrl: input.imageUrl || null,
        },
        include: { user: true },
      });

      // Recalcular ratingAvg y reviewsCount de la película
      const aggregates = await tx.review.aggregate({
        where: { movieId },
        _avg: { rating: true },
        _count: { id: true },
      });

      await tx.movie.update({
        where: { id: movieId },
        data: {
          ratingAvg: aggregates._avg.rating ?? 0,
          reviewsCount: aggregates._count.id,
        },
      });

      return saved;
    });

    return {
      id: review.id,
      rating: review.rating,
      content: review.content,
      imageUrl: review.imageUrl,
      likesCount: review.likesCount,
      hasLiked: false,
      createdAt: review.createdAt.toISOString(),
      user: toPublicUser(review.user),
      movieId: review.movieId,
    };
  },

  async deleteReview(userId: number, reviewId: number, isStaffUser: boolean): Promise<void> {
    const review = await prisma.review.findUnique({
      where: { id: reviewId },
      include: { movie: true },
    });
    if (!review) throw AppError.notFound('Reseña no encontrada');

    if (review.userId !== userId && !isStaffUser) {
      throw AppError.forbidden('No puedes eliminar esta reseña');
    }

    await prisma.$transaction(async (tx) => {
      await tx.review.delete({ where: { id: reviewId } });

      // Recalcular métricas de la película
      const aggregates = await tx.review.aggregate({
        where: { movieId: review.movieId },
        _avg: { rating: true },
        _count: { id: true },
      });

      await tx.movie.update({
        where: { id: review.movieId },
        data: {
          ratingAvg: aggregates._avg.rating ?? 0,
          reviewsCount: aggregates._count.id,
        },
      });

      // Recalcular reputación del autor de la reseña eliminada
      const allLikes = await tx.reviewLike.count({
        where: {
          review: {
            userId: review.userId,
            movie: { genreId: review.movie.genreId },
          },
        },
      });

      const points = Math.floor(allLikes / LIKES_PER_POINT);

      await tx.genreReputation.upsert({
        where: {
          userId_genreId: { userId: review.userId, genreId: review.movie.genreId },
        },
        create: {
          userId: review.userId,
          genreId: review.movie.genreId,
          likesReceived: allLikes,
          points,
        },
        update: {
          likesReceived: allLikes,
          points,
        },
      });

      const totalRep = await tx.genreReputation.aggregate({
        where: { userId: review.userId },
        _sum: { points: true },
      });

      await tx.user.update({
        where: { id: review.userId },
        data: { reputation: totalRep._sum.points ?? 0 },
      });
    });
  },

  async toggleLike(userId: number, reviewId: number) {
    const review = await prisma.review.findUnique({
      where: { id: reviewId },
      include: { movie: true },
    });
    if (!review) throw AppError.notFound('Reseña no encontrada');

    const existingLike = await prisma.reviewLike.findUnique({
      where: {
        userId_reviewId: { userId, reviewId },
      },
    });

    const hasLiked = !existingLike;

    const updatedLikesCount = await prisma.$transaction(async (tx) => {
      if (existingLike) {
        await tx.reviewLike.delete({
          where: { userId_reviewId: { userId, reviewId } },
        });
      } else {
        await tx.reviewLike.create({
          data: { userId, reviewId },
        });
      }

      const count = await tx.reviewLike.count({ where: { reviewId } });

      await tx.review.update({
        where: { id: reviewId },
        data: { likesCount: count },
      });

      // Actualizar reputación del autor de la reseña
      const authorId = review.userId;
      const genreId = review.movie.genreId;

      const allLikesForAuthorInGenre = await tx.reviewLike.count({
        where: {
          review: {
            userId: authorId,
            movie: { genreId },
          },
        },
      });

      const points = Math.floor(allLikesForAuthorInGenre / LIKES_PER_POINT);

      await tx.genreReputation.upsert({
        where: { userId_genreId: { userId: authorId, genreId } },
        create: {
          userId: authorId,
          genreId,
          likesReceived: allLikesForAuthorInGenre,
          points,
        },
        update: {
          likesReceived: allLikesForAuthorInGenre,
          points,
        },
      });

      const totalRep = await tx.genreReputation.aggregate({
        where: { userId: authorId },
        _sum: { points: true },
      });

      await tx.user.update({
        where: { id: authorId },
        data: { reputation: totalRep._sum.points ?? 0 },
      });

      return count;
    });

    return {
      hasLiked,
      likesCount: updatedLikesCount,
    };
  },

  async addResponse(userId: number, reviewId: number, input: ReviewResponseInput): Promise<ReviewResponseDto> {
    const review = await prisma.review.findUnique({ where: { id: reviewId } });
    if (!review) throw AppError.notFound('Reseña no encontrada');

    if (input.parentId) {
      const parent = await prisma.reviewResponse.findUnique({
        where: { id: input.parentId },
      });
      if (!parent || parent.reviewId !== reviewId) {
        throw AppError.badRequest('Comentario padre no válido');
      }
    }

    const created = await prisma.reviewResponse.create({
      data: {
        reviewId,
        userId,
        content: input.content,
        parentId: input.parentId || null,
      },
      include: { user: true },
    });

    return {
      id: created.id,
      content: created.content,
      createdAt: created.createdAt.toISOString(),
      parentId: created.parentId,
      user: toPublicUser(created.user),
    };
  },

  async listResponses(reviewId: number): Promise<ReviewResponseDto[]> {
    const responses = await prisma.reviewResponse.findMany({
      where: { reviewId },
      orderBy: { createdAt: 'asc' },
      include: { user: true },
    });

    return responses.map((r) => ({
      id: r.id,
      content: r.content,
      createdAt: r.createdAt.toISOString(),
      parentId: r.parentId,
      user: toPublicUser(r.user),
    }));
  },
};

