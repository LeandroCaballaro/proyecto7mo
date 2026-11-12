/**
 * Seed de comunidad: usuarios ficticios con reseñas, respuestas, likes y favoritas,
 * y reputación recalculada para que aparezcan en el ranking global y por género.
 *
 *   npm run db:seed-community -w @nexohub/api                 # 120 usuarios
 *   npm run db:seed-community -w @nexohub/api -- --users 200
 *
 * Es idempotente: borra los usuarios de comunidad anteriores (email @comunidad.nexohub.local)
 * y los vuelve a crear con los mismos datos (generador aleatorio con semilla fija).
 * Contraseña de todas las cuentas: SEED_DEMO_PASSWORD (por defecto "password").
 */
import 'dotenv/config';
import { PrismaClient } from '@prisma/client';
import { hashPassword } from '../../src/lib/password';

const prisma = new PrismaClient();
const EMAIL_DOMAIN = 'comunidad.nexohub.local';

function arg(name: string, fallback: number): number {
  const i = process.argv.indexOf(`--${name}`);
  const value = i >= 0 ? Number(process.argv[i + 1]) : Number.NaN;
  return Number.isInteger(value) && value > 0 ? value : fallback;
}

// ───────────────────────────── Aleatorio reproducible ─────────────────────────────

function mulberry32(seed: number) {
  return () => {
    seed |= 0;
    seed = (seed + 0x6d2b79f5) | 0;
    let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}
const rand = mulberry32(20261111);
const int = (min: number, max: number) => min + Math.floor(rand() * (max - min + 1));
const pick = <T>(list: readonly T[]): T => list[Math.floor(rand() * list.length)]!;
const chance = (p: number) => rand() < p;
function sample<T>(list: readonly T[], n: number): T[] {
  const copy = [...list];
  for (let i = copy.length - 1; i > 0; i--) {
    const j = Math.floor(rand() * (i + 1));
    [copy[i], copy[j]] = [copy[j]!, copy[i]!];
  }
  return copy.slice(0, Math.min(n, copy.length));
}
const DAY = 86_400_000;
const randomDate = (daysBack: number, after?: Date) => {
  const from = after ? after.getTime() : Date.now() - daysBack * DAY;
  return new Date(from + rand() * (Date.now() - from));
};

// ───────────────────────────── Datos base ─────────────────────────────

const FIRST_NAMES = [
  'Sofía',
  'Martina',
  'Valentina',
  'Camila',
  'Lucía',
  'Julieta',
  'Agustina',
  'Florencia',
  'Micaela',
  'Carolina',
  'Paula',
  'Victoria',
  'Milagros',
  'Rocío',
  'Abril',
  'Catalina',
  'Delfina',
  'Antonella',
  'Brenda',
  'Daniela',
  'Mateo',
  'Santiago',
  'Benjamín',
  'Joaquín',
  'Tomás',
  'Lautaro',
  'Facundo',
  'Nicolás',
  'Matías',
  'Gonzalo',
  'Franco',
  'Agustín',
  'Ignacio',
  'Federico',
  'Lucas',
  'Martín',
  'Thiago',
  'Bruno',
  'Emiliano',
  'Ramiro',
  'Ezequiel',
  'Leandro',
  'Sebastián',
  'Diego',
  'Juan',
];
const LAST_NAMES = [
  'González',
  'Rodríguez',
  'Gómez',
  'Fernández',
  'López',
  'Díaz',
  'Martínez',
  'Pérez',
  'García',
  'Sánchez',
  'Romero',
  'Sosa',
  'Álvarez',
  'Torres',
  'Ruiz',
  'Ramírez',
  'Flores',
  'Benítez',
  'Acosta',
  'Medina',
  'Herrera',
  'Suárez',
  'Aguirre',
  'Giménez',
  'Gutiérrez',
  'Pereyra',
  'Rojas',
  'Molina',
  'Castro',
  'Ortiz',
  'Silva',
  'Núñez',
  'Luna',
  'Juárez',
  'Cabrera',
  'Ríos',
  'Morales',
  'Godoy',
];

const BIOS: Record<string, string[]> = {
  accion: [
    'Si hay persecuciones y explosiones, ahí estoy.',
    'Fan de las coreografías de pelea bien filmadas.',
  ],
  aventura: [
    'Viajo con cada película de aventuras.',
    'Busco historias con mapas, tesoros y grandes viajes.',
  ],
  animacion: [
    'La animación no es solo para chicos.',
    'Fan del cine animado de todos los estudios.',
  ],
  comedia: ['Si me hace reír, ya ganó.', 'Coleccionista de comedias para ver en grupo.'],
  crimen: ['Me encantan los policiales con giros.', 'Analizo cada caso como si fuera detective.'],
  documental: ['Documentales para entender el mundo.', 'Prefiero las historias reales.'],
  drama: [
    'Las historias que te dejan pensando son lo mío.',
    'Lloro con cada drama y no me arrepiento.',
  ],
  fantasia: ['Mundos mágicos y criaturas imposibles.', 'Siempre listo para otra saga fantástica.'],
  terror: ['Veo terror con la luz apagada.', 'Coleccionista de sustos y finales perturbadores.'],
  misterio: ['Nunca adivino el final y me encanta.', 'Fan de los misterios bien construidos.'],
  romance: ['Defensora de las comedias románticas.', 'Una buena historia de amor nunca falla.'],
  'ciencia-ficcion': [
    'El futuro, el espacio y todo lo que viene.',
    'Ciencia ficción dura y de la otra.',
  ],
  thriller: ['Tensión de principio a fin, por favor.', 'Fan de los thrillers psicológicos.'],
};

const OPENINGS: Record<number, string[]> = {
  5: [
    'Una obra maestra.',
    'Impecable de principio a fin.',
    'De lo mejor que vi en mucho tiempo.',
    'Me voló la cabeza.',
  ],
  4: [
    'Muy buena película.',
    'Me gustó mucho.',
    'Sólida y entretenida.',
    'Supera las expectativas.',
  ],
  3: [
    'Está bien, sin más.',
    'Correcta, aunque le falta algo.',
    'Entretiene, pero no deslumbra.',
    'Tiene buenos momentos.',
  ],
  2: ['Me esperaba bastante más.', 'Floja en varios aspectos.', 'No terminó de convencerme.'],
  1: ['No la recomiendo.', 'Una decepción total.', 'Difícil de terminar.'],
};
const GENRE_LINES: Record<string, string[]> = {
  accion: [
    'Las escenas de acción están muy bien coreografiadas.',
    'El ritmo no da respiro.',
    'Las secuencias de persecución son espectaculares.',
  ],
  aventura: [
    'Te lleva de viaje desde la primera escena.',
    'Los paisajes y la escala son impresionantes.',
    'Tiene ese espíritu de aventura clásica.',
  ],
  animacion: [
    'La animación es preciosa en cada cuadro.',
    'Funciona tanto para chicos como para grandes.',
    'El diseño de personajes es muy creativo.',
  ],
  comedia: [
    'Los chistes funcionan casi todos.',
    'Tiene un humor muy bien medido.',
    'Me reí de verdad en varias escenas.',
  ],
  crimen: [
    'La trama policial está muy bien armada.',
    'Los personajes son ambiguos y eso suma.',
    'Cada pista encaja al final.',
  ],
  documental: [
    'Está muy bien investigado.',
    'Te hace replantear muchas cosas.',
    'Las entrevistas son muy potentes.',
  ],
  drama: [
    'Las actuaciones son de otro nivel.',
    'Emocionalmente es muy intensa.',
    'Los diálogos se sienten muy reales.',
  ],
  fantasia: [
    'El mundo que construye es fascinante.',
    'Los efectos acompañan sin opacar la historia.',
    'Tiene una mitología propia muy rica.',
  ],
  terror: [
    'Genera una tensión constante.',
    'Los sustos están bien ganados, no son baratos.',
    'La atmósfera es muy perturbadora.',
  ],
  misterio: [
    'No pude adivinar el final.',
    'Las pistas están bien distribuidas.',
    'Te mantiene pensando todo el tiempo.',
  ],
  romance: [
    'La química entre los protagonistas es enorme.',
    'Es tierna sin caer en lo empalagoso.',
    'La historia de amor se siente sincera.',
  ],
  'ciencia-ficcion': [
    'Las ideas que plantea son muy interesantes.',
    'El diseño visual es increíble.',
    'Combina bien ciencia y emoción.',
  ],
  thriller: [
    'La tensión crece escena a escena.',
    'Tiene giros que no ves venir.',
    'Te deja al borde del asiento.',
  ],
};
const DIRECTOR_LINES = [
  'Se nota la mano de {director} en cada decisión.',
  '{director} demuestra otra vez por qué es tan respetado.',
  'La dirección de {director} es muy precisa.',
];
const CLOSINGS: Record<number, string[]> = {
  5: ['Imperdible.', 'La volvería a ver mañana.', 'Recomendadísima.'],
  4: ['Muy recomendable.', 'Vale totalmente la pena.', 'Para ver sí o sí.'],
  3: ['Para una tarde tranquila está bien.', 'Se deja ver.', 'Puede gustar a los fans del género.'],
  2: ['Solo para fans muy fieles.', 'Hay mejores opciones.'],
  1: ['Mejor buscar otra.', 'No vale la pena.'],
};
const RESPONSES = [
  'Totalmente de acuerdo, me pasó lo mismo.',
  'Muy buena reseña, me dieron ganas de verla.',
  'Coincido en casi todo, aunque a mí el final no me cerró.',
  'Qué buen análisis, no lo había pensado así.',
  'Yo le hubiera puesto una estrella más.',
  'La vi por tu recomendación y no me arrepiento.',
  'Pienso distinto, a mí me pareció bastante floja.',
  'Gran reseña. ¿Viste otras del mismo director?',
  'Exacto, las actuaciones son lo mejor.',
  'Me la anoto para el fin de semana.',
];
const REPLIES = [
  'Sí, es de las mejores en su estilo.',
  'Jaja, entiendo, es muy de gustos.',
  'Gracias, me alegra que te haya servido.',
  'Tenés razón, el final es discutible.',
  'Te recomiendo también las anteriores.',
];

function reviewText(rating: number, genreSlug: string, director: string | null): string {
  const parts = [
    pick(OPENINGS[rating]!),
    ...sample(GENRE_LINES[genreSlug] ?? GENRE_LINES.drama!, int(1, 2)),
  ];
  if (director && chance(0.4))
    parts.push(pick(DIRECTOR_LINES).replace('{director}', director.split(',')[0]!));
  parts.push(pick(CLOSINGS[rating]!));
  return parts.join(' ');
}

// Calificaciones sesgadas hacia 3-5, como en una comunidad real.
const ratingFor = (affinity: boolean) => {
  const r = rand();
  if (affinity) return r < 0.45 ? 5 : r < 0.8 ? 4 : r < 0.95 ? 3 : 2;
  return r < 0.25 ? 5 : r < 0.6 ? 4 : r < 0.85 ? 3 : r < 0.95 ? 2 : 1;
};

const slugify = (s: string) =>
  s
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[^a-zA-Z0-9]/g, '')
    .toLowerCase();

// ───────────────────────────── Seed ─────────────────────────────

async function main() {
  const TOTAL = arg('users', 120);
  console.log(`Sembrando comunidad: ${TOTAL} usuarios...`);

  const movies = await prisma.movie.findMany({
    where: { status: 'APPROVED' },
    select: { id: true, director: true, genre: { select: { slug: true } } },
  });
  if (movies.length < 10) {
    throw new Error('Hay muy pocas películas aprobadas. Corré antes db:seed y db:import-movies.');
  }
  const bySlug = new Map<string, typeof movies>();
  for (const m of movies) bySlug.set(m.genre.slug, [...(bySlug.get(m.genre.slug) ?? []), m]);
  const slugs = [...bySlug.keys()];

  // 1. Limpieza de una corrida anterior (borra en cascada reseñas, likes, respuestas y favoritas).
  const removed = await prisma.user.deleteMany({
    where: { email: { endsWith: `@${EMAIL_DOMAIN}` } },
  });
  if (removed.count > 0) console.log(`  Comunidad anterior eliminada: ${removed.count} usuarios`);

  // 2. Usuarios
  const passwordHash = await hashPassword(process.env.SEED_DEMO_PASSWORD || 'password');
  const taken = new Set(
    (await prisma.user.findMany({ select: { username: true } })).map((u) => u.username),
  );
  const profiles: { favGenres: string[]; quality: number }[] = [];
  const usersData = [];
  for (let i = 0; i < TOTAL; i++) {
    const first = pick(FIRST_NAMES);
    const last = pick(LAST_NAMES);
    let username = `${slugify(first)}${slugify(last)}`.slice(0, 16);
    while (taken.has(username)) username = `${username.slice(0, 16)}${int(1, 999)}`;
    taken.add(username);
    const favGenres = sample(slugs, int(1, 3));
    profiles.push({ favGenres, quality: 0.3 + rand() * 0.7 });
    usersData.push({
      name: `${first} ${last}`,
      username,
      email: `${username}@${EMAIL_DOMAIN}`,
      passwordHash,
      bio: pick(BIOS[favGenres[0]!] ?? BIOS.drama!),
      isPublic: chance(0.92),
      role: 'USER' as const,
      createdAt: randomDate(365),
    });
  }
  await prisma.user.createMany({ data: usersData });
  const users = await prisma.user.findMany({
    where: { email: { endsWith: `@${EMAIL_DOMAIN}` } },
    orderBy: { id: 'asc' },
    select: { id: true, username: true, createdAt: true },
  });
  const profileOf = new Map(users.map((u, i) => [u.id, profiles[i]!]));
  console.log(`  Usuarios: ${users.length}`);

  // 3. Reseñas: cada usuario reseña sobre todo sus géneros favoritos.
  const reviewsData = [];
  for (const u of users) {
    const profile = profileOf.get(u.id)!;
    const own = profile.favGenres.flatMap((s) => bySlug.get(s) ?? []);
    const chosen = new Map<number, (typeof movies)[number]>();
    for (const m of sample(own, int(5, 12))) chosen.set(m.id, m);
    for (const m of sample(movies, int(2, 6))) chosen.set(m.id, m);
    for (const m of chosen.values()) {
      const affinity = profile.favGenres.includes(m.genre.slug);
      const rating = ratingFor(affinity);
      reviewsData.push({
        userId: u.id,
        movieId: m.id,
        rating,
        content: reviewText(rating, m.genre.slug, m.director),
        createdAt: randomDate(0, u.createdAt),
      });
    }
  }
  await prisma.review.createMany({ data: reviewsData, skipDuplicates: true });
  const reviews = await prisma.review.findMany({
    where: { user: { email: { endsWith: `@${EMAIL_DOMAIN}` } } },
    select: {
      id: true,
      userId: true,
      createdAt: true,
      movie: { select: { genre: { select: { slug: true } } } },
    },
  });
  console.log(`  Reseñas: ${reviews.length}`);

  // 4. Likes: las reseñas de usuarios "de calidad" y en sus géneros favoritos reciben más.
  const likesData: { userId: number; reviewId: number; createdAt: Date }[] = [];
  const userIds = users.map((u) => u.id);
  for (const r of reviews) {
    const profile = profileOf.get(r.userId)!;
    const specialist = profile.favGenres.includes(r.movie.genre.slug);
    const target = Math.round(profile.quality * (specialist ? int(8, 30) : int(0, 10)));
    for (const likerId of sample(userIds, target + 1)) {
      if (likerId === r.userId || likesData.length > 60_000) continue;
      likesData.push({ userId: likerId, reviewId: r.id, createdAt: randomDate(0, r.createdAt) });
    }
  }
  for (let i = 0; i < likesData.length; i += 5000) {
    await prisma.reviewLike.createMany({
      data: likesData.slice(i, i + 5000),
      skipDuplicates: true,
    });
  }
  console.log(`  Likes: ${likesData.length}`);

  // 5. Respuestas y respuestas a respuestas.
  const responsesData = [];
  for (const r of reviews) {
    if (!chance(0.35)) continue;
    for (const authorId of sample(
      userIds.filter((id) => id !== r.userId),
      int(1, 3),
    )) {
      responsesData.push({
        reviewId: r.id,
        userId: authorId,
        content: pick(RESPONSES),
        createdAt: randomDate(0, r.createdAt),
      });
    }
  }
  await prisma.reviewResponse.createMany({ data: responsesData });
  const firstLevel = await prisma.reviewResponse.findMany({
    where: { parentId: null, user: { email: { endsWith: `@${EMAIL_DOMAIN}` } } },
    select: { id: true, reviewId: true, createdAt: true, review: { select: { userId: true } } },
  });
  const repliesData = firstLevel
    .filter(() => chance(0.3))
    .map((resp) => ({
      reviewId: resp.reviewId,
      parentId: resp.id,
      userId: resp.review.userId, // responde el autor de la reseña
      content: pick(REPLIES),
      createdAt: randomDate(0, resp.createdAt),
    }));
  await prisma.reviewResponse.createMany({ data: repliesData });
  console.log(`  Respuestas: ${responsesData.length + repliesData.length}`);

  // 6. Favoritas: películas mejor calificadas por cada usuario y alguna más de sus géneros.
  const favoritesData: { userId: number; movieId: number; createdAt: Date }[] = [];
  const byUser = new Map<number, typeof reviewsData>();
  for (const r of reviewsData) byUser.set(r.userId, [...(byUser.get(r.userId) ?? []), r]);
  for (const u of users) {
    const loved = (byUser.get(u.id) ?? []).filter((r) => r.rating >= 4).map((r) => r.movieId);
    for (const movieId of sample(loved, int(2, 8))) {
      favoritesData.push({ userId: u.id, movieId, createdAt: randomDate(0, u.createdAt) });
    }
  }
  await prisma.favoriteMovie.createMany({ data: favoritesData, skipDuplicates: true });
  console.log(`  Favoritas: ${favoritesData.length}`);

  // 7. Recalcular métricas y reputación de TODA la base (incluye usuarios demo y reales).
  await prisma.$transaction([
    prisma.$executeRawUnsafe(
      'UPDATE reviews r SET likes_count = (SELECT COUNT(*) FROM review_likes l WHERE l.review_id = r.id)',
    ),
    prisma.$executeRawUnsafe(
      `UPDATE movies m SET
         reviews_count = (SELECT COUNT(*) FROM reviews r WHERE r.movie_id = m.id),
         rating_avg = COALESCE((SELECT ROUND(AVG(r.rating), 2) FROM reviews r WHERE r.movie_id = m.id), 0)`,
    ),
    prisma.$executeRawUnsafe('DELETE FROM genre_reputation'),
    prisma.$executeRawUnsafe(
      `INSERT INTO genre_reputation (user_id, genre_id, likes_received, points, updated_at)
       SELECT r.user_id, m.genre_id, COUNT(*), FLOOR(COUNT(*) / 10), NOW(3)
       FROM review_likes l
       JOIN reviews r ON r.id = l.review_id
       JOIN movies m ON m.id = r.movie_id
       GROUP BY r.user_id, m.genre_id`,
    ),
    prisma.$executeRawUnsafe(
      `UPDATE users u SET reputation = COALESCE(
         (SELECT SUM(g.points) FROM genre_reputation g WHERE g.user_id = u.id), 0)`,
    ),
  ]);

  const ranked = await prisma.user.count({ where: { isPublic: true, reputation: { gt: 0 } } });
  const top = await prisma.user.findMany({
    where: { isPublic: true },
    orderBy: { reputation: 'desc' },
    take: 5,
    select: { username: true, reputation: true },
  });
  console.log(`Listo. Usuarios en el ranking global: ${ranked}.`);
  console.log(`  Top 5: ${top.map((u) => `${u.username} (${u.reputation})`).join(', ')}`);
}

main()
  .catch((err) => {
    console.error((err as Error).message);
    process.exitCode = 1;
  })
  .finally(() => prisma.$disconnect());
