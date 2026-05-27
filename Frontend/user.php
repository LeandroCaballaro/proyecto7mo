<?php
session_start();

// manejo de peticiones ajax
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    require_once __DIR__ . '/../Backend/models/Database.php';
    header('Content-Type: application/json');

    $action  = $_POST['action'] ?? $_GET['action'] ?? '';
    $session = $_SESSION['user'] ?? null;

    if (!$session) { 
        echo json_encode(['ok' => false, 'msg' => 'no autenticado']); 
        exit; 
    }

    $user_id = $session['id'];
    $db      = Database::getInstance()->getConnection();
    
    // actualizar perfil
    if ($action === 'update_profile') {
        $new_name = trim($_POST['name'] ?? '');
        $new_desc = trim($_POST['description'] ?? '');

        try {
            if ($new_name !== '') {
                $db->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$new_name, $user_id]);
                $_SESSION['user']['name'] = $new_name;
            }
            if ($new_desc !== '') {
                try {
                    $db->prepare("UPDATE users SET description = ? WHERE id = ?")->execute([$new_desc, $user_id]);
                } catch (\PDOException $ex) {
                    $db->exec("ALTER TABLE users ADD COLUMN description VARCHAR(150) DEFAULT ''");
                    $db->prepare("UPDATE users SET description = ? WHERE id = ?")->execute([$new_desc, $user_id]);
                }
            }
            echo json_encode(['ok' => true, 'name' => $_SESSION['user']['name']]);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // subir foto de perfil
    if ($action === 'upload_photo') {
        $file = $_FILES['photo'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'msg' => 'error al subir archivo']); 
            exit;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            echo json_encode(['ok' => false, 'msg' => 'tipo de archivo no permitido']); 
            exit;
        }

        $dir = __DIR__ . '/uploads/avatars/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $user_id . '.' . $ext;
        $dest     = $dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $url = '/proyecto7mo/Frontend/uploads/avatars/' . $filename . '?v=' . time();
            try {
                $db->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$url, $user_id]);
            } catch (\PDOException $ex) {
                $db->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT ''");
                $db->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$url, $user_id]);
            }
            echo json_encode(['ok' => true, 'url' => $url]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'no se pudo mover el archivo']);
        }
        exit;
    }

    // obtener reseñas del usuario
    if ($action === 'get_reviews') {
        try {
            // consulta con join a movies y series
            $stmt = $db->prepare("
                SELECT 
                    r.id,
                    r.rating,
                    r.comment,
                    r.created_at,
                    r.media_title,
                    r.poster_url,
                    COALESCE(m.title, s.title, r.media_title, 'sin titulo') AS titulo,
                    COALESCE(m.poster_url, s.poster_url, r.poster_url, '') AS poster
                FROM reviews r
                LEFT JOIN movies m ON m.id = r.movie_id AND r.movie_id IS NOT NULL
                LEFT JOIN series s ON s.id = r.series_id AND r.series_id IS NOT NULL
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $reviews = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // formatear fechas
            foreach ($reviews as &$review) {
                if ($review['created_at']) {
                    $date = new \DateTime($review['created_at']);
                    $review['formatted_date'] = $date->format('d/m/Y');
                } else {
                    $review['formatted_date'] = 'fecha desconocida';
                }
                $review['rating_stars'] = round($review['rating'] ?? 0);
                $review['comment'] = $review['comment'] ?? 'sin comentario';
            }
            
            echo json_encode(['ok' => true, 'reviews' => $reviews, 'count' => count($reviews)]);
        } catch (\Exception $e) {
            // fallback sin joins por si no existen las tablas movies o series
            try {
                $stmt = $db->prepare("
                    SELECT 
                        id, rating, comment, created_at, media_title, poster_url,
                        DATE_FORMAT(created_at, '%d/%m/%Y') as formatted_date
                    FROM reviews 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$user_id]);
                $reviews = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                echo json_encode(['ok' => true, 'reviews' => $reviews, 'count' => count($reviews)]);
            } catch (\Exception $e2) {
                echo json_encode(['ok' => false, 'msg' => $e2->getMessage(), 'reviews' => []]);
            }
        }
        exit;
    }

    // obtener actividad del usuario
    if ($action === 'get_activity') {
        $activity = [];
        
        // obtener reseñas
        try {
            $stmt = $db->prepare("
                SELECT 
                    'review' AS tipo, 
                    r.created_at,
                    CONCAT('escribiste una reseña sobre ', COALESCE(m.title, s.title, r.media_title, 'un contenido')) AS detalle,
                    COALESCE(m.title, s.title, r.media_title, '') AS titulo,
                    r.rating
                FROM reviews r
                LEFT JOIN movies m ON m.id = r.movie_id
                LEFT JOIN series s ON s.id = r.series_id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC 
                LIMIT 20
            ");
            $stmt->execute([$user_id]);
            $reviews_activity = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $activity = array_merge($activity, $reviews_activity);
        } catch (\Exception $e) {
            try {
                $stmt = $db->prepare("
                    SELECT 
                        'review' AS tipo, 
                        created_at, 
                        CONCAT('escribiste una reseña sobre ', media_title) AS detalle,
                        media_title AS titulo,
                        rating
                    FROM reviews 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 20
                ");
                $stmt->execute([$user_id]);
                $reviews_activity = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $activity = array_merge($activity, $reviews_activity);
            } catch (\Exception $e2) {}
        }
        
        // obtener respuestas a reseñas
        try {
            $stmt = $db->prepare("
                SELECT 
                    'response' AS tipo, 
                    created_at, 
                    CONCAT('respondiste a una reseña') AS detalle,
                    '' AS titulo,
                    NULL AS rating
                FROM review_responses 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 20
            ");
            $stmt->execute([$user_id]);
            $responses = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $activity = array_merge($activity, $responses);
        } catch (\Exception $e) {}

        // ordenar por fecha descendente
        usort($activity, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // formatear fechas
        foreach ($activity as &$item) {
            if ($item['created_at']) {
                $date = new \DateTime($item['created_at']);
                $item['formatted_date'] = $date->format('d/m/Y H:i');
                $item['time_ago'] = time_ago($date);
            } else {
                $item['formatted_date'] = 'fecha desconocida';
                $item['time_ago'] = '';
            }
        }
        
        echo json_encode(['ok' => true, 'activity' => array_slice($activity, 0, 20)]);
        exit;
    }

    // eliminar reseña
    if ($action === 'delete_review') {
        $review_id = $_POST['review_id'] ?? 0;
        
        if (!$review_id) {
            echo json_encode(['ok' => false, 'msg' => 'id de reseña no valido']);
            exit;
        }
        
        try {
            // verificar que la reseña pertenezca al usuario
            $stmt = $db->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
            $stmt->execute([$review_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['ok' => true, 'msg' => 'reseña eliminada correctamente']);
            } else {
                echo json_encode(['ok' => false, 'msg' => 'no se encontro la reseña o no tienes permiso']);
            }
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'accion desconocida']);
    exit;
}

// funcion para mostrar tiempo relativo (ej: "hace 2 horas")
function time_ago($datetime) {
    $now = new \DateTime();
    $diff = $now->diff($datetime);
    
    if ($diff->y > 0) return 'hace ' . $diff->y . ' año' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return 'hace ' . $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
    if ($diff->d > 0) return 'hace ' . $diff->d . ' día' . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0) return 'hace ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
    if ($diff->i > 0) return 'hace ' . $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
    return 'hace un momento';
}

// si no hay sesion activa redirigir al login
if (empty($_SESSION['user'])) {
    header('Location: /proyecto7mo/Frontend/login.php');
    exit;
}

require_once __DIR__ . '/../Backend/models/Database.php';

$user_name  = $_SESSION['user']['name'];
$user_email = $_SESSION['user']['email'];
$user_id    = $_SESSION['user']['id'] ?? null;

$user_initial = mb_strtoupper(mb_substr($user_name, 0, 1, 'UTF-8'));

$reputation     = 0;
$reviews_count  = 0;
$responses_count = 0;
$comments_count = 0;
$user_avatar    = '';
$user_desc      = '';

if ($user_id) {
    try {
        $db = Database::getInstance()->getConnection();

        // obtener reputacion del usuario
        $stmt = $db->prepare("SELECT reputation FROM reviewers WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $reviewer = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($reviewer) $reputation = (int) $reviewer['reputation'];

        // contar reseñas
        $stmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $reviews_count = (int) $stmt->fetchColumn();

        // contar respuestas
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM review_responses WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $responses_count = (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            $responses_count = 0;
        }

        $comments_count = $reviews_count + $responses_count;

        // obtener avatar y descripcion
        try {
            $stmt = $db->prepare("SELECT avatar, description FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$user_id]);
            $udata = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($udata) {
                $user_avatar = $udata['avatar'] ?? '';
                $user_desc   = $udata['description'] ?? '';
            }
        } catch (\Exception $e) { 
            // si las columnas no existen no pasa nada
        }

    } catch (\Exception $e) { 
        // fallback silencioso por si hay error
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - NexoHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/styles.css">
    <link rel="stylesheet" href="style/user.css">
    <style>
        /* estilos para las tarjetas de reseñas */
        .review-card {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            background: var(--card-bg, #1e1e2e);
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .review-poster {
            flex-shrink: 0;
            width: 80px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            background: #2a2a3a;
        }
        
        .review-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-poster {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .review-content {
            flex: 1;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .review-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            color: #fff;
        }
        
        .review-rating {
            color: gold;
            letter-spacing: 2px;
            font-size: 0.9rem;
        }
        
        .review-comment {
            color: #ccc;
            line-height: 1.5;
            margin: 0.5rem 0;
        }
        
        .review-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.75rem;
            font-size: 0.8rem;
        }
        
        .review-date {
            color: #888;
        }
        
        .delete-review-btn {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.2s;
        }
        
        .delete-review-btn:hover {
            background: #dc3545;
            color: white;
        }
        
        /* estilos para los items de actividad */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: background 0.2s;
        }
        
        .activity-item:hover {
            background: rgba(255,255,255,0.05);
        }
        
        .act-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .act-icon svg {
            width: 20px;
            height: 20px;
            fill: #667eea;
        }
        
        .act-text {
            flex: 1;
            color: #e0e0e0;
        }
        
        .act-text b {
            color: white;
        }
        
        .act-text em {
            color: #667eea;
            font-style: normal;
            font-weight: 500;
        }
        
        .act-date {
            font-size: 0.75rem;
            color: #888;
            flex-shrink: 0;
        }
        
        .act-time-ago {
            font-size: 0.7rem;
            color: #666;
        }
        
        /* estado vacio y loading */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #888;
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .loading-placeholder {
            text-align: center;
            padding: 2rem;
            color: #888;
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .rating-stars {
            display: inline-flex;
            gap: 2px;
        }
    </style>
</head>
<body class="perfil-body">
<div class="perfil-container">

    <!-- barra lateral de navegacion -->
    <aside class="sidebar-estatico">
        <div class="sidebar-brand">
            <a href="/proyecto7mo/index.php" class="brand-link">
                <div class="brand-logo">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 4v16l13-8L7 4z"/>
                    </svg>
                </div>
                <span class="brand-text">Nexo<span class="text-highlight">Hub</span></span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item active" data-section="perfil">
                <span class="icon-circle icon-home">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </span>
                <span class="nav-label">Mi perfil</span>
            </a>
            <a href="#" class="nav-item" data-section="resenas">
                <span class="icon-circle icon-info">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 12H5v-2h14v2zm0-3H5V9h14v2zm0-3H5V6h14v2z"/></svg>
                </span>
                <span class="nav-label">Mis reseñas</span>
            </a>
            <a href="#" class="nav-item" data-section="actividad">
                <span class="icon-circle icon-security">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 2.05v2.02c3.95.49 7 3.85 7 7.93 0 3.21-1.81 6-4.72 7.28L13 17v5h5l-1.22-1.22C19.91 19.07 22 15.76 22 12c0-5.18-3.95-9.45-9-9.95zM11 2.05C5.95 2.55 2 6.82 2 12c0 3.76 2.09 7.07 5.22 8.78L6 22h5V2.05zM11 13H9V7h2v6zm4 0h-2V7h2v6z"/></svg>
                </span>
                <span class="nav-label">Mi actividad</span>
            </a>
            <a href="#" class="nav-item" data-section="config">
                <span class="icon-circle icon-privacy">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.07.62-.07.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                </span>
                <span class="nav-label">Configuración</span>
            </a>
        </nav>
    </aside>

    <!-- contenido principal -->
    <main class="contenido-perfil">

        <header class="header-interno">
            <div class="header-seccion-titulo">
                <button class="btn-toggle-movil" id="menuMovilToggle" aria-label="abrir menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="header-brand-movil">NexoHub Perfil</span>
            </div>
            <div class="header-acciones">
                <div class="avatar-pequeno" id="smallAvatar">
                    <?php if ($user_avatar): ?>
                        <img src="<?= htmlspecialchars($user_avatar) ?>" alt="avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    <?php else: ?>
                        <span><?= $user_initial ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- seccion: mi perfil -->
        <section id="perfil" class="perfil-seccion-central content-section">
            <div class="avatar-grande-container">
                <div class="avatar-grande" id="profileAvatar"
                     <?php if ($user_avatar): ?>
                         style="background-image:url('<?= htmlspecialchars($user_avatar) ?>');background-size:cover;background-position:center;color:transparent;"
                     <?php endif; ?>>
                    <span id="profileInitial" <?= $user_avatar ? 'style="opacity:0"' : '' ?>><?= $user_initial ?></span>
                </div>
                <button class="btn-cambiar-foto" id="btnCambiarFoto" type="button" title="cambiar foto de perfil">
                    <div class="camara-overlay">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/>
                        </svg>
                    </div>
                </button>
                <input type="file" id="inputFotoPerfil" accept="image/*" style="display:none">
            </div>

            <h1 class="perfil-nombre" id="displayName"><?= htmlspecialchars($user_name) ?></h1>
            <p class="perfil-correo"><?= htmlspecialchars($user_email) ?></p>
            <button class="edit-profile-btn" id="editProfileBtn">Editar perfil</button>

            <div class="stats-perfil-container">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-comentarios">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 12H5v-2h14v2zm0-3H5V9h14v2zm0-3H5V6h14v2z"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-valor" id="statComentarios"><?= $comments_count ?></span>
                        <span class="stat-label">Comentarios</span>
                    </div>
                </div>
                <div class="stat-separador"></div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-reputacion">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-valor" id="statReputacion"><?= $reputation ?></span>
                        <span class="stat-label">Reputación</span>
                    </div>
                </div>
            </div>

            <div class="descripcion-perfil-container">
                <div class="descripcion-perfil-wrapper">
                    <span class="pencil-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </span>
                    <input type="text" class="descripcion-input" id="inputDescripcion"
                           placeholder="Agregar descripción" maxlength="100"
                           value="<?= htmlspecialchars($user_desc) ?>">
                </div>
            </div>
        </section>

        <!-- seccion: mis reseñas -->
        <section id="resenas" class="perfil-seccion-central content-section section-hidden">
            <div class="section-card">
                <div class="section-header">
                    <h2>Mis reseñas</h2>
                    <p class="section-description">Aquí verás las películas y series a las que ya les hiciste una reseña.</p>
                </div>
                <div class="review-list" id="reviewList">
                    <div class="loading-placeholder">Cargando reseñas...</div>
                </div>
            </div>
        </section>

        <!-- seccion: mi actividad -->
        <section id="actividad" class="perfil-seccion-central content-section section-hidden">
            <div class="section-card">
                <div class="section-header">
                    <h2>Mi actividad</h2>
                    <p class="section-description">Revisa tu actividad reciente en NexoHub.</p>
                </div>
                <div id="activityList">
                    <div class="loading-placeholder">Cargando actividad...</div>
                </div>
            </div>
        </section>

        <!-- seccion: configuracion -->
        <section id="config" class="perfil-seccion-central content-section section-hidden">
            <div class="section-card">
                <div class="section-header">
                    <h2>Configuración</h2>
                    <p class="section-description">Ajusta tu tema y la visibilidad de tu perfil.</p>
                </div>
                <div class="settings-card">
                    <div class="settings-row settings-card-long">
                        <div class="settings-card-title">
                            <span class="settings-label">Tema</span>
                            <p class="settings-note">Selecciona claro u oscuro para tu perfil.</p>
                        </div>
                        <label class="setting-toggle theme-toggle">
                            <input type="checkbox" id="themeToggle">
                            <span class="toggle-slider theme-slider">
                                <span class="toggle-icon sun-icon">☀</span>
                                <span class="toggle-icon moon-icon">🌙</span>
                            </span>
                            <span class="toggle-label" id="themeLabel">Oscuro</span>
                        </label>
                    </div>
                    <div class="settings-row settings-card-long">
                        <div class="settings-card-title">
                            <span class="settings-label">Privacidad</span>
                            <p class="settings-note">Elige quién puede ver tu perfil.</p>
                        </div>
                        <div class="privacy-options" id="privacyOptions">
                            <button type="button" class="privacy-option" data-value="public">Público</button>
                            <button type="button" class="privacy-option" data-value="private">Privado</button>
                            <button type="button" class="privacy-option" data-value="friends">Solo amigos</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>
</div>

<div class="sidebar-overlay-movil" id="sidebarOverlay"></div>

<!-- mensajes flotantes -->
<div id="toast"></div>

<!-- modal para editar perfil -->
<div id="editModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
    <div class="modal-content">
        <h2 id="editModalTitle">Editar perfil</h2>
        <input type="text" id="modalName" placeholder="Nuevo nombre"
               value="<?= htmlspecialchars($user_name) ?>" maxlength="80">
        <input type="text" id="modalDesc" placeholder="Nueva descripción (opcional)"
               value="<?= htmlspecialchars($user_desc) ?>" maxlength="100">
        <div class="modal-btns">
            <button class="btn-cancelar" id="cancelModalBtn">Cancelar</button>
            <button class="btn-guardar" id="saveModalBtn">Guardar</button>
        </div>
    </div>
</div>

<script>
// cuando el documento esta listo
document.addEventListener('DOMContentLoaded', () => {

    // funciones auxiliares
    const $ = id => document.getElementById(id);
    
    // mostrar mensaje temporal
    const toast = (msg, isError = false) => {
        const t = $('toast');
        t.textContent = msg;
        t.className = 'show' + (isError ? ' error' : '');
        setTimeout(() => t.className = '', 3000);
    };

    // peticion post ajax
    const ajaxPost = async (data) => {
        const fd = new FormData();
        for (const [k, v] of Object.entries(data)) fd.append(k, v);
        const r = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        return r.json();
    };

    // peticion get ajax
    const ajaxGet = async (action) => {
        const r = await fetch(`${window.location.href}?action=${action}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        return r.json();
    };

    // dibujar estrellas segun puntuacion
    function renderStars(rating) {
        const fullStars = Math.min(5, Math.floor(rating || 0));
        const emptyStars = 5 - fullStars;
        return '<span class="rating-stars">' + 
               '★'.repeat(fullStars) + 
               '☆'.repeat(emptyStars) + 
               '</span>';
    }

    // escapar html para evitar xss
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // navegacion entre secciones
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.content-section');
    let reviewsLoaded = false;
    let activityLoaded = false;

    const showSection = (id) => {
        sections.forEach(sec => sec.classList.toggle('section-hidden', sec.id !== id));
        if (id === 'resenas' && !reviewsLoaded) { 
            loadReviews(); 
            reviewsLoaded = true; 
        }
        if (id === 'actividad' && !activityLoaded) { 
            loadActivity(); 
            activityLoaded = true; 
        }
    };

    navItems.forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            navItems.forEach(n => n.classList.remove('active'));
            item.classList.add('active');
            showSection(item.dataset.section);
            const sb = document.querySelector('.sidebar-estatico');
            const ov = $('sidebarOverlay');
            if (sb?.classList.contains('open')) { 
                sb.classList.remove('open'); 
                ov.classList.remove('show'); 
            }
        });
    });

    // menu para movil
    const menuToggle = $('menuMovilToggle');
    const sidebar = document.querySelector('.sidebar-estatico');
    const overlay = $('sidebarOverlay');
    menuToggle?.addEventListener('click', () => { 
        sidebar.classList.add('open'); 
        overlay.classList.add('show'); 
    });
    overlay?.addEventListener('click', () => { 
        sidebar.classList.remove('open'); 
        overlay.classList.remove('show'); 
    });

    // modal editar perfil
    const editModal = $('editModal');
    const saveModalBtn = $('saveModalBtn');
    const cancelModalBtn = $('cancelModalBtn');
    const modalName = $('modalName');
    const modalDesc = $('modalDesc');

    $('editProfileBtn')?.addEventListener('click', () => {
        modalName.value = $('displayName').textContent.trim();
        modalDesc.value = $('inputDescripcion').value;
        editModal.classList.add('show');
        modalName.focus();
    });

    cancelModalBtn?.addEventListener('click', () => editModal.classList.remove('show'));

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') editModal.classList.remove('show');
    });

    saveModalBtn?.addEventListener('click', async () => {
        const newName = modalName.value.trim();
        const newDesc = modalDesc.value.trim();

        if (!newName) { 
            toast('El nombre no puede estar vacio.', true); 
            return; 
        }

        saveModalBtn.disabled = true;
        saveModalBtn.innerHTML = '<span class="spinner"></span> Guardando...';

        try {
            const res = await ajaxPost({ 
                action: 'update_profile', 
                name: newName, 
                description: newDesc 
            });
            if (res.ok) {
                $('displayName').textContent = newName;
                $('inputDescripcion').value = newDesc;

                const newInitial = newName.charAt(0).toUpperCase();
                $('profileInitial').textContent = newInitial;
                const sa = $('smallAvatar').querySelector('span');
                if (sa) sa.textContent = newInitial;

                toast('Perfil actualizado correctamente');
                editModal.classList.remove('show');
            } else {
                toast(res.msg || 'Error al guardar.', true);
            }
        } catch (err) {
            toast('Error de conexion.', true);
        }

        saveModalBtn.disabled = false;
        saveModalBtn.textContent = 'Guardar';
    });

    // guardar descripcion automaticamente
    const inputDesc = $('inputDescripcion');
    let descTimer;
    inputDesc?.addEventListener('input', () => {
        clearTimeout(descTimer);
        descTimer = setTimeout(async () => {
            await ajaxPost({ 
                action: 'update_profile', 
                name: $('displayName').textContent.trim(), 
                description: inputDesc.value 
            });
        }, 800);
    });
    inputDesc?.addEventListener('keydown', e => { 
        if (e.key === 'Enter') inputDesc.blur(); 
    });

    // subir foto de perfil
    const btnFoto = $('btnCambiarFoto');
    const inputFoto = $('inputFotoPerfil');
    const avatar = $('profileAvatar');
    const initSpan = $('profileInitial');
    const smallAv = $('smallAvatar');

    btnFoto?.addEventListener('click', () => inputFoto.click());

    inputFoto?.addEventListener('change', async () => {
        const file = inputFoto.files[0];
        if (!file) return;

        const localUrl = URL.createObjectURL(file);
        avatar.style.backgroundImage = `url('${localUrl}')`;
        avatar.style.backgroundSize = 'cover';
        avatar.style.backgroundPosition = 'center';
        avatar.style.color = 'transparent';
        initSpan.style.opacity = '0';

        const fd = new FormData();
        fd.append('action', 'upload_photo');
        fd.append('photo', file);
        try {
            const r = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });
            const res = await r.json();
            if (res.ok) {
                avatar.style.backgroundImage = `url('${res.url}')`;
                smallAv.innerHTML = `<img src="${res.url}" alt="avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
                toast('Foto actualizada');
            } else {
                toast(res.msg || 'Error al subir la foto.', true);
                avatar.style.backgroundImage = '';
                initSpan.style.opacity = '1';
            }
        } catch {
            toast('Error de conexion al subir foto.', true);
        }
    });

    // cargar reseñas del usuario
    async function loadReviews() {
        const list = $('reviewList');
        if (!list) return;
        
        list.innerHTML = '<div class="loading-placeholder"><span class="spinner"></span> Cargando reseñas...</div>';
        
        try {
            const res = await ajaxGet('get_reviews');
            
            if (!res.ok) {
                throw new Error(res.msg || 'Error al cargar');
            }
            
            if (!res.reviews || res.reviews.length === 0) {
                list.innerHTML = `
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 12H5v-2h14v2zm0-3H5V9h14v2zm0-3H5V6h14v2z"/>
                        </svg>
                        <p>Todavia no has escrito ninguna reseña</p>
                        <small>Explora contenido y comparte tu opinion!</small>
                    </div>
                `;
                return;
            }
            
            // actualizar contador de comentarios
            const statComentarios = $('statComentarios');
            if (statComentarios) {
                statComentarios.textContent = res.count || res.reviews.length;
            }
            
            list.innerHTML = res.reviews.map(rv => {
                const stars = renderStars(rv.rating || 0);
                const fecha = rv.formatted_date || (rv.created_at ? new Date(rv.created_at).toLocaleDateString('es-AR') : 'fecha desconocida');
                const titulo = rv.titulo || rv.media_title || 'sin titulo';
                const poster = rv.poster || rv.poster_url || '';
                const comentario = rv.comment || 'sin comentario';
                
                return `
                    <div class="review-card" data-review-id="${rv.id}">
                        <div class="review-poster">
                            ${poster ? 
                                `<img src="${escHtml(poster)}" alt="${escHtml(titulo)}" loading="lazy" onerror="this.src='/proyecto7mo/Frontend/img/placeholder.jpg'">` : 
                                `<div class="no-poster">🎬</div>`
                            }
                        </div>
                        <div class="review-content">
                            <div class="review-header">
                                <h3 class="review-title">${escHtml(titulo)}</h3>
                                <div class="review-rating">${stars}</div>
                            </div>
                            <p class="review-comment">${escHtml(comentario)}</p>
                            <div class="review-footer">
                                <span class="review-date">📅 ${escHtml(fecha)}</span>
                                <button class="delete-review-btn" data-id="${rv.id}">🗑️ Eliminar</button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            // botones para eliminar reseñas
            document.querySelectorAll('.delete-review-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const reviewId = btn.dataset.id;
                    if (confirm('¿Estas seguro de que quieres eliminar esta reseña? Esta accion no se puede deshacer.')) {
                        try {
                            const res = await ajaxPost({ action: 'delete_review', review_id: reviewId });
                            if (res.ok) {
                                toast('Reseña eliminada correctamente');
                                // recargar datos
                                reviewsLoaded = false;
                                activityLoaded = false;
                                loadReviews();
                                loadActivity();
                                // actualizar contador
                                const currentCount = parseInt($('statComentarios')?.textContent || '0');
                                if ($('statComentarios')) {
                                    $('statComentarios').textContent = currentCount - 1;
                                }
                            } else {
                                toast(res.msg || 'Error al eliminar la reseña', true);
                            }
                        } catch (err) {
                            toast('Error de conexion', true);
                        }
                    }
                });
            });
            
        } catch (error) {
            console.error('error cargando reseñas:', error);
            list.innerHTML = `
                <div class="empty-state error">
                    <p>❌ No se pudieron cargar las reseñas</p>
                    <button onclick="location.reload()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #667eea; border: none; border-radius: 6px; color: white; cursor: pointer;">Reintentar</button>
                </div>
            `;
        }
    }

    // cargar actividad del usuario
    async function loadActivity() {
        const list = $('activityList');
        if (!list) return;
        
        list.innerHTML = '<div class="loading-placeholder"><span class="spinner"></span> Cargando actividad...</div>';
        
        try {
            const res = await ajaxGet('get_activity');
            
            if (!res.ok || !res.activity || res.activity.length === 0) {
                list.innerHTML = `
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                        </svg>
                        <p>Aun no hay actividad registrada</p>
                        <small>Escribe tu primera reseña para comenzar!</small>
                    </div>
                `;
                return;
            }
            
            list.innerHTML = res.activity.map(act => {
                const fecha = act.formatted_date || (act.created_at ? new Date(act.created_at).toLocaleDateString('es-AR') : '');
                const timeAgo = act.time_ago || '';
                const ratingStars = act.rating ? renderStars(act.rating) : '';
                const iconPath = act.tipo === 'review'
                    ? 'M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 12H5v-2h14v2zm0-3H5V9h14v2zm0-3H5V6h14v2z'
                    : 'M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4V4c0-1.1-.9-2-2-2z';
                
                return `
                    <div class="activity-item">
                        <div class="act-icon">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="${iconPath}"/>
                            </svg>
                        </div>
                        <div class="act-text">
                            <b>${escHtml(act.detalle)}</b>
                            ${act.titulo ? `<br><em>${escHtml(act.titulo)}</em>` : ''}
                            ${ratingStars ? `<div style="margin-top: 4px;">${ratingStars}</div>` : ''}
                        </div>
                        <div class="act-date">
                            ${fecha}
                            ${timeAgo ? `<div class="act-time-ago">${timeAgo}</div>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
            
        } catch (error) {
            console.error('error cargando actividad:', error);
            list.innerHTML = `
                <div class="empty-state error">
                    <p>❌ No se pudo cargar la actividad</p>
                    <button onclick="location.reload()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #667eea; border: none; border-radius: 6px; color: white; cursor: pointer;">Reintentar</button>
                </div>
            `;
        }
    }
//tema oscuro/claro
    const themeToggle = $('themeToggle');
    const themeLabel = $('themeLabel');

    const applyTheme = () => {
        const theme = localStorage.getItem('profile_theme') || 'dark';
        document.documentElement.dataset.theme = theme;
        if (themeToggle) themeToggle.checked = (theme === 'dark');
        if (themeLabel) themeLabel.textContent = theme === 'dark' ? 'Oscuro' : 'Claro';
    };

    themeToggle?.addEventListener('change', () => {
        const theme = themeToggle.checked ? 'dark' : 'light';
        localStorage.setItem('profile_theme', theme);
        applyTheme();
    });

    // configuracion de privacidad
    const privacyBtns = document.querySelectorAll('.privacy-option');

    const applyPrivacy = () => {
        const saved = localStorage.getItem('profile_privacy') || 'public';
        privacyBtns.forEach(b => b.classList.toggle('active', b.dataset.value === saved));
    };

    privacyBtns.forEach(b => b.addEventListener('click', () => {
        localStorage.setItem('profile_privacy', b.dataset.value);
        applyPrivacy();
        toast('Preferencia de privacidad guardada');
    }));

    // iniciar todo
    showSection('perfil');
    applyTheme();
    applyPrivacy();
});
</script>
</body>
</html>