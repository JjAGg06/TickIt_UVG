<?php 
include("includes/header.php");
include("includes/slideshow.php");

if (session_status() === PHP_SESSION_NONE) session_start();
include("config/conexion.php");

if (!isset($_SESSION['usuario_id'])) {
    echo "<p>Por favor inicia sesión para ver tus notificaciones.</p>";
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$hoy = date("Y-m-d");
$maniana = date("Y-m-d", strtotime("+1 day"));

// ====================
// 1. TAREAS PENDIENTES (mostrar todas las pendientes)
// ====================
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

$notificaciones = [];
while ($row = $result->fetch_assoc()) {
    // marcar visual si es próxima/atrasada (opcional)
    $row['proxima'] = ($row['fecha_vencimiento'] === $hoy || $row['fecha_vencimiento'] === $maniana || (!empty($row['fecha_vencimiento']) && $row['fecha_vencimiento'] < $hoy));
    $notificaciones[] = $row;
}

// ====================
// 2. RECORDATORIOS NO ENVIADOS (solo enviados = 0)
// ====================
$sql_recordatorios = "
    SELECT r.id_recordatorio, r.fecha_hora, r.metodo, t.id_tarea, t.titulo, r.enviado,
           GROUP_CONCAT(e.nombre, '|', e.color SEPARATOR '||') as etiquetas
    FROM recordatorios r
    INNER JOIN tareas t ON r.id_tarea = t.id_tarea
    LEFT JOIN tareas_etiquetas te ON t.id_tarea = te.id_tarea
    LEFT JOIN etiquetas e ON te.id_etiqueta = e.id_etiqueta
    WHERE t.id_usuario = ? AND r.enviado = 0
    GROUP BY r.id_recordatorio
    ORDER BY r.fecha_hora ASC
";
$stmt_rec = $conn->prepare($sql_recordatorios);
$stmt_rec->bind_param("i", $usuario_id);
$stmt_rec->execute();
$res_rec = $stmt_rec->get_result();
$recordatorios = $res_rec->fetch_all(MYSQLI_ASSOC);
?>

<link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/index.css" />

<div class="container mt-4">
    <h3>Notificaciones</h3>
    <hr>

    <?php if (empty($notificaciones) && empty($recordatorios)): ?>
        <p>No tienes notificaciones pendientes 🎉</p>
    <?php endif; ?>

    <!-- ======================= -->
    <!-- 🔔 RECORDATORIOS NUEVOS -->
    <!-- ======================= -->
    <?php foreach ($recordatorios as $rec): ?>
        <div class="tarea no-visto recordatorio"
             data-tipo="recordatorio"
             data-id-recordatorio="<?= $rec['id_recordatorio'] ?>"
             data-id-tarea="<?= $rec['id_tarea'] ?>"
             style="cursor:pointer; border:1px solid #333; border-radius:8px; padding:10px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h5><?= htmlspecialchars($rec['titulo']) ?></h5>
                <div>
                    <?php
                    if (!empty($rec['etiquetas'])) {
                        $etiquetas = explode('||', $rec['etiquetas']);
                        foreach ($etiquetas as $etq) {
                            list($nombre, $color) = explode('|', $etq);
                            echo "<span style='display:inline-block; padding:2px 6px; margin:2px; border-radius:6px; background:$color; color:#fff; font-size:0.85rem;'>"
                                 . htmlspecialchars($nombre) . "</span>";
                        }
                    }
                    ?>
                </div>
            </div>
            <div style="text-align:right;">
                <p style="margin:0; font-weight:bold;">Recordatorio</p>
                <small><?= date("d/m/Y H:i", strtotime($rec['fecha_hora'])) ?></small>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- ======================= -->
    <!-- 📌 TAREAS PENDIENTES -->
    <!-- ======================= -->
    <?php foreach ($notificaciones as $tarea): ?>
        <div class="tarea no-visto tarea-item"
             data-tipo="tarea"
             data-id-tarea="<?= $tarea['id_tarea'] ?>"
             style="cursor:pointer; border:1px solid #333; border-radius:8px; padding:10px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h5><?= htmlspecialchars($tarea['titulo']) ?></h5>
                <div>
                    <?php
                    if (!empty($tarea['etiquetas'])) {
                        $etiquetas = explode('||', $tarea['etiquetas']);
                        foreach ($etiquetas as $etq) {
                            list($nombre, $color) = explode('|', $etq);
                            echo "<span style='display:inline-block; padding:2px 6px; margin:2px; border-radius:6px; background:$color; color:#fff; font-size:0.85rem;'>"
                                 . htmlspecialchars($nombre) . "</span>";
                        }
                    }
                    ?>
                </div>
            </div>
            <div style="text-align:right;">
                <p style="margin:0; font-weight:bold;"><?= ucfirst($tarea['estado']) ?></p>
                <small><?= !empty($tarea['fecha_vencimiento']) ? date("d/m/Y", strtotime($tarea['fecha_vencimiento'])) : "Sin fecha" ?></small>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- MODAL: botones mantienen la misma funcionalidad -->
<div id="modalTarea" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
     background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:9999;">
    <div style="background:#fff; padding:20px; border-radius:8px; width:320px; text-align:center;">
        <h5>¿Qué quieres hacer?</h5>
        <button id="btnVerTarea" class="btn btn-primary mt-2">Ver Tarea</button><br>
        <button id="btnVerEtiquetas" class="btn btn-info mt-2">Ver Etiquetas</button><br>
        <button id="btnCompletar" class="btn btn-success mt-2">Marcar como Completada</button><br>
        <button id="btnMarcarLeido" class="btn btn-warning mt-2">Marcar como Leído</button><br>
        <button id="btnCerrar" class="btn btn-secondary mt-2">Cancelar</button>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("modalTarea");
    let selectedTipo = null;
    let selectedRecordatorioId = null;
    let selectedTareaId = null;

    // Abrir modal y guardar ids correctos
    document.querySelectorAll(".tarea").forEach(div => {
        div.addEventListener("click", () => {
            selectedTipo = div.dataset.tipo || null;
            selectedRecordatorioId = div.dataset.idRecordatorio || null;
            selectedTareaId = div.dataset.idTarea || null;
            modal.style.display = "flex";
        });
    });

    // Ver tarea -> abre el detalle (usa id_tarea)
    document.getElementById("btnVerTarea").addEventListener("click", () => {
        const idToOpen = selectedTareaId;
        if (idToOpen) {
            // opcional: marcar recordatorio como visto antes de redirigir (no obligatorio)
            window.location.href = "<?php echo URL_BASE ?>/pages/tareas.php?editar=" + idToOpen;
        }
    });

    // Ver etiquetas
    document.getElementById("btnVerEtiquetas").addEventListener("click", () => {
        window.location.href = "<?php echo URL_BASE ?>/pages/etiquetas.php";
    });

    // Marcar como completada (solo si hay tarea)
    document.getElementById("btnCompletar").addEventListener("click", () => {
        const idToComplete = selectedTareaId;
        if (!idToComplete) return;
        fetch("pages/guardar_tarea.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `completar=1&id=${idToComplete}`
        }).then(res => res.text()).then(data => {
            if (data.trim() === "ok") location.reload();
            else alert("Error completando: " + data);
        }).catch(err => alert("Error red: " + err));
    });

    // Marcar como leído: enviamos tipo e id (recordatorio o tarea)
    document.getElementById("btnMarcarLeido").addEventListener("click", () => {
        if (!selectedTipo) return alert("No hay notificación seleccionada.");

        let body;
        if (selectedTipo === 'recordatorio' && selectedRecordatorioId) {
            body = `id=${encodeURIComponent(selectedRecordatorioId)}&tipo=recordatorio`;
        } else if (selectedTipo === 'tarea' && selectedTareaId) {
            body = `id=${encodeURIComponent(selectedTareaId)}&tipo=tarea`;
        } else {
            return alert("ID inválido para marcar como leído.");
        }

        // deshabilitar botón momentáneamente
        const btn = document.getElementById("btnMarcarLeido");
        btn.disabled = true;

        fetch("pages/marcar_visto.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: body
        }).then(res => res.text()).then(data => {
            btn.disabled = false;
            data = data.trim();
            if (data === "ok") {
                if (selectedTipo === 'recordatorio' && selectedRecordatorioId) {
                    document.querySelector(`.tarea[data-id-recordatorio='${selectedRecordatorioId}']`)?.remove();
                }
                if (selectedTipo === 'tarea' && selectedTareaId) {
                    document.querySelectorAll(`.tarea[data-id-tarea='${selectedTareaId}']`).forEach(el => el.remove());
                }
                modal.style.display = "none";
            } else {
                alert("Error al marcar como leído: " + data);
                console.error("marcar_visto response:", data);
            }
        }).catch(err => {
            btn.disabled = false;
            alert("Error de red: " + err);
            console.error(err);
        });
    });

    // Cerrar modal
    document.getElementById("btnCerrar").addEventListener("click", () => modal.style.display = "none");
    modal.addEventListener("click", e => { if (e.target === modal) modal.style.display = "none"; });
});
</script>
