import { GENRES, type GenreSlug } from '@nexohub/shared';
import { logger } from '../../lib/logger';
import { prisma } from '../../lib/prisma';
import { isTmdbEnabled, tmdb, type TmdbListMovie } from '../../lib/tmdb';
import { isTranslationQuotaExhausted } from '../../lib/translator';
import {
  cinemetaPoster,
  directorFromCinemeta,
  fetchCinemeta,
  metaFromTmdbDetails,
  parseYear,
  toSpanish,
  type CinemetaMeta,
} from './external.service';
import { genreSlugFromEnglish, TMDB_GENRE_MAP } from './external-genres';

/**
 * Importación incremental del catálogo.
 * Cada corrida: 1) guarda al instante las películas más recientes que todavía no están en la base,
 * 2) traduce las sinopsis pendientes ('en') hasta agotar el cupo diario de MyMemory.
 * Las respuestas de Cinemeta y las traducciones quedan en caché en la base.
 * Fuente: TMDB si hay TMDB_API_KEY; si no, el catálogo "New" de Cinemeta (sin clave).
 */

export interface ImportOptions {
  /** Películas nuevas a agregar en esta corrida. */
  count: number;
  /** Años hacia atrás a recorrer, contando el actual. */
  years: number;
}

export interface ImportResult {
  source: 'tmdb' | 'cinemeta';
  created: number;
  translatedPending: number;
  pendingTranslation: number;
  failed: number;
}

type Log = (msg: string) => void;

const PENDING_BATCH = 100;

async function genreIds() {
  const rows = await prisma.genre.findMany();
  if (rows.length === 0)
    throw new Error('No hay géneros en la base. Corré primero: npm run db:seed');
  return new Map(rows.map((g) => [g.slug, g.id]));
}

async function ownerId() {
  const owner = await prisma.user.findFirst({
    where: { role: 'SUPERADMIN' },
    orderBy: { id: 'asc' },
  });
  return owner?.id ?? null;
}

/** Traduce sinopsis que quedaron en inglés en corridas anteriores. */
export async function translatePendingSynopses(log: Log = () => {}): Promise<number> {
  let done = 0;
  while (!isTranslationQuotaExhausted()) {
    const pending = await prisma.movie.findMany({
      where: { synopsisLang: 'en', synopsis: { not: null } },
      orderBy: { releaseYear: 'desc' },
      take: PENDING_BATCH,
      select: { id: true, synopsis: true },
    });
    if (pending.length === 0) break;

    let progressed = false;
    for (const movie of pending) {
      const result = await toSpanish(movie.synopsis);
      if (!result.translated) break;
      await prisma.movie.update({
        where: { id: movie.id },
        data: { synopsis: result.text, synopsisLang: 'es' },
      });
      done++;
      progressed = true;
    }
    if (!progressed) break;
  }
  if (done > 0) log(`  Sinopsis pendientes traducidas: ${done}`);
  return done;
}

/** Ids externos ya cargados (de cualquier fuente), para no duplicar. */
async function existingExternalIds(ids: string[]): Promise<Set<string>> {
  if (ids.length === 0) return new Set();
  const rows = await prisma.movie.findMany({
    where: { externalId: { in: ids } },
    select: { externalId: true },
  });
  return new Set(rows.map((r) => r.externalId!));
}

// ───────────────────────────── Cinemeta ─────────────────────────────

/** Origen de las filas creadas por el importador (las propuestas por usuarios usan 'imdb'). */
const CINEMETA_SOURCE = 'cinemeta';

/**
 * El catálogo "New" de Cinemeta trae entradas inconsistentes (p. ej. tt12590266 figura como
 * "I See Ice" de 1938 con el póster de otra obra). Se acepta solo si el año declarado coincide
 * con el año del catálogo, ya se estrenó, y tiene póster y sinopsis.
 */
export function isValidCatalogEntry(
  m: CinemetaMeta,
  catalogYear: number,
  now = Date.now(),
): boolean {
  if (!m.id?.startsWith('tt') || !m.poster || !m.description?.trim()) return false;
  if (parseYear(m.year ?? m.releaseInfo) !== catalogYear) return false;
  const released = m.released ? Date.parse(m.released) : Number.NaN;
  if (Number.isNaN(released)) return catalogYear < new Date(now).getFullYear();
  return new Date(released).getUTCFullYear() === catalogYear && released <= now;
}

/**
 * Borra filas importadas desde Cinemeta que no pasan la validación actual (año fuera de la
 * ventana o sin año), siempre que nadie las haya reseñado ni marcado como favoritas.
 */
async function pruneInvalidCinemetaImports(years: number, log: Log): Promise<number> {
  const minYear = new Date().getFullYear() - years + 1;
  const { count } = await prisma.movie.deleteMany({
    where: {
      externalSource: CINEMETA_SOURCE,
      reviewsCount: 0,
      favorites: { none: {} },
      OR: [{ releaseYear: null }, { releaseYear: { lt: minYear } }],
    },
  });
  if (count > 0) log(`  Películas inválidas eliminadas: ${count}`);
  return count;
}

/**
 * Recorre el catálogo de estrenos página por página y guarda cada página apenas llega,
 * con la sinopsis en inglés. La traducción se hace después, sin frenar la carga.
 */
async function importFromCinemeta(opts: ImportOptions, log: Log) {
  await pruneInvalidCinemetaImports(opts.years, log);
  const genres = await genreIds();
  const owner = await ownerId();
  const now = Date.now();
  const thisYear = new Date().getFullYear();
  let created = 0;
  let failed = 0;

  for (let year = thisYear; year > thisYear - opts.years && created < opts.count; year--) {
    let skip = 0;
    for (let page = 0; page < 10 && created < opts.count; page++) {
      const extra = skip > 0 ? `genre=${year}&skip=${skip}` : `genre=${year}`;
      let metas: CinemetaMeta[] = [];
      try {
        metas =
          (await fetchCinemeta<{ metas?: CinemetaMeta[] }>(`/catalog/movie/year/${extra}.json`))
            .metas ?? [];
      } catch (err) {
        failed++;
        log(
          `  No se pudo leer el catálogo ${year} (página ${page + 1}): ${(err as Error).message}`,
        );
        break;
      }
      if (metas.length === 0) break;
      skip += metas.length;

      const usable = metas.filter((m) => isValidCatalogEntry(m, year, now));
      const known = await existingExternalIds(usable.map((m) => m.id));
      const fresh = usable.filter((m) => !known.has(m.id)).slice(0, opts.count - created);
      if (fresh.length === 0) continue;

      const { count } = await prisma.movie.createMany({
        data: fresh.map((m) => ({
          title: m.name,
          synopsis: m.description!.trim(),
          synopsisLang: 'en',
          director: directorFromCinemeta(m),
          releaseYear: parseYear(m.year ?? m.releaseInfo),
          posterUrl: cinemetaPoster(m.poster),
          genreId: genres.get(genreSlugFromEnglish(m.genres ?? [])) ?? genres.get('drama')!,
          status: 'APPROVED' as const,
          externalSource: CINEMETA_SOURCE,
          externalId: m.id,
          proposedById: owner,
        })),
        skipDuplicates: true,
      });
      created += count;
      log(`  ${year}, página ${page + 1}: ${count} guardadas (total ${created})`);
    }
  }
  return { created, failed };
}

// ───────────────────────────── TMDB ─────────────────────────────

async function collectTmdb(opts: ImportOptions, log: Log) {
  const today = new Date();
  const from = new Date(today);
  from.setFullYear(today.getFullYear() - opts.years);
  const iso = (d: Date) => d.toISOString().slice(0, 10);
  const tmdbIdBySlug = new Map<GenreSlug, number>(
    Object.entries(TMDB_GENRE_MAP).map(([id, slug]) => [slug, Number(id)]),
  );

  // Se reparte el cupo entre géneros para que el catálogo quede balanceado.
  const perGenre = Math.ceil(opts.count / GENRES.length);
  const picked = new Map<number, { movie: TmdbListMovie; slug: GenreSlug }>();

  for (const g of GENRES) {
    const genreId = tmdbIdBySlug.get(g.slug);
    if (!genreId) continue;
    let count = 0;
    for (let page = 1; page <= 5 && count < perGenre && picked.size < opts.count; page++) {
      const res = await tmdb.discover({
        with_genres: genreId,
        sort_by: 'primary_release_date.desc',
        'primary_release_date.gte': iso(from),
        'primary_release_date.lte': iso(today),
        'vote_count.gte': g.slug === 'documental' ? 5 : 30,
        page,
      });
      const fresh = res.results.filter((m) => !m.adult && !picked.has(m.id));
      const known = await existingExternalIds(fresh.map((m) => String(m.id)));
      for (const movie of fresh) {
        if (count >= perGenre || picked.size >= opts.count) break;
        if (known.has(String(movie.id))) continue;
        picked.set(movie.id, { movie, slug: g.slug });
        count++;
      }
      if (page >= res.total_pages) break;
    }
  }
  log(`  ${picked.size} candidatas nuevas en TMDB`);
  return [...picked.values()];
}

async function importFromTmdb(opts: ImportOptions, log: Log) {
  const genres = await genreIds();
  const owner = await ownerId();
  let created = 0;
  let failed = 0;

  for (const { movie, slug } of await collectTmdb(opts, log)) {
    try {
      const details = await tmdb.details(movie.id);
      const meta = await metaFromTmdbDetails(details);
      const data = {
        title: meta.title,
        synopsis: meta.synopsis,
        synopsisLang: meta.synopsisLang,
        director: meta.director,
        releaseYear: meta.releaseYear,
        posterUrl: meta.posterUrl,
        genreId: genres.get(slug) ?? genres.get('drama')!,
        status: 'APPROVED' as const,
        externalSource: 'tmdb',
        externalId: String(movie.id),
      };
      // Si ya estaba cargada por su id de IMDb (Cinemeta / seed), se actualiza esa fila.
      const byImdb = details.imdb_id
        ? await prisma.movie.findFirst({ where: { externalId: details.imdb_id } })
        : null;
      if (byImdb) {
        await prisma.movie.update({ where: { id: byImdb.id }, data });
      } else {
        await prisma.movie.create({ data: { ...data, proposedById: owner } });
        created++;
      }
    } catch (err) {
      failed++;
      log(`  No se pudo importar "${movie.title}" (${movie.id}): ${(err as Error).message}`);
    }
  }
  return { created, failed };
}

// ───────────────────────────── Entrada ─────────────────────────────

let running = false;

export async function importLatestMovies(
  opts: ImportOptions,
  log: Log = () => {},
): Promise<ImportResult> {
  if (running) throw new Error('Ya hay una importación en curso');
  running = true;
  try {
    const source = isTmdbEnabled() ? 'tmdb' : 'cinemeta';
    log(`Fuente: ${source === 'tmdb' ? 'TMDB' : 'Cinemeta'}`);

    const { created, failed } =
      source === 'tmdb' ? await importFromTmdb(opts, log) : await importFromCinemeta(opts, log);
    // Las películas ya están guardadas y visibles; ahora se traducen sus sinopsis.
    const translatedPending = await translatePendingSynopses(log);
    const pendingTranslation = await prisma.movie.count({ where: { synopsisLang: 'en' } });

    return { source, created, translatedPending, pendingTranslation, failed };
  } finally {
    running = false;
  }
}

/** Importación automática diaria dentro del servidor (AUTO_IMPORT_MOVIES > 0). */
export function scheduleDailyImport(count: number, years: number): NodeJS.Timeout {
  const run = () =>
    importLatestMovies({ count, years })
      .then((r) => logger.info(r, 'Importación diaria de películas completada'))
      .catch((err) => logger.error({ err }, 'Falló la importación diaria de películas'));

  // Primera corrida un minuto después del arranque, para no competir con el inicio del server.
  setTimeout(run, 60_000).unref();
  const timer = setInterval(run, 24 * 60 * 60 * 1000);
  timer.unref();
  return timer;
}
