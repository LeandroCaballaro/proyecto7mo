import type { GenreSlug } from '@nexohub/shared';

/** Ids de género de TMDB -> catálogo cerrado de NexoHub. */
export const TMDB_GENRE_MAP: Record<number, GenreSlug> = {
  28: 'accion',
  12: 'aventura',
  16: 'animacion',
  35: 'comedia',
  80: 'crimen',
  99: 'documental',
  18: 'drama',
  14: 'fantasia',
  27: 'terror',
  9648: 'misterio',
  10749: 'romance',
  878: 'ciencia-ficcion',
  53: 'thriller',
};

/** Nombres en inglés (Cinemeta / IMDb) -> catálogo de NexoHub. */
export const ENGLISH_GENRE_MAP: Record<string, GenreSlug> = {
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

/** Primer género mapeable en el orden que da la fuente; `drama` si ninguno coincide. */
export function genreSlugFromTmdb(ids: number[]): GenreSlug {
  for (const id of ids) {
    const slug = TMDB_GENRE_MAP[id];
    if (slug) return slug;
  }
  return 'drama';
}

export function genreSlugFromEnglish(names: string[]): GenreSlug {
  for (const name of names) {
    const slug = ENGLISH_GENRE_MAP[name];
    if (slug) return slug;
  }
  return 'drama';
}
