import { PrismaClient, type Role } from '@prisma/client';
import { GENRES, LIKES_PER_POINT } from '@nexohub/shared';
import { hashPassword } from '../../src/lib/password';

const prisma = new PrismaClient();

const DEMO_USERS: { name: string; username: string; email: string; bio: string; role: Role }[] = [
  { name: 'Admin NexoHub', username: 'admin', email: 'admin@nexohub.local', bio: 'Cuenta administradora para pruebas.', role: 'ADMIN' },
  { name: 'Super Admin', username: 'superadmin', email: 'superadmin@nexohub.local', bio: 'Cuenta superadmin para administración completa.', role: 'SUPERADMIN' },
  { name: 'Carlos Perez', username: 'carlosp', email: 'carlos@nexohub.local', bio: 'Fan del cine de aventura y acción.', role: 'USER' },
  { name: 'Ana Gomez', username: 'anagomez', email: 'ana@nexohub.local', bio: 'Reseñas de drama, romance y comedia.', role: 'USER' },
  { name: 'Maria Lopez', username: 'marial', email: 'maria@nexohub.local', bio: 'Me gustan las historias con misterio.', role: 'USER' },
];

const CATALOG: Record<string, { id: string; title: string; year?: number; director?: string }[]> = {
  accion: [
    { id: 'tt0468569', title: 'The Dark Knight', year: 2008, director: 'Christopher Nolan' },
    { id: 'tt2911666', title: 'John Wick', year: 2014, director: 'Chad Stahelski' },
    { id: 'tt1392190', title: 'Mad Max: Fury Road', year: 2015, director: 'George Miller' },
    { id: 'tt4154756', title: 'Avengers: Infinity War', year: 2018, director: 'Anthony & Joe Russo' },
    { id: 'tt1745960', title: 'Top Gun: Maverick', year: 2022, director: 'Joseph Kosinski' },
  ],
  aventura: [
    { id: 'tt0082971', title: 'Raiders of the Lost Ark', year: 1981, director: 'Steven Spielberg' },
    { id: 'tt0107290', title: 'Jurassic Park', year: 1993, director: 'Steven Spielberg' },
    { id: 'tt0325980', title: 'Pirates of the Caribbean: The Curse of the Black Pearl', year: 2003, director: 'Gore Verbinski' },
    { id: 'tt0454876', title: 'Life of Pi', year: 2012, director: 'Ang Lee' },
    { id: 'tt1160419', title: 'Dune', year: 2021, director: 'Denis Villeneuve' },
  ],
  animacion: [
    { id: 'tt0114709', title: 'Toy Story', year: 1995, director: 'John Lasseter' },
    { id: 'tt0910970', title: 'WALL-E', year: 2008, director: 'Andrew Stanton' },
    { id: 'tt2096673', title: 'Inside Out', year: 2015, director: 'Pete Docter' },
    { id: 'tt2948356', title: 'Zootopia', year: 2016, director: 'Byron Howard' },
    { id: 'tt4633694', title: 'Spider-Man: Into the Spider-Verse', year: 2018, director: 'Bob Persichetti' },
  ],
  comedia: [
    { id: 'tt0107048', title: 'Groundhog Day', year: 1993, director: 'Harold Ramis' },
    { id: 'tt0118715', title: 'The Big Lebowski', year: 1998, director: 'Joel Coen' },
    { id: 'tt0377092', title: 'Mean Girls', year: 2004, director: 'Mark Waters' },
    { id: 'tt0829482', title: 'Superbad', year: 2007, director: 'Greg Mottola' },
    { id: 'tt2278388', title: 'The Grand Budapest Hotel', year: 2014, director: 'Wes Anderson' },
  ],
  crimen: [
    { id: 'tt0110912', title: 'Pulp Fiction', year: 1994, director: 'Quentin Tarantino' },
    { id: 'tt0068646', title: 'The Godfather', year: 1972, director: 'Francis Ford Coppola' },
    { id: 'tt0114814', title: 'The Usual Suspects', year: 1995, director: 'Bryan Singer' },
    { id: 'tt0407887', title: 'The Departed', year: 2006, director: 'Martin Scorsese' },
    { id: 'tt0114369', title: 'Se7en', year: 1995, director: 'David Fincher' },
  ],
  documental: [
    { id: 'tt11464826', title: 'The Social Dilemma', year: 2020, director: 'Jeff Orlowski' },
    { id: 'tt2375605', title: 'The Act of Killing', year: 2012, director: 'Joshua Oppenheimer' },
    { id: 'tt2545118', title: 'Blackfish', year: 2013, director: 'Gabriela Cowperthwaite' },
    { id: 'tt1286537', title: 'Food, Inc.', year: 2008, director: 'Robert Kenner' },
    { id: 'tt7775622', title: 'Free Solo', year: 2018, director: 'Jimmy Chin, Elizabeth Chai Vasarhelyi' },
  ],
  drama: [
    { id: 'tt0111161', title: 'The Shawshank Redemption', year: 1994, director: 'Frank Darabont' },
    { id: 'tt0109830', title: 'Forrest Gump', year: 1994, director: 'Robert Zemeckis' },
    { id: 'tt2582802', title: 'Whiplash', year: 2014, director: 'Damien Chazelle' },
    { id: 'tt4975722', title: 'Moonlight', year: 2016, director: 'Barry Jenkins' },
    { id: 'tt7653254', title: 'Marriage Story', year: 2019, director: 'Noah Baumbach' },
  ],
  fantasia: [
    { id: 'tt0241527', title: "Harry Potter and the Sorcerer's Stone", year: 2001, director: 'Chris Columbus' },
    { id: 'tt0167260', title: 'The Lord of the Rings: The Return of the King', year: 2003, director: 'Peter Jackson' },
    { id: 'tt0327597', title: 'Coraline', year: 2009, director: 'Henry Selick' },
    { id: 'tt1677720', title: 'Ready Player One', year: 2018, director: 'Steven Spielberg' },
    { id: 'tt2771200', title: 'Beauty and the Beast', year: 2017, director: 'Bill Condon' },
  ],
  terror: [
    { id: 'tt7784604', title: 'Hereditary', year: 2018, director: 'Ari Aster' },
    { id: 'tt5052448', title: 'Get Out', year: 2017, director: 'Jordan Peele' },
    { id: 'tt1457767', title: 'The Conjuring', year: 2013, director: 'James Wan' },
    { id: 'tt0070047', title: 'The Exorcist', year: 1973, director: 'William Friedkin' },
    { id: 'tt0081505', title: 'The Shining', year: 1980, director: 'Stanley Kubrick' },
  ],
  misterio: [
    { id: 'tt1130884', title: 'Shutter Island', year: 2010, director: 'Martin Scorsese' },
    { id: 'tt0209144', title: 'Memento', year: 2000, director: 'Christopher Nolan' },
    { id: 'tt0482571', title: 'The Prestige', year: 2006, director: 'Christopher Nolan' },
    { id: 'tt2267998', title: 'Gone Girl', year: 2014, director: 'David Fincher' },
    { id: 'tt1305806', title: 'The Secret in Their Eyes', year: 2009, director: 'Juan José Campanella' },
  ],
  romance: [
    { id: 'tt3783958', title: 'La La Land', year: 2016, director: 'Damien Chazelle' },
    { id: 'tt0414387', title: 'Pride & Prejudice', year: 2005, director: 'Joe Wright' },
    { id: 'tt0112471', title: 'Before Sunrise', year: 1995, director: 'Richard Linklater' },
    { id: 'tt0332280', title: 'The Notebook', year: 2004, director: 'Nick Cassavetes' },
    { id: 'tt0338013', title: 'Eternal Sunshine of the Spotless Mind', year: 2004, director: 'Michel Gondry' },
  ],
  'ciencia-ficcion': [
    { id: 'tt1375666', title: 'Inception', year: 2010, director: 'Christopher Nolan' },
    { id: 'tt0816692', title: 'Interstellar', year: 2014, director: 'Christopher Nolan' },
    { id: 'tt0083658', title: 'Blade Runner', year: 1982, director: 'Ridley Scott' },
    { id: 'tt0133093', title: 'The Matrix', year: 1999, director: 'Lana & Lilly Wachowski' },
    { id: 'tt2543164', title: 'Arrival', year: 2016, director: 'Denis Villeneuve' },
  ],
  thriller: [
    { id: 'tt6751668', title: 'Parasite', year: 2019, director: 'Bong Joon Ho' },
    { id: 'tt0364569', title: 'Oldboy', year: 2003, director: 'Park Chan-wook' },
    { id: 'tt1392214', title: 'Prisoners', year: 2013, director: 'Denis Villeneuve' },
    { id: 'tt0102926', title: 'The Silence of the Lambs', year: 1991, director: 'Jonathan Demme' },
    { id: 'tt0477348', title: 'No Country for Old Men', year: 2007, director: 'Joel & Ethan Coen' },
  ],
};

async function fetchCinemeta(imdbId: string) {
  try {
    const res = await fetch(`https://v3-cinemeta.strem.io/meta/movie/${encodeURIComponent(imdbId)}.json`, {
      signal: AbortSignal.timeout(4000),
    });
    if (!res.ok) return null;
    const json = (await res.json()) as { meta?: { description?: string; poster?: string; year?: string; director?: string[] } };
    return json.meta || null;
  } catch {
    return null;
  }
}

async function main() {
  console.log('Sembrando base de datos...');

  // 1. Géneros
  const genreMap = new Map<string, number>();
  for (const g of GENRES) {
    const row = await prisma.genre.upsert({
      where: { slug: g.slug },
      update: { name: g.name },
      create: { slug: g.slug, name: g.name },
    });
    genreMap.set(g.slug, row.id);
  }
  console.log(`  Géneros listos: ${GENRES.length}`);

  // 2. Usuarios
  const passwordHash = await hashPassword('password');
  const userRows = [];
  for (const u of DEMO_USERS) {
    const row = await prisma.user.upsert({
      where: { email: u.email },
      update: { name: u.name, username: u.username, bio: u.bio, role: u.role },
      create: { ...u, passwordHash },
    });
    userRows.push(row);
  }
  console.log(`  Usuarios demo: ${userRows.length}`);

  const normalUsers = userRows.filter((u) => u.role === 'USER');
  const adminUser = userRows.find((u) => u.role === 'ADMIN') || userRows[0]!;

  // 3. Catálogo de películas
  console.log('  Cargando catálogo cinematográfico...');
  let movieCount = 0;
  let reviewCount = 0;

  for (const [genreSlug, list] of Object.entries(CATALOG)) {
    const genreId = genreMap.get(genreSlug);
    if (!genreId) continue;

    for (const item of list) {
      const meta = await fetchCinemeta(item.id);

      const title = item.title;
      const releaseYear = item.year ?? (meta?.year ? parseInt(meta.year, 10) : 2015);
      const director = item.director ?? (meta?.director?.[0] || 'Director cinematográfico');
      const synopsis = meta?.description || `Excelente obra del género cinematográfico. Recomendada para toda la comunidad cinéfila.`;
      const posterUrl = meta?.poster || `https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=600&auto=format&fit=crop&q=80`;

      const movie = await prisma.movie.upsert({
        where: {
          externalSource_externalId: {
            externalSource: 'nexohub_real',
            externalId: item.id,
          },
        },
        create: {
          title,
          genreId,
          director,
          releaseYear,
          synopsis,
          posterUrl,
          externalSource: 'nexohub_real',
          externalId: item.id,
          status: 'APPROVED',
          proposedById: adminUser.id,
        },
        update: {
          title,
          director,
          releaseYear,
          synopsis,
          posterUrl,
          status: 'APPROVED',
        },
      });

      movieCount++;

      // Crear reseña de prueba por cada usuario normal para poblar rating y opiniones
      const reviewer = normalUsers[(movieCount - 1) % normalUsers.length]!;
      const rating = 4 + ((movieCount % 2) as number); // 4 o 5 estrellas

      const review = await prisma.review.upsert({
        where: {
          userId_movieId: { userId: reviewer.id, movieId: movie.id },
        },
        create: {
          userId: reviewer.id,
          movieId: movie.id,
          rating,
          content: `Una joya indiscutible de su género. Las actuaciones y la dirección de ${director} destacan de principio a fin. Totalmente recomendada.`,
        },
        update: {
          rating,
        },
      });

      reviewCount++;

      // Simular respuestas y likes para dar reputación y debate
      const otherUsers = normalUsers.filter((u) => u.id !== reviewer.id);
      for (const other of otherUsers) {
        await prisma.reviewLike.upsert({
          where: {
            userId_reviewId: { userId: other.id, reviewId: review.id },
          },
          create: { userId: other.id, reviewId: review.id },
          update: {},
        });
      }

      await prisma.reviewResponse.createMany({
        data: [
          {
            reviewId: review.id,
            userId: otherUsers[0]!.id,
            content: 'Totalmente de acuerdo con tu análisis. La cinematografía es brillante.',
          },
        ],
        skipDuplicates: true,
      });

      // Recalcular métricas de la película
      const agg = await prisma.review.aggregate({
        where: { movieId: movie.id },
        _avg: { rating: true },
        _count: { id: true },
      });
      const likesAgg = await prisma.reviewLike.count({ where: { reviewId: review.id } });

      await prisma.review.update({
        where: { id: review.id },
        data: { likesCount: likesAgg },
      });

      await prisma.movie.update({
        where: { id: movie.id },
        data: {
          ratingAvg: agg._avg.rating ?? rating,
          reviewsCount: agg._count.id,
        },
      });
    }
  }

  // 4. Recalcular reputación por género y reputación global para los usuarios
  for (const u of normalUsers) {
    for (const [, genreId] of genreMap.entries()) {
      const likesCount = await prisma.reviewLike.count({
        where: {
          review: {
            userId: u.id,
            movie: { genreId },
          },
        },
      });

      const points = Math.floor(likesCount / LIKES_PER_POINT);

      await prisma.genreReputation.upsert({
        where: { userId_genreId: { userId: u.id, genreId } },
        create: { userId: u.id, genreId, likesReceived: likesCount, points },
        update: { likesReceived: likesCount, points },
      });
    }

    const totalPoints = await prisma.genreReputation.aggregate({
      where: { userId: u.id },
      _sum: { points: true },
    });

    await prisma.user.update({
      where: { id: u.id },
      data: { reputation: totalPoints._sum.points ?? 0 },
    });
  }

  console.log(`Catálogo completo listo: ${movieCount} películas, ${reviewCount} reseñas con likes y respuestas.`);
}

main()
  .catch((err) => {
    console.error(err);
    process.exitCode = 1;
  })
  .finally(() => prisma.$disconnect());
