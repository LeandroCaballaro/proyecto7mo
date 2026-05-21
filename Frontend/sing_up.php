<?php
session_start();

// Si ya está autenticado, redirigir al inicio
if (!empty($_SESSION['user'])) {
<<<<<<< HEAD
    header('Location: /proyecto7mo/index.php');
=======
    header('Location: ../index.php');
>>>>>>> 7f18f1ab49d683819ce880bd7457e3da0465fd73
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
<<<<<<< HEAD
                header('Location: /proyecto7mo/index.php');
=======
                header('Location: ../index.php');
>>>>>>> 7f18f1ab49d683819ce880bd7457e3da0465fd73
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
<<<<<<< HEAD
<script src="https://accounts.google.com/gsi/client" async defer></script>
=======
>>>>>>> 7f18f1ab49d683819ce880bd7457e3da0465fd73
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
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-xs text-center">
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

        <hr class="separador">

        <div class="divider">
          <span class="divider-text">O utilice</span>
        </div>  
        <div class="social-buttons">
        <button class="btn-social btn-google">
<<<<<<< HEAD
<div id="g_id_onload"
     data-client_id="529275541215-1hacn0m3fhjt9j373kbqttumehhe0ksu.apps.googleusercontent.com"
     data-callback="handleCredentialResponse">
</div>

<div class="g_id_signin"
     data-type="standard"
     data-size="large"
     data-theme="outline"
     data-text="signup_with"
     data-shape="rectangular"
     data-logo_alignment="left">
</div>
=======
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          Registrate con Google
        </button>
>>>>>>> 7f18f1ab49d683819ce880bd7457e3da0465fd73
      </div>
        <p class="text-center text-xs text-muted-foreground mt-4">
                ¿Ya tienes una cuenta? <a href="/proyecto7mo/Frontend/login.php" class="text-primary hover:underline font-semibold">Inicia sesión</a>
            </p>

      </div>
    </div>
  </main>
<<<<<<< HEAD
  <script>
    async function handleCredentialResponse(response) {
fetch('/proyecto7mo/Frontend/google-login.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        credential: response.credential
    })
})
.then(res => res.json())
.then(data => {

    if (data.success) {

        window.location.href = '/proyecto7mo/index.php';

    } else {

        alert('Error con Google Login');

    }

});

}
</script>
</body>
</html>
=======
</body>
</html>
>>>>>>> 7f18f1ab49d683819ce880bd7457e3da0465fd73
