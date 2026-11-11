import type { ExternalMovieMeta, ExternalMovieResult } from '@nexohub/shared';
import { logger } from '../../lib/logger';
import {
  directorOf,
  isTmdbEnabled,
  pickSpanishOverview,
  pickSpanishTitle,
  posterUrl,
  releaseYearOf,
  tmdb,
  TmdbError,
  type TmdbMovieDetails,
} from '../../lib/tmdb';
import { translateText } from '../../lib/translator';
import { AppError } from '../../utils/AppError';
import { genreSlugFromEnglish, genreSlugFromTmdb } from './external-genres';

/**
 * Fuente externa de películas. TMDB si hay TMDB_API_KEY (datos en español);
 * si no, Cinemeta (sin clave, en inglés: la sinopsis se traduce con MyMemory).
 */

export const CINEMETA_URL = 'https://v3-cinemeta.strem.io';

export type SynopsisLang = 'es' | 'en';

export interface SpanishText {
  text: string | null;
  /** 'en' = quedó en inglés porque se agotó el cupo de traducción; se reintenta más tarde. */
  lang: SynopsisLang | null;
  translated: boolean;
}

export type ResolvedMeta = ExternalMovieMeta & {
  synopsisLang: SynopsisLang | null;
  synopsisTranslated: boolean;
};

export function parseExternalId(externalId: string): { source: 'tmdb' | 'cinemeta'; id: string } {
  if (externalId.startsWith('tmdb-')) return { source: 'tmdb', id: externalId.slice(5) };
  return { source: 'cinemeta', id: externalId };
}

/** Clave única guardada en movies.external_source / external_id. */
export function storageKeyOf(externalId: string) {
  const { source, id } = parseExternalId(externalId);
  return { externalSource: source === 'tmdb' ? 'tmdb' : 'imdb', externalId: id };
}

/** Traduce al español si hay cupo; si no, devuelve el original marcado como 'en'. */
export async function toSpanish(english: string | null | undefined): Promise<SpanishText> {
  const text = english?.trim();
  if (!text) return { text: null, lang: null, translated: false };
  const result = await translateText(text);
  return result
    ? { text: result, lang: 'es', translated: true }
    : { text, lang: 'en', translated: false };
}

async function spanishSynopsis(details: TmdbMovieDetails): Promise<SpanishText> {
  const { text, isSpanish } = pickSpanishOverview(details);
  if (!text) return { text: null, lang: null, translated: false };
  if (isSpanish) return { text, lang: 'es', translated: false };
  return toSpanish(text);
}

/** Convierte el detalle de TMDB al formato interno, con sinopsis en español. */
export async function metaFromTmdbDetails(details: TmdbMovieDetails): Promise<ResolvedMeta> {
  const synopsis = await spanishSynopsis(details);
  return {
    externalId: `tmdb-${details.id}`,
    title: pickSpanishTitle(details),
    originalTitle: details.original_title || null,
    releaseYear: releaseYearOf(details.release_date),
    posterUrl: posterUrl(details.poster_path),
    description: details.overview || null,
    director: directorOf(details),
    genres: details.genres.map((g) => g.name),
    genreSlug: genreSlugFromTmdb(details.genres.map((g) => g.id)),
    synopsis: synopsis.text,
    synopsisLang: synopsis.lang,
    synopsisTranslated: synopsis.translated,
    externalRating: details.vote_count > 0 ? Math.round(details.vote_average * 10) / 10 : null,
    source: 'tmdb',
  };
}

export async function fetchCinemeta<T>(path: string): Promise<T> {
  // Algunos catálogos redirigen (302) a cinemeta-catalogs.strem.io: fetch sigue la redirección.
  const res = await fetch(`${CINEMETA_URL}${path}`, {
    headers: { 'User-Agent': 'NexoHub/2.0' },
    signal: AbortSignal.timeout(10_000),
  });
  if (!res.ok) throw new Error(`Cinemeta respondió ${res.status}`);
  return (await res.json()) as T;
}

export interface CinemetaMeta {
  id: string;
  name: string;
  description?: string;
  poster?: string;
  year?: string | number;
  releaseInfo?: string;
  released?: string;
  director?: string[] | string;
  genres?: string[];
  imdbRating?: string;
}

export const parseYear = (v?: string | number) => {
  const n = typeof v === 'number' ? v : v ? Number.parseInt(v, 10) : Number.NaN;
  return Number.isFinite(n) ? n : null;
};

export const directorFromCinemeta = (m: CinemetaMeta) =>
  (Array.isArray(m.director) ? m.director.join(', ') : m.director) || null;

/** Cinemeta devuelve el póster "small" en los catálogos; "medium" se ve bien en las tarjetas. */
export const cinemetaPoster = (url?: string) =>
  url ? url.replace('/poster/small/', '/poster/medium/') : null;

const unavailable = () =>
  new AppError(502, 'EXTERNAL_UNAVAILABLE', 'El servicio externo de películas no respondió');

export const externalMovies = {
  async search(query: string): Promise<ExternalMovieResult[]> {
    const q = query.trim();
    if (q.length < 2) return [];

    try {
      if (isTmdbEnabled()) {
        const page = await tmdb.search(q);
        return page.results.slice(0, 12).map((m) => ({
          externalId: `tmdb-${m.id}`,
          title: m.title,
          originalTitle: m.original_title || null,
          releaseYear: releaseYearOf(m.release_date),
          posterUrl: posterUrl(m.poster_path),
          description: m.overview || null,
        }));
      }

      const data = await fetchCinemeta<{ metas?: CinemetaMeta[] }>(
        `/catalog/movie/top/search=${encodeURIComponent(q)}.json`,
      );
      return (data.metas ?? []).slice(0, 12).map((m) => ({
        externalId: m.id,
        title: m.name,
        originalTitle: null,
        releaseYear: parseYear(m.releaseInfo),
        posterUrl: cinemetaPoster(m.poster),
        description: m.description || null,
      }));
    } catch (err) {
      logger.warn({ err }, 'Falló la búsqueda externa de películas');
      throw unavailable();
    }
  },

  async getMeta(externalId: string): Promise<ResolvedMeta> {
    const { source, id } = parseExternalId(externalId);

    if (source === 'tmdb') {
      if (!isTmdbEnabled()) throw AppError.badRequest('TMDB no está configurado en el servidor');
      try {
        return await metaFromTmdbDetails(await tmdb.details(Number(id)));
      } catch (err) {
        if (err instanceof TmdbError && err.status === 404) {
          throw AppError.notFound('Película no encontrada en TMDB');
        }
        logger.warn({ err }, 'Falló la consulta a TMDB');
        throw unavailable();
      }
    }

    let m: CinemetaMeta | undefined;
    try {
      m = (
        await fetchCinemeta<{ meta?: CinemetaMeta }>(`/meta/movie/${encodeURIComponent(id)}.json`)
      ).meta;
    } catch (err) {
      logger.warn({ err }, 'Falló la consulta a Cinemeta');
      throw unavailable();
    }
    if (!m) throw AppError.notFound('Película no encontrada');

    const genres = Array.isArray(m.genres) ? m.genres : [];
    const synopsis = await toSpanish(m.description);

    return {
      externalId: m.id,
      title: m.name,
      originalTitle: m.name,
      releaseYear: parseYear(m.year) ?? parseYear(m.releaseInfo),
      posterUrl: cinemetaPoster(m.poster),
      description: m.description || null,
      director: directorFromCinemeta(m),
      genres,
      genreSlug: genreSlugFromEnglish(genres),
      synopsis: synopsis.text,
      synopsisLang: synopsis.lang,
      synopsisTranslated: synopsis.translated,
      externalRating: m.imdbRating ? Number(m.imdbRating) || null : null,
      source: 'cinemeta',
    };
  },
};
