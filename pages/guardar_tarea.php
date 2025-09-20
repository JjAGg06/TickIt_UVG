<?php
if (!defined('NOMBRE_SITIO')) {
    include_once(__DIR__ . '/../config/config.php');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../config/conexion.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . URL_BASE . "/iniciosesion.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

function checkStmt($stmt, $msg) {
    if (!$stmt) {
        die("Error en prepare ($msg): " . $GLOBALS['conn']->error);
    }
    return $stmt;
}

// CREAR TAREA
if (isset($_POST['crear'])) {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $estado = $_POST['estado'] ?? 'pendiente';
    $prioridad = $_POST['prioridad'] ?? 'media';
    $fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
    $etiqueta_id = intval($_POST['etiqueta_id'] ?? 0);

    if (!empty($titulo)) {
        // Encontrar el valor de orden más alto para el usuario y sumarle 1
        $sql_orden = "SELECT MAX(orden) AS max_orden FROM tareas WHERE id_usuario = ?";
        $stmt_orden = checkStmt($conn->prepare($sql_orden), "obtener_orden");
        $stmt_orden->bind_param("i", $usuario_id);
        $stmt_orden->execute();
        $res_orden = $stmt_orden->get_result();
        $fila_orden = $res_orden->fetch_assoc();
        $orden = ($fila_orden['max_orden'] ?? 0) + 1;

        $stmt = checkStmt(
            $conn->prepare("INSERT INTO tareas 
                (id_usuario, titulo, descripcion, estado, prioridad, fecha_creacion, fecha_vencimiento, orden) 
                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)"),
            "crear"
        );
        $stmt->bind_param("isssssi", $usuario_id, $titulo, $descripcion, $estado, $prioridad, $fecha_vencimiento, $orden);
        $stmt->execute();

        $id_tarea = $stmt->insert_id;

        // Guardar relación con etiqueta
        if ($etiqueta_id > 0) {
            $stmt_etq = checkStmt(
                $conn->prepare("INSERT INTO tareas_etiquetas (id_tarea, id_etiqueta) VALUES (?, ?)"),
                "crear_relacion"
            );
            $stmt_etq->bind_param("ii", $id_tarea, $etiqueta_id);
            $stmt_etq->execute();
        }
    }

    header("Location: tareas.php");
    exit;
}

// ACTUALIZAR TAREA
if (isset($_POST['editar']) && isset($_POST['id'])) {
    $id_tarea = intval($_POST['id']);
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $estado = $_POST['estado'] ?? 'pendiente';
    $prioridad = $_POST['prioridad'] ?? 'media';
    $fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
    $etiqueta_id = intval($_POST['etiqueta_id'] ?? 0);

    if (!empty($titulo)) {
        $stmt = checkStmt(
            $conn->prepare("UPDATE tareas 
                             SET titulo = ?, descripcion = ?, estado = ?, prioridad = ?, fecha_vencimiento = ?
                             WHERE id_tarea = ? AND id_usuario = ?"),
            "actualizar"
        );
        $stmt->bind_param("sssssii", $titulo, $descripcion, $estado, $prioridad, $fecha_vencimiento, $id_tarea, $usuario_id);
        $stmt->execute();

        // Actualizar relación etiqueta
        // Primero eliminar cualquier relación anterior
        $stmt_del_etq = checkStmt($conn->prepare("DELETE FROM tareas_etiquetas WHERE id_tarea = ?"), "eliminar_relacion");
        $stmt_del_etq->bind_param("i", $id_tarea);
        $stmt_del_etq->execute();

        // Luego insertar la nueva relación si se seleccionó etiqueta
        if ($etiqueta_id > 0) {
            $stmt_etq = checkStmt(
                $conn->prepare("INSERT INTO tareas_etiquetas (id_tarea, id_etiqueta) VALUES (?, ?)"),
                "actualizar_relacion"
            );
            $stmt_etq->bind_param("ii", $id_tarea, $etiqueta_id);
            $stmt_etq->execute();
        }
    }

    header("Location: tareas.php");
    exit;
}

// ELIMINAR TAREA
if (isset($_GET['eliminar'])) {
    $id_tarea = intval($_GET['eliminar']);

    // Usar sentencias preparadas para mayor seguridad
    $stmt_del_etq = checkStmt($conn->prepare("DELETE FROM tareas_etiquetas WHERE id_tarea = ?"), "eliminar_relacion_tarea");
    $stmt_del_etq->bind_param("i", $id_tarea);
    $stmt_del_etq->execute();

    $stmt = checkStmt(
        $conn->prepare("DELETE FROM tareas WHERE id_tarea = ? AND id_usuario = ?"),
        "eliminar"
    );
    $stmt->bind_param("ii", $id_tarea, $usuario_id);
    $stmt->execute();

    header("Location: tareas.php");
    exit;
}

// MARCAR COMO COMPLETADA
if (isset($_POST['completar']) && isset($_POST['id'])) {
    $id_tarea = intval($_POST['id']);

    $stmt = checkStmt(
        $conn->prepare("UPDATE tareas SET estado = 'completada' WHERE id_tarea = ? AND id_usuario = ?"),
        "completar"
    );
    $stmt->bind_param("ii", $id_tarea, $usuario_id);
    $stmt->execute();

    echo "ok";
    exit;
}
?>