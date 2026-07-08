<?php

require_once __DIR__ . '/../models/Database.php';

$pdo = Database::getInstance()->getConnection();

$genres = ['Accion', 'Aventura', 'Animacion', 'Comedia', 'Crimen', 'Documental', 'Drama', 'Fantasia', 'Terror', 'Misterio', 'Romance', 'Ciencia ficcion', 'Thriller'];

$genreTitles = [
    'Accion' => ['Pulso de Acero', 'Furia en la Autopista', 'Codigo Impacto', 'Operacion Aurora', 'Zona de Rescate'],
    'Aventura' => ['Mapa de Nubes', 'La Ruta del Sol', 'Expedicion Boreal', 'El Valle Escondido', 'Horizonte Salvaje'],
    'Animacion' => ['Luna de Papel', 'El Taller de las Estrellas', 'Nico y el Bosque Azul', 'Robot de Barrio', 'La Isla de los Colores'],
    'Comedia' => ['Plan Imperfecto', 'Vecinos al Borde', 'Risas de Medianoche', 'El Club del Desastre', 'Manual para Sobrevivir'],
    'Crimen' => ['Distrito Cero', 'La Ultima Coartada', 'Sombras del Puerto', 'Caso Abierto', 'El Testigo Silencioso'],
    'Documental' => ['Voces del Agua', 'Ciudades Invisibles', 'La Memoria del Hielo', 'Oficios Perdidos', 'Planeta Cercano'],
    'Drama' => ['Dias de Invierno', 'Cartas sin Enviar', 'El Peso del Silencio', 'Una Casa Lejana', 'Despues de la Lluvia'],
    'Fantasia' => ['El Reino de Bruma', 'La Llave del Alba', 'Guardianes del Umbral', 'Bosque de Cristal', 'La Torre Errante'],
    'Terror' => ['Habitacion 13', 'La Casa que Respira', 'Noche sin Rostro', 'Ecos del Sotano', 'El Ritual de la Niebla'],
    'Misterio' => ['El Enigma Hale', 'La Puerta Sellada', 'Huella en la Nieve', 'Archivo Nocturno', 'El Secreto del Faro'],
    'Romance' => ['Cafe de Abril', 'Todas las Cartas', 'Verano en Lisboa', 'La Cancion Pendiente', 'Dos Estaciones'],
    'Ciencia ficcion' => ['Orbita Final', 'Neon Genesis Delta', 'Proyecto Andromeda', 'Ciudad Sintetica', 'Senal desde Europa'],
    'Thriller' => ['Cuenta Regresiva', 'Linea de Fuego', 'La Decision', 'Punto de Quiebre', 'El Ultimo Minuto'],
];

$reviewTemplates = [
    5 => 'Tiene ritmo, personalidad y una direccion que entiende muy bien lo que promete.',
    4 => 'Funciona muy bien dentro de su genero y deja varias escenas para comentar.',
    4 => 'La historia engancha rapido y mantiene una energia pareja hasta el final.',
    3 => 'Es entretenida y correcta; no reinventa nada, pero cumple con oficio.',
    5 => 'Una sorpresa muy solida, con personajes claros y un cierre satisfactorio.',
];

$responseTemplates = [
    'Coincido bastante; lo mejor es como aprovecha los recursos del genero.',
    'Me gusto el enfoque, aunque senti que algunas escenas podian respirar mas.',
    'Buena recomendacion. La volveria a ver para notar detalles que se me escaparon.',
];

$users = $pdo->query("SELECT id FROM users ORDER BY FIELD(role, 'user', 'admin', 'superadmin'), id ASC")->fetchAll(PDO::FETCH_COLUMN);
if (count($users) < 2) {
    fwrite(STDERR, "Ejecuta primero 002_seed_users.sql para crear usuarios de demo.\n");
    exit(1);
}

$adminId = (int) ($pdo->query("SELECT id FROM users WHERE role IN ('admin', 'superadmin') ORDER BY FIELD(role, 'superadmin', 'admin'), id ASC LIMIT 1")->fetchColumn() ?: $users[0]);
$source = 'nexohub_seed';

$pdo->beginTransaction();

try {
    $seedMovieIds = $pdo->query("SELECT id FROM movies WHERE external_source = 'nexohub_seed'")->fetchAll(PDO::FETCH_COLUMN);
    if ($seedMovieIds) {
        $moviePlaceholders = implode(',', array_fill(0, count($seedMovieIds), '?'));
        $reviewStmt = $pdo->prepare("SELECT id FROM reviews WHERE movie_id IN ($moviePlaceholders)");
        $reviewStmt->execute($seedMovieIds);
        $reviewIds = $reviewStmt->fetchAll(PDO::FETCH_COLUMN);
        if ($reviewIds) {
            $reviewPlaceholders = implode(',', array_fill(0, count($reviewIds), '?'));
            $pdo->prepare("DELETE FROM review_likes WHERE review_id IN ($reviewPlaceholders)")->execute($reviewIds);
            $pdo->prepare("DELETE FROM review_responses WHERE review_id IN ($reviewPlaceholders)")->execute($reviewIds);
            $pdo->prepare("DELETE FROM reviews WHERE id IN ($reviewPlaceholders)")->execute($reviewIds);
        }
    }

    $findMovie = $pdo->prepare("SELECT id FROM movies WHERE external_source = ? AND external_id = ? LIMIT 1");
    $insertMovie = $pdo->prepare("
        INSERT INTO movies (title, genre, featured, description, year, movie_author, external_source, external_id, poster_url, approval_status, user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, 'approved', ?)
    ");
    $updateMovie = $pdo->prepare("
        UPDATE movies
        SET title = ?, genre = ?, featured = ?, description = ?, year = ?, movie_author = ?, approval_status = 'approved', user_id = COALESCE(user_id, ?)
        WHERE id = ?
    ");
    $insertReview = $pdo->prepare("INSERT INTO reviews (user_id, movie_id, rating, image_url, comment, created_at) VALUES (?, ?, ?, NULL, ?, NOW())");
    $insertResponse = $pdo->prepare("INSERT INTO review_responses (review_id, user_id, rating, comment, created_at) VALUES (?, ?, NULL, ?, NOW())");
    $insertLike = $pdo->prepare("INSERT IGNORE INTO review_likes (review_id, user_id) VALUES (?, ?)");

    $moviesWritten = 0;
    $reviewsWritten = 0;
    $responsesWritten = 0;
    $likesWritten = 0;

    foreach ($genres as $genreIndex => $genre) {
        foreach ($genreTitles[$genre] as $movieIndex => $title) {
            $externalId = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $genre . '-' . $movieIndex));
            $year = 2020 + (($genreIndex + $movieIndex) % 7);
            $featured = $movieIndex === 0 ? 1 : 0;
            $description = "Una propuesta de {$genre} creada para poblar NexoHub con catalogo variado, reseñas y actividad de usuarios.";
            $movieAuthor = ['Nexo Studios', 'Mirada Sur', 'Atlas Films', 'Cine Norte', 'Casa Prisma'][$movieIndex];

            $findMovie->execute([$source, $externalId]);
            $movieId = (int) $findMovie->fetchColumn();
            if ($movieId) {
                $updateMovie->execute([$title, $genre, $featured, $description, $year, $movieAuthor, $adminId, $movieId]);
            } else {
                $insertMovie->execute([$title, $genre, $featured, $description, $year, $movieAuthor, $source, $externalId, $adminId]);
                $movieId = (int) $pdo->lastInsertId();
            }
            $moviesWritten++;

            $reviewUserId = (int) $users[$genreIndex % count($users)];
            $rating = array_keys($reviewTemplates)[$movieIndex % count($reviewTemplates)];
            $reviewText = '[Seed NexoHub] ' . $reviewTemplates[$rating] . ' "' . $title . '" suma identidad dentro de ' . $genre . '.';
            $insertReview->execute([$reviewUserId, $movieId, $rating, $reviewText]);
            $reviewId = (int) $pdo->lastInsertId();
            $reviewsWritten++;

            for ($i = 0; $i < 3; $i++) {
                $responseUserId = (int) $users[($genreIndex + $movieIndex + $i + 1) % count($users)];
                if ($responseUserId === $reviewUserId) {
                    $responseUserId = (int) $users[($genreIndex + $movieIndex + $i + 2) % count($users)];
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
    echo "Catalogo seed: {$moviesWritten} peliculas, {$reviewsWritten} resenas, {$responsesWritten} comentarios, {$likesWritten} corazones.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "No se pudo ejecutar el seed completo: {$e->getMessage()}\n");
    exit(1);
}
