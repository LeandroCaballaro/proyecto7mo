<?php
session_start();

if (!empty($_SESSION['user'])) {
    header('Location: /proyecto7mo/index.php');
    exit;
}

define('API_URL', 'http://localhost/proyecto7mo/Backend/public/api.php');

function api_post($route, $data)
{
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n",
            'content'       => json_encode($data),
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents(API_URL . '?route=' . urlencode($route), false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}

$authError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $res = api_post('auth/login', [
            'email'    => $email,
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
  <link rel="stylesheet" href="style/login.css">
  <link rel="stylesheet" href="style/styles.css">
  <link rel="icon" href="../public/nhlogo.png" type="image/png">
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
  <?php include 'header.php'; ?>

  <main class="flex-1 flex items-center justify-center py-12 px-4">
    <form method="POST" action="/proyecto7mo/Frontend/login.php" class="contenedor">
      <h1 class="titulo">Inicia Sesion</h1>

      <?php if ($authError): ?>
        <div class="error-message">
          <?= htmlspecialchars($authError) ?>
        </div>
      <?php endif; ?>

      <div class="labels">
        <div class="labeluser">
          <p class="label">Correo Electronico</p>
          <input type="email" name="email" class="input" placeholder="Ingrese su correo" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="labelpass">
          <p class="label">Contrasena</p>
          <div class="password-container">
            <input type="password" name="password" id="password" class="input" placeholder="Ingrese su contrasena" required>
            <span id="togglePassword" class="password-toggle cursor-pointer select-none" role="button" aria-label="Mostrar contrasena"></span>
          </div>
        </div>
      </div>

      <button type="submit" class="btn">Iniciar Sesion</button>

      <p class="text-center text-xs text-muted-foreground mt-4">
        No tienes cuenta? <a href="/proyecto7mo/Frontend/sing_up.php" class="text-primary hover:underline font-semibold">Registrate gratis</a>
      </p>
    </form>
  </main>

  <script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const eyeIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    const eyeOffIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l18 18"></path><path d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6"></path><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a18.4 18.4 0 0 1-2.3 3.2"></path><path d="M6.6 6.6C3.7 8.5 2 12 2 12s3.5 7 10 7a10.7 10.7 0 0 0 4.4-.9"></path></svg>';

    if (togglePassword && password) {
      togglePassword.innerHTML = eyeIcon;
      togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.innerHTML = type === 'password' ? eyeIcon : eyeOffIcon;
        this.setAttribute('aria-label', type === 'password' ? 'Mostrar contrasena' : 'Ocultar contrasena');
      });
    }
  </script>
</body>
</html>
