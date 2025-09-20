<?php
if (!defined('NOMBRE_SITIO')) {
    include_once(__DIR__ . '/../config/config.php');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../config/conexion.php');

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . URL_BASE . "/iniciosesion.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Función para manejar errores de consultas
function checkStmt($stmt, $msg) {
    if (!$stmt) {
        die("Error en prepare ($msg): " . $GLOBALS['conn']->error);
    }
    return $stmt;
}

// CREAR ETIQUETA
if (isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre']);
    $color = $_POST['color'] ?? '#ff0000';

    if (!empty($nombre)) {
        $stmt = checkStmt(
            $conn->prepare("INSERT INTO etiquetas (id_usuario, nombre, color) VALUES (?, ?, ?)"),
            "crear"
        );
        $stmt->bind_param("iss", $usuario_id, $nombre, $color);
        $stmt->execute();
    }

    header("Location: etiquetas.php");
    exit;
}

// ACTUALIZAR ETIQUETA
if (isset($_POST['actualizar']) && isset($_POST['id'])) {
    $id_etiqueta = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $color = $_POST['color'] ?? '#ff0000';

    if (!empty($nombre)) {
        $stmt = checkStmt(
            $conn->prepare("UPDATE etiquetas SET nombre = ?, color = ? WHERE id_etiqueta = ? AND id_usuario = ?"),
            "actualizar"
        );
        $stmt->bind_param("ssii", $nombre, $color, $id_etiqueta, $usuario_id);
        $stmt->execute();
    }

    header("Location: etiquetas.php");
    exit;
}

// ELIMINAR ETIQUETA
if (isset($_GET['eliminar'])) {
    $id_etiqueta = intval($_GET['eliminar']);

    $stmt = checkStmt(
        $conn->prepare("DELETE FROM etiquetas WHERE id_etiqueta = ? AND id_usuario = ?"),
        "eliminar"
    );
    $stmt->bind_param("ii", $id_etiqueta, $usuario_id);
    $stmt->execute();

    header("Location: etiquetas.php");
    exit;
}
?>