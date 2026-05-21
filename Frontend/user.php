<?php
session_start();

// Si no está autenticado, redirigir al login
if (empty($_SESSION['user'])) {
    header('Location: /proyecto7mo/Frontend/login.php');
    exit;
}

require_once __DIR__ . '/../Backend/models/Database.php';

$user_name = $_SESSION['user']['name'];
$user_email = $_SESSION['user']['email'];
$user_id = $_SESSION['user']['id'] ?? null;
$user_initial = mb_strtoupper(mb_substr($user_name, 0, 1, 'UTF-8'));

$reputation = 0;
$comments_count = 0;

if ($user_id) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Cargar reputación real
        $stmt = $db->prepare("SELECT reputation FROM reviewers WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $reviewer = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($reviewer) {
            $reputation = (int) $reviewer['reputation'];
        }
        
        // Contar reseñas hechas
        $stmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $reviews_count = (int) $stmt->fetchColumn();
        
        // Contar respuestas a reseñas
        $stmt = $db->prepare("SELECT COUNT(*) FROM review_responses WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $responses_count = (int) $stmt->fetchColumn();
        
        $comments_count = $reviews_count + $responses_count;
    } catch (Exception $e) {
        // Mantener fallbacks silenciosos en caso de error de BD
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - NexoHub</title>
    <!-- Importamos la fuente Outfit para un aspecto moderno y premium similar a Google Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/styles.css">
    <link rel="stylesheet" href="style/user.css">
</head>
<body class="perfil-body">
    <!-- Contenedor Principal Split-Screen -->
    <div class="perfil-container">
        
        <!-- SIDEBAR ESTÁTICO (Izquierda) -->
        <aside class="sidebar-estatico">
            <div class="sidebar-brand">
                <a href="/proyecto7mo/index.php" class="brand-link">
                    <div class="brand-logo">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 4v16l13-8L7 4z"></path>
                        </svg>
                    </div>
                    <span class="brand-text">Nexo<span class="text-highlight">Hub</span></span>
                </a>
            </div>

            <!-- Menú de navegación -->
            <nav class="sidebar-nav">
                <!-- Mi Perfil -->
                <a href="#" class="nav-item active" data-section="perfil">
                    <span class="icon-circle icon-home">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </span>
                    <span class="nav-label">Mi perfil</span>
                </a>

                <!-- Mis Reseñas -->
                <a href="#" class="nav-item" data-section="resenas">
                    <span class="icon-circle icon-info">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 12H5v-2h14v2zm0-3H5V9h14v2zm0-3H5V6h14v2z"/>
                        </svg>
                    </span>
                    <span class="nav-label">Mis reseñas</span>
                </a>

                <!-- Mi Actividad -->
                <a href="#" class="nav-item" data-section="actividad">
                    <span class="icon-circle icon-security">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M13 2.05v2.02c3.95.49 7 3.85 7 7.93 0 3.21-1.81 6-4.72 7.28L13 17v5h5l-1.22-1.22C19.91 19.07 22 15.76 22 12c0-5.18-3.95-9.45-9-9.95zM11 2.05C5.95 2.55 2 6.82 2 12c0 3.76 2.09 7.07 5.22 8.78L6 22h5V2.05zM11 13H9V7h2v6zm4 0h-2V7h2v6z"/>
                        </svg>
                    </span>
                    <span class="nav-label">Mi actividad</span>
                </a>

                <!-- Amigos -->
                <a href="#" class="nav-item" data-section="amigos">
                    <span class="icon-circle icon-sharing">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                        </svg>
                    </span>
                    <span class="nav-label">Amigos</span>
                </a>

                <!-- Configuración -->
                <a href="#" class="nav-item" data-section="config">
                    <span class="icon-circle icon-privacy">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.07.62-.07.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                        </svg>
                    </span>
                    <span class="nav-label">Configuración</span>
                </a>
            </nav>

        </aside>

        <!-- CONTENIDO PRINCIPAL (Derecha) -->
        <main class="contenido-perfil">
            
            <!-- Barra superior del contenido -->
            <header class="header-interno">
                <div class="header-seccion-titulo">
                    <button class="btn-toggle-movil" id="menuMovilToggle" aria-label="Abrir menú">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <span class="header-brand-movil">NexoHub Perfil</span>
                </div>
                
                <div class="header-acciones">
                    <!-- Avatar inicial pequeño -->
                    <div class="avatar-pequeno">
                        <span><?= $user_initial ?></span>
                    </div>
                </div>
            </header>

            <!-- Contenedor central de información de perfil -->
            <section class="perfil-seccion-central">
                
                <!-- Avatar de usuario grande con botón de foto flotante -->
                <div class="avatar-grande-container">
                    <div class="avatar-grande">
                        <span><?= $user_initial ?></span>
                    </div>
                    <button class="btn-cambiar-foto" title="Cambiar foto de perfil">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            <path d="M17 19c1.1 0 2-.9 2-2v-4h-2v4h-4v2h4z" style="display:none;"/>
                        </svg>
                        <div class="camara-overlay">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12m-3.2 0a3.2 3.2 0 1 1 6.4 0a3.2 3.2 0 1 1 -6.4 0M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5s5 2.24 5 5s-2.24 5-5 5z"/>
                            </svg>
                        </div>
                    </button>
                </div>

                <!-- Nombre y Correo del Usuario -->
                <h1 class="perfil-nombre"><?= htmlspecialchars($user_name) ?></h1>
                <p class="perfil-correo"><?= htmlspecialchars($user_email) ?></p>

                <!-- Estadísticas del usuario -->
                <div class="stats-perfil-container">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-comentarios">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 12H5v-2h14v2zm0-3H5V9h14v2zm0-3H5V6h14v2z"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-valor" id="statComentarios"><?= $comments_count ?></span>
                            <span class="stat-label">Comentarios</span>
                        </div>
                    </div>

                    <div class="stat-separador"></div>

                    <div class="stat-card">
                        <div class="stat-icon stat-icon-reputacion">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-valor" id="statReputacion"><?= $reputation ?></span>
                            <span class="stat-label">Reputación</span>
                        </div>
                    </div>
                </div>

                <!-- Descripción de cuenta interactiva -->
                <div class="descripcion-perfil-container">
                    <div class="descripcion-perfil-wrapper">
                        <span class="pencil-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </span>
                        <input type="text" class="descripcion-input" id="inputDescripcion" placeholder="Agregar descripción" maxlength="100">
                    </div>
                </div>

            </section>

        </main>
        
    </div>

    <!-- Overlay móvil para cerrar el menú lateral -->
    <div class="sidebar-overlay-movil" id="sidebarOverlay"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuMovilToggle = document.getElementById('menuMovilToggle');
            const sidebar = document.querySelector('.sidebar-estatico');
            const overlay = document.getElementById('sidebarOverlay');
            const navItems = document.querySelectorAll('.nav-item');

            // Abrir y cerrar menú móvil
            if (menuMovilToggle && sidebar && overlay) {
                menuMovilToggle.addEventListener('click', () => {
                    sidebar.classList.add('open');
                    overlay.classList.add('show');
                });

                overlay.addEventListener('click', () => {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('show');
                });
            }

            // Cambiar item activo para simular navegación
            navItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    navItems.forEach(nav => nav.classList.remove('active'));
                    item.classList.add('active');
                    
                    // Si estamos en móvil, cerramos el menú al hacer click en una opción
                    if (sidebar && sidebar.classList.contains('open')) {
                        sidebar.classList.remove('open');
                        overlay.classList.remove('show');
                    }
                });
            });

            // Guardar y cargar descripción en localStorage para simular persistencia
            const inputDescripcion = document.getElementById('inputDescripcion');
            if (inputDescripcion) {
                const descripcionGuardada = localStorage.getItem('user_description');
                if (descripcionGuardada) {
                    inputDescripcion.value = descripcionGuardada;
                }

                inputDescripcion.addEventListener('blur', () => {
                    localStorage.setItem('user_description', inputDescripcion.value);
                });

                inputDescripcion.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        inputDescripcion.blur();
                    }
                });
            }
        });
    </script>
</body>
</html>