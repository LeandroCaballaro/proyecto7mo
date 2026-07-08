<?php
$user = $_SESSION['user'] ?? null;
$userPhoto = $user['profile_image'] ?? null;
$userInitial = $user ? mb_strtoupper(mb_substr($user['name'], 0, 1, 'UTF-8')) : '';

function profile_image_url(?string $path): ?string
{
    $path = trim((string) $path);
    if ($path === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    if ($path[0] === '/') {
        return $path;
    }

    return '/proyecto7mo/' . ltrim($path, '/');
}

require_once __DIR__ . '/../../Backend/models/Database.php';

if ($user && isset($user['id'])) {

    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT profile_image, role
        FROM users
        WHERE id = ?
    ");

    $stmt->execute([$user['id']]);

    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    $userPhoto = $userData['profile_image'] ?? null;
    $_SESSION['user']['profile_image'] = $userPhoto;
    $_SESSION['user']['role'] = $userData['role'] ?? ($_SESSION['user']['role'] ?? 'user');
    $user['role'] = $_SESSION['user']['role'];
}
$userPhoto = profile_image_url($userPhoto);
// Determine current page to highlight active navigation link
$uri = $_SERVER['REQUEST_URI'] ?? '';
$is_home = (strpos($uri, 'index.php') !== false || $uri === '/proyecto7mo/' || $uri === '/proyecto7mo' || substr($uri, -1) === '/');
$is_explorar = (strpos($uri, 'explorar.php') !== false);
$is_ranking = (strpos($uri, 'ranking.php') !== false);
$is_generos = (strpos($uri, 'generos.php') !== false);
$is_approval = (strpos($uri, 'approve_movies.php') !== false);
?>
<header class="sticky top-0 z-50 w-full border-b border-border header-glass transition-all duration-300">
    <div class="container mx-auto flex h-16 items-center justify-between px-4">
        <!-- Logo -->
        <a href="/proyecto7mo/index.php" class="flex items-center gap-3 group">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary transition-transform duration-300 group-hover:scale-110 shadow-lg shadow-primary/25">
                <svg class="h-7 w-7 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16l13-8L7 4z"></path>
                </svg>
            </div>
            <span class="text-2xl font-extrabold text-foreground tracking-tight transition-colors duration-300 group-hover:text-primary">
                Nexo<span class="text-primary group-hover:text-foreground transition-colors duration-300">Hub</span>
            </span>
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden items-center gap-8 md:flex">
            <a href="/proyecto7mo/index.php" class="text-sm font-semibold nav-link <?= $is_home ? 'text-primary active' : 'text-muted-foreground hover:text-primary' ?>">Inicio</a>
            <a href="/proyecto7mo/Frontend/explorar.php" class="text-sm font-semibold nav-link <?= $is_explorar ? 'text-primary active' : 'text-muted-foreground hover:text-primary' ?>">Explorar</a>
            <a href="/proyecto7mo/Frontend/generos.php" class="text-sm font-semibold nav-link <?= $is_generos ? 'text-primary active' : 'text-muted-foreground hover:text-primary' ?>">Géneros</a>
            <a href="/proyecto7mo/Frontend/ranking.php" class="text-sm font-semibold nav-link <?= $is_ranking ? 'text-primary active' : 'text-muted-foreground hover:text-primary' ?>">Ranking</a>
            <?php if (in_array($user['role'] ?? '', ['admin', 'superadmin'], true)): ?>
                <a href="/proyecto7mo/Frontend/admin.php" class="text-sm font-semibold nav-link text-muted-foreground hover:text-primary">Administración</a>
                <a href="/proyecto7mo/Frontend/approve_movies.php" class="text-sm font-semibold nav-link <?= $is_approval ? 'text-primary active' : 'text-muted-foreground hover:text-primary' ?>">Aprobar Películas</a>
            <?php endif; ?>
        </nav>

        <!-- Desktop Actions -->
        <div class="hidden items-center gap-4 md:flex">
            <a href="/proyecto7mo/Frontend/explorar.php?focus=search" class="text-muted-foreground hover:text-primary transition-colors duration-200 p-2" title="Buscar">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span class="sr-only">Buscar</span>
            </a>
            <a href="/proyecto7mo/Frontend/user.php#favoritas" class="text-muted-foreground hover:text-primary transition-colors duration-200 p-2" title="Favoritos">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
                <span class="sr-only">Favoritos</span>
            </a>
            <?php if ($user): ?>
                <div class="flex items-center gap-3">
                    <a href="/proyecto7mo/Frontend/user.php" class="inline-flex items-center justify-center h-11 w-11 rounded-full overflow-hidden border border-border bg-secondary hover:border-primary transition-all duration-200">
                        <?php if ($userPhoto): ?>
                            <img src="<?= htmlspecialchars($userPhoto) ?>" alt="image" class="h-full w-full object-cover">
                        <?php else: ?>
                            <span class="flex h-full w-full items-center justify-center bg-primary text-primary-foreground font-semibold text-base"><?= htmlspecialchars($userInitial) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/proyecto7mo/index.php?logout=1" class="border border-border text-foreground hover:bg-secondary hover:text-primary px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200">Salir</a>
                </div>
            <?php else: ?>
                <a href="/proyecto7mo/Frontend/login.php" class="border border-border text-foreground hover:bg-secondary hover:text-primary px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200">Log In</a>
                <a href="/proyecto7mo/Frontend/sing_up.php" class="bg-primary text-primary-foreground hover:bg-primary/90 px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200 shadow-md hover:shadow-primary/20">Sign Up</a>
            <?php endif; ?>
        </div>

        <!-- Mobile Menu Button -->
        <button id="menu-toggle" class="text-foreground md:hidden p-2 rounded-lg hover:bg-secondary focus:outline-none transition-colors duration-200">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <span class="sr-only">Toggle menu</span>
        </button>
    </div>

    <!-- Mobile Navigation -->
    <div id="mobile-menu" class="hidden border-t border-border bg-background md:hidden transition-all duration-300">
        <nav class="container mx-auto flex flex-col gap-4 px-4 py-6">
            <a href="/proyecto7mo/index.php" class="text-sm font-semibold <?= $is_home ? 'text-primary' : 'text-muted-foreground' ?> hover:text-primary transition-colors duration-200">Inicio</a>
            <a href="/proyecto7mo/Frontend/explorar.php" class="text-sm font-semibold <?= $is_explorar ? 'text-primary' : 'text-muted-foreground' ?> hover:text-primary transition-colors duration-200">Explorar</a>
            <a href="/proyecto7mo/Frontend/generos.php" class="text-sm font-semibold <?= $is_generos ? 'text-primary' : 'text-muted-foreground' ?> hover:text-primary transition-colors duration-200">Géneros</a>
            <a href="/proyecto7mo/Frontend/ranking.php" class="text-sm font-semibold <?= $is_ranking ? 'text-primary' : 'text-muted-foreground' ?> hover:text-primary transition-colors duration-200">Ranking</a>
            <a href="/proyecto7mo/Frontend/explorar.php?focus=search" class="text-left text-sm font-semibold text-muted-foreground hover:text-primary transition-colors duration-200 flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Buscar
            </a>
            <a href="/proyecto7mo/Frontend/user.php#favoritas" class="text-left text-sm font-semibold text-muted-foreground hover:text-primary transition-colors duration-200 flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
                Favoritos
            </a>
            <div class="border-t border-border pt-4 mt-2">
                <?php if ($user): ?>
                    <div class="flex flex-col gap-3">
                        <a href="/proyecto7mo/Frontend/user.php" class="inline-flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full overflow-hidden border border-border bg-secondary">
                                <?php if ($userPhoto): ?>
                                    <img src="<?= htmlspecialchars($userPhoto) ?>" alt="Avatar" class="h-full w-full object-cover">
                                <?php else: ?>
                                    <span class="flex h-full w-full items-center justify-center bg-primary text-primary-foreground font-semibold text-base"><?= htmlspecialchars($userInitial) ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="text-sm text-muted-foreground">Mi perfil</span>
                        </a>
                        <?php if (in_array($user['role'] ?? '', ['admin', 'superadmin'], true)): ?>
                            <a href="/proyecto7mo/Frontend/admin.php" class="text-sm font-semibold text-muted-foreground hover:text-primary transition-colors duration-200">Administración</a>
                            <a href="/proyecto7mo/Frontend/approve_movies.php" class="text-sm font-semibold <?= $is_approval ? 'text-primary' : 'text-muted-foreground' ?> hover:text-primary transition-colors duration-200">Aprobar Películas</a>
                        <?php endif; ?>
                        <a href="/proyecto7mo/index.php?logout=1" class="text-center border border-border text-foreground hover:bg-secondary hover:text-primary px-4 py-2 rounded-lg font-semibold transition-all duration-200">Salir</a>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col gap-2">
                        <a href="/proyecto7mo/Frontend/login.php" class="text-center border border-border text-foreground hover:bg-secondary hover:text-primary px-4 py-2 rounded-lg font-semibold transition-all duration-200">Iniciar Sesión</a>
                        <a href="/proyecto7mo/Frontend/sing_up.php" class="text-center bg-primary text-primary-foreground hover:bg-primary/90 px-4 py-2 rounded-lg font-semibold transition-all duration-200 shadow-md">Registrarse</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function (event) {
            if (!mobileMenu.contains(event.target) && !menuToggle.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });
    }
});
</script>
