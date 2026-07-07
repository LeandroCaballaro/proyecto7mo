<?php
session_start();
require_once __DIR__ . '/../Backend/models/Database.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$message = '';
$success = false;

function strong_reset_password(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT prt.user_id, u.email
    FROM password_reset_tokens prt
    JOIN users u ON u.id = prt.user_id
    WHERE prt.token = ? AND prt.used_at IS NULL AND prt.expires_at > NOW()
    LIMIT 1
");
$stmt->execute([$token]);
$reset = $token !== '' ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$reset) {
        $message = 'El token no es valido o ya expiro.';
    } elseif ($password !== $confirm) {
        $message = 'Las contrasenas no coinciden.';
    } elseif (!strong_reset_password($password)) {
        $message = 'La contrasena debe tener al menos 8 caracteres, una mayuscula, una minuscula y un numero.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, (int) $reset['user_id']]);
        $db->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE token = ?")->execute([$token]);
        $db->prepare("DELETE FROM api_tokens WHERE user_id = ?")->execute([(int) $reset['user_id']]);
        $success = true;
        $message = 'Contrasena actualizada correctamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nueva contrasena - NexoHub</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style/login.css">
  <link rel="stylesheet" href="style/styles.css">
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
  <?php include 'header.php'; ?>
  <main class="flex-1 flex items-center justify-center py-12 px-4">
    <form method="POST" class="contenedor reset-container">
      <h1 class="titulo">Nueva contrasena</h1>
      <?php if ($message): ?><div class="error-message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
      <?php if (!$success && $reset): ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="labels">
          <div>
            <p class="label">Nueva contrasena</p>
            <input type="password" name="password" class="input" required>
          </div>
          <div>
            <p class="label">Confirmar contrasena</p>
            <input type="password" name="confirm_password" class="input" required>
          </div>
        </div>
        <button type="submit" class="btn">Cambiar contrasena</button>
      <?php elseif (!$reset): ?>
        <p class="text-center text-sm text-muted-foreground mt-4">Solicita un nuevo enlace de recuperacion.</p>
      <?php endif; ?>
      <p class="text-center text-xs text-muted-foreground mt-4">
        <a href="/proyecto7mo/Frontend/login.php" class="text-primary hover:underline font-semibold">Volver al login</a>
      </p>
    </form>
  </main>
</body>
</html>
