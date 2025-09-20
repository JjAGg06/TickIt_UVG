<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo "no_auth";
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

if (!isset($_POST['id']) || !isset($_POST['tipo'])) {
    echo "missing_params";
    exit;
}

$id = intval($_POST['id']);
$tipo = $_POST['tipo'];

// MARCAR TAREA COMO LEÍDA
if($tipo === 'tarea') {
    $sql = "UPDATE tareas SET visto=1 WHERE id_tarea=? AND id_usuario=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    echo ($stmt->affected_rows > 0) ? 'ok' : "error_sql_tarea";
    $stmt->close();
    exit;
}

// MARCAR RECORDATORIO COMO VISTO
if($tipo === 'recordatorio') {
    $sql = "UPDATE recordatorios r
            INNER JOIN tareas t ON r.id_tarea = t.id_tarea
            SET r.enviado=1
            WHERE r.id_recordatorio=? AND t.id_usuario=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    echo ($stmt->affected_rows > 0) ? 'ok' : "error_sql_recordatorio";
    $stmt->close();
    exit;
}

echo "tipo_invalido";
exit;