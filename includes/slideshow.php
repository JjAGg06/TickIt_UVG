<?php
if (!defined('NOMBRE_SITIO')) {
    include_once(__DIR__ . '/../config/config.php');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../config/conexion.php');

$user = null;
if (isset($_SESSION['usuario_id'])) {
    $id = $_SESSION['usuario_id'];
    $sql = "SELECT username, imagen, tema_preferido FROM usuario WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $user = $resultado->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slideshow - TickIt UVG</title>
    <link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/slideshow.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body.dark-mode {
            background-color: #2b2b2b;
            color: #f1f1f1;
        }
    </style>
</head>

<body class="<?php echo ($user && $user['tema_preferido'] === 'oscuro') ? 'dark-mode' : ''; ?>">

    <!-- Boton flotante -->
    <button id="open-slideshow" class="floating-icon">
        <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Slideshow oculto -->
    <div class="slideshow" id="slideshow">

        <?php if ($user) { ?>
            <!-- Icono para cerrar sesion -->
            <div class="top-left">
                <a href="<?php echo URL_BASE ?>/cerrarsesion.php" class="icon-btn">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>

            <!-- Icono cambiar tema -->
            <div class="top-right">
                <button class="icon-btn" id="toggle-theme">
                    <i class="fa-solid <?php echo ($user['tema_preferido'] === 'oscuro') ? 'fa-sun' : 'fa-moon'; ?>"></i>
                </button>
            </div>

            <!-- Imagen perfil del usuario -->
            <div class="profile-section">
                <?php if (!empty($user['imagen'])) { ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($user['imagen']); ?>"
                        alt="Perfil" class="profile-img">
                <?php } else { ?>
                    <img src="<?php echo URL_BASE ?>/assets/img/user.png" alt="Perfil" class="profile-img">
                <?php } ?>
                <p class="username"><?php echo htmlspecialchars($user['username']); ?></p>
            </div>

            <!-- Boton editar perfil -->
            <div class="notifications">
                <button class="notif-btn" onclick="window.location.href='<?php echo URL_BASE; ?>/editarperfil.php'">
                    <i class="fa-solid fa-user"></i> Editar Perfil
                </button>
            </div>

            <!-- Boton para notificaciones -->
            <div class="notifications">
                <button class="notif-btn" onclick="window.location.href='<?php echo URL_BASE; ?>/notificaciones.php'">
                    <i class="fa-solid fa-bell"></i> Notificaciones
                </button>
            </div>

        <?php } else { ?>
            <!-- Si NO hay sesión -->
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
        const btnOpen = document.getElementById("open-slideshow");
        const slideshow = document.getElementById("slideshow");

        btnOpen.addEventListener("click", function() {
            slideshow.classList.add("active");
            btnOpen.style.display = "none";
        });

        document.addEventListener("click", function(e) {
            if (slideshow.classList.contains("active")) {
                if (!slideshow.contains(e.target) && !btnOpen.contains(e.target)) {
                    slideshow.classList.remove("active");
                    btnOpen.style.display = "flex";
                }
            }
        });

        // Cambio de tema
        const toggleBtn = document.getElementById("toggle-theme");
        if (toggleBtn) {
            toggleBtn.addEventListener("click", function() {
                document.body.classList.toggle("dark-mode");
                const icon = this.querySelector("i");
                const nuevoTema = document.body.classList.contains("dark-mode") ? "oscuro" : "claro";

                if (nuevoTema === "oscuro") {
                    icon.classList.remove("fa-moon");
                    icon.classList.add("fa-sun");
                } else {
                    icon.classList.remove("fa-sun");
                    icon.classList.add("fa-moon");
                }

                // Guardar preferencia en BD
                fetch("<?php echo URL_BASE; ?>/pages/tema_preferido.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "tema=" + encodeURIComponent(nuevoTema)
                });
            });
        }
    </script>
</body>
</html>