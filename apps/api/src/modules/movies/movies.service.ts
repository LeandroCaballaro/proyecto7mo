import type { Prisma } from '@prisma/client';
import {
  isStaff,
  type CreateMovieInput,
  type MovieDto,
  type MovieQueryInput,
  type Role,
} from '@nexohub/shared';
import { prisma } from '../../lib/prisma';
import { AppError } from '../../utils/AppError';
import { toPublicUser } from '../users/users.mapper';
import { externalMovies, storageKeyOf } from './external.service';
import { toMovieDto } from './movies.mapper';

const favoritesOf = (userId?: number) => (userId ? { where: { userId } } : false);

export interface Viewer {
  userId?: number;
  role?: Role;
}

export const moviesService = {
  async listMovies(query: MovieQueryInput, currentUserId?: number) {
    const { page, pageSize, q, genre, sort } = query;

    const where: Prisma.MovieWhereInput = {
      status: 'APPROVED',
      ...(genre ? { genre: { slug: genre } } : {}),
      ...(q
        ? {
            OR: [
              { title: { contains: q } },
              { director: { contains: q } },
              { synopsis: { contains: q } },
            ],
          }
        : {}),
    };

    const orderBy: Prisma.MovieOrderByWithRelationInput[] =
      sort === 'rating'
        ? [{ ratingAvg: 'desc' }, { reviewsCount: 'desc' }]
        : sort === 'reviews'
          ? [{ reviewsCount: 'desc' }, { ratingAvg: 'desc' }]
          : sort === 'year'
            ? [{ releaseYear: 'desc' }, { id: 'desc' }]
            : [{ createdAt: 'desc' }, { id: 'desc' }];

    const [total, movies] = await Promise.all([
      prisma.movie.count({ where }),
      prisma.movie.findMany({
        where,
        skip: (page - 1) * pageSize,
        take: pageSize,
        orderBy,
        include: { genre: true, proposedBy: true, favorites: favoritesOf(currentUserId) },
      }),
    ]);

    return {
      data: movies.map((m) => toMovieDto(m)),
      meta: { page, pageSize, total, totalPages: Math.ceil(total / pageSize) },
    };
  },

  async getFeatured(limit: number, currentUserId?: number): Promise<MovieDto[]> {
    const movies = await prisma.movie.findMany({
      where: { status: 'APPROVED' },
      take: limit,
      orderBy: [
        { featured: 'desc' },
        { ratingAvg: 'desc' },
        { reviewsCount: 'desc' },
        { releaseYear: 'desc' },
      ],
      include: { genre: true, proposedBy: true, favorites: favoritesOf(currentUserId) },
    });
    return movies.map((m) => toMovieDto(m));
  },

  async getById(id: number, viewer: Viewer): Promise<MovieDto> {
    const [m, userReview] = await Promise.all([
      prisma.movie.findUnique({
        where: { id },
        include: { genre: true, proposedBy: true, favorites: favoritesOf(viewer.userId) },
      }),
      viewer.userId
        ? prisma.review.findUnique({
            where: { userId_movieId: { userId: viewer.userId, movieId: id } },
            include: { user: true },
          })
        : null,
    ]);

    // Las películas pendientes o rechazadas solo las ven el staff y quien las propuso.
    const canSee =
      m &&
      (m.status === 'APPROVED' ||
        isStaff(viewer.role) ||
        (viewer.userId != null && m.proposedById === viewer.userId));
    if (!m || !canSee) throw AppError.notFound('Película no encontrada');

    return {
      ...toMovieDto(m),
      userReview: userReview
        ? {
            id: userReview.id,
            rating: userReview.rating,
            content: userReview.content,
            imageUrl: userReview.imageUrl,
            likesCount: userReview.likesCount,
            createdAt: userReview.createdAt.toISOString(),
            user: toPublicUser(userReview.user),
          }
        : null,
    };
  },

  async createMovie(input: CreateMovieInput, userId: number, isStaffUser: boolean) {
    // Con id externo, los datos se vuelven a pedir a la fuente: el cliente no puede falsearlos.
    const verified = input.externalId ? await externalMovies.getMeta(input.externalId) : null;
    const key = input.externalId ? storageKeyOf(input.externalId) : null;

    const genreSlug = input.genreSlug || verified?.genreSlug || 'drama';
    const genre = await prisma.genre.findUnique({ where: { slug: genreSlug } });
    if (!genre) throw AppError.badRequest('El género especificado no existe');

    const title = verified?.title ?? input.title;
    const releaseYear = verified?.releaseYear ?? input.releaseYear ?? null;

    let movie = key
      ? await prisma.movie.findUnique({
          where: { externalSource_externalId: key },
          include: { genre: true },
        })
      : null;
    movie ??= await prisma.movie.findFirst({
      where: { title, ...(releaseYear ? { releaseYear } : {}) },
      include: { genre: true },
    });

    if (movie?.status === 'REJECTED' && !isStaffUser) {
      throw AppError.conflict('Esta película ya fue revisada y rechazada por la moderación');
    }

    const created = !movie;
    if (!movie) {
      movie = await prisma.movie.create({
        data: {
          title,
          genreId: genre.id,
          synopsis: verified?.synopsis ?? input.synopsis ?? null,
          synopsisLang: verified ? verified.synopsisLang : input.synopsis ? 'es' : null,
          director: verified?.director ?? input.director ?? null,
          releaseYear,
          posterUrl: verified?.posterUrl ?? (input.posterUrl || null),
          status: isStaffUser || verified ? 'APPROVED' : 'PENDING',
          proposedById: userId,
          ...(key ?? {}),
        },
        include: { genre: true },
      });
    }

    const initialReview = input.initialReview;
    if (initialReview && movie.status === 'APPROVED') {
      const movieId = movie.id;
      await prisma.$transaction(async (tx) => {
        const exists = await tx.review.findUnique({
          where: { userId_movieId: { userId, movieId } },
        });
        if (exists) return;
        await tx.review.create({
          data: { userId, movieId, rating: initialReview.rating, content: initialReview.content },
        });
        const agg = await tx.review.aggregate({
          where: { movieId },
          _avg: { rating: true },
          _count: { id: true },
        });
        await tx.movie.update({
          where: { id: movieId },
          data: { ratingAvg: agg._avg.rating ?? 0, reviewsCount: agg._count.id },
        });
      });
    }

    const message =
      movie.status === 'APPROVED'
        ? created
          ? 'Película verificada y publicada en el catálogo.'
          : 'La película ya estaba en el catálogo.'
        : 'Película enviada a revisión. Un moderador la evaluará pronto.';

    return {
      movie: {
        id: movie.id,
        title: movie.title,
        status: movie.status,
        genre: { id: movie.genre.id, name: movie.genre.name, slug: movie.genre.slug },
      },
      created,
      message,
    };
  },

  searchExternal: externalMovies.search,
  getExternalMeta: externalMovies.getMeta,

  async getGenres() {
    const genres = await prisma.genre.findMany({
      orderBy: { name: 'asc' },
      select: {
        id: true,
        name: true,
        slug: true,
        _count: { select: { movies: { where: { status: 'APPROVED' } } } },
        movies: {
          where: { status: 'APPROVED', posterUrl: { not: null } },
          orderBy: [
            { featured: 'desc' },
            { reviewsCount: 'desc' },
            { ratingAvg: 'desc' },
            { releaseYear: 'desc' },
          ],
          take: 1,
          select: { id: true, title: true, posterUrl: true },
        },
      },
    });
    return genres.map((g) => ({
      id: g.id,
      name: g.name,
      slug: g.slug,
      moviesCount: g._count.movies,
      featuredMovie: g.movies[0] ?? null,
    }));
  },
};
