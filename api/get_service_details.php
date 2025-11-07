<?php
/*
 * Archivo: api/get_service_details.php
 * API simple para obtener los detalles de un servicio por su ID.
 * Devuelve los datos en formato JSON para ser usados por JavaScript (fetch).
 */

// Incluir solo la conexión a la BD (no necesitamos header/footer aquí)
// Usamos '../' para subir un nivel desde 'api' a la raíz
include("../includes/db.php");

// INDICAR QUE LA RESPUESTA ES JSON
// Esto le dice al navegador (y al fetch de JS) que espere datos en formato JSON.
header('Content-Type: application/json; charset=utf-8'); // Añadimos charset=utf-8

// VALIDAR EL ID RECIBIDO POR GET
// Comprobamos si se envió el parámetro 'id' en la URL y si es un número entero.
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    // Si no es válido, devolvemos un objeto JSON con un mensaje de error
    // y detenemos el script con http_response_code para indicar un error de cliente.
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID de servicio no válido o faltante.']);
    exit();
}
$id_servicio = (int)$_GET['id'];

// PREPARAR Y EJECUTAR LA CONSULTA SEGURA
// Buscamos el servicio específico por su ID.
$stmt = $conn->prepare("SELECT nombre_servicio, descripcion_breve, costo, tiempo_estimado, foto_url
                         FROM servicios
                         WHERE id_servicio = ?");
$stmt->bind_param("i", $id_servicio); // 'i' = integer
$stmt->execute();
$result = $stmt->get_result(); // Obtenemos el resultado
$servicio = $result->fetch_assoc(); // Extraemos la fila (o null si no se encontró)

// CERRAR CONEXIÓN Y CONSULTA
$stmt->close();
$conn->close();

// DEVOLVER LA RESPUESTA JSON
if ($servicio) {
    // Si se encontró el servicio ($servicio no es null),
    // lo convertimos a formato JSON y lo enviamos como respuesta.
    echo json_encode($servicio);
} else {
    // Si no se encontró (ID no existe en la BD),
    // devolvemos un error JSON con código 404 (Not Found).
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Servicio no encontrado.']);
}

exit(); // Asegura que el script termine aquí.
?>