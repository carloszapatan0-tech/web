<?php
/*
 * Archivo: pages/gestion_servicios.php
 * Panel de admin para gestionar los servicios ofrecidos.
 */

include("../includes/header.php");
include("../includes/db.php");

// SEGURIDAD: Proteger página
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}

$mensaje = "";
$upload_dir = "../img/servicios/"; // Directorio donde se guardarán las fotos de servicios

// Asegurarse de que el directorio de subida exista
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// --- LÓGICA PARA AÑADIR NUEVO SERVICIO ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_service'])) {
    $nombre = trim($_POST['nombre_servicio']);
    $descripcion = trim($_POST['descripcion_breve']);
    $costo = !empty($_POST['costo']) ? $_POST['costo'] : NULL;
    $tiempo = trim($_POST['tiempo_estimado']);
    $foto_nombre = NULL; // Nombre de la foto a guardar en BD

    // Validación básica
    if (empty($nombre)) {
        $mensaje = "<div class='alert alert-danger'>El nombre del servicio es obligatorio.</div>";
    } else {
        // Manejo de la subida de imagen (si se seleccionó una)
        if (isset($_FILES['foto_servicio']) && $_FILES['foto_servicio']['error'] == 0) {
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $file_info = pathinfo($_FILES['foto_servicio']['name']);
            $extension = strtolower($file_info['extension']);

            if (in_array($extension, $allowed_types)) {
                // Crear un nombre de archivo único para evitar sobreescrituras
                $foto_nombre = uniqid('servicio_', true) . '.' . $extension;
                $target_file = $upload_dir . $foto_nombre;

                // Mover el archivo subido al directorio de destino
                if (!move_uploaded_file($_FILES['foto_servicio']['tmp_name'], $target_file)) {
                    $mensaje = "<div class='alert alert-danger'>Error al subir la imagen.</div>";
                    $foto_nombre = NULL; // No guardar nombre si falla la subida
                }
            } else {
                $mensaje = "<div class='alert alert-warning'>Tipo de archivo no permitido. Solo JPG, JPEG, PNG, GIF.</div>";
            }
        }

        // Insertar en la base de datos (solo si no hubo error grave)
        if (empty($mensaje) || strpos($mensaje, 'alert-danger') === false) {
             $stmt = $conn->prepare("INSERT INTO servicios (nombre_servicio, descripcion_breve, costo, tiempo_estimado, foto_url) VALUES (?, ?, ?, ?, ?)");
             // d = decimal, s = string
             $stmt->bind_param("ssdss", $nombre, $descripcion, $costo, $tiempo, $foto_nombre);

             if ($stmt->execute()) {
                 $mensaje = "<div class='alert alert-success'>Servicio añadido correctamente.</div>";
             } else {
                 $mensaje = "<div class='alert alert-danger'>Error al añadir el servicio: " . $stmt->error . "</div>";
                 // Si falla la inserción, intentar borrar la foto subida (si existe)
                 if ($foto_nombre && file_exists($upload_dir . $foto_nombre)) {
                     unlink($upload_dir . $foto_nombre);
                 }
             }
             $stmt->close();
        }
    }
}

// --- LÓGICA PARA ELIMINAR SERVICIO ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_service'])) {
    $id_servicio_eliminar = (int)$_POST['id_servicio'];

    // Antes de borrar de BD, obtener el nombre de la foto para borrarla del servidor
    $stmt_get_foto = $conn->prepare("SELECT foto_url FROM servicios WHERE id_servicio = ?");
    $stmt_get_foto->bind_param("i", $id_servicio_eliminar);
    $stmt_get_foto->execute();
    $foto_a_borrar = $stmt_get_foto->get_result()->fetch_assoc()['foto_url'] ?? null;
    $stmt_get_foto->close();

    // Eliminar de la base de datos
    $stmt = $conn->prepare("DELETE FROM servicios WHERE id_servicio = ?");
    $stmt->bind_param("i", $id_servicio_eliminar);

    if ($stmt->execute()) {
        $mensaje = "<div class='alert alert-success'>Servicio eliminado correctamente.</div>";
        // Si se eliminó de BD y tenía foto, borrar el archivo
        if ($foto_a_borrar && file_exists($upload_dir . $foto_a_borrar)) {
            unlink($upload_dir . $foto_a_borrar);
        }
    } else {
        // Error común: El servicio tiene citas asociadas
        if ($conn->errno == 1451) { // Error de Foreign Key
             $mensaje = "<div class='alert alert-danger'>Error: No se puede eliminar el servicio porque tiene citas asociadas. Primero elimina o reasigna esas citas.</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al eliminar el servicio: " . $stmt->error . "</div>";
        }
    }
    $stmt->close();
}

// --- OBTENER LISTA DE SERVICIOS PARA MOSTRAR ---
$servicios = $conn->query("SELECT * FROM servicios ORDER BY nombre_servicio ASC");

?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2>Gestión de Servicios</h2>
        <button class="btn btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#addServiceForm" aria-expanded="false" aria-controls="addServiceForm">
            <i class="fas fa-plus"></i> Añadir Servicio
        </button>
    </div>
    <a href="dashboard_admin.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Volver al Panel</a>

    <?php echo $mensaje; ?>

    <div class="collapse mb-4" id="addServiceForm">
        <div class="card card-body">
            <h4>Añadir Nuevo Servicio</h4>
            <form action="gestion_servicios.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="nombre_servicio" class="form-label">Nombre Servicio <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre_servicio" name="nombre_servicio" required>
                </div>
                <div class="mb-3">
                    <label for="descripcion_breve" class="form-label">Descripción Breve</label>
                    <textarea class="form-control" id="descripcion_breve" name="descripcion_breve" rows="2"></textarea>
                </div>
                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="costo" class="form-label">Costo Aprox. (MXN)</label>
                        <input type="number" step="0.01" class="form-control" id="costo" name="costo" placeholder="Ej: 800.00">
                    </div>
                    <div class="col-md-6 mb-3">
                         <label for="tiempo_estimado" class="form-label">Tiempo Estimado</label>
                         <input type="text" class="form-control" id="tiempo_estimado" name="tiempo_estimado" placeholder="Ej: 45 minutos">
                    </div>
                 </div>
                 <div class="mb-3">
                     <label for="foto_servicio" class="form-label">Foto del Servicio (Opcional)</label>
                     <input class="form-control" type="file" id="foto_servicio" name="foto_servicio">
                     <div class="form-text">Formatos permitidos: JPG, JPEG, PNG, GIF.</div>
                 </div>
                <button type="submit" name="add_service" class="btn btn-primary">Guardar Servicio</button>
            </form>
        </div>
    </div>

    <h4>Servicios Actuales</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Foto</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Costo</th>
                    <th>Tiempo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($servicios && $servicios->num_rows > 0): ?>
                    <?php while ($servicio = $servicios->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center">
                                <?php if ($servicio['foto_url']): ?>
                                    <img src="<?php echo $base_url . 'img/servicios/' . htmlspecialchars($servicio['foto_url']); ?>"
                                         alt="<?php echo htmlspecialchars($servicio['nombre_servicio']); ?>"
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-image fa-2x text-muted"></i> <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($servicio['nombre_servicio']); ?></td>
                            <td><?php echo htmlspecialchars($servicio['descripcion_breve'] ?? 'N/A'); ?></td>
                            <td><?php echo $servicio['costo'] ? '$' . number_format($servicio['costo'], 2) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($servicio['tiempo_estimado'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="modificar_servicio.php?id_servicio=<?php echo $servicio['id_servicio']; ?>" class="btn btn-primary btn-sm me-1" title="Modificar Servicio">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="gestion_servicios.php" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Estás seguro de que quieres eliminar este servicio? Si tiene citas asociadas, podría fallar.');">
                                    <input type="hidden" name="id_servicio" value="<?php echo $servicio['id_servicio']; ?>">
                                    <button type="submit" name="delete_service" class="btn btn-danger btn-sm" title="Eliminar Servicio">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay servicios registrados. Añade uno con el botón de arriba.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$conn->close();
include("../includes/footer.php");
?>