<?php
/*
 * Archivo: pages/imprimir_expediente.php
 * Genera un PDF con el expediente médico de una cita.
 * Solo accesible para el Administrador.
 */

session_start();
include("../includes/db.php");
require_once '../vendor/autoload.php'; // Carga Dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

// --- ¡SEGURIDAD! PROTEGER EL ARCHIVO ---
// Validar que el usuario esté logueado Y sea un Administrador
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    die("Acceso no autorizado.");
}

// Validar el ID de la cita desde la URL
if (!isset($_GET['id_cita']) || !filter_var($_GET['id_cita'], FILTER_VALIDATE_INT)) {
    die("ID de cita no válido.");
}
$id_cita_imprimir = (int)$_GET['id_cita'];

// --- OBTENER DATOS DE LA BD ---
// Esta consulta es la más completa: une 4 tablas.
// Usamos LEFT JOIN en usuarios (por si es visitante)
// y LEFT JOIN en expedientes (por si el expediente está vacío).
$stmt = $conn->prepare("SELECT 
                            c.id_cita, c.fecha_cita, c.hora_cita,
                            s.nombre_servicio,
                            u.nombre, u.apellido_paterno,
                            c.nombre_visitante,
                            e.diagnostico, e.tratamiento_realizado, e.notas_doctor
                         FROM citas c
                         JOIN servicios s ON c.id_servicio = s.id_servicio
                         LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
                         LEFT JOIN expedientes e ON c.id_cita = e.id_cita
                         WHERE c.id_cita = ?");
$stmt->bind_param("i", $id_cita_imprimir);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

// Si la cita no se encuentra
if (!$datos) {
    die("Error: Cita no encontrada.");
}

// --- PREPARAR EL HTML QUE SE CONVERTIRÁ EN PDF ---
$html = "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Expediente de Cita</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; margin: 25px; font-size: 14px; }
        .container { border: 1px solid #AAA; padding: 20px; border-radius: 5px; }
        h1 { color: #0056b3; text-align: center; border-bottom: 2px solid #EEE; padding-bottom: 10px; margin-top: 0; }
        h3 { color: #0056b3; border-bottom: 1px solid #EEE; padding-bottom: 5px; }
        .info-paciente { background-color: #f9f9f9; padding: 10px 15px; border-radius: 5px; }
        .info-paciente p { margin: 5px 0; }
        .info-paciente strong { min-width: 80px; display: inline-block; }
        .expediente-seccion { margin-top: 20px; }
        .expediente-seccion p { background-color: #fdfdfd; border: 1px solid #EEE; padding: 10px; min-height: 50px; border-radius: 3px; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Expediente Médico</h1>
        
        <div class='info-paciente'>
            <div style='float: left; width: 60%;'>
                <p><strong>Paciente:</strong> 
                " . htmlspecialchars($datos['nombre_usuario'] ? $datos['nombre'] . ' ' . $datos['apellido_paterno'] : $datos['nombre_visitante']) . "
                </p>
                <p><strong>Servicio:</strong> " . htmlspecialchars($datos['nombre_servicio']) . "</p>
            </div>
            <div style='float: right; width: 38%;'>
                <p><strong>Cita ID:</strong> " . $datos['id_cita'] . "</p>
                <p><strong>Fecha:</strong> " . date("d/m/Y", strtotime($datos['fecha_cita'])) . "</p>
            </div>
            <div style='clear: both;'></div>
        </div>

        <div class='expediente-seccion'>
            <h3>Diagnóstico</h3>
            <p>" . nl2br(htmlspecialchars($datos['diagnostico'] ?? 'N/A')) . "</p>
        </div>

        <div class='expediente-seccion'>
            <h3>Tratamiento Realizado</h3>
            <p>" . nl2br(htmlspecialchars($datos['tratamiento_realizado'] ?? 'N/A')) . "</p>
        </div>
        
        <div class='expediente-seccion'>
            <h3>Notas Adicionales / Receta</h3>
            <p>" . nl2br(htmlspecialchars($datos['notas_doctor'] ?? 'N/A')) . "</p>
        </div>

        <p class='footer'>
            Documento generado por el Sistema de Consultorio Dental.<br>
            Fecha de impresión: " . date("d/m/Y h:i A") . "
        </p>
    </div>
</body>
</html>
";

// --- INICIALIZAR DOMPDF Y GENERAR EL PDF ---
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Helvetica');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// --- ENVIAR EL PDF AL NAVEGADOR ---
$dompdf->stream(
    "expediente_cita_" . $id_cita_imprimir . ".pdf", // Nombre del archivo
    ["Attachment" => 0] // 0 = Mostrar en navegador
);

exit();
?>