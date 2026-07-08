<?php

require_once __DIR__ . '/../models/Database.php';

$pdo = Database::getInstance()->getConnection();
$source = 'nexohub_real';

$catalog = [
    'Accion' => [
        ['id' => 'tt0468569', 'title' => 'The Dark Knight'],
        ['id' => 'tt2911666', 'title' => 'John Wick'],
        ['id' => 'tt1392190', 'title' => 'Mad Max: Fury Road'],
        ['id' => 'tt4154756', 'title' => 'Avengers: Infinity War'],
        ['id' => 'tt1745960', 'title' => 'Top Gun: Maverick'],
    ],
    'Aventura' => [
        ['id' => 'tt0082971', 'title' => 'Raiders of the Lost Ark'],
        ['id' => 'tt0107290', 'title' => 'Jurassic Park'],
        ['id' => 'tt0325980', 'title' => 'Pirates of the Caribbean: The Curse of the Black Pearl'],
        ['id' => 'tt0454876', 'title' => 'Life of Pi'],
        ['id' => 'tt1160419', 'title' => 'Dune'],
    ],
    'Animacion' => [
        ['id' => 'tt0114709', 'title' => 'Toy Story'],
        ['id' => 'tt0910970', 'title' => 'WALL-E'],
        ['id' => 'tt2096673', 'title' => 'Inside Out'],
        ['id' => 'tt2948356', 'title' => 'Zootopia'],
        ['id' => 'tt4633694', 'title' => 'Spider-Man: Into the Spider-Verse'],
    ],
    'Comedia' => [
        ['id' => 'tt0107048', 'title' => 'Groundhog Day'],
        ['id' => 'tt0118715', 'title' => 'The Big Lebowski'],
        ['id' => 'tt0377092', 'title' => 'Mean Girls'],
        ['id' => 'tt0829482', 'title' => 'Superbad'],
        ['id' => 'tt2278388', 'title' => 'The Grand Budapest Hotel'],
    ],
    'Crimen' => [
        ['id' => 'tt0110912', 'title' => 'Pulp Fiction'],
        ['id' => 'tt0068646', 'title' => 'The Godfather'],
        ['id' => 'tt0114814', 'title' => 'The Usual Suspects'],
        ['id' => 'tt0407887', 'title' => 'The Departed'],
        ['id' => 'tt0114369', 'title' => 'Se7en'],
    ],
    'Documental' => [
        ['id' => 'tt11464826', 'title' => 'The Social Dilemma'],
        ['id' => 'tt2375605', 'title' => 'The Act of Killing'],
        ['id' => 'tt2545118', 'title' => 'Blackfish'],
        ['id' => 'tt1286537', 'title' => 'Food, Inc.'],
        ['id' => 'tt7775622', 'title' => 'Free Solo'],
    ],
    'Drama' => [
        ['id' => 'tt0111161', 'title' => 'The Shawshank Redemption'],
        ['id' => 'tt0109830', 'title' => 'Forrest Gump'],
        ['id' => 'tt2582802', 'title' => 'Whiplash'],
        ['id' => 'tt4975722', 'title' => 'Moonlight'],
        ['id' => 'tt7653254', 'title' => 'Marriage Story'],
    ],
    'Fantasia' => [
        ['id' => 'tt0241527', 'title' => 'Harry Potter and the Sorcerer\'s Stone'],
        ['id' => 'tt0167260', 'title' => 'The Lord of the Rings: The Return of the King'],
        ['id' => 'tt0327597', 'title' => 'Coraline'],
        ['id' => 'tt1677720', 'title' => 'Ready Player One'],
        ['id' => 'tt2771200', 'title' => 'Beauty and the Beast'],
    ],
    'Terror' => [
        ['id' => 'tt7784604', 'title' => 'Hereditary'],
        ['id' => 'tt5052448', 'title' => 'Get Out'],
        ['id' => 'tt1457767', 'title' => 'The Conjuring'],
        ['id' => 'tt0070047', 'title' => 'The Exorcist'],
        ['id' => 'tt0081505', 'title' => 'The Shining'],
    ],
    'Misterio' => [
        ['id' => 'tt1130884', 'title' => 'Shutter Island'],
        ['id' => 'tt0209144', 'title' => 'Memento'],
        ['id' => 'tt0482571', 'title' => 'The Prestige'],
        ['id' => 'tt2267998', 'title' => 'Gone Girl'],
        ['id' => 'tt1305806', 'title' => 'The Secret in Their Eyes'],
    ],
    'Romance' => [
        ['id' => 'tt3783958', 'title' => 'La La Land'],
        ['id' => 'tt0414387', 'title' => 'Pride & Prejudice'],
        ['id' => 'tt0112471', 'title' => 'Before Sunrise'],
        ['id' => 'tt0332280', 'title' => 'The Notebook'],
        ['id' => 'tt0338013', 'title' => 'Eternal Sunshine of the Spotless Mind'],
    ],
    'Ciencia ficcion' => [
        ['id' => 'tt1375666', 'title' => 'Inception'],
        ['id' => 'tt0816692', 'title' => 'Interstellar'],
        ['id' => 'tt0083658', 'title' => 'Blade Runner'],
        ['id' => 'tt0133093', 'title' => 'The Matrix'],
        ['id' => 'tt2543164', 'title' => 'Arrival'],
    ],
    'Thriller' => [
        ['id' => 'tt6751668', 'title' => 'Parasite'],
        ['id' => 'tt0364569', 'title' => 'Oldboy'],
        ['id' => 'tt1392214', 'title' => 'Prisoners'],
        ['id' => 'tt0102926', 'title' => 'The Silence of the Lambs'],
        ['id' => 'tt0477348', 'title' => 'No Country for Old Men'],
    ],
];

$catalogMetadata = [
    'tt0468569' => ['year' => 2008, 'description' => 'Batman enfrenta al Joker, un criminal impredecible que empuja a Gotham al caos y pone a prueba los limites morales de sus heroes.'],
    'tt2911666' => ['year' => 2014, 'description' => 'Un exasesino vuelve al mundo criminal despues de una perdida personal y desata una cadena de venganza contra quienes le quitaron su ultimo refugio.'],
    'tt1392190' => ['year' => 2015, 'description' => 'En un desierto postapocaliptico, Max se une a Furiosa para escapar de un tirano y proteger a un grupo de mujeres que buscan libertad.'],
    'tt4154756' => ['year' => 2018, 'description' => 'Los Avengers y sus aliados intentan detener a Thanos antes de que reuna las Gemas del Infinito y cambie el destino del universo.'],
    'tt1745960' => ['year' => 2022, 'description' => 'Maverick regresa como instructor para preparar a una nueva generacion de pilotos ante una mision aerea de riesgo extremo.'],
    'tt0082971' => ['year' => 1981, 'description' => 'Indiana Jones compite contra agentes nazis para encontrar el Arca de la Alianza en una aventura arqueologica llena de peligros.'],
    'tt0107290' => ['year' => 1993, 'description' => 'Un parque tematico con dinosaurios clonados se convierte en una pesadilla cuando los sistemas de seguridad fallan y las criaturas quedan libres.'],
    'tt0325980' => ['year' => 2003, 'description' => 'El capitan Jack Sparrow y Will Turner se enfrentan a piratas malditos para rescatar a Elizabeth Swann y recuperar un barco legendario.'],
    'tt0454876' => ['year' => 2012, 'description' => 'Tras un naufragio, un joven queda a la deriva en un bote salvavidas junto a un tigre de Bengala y debe sobrevivir en alta mar.'],
    'tt1160419' => ['year' => 2021, 'description' => 'Paul Atreides viaja al planeta Arrakis, donde el control de una especia valiosa desata conflictos politicos, familiares y espirituales.'],
    'tt0114709' => ['year' => 1995, 'description' => 'Los juguetes de Andy cobran vida cuando nadie los mira, y Woody debe aprender a convivir con el nuevo favorito: Buzz Lightyear.'],
    'tt0910970' => ['year' => 2008, 'description' => 'En una Tierra abandonada, un robot recolector de basura descubre una nueva esperanza para la humanidad al conocer a la avanzada EVA.'],
    'tt2096673' => ['year' => 2015, 'description' => 'Las emociones de Riley intentan guiarla durante una mudanza dificil que cambia su forma de entender la infancia y los recuerdos.'],
    'tt2948356' => ['year' => 2016, 'description' => 'Una coneja policia y un zorro astuto investigan una conspiracion que amenaza la convivencia entre depredadores y presas en Zootopia.'],
    'tt4633694' => ['year' => 2018, 'description' => 'Miles Morales descubre sus poderes y se une a versiones alternativas de Spider-Man para salvar su ciudad y su universo.'],
    'tt0107048' => ['year' => 1993, 'description' => 'Un meteorologo arrogante queda atrapado repitiendo el mismo dia y debe cambiar su mirada sobre la vida para avanzar.'],
    'tt0118715' => ['year' => 1998, 'description' => 'Un hombre tranquilo conocido como The Dude se ve arrastrado a un absurdo caso criminal por una confusion de identidad.'],
    'tt0377092' => ['year' => 2004, 'description' => 'Una adolescente educada en casa entra a la secundaria y descubre las reglas sociales de un grupo popular tan atractivo como cruel.'],
    'tt0829482' => ['year' => 2007, 'description' => 'Dos amigos inseparables intentan cerrar la secundaria con una gran fiesta, mientras sus inseguridades ponen a prueba la amistad.'],
    'tt2278388' => ['year' => 2014, 'description' => 'El conserje de un hotel europeo y su joven aprendiz quedan envueltos en una disputa por una herencia, un asesinato y una obra de arte.'],
    'tt0110912' => ['year' => 1994, 'description' => 'Historias de criminales, boxeadores y buscavidas se cruzan en Los Angeles con violencia, humor negro y decisiones inesperadas.'],
    'tt0068646' => ['year' => 1972, 'description' => 'La familia Corleone atraviesa una lucha de poder mientras Michael se transforma de hijo distante en heredero del imperio criminal.'],
    'tt0114814' => ['year' => 1995, 'description' => 'Un sobreviviente relata como un grupo de criminales fue manipulado por la sombra de Keyser Soze tras un golpe fallido.'],
    'tt0407887' => ['year' => 2006, 'description' => 'Un policia encubierto y un infiltrado de la mafia intentan descubrirse mutuamente dentro de una guerra criminal en Boston.'],
    'tt0114369' => ['year' => 1995, 'description' => 'Dos detectives persiguen a un asesino serial que construye sus crimenes alrededor de los siete pecados capitales.'],
    'tt11464826' => ['year' => 2020, 'description' => 'Expertos de la industria tecnologica analizan como las redes sociales moldean la atencion, la conducta y la informacion publica.'],
    'tt2375605' => ['year' => 2012, 'description' => 'Antiguos miembros de escuadrones de la muerte recrean sus crimenes y revelan la impunidad que marco la historia de Indonesia.'],
    'tt2545118' => ['year' => 2013, 'description' => 'El documental examina el cautiverio de orcas en parques acuaticos y las consecuencias fisicas y psicologicas del encierro.'],
    'tt1286537' => ['year' => 2008, 'description' => 'Una mirada critica a la industria alimentaria estadounidense, sus practicas de produccion y su impacto en consumidores y trabajadores.'],
    'tt7775622' => ['year' => 2018, 'description' => 'El escalador Alex Honnold prepara su ascenso sin cuerdas a El Capitan, enfrentando una hazana fisica y mental extrema.'],
    'tt0111161' => ['year' => 1994, 'description' => 'Andy Dufresne mantiene la esperanza durante decadas en prision mientras construye una amistad profunda con Red.'],
    'tt0109830' => ['year' => 1994, 'description' => 'Forrest Gump atraviesa momentos clave de la historia estadounidense guiado por su inocencia, su perseverancia y su amor por Jenny.'],
    'tt2582802' => ['year' => 2014, 'description' => 'Un joven baterista entra en una exigente academia de musica donde un profesor implacable lo empuja al limite.'],
    'tt4975722' => ['year' => 2016, 'description' => 'La vida de Chiron se cuenta en tres etapas mientras busca identidad, afecto y lugar en un entorno marcado por la dureza.'],
    'tt7653254' => ['year' => 2019, 'description' => 'Una pareja atraviesa un divorcio doloroso que expone heridas, afectos y decisiones dificiles sobre familia y futuro.'],
    'tt0241527' => ['year' => 2001, 'description' => 'Harry Potter descubre que es mago y comienza sus estudios en Hogwarts, donde encuentra amistad, secretos y un destino peligroso.'],
    'tt0167260' => ['year' => 2003, 'description' => 'La batalla final por la Tierra Media se acerca mientras Frodo y Sam avanzan hacia Mordor para destruir el Anillo Unico.'],
    'tt0327597' => ['year' => 2009, 'description' => 'Coraline encuentra una version alternativa de su casa que parece perfecta, hasta que descubre el precio oscuro de quedarse alli.'],
    'tt1677720' => ['year' => 2018, 'description' => 'En un futuro dominado por la realidad virtual, un joven compite por controlar OASIS siguiendo las pistas de su creador.'],
    'tt2771200' => ['year' => 2017, 'description' => 'Bella queda prisionera en un castillo encantado y descubre la humanidad escondida bajo la apariencia de una bestia.'],
    'tt7784604' => ['year' => 2018, 'description' => 'Una familia atraviesa un duelo perturbador mientras secretos heredados desatan una presencia cada vez mas siniestra.'],
    'tt5052448' => ['year' => 2017, 'description' => 'Chris visita a la familia de su novia y descubre que la incomodidad inicial esconde una amenaza racista mucho mas terrible.'],
    'tt1457767' => ['year' => 2013, 'description' => 'Los investigadores paranormales Ed y Lorraine Warren ayudan a una familia acosada por una presencia maligna en su nueva casa.'],
    'tt0070047' => ['year' => 1973, 'description' => 'Una madre busca ayuda religiosa y medica cuando su hija muestra signos cada vez mas violentos de posesion demoniaca.'],
    'tt0081505' => ['year' => 1980, 'description' => 'Un escritor acepta cuidar un hotel aislado durante el invierno, pero el encierro y el lugar erosionan su cordura.'],
    'tt1130884' => ['year' => 2010, 'description' => 'Dos agentes investigan la desaparicion de una paciente en un hospital psiquiatrico aislado, donde nada parece confiable.'],
    'tt0209144' => ['year' => 2000, 'description' => 'Leonard busca al asesino de su esposa mientras lidia con una condicion que le impide formar nuevos recuerdos.'],
    'tt0482571' => ['year' => 2006, 'description' => 'Dos magos rivales llevan su competencia hasta extremos obsesivos en busca del truco definitivo.'],
    'tt2267998' => ['year' => 2014, 'description' => 'La desaparicion de Amy Dunne convierte a su esposo en sospechoso y revela una relacion construida sobre secretos y manipulacion.'],
    'tt1305806' => ['year' => 2009, 'description' => 'Un investigador judicial retirado revisita un caso de asesinato que marco su vida y un amor que nunca pudo cerrar.'],
    'tt3783958' => ['year' => 2016, 'description' => 'Una actriz y un musico se enamoran en Los Angeles mientras persiguen sus carreras y enfrentan el costo de sus ambiciones.'],
    'tt0414387' => ['year' => 2005, 'description' => 'Elizabeth Bennet y Mr. Darcy chocan por orgullo, prejuicios y diferencias sociales antes de reconocer lo que sienten.'],
    'tt0112471' => ['year' => 1995, 'description' => 'Dos jovenes se conocen en un tren y pasan una noche caminando por Viena, conversando sobre amor, tiempo y decisiones.'],
    'tt0332280' => ['year' => 2004, 'description' => 'Una historia de amor marcada por diferencias sociales, memoria y promesas se reconstruye a traves de un relato compartido.'],
    'tt0338013' => ['year' => 2004, 'description' => 'Joel y Clementine borran recuerdos de su relacion, pero el proceso revela lo complejo que es olvidar un amor.'],
    'tt1375666' => ['year' => 2010, 'description' => 'Un ladron especializado en entrar en los suenos recibe la mision de implantar una idea en la mente de un heredero.'],
    'tt0816692' => ['year' => 2014, 'description' => 'Un grupo de astronautas viaja a traves de un agujero de gusano para buscar un nuevo hogar para la humanidad.'],
    'tt0083658' => ['year' => 1982, 'description' => 'Un blade runner retirado persigue replicantes fugitivos en una ciudad futurista donde la frontera entre humano y artificial se desdibuja.'],
    'tt0133093' => ['year' => 1999, 'description' => 'Neo descubre que su realidad es una simulacion y se une a una rebelion contra las maquinas que controlan a la humanidad.'],
    'tt2543164' => ['year' => 2016, 'description' => 'Una linguista intenta comunicarse con visitantes extraterrestres y descubre que el lenguaje puede cambiar la forma de percibir el tiempo.'],
    'tt6751668' => ['year' => 2019, 'description' => 'Una familia pobre se infiltra en la vida de una familia rica, desatando una cadena de tensiones sociales y consecuencias violentas.'],
    'tt0364569' => ['year' => 2003, 'description' => 'Un hombre liberado tras quince anios de cautiverio busca a su captor y descubre una verdad devastadora.'],
    'tt1392214' => ['year' => 2013, 'description' => 'La desaparicion de dos ninas lleva a un padre desesperado y a un detective a cruzar limites morales en busca de respuestas.'],
    'tt0102926' => ['year' => 1991, 'description' => 'Una agente del FBI consulta al brillante asesino Hannibal Lecter para capturar a otro criminal serial antes de que vuelva a atacar.'],
    'tt0477348' => ['year' => 2007, 'description' => 'Un hombre encuentra dinero de un negocio criminal y queda perseguido por un asesino metodico en una historia de violencia y azar.'],
];

$reviewTemplates = [
    ['rating' => 5, 'comment' => 'Una obra muy recomendable, con ritmo y una identidad clara.'],
    ['rating' => 4, 'comment' => 'Funciona muy bien dentro de su genero y tiene escenas memorables.'],
    ['rating' => 4, 'comment' => 'La historia engancha y sostiene bien sus mejores ideas.'],
    ['rating' => 3, 'comment' => 'Entretenida y correcta; cumple con oficio aunque no todo sorprende.'],
    ['rating' => 5, 'comment' => 'Excelente para debatir y volver a ver con mas atencion.'],
];

$responseTemplates = [
    'Coincido bastante; lo mejor es como aprovecha sus recursos.',
    'Me gusto el enfoque, aunque algunas escenas podrian respirar mas.',
    'Buena recomendacion. Tiene detalles que vale la pena comentar.',
];

function seed_http_json(string $url): ?array
{
    $context = stream_context_create(['http' => [
        'timeout' => 15,
        'ignore_errors' => true,
        'header' => "User-Agent: NexoHubSeeder/1.0\r\n",
    ]]);
    $raw = @file_get_contents($url, false, $context);
    $data = $raw ? json_decode($raw, true) : null;

    return is_array($data) ? $data : null;
}

function seed_extract_year(?string $releaseInfo, ?int $fallbackYear = null): int
{
    preg_match('/\d{4}/', (string) $releaseInfo, $matches);
    if (isset($matches[0])) {
        return (int) $matches[0];
    }

    return $fallbackYear ?? (int) date('Y');
}

function seed_director(array $meta): string
{
    $directorValue = $meta['director'] ?? [];
    $directors = is_array($directorValue) ? $directorValue : [$directorValue];
    $director = trim((string) ($directors[0] ?? ''));

    return $director !== '' ? $director : 'Cinemeta';
}

function seed_cover_dir(): string
{
    $dir = dirname(__DIR__, 2) . '/Frontend/public/covers';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function seed_clear_cover_files(int $movieId): void
{
    foreach (glob(seed_cover_dir() . '/' . $movieId . '.*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

function seed_download_cover(int $movieId, string $posterUrl): void
{
    if ($posterUrl === '') {
        return;
    }

    $context = stream_context_create(['http' => [
        'timeout' => 20,
        'ignore_errors' => true,
        'header' => "User-Agent: NexoHubSeeder/1.0\r\n",
    ]]);
    $image = @file_get_contents($posterUrl, false, $context);
    if (!$image) {
        return;
    }

    $extension = seed_image_extension($image);
    if ($extension === null) {
        return;
    }

    seed_clear_cover_files($movieId);
    file_put_contents(seed_cover_dir() . '/' . $movieId . '.' . $extension, $image);
}

function seed_meta_for(string $imdbId): ?array
{
    $data = seed_http_json('https://v3-cinemeta.strem.io/meta/movie/' . rawurlencode($imdbId) . '.json');
    return is_array($data['meta'] ?? null) ? $data['meta'] : null;
}

function seed_image_extension(string $image): ?string
{
    if (str_starts_with($image, "\xFF\xD8\xFF")) {
        return 'jpg';
    }
    if (str_starts_with($image, "\x89PNG\r\n\x1A\n")) {
        return 'png';
    }
    if (str_starts_with($image, 'GIF87a') || str_starts_with($image, 'GIF89a')) {
        return 'gif';
    }
    if (substr($image, 0, 4) === 'RIFF' && substr($image, 8, 4) === 'WEBP') {
        return 'webp';
    }

    return null;
}

$users = $pdo->query("SELECT id FROM users ORDER BY FIELD(role, 'user', 'admin', 'superadmin'), id ASC")->fetchAll(PDO::FETCH_COLUMN);
if (count($users) < 2) {
    fwrite(STDERR, "Ejecuta primero 002_seed_users.sql para crear usuarios de demo.\n");
    exit(1);
}

$adminId = (int) ($pdo->query("SELECT id FROM users WHERE role IN ('admin', 'superadmin') ORDER BY FIELD(role, 'superadmin', 'admin'), id ASC LIMIT 1")->fetchColumn() ?: $users[0]);

$pdo->beginTransaction();

try {
    $oldMovieIds = $pdo->query("SELECT id FROM movies WHERE external_source IN ('nexohub_seed', 'nexohub_real')")->fetchAll(PDO::FETCH_COLUMN);
    if ($oldMovieIds) {
        $moviePlaceholders = implode(',', array_fill(0, count($oldMovieIds), '?'));
        $reviewStmt = $pdo->prepare("SELECT id FROM reviews WHERE movie_id IN ($moviePlaceholders)");
        $reviewStmt->execute($oldMovieIds);
        $reviewIds = $reviewStmt->fetchAll(PDO::FETCH_COLUMN);
        if ($reviewIds) {
            $reviewPlaceholders = implode(',', array_fill(0, count($reviewIds), '?'));
            $pdo->prepare("DELETE FROM review_likes WHERE review_id IN ($reviewPlaceholders)")->execute($reviewIds);
            $pdo->prepare("DELETE FROM review_responses WHERE review_id IN ($reviewPlaceholders)")->execute($reviewIds);
            $pdo->prepare("DELETE FROM reviews WHERE id IN ($reviewPlaceholders)")->execute($reviewIds);
        }
        $pdo->prepare("DELETE FROM favorite_movies WHERE movie_id IN ($moviePlaceholders)")->execute($oldMovieIds);
        $pdo->prepare("DELETE FROM movies WHERE id IN ($moviePlaceholders)")->execute($oldMovieIds);
        foreach ($oldMovieIds as $movieId) {
            seed_clear_cover_files((int) $movieId);
        }
    }

    $insertMovie = $pdo->prepare("
        INSERT INTO movies (title, genre, featured, description, year, movie_author, external_source, external_id, poster_url, approval_status, user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?)
    ");
    $insertReview = $pdo->prepare("INSERT INTO reviews (user_id, movie_id, rating, image_url, comment, created_at) VALUES (?, ?, ?, NULL, ?, NOW())");
    $insertResponse = $pdo->prepare("INSERT INTO review_responses (review_id, user_id, rating, comment, created_at) VALUES (?, ?, NULL, ?, NOW())");
    $insertLike = $pdo->prepare("INSERT IGNORE INTO review_likes (review_id, user_id) VALUES (?, ?)");

    $moviesWritten = 0;
    $reviewsWritten = 0;
    $responsesWritten = 0;
    $likesWritten = 0;

    foreach ($catalog as $genreIndex => $movies) {
        $genre = (string) $genreIndex;
        foreach ($movies as $movieIndex => $movie) {
            $meta = seed_meta_for($movie['id']);
            $fallback = $catalogMetadata[$movie['id']] ?? [];
            $title = trim((string) ($meta['name'] ?? $movie['title']));
            $description = trim((string) ($meta['description'] ?? $fallback['description'] ?? 'Pelicula destacada para el catalogo de NexoHub.'));
            $year = seed_extract_year($meta['releaseInfo'] ?? '', $fallback['year'] ?? null);
            $movieAuthor = $meta ? seed_director($meta) : 'Cinemeta';
            $posterUrl = trim((string) ($meta['poster'] ?? ''));
            if ($posterUrl === '') {
                $posterUrl = 'https://images.metahub.space/poster/medium/' . rawurlencode($movie['id']) . '/img';
            }

            $insertMovie->execute([
                $title,
                $genre,
                $movieIndex === 0 ? 1 : 0,
                $description,
                $year,
                $movieAuthor,
                $source,
                $movie['id'],
                $posterUrl,
                $adminId,
            ]);
            $movieId = (int) $pdo->lastInsertId();
            seed_download_cover($movieId, $posterUrl);
            $moviesWritten++;

            $reviewUserId = (int) $users[array_search($genre, array_keys($catalog), true) % count($users)];
            $template = $reviewTemplates[$movieIndex % count($reviewTemplates)];
            $reviewText = '[Seed NexoHub] ' . $template['comment'] . ' "' . $title . '" suma mucho dentro de ' . $genre . '.';
            $insertReview->execute([$reviewUserId, $movieId, $template['rating'], $reviewText]);
            $reviewId = (int) $pdo->lastInsertId();
            $reviewsWritten++;

            for ($i = 0; $i < 3; $i++) {
                $responseUserId = (int) $users[($movieIndex + $i + 1) % count($users)];
                if ($responseUserId === $reviewUserId) {
                    $responseUserId = (int) $users[($movieIndex + $i + 2) % count($users)];
                }
                $insertResponse->execute([$reviewId, $responseUserId, $responseTemplates[$i]]);
                $responsesWritten++;
            }

            foreach ($users as $likeUserId) {
                if ((int) $likeUserId === $reviewUserId) {
                    continue;
                }
                $insertLike->execute([$reviewId, (int) $likeUserId]);
                $likesWritten += $insertLike->rowCount();
            }
        }
    }

    $pdo->commit();
    echo "Catalogo real: {$moviesWritten} peliculas, {$reviewsWritten} resenas, {$responsesWritten} comentarios, {$likesWritten} corazones.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "No se pudo ejecutar el seed real: {$e->getMessage()}\n");
    exit(1);
}
