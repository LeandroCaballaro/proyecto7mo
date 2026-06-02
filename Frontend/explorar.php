<?php
session_start();
define('API_URL', 'http://localhost/proyecto7mo/Backend/public/api.php');

function api_get($route, $extra = [])
{
    $url = API_URL . '?' . http_build_query(array_merge(['route' => $route], $extra));
    $ctx = stream_context_create(['http' => ['ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}

function api_post($route, $data, $token = null)
{
    $h = "Content-Type: application/json\r\n";
    if ($token) {
        $h .= "Authorization: Bearer $token\r\n";
    }
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => $h,
        'content'       => json_encode($data),
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents(API_URL . '?route=' . urlencode($route), false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}

$genre = $_GET['genre'] ?? '';
$msg = '';
$active_movie_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['token'])) {
        $msg = 'Debes iniciar sesión para publicar películas o responder reseñas.';
    } else {
        $action = $_POST['action'] ?? 'submit_review';

        if ($action === 'create_movie') {
            $res = api_post('movies', [
                'title' => $_POST['title'] ?? '',
                'genre' => $_POST['genre'] ?? '',
                'year' => (int) ($_POST['year'] ?? 0),
                'description' => $_POST['description'] ?? '',
            ], $_SESSION['token']);
            $msg = isset($res['ok']) ? 'Película publicada correctamente.' : ($res['error'] ?? 'Error al publicar película');
        } elseif ($action === 'respond_review') {
            $res = api_post('reviews/' . urlencode((int) ($_POST['review_id'] ?? 0)) . '/responses', [
                'rating' => (int) ($_POST['rating'] ?? 0),
                'comment' => $_POST['comment'] ?? '',
            ], $_SESSION['token']);
            $msg = isset($res['ok']) ? 'Respuesta enviada correctamente.' : ($res['error'] ?? 'Error al responder la reseña');
            if (isset($res['ok'])) {
                $active_movie_id = (int) ($_POST['movie_id'] ?? 0);
            }
        } else {
            $movie_id = (int) ($_POST['movie_id'] ?? 0);
            $res = api_post('reviews', [
                'movie_id' => $movie_id,
                'rating' => (int) ($_POST['rating'] ?? 0),
                'comment' => $_POST['comment'] ?? '',
            ], $_SESSION['token']);
            $msg = isset($res['ok']) ? 'Reseña publicada (+reputación por género)' : ($res['error'] ?? 'Error');
            if (isset($res['ok'])) {
                $active_movie_id = $movie_id;
            }
        }
    }
}

$peliculas = $genre ? api_get('movies/genre/' . urlencode($genre)) : api_get('movies');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar Películas - NexoHub</title>
    <meta name="description" content="Explora películas por género y reseñas de la comunidad">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/explorar.css">
    <link href="style/styles.css" rel="stylesheet">
    <link rel="icon" href="../public/nhlogo.png" type="image/png">
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
    <?php include 'header.php'; ?>

    <main class="flex-1 py-12">
        <div class="container mx-auto px-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold md:text-4xl text-foreground">
                    Explorar Películas
                </h1>
                <?php if ($genre): ?>
                    <p class="mt-2 text-muted-foreground">Filtrado por género: <strong class="text-primary"><?= htmlspecialchars($genre) ?></strong></p>
                <?php else: ?>
                    <p class="mt-2 text-muted-foreground">Descubre películas recomendadas por expertos en cada género</p>
                <?php endif; ?>
            </div>

            <!-- Alerta de Respuesta del Formulario -->
            <?php if ($msg): ?>
                <div class="mb-6 p-4 rounded-lg bg-card border border-border text-foreground flex items-center gap-2">
                    <span class="text-primary font-bold">📢</span>
                    <span><?= htmlspecialchars($msg) ?></span>
                </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="mb-8 flex flex-wrap gap-4">
                <select onchange="location = this.value;" class="rounded-lg border border-border bg-card px-4 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="explorar.php">Todos los Géneros</option>
                    <?php foreach (api_get('movies/genres') ?: [] as $g): ?>
                        <option value="explorar.php?genre=<?= urlencode($g) ?>" <?= $genre === $g ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select class="rounded-lg border border-border bg-card px-4 py-2 text-foreground focus:outline-none">
                    <option>Ordenar por: Rating</option>
                    <option>Ordenar por: Año</option>
                    <option>Ordenar por: Popularidad</option>
                </select>
            </div>

            <?php if (!empty($_SESSION['user'])): ?>
                <div class="mb-8 rounded-xl border border-border bg-card p-6">
                    <h2 class="text-xl font-semibold text-foreground mb-4">Publicar nueva película</h2>
                    <form method="post" class="grid gap-4 lg:grid-cols-2">
                        <input type="hidden" name="action" value="create_movie">
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground">Título</label>
                            <input type="text" name="title" required class="mt-2 w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Nombre de la película">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground">Género</label>
                            <input type="text" name="genre" required class="mt-2 w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Ej. Acción, Drama">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground">Año</label>
                            <input type="number" name="year" min="1880" max="2100" required class="mt-2 w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="<?= date('Y') ?>" value="<?= date('Y') ?>">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground">Descripción</label>
                            <input type="text" name="description" class="mt-2 w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Un breve resumen">
                        </div>
                        <div class="lg:col-span-2">
                            <button type="submit" class="rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition-colors">
                                Publicar película
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Películas Grid -->
            <?php if (empty($peliculas)): ?>
                <div class="text-center py-20 bg-card rounded-xl border border-border">
                    <span class="text-4xl">🎬</span>
                    <p class="mt-4 text-muted-foreground">No se encontraron películas en esta categoría.</p>
                </div>
            <?php else: ?>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <?php foreach ($peliculas as $p): ?>
                        <div class="movie-card-container" onclick="openMovieModal(<?= (int)$p['id'] ?>)">
                            <div class="movie-flip-card">
                                <!-- FRONT SIDE -->
                                <div class="movie-flip-card-front relative flex flex-col justify-between">
                                    <?php 
                                    $coverPath = "public/covers/" . (int)$p['id'] . ".png";
                                    $hasCover = file_exists("C:/xampp/htdocs/proyecto7mo/" . $coverPath);
                                    if ($hasCover): 
                                    ?>
                                        <img src="/proyecto7mo/<?= $coverPath ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <!-- Fallback gradient design -->
                                        <div class="w-full h-full bg-gradient-to-br from-primary/20 to-secondary/30 flex flex-col items-center justify-center p-4">
                                            <span class="text-5xl mb-4">🎥</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Cover detail overlay -->
                                    <div class="movie-poster-overlay">
                                        <h3 class="text-xl font-bold text-foreground line-clamp-2"><?= htmlspecialchars($p['title']) ?></h3>
                                        <p class="text-xs text-muted-foreground mt-1"><?= htmlspecialchars($p['genre'] ?? 'General') ?> • <?= (int) ($p['year'] ?? 2024) ?><span class="click-here">Click Aquí</span></p>
                                    </div>
                                </div>
                                
                                <!-- BACK SIDE -->
                               
                                
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Backdrop -->
    <div id="movieModalBackdrop" class="modal-backdrop" onclick="closeMovieModalOutside(event)">
        <div class="modal-container" onclick="event.stopPropagation()">
            <!-- LEFT COLUMN: Movie Poster -->
            <div class="modal-left">
                <img id="modalMoviePoster" src="" alt="Portada" class="modal-poster-img">
                <div class="modal-poster-overlay">
                    <span id="modalMovieGenreYear" class="text-xs font-bold text-primary tracking-wide uppercase">GÉNERO • AÑO</span>
                    <h2 id="modalMovieTitle" class="text-2xl md:text-3xl font-extrabold text-foreground mt-1">Título de la Película</h2>
                    <p id="modalMovieDesc" class="text-sm text-muted-foreground mt-3 line-clamp-4">Descripción de la película...</p>
                </div>
            </div>
            
            <!-- RIGHT COLUMN: Reviews Feed -->
            <div class="modal-right">
                <div class="modal-header">
                    <span class="text-sm font-bold text-muted-foreground uppercase tracking-widest">Feed de Reseñas</span>
                    <div class="modal-close-btn" onclick="closeMovieModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </div>
                
                <div class="modal-body custom-scrollbar" id="modalReviewsBody">
                    <!-- Reviews and replies loaded dynamically here -->
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    // Prepare all movie data on the client side
    const moviesData = {
        <?php foreach ($peliculas as $p): ?>
            "<?= (int)$p['id'] ?>": {
                id: <?= (int)$p['id'] ?>,
                title: <?= json_encode($p['title']) ?>,
                genre: <?= json_encode($p['genre'] ?? 'General') ?>,
                year: <?= (int)($p['year'] ?? 2024) ?>,
                description: <?= json_encode($p['description'] ?? '') ?>,
                hasCover: <?= file_exists("C:/xampp/htdocs/proyecto7mo/public/covers/" . (int)$p['id'] . ".png") ? 'true' : 'false' ?>,
                reviews: [
                    <?php 
                    $reviews = api_get('movies/' . urlencode((int) $p['id']) . '/reviews') ?: [];
                    foreach ($reviews as $rev): 
                        $responses = api_get('reviews/' . urlencode((int) $rev['id']) . '/responses') ?: [];
                    ?>
                    {
                        id: <?= (int)$rev['id'] ?>,
                        user_id: <?= (int)$rev['user_id'] ?>,
                        user_name: <?= json_encode($rev['user_name']) ?>,
                        rating: <?= (int)$rev['rating'] ?>,
                        comment: <?= json_encode($rev['comment'] ?: 'Sin comentario') ?>,
                        responses: <?= json_encode($responses) ?>
                    },
                    <?php endforeach; ?>
                ]
            },
        <?php endforeach; ?>
    };

    const currentUser = <?= isset($_SESSION['user']) ? json_encode($_SESSION['user']) : 'null' ?>;
    const activeMovieId = <?= $active_movie_id ?>;

    // Helper to safely escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Modal Control Functions
    function openMovieModal(movieId) {
        const movie = moviesData[movieId];
        if (!movie) return;

        // Set left side details
        const posterImg = document.getElementById('modalMoviePoster');
        if (movie.hasCover) {
            posterImg.src = `/proyecto7mo/public/covers/${movie.id}.png`;
            posterImg.style.display = 'block';
        } else {
            // Simulated generic background if no poster image
            posterImg.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="150" viewBox="0 0 100 150"><rect width="100" height="150" fill="%231a1a2e"/><circle cx="50" cy="65" r="20" fill="%23e94560" opacity="0.3"/><text x="50" y="70" font-family="sans-serif" font-size="20" fill="%23f5f5f5" text-anchor="middle">🎥</text></svg>';
        }

        document.getElementById('modalMovieGenreYear').innerText = `${movie.genre} • ${movie.year}`;
        document.getElementById('modalMovieTitle').innerText = movie.title;
        document.getElementById('modalMovieDesc').innerText = movie.description || 'Sin descripción disponible.';

        // Render reviews list on the right side
        renderReviewsList(movie);

        // Show Modal
        document.getElementById('movieModalBackdrop').classList.add('active');
        document.body.style.overflow = 'hidden'; // Disable background scroll
    }

    function closeMovieModal() {
        document.getElementById('movieModalBackdrop').classList.remove('active');
        document.body.style.overflow = ''; // Restore scroll
    }

    function closeMovieModalOutside(event) {
        if (event.target === document.getElementById('movieModalBackdrop')) {
            closeMovieModal();
        }
    }

    // Escape key closes modal
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeMovieModal();
        }
    });

    // Expand/collapse review replies
    function toggleReplies(reviewId) {
        const container = document.getElementById(`replies-container-${reviewId}`);
        const btn = document.getElementById(`toggle-replies-btn-${reviewId}`);
        if (!container) return;

        container.classList.toggle('open');
        btn.classList.toggle('open');
    }

    // Render responses markup
    function renderResponses(responses) {
        if (responses.length === 0) {
            return `<p class="text-xs text-muted-foreground italic pl-4 opacity-75">Sé el primero en responder...</p>`;
        }
        return responses.map(resp => `
            <div class="pl-4 border-l-2 border-primary/30 py-1.5 review-item">
                <div class="flex items-center justify-between gap-3 text-xs mb-1">
                    <span class="font-bold text-foreground">${escapeHtml(resp.user_name)}</span>
                    <span class="text-yellow-400 font-normal">${'★'.repeat(resp.rating)}${'☆'.repeat(5 - resp.rating)}</span>
                </div>
                <p class="text-muted-foreground text-xs leading-relaxed">${escapeHtml(resp.comment)}</p>
            </div>
        `).join('');
    }

    // Render response reply form markup
    function renderReplyForm(rev, movieId) {
        if (!currentUser) return '';
        if (currentUser.id === rev.user_id) {
            return `
                <div class="rounded border border-secondary/20 bg-secondary/5 p-2.5 text-xs text-muted-foreground mt-3 text-center">
                    ℹ️ No puedes responder tu propia reseña
                </div>
            `;
        }

        return `
            <form method="post" class="space-y-3 rounded-lg border border-primary/10 bg-primary/5 p-4 mt-3">
                <input type="hidden" name="action" value="respond_review">
                <input type="hidden" name="review_id" value="${rev.id}">
                <input type="hidden" name="movie_id" value="${movieId}">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm">💬</span>
                    <label class="block text-[11px] font-bold text-primary uppercase tracking-wide">Responde esta reseña</label>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] uppercase font-bold text-muted-foreground">Tu calificación:</label>
                    <select name="rating" required class="rounded border border-border bg-background text-foreground text-xs px-2 py-1 focus:ring-1 focus:ring-primary focus:outline-none w-full">
                        <option value="">-- Selecciona una calificación --</option>
                        <option value="5">★★★★★ - 5 estrellas</option>
                        <option value="4">★★★★☆ - 4 estrellas</option>
                        <option value="3">★★★☆☆ - 3 estrellas</option>
                        <option value="2">★★☆☆☆ - 2 estrellas</option>
                        <option value="1">★☆☆☆☆ - 1 estrella</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="block text-[10px] uppercase font-bold text-muted-foreground">Tu respuesta:</label>
                    <textarea name="comment" placeholder="Comparte tu opinión..." required rows="2" class="w-full text-xs rounded border border-border bg-background px-3 py-2 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary"></textarea>
                </div>
                <button type="submit" class="w-full rounded bg-primary text-primary-foreground hover:bg-primary/90 py-1.5 text-xs font-bold transition-colors">
                    ✓ Publicar respuesta
                </button>
            </form>
        `;
    }

    // Render right column contents
    function renderReviewsList(movie) {
        const container = document.getElementById('modalReviewsBody');
        let htmlContent = '';

        if (movie.reviews.length === 0) {
            htmlContent += `
                <div class="text-center py-16 opacity-75">
                    <span class="text-4xl">💬</span>
                    <p class="mt-3 text-sm text-muted-foreground">Aún no hay reseñas para esta película.</p>
                </div>
            `;
        } else {
            movie.reviews.forEach(rev => {
                htmlContent += `
                    <div class="rounded-xl border border-border bg-background/30 p-4 mb-4 review-item transition-all duration-200">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <span class="font-extrabold text-foreground text-sm">${escapeHtml(rev.user_name)}</span>
                                <span class="text-yellow-400 text-xs ml-2">${'★'.repeat(rev.rating)}${'☆'.repeat(5 - rev.rating)}</span>
                            </div>
                        </div>
                        <p class="text-muted-foreground text-xs leading-relaxed mt-2">${escapeHtml(rev.comment)}</p>
                        
                        <!-- Replies section toggle -->
                        <div class="mt-3 flex items-center justify-between">
                            <div class="toggle-replies-btn" id="toggle-replies-btn-${rev.id}" onclick="toggleReplies(${rev.id})">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                </svg>
                                <span>Respuestas (${rev.responses.length})</span>
                            </div>
                        </div>
                        
                        <!-- Collapsible Replies Container -->
                        <div class="replies-container" id="replies-container-${rev.id}">
                            <div class="border-t border-border mt-3 pt-3 space-y-3">
                                ${renderResponses(rev.responses)}
                                ${renderReplyForm(rev, movie.id)}
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        // Add Review Section at the bottom of the right column
        if (currentUser) {
            // Check if user already reviewed this movie
            const alreadyReviewed = movie.reviews.some(rev => rev.user_id === currentUser.id);

            if (alreadyReviewed) {
                htmlContent += `
                    <div class="mt-6 pt-4 border-t border-border text-center">
                        <p class="text-xs text-muted-foreground bg-primary/5 border border-primary/10 rounded-lg py-3 px-4">
                            ✨ Ya has reseñado esta película. ¡Gracias por tu opinión!
                        </p>
                    </div>
                `;
            } else {
                htmlContent += `
                    <form method="post" class="mt-6 pt-6 border-t border-border space-y-3">
                        <input type="hidden" name="action" value="submit_review">
                        <input type="hidden" name="movie_id" value="${movie.id}">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-muted-foreground uppercase">Calificación:</label>
                            <select name="rating" class="rounded border border-border bg-background text-foreground text-xs px-2 py-1 focus:ring-1 focus:ring-primary focus:outline-none">
                                <option value="5">★★★★★</option>
                                <option value="4">★★★★☆</option>
                                <option value="3">★★★☆☆</option>
                                <option value="2">★★☆☆☆</option>
                                <option value="1">★☆☆☆☆</option>
                            </select>
                        </div>
                        <input type="text" name="comment" placeholder="Escribe tu reseña..." required class="w-full text-xs rounded border border-border bg-background px-3 py-2 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary">
                        <button type="submit" class="w-full rounded-lg bg-primary text-primary-foreground hover:bg-primary/90 py-2.5 text-xs font-bold transition-colors">
                            Publicar Reseña
                        </button>
                    </form>
                `;
            }
        } else {
            htmlContent += `
                <div class="mt-6 pt-4 border-t border-border text-center">
                    <p class="text-xs text-muted-foreground">
                        <a href="/proyecto7mo/Frontend/login.php" class="text-primary hover:underline font-bold">Inicia sesión</a> para calificar esta película.
                    </p>
                </div>
            `;
        }

        container.innerHTML = htmlContent;
    }

    // Auto-open modal on load if activeMovieId is set
    window.addEventListener('DOMContentLoaded', () => {
        if (activeMovieId > 0) {
            openMovieModal(activeMovieId);
        }
    });
    </script>
</body>
</html>