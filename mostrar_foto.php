<?php
include_once(__DIR__ . '/config/conexion.php');

$id = intval($_GET['id']);
$sql = "SELECT foto FROM usuario WHERE id_usuario=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && !empty($row['foto'])) {
    header("Content-type: image/jpeg");
    echo $row['foto'];
}
?>
