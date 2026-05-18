<?php $user = $_SESSION['user'] ?? null; ?>
<header class="sticky top-0 z-50 w-full border-b border-border bg-background/95 backdrop-blur">
    <div class="container mx-auto flex h-16 items-center justify-between px-4">
        <a href="index.php" class="flex items-center gap-2">
            <span class="text-xl font-bold">Nexo<span class="text-primary">Hub</span></span>
        </a>
        <nav class="hidden md:flex gap-6">
            <a href="index.php">Inicio</a>
            <a href="explorar.php">Explorar</a>
            <a href="index.php#generos">Géneros</a>
            <a href="index.php#ranking">Ranking</a>
        </nav>
        <div class="hidden md:flex gap-3">
            <?php if ($user): ?>
                <span class="text-sm">Hola, <?= htmlspecialchars($user['name']) ?></span>
                <a href="index.php?logout=1" class="border px-4 py-2 rounded">Salir</a>
            <?php else: ?>
                <a href="index.php?login=1" class="border px-4 py-2 rounded">Iniciar Sesión</a>
                <a href="index.php?registro=1" class="bg-primary text-primary-foreground px-4 py-2 rounded">Registrarse</a>
            <?php endif; ?>
        </div>
    </div>
</header>
