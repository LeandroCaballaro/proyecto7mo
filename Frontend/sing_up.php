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
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $allowedDomains = ['gmail.com', 'hotmail.com', 'live.com', 'outlook.com', 'yahoo.com', 'icloud.com', 'proton.me','abc.gob.ar','edu.ar'];
    $emailDomain = strtolower(substr(strrchr($email, '@') ?: '', 1));

    if ($name === '' || $username === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $authError = 'Por favor complete todos los campos';
    } elseif (!preg_match('/^[\p{L}\p{N} ]{1,40}$/u', $name)) {
        $authError = 'El nombre no puede tener caracteres especiales ni superar los 40 caracteres';
    } elseif (!preg_match('/^[A-Za-z0-9]{1,20}$/', $username)) {
        $authError = 'El nombre de usuario solo puede tener letras y numeros, hasta 20 caracteres';
    } elseif (!in_array($emailDomain, $allowedDomains, true)) {
        $authError = 'Use un correo de Gmail, Hotmail, Live, Outlook, Yahoo, iCloud, Proton, abc.gob.ar o edu.ar';
    } elseif ($password !== $confirmPassword) {
        $authError = 'Las contrasenas no coinciden';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $authError = 'La contrasena debe tener al menos 8 caracteres, una mayuscula, una minuscula y un numero';
    } else {
        $res = api_post('auth/register', [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'confirm_password' => $confirmPassword,
        ]);

        if (!empty($res['token'])) {
            $_SESSION['token'] = $res['token'];
            $_SESSION['user'] = $res['user'];
            header('Location: /proyecto7mo/index.php');
            exit;
        }

        $authError = $res['error'] ?? 'Error al registrar la cuenta';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrate Gratis - NexoHub</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style/sing_up.css">
  <link rel="stylesheet" href="style/styles.css">
  <link rel="icon" href="../public/nhlogo.png" type="image/png">
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
  <?php include 'header.php'; ?>

  <main class="flex-1 flex items-center justify-center py-12 px-4">
    <div class="signup-container">
      <div class="signup-card">
        <div class="signup-header">
          <h1>Registrate Gratis</h1>
        </div>

        <?php if ($authError): ?>
          <div class="error-message">
            <?= htmlspecialchars($authError) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="/proyecto7mo/Frontend/sing_up.php" class="signup-form">
          <div class="form-group">
            <p class="label">Nombre completo</p>
            <input type="text" id="name" name="name" placeholder="Nombre completo" required maxlength="40" pattern="[\p{L}\p{N} ]{1,40}" title="Solo letras, numeros y espacios. Maximo 40 caracteres" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div>

          <div class="form-group">
            <p class="label">Nombre de Usuario</p>
            <input type="text" id="username" name="username" placeholder="Nombre de Usuario" required maxlength="20" pattern="[A-Za-z0-9]{1,20}" title="Solo letras y numeros. Maximo 20 caracteres" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
          </div>

          <div class="form-group">
            <p class="label">Correo Electronico</p>
            <input type="email" id="email" name="email" placeholder="Correo Electronico" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>

          <div class="form-group">
            <div class="password-container">
              <p class="label">Contrasena</p>
              <input type="password" name="password" id="password" class="input" placeholder="Minimo 8 caracteres" required minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}" title="Debe tener al menos 8 caracteres, una mayuscula, una minuscula y un numero">
              <span id="togglePassword" class="password-toggle cursor-pointer select-none" role="button" aria-label="Mostrar contrasena"></span>
            </div>
          </div>

          <div class="form-group">
            <div class="password-container">
              <p class="label">Confirmar contrasena</p>
              <input type="password" name="confirm_password" id="confirmPassword" class="input" placeholder="Repita su contrasena" required minlength="8">
              <span id="toggleConfirmPassword" class="password-toggle cursor-pointer select-none" role="button" aria-label="Mostrar contrasena"></span>
            </div>
          </div>

          <button type="submit" class="btn-primary">Registrarse</button>
        </form>

        <p class="text-center text-xs text-muted-foreground mt-4">
          Ya tienes una cuenta? <a href="/proyecto7mo/Frontend/login.php" class="text-primary hover:underline font-semibold">Inicia sesion</a>
        </p>
      </div>
    </div>
  </main>

  <script>
    const togglePassword = document.querySelector('#togglePassword');
    const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
    const password = document.querySelector('#password');
    const confirmPassword = document.querySelector('#confirmPassword');
    const showIcon = '🙈';
    const hideIcon = '🙉';

    if (togglePassword && password) {
      togglePassword.textContent = showIcon;
      togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.textContent = type === 'password' ? showIcon : hideIcon;
        this.setAttribute('aria-label', type === 'password' ? 'Mostrar contrasena' : 'Ocultar contrasena');
      });
    }

    if (toggleConfirmPassword && confirmPassword) {
      toggleConfirmPassword.textContent = showIcon;
      toggleConfirmPassword.addEventListener('click', function () {
        const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPassword.setAttribute('type', type);
        this.textContent = type === 'password' ? showIcon : hideIcon;
        this.setAttribute('aria-label', type === 'password' ? 'Mostrar contrasena' : 'Ocultar contrasena');
      });
    }

    const signupForm = document.querySelector('.signup-form');
    const nameInput = document.querySelector('#name');
    const username = document.querySelector('#username');
    const email = document.querySelector('#email');
    const allowedDomains = ['gmail.com', 'hotmail.com', 'live.com', 'outlook.com', 'yahoo.com', 'icloud.com', 'proton.me'];

    if (signupForm && nameInput && username && email && password && confirmPassword) {
      nameInput.addEventListener('input', () => nameInput.setCustomValidity(''));
      username.addEventListener('input', () => username.setCustomValidity(''));
      email.addEventListener('input', () => email.setCustomValidity(''));
      confirmPassword.addEventListener('input', () => confirmPassword.setCustomValidity(''));
      password.addEventListener('input', () => confirmPassword.setCustomValidity(''));

      signupForm.addEventListener('submit', function (event) {
        const domain = email.value.split('@').pop().toLowerCase();
        const namePattern = /^[\p{L}\p{N} ]{1,40}$/u;
        const usernamePattern = /^[A-Za-z0-9]{1,20}$/;

        if (!namePattern.test(nameInput.value.trim())) {
          nameInput.setCustomValidity('Solo letras, numeros y espacios. Maximo 40 caracteres.');
        } else {
          nameInput.setCustomValidity('');
        }

        if (!usernamePattern.test(username.value.trim())) {
          username.setCustomValidity('Solo letras y numeros. Maximo 20 caracteres.');
        } else {
          username.setCustomValidity('');
        }

        if (!allowedDomains.includes(domain)) {
          email.setCustomValidity('Use Gmail, Hotmail, Live, Outlook, Yahoo, iCloud o Proton.');
        } else {
          email.setCustomValidity('');
        }

        if (password.value !== confirmPassword.value) {
          confirmPassword.setCustomValidity('Las contrasenas no coinciden.');
        } else {
          confirmPassword.setCustomValidity('');
        }

        if (!signupForm.checkValidity()) {
          event.preventDefault();
          signupForm.reportValidity();
        }
      });
    }
  </script>
</body>
</html>
