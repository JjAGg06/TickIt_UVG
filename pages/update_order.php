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
    http_response_code(403);
    echo "No autorizado";
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Leer JSON del body
$data = json_decode(file_get_contents("php://input"), true);

if ($data && is_array($data)) {
    foreach ($data as $item) {
        $id_tarea = intval($item['id']);
        $orden = intval($item['orden']);

        $stmt = $conn->prepare("UPDATE tareas SET orden = ? WHERE id_tarea = ? AND id_usuario = ?");
        $stmt->bind_param("iii", $orden, $id_tarea, $usuario_id);
        $stmt->execute();
    }

    echo "Orden actualizado correctamente";
} else {
    echo "Datos inválidos";
}