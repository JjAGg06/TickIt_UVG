<?php
include("../includes/slideshow.php");
if (!defined('NOMBRE_SITIO')) {
    include_once(__DIR__ . '/../config/config.php');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../config/conexion.php');

// Traer datos del usuario logueado
$user = null;
$tema_preferido = 'claro';

if (isset($_SESSION['usuario_id'])) {
    $id = $_SESSION['usuario_id'];

    $sql = "SELECT id_usuario, username, imagen, tema_preferido FROM usuario WHERE id_usuario = ?";
    $stmt_user = $conn->prepare($sql);

    if (!$stmt_user) {
        die("Error en consulta usuario: " . $conn->error);
    }

    $stmt_user->bind_param("i", $id);
    $stmt_user->execute();
    $resultado_user = $stmt_user->get_result();
    $user = $resultado_user->fetch_assoc();

    if ($user) {
        $tema_preferido = $user['tema_preferido'] ?? 'claro';
    }
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
    $stmt_edit = $conn->prepare("SELECT nombre, color FROM etiquetas WHERE id_etiqueta = ? AND id_usuario = ?");
    if ($stmt_edit) {
        $stmt_edit->bind_param("ii", $edit_id, $user['id_usuario']);
        $stmt_edit->execute();
        $res_edit = $stmt_edit->get_result();
        if ($res_edit && $res_edit->num_rows === 1) {
            $fila = $res_edit->fetch_assoc();
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

<link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/estilos-etiquetas.css" />

<body class="<?php echo ($tema_preferido === 'oscuro') ? 'dark-mode' : ''; ?>">
    <div class="container mt-4">
        <h2 class="mb-4">Gestión de Etiquetas <b><?php echo htmlspecialchars($user['username']); ?></b></h2>

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
                        <button type="submit" name="actualizar" class="btn btn-primary">Actualizar</button>
                        <a href="etiquetas.php" class="btn btn-secondary">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="crear" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Crear
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

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
                    $stmt_etiquetas = $conn->prepare($sql);
                    if ($stmt_etiquetas) {
                        $stmt_etiquetas->bind_param("i", $user['id_usuario']);
                        $stmt_etiquetas->execute();
                        $res_etiquetas = $stmt_etiquetas->get_result();

                        while ($row_etq = $res_etiquetas->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row_etq['nombre']) ?></td>
                                <td><?= htmlspecialchars($row_etq['color']) ?></td>
                                <td>
                                    <div style="width:25px; height:25px; border-radius:50%; background: <?= htmlspecialchars($row_etq['color']) ?>; border:1px solid #ccc;"></div>
                                </td>
                                <td>
                                    <a href="<?php echo URL_BASE ?>/pages/etiquetas.php?editar=<?= $row_etq['id_etiqueta'] ?>"
                                        class="btn btn-warning btn-sm action-link">Editar</a>
                                    <a href="<?php echo URL_BASE ?>/pages/guardar_etiqueta.php?eliminar=<?= $row_etq['id_etiqueta'] ?>"
                                        class="btn btn-danger btn-sm btnEliminar action-link">
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

    <div id="modalEliminar" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
     background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:9999;">
        <div style="background:#fff; padding:20px; border-radius:12px; width:320px; text-align:center;">
            <h5>¿Estás seguro de eliminar esta etiqueta?</h5>
            <div class="mt-3">
                <button id="btnSiEliminar" class="btn btn-success">Sí</button>
                <button id="btnNoEliminar" class="btn btn-secondary">No</button>
            </div>
        </div>
    </div>

    <script>
        let enlaceEliminar = null;
        const modal = document.getElementById("modalEliminar");
        const btnSi = document.getElementById("btnSiEliminar");
        const btnNo = document.getElementById("btnNoEliminar");

        document.querySelectorAll(".btnEliminar").forEach(link => {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                enlaceEliminar = this.getAttribute("href");
                modal.style.display = "flex";
            });
        });

        btnSi.addEventListener("click", function() {
            if (enlaceEliminar) {
                window.location.href = enlaceEliminar;
            }
        });

        btnNo.addEventListener("click", function() {
            modal.style.display = "none";
            enlaceEliminar = null;
        });

        modal.addEventListener("click", function(e) {
            if (e.target === modal) {
                modal.style.display = "none";
                enlaceEliminar = null;
            }
        });
    </script>

    <?php include(__DIR__ . '/../includes/footer.php'); ?>
</body>