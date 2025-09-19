<?php
session_start();
include("../config/conexion.php");

// Validar sesión
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

// ====================
// MARCAR TAREA COMO COMPLETADA
// ====================
if($tipo === 'completada') {
    $sql = "UPDATE tareas SET estado='completada' WHERE id_tarea=? AND id_usuario=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    echo ($stmt->affected_rows > 0) ? 'ok' : "error_sql: filas=0 | id=$id | usuario=$usuario_id";
    $stmt->close();
    exit;
}

// ====================
// VER DETALLES DE TAREA
// ====================
if($tipo === 'ver') {
    $sql = "SELECT titulo, descripcion FROM tareas WHERE id_tarea=? AND id_usuario=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($tarea = $result->fetch_assoc()){
        echo "<h2>" . htmlspecialchars($tarea['titulo']) . "</h2>";
        echo "<p>" . htmlspecialchars($tarea['descripcion']) . "</p>";
    } else {
        echo "<p>Tarea no encontrada.</p>";
    }
    $stmt->close();
    exit;
}

// ====================
// VER ETIQUETAS DE LA TAREA
// ====================
if($tipo === 'etiquetas') {
    $sql = "SELECT e.nombre 
            FROM etiquetas e
            INNER JOIN tareas_etiquetas te ON e.id_etiqueta = te.id_etiqueta
            WHERE te.id_tarea=? AND te.id_usuario=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo "<ul>";
    while($etiqueta = $result->fetch_assoc()){
        echo "<li>" . htmlspecialchars($etiqueta['nombre']) . "</li>";
    }
    echo "</ul>";
    $stmt->close();
    exit;
}

echo "tipo_invalido";
exit;
