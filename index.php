<?php
include("includes/header.php"); // Incluye header y toggle de tema
include("includes/slideshow.php"); // Incluye slide

if (session_status() === PHP_SESSION_NONE) session_start();
include("config/conexion.php");

if (!isset($_SESSION['usuario_id'])) {
    echo "<p>Por favor inicia sesión para ver tus tareas.</p>";
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener tareas
$sql = "
    SELECT t.id_tarea, t.titulo, t.descripcion, t.estado, t.prioridad, t.fecha_vencimiento,
           GROUP_CONCAT(e.nombre, '|', e.color SEPARATOR '||') as etiquetas
    FROM tareas t
    LEFT JOIN tareas_etiquetas te ON t.id_tarea = te.id_tarea
    LEFT JOIN etiquetas e ON te.id_etiqueta = e.id_etiqueta
    WHERE t.id_usuario = ?
    GROUP BY t.id_tarea
    ORDER BY t.fecha_vencimiento ASC, t.orden ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$tareas_por_fecha = [];
while ($row = $result->fetch_assoc()) {
    $fecha = $row['fecha_vencimiento'] ?? 'Sin fecha';
    if (!isset($tareas_por_fecha[$fecha])) $tareas_por_fecha[$fecha] = [];
    $tareas_por_fecha[$fecha][] = $row;
}

$hoy = date("Y-m-d");
$maniana = date("Y-m-d", strtotime("+1 day"));
?>

<link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/index.css" />
<link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/dark.css" />

<div class="container mt-4">
    <?php foreach ($tareas_por_fecha as $fecha => $tareas): ?>
        <?php
        if ($fecha === 'Sin fecha') $titulo_fecha = 'Tareas sin fecha';
        elseif ($fecha === $hoy) $titulo_fecha = 'Tareas para hoy';
        elseif ($fecha === $maniana) $titulo_fecha = 'Tareas para mañana';
        else $titulo_fecha = 'Tareas para ' . date("d/m/Y", strtotime($fecha));
        ?>
        <h3 class="mb-3"><?= $titulo_fecha ?></h3>
        <hr class="linea">
        <?php foreach ($tareas as $tarea): ?>
            <div class="tarea" data-id="<?= $tarea['id_tarea'] ?>">
                <div>
                    <h5><?= htmlspecialchars($tarea['titulo']) ?></h5>
                    <div>
                        <?php
                        if (!empty($tarea['etiquetas'])) {
                            $etiquetas = explode('||', $tarea['etiquetas']);
                            foreach ($etiquetas as $etq) {
                                list($nombre, $color) = explode('|', $etq);
                                echo "<span style='background:$color;'>-" . htmlspecialchars($nombre) . "</span>";
                            }
                        }
                        ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0; font-weight:bold;"><?= ucfirst($tarea['estado']) ?></p>
                    <?php if ($fecha !== 'Sin fecha'): ?>
                        <small><?= date("l - gA", strtotime($fecha)) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

<div id="modalTarea">
    <div>
        <h5>¿Qué quieres hacer?</h5>
        <button id="btnVerTarea">Ver Tarea</button><br>
        <button id="btnVerEtiquetas">Ver Etiquetas</button><br>
        <button id="btnCompletar">Marcar como Completada</button><br>
        <button id="btnCerrar">Cancelar</button>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("modalTarea");
    let tareaSeleccionada = null;

    document.querySelectorAll(".tarea").forEach(div => {
        div.addEventListener("click", () => {
            tareaSeleccionada = div.getAttribute("data-id");
            modal.style.display = "flex";
        });
    });

    document.getElementById("btnCerrar").addEventListener("click", () => modal.style.display = "none");
    modal.addEventListener("click", e => { if (e.target === modal) modal.style.display = "none"; });
});
</script>

<?php include("includes/footer.php"); ?>
