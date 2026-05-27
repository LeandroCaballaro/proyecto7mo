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
echo "CAMBIO_DE_PRUEBA";
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
            <section id="perfil" class="perfil-seccion-central content-section">
                
                <!-- Avatar de usuario grande con botón de foto flotante -->
                <div class="avatar-grande-container">
                    <div class="avatar-grande" id="profileAvatar">
                        <span id="profileInitial"><?= $user_initial ?></span>
                    </div>
                    <button class="btn-cambiar-foto" id="btnCambiarFoto" type="button" title="Cambiar foto de perfil">
                        <div class="camara-overlay">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12m-3.2 0a3.2 3.2 0 1 1 6.4 0a3.2 3.2 0 1 1 -6.4 0M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5s5 2.24 5 5s-2.24 5-5 5z"/>
                            </svg>
                        </div>
                    </button>
                    <input type="file" id="inputFotoPerfil" accept="image/*" style="display:none">
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

            <section id="resenas" class="perfil-seccion-central content-section section-hidden">
                <div class="section-card">
                    <div class="section-header">
                        <h2>Mis reseñas</h2>
                        <p class="section-description">Aquí verás las películas y series a las que ya les hiciste una reseña.</p>
                    </div>
                    <div class="review-list" id="reviewList">
                        <div class="empty-state">
                            <p>Todavía no se han hecho reseñas.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="actividad" class="perfil-seccion-central content-section section-hidden">
                <div class="section-card">
                    <div class="section-header">
                        <h2>Mi actividad</h2>
                        <p class="section-description">Revisa tu actividad reciente en NexoHub.</p>
                    </div>
                    <div class="empty-state">
                        <p>Aquí aparecerán próximas actualizaciones de tu actividad.</p>
                    </div>
                </div>
            </section>

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

    <!-- Overlay móvil para cerrar el menú lateral -->
    <div class="sidebar-overlay-movil" id="sidebarOverlay"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuMovilToggle = document.getElementById('menuMovilToggle');
            const sidebar = document.querySelector('.sidebar-estatico');
            const overlay = document.getElementById('sidebarOverlay');
            const navItems = document.querySelectorAll('.nav-item');
            const sections = document.querySelectorAll('.content-section');
            const inputDescripcion = document.getElementById('inputDescripcion');
            const btnCambiarFoto = document.getElementById('btnCambiarFoto');
            const inputFotoPerfil = document.getElementById('inputFotoPerfil');
            const profileAvatar = document.getElementById('profileAvatar');
            const profileInitial = document.getElementById('profileInitial');
            const smallAvatar = document.querySelector('.avatar-pequeno');
            const themeToggle = document.getElementById('themeToggle');
            const themeLabel = document.getElementById('themeLabel');
            const privacyOptions = document.querySelectorAll('.privacy-option');
            const reviews = [];

            const showSection = (sectionId) => {
                sections.forEach(section => {
                    section.classList.toggle('section-hidden', section.id !== sectionId);
                });
            };

            const setActiveNav = (activeItem) => {
                navItems.forEach(nav => nav.classList.toggle('active', nav === activeItem));
            };

            const applySavedPhoto = () => {
                const photoData = localStorage.getItem('profile_photo');
                if (photoData) {
                    profileAvatar.style.backgroundImage = `url('${photoData}')`;
                    profileAvatar.style.backgroundSize = 'cover';
                    profileAvatar.style.backgroundPosition = 'center';
                    profileAvatar.style.backgroundRepeat = 'no-repeat';
                    profileAvatar.style.color = 'transparent';
                    profileInitial.style.opacity = '0';

                    if (smallAvatar) {
                        smallAvatar.style.backgroundImage = `url('${photoData}')`;
                        smallAvatar.style.backgroundSize = 'cover';
                        smallAvatar.style.backgroundPosition = 'center';
                        smallAvatar.style.backgroundRepeat = 'no-repeat';
                        smallAvatar.style.color = 'transparent';
                    }
                }
            };

            const applySavedTheme = () => {
                const savedTheme = localStorage.getItem('profile_theme') || 'dark';
                const isDark = savedTheme === 'dark';
                if (themeToggle) themeToggle.checked = isDark;
                if (themeLabel) themeLabel.textContent = isDark ? 'Oscuro' : 'Claro';
                document.documentElement.dataset.theme = savedTheme;
            };

            const updatePrivacySelection = (privacyMode) => {
                privacyOptions.forEach(option => {
                    option.classList.toggle('active', option.dataset.value === privacyMode);
                });
                localStorage.setItem('profile_privacy', privacyMode);
            };

            const applySavedPrivacy = () => {
                const savedPrivacy = localStorage.getItem('profile_privacy') || 'public';
                if (savedPrivacy) {
                    updatePrivacySelection(savedPrivacy);
                }
            };

            const initSectionNavigation = () => {
                navItems.forEach(item => {
                    item.addEventListener('click', (e) => {
                        e.preventDefault();
                        setActiveNav(item);
                        showSection(item.dataset.section);

                        if (sidebar && sidebar.classList.contains('open')) {
                            sidebar.classList.remove('open');
                            overlay.classList.remove('show');
                        }
                    });
                });
            };

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

            initSectionNavigation();
            showSection('perfil');

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

            if (btnCambiarFoto && inputFotoPerfil) {
                btnCambiarFoto.addEventListener('click', () => inputFotoPerfil.click());

                inputFotoPerfil.addEventListener('change', () => {
                    const file = inputFotoPerfil.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const imageUrl = event.target.result;
                        localStorage.setItem('profile_photo', imageUrl);
                        applySavedPhoto();
                    };
                    reader.readAsDataURL(file);
                });
            }

            if (themeToggle) {
                themeToggle.addEventListener('change', () => {
                    const selected = themeToggle.checked ? 'dark' : 'light';
                    if (themeLabel) themeLabel.textContent = themeToggle.checked ? 'Oscuro' : 'Claro';
                    document.documentElement.dataset.theme = selected;
                    localStorage.setItem('profile_theme', selected);
                });
            }
    
            if (privacyOptions.length) {
                privacyOptions.forEach(option => {
                    option.addEventListener('click', () => {
                        updatePrivacySelection(option.dataset.value);
                    });
                });
            }

            applySavedPhoto();
            applySavedTheme();
            applySavedPrivacy();
        });
    </script>
</body>
</html>