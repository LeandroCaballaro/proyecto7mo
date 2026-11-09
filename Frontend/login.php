<?php
session_start();

if (!empty($_SESSION['user'])) {
    header('Location: /proyecto7mo/index.php');
    exit;
}

require_once __DIR__ . '/api_helper.php';

$authError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($identifier) && !empty($password)) {
        $res = api_post('auth/login', [
            'identifier' => $identifier,
            'password' => $password
        ]);

        if (!empty($res['token'])) {
            $_SESSION['token'] = $res['token'];
            $_SESSION['user'] = $res['user'];
            header('Location: /proyecto7mo/index.php');
            exit;
        }

        $authError = $res['error'] ?? 'Credenciales incorrectas';
    } else {
        $authError = 'Por favor complete todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesion - NexoHub</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/login.css">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="icon" href="public/nhlogo.png" type="image/png">
</head>
<body class="bg-background text-foreground flex items-center justify-center min-h-screen">
  <div class="card p-8 rounded-lg shadow-lg w-full max-w-md">
    <div class="logo">
      <img src="public/NexoHub%20Logo.png" alt="NexoHub Logo">
    </div>

    <?php if (!empty($authError)): ?>
      <div class="bg-red-500/20 border border-red-500 text-red-200 p-3 rounded mb-4 text-sm">
        <?= htmlspecialchars($authError) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="/proyecto7mo/Frontend/login.php" class="contenedor">
      <div class="mb-4">
        <label for="identifier" class="block text-sm font-medium mb-1">Email o Nombre de Usuario</label>
        <input
          type="text"
          id="identifier"
          name="identifier"
          required
          autocomplete="username"
          class="w-full px-3 py-2 bg-secondary border border-border rounded text-foreground focus:outline-none focus:border-primary"
          placeholder="gonzalo o gonzalo@ejemplo.com"
          value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
        >
      </div>

      <div class="mb-6">
        <label for="password" class="block text-sm font-medium mb-1">Contrasena</label>
        <input
          type="password"
          id="password"
          name="password"
          required
          autocomplete="current-password"
          class="w-full px-3 py-2 bg-secondary border border-border rounded text-foreground focus:outline-none focus:border-primary"
          placeholder="••••••••"
        >
      </div>

      <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white font-semibold py-2 px-4 rounded transition duration-200">
        Iniciar Sesion
      </button>
    </form>

    <div class="mt-4 text-center text-sm">
      <a href="/proyecto7mo/Frontend/forgot_password.php" class="text-primary hover:underline font-semibold">Olvidaste tu contrasena?</a>
    </div>

    <div class="mt-2 text-center text-sm text-muted-foreground">
      No tienes cuenta? <a href="/proyecto7mo/Frontend/sing_up.php" class="text-primary hover:underline font-semibold">Registrate gratis</a>
    </div>
  </div>
</body>
</html>
