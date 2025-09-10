<?php
if (!defined('NOMBRE_SITIO')) {
    include_once(__DIR__ . '/../config/config.php');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../config/conexion.php');

// Traer datos del usuario logueado
$user = null;
if (isset($_SESSION['usuario_id'])) {
    $id = $_SESSION['usuario_id'];
    $sql = "SELECT id_usuario, username, imagen FROM usuario WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error en consulta usuario: " . $conn->error);
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $user = $resultado->fetch_assoc();
}

// Redirigir si no hay usuario
if (!$user) {
    header("Location: " . URL_BASE . "/iniciosesion.php");
    exit;
}

// Colores disponibles
$colores_disponibles = [
    '#ff0000' => 'Rojo',
    '#008c3e' => 'Verde',
    '#0000ff' => 'Azul',
    '#ffff00' => 'Amarillo',
    '#ffa500' => 'Naranja',
    '#800080' => 'Morado'
];

// Variables para edición
$editando = false;
$edit_id = 0;
$nombre = '';
$color = '#ff0000';

if (isset($_GET['editar'])) {
    $edit_id = intval($_GET['editar']);
    $stmt = $conn->prepare("SELECT nombre, color FROM etiquetas WHERE id_etiqueta = ? AND id_usuario = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $edit_id, $user['id_usuario']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $fila = $res->fetch_assoc();
            $nombre = $fila['nombre'];
            $color = $fila['color'];
            $editando = true;
        }
    } else {
        echo "<div class='alert alert-danger'>Error SQL: " . $conn->error . "</div>";
    }
}
?>

<?php include(__DIR__ . '/../includes/header.php'); ?>

<!-- CSS personalizado -->
<link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/estilos-etiquetas.css" />

<div class="container mt-4">
    <h2 class="mb-4">Gestión de Etiquetas <b><?php echo htmlspecialchars($user['username']); ?></b></h2>

    <!-- Formulario -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="POST" action="guardar_etiqueta.php">
                <div class="form-group mb-3">
                    <label><b>Nombre de la etiqueta:</b></label>
                    <input type="text" name="nombre" class="form-control" 
                           value="<?= htmlspecialchars($nombre) ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label><b>Color:</b></label>
                    <select name="color" class="form-control" required>
                        <?php foreach ($colores_disponibles as $hex => $nombre_color): ?>
                            <option value="<?= $hex ?>" <?= ($color === $hex) ? 'selected' : '' ?>>
                                <?= $nombre_color ?> (<?= $hex ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= $edit_id ?>">
                    <button type="submit" name="actualizar" class="btn btn-primary">
                        <i class="bi bi-pencil-square"></i> Actualizar
                    </button>
                    <a href="etiquetas.php" class="btn btn-secondary">Cancelar</a>
                <?php else: ?>
                    <button type="submit" name="crear" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Crear
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Tabla de etiquetas -->
    <h4 class="mb-3">Mis etiquetas</h4>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle shadow-sm">
            <thead class="table-dark">
                <tr>
                    <th>Nombre</th>
                    <th>Color HEX</th>
                    <th>Vista previa</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT id_etiqueta, nombre, color FROM etiquetas WHERE id_usuario = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("i", $user['id_usuario']);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    while ($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nombre']) ?></td>
                            <td><?= htmlspecialchars($row['color']) ?></td>
                            <td>
                                <div style="width:25px; height:25px; border-radius:50%; background: <?= htmlspecialchars($row['color']) ?>; border:1px solid #ccc;"></div>
                            </td>
                            <td>
                                <a href="etiquetas.php?editar=<?= $row['id_etiqueta'] ?>" 
                                   class="btn btn-warning btn-sm">
                                   Editar
                                </a>
                                <a href="guardar_etiqueta.php?eliminar=<?= $row['id_etiqueta'] ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('¿Eliminar esta etiqueta?')">
                                   Eliminar
                                </a>
                            </td>
                        </tr>
                    <?php endwhile;
                } else {
                    echo "<tr><td colspan='4' class='text-danger'>Error SQL: " . $conn->error . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
