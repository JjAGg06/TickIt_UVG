<?php
if (!defined('NOMBRE_SITIO')) {
    include_once(__DIR__ . '/config/config.php');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/config/conexion.php');

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: iniciosesion.php");
    exit();
}

$mensaje = "";
$id_usuario = $_SESSION['usuario_id'];

// Obtener datos actuales
$sql = "SELECT username, correo, tema_preferido, imagen FROM usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['usuario'] ?? '';
    $correo   = $_POST['correo'] ?? '';
    $contra   = $_POST['contrasena'] ?? '';
    $tema     = $_POST['tema'] ?? 'claro';

    // Con imagen nueva
    if (!empty($_FILES['foto']['tmp_name'])) {
        $foto = file_get_contents($_FILES['foto']['tmp_name']);

        if (!empty($contra)) {
            $sql = "UPDATE usuario 
                       SET username=?, correo=?, contra=?, tema_preferido=?, imagen=? 
                     WHERE id_usuario=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssbi", $username, $correo, $contra, $tema, $foto, $id_usuario);
            $stmt->send_long_data(4, $foto);
        } else {
            $sql = "UPDATE usuario 
                       SET username=?, correo=?, tema_preferido=?, imagen=? 
                     WHERE id_usuario=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssbi", $username, $correo, $tema, $foto, $id_usuario);
            $stmt->send_long_data(3, $foto);
        }
    } else {
        if (!empty($contra)) {
            $sql = "UPDATE usuario 
                       SET username=?, correo=?, contra=?, tema_preferido=? 
                     WHERE id_usuario=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $username, $correo, $contra, $tema, $id_usuario);
        } else {
            $sql = "UPDATE usuario 
                       SET username=?, correo=?, tema_preferido=? 
                     WHERE id_usuario=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $username, $correo, $tema, $id_usuario);
        }
    }

    if ($stmt->execute()) {
        $mensaje = "Perfil actualizado correctamente.";

        // Refrescar datos en sesión (username)
        $_SESSION['usuario_nombre'] = $username;

        // Refrescar datos del usuario
        $sql = "SELECT username, correo, tema_preferido, imagen FROM usuario WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
    } else {
        $mensaje = "Error al actualizar: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/sesion.css"/>
</head>
<body>
<div class="login-contenedor">
    <form method="POST" action="" enctype="multipart/form-data">
        <h2>Editar Perfil</h2>

        <?php if (!empty($mensaje)): ?>
            <p class="mensaje-error"><?php echo $mensaje; ?></p>
        <?php endif; ?>

        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario"
               value="<?php echo htmlspecialchars($usuario['username']); ?>" required>

        <label for="correo">Correo</label>
        <input type="email" id="correo" name="correo"
               value="<?php echo htmlspecialchars($usuario['correo']); ?>" required>

        <label for="contrasena">Nueva Contraseña (opcional)</label>
        <input type="password" id="contrasena" name="contrasena">

        <label for="tema">Tema Preferido</label>
        <select id="tema" name="tema">
            <option value="claro" <?php if($usuario['tema_preferido']=="claro") echo "selected"; ?>>Claro</option>
            <option value="oscuro" <?php if($usuario['tema_preferido']=="oscuro") echo "selected"; ?>>Oscuro</option>
        </select>

        <label for="foto">Foto de Perfil</label>
        <input type="file" id="foto" name="foto">

        <?php if (!empty($usuario['imagen'])): ?>
            <div>
                <p>Foto actual:</p>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($usuario['imagen']); ?>" width="150">
            </div>
        <?php endif; ?>

        <button type="submit">Guardar Cambios</button>

        <div class="contenedor">
        <a href="index.php" class="btn-regresar">⬅ Regresar</a>
    </div>
    </form>

</body>
</html>

