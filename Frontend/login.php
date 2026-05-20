<?php
session_start();

// Si ya está autenticado, redirigir al inicio
if (!empty($_SESSION['user'])) {
    header('Location: ../index.php');
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
            header('Location: ../index.php');
            exit;
        } else {
            $authError = $res['error'] ?? 'Credenciales incorrectas';
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/login.css">
    <link rel="stylesheet" href="style/styles.css">
    <title>Iniciar Sesión - NexoHub</title>
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
    <?php include 'header.php'; ?>

    <main class="flex-1 flex items-center justify-center py-12 px-4">
        <form method="POST" action="/proyecto7mo/Frontend/login.php" class="contenedor">
            <h1 class="titulo">Inicia Sesión</h1>
            <br>
            
            <?php if ($authError): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-sm text-center">
                    <?= htmlspecialchars($authError) ?>
                </div>
            <?php endif; ?>

            <div class="labels">
                <div class="labeluser">
                    <p class="label">Correo Electrónico</p>
                    <input type="email" name="email" class="input" placeholder="Ingrese su correo" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="labelpass">
                    <p class="label">Contraseña</p> 
                    <div class="password-container">
                        <input type="password" name="password" id="password" class="input" placeholder="Ingrese su contraseña" required>
                        <span id="togglePassword" class="cursor-pointer select-none">🙈</span>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn">Iniciar Sesión</button>
                
                <hr class="separador">

            <div class="divider">
          <span class="divider-text">O inicie sesión con</span>
            </div>  
            <div class="social-buttons">
                <button class="btn-social btn-google">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Iniciar sesión con Google
                </button>
            </div>
            
            <p class="text-center text-xs text-muted-foreground mt-4">
                ¿No tienes cuenta? <a href="/proyecto7mo/Frontend/sing_up.php" class="text-primary hover:underline font-semibold">Regístrate gratis</a>
            </p>
        </form>
    </main>

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
</body>
</html>
