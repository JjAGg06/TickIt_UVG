<?php
session_start();
include("../config/conexion.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo "no_auth";
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Validar parámetros
if (!isset($_POST['id']) || !isset($_POST['tipo'])) {
    echo "missing_params";
    exit;
}

$id = intval($_POST['id']);
$tipo = $_POST['tipo'];

// ====================
// MARCAR RECORDATORIO
// ====================
if ($tipo === 'recordatorio') {
    $sql = "UPDATE recordatorios r
            INNER JOIN tareas t ON r.id_tarea = t.id_tarea
            SET r.enviado = 1
            WHERE r.id_recordatorio = ? AND t.id_usuario = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "prepare_error: " . $conn->error;
        exit;
    }
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "ok";
    } else {
        echo "error_sql: filas=0 | id=$id | usuario=$usuario_id";
    }
    $stmt->close();
    exit;
}

// ====================
// MARCAR TAREA
// ====================
if ($tipo === 'tarea') {
    // ⚡ Aquí mejor cambiamos el estado de la tarea a 'completada'
    $sql = "UPDATE tareas 
            SET estado = 'completada' 
            WHERE id_tarea = ? AND id_usuario = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "prepare_error: " . $conn->error;
        exit;
    }
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "ok";
    } else {
        echo "error_sql: filas=0 | id=$id | usuario=$usuario_id";
    }
    $stmt->close();
    exit;
}

echo "tipo_invalido";
exit;
