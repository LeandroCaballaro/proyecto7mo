import { describe, expect, it } from 'vitest';
import {
  directorOf,
  pickSpanishOverview,
  pickSpanishTitle,
  releaseYearOf,
  type TmdbMovieDetails,
} from '../src/lib/tmdb';
import { genreSlugFromEnglish, genreSlugFromTmdb } from '../src/modules/movies/external-genres';
import { parseExternalId, storageKeyOf } from '../src/modules/movies/external.service';

const base: TmdbMovieDetails = {
  id: 1,
  imdb_id: 'tt0000001',
  title: '',
  original_title: 'Original',
  original_language: 'en',
  overview: '',
  release_date: '2025-03-14',
  poster_path: null,
  genres: [],
  vote_average: 7.5,
  vote_count: 100,
  adult: false,
  credits: {
    crew: [
      { job: 'Director', name: 'A' },
      { job: 'Writer', name: 'B' },
      { job: 'Director', name: 'A' },
    ],
  },
  translations: {
    translations: [
      { iso_639_1: 'en', iso_3166_1: 'US', data: { title: 'Original', overview: 'English plot.' } },
      {
        iso_639_1: 'es',
        iso_3166_1: 'ES',
        data: { title: 'Título España', overview: 'Sinopsis de España.' },
      },
    ],
  },
};

describe('TMDB: selección de idioma', () => {
  it('usa la sinopsis del idioma pedido si existe', () => {
    expect(pickSpanishOverview({ ...base, overview: 'Sinopsis MX.' })).toEqual({
      text: 'Sinopsis MX.',
      isSpanish: true,
    });
  });

  it('cae a otra variante de español antes que al inglés', () => {
    expect(pickSpanishOverview(base)).toEqual({ text: 'Sinopsis de España.', isSpanish: true });
    expect(pickSpanishTitle(base)).toBe('Título España');
  });

  it('marca el inglés como pendiente de traducción', () => {
    const onlyEn = {
      ...base,
      translations: { translations: [base.translations!.translations[0]!] },
    };
    expect(pickSpanishOverview(onlyEn)).toEqual({ text: 'English plot.', isSpanish: false });
    expect(pickSpanishTitle(onlyEn)).toBe('Original');
  });

  it('extrae año y directores sin duplicados', () => {
    expect(releaseYearOf(base.release_date)).toBe(2025);
    expect(releaseYearOf('')).toBeNull();
    expect(directorOf(base)).toBe('A');
  });
});

describe('Mapeo de géneros', () => {
  it('toma el primer género mapeable y cae a drama', () => {
    expect(genreSlugFromTmdb([10751, 878, 28])).toBe('ciencia-ficcion');
    expect(genreSlugFromTmdb([10751])).toBe('drama');
    expect(genreSlugFromEnglish(['Family', 'Horror'])).toBe('terror');
  });
});

describe('Ids externos', () => {
  it('distingue TMDB de IMDb', () => {
    expect(parseExternalId('tmdb-603')).toEqual({ source: 'tmdb', id: '603' });
    expect(storageKeyOf('tt0133093')).toEqual({ externalSource: 'imdb', externalId: 'tt0133093' });
    expect(storageKeyOf('tmdb-603')).toEqual({ externalSource: 'tmdb', externalId: '603' });
  });
});
