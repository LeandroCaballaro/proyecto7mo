import type { Prisma } from '@prisma/client';
import type { CreateMovieInput, MovieDto, MovieQueryInput } from '@nexohub/shared';
import { prisma } from '../../lib/prisma';
import { AppError } from '../../utils/AppError';
import { toPublicUser } from '../users/users.mapper';

const EXTERNAL_GENRE_MAP: Record<string, string> = {
  Action: 'accion',
  Adventure: 'aventura',
  Animation: 'animacion',
  Comedy: 'comedia',
  Crime: 'crimen',
  Documentary: 'documental',
  Drama: 'drama',
  Fantasy: 'fantasia',
  Horror: 'terror',
  Mystery: 'misterio',
  Romance: 'romance',
  'Sci-Fi': 'ciencia-ficcion',
  'Science Fiction': 'ciencia-ficcion',
  Thriller: 'thriller',
};

export const moviesService = {
  async listMovies(query: MovieQueryInput, currentUserId?: number) {
    const { page, pageSize, q, genre, sort } = query;
    const skip = (page - 1) * pageSize;

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

    let orderBy: Prisma.MovieOrderByWithRelationInput = { createdAt: 'desc' };
    if (sort === 'rating') {
      orderBy = { ratingAvg: 'desc' };
    } else if (sort === 'reviews') {
      orderBy = { reviewsCount: 'desc' };
    } else if (sort === 'year') {
      orderBy = { releaseYear: 'desc' };
    }

    const [total, movies] = await Promise.all([
      prisma.movie.count({ where }),
      prisma.movie.findMany({
        where,
        skip,
        take: pageSize,
        orderBy,
        include: {
          genre: true,
          proposedBy: true,
          favorites: currentUserId ? { where: { userId: currentUserId } } : false,
        },
      }),
    ]);

    const data: MovieDto[] = movies.map((m) => ({
      id: m.id,
      title: m.title,
      synopsis: m.synopsis,
      director: m.director,
      releaseYear: m.releaseYear,
      posterUrl: m.posterUrl,
      featured: m.featured,
      status: m.status,
      rejectionReason: m.rejectionReason,
      ratingAvg: Number(m.ratingAvg),
      reviewsCount: m.reviewsCount,
      genre: {
        id: m.genre.id,
        name: m.genre.name,
        slug: m.genre.slug,
      },
      proposedBy: m.proposedBy ? toPublicUser(m.proposedBy) : null,
      isFavorite: currentUserId ? (m.favorites?.length ?? 0) > 0 : false,
      createdAt: m.createdAt.toISOString(),
    }));

    return {
      data,
      meta: {
        page,
        pageSize,
        total,
        totalPages: Math.ceil(total / pageSize),
      },
    };
  },

  async getFeatured(limit = 10, currentUserId?: number) {
    const movies = await prisma.movie.findMany({
      where: { status: 'APPROVED' },
      take: limit,
      orderBy: [{ featured: 'desc' }, { ratingAvg: 'desc' }, { reviewsCount: 'desc' }],
      include: {
        genre: true,
        proposedBy: true,
        favorites: currentUserId ? { where: { userId: currentUserId } } : false,
      },
    });

    return movies.map((m) => ({
      id: m.id,
      title: m.title,
      synopsis: m.synopsis,
      director: m.director,
      releaseYear: m.releaseYear,
      posterUrl: m.posterUrl,
      featured: m.featured,
      status: m.status,
      rejectionReason: m.rejectionReason,
      ratingAvg: Number(m.ratingAvg),
      reviewsCount: m.reviewsCount,
      genre: {
        id: m.genre.id,
        name: m.genre.name,
        slug: m.genre.slug,
      },
      proposedBy: m.proposedBy ? toPublicUser(m.proposedBy) : null,
      isFavorite: currentUserId ? (m.favorites?.length ?? 0) > 0 : false,
      createdAt: m.createdAt.toISOString(),
    }));
  },

  async getById(id: number, currentUserId?: number) {
    const [m, userReview] = await Promise.all([
      prisma.movie.findUnique({
        where: { id },
        include: {
          genre: true,
          proposedBy: true,
          favorites: currentUserId ? { where: { userId: currentUserId } } : false,
        },
      }),
      currentUserId
        ? prisma.review.findUnique({
            where: { userId_movieId: { userId: currentUserId, movieId: id } },
            include: { user: true },
          })
        : null,
    ]);

    if (!m) throw AppError.notFound('Película no encontrada');

    const dto: MovieDto = {
      id: m.id,
      title: m.title,
      synopsis: m.synopsis,
      director: m.director,
      releaseYear: m.releaseYear,
      posterUrl: m.posterUrl,
      featured: m.featured,
      status: m.status,
      rejectionReason: m.rejectionReason,
      ratingAvg: Number(m.ratingAvg),
      reviewsCount: m.reviewsCount,
      genre: {
        id: m.genre.id,
        name: m.genre.name,
        slug: m.genre.slug,
      },
      proposedBy: m.proposedBy ? toPublicUser(m.proposedBy) : null,
      isFavorite: currentUserId ? (m.favorites?.length ?? 0) > 0 : false,
      userReview: userReview
        ? {
            id: userReview.id,
            rating: userReview.rating,
            content: userReview.content,
            imageUrl: userReview.imageUrl,
            likesCount: 0,
            createdAt: userReview.createdAt.toISOString(),
            user: toPublicUser(userReview.user),
          }
        : null,
      createdAt: m.createdAt.toISOString(),
    };

    return dto;
  },

  async createMovie(input: CreateMovieInput, userId: number, isStaffUser: boolean) {
    const genre = await prisma.genre.findUnique({
      where: { slug: input.genreSlug },
    });
    if (!genre) throw AppError.badRequest('El género especificado no existe');

    // Películas verificadas mediante IMDb o creadas por staff se aprueban automáticamente
    const isVerified = Boolean((input as any).imdbId);
    const status = isStaffUser || isVerified ? 'APPROVED' : 'PENDING';

    // Buscar si ya existe la película en el catálogo
    let movie = await prisma.movie.findFirst({
      where: {
        title: { equals: input.title },
        ...(input.releaseYear ? { releaseYear: input.releaseYear } : {}),
      },
      include: {
        genre: true,
        proposedBy: true,
      },
    });

    if (!movie) {
      movie = await prisma.movie.create({
        data: {
          title: input.title,
          genreId: genre.id,
          synopsis: input.synopsis || null,
          director: input.director || null,
          releaseYear: input.releaseYear || null,
          posterUrl: input.posterUrl || null,
          status,
          proposedById: userId,
        },
        include: {
          genre: true,
          proposedBy: true,
        },
      });
    }

    // Si el usuario proporcionó una reseña inicial al proponer la película
    const initialReview = (input as any).initialReview;
    if (initialReview && initialReview.content && initialReview.rating) {
      const existingReview = await prisma.review.findUnique({
        where: {
          userId_movieId: { userId, movieId: movie.id },
        },
      });

      if (!existingReview) {
        await prisma.$transaction(async (tx) => {
          await tx.review.create({
            data: {
              userId,
              movieId: movie.id,
              rating: initialReview.rating,
              content: initialReview.content,
            },
          });

          const agg = await tx.review.aggregate({
            where: { movieId: movie.id },
            _avg: { rating: true },
            _count: { id: true },
          });

          await tx.movie.update({
            where: { id: movie.id },
            data: {
              ratingAvg: agg._avg.rating || initialReview.rating,
              reviewsCount: agg._count.id || 1,
            },
          });
        });
      }
    }

    return {
      movie: {
        id: movie.id,
        title: movie.title,
        status: movie.status,
        genre: { id: genre.id, name: genre.name, slug: genre.slug },
      },
      message:
        status === 'APPROVED'
          ? 'Película verificada y publicada en el catálogo.'
          : 'Película enviada a revisión. Un moderador la evaluará pronto.',
    };
  },

  async searchExternal(query: string) {
    const q = query.trim();
    if (!q) return [];

    try {
      const url = `https://v3-cinemeta.strem.io/catalog/movie/top/search=${encodeURIComponent(q)}.json`;
      const res = await fetch(url, { headers: { 'User-Agent': 'NexoHub/2.0' } });
      const data: any = await res.json();
      const metas = Array.isArray(data?.metas) ? data.metas : [];

      return metas.slice(0, 12).map((m: any) => ({
        imdbId: m.id,
        title: m.name,
        releaseYear: m.releaseInfo ? parseInt(m.releaseInfo, 10) : null,
        posterUrl: m.poster || null,
        description: m.description || null,
      }));
    } catch (err) {
      console.error('Error buscando en Cinemeta:', err);
      return [];
    }
  },

  async getExternalMeta(imdbId: string) {
    const id = imdbId.trim();
    if (!id.startsWith('tt')) {
      throw AppError.badRequest('ID de IMDb no válido');
    }

    try {
      const url = `https://v3-cinemeta.strem.io/meta/movie/${encodeURIComponent(id)}.json`;
      const res = await fetch(url, { headers: { 'User-Agent': 'NexoHub/2.0' } });
      const data: any = await res.json();
      const m = data?.meta;
      if (!m) {
        throw AppError.notFound('Película no encontrada en la base de datos de IMDb');
      }

      const director = Array.isArray(m.director)
        ? m.director.join(', ')
        : typeof m.director === 'string'
          ? m.director
          : null;

      const genreList: string[] = Array.isArray(m.genres) ? m.genres : [];
      const primaryEnglishGenre = genreList[0] || 'Drama';
      const mappedGenreSlug = EXTERNAL_GENRE_MAP[primaryEnglishGenre] || 'drama';

      return {
        imdbId: m.id,
        title: m.name,
        releaseYear: m.year
          ? parseInt(m.year, 10)
          : m.releaseInfo
            ? parseInt(m.releaseInfo, 10)
            : null,
        director,
        genres: genreList,
        genreSlug: mappedGenreSlug,
        synopsis: m.description || null,
        posterUrl: m.poster || null,
        imdbRating: m.imdbRating || null,
      };
    } catch (err: any) {
      if (err instanceof AppError) throw err;
      throw AppError.badRequest('No se pudieron obtener los datos de IMDb');
    }
  },

  async getGenres() {
    const genres = await prisma.genre.findMany({
      orderBy: { name: 'asc' },
      select: {
        id: true,
        name: true,
        slug: true,
        _count: {
          select: {
            movies: {
              where: { status: 'APPROVED' },
            },
          },
        },
      },
    });

    return genres.map((g) => ({
      id: g.id,
      name: g.name,
      slug: g.slug,
      moviesCount: g._count.movies,
    }));
  },
};
