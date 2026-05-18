<?php
/* Copiado desde raíz */
?>
<header class="sticky top-0 z-50 w-full border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
    <div class="container mx-auto flex h-16 items-center justify-between px-4">
        <!-- Logo -->
        <a href="index.php" class="flex items-center gap-2">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary">
                <svg class="h-6 w-6 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16l13-8L7 4z"></path>
                </svg>
            </div>
            <span class="text-xl font-bold text-foreground">
                Nexo<span class="text-primary">Hub</span>
            </span>
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden items-center gap-6 md:flex">
            <a href="index.php" class="text-sm font-medium text-foreground transition-colors hover:text-primary">Inicio</a>
            <a href="explorar.php" class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary">Explorar</a>
            <a href="index.php#generos" class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary">Géneros</a>
            <a href="index.php#ranking" class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary">Ranking</a>
        </nav>

        <!-- Desktop Actions -->
        <div class="hidden items-center gap-3 md:flex">
            <button class="text-muted-foreground hover:text-foreground p-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span class="sr-only">Buscar</span>
            </button>
            <button class="text-muted-foreground hover:text-foreground p-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
                <span class="sr-only">Favoritos</span>
            </button>
            <button class="border border-border text-foreground hover:bg-secondary px-4 py-2 rounded"><a href="../login.php">Log In</a></button>
            <button class="bg-primary text-primary-foreground hover:bg-primary/90 px-4 py-2 rounded"><a href="../signup.php">Sign Up</a></button>
        </div>

        <!-- Mobile Menu Button -->
        <button id="menu-toggle" class="text-foreground md:hidden p-2">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <span class="sr-only">Toggle menu</span>
        </button>
    </div>

    <!-- Mobile Navigation -->
    <div id="mobile-menu" class="hidden border-t border-border bg-background md:hidden">
        <nav class="container mx-auto flex flex-col gap-4 px-4 py-4">
            <a href="index.php" class="text-sm font-medium text-foreground transition-colors hover:text-primary">Inicio</a>
            <a href="explorar.php" class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary">Explorar</a>
            <a href="index.php#generos" class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary">Géneros</a>
            <a href="index.php#ranking" class="text-sm font-medium text-muted-foreground transition-colors hover:text-primary">Ranking</a>
            <button class="text-left text-sm font-medium text-muted-foreground transition-colors hover:text-primary">Buscar</button>
            <button class="text-left text-sm font-medium text-muted-foreground transition-colors hover:text-primary">Favoritos</button>
            <button class="text-left border border-border text-foreground hover:bg-secondary px-4 py-2 rounded mt-2"><a href="../login.php">Iniciar Sesión</a></button>
            <button class="text-left bg-primary text-primary-foreground hover:bg-primary/90 px-4 py-2 rounded"><a href="signup.php">Registrarse</a></button>
        </nav>
    </div>
</header>

<script>
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    menuToggle.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });
</script>
