<?php
/*
 * Archivo: pages/gestion_personal.php
 * Panel de admin para gestionar (CRUD) al personal/especialistas.
 */

include("../includes/header.php");
include("../includes/db.php");

// SEGURIDAD: Proteger página
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}

$mensaje = "";
// Directorio donde se guardarán las fotos del personal
$upload_dir = "../img/personal/"; 

// Asegurarse de que el directorio de subida exista
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// --- LÓGICA DE ACCIONES (POST) ---

// --- ACCIÓN: AÑADIR NUEVO PERSONAL ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_personal'])) {
    
    // Recolectar todos los datos del formulario
    $nombre = trim($_POST['nombre']);
    $puesto = trim($_POST['puesto']);
    $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : NULL;
    $email = !empty($_POST['email']) ? trim($_POST['email']) : NULL;
    $semestre = !empty($_POST['semestre']) ? trim($_POST['semestre']) : NULL;
    $pregunta = !empty($_POST['pregunta_profesion']) ? trim($_POST['pregunta_profesion']) : NULL;
    $foto_nombre = NULL;

    // Validación básica
    if (empty($nombre) || (empty($puesto) && empty($semestre))) {
        $mensaje = "<div class='alert alert-danger'>El Nombre y el Puesto (o Semestre) son obligatorios.</div>";
    } else {
        // Manejo de la subida de imagen (si se seleccionó una)
        if (isset($_FILES['foto_personal']) && $_FILES['foto_personal']['error'] == 0) {
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_info = pathinfo($_FILES['foto_personal']['name']);
            $extension = strtolower($file_info['extension']);

            if (in_array($extension, $allowed_types)) {
                $foto_nombre = uniqid('personal_', true) . '.' . $extension;
                $target_file = $upload_dir . $foto_nombre;

                if (!move_uploaded_file($_FILES['foto_personal']['tmp_name'], $target_file)) {
                    $mensaje = "<div class='alert alert-danger'>Error al subir la imagen.</div>";
                    $foto_nombre = NULL;
                }
            } else {
                $mensaje = "<div class='alert alert-warning'>Tipo de archivo no permitido (solo JPG, PNG, GIF, WEBP).</div>";
            }
        }

        // Insertar en la base de datos (solo si no hubo error grave)
        if (empty($mensaje) || strpos($mensaje, 'alert-danger') === false) {
             $stmt = $conn->prepare("INSERT INTO personal (nombre, puesto, fecha_nacimiento, email, semestre, pregunta_profesion, foto_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
             $stmt->bind_param("sssssss", $nombre, $puesto, $fecha_nacimiento, $email, $semestre, $pregunta, $foto_nombre);

             if ($stmt->execute()) {
                 $mensaje = "<div class='alert alert-success'>Miembro del equipo añadido correctamente.</div>";
             } else {
                 $mensaje = "<div class='alert alert-danger'>Error al añadir al miembro: " . $stmt->error . "</div>";
                 if ($foto_nombre && file_exists($upload_dir . $foto_nombre)) {
                     unlink($upload_dir . $foto_nombre);
                 }
             }
             $stmt->close();
        }
    }
}

// --- ACCIÓN: ELIMINAR PERSONAL ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_personal'])) {
    $id_personal_eliminar = (int)$_POST['id_personal'];

    // Obtener el nombre de la foto para borrarla del servidor
    $stmt_get_foto = $conn->prepare("SELECT foto_url FROM personal WHERE id_personal = ?");
    $stmt_get_foto->bind_param("i", $id_personal_eliminar);
    $stmt_get_foto->execute();
    $foto_a_borrar = $stmt_get_foto->get_result()->fetch_assoc()['foto_url'] ?? null;
    $stmt_get_foto->close();

    // Eliminar de la base de datos
    $stmt = $conn->prepare("DELETE FROM personal WHERE id_personal = ?");
    $stmt->bind_param("i", $id_personal_eliminar);

    if ($stmt->execute()) {
        $mensaje = "<div class='alert alert-success'>Miembro del equipo eliminado.</div>";
        // Si se eliminó de BD y tenía foto, borrar el archivo físico
        if ($foto_a_borrar && file_exists($upload_dir . $foto_a_borrar)) {
            unlink($upload_dir . $foto_a_borrar);
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Error al eliminar: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// --- OBTENER LISTA DE PERSONAL PARA MOSTRAR ---
$personal = $conn->query("SELECT * FROM personal ORDER BY nombre ASC");

?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2>Gestión de Personal</h2>
        <button class="btn btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#addPersonalForm" aria-expanded="false" aria-controls="addPersonalForm">
            <i class="fas fa-plus"></i> Añadir Miembro
        </button>
    </div>
    <a href="dashboard_admin.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Volver al Panel</a>

    <?php echo $mensaje; ?>

    <div class="collapse mb-4" id="addPersonalForm">
        <div class="card card-body shadow-sm">
            <h4>Añadir Nuevo Miembro</h4>
            <form action="gestion_personal.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                     <div class="col-md-6 mb-3">
                        <label for="puesto" class="form-label">Puesto (Ej: Odontóloga Principal)</label>
                        <input type="text" class="form-control" id="puesto" name="puesto">
                    </div>
                </div>
                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                    </div>
                    <div class="col-md-6 mb-3">
                         <label for="email" class="form-label">Email</label>
                         <input type="email" class="form-control" id="email" name="email" placeholder="ejemplo@correo.com">
                    </div>
                 </div>
                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="semestre" class="form-label">Semestre (Si es estudiante)</label>
                         <input type="text" class="form-control" id="semestre" name="semestre" placeholder="Ej: 8vo Semestre">
                    </div>
                     <div class="col-md-6 mb-3">
                         <label for="foto_personal" class="form-label">Foto (Opcional)</label>
                         <input class="form-control" type="file" id="foto_personal" name="foto_personal">
                    </div>
                 </div>
                 <div class="mb-3">
                    <label for="pregunta_profesion" class="form-label">¿Por qué elegiste la profesión?</label>
                    <textarea class="form-control" id="pregunta_profesion" name="pregunta_profesion" rows="2"></textarea>
                 </div>
                <button type="submit" name="add_personal" class="btn btn-primary">Guardar Miembro</button>
            </form>
        </div>
    </div>

    <h4>Equipo Actual</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Foto</th>
                    <th>Nombre</th>
                    <th>Puesto / Semestre</th>
                    <th>Email</th>
                    <th>Edad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($personal && $personal->num_rows > 0): ?>
                    <?php while ($persona = $personal->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center">
                                <?php if ($persona['foto_url']): ?>
                                    <img src="<?php echo $base_url . 'img/personal/' . htmlspecialchars($persona['foto_url']); ?>"
                                         alt="<?php echo htmlspecialchars($persona['nombre']); ?>"
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle fa-2x text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($persona['nombre']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($persona['semestre'] ? $persona['semestre'] : $persona['puesto']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($persona['email'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                if (!empty($persona['fecha_nacimiento'])) {
                                    $fecha_nac = new DateTime($persona['fecha_nacimiento']);
                                    $hoy = new DateTime();
                                    echo $hoy->diff($fecha_nac)->y; // Muestra los años
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="modificar_personal.php?id_personal=<?php echo $persona['id_personal']; ?>" class="btn btn-primary btn-sm me-1" title="Modificar Personal">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="gestion_personal.php" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Estás seguro de que quieres eliminar a este miembro del equipo?');">
                                    <input type="hidden" name="id_personal" value="<?php echo $persona['id_personal']; ?>">
                                    <button type="submit" name="delete_personal" class="btn btn-danger btn-sm" title="Eliminar Personal">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay miembros del equipo registrados.</td>
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