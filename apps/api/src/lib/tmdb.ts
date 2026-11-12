import { env } from '../config/env';
import { cached, DAY, HOUR } from './cache';

/**
 * Cliente mínimo de TMDB (https://developer.themoviedb.org).
 * Uso gratuito no comercial con atribución obligatoria (ver Footer del frontend).
 * Acepta la API key v3 (32 caracteres hex) o el token de lectura v4 (JWT).
 */

const BASE_URL = 'https://api.themoviedb.org/3';
export const TMDB_IMAGE_BASE = 'https://image.tmdb.org/t/p/w500';

export const isTmdbEnabled = () => Boolean(env.TMDB_API_KEY);

export class TmdbError extends Error {
  constructor(
    public status: number,
    message: string,
  ) {
    super(message);
    this.name = 'TmdbError';
  }
}

export interface TmdbListMovie {
  id: number;
  title: string;
  original_title: string;
  original_language: string;
  overview: string;
  release_date: string;
  poster_path: string | null;
  genre_ids: number[];
  popularity: number;
  vote_average: number;
  vote_count: number;
  adult: boolean;
}

export interface TmdbPage<T> {
  page: number;
  total_pages: number;
  total_results: number;
  results: T[];
}

interface TmdbTranslation {
  iso_639_1: string;
  iso_3166_1: string;
  data: { title?: string; overview?: string };
}

export interface TmdbMovieDetails {
  id: number;
  imdb_id: string | null;
  title: string;
  original_title: string;
  original_language: string;
  overview: string;
  release_date: string;
  poster_path: string | null;
  genres: { id: number; name: string }[];
  vote_average: number;
  vote_count: number;
  adult: boolean;
  credits?: { crew: { job: string; name: string }[] };
  translations?: { translations: TmdbTranslation[] };
}

async function request<T>(
  path: string,
  query: Record<string, string | number | boolean> = {},
  attempt = 0,
): Promise<T> {
  const key = env.TMDB_API_KEY;
  if (!key) throw new TmdbError(500, 'TMDB_API_KEY no está configurada');

  const isBearer = key.includes('.');
  const params = new URLSearchParams(Object.entries(query).map(([k, v]) => [k, String(v)]));
  if (!isBearer) params.set('api_key', key);

  const res = await fetch(`${BASE_URL}${path}?${params}`, {
    headers: {
      Accept: 'application/json',
      ...(isBearer ? { Authorization: `Bearer ${key}` } : {}),
    },
    signal: AbortSignal.timeout(10_000),
  });

  // 429: TMDB limita ~50 req/s por IP. Reintento con espera creciente.
  if (res.status === 429 && attempt < 4) {
    const retryAfter = Number(res.headers.get('retry-after')) || attempt + 1;
    await new Promise((r) => setTimeout(r, retryAfter * 1000));
    return request<T>(path, query, attempt + 1);
  }
  if (!res.ok) {
    throw new TmdbError(
      res.status,
      res.status === 401 ? 'TMDB_API_KEY inválida' : `TMDB respondió ${res.status}`,
    );
  }
  return (await res.json()) as T;
}

export const tmdb = {
  discover(query: Record<string, string | number | boolean>) {
    return request<TmdbPage<TmdbListMovie>>('/discover/movie', {
      language: env.TMDB_LANGUAGE,
      include_adult: false,
      include_video: false,
      ...query,
    });
  },

  nowPlaying(page: number, region = 'AR') {
    return request<TmdbPage<TmdbListMovie>>('/movie/now_playing', {
      language: env.TMDB_LANGUAGE,
      region,
      page,
    });
  },

  search(q: string) {
    return cached(`tmdb:search:${env.TMDB_LANGUAGE}:${q.toLowerCase()}`, 6 * HOUR, () =>
      request<TmdbPage<TmdbListMovie>>('/search/movie', {
        query: q,
        language: env.TMDB_LANGUAGE,
        include_adult: false,
        page: 1,
      }),
    );
  },

  /** Busca por id de IMDb (`tt...`). */
  findByImdbId(imdbId: string) {
    return request<{ movie_results: TmdbListMovie[] }>(`/find/${encodeURIComponent(imdbId)}`, {
      external_source: 'imdb_id',
      language: env.TMDB_LANGUAGE,
    });
  },

  details(id: number) {
    return cached(`tmdb:movie:${env.TMDB_LANGUAGE}:${id}`, 7 * DAY, () =>
      request<TmdbMovieDetails>(`/movie/${id}`, {
        language: env.TMDB_LANGUAGE,
        append_to_response: 'credits,translations',
      }),
    );
  },
};

export const posterUrl = (path: string | null) => (path ? `${TMDB_IMAGE_BASE}${path}` : null);

export const releaseYearOf = (date: string | null | undefined) => {
  const year = date ? Number.parseInt(date.slice(0, 4), 10) : Number.NaN;
  return Number.isFinite(year) ? year : null;
};

export function directorOf(details: TmdbMovieDetails): string | null {
  const names = (details.credits?.crew ?? [])
    .filter((c) => c.job === 'Director')
    .map((c) => c.name);
  return names.length > 0 ? [...new Set(names)].join(', ') : null;
}

/** Prioridad de idiomas: el configurado, después cualquier variante de español. */
function spanishTranslations(details: TmdbMovieDetails): TmdbTranslation[] {
  const [lang, country] = env.TMDB_LANGUAGE.split('-');
  const list = (details.translations?.translations ?? []).filter((t) => t.iso_639_1 === 'es');
  const rank = (t: TmdbTranslation) =>
    t.iso_639_1 === lang && t.iso_3166_1 === country
      ? 0
      : t.iso_3166_1 === 'MX'
        ? 1
        : t.iso_3166_1 === 'ES'
          ? 2
          : 3;
  return list.sort((a, b) => rank(a) - rank(b));
}

/** Sinopsis en español si TMDB la tiene en alguna variante; si no, la original en inglés. */
export function pickSpanishOverview(details: TmdbMovieDetails): {
  text: string | null;
  isSpanish: boolean;
} {
  if (details.overview?.trim()) return { text: details.overview.trim(), isSpanish: true };

  const es = spanishTranslations(details).find((t) => t.data.overview?.trim());
  if (es) return { text: es.data.overview!.trim(), isSpanish: true };

  const en = (details.translations?.translations ?? []).find(
    (t) => t.iso_639_1 === 'en' && t.data.overview?.trim(),
  );
  return { text: en?.data.overview?.trim() ?? null, isSpanish: false };
}

export function pickSpanishTitle(details: TmdbMovieDetails): string {
  if (details.title?.trim()) return details.title.trim();
  const es = spanishTranslations(details).find((t) => t.data.title?.trim());
  return es?.data.title?.trim() || details.original_title;
}
