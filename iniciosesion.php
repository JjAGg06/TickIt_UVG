<?php
if (!defined('NOMBRE_SITIO')) {
    include_once(__DIR__ . '/config/config.php');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    include_once(__DIR__ . '/config/conexion.php'); // conexión mysqli

    $usuario = $_POST['usuario'] ?? '';
    $contra = $_POST['contrasena'] ?? '';

    // Consulta a la BD
    $sql = "SELECT id_usuario, username, contra FROM usuario WHERE username = ? AND contra = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $usuario, $contra);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $row = $resultado->fetch_assoc();

        // Guardar la sesion (solo lo necesario)
        $_SESSION['usuario_id'] = $row['id_usuario'];
        $_SESSION['usuario_nombre'] = $row['username'];

        header("Location: index.php"); // redirigir al index
        exit;
    } else {
        $mensaje = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Iniciar Sesión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/sesion.css"/>
</head>
<body>
    <div class="login-contenedor">
        <form method="POST" action="">
            <h2>Iniciar Sesión</h2>

            <div class="contenedor">
                <img src="../Assets/img/TIUVG.png" alt="Logo UVG">
            </div>

            <?php if (!empty($mensaje)): ?>
                <p class="mensaje-error"><?php echo $mensaje; ?></p>
            <?php endif; ?>

            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required>

            <label for="contrasena">Contraseña</label>
            <input type="password" id="contrasena" name="contrasena" required>

            <button type="submit">Entrar</button>

            <div class="contenedor">
                <a href="registro.php">Crea una Cuenta</a>
            </div>
        </form>
    </div>
</body>
</html>