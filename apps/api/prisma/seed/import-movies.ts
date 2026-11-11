/**
 * Suma al catálogo las películas más recientes que todavía no están en la base.
 *
 *   npm run db:import-movies -w @nexohub/api                          # 40 nuevas, años actual y anterior
 *   npm run db:import-movies -w @nexohub/api -- --count 100 --years 3
 *
 * Fuente: Cinemeta (sin clave). Si hay TMDB_API_KEY en .env, usa TMDB.
 * Sinopsis: se traducen con MyMemory; si se agota el cupo diario quedan en inglés
 * y se traducen solas en la próxima corrida.
 */
import { prisma } from '../../src/lib/prisma';
import { importLatestMovies } from '../../src/modules/movies/catalog-import.service';

function arg(name: string, fallback: number): number {
  const i = process.argv.indexOf(`--${name}`);
  const value = i >= 0 ? Number(process.argv[i + 1]) : Number.NaN;
  return Number.isInteger(value) && value > 0 ? value : fallback;
}

const count = arg('count', 40);
const years = arg('years', 2);

console.log(`Buscando hasta ${count} películas nuevas (últimos ${years} años)...`);

importLatestMovies({ count, years }, console.log)
  .then((r) => {
    console.log(
      `Listo: ${r.created} nuevas, ${r.translatedPending} sinopsis pendientes traducidas, ` +
        `${r.pendingTranslation} siguen en inglés, ${r.failed} con error.`,
    );
    if (r.pendingTranslation > 0) {
      console.log('Las sinopsis en inglés se traducen solas en la próxima corrida (cupo diario de MyMemory).');
    }
  })
  .catch((err) => {
    console.error((err as Error).message);
    process.exitCode = 1;
  })
  .finally(() => prisma.$disconnect());
