<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../config/conexion.php');

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo "No autorizado";
    exit;
}

// Validar parámetro
if (!isset($_POST['tema']) || !in_array($_POST['tema'], ['claro', 'oscuro'])) {
    http_response_code(400);
    echo "Parámetro inválido";
    exit;
}

$id = $_SESSION['usuario_id'];
$tema = $_POST['tema'];

// Guardar en BD
$sql = "UPDATE usuario SET tema_preferido = ? WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $tema, $id);

if ($stmt->execute()) {
    echo "Tema actualizado a $tema";
} else {
    http_response_code(500);
    echo "Error al actualizar";
}
