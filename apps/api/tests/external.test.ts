import { describe, expect, it } from 'vitest';
import {
  directorOf,
  pickSpanishOverview,
  pickSpanishTitle,
  releaseYearOf,
  type TmdbMovieDetails,
} from '../src/lib/tmdb';
import { isValidCatalogEntry } from '../src/modules/movies/catalog-import.service';
import { genreSlugFromEnglish, genreSlugFromTmdb } from '../src/modules/movies/external-genres';
import {
  cinemetaPoster,
  directorFromCinemeta,
  parseExternalId,
  parseYear,
  storageKeyOf,
} from '../src/modules/movies/external.service';

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

describe('Cinemeta', () => {
  it('normaliza póster, año y director', () => {
    expect(cinemetaPoster('https://images.metahub.space/poster/small/tt1/img')).toBe(
      'https://images.metahub.space/poster/medium/tt1/img',
    );
    expect(cinemetaPoster(undefined)).toBeNull();
    expect(parseYear('2025–')).toBe(2025);
    expect(parseYear(2026)).toBe(2026);
    expect(parseYear(undefined)).toBeNull();
    expect(directorFromCinemeta({ id: 'tt1', name: 'X', director: ['A', 'B'] })).toBe('A, B');
    expect(directorFromCinemeta({ id: 'tt1', name: 'X' })).toBeNull();
  });
});

describe('Filtro del catálogo "New" de Cinemeta', () => {
  const now = Date.parse('2026-11-11T12:00:00Z');
  const base = { id: 'tt1', name: 'X', poster: 'p', description: 'd' };

  it('descarta entradas cuyo año no coincide con el catálogo', () => {
    expect(isValidCatalogEntry({ ...base, releaseInfo: '1938' }, 2026, now)).toBe(false);
    expect(isValidCatalogEntry({ ...base, releaseInfo: '2022' }, 2025, now)).toBe(false);
  });

  it('descarta las que todavía no se estrenaron', () => {
    expect(isValidCatalogEntry({ ...base, year: 2026, released: '2026-11-20T00:00:00Z' }, 2026, now)).toBe(false);
  });

  it('acepta estrenos ya publicados del año del catálogo', () => {
    expect(isValidCatalogEntry({ ...base, year: 2026, released: '2026-11-06T00:00:00Z' }, 2026, now)).toBe(true);
    expect(isValidCatalogEntry({ ...base, releaseInfo: '2025' }, 2025, now)).toBe(true);
  });

  it('sin fecha exacta, del año en curso no se acepta (podría no haberse estrenado)', () => {
    expect(isValidCatalogEntry({ ...base, releaseInfo: '2026' }, 2026, now)).toBe(false);
  });
});
