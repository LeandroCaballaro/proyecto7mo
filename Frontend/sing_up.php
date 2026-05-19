<?php
session_start();

// Si ya está autenticado, redirigir al inicio
if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

define('API_URL', 'http://localhost/proyecto7mo-main/Backend/public/api.php');

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
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($name) && !empty($email) && !empty($password)) {
        if (strlen($password) < 6) {
            $authError = 'La contraseña debe tener al menos 6 caracteres';
        } else {
            $res = api_post('auth/register', [
                'name'     => $name,
                'email'    => $email,
                'password' => $password
            ]);

            if (!empty($res['token'])) {
                $_SESSION['token'] = $res['token'];
                $_SESSION['user'] = $res['user'];
                header('Location: index.php');
                exit;
            } else {
                $authError = $res['error'] ?? 'Error al registrar la cuenta';
            }
        }
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
  <title>Regístrate Gratis - NexoHub</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style/sing_up.css">
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
  <?php include 'header.php'; ?>

  <main class="flex-1 flex items-center justify-center py-12 px-4">
    <div class="signup-container mt-6">
      <div class="signup-card">
        <div class="signup-header">
          <h1>Regístrate gratis</h1>
        </div>

        <?php if ($authError): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-xs text-center">
                <?= htmlspecialchars($authError) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="sing_up.php" class="signup-form">
          <div class="form-group">
            <input type="text" id="name" name="name" placeholder="Nombre completo" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div>

          <div class="form-group">
            <input type="email" id="email" name="email" placeholder="Correo electrónico" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>

          <div class="form-group">
            <input type="password" id="password" name="password" placeholder="Contraseña (mín. 6 caracteres)" required>
          </div>

          <button type="submit" class="btn-primary">Registrarse</button>

          <p class="terms-text">
            Al registrarte, aceptas nuestros <a href="#">términos de uso</a> y políticas.
          </p>
        </form>

        <div class="divider">
          <span class="divider-text">¿Ya tienes una cuenta?</span>
        </div>

        <div class="social-buttons">
          <a href="login.php" class="btn-social text-center block w-full py-2 bg-secondary text-foreground hover:bg-secondary/80 rounded transition-colors text-sm font-semibold">
            Inicia Sesión
          </a>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
