<?php

require_once __DIR__ . '/../models/Database.php';

$pdo = Database::getInstance()->getConnection();
$limitEnv = getenv('REVIEW_SEED_MOVIE_LIMIT');
$movieLimit = $limitEnv === false ? 12 : (int) $limitEnv;
if ($movieLimit < 0) {
    $movieLimit = 0;
}

$users = $pdo->query("
    SELECT id, name
    FROM users
    WHERE role IN ('user', 'admin', 'superadmin')
    ORDER BY FIELD(role, 'user', 'admin', 'superadmin'), id ASC
")->fetchAll(PDO::FETCH_ASSOC);

if (count($users) < 2) {
    fwrite(STDERR, "Ejecuta primero 002_seed_users.sql para crear usuarios de demo.\n");
    exit(1);
}

$movieSql = ""
    SELECT id, title, genre
    FROM movies
    WHERE external_source = 'cinemeta'
    ORDER BY featured DESC, year DESC, title ASC
"";
if ($movieLimit > 0) {
    $movieSql .= "\n    LIMIT ?";
}

$movieStmt = $pdo->prepare($movieSql);
if ($movieLimit > 0) {
    $movieStmt->bindValue(1, $movieLimit, PDO::PARAM_INT);
}
$movieStmt->execute();
$movies = $movieStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$movies) {
    fwrite(STDERR, "No hay peliculas de API. Ejecuta primero 003_seed_movies_from_api.php.\n");
    exit(1);
}

$reviewTemplates = [
    ['rating' => 5, 'comment' => 'Muy recomendable: sostiene el ritmo, tiene buenas actuaciones y deja ganas de comentarla.'],
    ['rating' => 4, 'comment' => 'Buena pelicula para el genero; tiene personalidad y varios momentos que funcionan muy bien.'],
    ['rating' => 4, 'comment' => 'Me gusto bastante. La historia engancha y la puesta en escena acompana sin sentirse exagerada.'],
    ['rating' => 3, 'comment' => 'Correcta y entretenida, aunque algunas partes podrian tener mas fuerza.'],
];

$insertReview = $pdo->prepare("
    INSERT INTO reviews (user_id, movie_id, rating, image_url, comment)
    VALUES (?, ?, ?, NULL, ?)
    ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)
");
$findReview = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND movie_id = ? LIMIT 1");
$findResponse = $pdo->prepare("SELECT id FROM review_responses WHERE review_id = ? AND user_id = ? LIMIT 1");
$insertResponse = $pdo->prepare("
    INSERT INTO review_responses (review_id, user_id, rating, comment, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$updateResponse = $pdo->prepare("
    UPDATE review_responses
    SET rating = ?, comment = ?
    WHERE id = ?
");
$upsertReputation = $pdo->prepare("
    INSERT INTO genre_reputation (user_id, genre, points)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE points = GREATEST(points, VALUES(points))
");
$insertFavorite = $pdo->prepare("
    INSERT INTO favorite_movies (user_id, movie_id)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE created_at = created_at
");

$reviews = 0;
$responses = 0;
$favorites = 0;

foreach ($movies as $index => $movie) {
    $primaryUser = $users[$index % count($users)];
    $secondaryUser = $users[($index + 1) % count($users)];
    $template = $reviewTemplates[$index % count($reviewTemplates)];
    $rating = $template['rating'];
    $comment = $template['comment'] . ' "' . $movie['title'] . '" suma mucho dentro de ' . $movie['genre'] . '.';

    $insertReview->execute([
        (int) $primaryUser['id'],
        (int) $movie['id'],
        $rating,
        $comment,
    ]);
    $findReview->execute([(int) $primaryUser['id'], (int) $movie['id']]);
    $reviewId = (int) $findReview->fetchColumn();
    $reviews++;

    if ($reviewId) {
        $responseComment = 'Coincido con varios puntos; tambien me parecio una buena eleccion para ver y debatir.';
        $findResponse->execute([$reviewId, (int) $secondaryUser['id']]);
        $responseId = (int) $findResponse->fetchColumn();
        if ($responseId) {
            $updateResponse->execute([4, $responseComment, $responseId]);
        } else {
            $insertResponse->execute([$reviewId, (int) $secondaryUser['id'], 4, $responseComment]);
        }
        $responses++;
    }

    $upsertReputation->execute([(int) $primaryUser['id'], $movie['genre'], $rating * 5]);
    $upsertReputation->execute([(int) $secondaryUser['id'], $movie['genre'], 10]);

    $insertFavorite->execute([(int) $primaryUser['id'], (int) $movie['id']]);
    $favorites++;
    if ($index % 2 === 0) {
        $insertFavorite->execute([(int) $secondaryUser['id'], (int) $movie['id']]);
        $favorites++;
    }
}

echo "Resenas seed: {$reviews} resenas, {$responses} respuestas, {$favorites} favoritos.\n";
