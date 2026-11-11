/**
 * Importa películas actuales desde TMDB, con título y sinopsis en español.
 *
 *   npm run db:import-movies -w @nexohub/api                     # 240 películas de los últimos 3 años
 *   npm run db:import-movies -w @nexohub/api -- --count 300 --years 5
 *
 * - Reparte el cupo entre los 13 géneros del catálogo (las más populares de cada uno).
 * - Sinopsis: es-MX -> otra variante de español -> inglés traducido con MyMemory.
 * - Es idempotente: vuelve a correrlo para actualizar datos sin duplicar.
 */
import { PrismaClient } from '@prisma/client';
import { GENRES, type GenreSlug } from '@nexohub/shared';
import { isTmdbEnabled, tmdb, type TmdbListMovie } from '../../src/lib/tmdb';
import { metaFromTmdbDetails } from '../../src/modules/movies/external.service';
import { TMDB_GENRE_MAP } from '../../src/modules/movies/external-genres';

const prisma = new PrismaClient();

function arg(name: string, fallback: number): number {
  const i = process.argv.indexOf(`--${name}`);
  const value = i >= 0 ? Number(process.argv[i + 1]) : Number.NaN;
  return Number.isInteger(value) && value > 0 ? value : fallback;
}

const TARGET = arg('count', 240);
const YEARS = arg('years', 3);
const CONCURRENCY = 5;

const tmdbIdBySlug = new Map<GenreSlug, number>(
  Object.entries(TMDB_GENRE_MAP).map(([id, slug]) => [slug, Number(id)]),
);

async function collectCandidates(): Promise<
  Map<number, { movie: TmdbListMovie; slug: GenreSlug }>
> {
  const today = new Date();
  const from = new Date(today);
  from.setFullYear(today.getFullYear() - YEARS);
  const iso = (d: Date) => d.toISOString().slice(0, 10);

  const perGenre = Math.ceil(TARGET / GENRES.length);
  const picked = new Map<number, { movie: TmdbListMovie; slug: GenreSlug }>();

  for (const g of GENRES) {
    const genreId = tmdbIdBySlug.get(g.slug);
    if (!genreId) continue;
    let count = 0;

    for (let page = 1; page <= 5 && count < perGenre; page++) {
      const res = await tmdb.discover({
        with_genres: genreId,
        sort_by: 'popularity.desc',
        'primary_release_date.gte': iso(from),
        'primary_release_date.lte': iso(today),
        // Los documentales tienen pocos votos: umbral más bajo para que el género no quede vacío.
        'vote_count.gte': g.slug === 'documental' ? 10 : 50,
        page,
      });
      for (const movie of res.results) {
        if (count >= perGenre) break;
        if (movie.adult || picked.has(movie.id)) continue;
        picked.set(movie.id, { movie, slug: g.slug });
        count++;
      }
      if (page >= res.total_pages) break;
    }
    console.log(`  ${g.name.padEnd(16)} ${count} candidatas`);
  }

  // Completa con lo que está en cartelera si algún género no alcanzó su cuota.
  for (let page = 1; picked.size < TARGET && page <= 5; page++) {
    const res = await tmdb.nowPlaying(page);
    for (const movie of res.results) {
      if (picked.size >= TARGET) break;
      if (movie.adult || picked.has(movie.id)) continue;
      const slug = movie.genre_ids.map((id) => TMDB_GENRE_MAP[id]).find(Boolean);
      if (slug) picked.set(movie.id, { movie, slug });
    }
  }

  return picked;
}

async function runPool<T>(items: T[], worker: (item: T, index: number) => Promise<void>) {
  let next = 0;
  await Promise.all(
    Array.from({ length: CONCURRENCY }, async () => {
      while (next < items.length) {
        const i = next++;
        await worker(items[i]!, i);
      }
    }),
  );
}

async function main() {
  if (!isTmdbEnabled()) {
    console.error('Falta TMDB_API_KEY en apps/api/.env (https://www.themoviedb.org/settings/api).');
    process.exitCode = 1;
    return;
  }

  console.log(`Importando ${TARGET} películas de TMDB (estrenos de los últimos ${YEARS} años)...`);

  const genreRows = await prisma.genre.findMany();
  const genreIdBySlug = new Map(genreRows.map((g) => [g.slug, g.id]));
  if (genreIdBySlug.size === 0) {
    console.error('No hay géneros en la base. Corré primero: npm run db:seed');
    process.exitCode = 1;
    return;
  }

  const owner = await prisma.user.findFirst({
    where: { role: 'SUPERADMIN' },
    orderBy: { id: 'asc' },
  });
  const candidates = [...(await collectCandidates()).values()];

  let created = 0;
  let updated = 0;
  let translated = 0;
  let failed = 0;

  await runPool(candidates, async ({ movie, slug }, i) => {
    try {
      const details = await tmdb.details(movie.id);
      const meta = await metaFromTmdbDetails(details);
      if (meta.synopsisTranslated) translated++;

      const data = {
        title: meta.title,
        synopsis: meta.synopsis,
        director: meta.director,
        releaseYear: meta.releaseYear,
        posterUrl: meta.posterUrl,
        genreId: genreIdBySlug.get(slug) ?? genreIdBySlug.get('drama')!,
        status: 'APPROVED' as const,
      };

      // Si la película ya estaba cargada por su id de IMDb (seed anterior), se actualiza esa fila.
      const existing =
        (await prisma.movie.findUnique({
          where: {
            externalSource_externalId: { externalSource: 'tmdb', externalId: String(movie.id) },
          },
        })) ??
        (details.imdb_id
          ? await prisma.movie.findFirst({ where: { externalId: details.imdb_id } })
          : null);

      if (existing) {
        await prisma.movie.update({
          where: { id: existing.id },
          data: { ...data, externalSource: 'tmdb', externalId: String(movie.id) },
        });
        updated++;
      } else {
        await prisma.movie.create({
          data: {
            ...data,
            externalSource: 'tmdb',
            externalId: String(movie.id),
            proposedById: owner?.id ?? null,
          },
        });
        created++;
      }

      if ((i + 1) % 20 === 0) console.log(`  ${i + 1}/${candidates.length} procesadas`);
    } catch (err) {
      failed++;
      console.warn(
        `  No se pudo importar "${movie.title}" (${movie.id}): ${(err as Error).message}`,
      );
    }
  });

  console.log(
    `Listo: ${created} nuevas, ${updated} actualizadas, ${translated} sinopsis traducidas automáticamente, ${failed} con error.`,
  );
}

main()
  .catch((err) => {
    console.error(err);
    process.exitCode = 1;
  })
  .finally(() => prisma.$disconnect());
