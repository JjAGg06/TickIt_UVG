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
if (isset($_SESSION['usuario_id'])) {
    $id = $_SESSION['usuario_id'];
    $sql = "SELECT id_usuario, username, imagen FROM usuario WHERE id_usuario = ?";
    $stmt_user = $conn->prepare($sql);

    if (!$stmt_user) {
        die("Error en consulta usuario: " . $conn->error);
    }

    $stmt_user->bind_param("i", $id);
    $stmt_user->execute();
    $resultado_user = $stmt_user->get_result();
    $user = $resultado_user->fetch_assoc();
}

// Redirigir si no hay usuario
if (!$user) {
    header("Location: " . URL_BASE . "/iniciosesion.php");
    exit;
}

// Variables para edición
$editando = false;
$edit_id = 0;
$titulo = '';
$descripcion = '';
$estado = 'pendiente';
$prioridad = 'media';
$fecha_vencimiento = '';
$etiqueta_id = null;

// Obtener lista de etiquetas del usuario
$etiquetas = [];
$stmt_etq = $conn->prepare("SELECT id_etiqueta, nombre FROM etiquetas WHERE id_usuario = ?");
$stmt_etq->bind_param("i", $user['id_usuario']);
$stmt_etq->execute();
$res_etq = $stmt_etq->get_result();
while ($row = $res_etq->fetch_assoc()) {
    $etiquetas[] = $row;
}

// Si está editando una tarea
if (isset($_GET['editar'])) {
    $edit_id = intval($_GET['editar']);
    $sql = "SELECT t.titulo, t.descripcion, t.estado, t.prioridad, t.fecha_vencimiento, te.id_etiqueta
             FROM tareas t
             LEFT JOIN tareas_etiquetas te ON t.id_tarea = te.id_tarea
             WHERE t.id_tarea = ? AND t.id_usuario = ?";
    $stmt_edit = $conn->prepare($sql);
    if ($stmt_edit) {
        $stmt_edit->bind_param("ii", $edit_id, $user['id_usuario']);
        $stmt_edit->execute();
        $res_edit = $stmt_edit->get_result();
        if ($res_edit && $res_edit->num_rows === 1) {
            $fila = $res_edit->fetch_assoc();
            $titulo = $fila['titulo'];
            $descripcion = $fila['descripcion'];
            $estado = $fila['estado'];
            $prioridad = $fila['prioridad'];
            $fecha_vencimiento = $fila['fecha_vencimiento'];
            $etiqueta_id = $fila['id_etiqueta'];
            $editando = true;
        }
    }
}

// ==========================
// Cargar todas las tareas del usuario
// ==========================
$tareas = [];
$sql = "SELECT t.id_tarea, t.titulo, t.descripcion, t.estado, t.prioridad, t.fecha_vencimiento, t.orden,
                GROUP_CONCAT(e.nombre SEPARATOR ', ') AS etiquetas
        FROM tareas t
        LEFT JOIN tareas_etiquetas te ON t.id_tarea = te.id_tarea
        LEFT JOIN etiquetas e ON te.id_etiqueta = e.id_etiqueta
        WHERE t.id_usuario = ?
        GROUP BY t.id_tarea
        ORDER BY t.orden ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id_usuario']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $tareas[] = $row;
}
?>

<?php include(__DIR__ . '/../includes/header.php'); ?>

<link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/estilos-etiquetas.css" />

<div class="container mt-4">
    <h2 class="mb-4">Gestión de Tareas <b><?php echo htmlspecialchars($user['username']); ?></b></h2>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="POST" action="guardar_tarea.php">
                <div class="form-group mb-3">
                    <label><b>Título:</b></label>
                    <input type="text" name="titulo" class="form-control"
                        value="<?= htmlspecialchars($titulo) ?>" required>
                </div>

                <div class="form-group mb-3">
                    <label><b>Descripción:</b></label>
                    <textarea name="descripcion" class="form-control" rows="3" required><?= htmlspecialchars($descripcion) ?></textarea>
                </div>

                <div class="form-group mb-3">
                    <label><b>Estado:</b></label>
                    <select name="estado" class="form-control" required>
                        <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="completada" <?= $estado === 'completada' ? 'selected' : '' ?>>Completada</option>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label><b>Prioridad:</b></label>
                    <select name="prioridad" class="form-control" required>
                        <option value="alta" <?= $prioridad === 'alta' ? 'selected' : '' ?>>Alta</option>
                        <option value="media" <?= $prioridad === 'media' ? 'selected' : '' ?>>Media</option>
                        <option value="baja" <?= $prioridad === 'baja' ? 'selected' : '' ?>>Baja</option>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label><b>Fecha de vencimiento:</b></label>
                    <input type="date" name="fecha_vencimiento" class="form-control"
                        value="<?= htmlspecialchars($fecha_vencimiento) ?>">
                </div>

                <div class="form-group mb-3">
                    <label><b>Etiqueta:</b></label>
                    <select name="etiqueta_id" class="form-control">
                        <option value="">Sin etiqueta</option>
                        <?php foreach ($etiquetas as $etq): ?>
                            <option value="<?= $etq['id_etiqueta'] ?>" <?= ($etiqueta_id == $etq['id_etiqueta']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($etq['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= $edit_id ?>">
                    <button type="submit" name="editar" class="btn btn-primary">Actualizar</button>
                    <a href="<?php echo URL_BASE ?>/pages/tareas.php" class="btn btn-secondary">Cancelar</a>
                <?php else: ?>
                    <button type="submit" name="crear" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Crear
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle shadow-sm">
            <thead class="table-dark">
                <tr>
                    <th>Orden</th>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Etiquetas</th>
                    <th>Vencimiento</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="sortable">
                <?php foreach ($tareas as $t): ?>
                    <tr data-id="<?php echo $t['id_tarea']; ?>">
                        <td><?php echo (int)$t['orden']; ?></td>
                        <td><?php echo htmlspecialchars($t['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($t['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($t['prioridad']); ?></td>
                        <td><?php echo htmlspecialchars($t['estado']); ?></td>
                        <td><?php echo $t['etiquetas'] ?: '<small class="text-muted">Sin etiquetas</small>'; ?></td>
                        <td><?php echo !empty($t['fecha_vencimiento']) ? htmlspecialchars($t['fecha_vencimiento']) : '-'; ?></td>
                        <td>
                            <a href="<?php echo URL_BASE ?>/pages/tareas.php?editar=<?php echo $t['id_tarea']; ?>"
                                class="btn btn-warning btn-sm action-link">Editar</a>
                            <a href="<?php echo URL_BASE ?>/pages/guardar_tarea.php?eliminar=<?php echo $t['id_tarea']; ?>"
                                class="btn btn-danger btn-sm btnEliminar action-link">Eliminar</a>
                            <a href="<?php echo URL_BASE ?>/pages/imprimir.php?id=<?php echo $t['id_tarea']; ?>"
                                class="btn btn-warning btn-sm action-link">Imprimir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include(__DIR__ . '/../includes/footer.php'); ?>

<div id="modalEliminar" class="modal-custom">
    <div class="modal-content-custom">
        <h5>¿Estás seguro de eliminar esta Tarea?</h5>
        <div class="mt-3">
            <button id="btnSiEliminar" class="btn btn-success">Sí</button>
            <button id="btnNoEliminar" class="btn btn-secondary">No</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const tabla = document.getElementById("sortable");

        new Sortable(tabla, {
            animation: 150,
            onEnd: () => {
                let orden = [];
                tabla.querySelectorAll("tr[data-id]").forEach((row, index) => {
                    orden.push({
                        id: row.dataset.id,
                        orden: index + 1
                    });
                });

                fetch("<?php echo URL_BASE ?>/pages/update_order.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify(orden)
                    })
                    .then(res => res.text())
                    .then(resp => {
                        console.log("Respuesta servidor:", resp);
                    })
                    .catch(err => console.error("Error:", err));
            }
        });

        // MODAL eliminar
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
    });
</script>