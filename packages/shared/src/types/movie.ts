import type { PublicUser } from './user';

export interface GenreDto {
  id: number;
  name: string;
  slug: string;
}

export interface MovieDto {
  id: number;
  title: string;
  synopsis: string | null;
  director: string | null;
  releaseYear: number | null;
  posterUrl: string | null;
  featured: boolean;
  status: 'PENDING' | 'APPROVED' | 'REJECTED';
  rejectionReason: string | null;
  ratingAvg: number;
  reviewsCount: number;
  genre: GenreDto;
  proposedBy?: PublicUser | null;
  isFavorite?: boolean;
  userReview?: ReviewDto | null;
  createdAt: string;
}

export interface ReviewResponseDto {
  id: number;
  content: string;
  createdAt: string;
  user: PublicUser;
  parentId?: number | null;
}

export interface ReviewDto {
  id: number;
  rating: number;
  content: string | null;
  imageUrl: string | null;
  likesCount: number;
  hasLiked?: boolean;
  createdAt: string;
  user: PublicUser;
  movieId?: number;
  responses?: ReviewResponseDto[];
}

export interface RankingUserDto {
  id: number;
  name: string;
  username: string;
  avatarUrl: string | null;
  reputation: number;
  points?: number;
  genre?: string;
}

/** Resultado de búsqueda en la fuente externa (TMDB o Cinemeta). */
export interface ExternalMovieResult {
  externalId: string;
  title: string;
  originalTitle: string | null;
  releaseYear: number | null;
  posterUrl: string | null;
  description: string | null;
}

/** Metadata completa de una película externa, ya en español cuando es posible. */
export interface ExternalMovieMeta extends ExternalMovieResult {
  director: string | null;
  genres: string[];
  genreSlug: string;
  synopsis: string | null;
  externalRating: number | null;
  source: 'tmdb' | 'cinemeta';
}
