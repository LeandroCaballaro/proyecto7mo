/**
 * Catálogo cerrado de géneros (paridad con MovieService::createMovie del proyecto PHP).
 * `legacyName` es el valor sin tildes guardado en `movies.genre` de la BD original;
 * lo usa el ETL para mapear filas existentes.
 */
export const GENRES = [
  { slug: 'accion', name: 'Acción', legacyName: 'Accion' },
  { slug: 'aventura', name: 'Aventura', legacyName: 'Aventura' },
  { slug: 'animacion', name: 'Animación', legacyName: 'Animacion' },
  { slug: 'comedia', name: 'Comedia', legacyName: 'Comedia' },
  { slug: 'crimen', name: 'Crimen', legacyName: 'Crimen' },
  { slug: 'documental', name: 'Documental', legacyName: 'Documental' },
  { slug: 'drama', name: 'Drama', legacyName: 'Drama' },
  { slug: 'fantasia', name: 'Fantasía', legacyName: 'Fantasia' },
  { slug: 'terror', name: 'Terror', legacyName: 'Terror' },
  { slug: 'misterio', name: 'Misterio', legacyName: 'Misterio' },
  { slug: 'romance', name: 'Romance', legacyName: 'Romance' },
  { slug: 'ciencia-ficcion', name: 'Ciencia ficción', legacyName: 'Ciencia ficcion' },
  { slug: 'thriller', name: 'Thriller', legacyName: 'Thriller' },
] as const;

export type GenreSlug = (typeof GENRES)[number]['slug'];

export const GENRE_SLUGS = GENRES.map((g) => g.slug) as [GenreSlug, ...GenreSlug[]];

