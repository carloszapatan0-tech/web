<?php
/*
 * Archivo: pages/modificar_servicio.php
 * Formulario de admin para modificar un servicio existente.
 */

include("../includes/header.php");
include("../includes/db.php");

// SEGURIDAD: Proteger página y validar ID de servicio
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}
if (!isset($_GET['id_servicio']) || !filter_var($_GET['id_servicio'], FILTER_VALIDATE_INT)) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ID de servicio no válido. <a href='gestion_servicios.php'>Volver</a>.</div></div>";
    include("../includes/footer.php");
    exit();
}
$id_servicio_modificar = (int)$_GET['id_servicio'];

$mensaje = "";
$upload_dir = "../img/servicios/"; // Directorio de fotos de servicios

// PROCESAMIENTO DEL FORMULARIO (SI SE ENVÍA - MÉTODO POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Recolección de Datos ---
    $nombre = trim($_POST['nombre_servicio']);
    $descripcion = trim($_POST['descripcion_breve']);
    $costo = !empty($_POST['costo']) ? $_POST['costo'] : NULL;
    $tiempo = trim($_POST['tiempo_estimado']);
    $foto_actual = $_POST['foto_actual']; // Nombre de la foto que ya tenía (si tenía)
    $eliminar_foto = isset($_POST['eliminar_foto']); // Checkbox para borrar foto
    $foto_nombre_nuevo = $foto_actual; // Por defecto, mantenemos la foto actual

    // --- Validación ---
    if (empty($nombre)) {
        $mensaje = "<div class='alert alert-danger'>El nombre del servicio es obligatorio.</div>";
    } else {
        // --- Lógica de Manejo de Foto ---

        // Si se marcó "Eliminar foto actual"
        if ($eliminar_foto && !empty($foto_actual)) {
            if (file_exists($upload_dir . $foto_actual)) {
                unlink($upload_dir . $foto_actual); // Borra el archivo físico
            }
            $foto_nombre_nuevo = NULL; // Borra el nombre de la BD
            $mensaje .= "<div class='alert alert-info'>Foto actual eliminada.</div>";
        }

        // Si se subió una NUEVA foto
        if (isset($_FILES['nueva_foto']) && $_FILES['nueva_foto']['error'] == 0) {
            // Borrar la foto anterior (si existía y no se marcó eliminar)
            if (!$eliminar_foto && !empty($foto_actual) && file_exists($upload_dir . $foto_actual)) {
                unlink($upload_dir . $foto_actual);
            }

            // Validar y mover la nueva foto (similar a 'añadir')
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $file_info = pathinfo($_FILES['nueva_foto']['name']);
            $extension = strtolower($file_info['extension']);

            if (in_array($extension, $allowed_types)) {
                $foto_nombre_nuevo = uniqid('servicio_', true) . '.' . $extension;
                $target_file = $upload_dir . $foto_nombre_nuevo;
                if (!move_uploaded_file($_FILES['nueva_foto']['tmp_name'], $target_file)) {
                    $mensaje .= "<div class='alert alert-danger'>Error al subir la nueva imagen. Se conservará la anterior si existía.</div>";
                    $foto_nombre_nuevo = $foto_actual; // Revertir al nombre anterior si falla la subida
                } else {
                     $mensaje .= "<div class='alert alert-info'>Nueva foto subida correctamente.</div>";
                }
            } else {
                $mensaje .= "<div class='alert alert-warning'>Tipo de archivo no permitido para la nueva foto. Se conservará la anterior si existía.</div>";
                 $foto_nombre_nuevo = $foto_actual; // Revertir si el tipo no es válido
            }
        }
        // Si no se marcó eliminar Y no se subió nueva, $foto_nombre_nuevo sigue siendo $foto_actual.

        // --- Actualización en la Base de Datos ---
        // Se ejecuta solo si la validación básica del nombre pasó
        $stmt_update = $conn->prepare("UPDATE servicios SET
            nombre_servicio = ?,
            descripcion_breve = ?,
            costo = ?,
            tiempo_estimado = ?,
            foto_url = ?
            WHERE id_servicio = ?");

        // bind_param: s = string, d = double (decimal), i = integer
        $stmt_update->bind_param("ssdssi",
            $nombre, $descripcion, $costo, $tiempo,
            $foto_nombre_nuevo, // El nombre final de la foto (puede ser NULL, nuevo o el actual)
            $id_servicio_modificar
        );

        if ($stmt_update->execute()) {
             // Si $mensaje ya tenía algo (de la foto), añadimos el éxito. Si no, lo creamos.
            if(empty($mensaje) || strpos($mensaje, 'alert-danger') === false){
                 $mensaje = "<div class='alert alert-success'>Servicio actualizado correctamente. <a href='gestion_servicios.php' class='alert-link'>Volver a la lista</a>.</div>" . $mensaje; // Añade info de foto al final
            }
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al actualizar el servicio: " . $stmt_update->error . "</div>";
        }
        $stmt_update->close();
    } // Fin validación nombre
} // Fin POST

// CARGAR DATOS ACTUALES DEL SERVICIO (PARA MOSTRAR EN EL FORMULARIO)
$stmt_load = $conn->prepare("SELECT * FROM servicios WHERE id_servicio = ?");
$stmt_load->bind_param("i", $id_servicio_modificar);
$stmt_load->execute();
$servicio_actual = $stmt_load->get_result()->fetch_assoc();
$stmt_load->close();

// Si no se encontró el servicio
if (!$servicio_actual) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>No se encontró el servicio solicitado. <a href='gestion_servicios.php'>Volver</a>.</div></div>";
    include("../includes/footer.php");
    exit();
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card auth-card">
            <div class="card-body">
                <h2 class="text-center mb-4">Modificar Servicio (ID: <?php echo $id_servicio_modificar; ?>)</h2>

                <a href="gestion_servicios.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Volver a la lista</a>

                <?php echo $mensaje; ?>

                <form action="modificar_servicio.php?id_servicio=<?php echo $id_servicio_modificar; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="foto_actual" value="<?php echo htmlspecialchars($servicio_actual['foto_url'] ?? ''); ?>">

                    <div class="mb-3">
                        <label for="nombre_servicio" class="form-label">Nombre Servicio <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre_servicio" name="nombre_servicio" required
                               value="<?php echo htmlspecialchars($servicio_actual['nombre_servicio']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="descripcion_breve" class="form-label">Descripción Breve</label>
                        <textarea class="form-control" id="descripcion_breve" name="descripcion_breve" rows="2"><?php echo htmlspecialchars($servicio_actual['descripcion_breve'] ?? ''); ?></textarea>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="costo" class="form-label">Costo Aprox. (MXN)</label>
                            <input type="number" step="0.01" class="form-control" id="costo" name="costo" placeholder="Ej: 800.00"
                                   value="<?php echo htmlspecialchars($servicio_actual['costo'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                             <label for="tiempo_estimado" class="form-label">Tiempo Estimado</label>
                             <input type="text" class="form-control" id="tiempo_estimado" name="tiempo_estimado" placeholder="Ej: 45 minutos"
                                    value="<?php echo htmlspecialchars($servicio_actual['tiempo_estimado'] ?? ''); ?>">
                        </div>
                     </div>

                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Foto Actual:</label>
                        <?php if ($servicio_actual['foto_url']): ?>
                            <div class="mb-2">
                                <img src="<?php echo $base_url . 'img/servicios/' . htmlspecialchars($servicio_actual['foto_url']); ?>"
                                     alt="Foto actual" style="max-width: 100px; max-height: 100px;">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" value="1" id="eliminar_foto" name="eliminar_foto">
                                    <label class="form-check-label" for="eliminar_foto">
                                        Eliminar foto actual (se borrará al guardar)
                                    </label>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No hay foto asignada.</p>
                        <?php endif; ?>
                    </div>
                     <div class="mb-3">
                         <label for="nueva_foto" class="form-label">Subir Nueva Foto (Opcional):</label>
                         <input class="form-control" type="file" id="nueva_foto" name="nueva_foto">
                         <div class="form-text">Si subes una nueva, reemplazará la actual (si existe). Formatos: JPG, JPEG, PNG, GIF.</div>
                     </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="gestion_servicios.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
if(isset($conn)){
    $conn->close();
}
include("../includes/footer.php");
?>