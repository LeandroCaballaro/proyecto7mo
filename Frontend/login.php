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
            header('Location: index.php');
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
    <title>Iniciar Sesión - NexoHub</title>
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
    <?php include 'header.php'; ?>

    <main class="flex-1 flex items-center justify-center py-12 px-4">
        <form method="POST" action="login.php" class="contenedor">
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
            
            <p class="text-center text-xs text-muted-foreground mt-4">
                ¿No tienes cuenta? <a href="sing_up.php" class="text-primary hover:underline font-semibold">Regístrate gratis</a>
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
