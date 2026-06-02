<?php
session_start();

// Si ya está autenticado, redirigir al inicio
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
                header('Location: /proyecto7mo/index.php');
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
  <link rel="stylesheet" href="style/styles.css">
  <link rel="icon" href="../public/nhlogo.png" type="image/png">
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
  <?php include 'header.php'; ?>

  <main class="flex-1 flex items-center justify-center py-12 px-4">
    
    <div class="signup-container">
      <div class="signup-card">
        <div class="signup-header">
          <h1>Regístrate Gratis</h1>
        </div>

        <?php if ($authError): ?>
            <div class="error-message">
                <?= htmlspecialchars($authError) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/proyecto7mo/Frontend/sing_up.php" class="signup-form">
          <div class="form-group">
            <p class="label">Nombre completo</p>
            <input type="text" id="name" name="name" placeholder="Nombre completo" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div>

          <div class="form-group">
            <p class="label">Correo Electrónico</p>
            <input type="email" id="email" name="email" placeholder="Correo Electrónico" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>

          <div class="form-group">
            <div class="password-container">
              <p class="label">Contraseña</p>
                <input type="password" name="password" id="password" class="input" placeholder="Ingrese su contraseña" required>
                <span id="togglePassword" class="cursor-pointer select-none">🙈</span>
            </div>
          </div>

          <script>
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');

            togglePassword.addEventListener('click', function (e) {
                // Alternar el tipo de input
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                // Alternar el icono
                this.textContent = type === 'password' ? '🙈' : '🙉';
            });
          </script>

          <button type="submit" class="btn-primary">Registrarse</button>

        </form>

        <div class="divider">
          <hr class="separador">
            <span class="divider-text">O utilice</span>
          <hr class="separador">

        </div>  
        <div class="social-buttons">
            <div id="g_id_onload"
                 data-client_id="529275541215-1hacn0m3fhjt9j373kbqttumehhe0ksu.apps.googleusercontent.com"
                 data-callback="handleCredentialResponse">
            </div>

            <div class="google-button-wrapper">
              <div class="g_id_signin"
                   data-type="standard"
                   data-size="large"
                   data-theme="filled_black"
                   data-text="signup_with"
                   data-shape="pill"
                   data-logo_alignment="left">
              </div>
            </div>
        </div>
        <p class="text-center text-xs text-muted-foreground mt-4">
            ¿Ya tienes una cuenta? <a href="/proyecto7mo/Frontend/login.php" class="text-primary hover:underline font-semibold">Inicia sesión</a>
        </p>

      </div>
    </div>
  </main>
  <script>
    async function handleCredentialResponse(response) {
      const res = await fetch('/proyecto7mo/Frontend/google-login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ credential: response.credential })
      });
      const data = await res.json();
      if (data.success) {
        window.location.href = '/proyecto7mo/index.php';
      } else {
        alert('Error con Google Login');
      }
    }
  </script>
</body>
</html>

