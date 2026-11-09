import { z } from 'zod';
import { RATING_MAX, RATING_MIN } from '../constants/rules';

export const movieGenreSlugSchema = z.string().trim().toLowerCase();

export const createMovieSchema = z.object({
  title: z
    .string()
    .trim()
    .min(1, 'El título es obligatorio')
    .max(255, 'El título no puede superar los 255 caracteres'),
  genreSlug: movieGenreSlugSchema.min(1, 'El género es obligatorio'),
  synopsis: z.string().trim().max(3000, 'La sinopsis es muy extensa').optional(),
  director: z.string().trim().max(255).optional(),
  releaseYear: z
    .coerce
    .number()
    .int('El año debe ser un número entero')
    .min(1888, 'Año no válido')
    .max(new Date().getFullYear() + 5, 'Año no válido')
    .optional(),
  posterUrl: z.string().url('URL de póster no válida').optional().or(z.literal('')),
  imdbId: z.string().trim().optional(),
  initialReview: z
    .object({
      rating: z.coerce.number().int().min(1).max(5),
      content: z.string().trim().min(3, 'La reseña debe tener al menos 3 caracteres').max(2000),
    })
    .optional(),
});

export const movieQuerySchema = z.object({
  page: z.coerce.number().int().min(1).default(1),
  pageSize: z.coerce.number().int().min(1).max(50).default(12),
  q: z.string().trim().optional(),
  genre: z.string().trim().toLowerCase().optional(),
  sort: z.enum(['newest', 'rating', 'reviews', 'year']).default('newest'),
});

export const reviewSchema = z.object({
  rating: z
    .coerce
    .number()
    .int()
    .min(RATING_MIN, `La calificación mínima es ${RATING_MIN}`)
    .max(RATING_MAX, `La calificación máxima es ${RATING_MAX}`),
  content: z
    .string()
    .trim()
    .min(3, 'La reseña debe tener al menos 3 caracteres')
    .max(2000, 'La reseña no puede exceder 2000 caracteres'),
  imageUrl: z.string().url('URL de imagen no válida').optional().or(z.literal('')),
});

export const reviewResponseSchema = z.object({
  content: z
    .string()
    .trim()
    .min(1, 'El comentario no puede estar vacío')
    .max(1000, 'El comentario no puede exceder 1000 caracteres'),
  parentId: z.coerce.number().int().positive().optional(),
});

export type CreateMovieInput = z.infer<typeof createMovieSchema>;
export type MovieQueryInput = z.infer<typeof movieQuerySchema>;
export type ReviewInput = z.infer<typeof reviewSchema>;
export type ReviewResponseInput = z.infer<typeof reviewResponseSchema>;

