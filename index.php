<?php
include("includes/header.php");
include("includes/slideshow.php");

// Asegurarnos de tener la sesión del usuario
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("config/conexion.php");

if (!isset($_SESSION['usuario_id'])) {
    echo "<p>Por favor inicia sesión para ver tus tareas.</p>";
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Traer tareas agrupadas por fecha de vencimiento (SOLO pendientes)
$sql = "
    SELECT t.id_tarea, t.titulo, t.descripcion, t.estado, t.prioridad, t.fecha_vencimiento,
           GROUP_CONCAT(e.nombre, '|', e.color SEPARATOR '||') as etiquetas
    FROM tareas t
    LEFT JOIN tareas_etiquetas te ON t.id_tarea = te.id_tarea
    LEFT JOIN etiquetas e ON te.id_etiqueta = e.id_etiqueta
    WHERE t.id_usuario = ? AND t.estado = 'pendiente'
    GROUP BY t.id_tarea
    ORDER BY t.fecha_vencimiento ASC, t.orden ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

// Agrupar por fecha
$tareas_por_fecha = [];
while ($row = $result->fetch_assoc()) {
    $fecha = $row['fecha_vencimiento'] ?? 'Sin fecha';
    if (!isset($tareas_por_fecha[$fecha])) {
        $tareas_por_fecha[$fecha] = [];
    }
    $tareas_por_fecha[$fecha][] = $row;
}

// Fechas de referencia
$hoy = date("Y-m-d");
$maniana = date("Y-m-d", strtotime("+1 day"));
?>

<link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/index.css" />

<div class="container mt-4">
    <?php foreach ($tareas_por_fecha as $fecha => $tareas): ?>
        <?php
        if ($fecha === 'Sin fecha') {
            $titulo_fecha = 'Tareas sin fecha';
        } elseif ($fecha === $hoy) {
            $titulo_fecha = 'Tareas para hoy';
        } elseif ($fecha === $maniana) {
            $titulo_fecha = 'Tareas para mañana';
        } else {
            $titulo_fecha = 'Tareas para ' . date("d/m/Y", strtotime($fecha));
        }
        ?>
        <h3 class="mb-3"><?= $titulo_fecha ?></h3>
        <hr class="linea">
        <?php foreach ($tareas as $tarea): ?>
            <div class="tarea"
                data-id="<?= $tarea['id_tarea'] ?>"
                style="cursor:pointer; border:1px solid #333; border-radius:8px; padding:10px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h5><?= htmlspecialchars($tarea['titulo']) ?></h5>
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
                    <?php if ($fecha !== 'Sin fecha'): ?>
                        <small><?= date("l - gA", strtotime($fecha)) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

<!-- MODAL -->
<div id="modalTarea" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
     background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:9999;">
    <div style="background:#fff; padding:20px; border-radius:8px; width:300px; text-align:center;">
        <h5>¿Qué quieres hacer?</h5>
        <button id="btnVerTarea" class="btn btn-primary mt-2">Ver Tarea</button><br>
        <button id="btnVerEtiquetas" class="btn btn-info mt-2">Ver Etiquetas</button><br>
        <button id="btnCompletar" class="btn btn-success mt-2">Marcar como Completada</button><br>
        <button id="btnCerrar" class="btn btn-secondary mt-2">Cancelar</button>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const modal = document.getElementById("modalTarea");
        let tareaSeleccionada = null;

        // Al hacer clic en una tarea, abrir modal
        document.querySelectorAll(".tarea").forEach(div => {
            div.addEventListener("click", () => {
                tareaSeleccionada = div.getAttribute("data-id");
                modal.style.display = "flex";
            });
        });

        // Botones
        document.getElementById("btnVerTarea").addEventListener("click", () => {
            if (tareaSeleccionada) {
                window.location.href = "<?php echo URL_BASE ?>/pages/tareas.php?editar=" + tareaSeleccionada;
            }
        });

        document.getElementById("btnVerEtiquetas").addEventListener("click", () => {
            window.location.href = "<?php echo URL_BASE ?>/pages/etiquetas.php";
        });

        document.getElementById("btnCompletar").addEventListener("click", () => {
            if (tareaSeleccionada) {
                fetch("pages/guardar_tarea.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `completar=1&id=${tareaSeleccionada}`
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.trim() === "ok") {
                            location.reload();
                        } else {
                            console.error("Error:", data);
                        }
                    });
            }
        });

        document.getElementById("btnCerrar").addEventListener("click", () => {
            modal.style.display = "none";
        });

        // Cerrar al hacer clic fuera del modal
        modal.addEventListener("click", e => {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });
    });
</script>

<?php include("includes/footer.php"); ?>