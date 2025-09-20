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

// Obtener tema del usuario
$sqlTema = "SELECT tema_preferido FROM usuario WHERE id_usuario = ?";
$stmtTema = $conn->prepare($sqlTema);
$stmtTema->bind_param("i", $usuario_id);
$stmtTema->execute();
$resTema = $stmtTema->get_result();
$userTema = $resTema->fetch_assoc();
$tema_preferido = $userTema['tema_preferido'] ?? 'claro';

$hoy = date("Y-m-d");
$maniana = date("Y-m-d", strtotime("+1 day"));

// ====================
// CREAR RECORDATORIOS AUTOMÁTICOS
// ====================
$sql_tareas_vencen = "
    SELECT id_tarea, titulo, fecha_vencimiento
    FROM tareas
    WHERE id_usuario = ? 
      AND estado = 'pendiente'
      AND DATE(fecha_vencimiento) = ?
";
$stmt_tareas = $conn->prepare($sql_tareas_vencen);
$stmt_tareas->bind_param("is", $usuario_id, $maniana);
$stmt_tareas->execute();
$res_tareas = $stmt_tareas->get_result();

while ($tarea = $res_tareas->fetch_assoc()) {
    $id_tarea = $tarea['id_tarea'];

    $sql_check = "SELECT id_recordatorio FROM recordatorios 
                  WHERE id_tarea=? AND metodo='notificacion' 
                    AND DATE(fecha_hora)=? LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("is", $id_tarea, $maniana);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows === 0) {
        $sql_insert = "INSERT INTO recordatorios (id_tarea, fecha_hora, metodo, enviado) 
                       VALUES (?, ?, 'notificacion', 0)";
        $stmt_insert = $conn->prepare($sql_insert);
        $fecha_hora = $maniana . " 09:00:00";
        $stmt_insert->bind_param("is", $id_tarea, $fecha_hora);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    $stmt_check->close();
}

// ====================
// RECORDATORIOS ACTUALES
// ====================
$sql_actuales = "
    SELECT r.id_recordatorio, r.fecha_hora, r.metodo, r.enviado,
           t.id_tarea, t.titulo, t.descripcion, t.estado,
           GROUP_CONCAT(e.nombre, '|', e.color SEPARATOR '||') as etiquetas
    FROM recordatorios r
    INNER JOIN tareas t ON r.id_tarea = t.id_tarea
    LEFT JOIN tareas_etiquetas te ON t.id_tarea = te.id_tarea
    LEFT JOIN etiquetas e ON te.id_etiqueta = e.id_etiqueta
    WHERE t.id_usuario = ? AND r.enviado = 0 AND t.estado='pendiente'
    GROUP BY r.id_recordatorio
    ORDER BY r.fecha_hora ASC
";
$stmt = $conn->prepare($sql_actuales);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res_actuales = $stmt->get_result();
$recordatorios_actuales = $res_actuales->fetch_all(MYSQLI_ASSOC);

// ====================
// RECORDATORIOS VISTOS
// ====================
$sql_vistos = "
    SELECT r.id_recordatorio, r.fecha_hora, r.metodo, r.enviado,
           t.id_tarea, t.titulo, t.descripcion, t.estado,
           GROUP_CONCAT(e.nombre, '|', e.color SEPARATOR '||') as etiquetas
    FROM recordatorios r
    INNER JOIN tareas t ON r.id_tarea = t.id_tarea
    LEFT JOIN tareas_etiquetas te ON t.id_tarea = te.id_tarea
    LEFT JOIN etiquetas e ON te.id_etiqueta = e.id_etiqueta
    WHERE t.id_usuario = ? AND r.enviado = 1
    GROUP BY r.id_recordatorio
    ORDER BY r.fecha_hora DESC
";
$stmt = $conn->prepare($sql_vistos);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res_vistos = $stmt->get_result();
$recordatorios_vistos = $res_vistos->fetch_all(MYSQLI_ASSOC);
?>

<link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/index.css" />

<div class="container mt-4">
    <h3>Notificaciones</h3>
    <hr class="linea">

    <!-- Recordatorios actuales -->
    <h4>Recordatorios actuales</h4>
    <?php if (empty($recordatorios_actuales)): ?>
        <p>No tienes recordatorios pendientes</p>
    <?php else: ?>
        <?php foreach ($recordatorios_actuales as $rec): ?>
            <div class="tarea no-visto recordatorio"
                data-tipo="recordatorio"
                data-id-recordatorio="<?= $rec['id_recordatorio'] ?>"
                data-id-tarea="<?= $rec['id_tarea'] ?>"
                data-enviado="<?= $rec['enviado'] ?>"
                data-estado="<?= $rec['estado'] ?>">
                <div>
                    <h5><?= htmlspecialchars($rec['titulo']) ?></h5>
                    <div>
                        <?php
                        if (!empty($rec['etiquetas'])) {
                            $etiquetas = explode('||', $rec['etiquetas']);
                            foreach ($etiquetas as $etq) {
                                list($nombre, $color) = explode('|', $etq);
                                echo "<span style='padding:2px 6px; margin:2px; border-radius:6px; background:$color; color:#fff; font-size:0.85rem;'>"
                                    . htmlspecialchars($nombre) . "</span>";
                            }
                        }
                        ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0; font-weight:bold;">Pendiente</p>
                    <small><?= date("d/m/Y H:i", strtotime($rec['fecha_hora'])) ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Recordatorios vistos -->
    <hr class="linea">
    <h4>Recordatorios vistos</h4>
    <?php if (empty($recordatorios_vistos)): ?>
        <p>No tienes recordatorios vistos aún.</p>
    <?php else: ?>
        <?php foreach ($recordatorios_vistos as $rec): ?>
            <div class="tarea visto recordatorio"
                data-tipo="recordatorio"
                data-id-recordatorio="<?= $rec['id_recordatorio'] ?>"
                data-id-tarea="<?= $rec['id_tarea'] ?>"
                data-enviado="<?= $rec['enviado'] ?>"
                data-estado="<?= $rec['estado'] ?>">
                <div>
                    <h5><?= htmlspecialchars($rec['titulo']) ?></h5>
                    <p><?= htmlspecialchars($rec['descripcion']) ?></p>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0; font-weight:bold;">Visto</p>
                    <small><?= date("d/m/Y H:i", strtotime($rec['fecha_hora'])) ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- MODAL -->
<div id="modalTarea" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
     background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:9999;">
    <div class="modal-content">
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
        const btnCompletar = document.getElementById("btnCompletar");
        const btnMarcarLeido = document.getElementById("btnMarcarLeido");
        let selectedTipo = null;
        let selectedRecordatorioId = null;
        let selectedTareaId = null;
        let seleccionadoEnviado = null;
        let seleccionadoEstado = null;

        document.querySelectorAll(".tarea").forEach(div => {
            div.addEventListener("click", () => {
                selectedTipo = div.dataset.tipo || null;
                selectedRecordatorioId = div.dataset.idRecordatorio || null;
                selectedTareaId = div.dataset.idTarea || null;
                seleccionadoEnviado = div.dataset.enviado || "0";
                seleccionadoEstado = div.dataset.estado || "pendiente";

                btnMarcarLeido.style.display = (selectedTipo === "recordatorio" && seleccionadoEnviado === "0") ? "block" : "none";
                btnCompletar.style.display = (seleccionadoEstado === "pendiente") ? "block" : "none";

                modal.style.display = "flex";
            });
        });

        document.getElementById("btnVerTarea").addEventListener("click", () => {
            if (selectedTareaId) {
                window.location.href = "<?php echo URL_BASE ?>/pages/tareas.php?editar=" + selectedTareaId;
            }
        });

        document.getElementById("btnVerEtiquetas").addEventListener("click", () => {
            window.location.href = "<?php echo URL_BASE ?>/pages/etiquetas.php";
        });

        btnCompletar.addEventListener("click", () => {
            if (!selectedTareaId) return;
            fetch("pages/guardar_tarea.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `completar=1&id=${selectedTareaId}`
            }).then(res => res.text()).then(data => {
                if (data.trim() === "ok") location.reload();
                else alert("Error completando: " + data);
            });
        });

        btnMarcarLeido.addEventListener("click", () => {
            if (!selectedTipo) return alert("No hay notificación seleccionada.");

            let body;
            if (selectedTipo === 'recordatorio' && selectedRecordatorioId) {
                body = `id=${encodeURIComponent(selectedRecordatorioId)}&tipo=recordatorio`;
            } else {
                return alert("ID inválido para marcar como leído.");
            }

            fetch("pages/marcar_visto.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: body
            }).then(res => res.text()).then(data => {
                if (data.trim() === "ok") location.reload();
                else alert("Error al marcar como leído: " + data);
            });
        });

        document.getElementById("btnCerrar").addEventListener("click", () => modal.style.display = "none");
        modal.addEventListener("click", e => {
            if (e.target === modal) modal.style.display = "none";
        });
    });
</script>