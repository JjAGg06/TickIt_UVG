<?php
if (!defined('NOMBRE_SITIO')) {
    include_once(__DIR__ . '/../config/config.php');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../config/conexion.php');

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . URL_BASE . "/iniciosesion.php");
    exit();
}

// Validar tarea
if (!isset($_GET['id'])) {
    die("No se especificó la tarea.");
}

$id_tarea = intval($_GET['id']);
$id_usuario = $_SESSION['usuario_id'];

// Consultar datos de la tarea
$sql = "SELECT t.titulo, t.descripcion, t.estado, t.prioridad, t.fecha_creacion, t.fecha_vencimiento, 
               e.nombre AS etiqueta
        FROM tareas t
        LEFT JOIN tareas_etiquetas te ON t.id_tarea = te.id_tarea
        LEFT JOIN etiquetas e ON te.id_etiqueta = e.id_etiqueta
        WHERE t.id_tarea = ? AND t.id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_tarea, $id_usuario);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("No se encontró la tarea.");
}

$tarea = $res->fetch_assoc();

// ============================
// Generar PDF con fpdf
// ============================
require(__DIR__ . '/../fpdf/fpdf.php'); // asegúrate de tener la librería en /lib/

$pdf = new fpdf();
$pdf->AddPage();

// Logo
$logoPath = __DIR__ . '/../assets/img/TIUVG.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 8, 30);
}
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(80);
$pdf->Cell(30, 10, 'TickIt UVG - Detalle de Tarea', 0, 1, 'C');
$pdf->Ln(20);

// Cabecera de tabla
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Campo', 1, 0, 'C');
$pdf->Cell(130, 10, 'Valor', 1, 1, 'C');

$pdf->SetFont('Arial', '', 12);

// Datos
$pdf->Cell(50, 10, 'Titulo', 1);
$pdf->Cell(130, 10, utf8_decode($tarea['titulo']), 1, 1);

$pdf->Cell(50, 10, 'Descripcion', 1);
$pdf->MultiCell(130, 10, utf8_decode($tarea['descripcion']), 1);

$pdf->Cell(50, 10, 'Estado', 1);
$pdf->Cell(130, 10, utf8_decode($tarea['estado']), 1, 1);

$pdf->Cell(50, 10, 'Prioridad', 1);
$pdf->Cell(130, 10, utf8_decode($tarea['prioridad']), 1, 1);

$pdf->Cell(50, 10, 'Fecha Creacion', 1);
$pdf->Cell(130, 10, $tarea['fecha_creacion'], 1, 1);

$pdf->Cell(50, 10, 'Fecha Vencimiento', 1);
$pdf->Cell(130, 10, $tarea['fecha_vencimiento'], 1, 1);

$pdf->Cell(50, 10, 'Etiqueta', 1);
$pdf->Cell(130, 10, utf8_decode($tarea['etiqueta'] ?? 'Sin etiqueta'), 1, 1);

// Descargar PDF automáticamente
$pdf->Output('D', 'tarea_' . $id_tarea . '.pdf');
exit;
?>
