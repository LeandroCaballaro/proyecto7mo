<?php
session_start();
require_once __DIR__ . '/../Backend/models/Database.php';

$message = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Ingresa un correo valido.';
    } else {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();

        if ($userId) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $db->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL")
                ->execute([(int) $userId]);
            $db->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)")
                ->execute([(int) $userId, $token, $expiresAt]);
            $resetLink = '/proyecto7mo/Frontend/reset_password.php?token=' . urlencode($token);
        }

        $message = 'Si el correo existe, se genero un enlace de recuperacion.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar contrasena - NexoHub</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style/login.css">
  <link rel="stylesheet" href="style/styles.css">
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
  <?php include 'header.php'; ?>
  <main class="flex-1 flex items-center justify-center py-12 px-4">
    <form method="POST" class="contenedor reset-container">
      <h1 class="titulo">Recuperar contrasena</h1>
      <?php if ($message): ?><div class="error-message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
      <div class="labels">
        <div class="labeluser">
          <p class="label">Correo Electronico</p>
          <input type="email" name="email" class="input" placeholder="Ingrese su correo" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <button type="submit" class="btn">Generar token</button>
      <?php if ($resetLink): ?>
        <div class="reset-token-box">
          <p>Enlace de recuperacion:</p>
          <a href="<?= htmlspecialchars($resetLink) ?>"><?= htmlspecialchars($resetLink) ?></a>
        </div>
      <?php endif; ?>
      <p class="text-center text-xs text-muted-foreground mt-4">
        <a href="/proyecto7mo/Frontend/login.php" class="text-primary hover:underline font-semibold">Volver al login</a>
      </p>
    </form>
  </main>
</body>
</html>
