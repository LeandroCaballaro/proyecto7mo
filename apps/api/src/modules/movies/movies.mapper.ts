import type { Genre, Movie, User } from '@prisma/client';
import type { MovieDto } from '@nexohub/shared';
import { toPublicUser } from '../users/users.mapper';

type MovieWithRelations = Movie & {
  genre: Genre;
  proposedBy?: User | null;
  favorites?: unknown[];
};

/** Único punto de conversión Movie (Prisma) -> MovieDto. */
export function toMovieDto(m: MovieWithRelations, opts: { isFavorite?: boolean } = {}): MovieDto {
  return {
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
    genre: { id: m.genre.id, name: m.genre.name, slug: m.genre.slug },
    proposedBy: m.proposedBy ? toPublicUser(m.proposedBy) : null,
    isFavorite: opts.isFavorite ?? (m.favorites ? m.favorites.length > 0 : false),
    createdAt: m.createdAt.toISOString(),
  };
}
