<?php
include("../includes/header.php");
include("../includes/slideshow.php");
include("../config/conexion.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    echo "<p>Por favor inicia sesión para usar la búsqueda.</p>";
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Parámetros de búsqueda
$q = $_GET['q'] ?? '';
$filtro_titulo = $_GET['filtro_titulo'] ?? '';
$filtro_descripcion = $_GET['filtro_descripcion'] ?? '';
$filtro_estado = $_GET['filtro_estado'] ?? '';
$filtro_etiqueta = $_GET['filtro_etiqueta'] ?? '';

// Construir SQL dinámico
$sql = "
    SELECT t.id_tarea, t.titulo, t.descripcion, t.estado, t.prioridad, t.fecha_vencimiento,
           GROUP_CONCAT(e.nombre, '|', e.color SEPARATOR '||') as etiquetas
    FROM tareas t
    LEFT JOIN tareas_etiquetas te ON t.id_tarea = te.id_tarea
    LEFT JOIN etiquetas e ON te.id_etiqueta = e.id_etiqueta
    WHERE t.id_usuario = ?
";

$params = [$usuario_id];
$types = "i";

// Si viene búsqueda general
if (!empty($q)) {
    $sql .= " AND (t.titulo LIKE ? OR t.descripcion LIKE ? OR e.nombre LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $types .= "sss";
}

// Filtros
if (!empty($filtro_titulo)) {
    $sql .= " AND t.titulo LIKE ?";
    $params[] = "%$filtro_titulo%";
    $types .= "s";
}
if (!empty($filtro_descripcion)) {
    $sql .= " AND t.descripcion LIKE ?";
    $params[] = "%$filtro_descripcion%";
    $types .= "s";
}
if (!empty($filtro_estado)) {
    $sql .= " AND t.estado = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}
if (!empty($filtro_etiqueta)) {
    $sql .= " AND e.nombre LIKE ?";
    $params[] = "%$filtro_etiqueta%";
    $types .= "s";
}

$sql .= " GROUP BY t.id_tarea ORDER BY t.fecha_vencimiento ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error en SQL: " . $conn->error);
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/index.css" />

<div class="container mt-4">

    <!-- Formulario de filtros -->
    <div class="filtros-card">
        <h4>Filtrar Tareas</h4>
        <form method="GET" action="busqueda.php" class="filtros-form">
            <div class="row g-3">
                <!-- Filtro por título -->
                <div class="col-md-3">
                    <label for="filtro_titulo">Título</label>
                    <input type="text" id="filtro_titulo" name="filtro_titulo"
                        class="form-control"
                        placeholder="Filtrar por título"
                        value="<?= htmlspecialchars($filtro_titulo ?? '') ?>">
                </div>

                <!-- Filtro por descripción -->
                <div class="col-md-3">
                    <label for="filtro_descripcion">Descripción</label>
                    <input type="text" id="filtro_descripcion" name="filtro_descripcion"
                        class="form-control"
                        placeholder="Filtrar por descripción"
                        value="<?= htmlspecialchars($filtro_descripcion ?? '') ?>">
                </div>

                <!-- Filtro por estado -->
                <div class="col-md-2">
                    <label for="filtro_estado">Estado</label>
                    <select id="filtro_estado" name="filtro_estado" class="form-control">
                        <option value="">Todos</option>
                        <option value="pendiente" <?= ($filtro_estado ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="completada" <?= ($filtro_estado ?? '') === 'completada' ? 'selected' : '' ?>>Completada</option>
                    </select>
                </div>

                <!-- Filtro por etiqueta -->
                <div class="col-md-2">
                    <label for="filtro_etiqueta">Etiqueta</label>
                    <input type="text" id="filtro_etiqueta" name="filtro_etiqueta"
                        class="form-control"
                        placeholder="Filtrar por etiqueta"
                        value="<?= htmlspecialchars($filtro_etiqueta ?? '') ?>">
                </div>

                <!-- Botón aplicar filtros -->
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn-filtrar">Aplicar Filtros</button>
                </div>
            </div>
        </form>
    </div>

    <h2 class="mb-3">Resultados de búsqueda</h2>

    <!-- Resultados -->
    <?php if ($result->num_rows > 0): ?>
        <?php while ($tarea = $result->fetch_assoc()): ?>
            <div class="tarea"
                style="cursor:pointer; border:1px solid #333; border-radius:8px; padding:10px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h5><?= htmlspecialchars($tarea['titulo']) ?></h5>
                    <p><?= htmlspecialchars($tarea['descripcion']) ?></p>
                    <div>
                        <?php
                        if (!empty($tarea['etiquetas'])) {
                            $etiquetas = explode('||', $tarea['etiquetas']);
                            foreach ($etiquetas as $etq) {
                                list($nombre, $color) = explode('|', $etq);
                                echo "<span style='display:inline-block; padding:2px 6px; margin:2px; border-radius:6px; background:$color; color:#fff; font-size:0.85rem;'>-" . htmlspecialchars($nombre) . "</span>";
                            }
                        }
                        ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0; font-weight:bold;"><?= ucfirst($tarea['estado']) ?></p>
                    <?php if (!empty($tarea['fecha_vencimiento'])): ?>
                        <small><?= date("d/m/Y", strtotime($tarea['fecha_vencimiento'])) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-danger">No se encontraron resultados.</p>
    <?php endif; ?>
</div>

<?php include("../includes/footer.php"); ?>