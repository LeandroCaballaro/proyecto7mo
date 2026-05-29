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

function api_post($route, $data)
{
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n",
            'content'       => json_encode($data),
            'ignore_errors' => true,   // leer respuesta aunque sea 4xx/5xx
        ],
    ]);
    $raw = @file_get_contents(API_URL . '?route=' . urlencode($route), false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}

$authError = '';
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth'])) {
    if ($_POST['auth'] === 'login') {
        $res = api_post('auth/login', ['email' => $_POST['email'] ?? '', 'password' => $_POST['password'] ?? '']);
    } else {
        $res = api_post('auth/register', ['name' => $_POST['name'] ?? '', 'email' => $_POST['email'] ?? '', 'password' => $_POST['password'] ?? '']);
    }
    if (!empty($res['token'])) {
        $_SESSION['token'] = $res['token'];
        $_SESSION['user'] = $res['user'];
        header('Location: index.php');
        exit;
    }
    $authError = $res['error'] ?? 'Error de autenticación';
}

$showLogin = isset($_GET['login']);
$showRegistro = isset($_GET['registro']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexoHub</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="style/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <?php if ($showLogin || $showRegistro): ?>
    <section class="container mx-auto px-4 py-8 max-w-md">
        <h2 class="text-xl font-bold mb-4"><?= $showLogin ? 'Iniciar sesión' : 'Crear cuenta' ?></h2>
        <?php if ($authError): ?><p class="text-red-600 mb-4"><?= htmlspecialchars($authError) ?></p><?php endif; ?>
        <form method="post" class="space-y-3">
            <input type="hidden" name="auth" value="<?= $showLogin ? 'login' : 'register' ?>">
            <?php if ($showRegistro): ?>
            <input type="text" name="name" placeholder="Nombre" required class="w-full border rounded px-3 py-2">
            <?php endif; ?>
            <input type="email" name="email" placeholder="Email" required class="w-full border rounded px-3 py-2">
            <input type="password" name="password" placeholder="Contraseña" required minlength="6" class="w-full border rounded px-3 py-2">
            <button type="submit" class="w-full bg-primary text-white py-2 rounded">Enviar</button>
        </form>
    </section>
    <?php endif; ?>

    <main>
        <?php include 'hero-section.php'; ?>
        <?php include 'features-section.php'; ?>
        <?php include 'genres-section.php'; ?>
        <div class="w-full border-t border-border opacity-30"></div>
        <?php include 'featured-movies-section.php'; ?>
        <?php include 'top-reviewers-section.php'; ?>
        <?php include 'cta-section.php'; ?>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
