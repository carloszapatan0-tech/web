<?php
/*
 * Archivo: pages/imprimir_cita.php
 * Genera un PDF con los detalles de una cita para el paciente.
 * Utiliza la librería Dompdf instalada con Composer.
 */

// CARGAR LIBRERÍAS Y CONFIGURACIÓN INICIAL
session_start(); // Necesitamos la sesión para verificar al usuario
include("../includes/db.php"); // Necesitamos la conexión a la BD

// Carga Dompdf y todas sus dependencias desde la carpeta 'vendor'
require_once '../vendor/autoload.php';

// Importar las clases de Dompdf que usaremos
use Dompdf\Dompdf;
use Dompdf\Options;

// ¡SEGURIDAD! PROTEGER EL ARCHIVO
// Validar que el usuario esté logueado Y sea un paciente
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'paciente') {
    die("Acceso no autorizado. Debes ser un paciente.");
}
$id_usuario_actual = $_SESSION['id_usuario'];

// Validar el ID de la cita desde la URL
if (!isset($_GET['id_cita']) || !filter_var($_GET['id_cita'], FILTER_VALIDATE_INT)) {
    die("ID de cita no válido.");
}
$id_cita_imprimir = (int)$_GET['id_cita'];

// OBTENER DATOS DE LA BD
// Hacemos una consulta para obtener los datos de la cita, el servicio y el paciente.
// Añadimos "AND c.id_usuario = ?" para asegurar
// que el paciente solo pueda imprimir SUS PROPIAS citas.
$stmt = $conn->prepare("SELECT 
                            c.id_cita, c.fecha_cita, c.hora_cita, c.estado_cita,
                            s.nombre_servicio, s.costo, s.tiempo_estimado,
                            u.nombre, u.apellido_paterno
                         FROM citas c
                         JOIN servicios s ON c.id_servicio = s.id_servicio
                         JOIN usuarios u ON c.id_usuario = u.id_usuario
                         WHERE c.id_cita = ? AND c.id_usuario = ?");
$stmt->bind_param("ii", $id_cita_imprimir, $id_usuario_actual);
$stmt->execute();
$cita = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

// Si la consulta no devuelve nada (la cita no existe o no pertenece al usuario), detenemos.
if (!$cita) {
    die("Error: Cita no encontrada o no te pertenece.");
}

// PREPARAR EL HTML QUE SE CONVERTIRÁ EN PDF
// Usamos HTML y CSS simple. Dompdf lo interpretará.
$html = "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Comprobante de Cita</title>
    <style>
        /* Estilos para el PDF */
        body { font-family: 'Helvetica', sans-serif; margin: 25px; font-size: 14px; }
        .container { border: 1px solid #DDD; padding: 20px; border-radius: 5px; }
        h1 { color: #0056b3; text-align: center; border-bottom: 2px solid #EEE; padding-bottom: 10px; margin-top: 0; }
        h3 { color: #333; }
        .info-box { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .info-box p { margin: 8px 0; }
        .info-box strong { color: #0056b3; min-width: 150px; display: inline-block; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Comprobante de Cita</h1>
        
        <h3>Datos del Paciente</h3>
        <p><strong>Nombre:</strong> " . htmlspecialchars($cita['nombre'] . ' ' . $cita['apellido_paterno']) . "</p>
        
        <div class='info-box'>
            <h3>Detalles de la Cita</h3>
            <p><strong>N° de Cita:</strong> " . $cita['id_cita'] . "</p>
            <p><strong>Servicio:</strong> " . htmlspecialchars($cita['nombre_servicio']) . "</p>
            <p><strong>Fecha:</strong> " . date("d/m/Y", strtotime($cita['fecha_cita'])) . "</p>
            <p><strong>Hora:</strong> " . date("h:i A", strtotime($cita['hora_cita'])) . "</p>
            <p><strong>Estado:</strong> " . htmlspecialchars($cita['estado_cita']) . "</p>
        </div>

        <div class='info-box' style='margin-top: 15px;'>
            <h3>Detalles del Servicio</h3>
            <p><strong>Tiempo Estimado:</strong> " . htmlspecialchars($cita['tiempo_estimado'] ?? 'N/A') . "</p>
            <p><strong>Costo Aproximado:</strong> " . ($cita['costo'] ? '$' . number_format($cita['costo'], 2) . ' MXN' : 'N/A') . "</p>
        </div>

        <p class='footer'>
            Sistema de Consultorio Dental - " . date("Y") . "
        </p>
    </div>
</body>
</html>
";

// INICIALIZAR DOMPDF Y GENERAR EL PDF
// Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true); // Habilitar parser HTML5
$options->set('defaultFont', 'Helvetica'); // Fuente por defecto

// Instanciar Dompdf con las opciones
$dompdf = new Dompdf($options);

// Cargar el HTML que creamos en la variable $html
$dompdf->loadHtml($html);

// Definir el tamaño y orientación del papel
$dompdf->setPaper('A4', 'portrait'); // Tamaño A4, orientación vertical

// Renderizar (dibujar) el HTML como PDF
$dompdf->render();

// ENVIAR EL PDF AL NAVEGADOR
// Esto hará que el PDF se muestre en la pestaña del navegador (en lugar de descargarse)
$dompdf->stream(
    "comprobante_cita_" . $id_cita_imprimir . ".pdf", // Nombre del archivo
    ["Attachment" => 0] // 0 = Mostrar en navegador (inline), 1 = Forzar descarga
);

exit(); // Terminar script
?>