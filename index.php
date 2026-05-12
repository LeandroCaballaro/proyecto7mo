<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexoHub - Reseñas de Películas con Sistema de Reputación</title>
    <meta name="description" content="Plataforma de reseñas de películas con sistema de reputación por género. Conecta con cinéfilos, descubre películas y comparte tus opiniones.">
    <link rel="icon" href="/icon-light-32x32.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="/icon-dark-32x32.png" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="style/styles.css" rel="stylesheet">

</head>
<body>
    <?php include 'header.php'; ?>
    <main>
        <?php include 'hero-section.php'; ?>
        <?php include 'features-section.php'; ?>
        <?php include 'genres-section.php'; ?>
        <?php include 'featured-movies-section.php'; ?>
        <?php include 'top-reviewers-section.php'; ?>
        <?php include 'cta-section.php'; ?>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>