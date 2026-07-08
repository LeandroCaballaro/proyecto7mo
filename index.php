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

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexoHub</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="Frontend/assets/css/styles.css" rel="stylesheet">
    <link rel="icon" href="Frontend/public/nhlogo.png" type="image/png">
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
    <?php include 'Frontend/components/header.php'; ?>

    <main class="flex-1">
        <?php include 'Frontend/components/hero-section.php'; ?>
        <?php include 'Frontend/components/features-section.php'; ?>
        <?php include 'Frontend/components/genres-section.php'; ?>
        <?php include 'Frontend/components/featured-movies-section.php'; ?>
        <?php include 'Frontend/components/cta-section.php'; ?>
    </main>
    
    <?php include 'Frontend/components/footer.php'; ?>
</body>
</html>
