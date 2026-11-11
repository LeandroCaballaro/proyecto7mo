import type { MovieDto, UpdateProfileInput } from '@nexohub/shared';
import { prisma } from '../../lib/prisma';
import { AppError } from '../../utils/AppError';
import { toAuthUser, toPublicUser } from './users.mapper';

export const usersService = {
  async getPublicProfile(username: string) {
    const user = await prisma.user.findUnique({
      where: { username },
      include: {
        genreReputation: {
          where: { points: { gt: 0 } },
          orderBy: { points: 'desc' },
          include: { genre: true },
        },
      },
    });

    if (!user) throw AppError.notFound('Usuario no encontrado');

    const publicUser = toPublicUser(user);

    let favorites: MovieDto[] = [];
    if (user.isPublic) {
      const favRows = await prisma.favoriteMovie.findMany({
        where: { userId: user.id },
        include: {
          movie: {
            include: { genre: true },
          },
        },
        orderBy: { createdAt: 'desc' },
      });

      favorites = favRows.map((f) => ({
        id: f.movie.id,
        title: f.movie.title,
        synopsis: f.movie.synopsis,
        director: f.movie.director,
        releaseYear: f.movie.releaseYear,
        posterUrl: f.movie.posterUrl,
        featured: f.movie.featured,
        status: f.movie.status,
        rejectionReason: f.movie.rejectionReason,
        ratingAvg: Number(f.movie.ratingAvg),
        reviewsCount: f.movie.reviewsCount,
        genre: {
          id: f.movie.genre.id,
          name: f.movie.genre.name,
          slug: f.movie.genre.slug,
        },
        isFavorite: true,
        createdAt: f.movie.createdAt.toISOString(),
      }));
    }

    return {
      user: publicUser,
      genreBadges: user.genreReputation.map((gr) => ({
        genre: gr.genre.name,
        slug: gr.genre.slug,
        points: gr.points,
      })),
      favorites: user.isPublic ? favorites : [],
      isPrivate: !user.isPublic,
    };
  },

  async getMyProfileDashboard(userId: number) {
    const user = await prisma.user.findUnique({
      where: { id: userId },
    });
    if (!user) throw AppError.notFound('Usuario no encontrado');

    const [reviewsCount, responsesCount, reviews, favorites, reviewResponses] = await Promise.all([
      prisma.review.count({ where: { userId } }),
      prisma.reviewResponse.count({ where: { userId } }),
      prisma.review.findMany({
        where: { userId },
        include: {
          movie: { select: { id: true, title: true, posterUrl: true } },
        },
        orderBy: { createdAt: 'desc' },
      }),
      prisma.favoriteMovie.findMany({
        where: { userId },
        include: {
          movie: { select: { id: true, title: true, posterUrl: true, ratingAvg: true } },
        },
        orderBy: { createdAt: 'desc' },
      }),
      prisma.reviewResponse.findMany({
        where: { userId },
        include: {
          review: {
            include: {
              movie: { select: { id: true, title: true } },
            },
          },
        },
        orderBy: { createdAt: 'desc' },
        take: 15,
      }),
    ]);

    const activity = [
      ...reviews.map((r) => ({
        id: `rev-${r.id}`,
        type: 'review' as const,
        movieId: r.movie.id,
        movieTitle: r.movie.title,
        comment: r.content,
        rating: r.rating,
        createdAt: r.createdAt.toISOString(),
      })),
      ...reviewResponses.map((rr) => ({
        id: `resp-${rr.id}`,
        type: 'response' as const,
        movieId: rr.review.movie.id,
        movieTitle: rr.review.movie.title,
        comment: rr.content,
        createdAt: rr.createdAt.toISOString(),
      })),
    ].sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime());

    return {
      user: toAuthUser(user),
      commentsCount: reviewsCount + responsesCount,
      reviewsCount,
      reputation: user.reputation,
      reviews: reviews.map((r) => ({
        id: r.id,
        movieId: r.movie.id,
        title: r.movie.title,
        posterUrl: r.movie.posterUrl,
        rating: r.rating,
        content: r.content,
        createdAt: r.createdAt.toISOString(),
      })),
      favorites: favorites.map((f) => ({
        id: f.movie.id,
        title: f.movie.title,
        posterUrl: f.movie.posterUrl,
        ratingAvg: Number(f.movie.ratingAvg),
      })),
      activity,
    };
  },

  async updateProfile(userId: number, input: UpdateProfileInput) {
    if (input.username) {
      const taken = await prisma.user.findFirst({
        where: {
          username: input.username,
          id: { not: userId },
        },
      });
      if (taken) {
        throw AppError.conflict('El nombre de usuario ya está en uso');
      }
    }

    const updated = await prisma.user.update({
      where: { id: userId },
      data: {
        ...(input.name ? { name: input.name } : {}),
        ...(input.username ? { username: input.username } : {}),
        ...(input.bio !== undefined ? { bio: input.bio || null } : {}),
        ...(input.avatarUrl !== undefined ? { avatarUrl: input.avatarUrl || null } : {}),
        ...(input.isPublic !== undefined ? { isPublic: input.isPublic } : {}),
      },
    });

    return toAuthUser(updated);
  },

  async listFavorites(userId: number): Promise<MovieDto[]> {
    const favs = await prisma.favoriteMovie.findMany({
      where: { userId },
      include: {
        movie: {
          include: { genre: true },
        },
      },
      orderBy: { createdAt: 'desc' },
    });

    return favs.map((f) => ({
      id: f.movie.id,
      title: f.movie.title,
      synopsis: f.movie.synopsis,
      director: f.movie.director,
      releaseYear: f.movie.releaseYear,
      posterUrl: f.movie.posterUrl,
      featured: f.movie.featured,
      status: f.movie.status,
      rejectionReason: f.movie.rejectionReason,
      ratingAvg: Number(f.movie.ratingAvg),
      reviewsCount: f.movie.reviewsCount,
      genre: {
        id: f.movie.genre.id,
        name: f.movie.genre.name,
        slug: f.movie.genre.slug,
      },
      isFavorite: true,
      createdAt: f.movie.createdAt.toISOString(),
    }));
  },

  async addFavorite(userId: number, movieId: number) {
    const movie = await prisma.movie.findUnique({
      where: { id: movieId },
      select: { id: true, status: true },
    });
    if (!movie || movie.status !== 'APPROVED') {
      throw AppError.notFound('Película no disponible');
    }

    await prisma.favoriteMovie.upsert({
      where: { userId_movieId: { userId, movieId } },
      create: { userId, movieId },
      update: {},
    });

    return { isFavorite: true };
  },

  async removeFavorite(userId: number, movieId: number) {
    await prisma.favoriteMovie.deleteMany({
      where: { userId, movieId },
    });

    return { isFavorite: false };
  },

  async deleteAccount(userId: number) {
    await prisma.user.delete({
      where: { id: userId },
    });
  },
};
