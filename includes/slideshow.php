<?php
if (!defined('NOMBRE_SITIO')) {
    include_once(__DIR__ . '/../config/config.php');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../config/conexion.php');

$user = null;
$user_id = $_SESSION['usuario_id'] ?? null;

if ($user_id) {
    $stmt = $conn->prepare("SELECT username, imagen FROM usuario WHERE id_usuario=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    $user = $resultado;
}
?>
<link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/slideshow.css" />
<link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/dark.css" />

<body>
<button id="open-slideshow" class="floating-icon">
    <i class="fa-solid fa-bars"></i>
</button>

<div class="slideshow" id="slideshow">
    <?php if($user) { ?>
        <div class="top-left">
            <a href="<?php echo URL_BASE ?>/cerrarsesion.php" class="icon-btn">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
        <div class="top-right">
            <button class="icon-btn" id="toggle-theme">
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>
        <div class="profile-section">
            <?php if (!empty($user['imagen'])) { ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($user['imagen']); ?>" alt="Perfil" class="profile-img">
            <?php } else { ?>
                <img src="<?php echo URL_BASE ?>/assets/img/user.png" alt="Perfil" class="profile-img">
            <?php } ?>
            <p class="username"><?php echo htmlspecialchars($user['username']); ?></p>
        </div>

        <div class="notifications">
            <a href="<?php echo URL_BASE ?>/editarperfil.php" class="notif-btn"><i class="fa-solid fa-user"></i> Editar Perfil</a>
        </div>
        <div class="notifications">
            <a href="<?php echo URL_BASE ?>/notificaciones.php" class="notif-btn"><i class="fa-solid fa-bell"></i> Notificaciones</a>
        </div>
        <div class="calendar">
            <input type="date" id="calendar-input">
        </div>
    <?php } else { ?>
        <div class="profile-section">
            <img src="<?php echo URL_BASE ?>/assets/img/user.png" alt="Perfil" class="profile-img">
            <p class="username">Invitado</p>
        </div>
        <div class="notifications">
            <a href="<?php echo URL_BASE ?>/iniciosesion.php" class="notif-btn">
                <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión
            </a>
        </div>
    <?php } ?>
</div>

<script>
// Abrir/cerrar slide
const btnOpen = document.getElementById("open-slideshow");
const slideshow = document.getElementById("slideshow");
btnOpen.addEventListener("click", () => slideshow.classList.add("active"));
document.addEventListener("click", (e) => {
    if (!slideshow.contains(e.target) && e.target !== btnOpen) slideshow.classList.remove("active");
});

// Modo claro/oscuro con localStorage
document.addEventListener("DOMContentLoaded", () => {
    const toggleBtn = document.getElementById("toggle-theme");
    const body = document.body;
    const icon = toggleBtn ? toggleBtn.querySelector("i") : null;

    // Aplicar preferencia guardada
    if(localStorage.getItem("modo") === "oscuro") {
        body.classList.add("dark-mode");
        if(icon){ icon.classList.remove("fa-moon"); icon.classList.add("fa-sun"); }
    }

    if(toggleBtn){
        toggleBtn.addEventListener("click", () => {
            const isDark = body.classList.toggle("dark-mode");
            if(icon){ icon.classList.toggle("fa-moon", !isDark); icon.classList.toggle("fa-sun", isDark); }
            localStorage.setItem("modo", isDark ? "oscuro" : "claro");
        });
    }
});
</script>
