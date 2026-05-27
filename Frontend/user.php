<?php
session_start();

// ─── MANEJO DE PETICIONES AJAX ────────────────────────────────────────────────
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    require_once __DIR__ . '/../Backend/models/Database.php';
    header('Content-Type: application/json');

    $action  = $_POST['action'] ?? $_GET['action'] ?? '';
    $session = $_SESSION['user'] ?? null;

    if (!$session) { echo json_encode(['ok' => false, 'msg' => 'No autenticado']); exit; }

    $user_id = $session['id'];
    $db      = Database::getInstance()->getConnection();
    if ($action === 'update_profile') {
        $new_name = trim($_POST['name'] ?? '');
        $new_desc = trim($_POST['description'] ?? '');

        try {
            // Asegurarse de que la columna description existe en users
            if ($new_name !== '') {
                $db->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$new_name, $user_id]);
                $_SESSION['user']['name'] = $new_name;
            }
            if ($new_desc !== '') {
                // Intentar actualizar si la columna no existe, la creamos
                try {
                    $db->prepare("UPDATE users SET description = ? WHERE id = ?")->execute([$new_desc, $user_id]);
                } catch (\PDOException $ex) {
                    // Si falta la columna, la añadimos y reintentamos
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

    if ($action === 'upload_photo') {
        $file = $_FILES['photo'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'msg' => 'Error al subir archivo']); exit;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            echo json_encode(['ok' => false, 'msg' => 'Tipo de archivo no permitido']); exit;
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
            echo json_encode(['ok' => false, 'msg' => 'No se pudo mover el archivo']);
        }
        exit;
    }
    if ($action === 'get_reviews') {
        try {
            // Intentamos un JOIN generico
            $stmt = $db->prepare("
                SELECT r.id,
                       r.rating,
                       r.comment,
                       r.created_at,
                       COALESCE(m.title, s.title, r.media_title, 'Sin título') AS titulo,
                       COALESCE(m.poster_url, s.poster_url, r.poster_url, '') AS poster
                FROM reviews r
                LEFT JOIN movies  m ON m.id = r.movie_id
                LEFT JOIN series  s ON s.id = r.series_id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $reviews = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'reviews' => $reviews]);
        } catch (\Exception $e) {
            // Consulta de fallback sin JOINs
            try {
                $stmt = $db->prepare("SELECT id, rating, comment, created_at FROM reviews WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->execute([$user_id]);
                $reviews = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                echo json_encode(['ok' => true, 'reviews' => $reviews]);
            } catch (\Exception $e2) {
                echo json_encode(['ok' => false, 'msg' => $e2->getMessage(), 'reviews' => []]);
            }
        }
        exit;
    }

    if ($action === 'get_activity') {
        $activity = [];
        try {
            // Reseñas
            $stmt = $db->prepare("
                SELECT 'review' AS tipo, r.created_at,
                       CONCAT('Escribiste una reseña') AS detalle,
                       COALESCE(m.title, s.title, r.media_title, 'un contenido') AS titulo
                FROM reviews r
                LEFT JOIN movies m ON m.id = r.movie_id
                LEFT JOIN series s ON s.id = r.series_id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $activity = array_merge($activity, $stmt->fetchAll(\PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            try {
                $stmt = $db->prepare("SELECT 'review' AS tipo, created_at, 'Escribiste una reseña' AS detalle, '' AS titulo FROM reviews WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
                $stmt->execute([$user_id]);
                $activity = array_merge($activity, $stmt->fetchAll(\PDO::FETCH_ASSOC));
            } catch (\Exception $e2) {}
        }
        try {
            // Respuestas
            $stmt = $db->prepare("SELECT 'response' AS tipo, created_at, 'Respondiste a una reseña' AS detalle, '' AS titulo FROM review_responses WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$user_id]);
            $activity = array_merge($activity, $stmt->fetchAll(\PDO::FETCH_ASSOC));
        } catch (\Exception $e) {}

        // Ordenar por fecha desc
        usort($activity, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        echo json_encode(['ok' => true, 'activity' => array_slice($activity, 0, 15)]);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Acción desconocida']);
    exit;
}

// RENDERIZADO NORMAL DE LA pagina 
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
$comments_count = 0;
$user_avatar    = '';
$user_desc      = '';

if ($user_id) {
    try {
        $db = Database::getInstance()->getConnection();

        // reputacion
        $stmt = $db->prepare("SELECT reputation FROM reviewers WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $reviewer = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($reviewer) $reputation = (int) $reviewer['reputation'];

        // Conteo comentarios
        $stmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $reviews_count = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM review_responses WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $responses_count = (int) $stmt->fetchColumn();

        $comments_count = $reviews_count + $responses_count;

        // Avatar y descripcion (columnas opcionales)
        try {
            $stmt = $db->prepare("SELECT avatar, description FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$user_id]);
            $udata = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($udata) {
                $user_avatar = $udata['avatar'] ?? '';
                $user_desc   = $udata['description'] ?? '';
            }
        } catch (\Exception $e) { /* columnas aún no creadas */ }

    } catch (\Exception $e) { /* fallback silencioso */ }
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
</head>
<body class="perfil-body">
<div class="perfil-container">

     <!-- SIDEBAR -->
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

    <!--  CONTENIDO PRINCIPAL  -->
    <main class="contenido-perfil">

        <header class="header-interno">
            <div class="header-seccion-titulo">
                <button class="btn-toggle-movil" id="menuMovilToggle" aria-label="Abrir menú">
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

        <!--  seccion: MI PERFIL  -->
        <section id="perfil" class="perfil-seccion-central content-section">

            <div class="avatar-grande-container">
                <div class="avatar-grande" id="profileAvatar"
                     <?php if ($user_avatar): ?>
                         style="background-image:url('<?= htmlspecialchars($user_avatar) ?>');background-size:cover;background-position:center;color:transparent;"
                     <?php endif; ?>>
                    <span id="profileInitial" <?= $user_avatar ? 'style="opacity:0"' : '' ?>><?= $user_initial ?></span>
                </div>
                <button class="btn-cambiar-foto" id="btnCambiarFoto" type="button" title="Cambiar foto de perfil">
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

        <!--  seccion: MIS RESEÑAS  -->
        <section id="resenas" class="perfil-seccion-central content-section section-hidden">
            <div class="section-card">
                <div class="section-header">
                    <h2>Mis reseñas</h2>
                    <p class="section-description">Aquí verás las películas y series a las que ya les hiciste una reseña.</p>
                </div>
                <div class="review-list" id="reviewList">
                    <div class="loading-placeholder">Cargando reseñas…</div>
                </div>
            </div>
        </section>

        <!--  seccion: MI ACTIVIDAD  -->
        <section id="actividad" class="perfil-seccion-central content-section section-hidden">
            <div class="section-card">
                <div class="section-header">
                    <h2>Mi actividad</h2>
                    <p class="section-description">Revisa tu actividad reciente en NexoHub.</p>
                </div>
                <div id="activityList">
                    <div class="loading-placeholder">Cargando actividad…</div>
                </div>
            </div>
        </section>

        <!--  seccion: configuracion  -->
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

<!--  TOAST  -->
<div id="toast"></div>

<!--  MODAL EDITAR PERFIL  -->
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
document.addEventListener('DOMContentLoaded', () => {

    //  Utilidades 
    const $ = id => document.getElementById(id);
    const toast = (msg, isError = false) => {
        const t = $('toast');
        t.textContent = msg;
        t.className = 'show' + (isError ? ' error' : '');
        setTimeout(() => t.className = '', 3000);
    };

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

    const ajaxGet = async (action) => {
        const r = await fetch(`${window.location.href}?action=${action}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        return r.json();
    };

    //  Navegación secciones 
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.content-section');
    let reviewsLoaded   = false;
    let activityLoaded  = false;

    const showSection = (id) => {
        sections.forEach(sec => sec.classList.toggle('section-hidden', sec.id !== id));
        if (id === 'resenas'  && !reviewsLoaded)  { loadReviews();  reviewsLoaded  = true; }
        if (id === 'actividad' && !activityLoaded) { loadActivity(); activityLoaded = true; }
    };

    navItems.forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            navItems.forEach(n => n.classList.remove('active'));
            item.classList.add('active');
            showSection(item.dataset.section);
            const sb = document.querySelector('.sidebar-estatico');
            const ov = $('sidebarOverlay');
            if (sb?.classList.contains('open')) { sb.classList.remove('open'); ov.classList.remove('show'); }
        });
    });

    //  menu movil 
    const menuToggle = $('menuMovilToggle');
    const sidebar    = document.querySelector('.sidebar-estatico');
    const overlay    = $('sidebarOverlay');
    menuToggle?.addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('show'); });
    overlay?.addEventListener('click',   () => { sidebar.classList.remove('open'); overlay.classList.remove('show'); });

    //  MODAL EDITAR PERFIL 
    const editModal     = $('editModal');
    const saveModalBtn  = $('saveModalBtn');
    const cancelModalBtn = $('cancelModalBtn');
    const modalName     = $('modalName');
    const modalDesc     = $('modalDesc');

    $('editProfileBtn')?.addEventListener('click', () => {
        modalName.value = $('displayName').textContent.trim();
        modalDesc.value = $('inputDescripcion').value;
        editModal.classList.add('show');
        modalName.focus();
    });

    cancelModalBtn?.addEventListener('click', () => editModal.classList.remove('show'));

    // Cerrar modal con Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') editModal.classList.remove('show');
    });

    saveModalBtn?.addEventListener('click', async () => {
        const newName = modalName.value.trim();
        const newDesc = modalDesc.value.trim();

        if (!newName) { toast('El nombre no puede estar vacío.', true); return; }

        saveModalBtn.disabled = true;
        saveModalBtn.innerHTML = '<span class="spinner"></span> Guardando…';

        try {
            const res = await ajaxPost({ action: 'update_profile', name: newName, description: newDesc });
            if (res.ok) {
                // Actualizar UI
                $('displayName').textContent = newName;
                $('inputDescripcion').value  = newDesc;

                // Actualizar inicial si cambio el nombre
                const newInitial = newName.charAt(0).toUpperCase();
                $('profileInitial').textContent = newInitial;
                const sa = $('smallAvatar').querySelector('span');
                if (sa) sa.textContent = newInitial;

                toast('Perfil actualizado correctamente ✓');
                editModal.classList.remove('show');
            } else {
                toast(res.msg || 'Error al guardar.', true);
            }
        } catch (err) {
            toast('Error de conexión.', true);
        }

        saveModalBtn.disabled = false;
        saveModalBtn.textContent = 'Guardar';
    });

    //  descripcion — guardar al perder foco 
    const inputDesc = $('inputDescripcion');
    let descTimer;
    inputDesc?.addEventListener('input', () => {
        clearTimeout(descTimer);
        descTimer = setTimeout(async () => {
            await ajaxPost({ action: 'update_profile', name: $('displayName').textContent.trim(), description: inputDesc.value });
        }, 800);
    });
    inputDesc?.addEventListener('keydown', e => { if (e.key === 'Enter') inputDesc.blur(); });

    //  Foto de perfil 
    const btnFoto    = $('btnCambiarFoto');
    const inputFoto  = $('inputFotoPerfil');
    const avatar     = $('profileAvatar');
    const initSpan   = $('profileInitial');
    const smallAv    = $('smallAvatar');

    btnFoto?.addEventListener('click', () => inputFoto.click());

    inputFoto?.addEventListener('change', async () => {
        const file = inputFoto.files[0];
        if (!file) return;

        // vista previa inmediata
        const localUrl = URL.createObjectURL(file);
        avatar.style.backgroundImage    = `url('${localUrl}')`;
        avatar.style.backgroundSize     = 'cover';
        avatar.style.backgroundPosition = 'center';
        avatar.style.color              = 'transparent';
        initSpan.style.opacity          = '0';

        // Subir al servidor
        const fd = new FormData();
        fd.append('action', 'upload_photo');
        fd.append('photo', file);
        try {
            const r   = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });
            const res = await r.json();
            if (res.ok) {
                // Actualizar avatar pequeño con URL del servidor
                avatar.style.backgroundImage = `url('${res.url}')`;
                smallAv.innerHTML = `<img src="${res.url}" alt="avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
                toast('Foto actualizada ✓');
            } else {
                toast(res.msg || 'Error al subir la foto.', true);
                // Revertir preview si falla
                avatar.style.backgroundImage = '';
                initSpan.style.opacity = '1';
            }
        } catch {
            toast('Error de conexión al subir foto.', true);
        }
    });

    //   Cargar reseñas 
    async function loadReviews() {
        const list = $('reviewList');
        try {
            const res = await ajaxGet('get_reviews');
            if (!res.ok || !res.reviews.length) {
                list.innerHTML = '<div class="empty-state"><p>Todavía no se han hecho reseñas.</p></div>';
                return;
            }
            list.innerHTML = res.reviews.map(rv => {
                const stars  = '★'.repeat(Math.min(5, Math.round(rv.rating || 0))) +
                               '☆'.repeat(Math.max(0, 5 - Math.round(rv.rating || 0)));
                const fecha  = rv.created_at ? new Date(rv.created_at).toLocaleDateString('es-AR', { year:'numeric', month:'short', day:'numeric' }) : '';
                const titulo = rv.titulo || rv.media_title || 'Sin título';
                const poster = rv.poster  || rv.poster_url || '';

                return `<div class="review-card">
                    ${poster
                        ? `<img class="poster-thumb" src="${escHtml(poster)}" alt="${escHtml(titulo)}" loading="lazy">`
                        : `<div class="poster-thumb no-img">🎬</div>`}
                    <div class="rv-info">
                        <div class="rv-title">${escHtml(titulo)}</div>
                        <div class="rv-stars">${stars}</div>
                        <div class="rv-comment">${escHtml(rv.comment || '')}</div>
                        <div class="rv-date">${fecha}</div>
                    </div>
                </div>`;
            }).join('');
        } catch {
            list.innerHTML = '<div class="empty-state"><p>No se pudieron cargar las reseñas.</p></div>';
        }
    }

    //  Cargar actividad 
    async function loadActivity() {
        const list = $('activityList');
        try {
            const res = await ajaxGet('get_activity');
            if (!res.ok || !res.activity.length) {
                list.innerHTML = '<div class="empty-state"><p>Aún no hay actividad registrada.</p></div>';
                return;
            }
            list.innerHTML = res.activity.map(act => {
                const fecha  = act.created_at ? new Date(act.created_at).toLocaleDateString('es-AR', { year:'numeric', month:'short', day:'numeric' }) : '';
                const iconPath = act.tipo === 'review'
                    ? 'M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 12H5v-2h14v2zm0-3H5V9h14v2zm0-3H5V6h14v2z'
                    : 'M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18z';
                return `<div class="activity-item">
                    <div class="act-icon"><svg viewBox="0 0 24 24"><path d="${iconPath}"/></svg></div>
                    <div class="act-text">
                        <b>${escHtml(act.detalle)}</b>${act.titulo ? ` sobre <em>${escHtml(act.titulo)}</em>` : ''}
                    </div>
                    <div class="act-date">${fecha}</div>
                </div>`;
            }).join('');
        } catch {
            list.innerHTML = '<div class="empty-state"><p>No se pudo cargar la actividad.</p></div>';
        }
    }
    const themeToggle = $('themeToggle');
    const themeLabel  = $('themeLabel');

    const applyTheme = () => {
        const theme = localStorage.getItem('profile_theme') || 'dark';
        document.documentElement.dataset.theme = theme;
        if (themeToggle) themeToggle.checked = (theme === 'dark');
        if (themeLabel)  themeLabel.textContent = theme === 'dark' ? 'Oscuro' : 'Claro';
    };

    themeToggle?.addEventListener('change', () => {
        const theme = themeToggle.checked ? 'dark' : 'light';
        localStorage.setItem('profile_theme', theme);
        applyTheme();
    });
    const privacyBtns = document.querySelectorAll('.privacy-option');

    const applyPrivacy = () => {
        const saved = localStorage.getItem('profile_privacy') || 'public';
        privacyBtns.forEach(b => b.classList.toggle('active', b.dataset.value === saved));
    };

    privacyBtns.forEach(b => b.addEventListener('click', () => {
        localStorage.setItem('profile_privacy', b.dataset.value);
        applyPrivacy();
        toast('Preferencia de privacidad guardada ✓');
    }));

    function escHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    showSection('perfil');
    applyTheme();
    applyPrivacy();
});
</script>
</body>
</html>
