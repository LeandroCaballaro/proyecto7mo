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
 * si no, Cinemeta (sin clave, solo inglés, se traduce la sinopsis).
 */

const CINEMETA = 'https://v3-cinemeta.strem.io';

export function parseExternalId(externalId: string): { source: 'tmdb' | 'cinemeta'; id: string } {
  if (externalId.startsWith('tmdb-')) return { source: 'tmdb', id: externalId.slice(5) };
  return { source: 'cinemeta', id: externalId };
}

/** Clave única guardada en movies.external_source / external_id. */
export function storageKeyOf(externalId: string) {
  const { source, id } = parseExternalId(externalId);
  return { externalSource: source === 'tmdb' ? 'tmdb' : 'imdb', externalId: id };
}

/** `translated`: la sinopsis venía solo en inglés y se tradujo con MyMemory. */
async function spanishSynopsis(
  details: TmdbMovieDetails,
): Promise<{ text: string | null; translated: boolean }> {
  const { text, isSpanish } = pickSpanishOverview(details);
  if (!text || isSpanish) return { text, translated: false };
  const result = await translateText(text);
  return { text: result ?? text, translated: result != null };
}

/** Convierte el detalle de TMDB al formato interno, con sinopsis en español. */
export async function metaFromTmdbDetails(
  details: TmdbMovieDetails,
): Promise<ExternalMovieMeta & { synopsisTranslated: boolean }> {
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
    synopsisTranslated: synopsis.translated,
    externalRating: details.vote_count > 0 ? Math.round(details.vote_average * 10) / 10 : null,
    source: 'tmdb',
  };
}

async function fetchCinemeta<T>(path: string): Promise<T> {
  const res = await fetch(`${CINEMETA}${path}`, {
    headers: { 'User-Agent': 'NexoHub/2.0' },
    signal: AbortSignal.timeout(8000),
  });
  if (!res.ok) throw new Error(`Cinemeta respondió ${res.status}`);
  return (await res.json()) as T;
}

interface CinemetaMeta {
  id: string;
  name: string;
  description?: string;
  poster?: string;
  year?: string;
  releaseInfo?: string;
  director?: string[] | string;
  genres?: string[];
  imdbRating?: string;
}

const parseYear = (v?: string) => {
  const n = v ? Number.parseInt(v, 10) : Number.NaN;
  return Number.isFinite(n) ? n : null;
};

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
        posterUrl: m.poster || null,
        description: m.description || null,
      }));
    } catch (err) {
      logger.warn({ err }, 'Falló la búsqueda externa de películas');
      throw new AppError(
        502,
        'EXTERNAL_UNAVAILABLE',
        'El servicio externo de películas no respondió',
      );
    }
  },

  async getMeta(externalId: string): Promise<ExternalMovieMeta> {
    const { source, id } = parseExternalId(externalId);

    if (source === 'tmdb') {
      if (!isTmdbEnabled()) throw AppError.badRequest('TMDB no está configurado en el servidor');
      try {
        return await metaFromTmdbDetails(await tmdb.details(Number(id)));
      } catch (err) {
        if (err instanceof TmdbError && err.status === 404)
          throw AppError.notFound('Película no encontrada en TMDB');
        logger.warn({ err }, 'Falló la consulta a TMDB');
        throw new AppError(502, 'EXTERNAL_UNAVAILABLE', 'TMDB no respondió');
      }
    }

    let m: CinemetaMeta | undefined;
    try {
      m = (
        await fetchCinemeta<{ meta?: CinemetaMeta }>(`/meta/movie/${encodeURIComponent(id)}.json`)
      ).meta;
    } catch (err) {
      logger.warn({ err }, 'Falló la consulta a Cinemeta');
      throw new AppError(
        502,
        'EXTERNAL_UNAVAILABLE',
        'El servicio externo de películas no respondió',
      );
    }
    if (!m) throw AppError.notFound('Película no encontrada');

    const genres = Array.isArray(m.genres) ? m.genres : [];
    const director = Array.isArray(m.director) ? m.director.join(', ') : m.director || null;
    const synopsis = m.description ? ((await translateText(m.description)) ?? m.description) : null;

    return {
      externalId: m.id,
      title: m.name,
      originalTitle: m.name,
      releaseYear: parseYear(m.year) ?? parseYear(m.releaseInfo),
      posterUrl: m.poster || null,
      description: m.description || null,
      director,
      genres,
      genreSlug: genreSlugFromEnglish(genres),
      synopsis,
      externalRating: m.imdbRating ? Number(m.imdbRating) || null : null,
      source: 'cinemeta',
    };
  },
};
