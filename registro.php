<?php
if (!defined('NOMBRE_SITIO')) {
    include_once(__DIR__ . '/config/config.php');
}

session_start();

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    include_once(__DIR__ . '/config/conexion.php');

    $usuario = $_POST['usuario'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $contra = $_POST['contrasena'] ?? '';
    $tema = $_POST['tema'] ?? 'claro'; // valor por defecto

    // Verificar si el usuario ya existe
    $sql = "SELECT id_usuario FROM usuario WHERE username = ? OR correo = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $usuario, $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $mensaje = "El usuario o correo ya están registrados.";
    } else {
        // Insertar en la BD
        $sql = "INSERT INTO usuario (username, correo, contra, tema_preferido) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $usuario, $correo, $contra, $tema);

        if ($stmt->execute()) {
            $mensaje = "Registro exitoso, ahora puedes iniciar sesión.";
        } else {
            $mensaje = "Error al registrar: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/sesion.css"/>
</head>
<body>
    <div class="login-contenedor">
        <form method="POST" action="">
            <h2>Registro</h2>

            <?php if (!empty($mensaje)): ?>
                <p class="mensaje-error"><?php echo $mensaje; ?></p>
            <?php endif; ?>

            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required>

            <label for="correo">Correo</label>
            <input type="email" id="correo" name="correo" required>

            <label for="contrasena">Contraseña</label>
            <input type="password" id="contrasena" name="contrasena" required>

            <label for="tema">Tema Preferido</label>
            <select id="tema" name="tema">
                <option value="claro">Claro</option>
                <option value="oscuro">Oscuro</option>
            </select>

            <button type="submit">Registrarse</button>

            <div class="contenedor">
                <a href="iniciosesion.php">¿Ya tienes cuenta? Inicia sesión</a>
            </div>
        </form>
    </div>
</body>
</html>
