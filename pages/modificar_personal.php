<?php
/*
 * Archivo: pages/modificar_personal.php
 * Formulario de admin para modificar un miembro del personal existente.
 */

include("../includes/header.php");
include("../includes/db.php");

// SEGURIDAD: Proteger página y validar ID
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}
if (!isset($_GET['id_personal']) || !filter_var($_GET['id_personal'], FILTER_VALIDATE_INT)) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ID de personal no válido. <a href='gestion_personal.php'>Volver</a>.</div></div>";
    include("../includes/footer.php");
    exit();
}
$id_personal_modificar = (int)$_GET['id_personal'];

$mensaje = "";
$upload_dir = "../img/personal/"; // Directorio de fotos

// PROCESAMIENTO DEL FORMULARIO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Recolección de Datos ---
    $nombre = trim($_POST['nombre']);
    $puesto = trim($_POST['puesto']);
    $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : NULL;
    $email = !empty($_POST['email']) ? trim($_POST['email']) : NULL;
    $semestre = !empty($_POST['semestre']) ? trim($_POST['semestre']) : NULL;
    $pregunta = !empty($_POST['pregunta_profesion']) ? trim($_POST['pregunta_profesion']) : NULL;
    
    $foto_actual = $_POST['foto_actual']; // Nombre de la foto que ya tenía
    $eliminar_foto = isset($_POST['eliminar_foto']); // Checkbox para borrar foto
    $foto_nombre_nuevo = $foto_actual; // Por defecto, mantenemos la foto actual

    // --- Validación ---
    if (empty($nombre) || (empty($puesto) && empty($semestre))) {
        $mensaje = "<div class='alert alert-danger'>El Nombre y el Puesto (o Semestre) son obligatorios.</div>";
    } else {
        // --- Lógica de Manejo de Foto (Igual a modificar_servicio.php) ---

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
            // Borrar la foto anterior (si existía)
            if (!$eliminar_foto && !empty($foto_actual) && file_exists($upload_dir . $foto_actual)) {
                unlink($upload_dir . $foto_actual);
            }

            // Validar y mover la nueva foto
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_info = pathinfo($_FILES['nueva_foto']['name']);
            $extension = strtolower($file_info['extension']);

            if (in_array($extension, $allowed_types)) {
                $foto_nombre_nuevo = uniqid('personal_', true) . '.' . $extension;
                $target_file = $upload_dir . $foto_nombre_nuevo;
                if (move_uploaded_file($_FILES['nueva_foto']['tmp_name'], $target_file)) {
                    $mensaje .= "<div class='alert alert-info'>Nueva foto subida correctamente.</div>";
                } else {
                    $mensaje .= "<div class='alert alert-danger'>Error al subir la nueva imagen.</div>";
                    $foto_nombre_nuevo = $foto_actual; // Revertir si falla la subida
                }
            } else {
                $mensaje .= "<div class='alert alert-warning'>Tipo de archivo no permitido.</div>";
                $foto_nombre_nuevo = $foto_actual; // Revertir si tipo no válido
            }
        }

        // --- Actualización en la Base de Datos ---
        $stmt_update = $conn->prepare("UPDATE personal SET
            nombre = ?,
            puesto = ?,
            fecha_nacimiento = ?,
            email = ?,
            semestre = ?,
            pregunta_profesion = ?,
            foto_url = ?
            WHERE id_personal = ?");

        $stmt_update->bind_param("sssssssi",
            $nombre, $puesto, $fecha_nacimiento, $email,
            $semestre, $pregunta, $foto_nombre_nuevo,
            $id_personal_modificar
        );

        if ($stmt_update->execute()) {
            if(empty($mensaje) || strpos($mensaje, 'alert-danger') === false){
                 $mensaje = "<div class='alert alert-success'>Miembro del equipo actualizado. <a href='gestion_personal.php' class='alert-link'>Volver a la lista</a>.</div>" . $mensaje;
            }
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al actualizar: " . $stmt_update->error . "</div>";
        }
        $stmt_update->close();
    } // Fin validación nombre
} // Fin POST

// CARGAR DATOS ACTUALES DEL MIEMBRO (PARA MOSTRAR EN EL FORMULARIO)
$stmt_load = $conn->prepare("SELECT * FROM personal WHERE id_personal = ?");
$stmt_load->bind_param("i", $id_personal_modificar);
$stmt_load->execute();
$persona_actual = $stmt_load->get_result()->fetch_assoc();
$stmt_load->close();

if (!$persona_actual) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>No se encontró al miembro del equipo. <a href='gestion_personal.php'>Volver</a>.</div></div>";
    include("../includes/footer.php");
    exit();
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card auth-card">
            <div class="card-body">
                <h2 class="text-center mb-4">Modificar Miembro del Equipo</h2>
                
                <a href="gestion_personal.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Volver a la lista</a>

                <?php echo $mensaje; ?>

                <form action="modificar_personal.php?id_personal=<?php echo $id_personal_modificar; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="foto_actual" value="<?php echo htmlspecialchars($persona_actual['foto_url'] ?? ''); ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required
                                   value="<?php echo htmlspecialchars($persona_actual['nombre']); ?>">
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="puesto" class="form-label">Puesto (Ej: Odontóloga Principal)</label>
                            <input type="text" class="form-control" id="puesto" name="puesto"
                                   value="<?php echo htmlspecialchars($persona_actual['puesto'] ?? ''); ?>">
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                   value="<?php echo htmlspecialchars($persona_actual['fecha_nacimiento'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                             <label for="email" class="form-label">Email</label>
                             <input type="email" class="form-control" id="email" name="email" placeholder="ejemplo@correo.com"
                                    value="<?php echo htmlspecialchars($persona_actual['email'] ?? ''); ?>">
                        </div>
                     </div>
                     <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="semestre" class="form-label">Semestre (Si es estudiante)</label>
                             <input type="text" class="form-control" id="semestre" name="semestre" placeholder="Ej: 8vo Semestre"
                                    value="<?php echo htmlspecialchars($persona_actual['semestre'] ?? ''); ?>">
                        </div>
                     </div>
                     <div class="mb-3">
                        <label for="pregunta_profesion" class="form-label">¿Por qué elegiste la profesión?</label>
                        <textarea class="form-control" id="pregunta_profesion" name="pregunta_profesion" rows="3"><?php echo htmlspecialchars($persona_actual['pregunta_profesion'] ?? ''); ?></textarea>
                     </div>

                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Foto Actual:</label>
                        <?php if ($persona_actual['foto_url']): ?>
                            <div class="mb-2">
                                <img src="<?php echo $base_url . 'img/personal/' . htmlspecialchars($persona_actual['foto_url']); ?>"
                                     alt="Foto actual" style="max-width: 100px; max-height: 100px; border-radius: 50%; object-fit: cover;">
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
                         <div class="form-text">Si subes una nueva, reemplazará la actual (si existe).</div>
                     </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="gestion_personal.php" class="btn btn-secondary">Cancelar</a>
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