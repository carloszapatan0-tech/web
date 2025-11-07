<?php
/*
 * Archivo: pages/modificar_usuario.php
 * Formulario de admin para modificar datos de un usuario existente.
 */

include("../includes/header.php");
include("../includes/db.php");

// SEGURIDAD: Proteger página y validar ID de usuario de la URL
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}
if (!isset($_GET['id_usuario']) || !filter_var($_GET['id_usuario'], FILTER_VALIDATE_INT)) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ID de usuario no válido. <a href='gestion_usuarios.php'>Volver</a>.</div></div>";
    include("../includes/footer.php");
    exit();
}
$id_usuario_modificar = (int)$_GET['id_usuario'];

// Medida de seguridad: Evitar que el admin se edite a sí mismo en esta página
if ($id_usuario_modificar == $_SESSION['id_usuario']) {
     echo "<div class='container mt-5'><div class='alert alert-warning'>Para modificar tu propia cuenta, usa la opción 'Mi Perfil' desde el menú. <a href='gestion_usuarios.php'>Volver a la lista</a>.</div></div>";
    include("../includes/footer.php");
    exit();
}

$mensaje = ""; // Para mensajes de éxito o error

// PROCESAMIENTO DEL FORMULARIO (CUANDO SE ENVÍAN LOS CAMBIOS - MÉTODO POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Recolección de Datos del Formulario ---
    $nombre = trim($_POST['nombre']);
    $apellido_paterno = trim($_POST['apellido_paterno']);
    $apellido_materno = trim($_POST['apellido_materno']);
    $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
    $genero = $_POST['genero'] ?? '';
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    // Nota: El rol y la contraseña se gestionan por separado en gestion_usuarios.php por seguridad.

    // --- Validación de Backend ---
    $errores = [];
    if (empty($nombre) || empty($apellido_paterno) || empty($email) || empty($genero) || empty($fecha_nacimiento)) {
        $errores[] = "Nombre, Ap. Paterno, Email, Género y Fecha de Nacimiento son campos obligatorios.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $errores[] = "El formato del correo electrónico no es válido.";
    }

    // --- Comprobar Email Duplicado (SOLO SI SE CAMBIÓ) ---
    if (empty($errores)) {
        // Obtenemos el email actual del usuario para compararlo
        $stmt_email_actual = $conn->prepare("SELECT email FROM usuarios WHERE id_usuario = ?");
        $stmt_email_actual->bind_param("i", $id_usuario_modificar);
        $stmt_email_actual->execute();
        $email_actual = $stmt_email_actual->get_result()->fetch_assoc()['email'];
        $stmt_email_actual->close();

        // Si el email del formulario es DIFERENTE al que ya tenía,
        // debemos verificar que el NUEVO email no esté ya en uso por OTRO usuario.
        if ($email !== $email_actual) {
            $stmt_check_email = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();
            if ($stmt_check_email->num_rows > 0) {
                $errores[] = "El nuevo correo electrónico ya está registrado por otro usuario. Por favor, elige uno diferente.";
            }
            $stmt_check_email->close();
        }
    }

    // --- Actualización en la Base de Datos ---
    if (empty($errores)) {
        $stmt_update = $conn->prepare("UPDATE usuarios SET
            nombre = ?,
            apellido_paterno = ?,
            apellido_materno = ?,
            fecha_nacimiento = ?,
            genero = ?,
            telefono = ?,
            email = ?
            WHERE id_usuario = ?");

        // bind_param: s = string, i = integer
        $stmt_update->bind_param("sssssssi",
            $nombre, $apellido_paterno, $apellido_materno, $fecha_nacimiento,
            $genero, $telefono, $email, $id_usuario_modificar
        );

        if ($stmt_update->execute()) {
            $mensaje = "<div class='alert alert-success'>Datos del usuario actualizados correctamente. <a href='gestion_usuarios.php' class='alert-link'>Volver a la lista</a>.</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al actualizar los datos: " . $stmt_update->error . "</div>";
        }
        $stmt_update->close();
    } else {
        // Mostrar errores de validación
        $mensaje = "<div class='alert alert-danger'><ul>";
        foreach ($errores as $error) {
            $mensaje .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $mensaje .= "</ul></div>";
    }
}

// CARGAR DATOS ACTUALES DEL USUARIO (PARA MOSTRAR EN EL FORMULARIO)
$stmt_load = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
$stmt_load->bind_param("i", $id_usuario_modificar);
$stmt_load->execute();
$usuario_actual = $stmt_load->get_result()->fetch_assoc();
$stmt_load->close();

// Si no se encontró el usuario, detenemos la página
if (!$usuario_actual) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>No se encontró el usuario solicitado. <a href='gestion_usuarios.php'>Volver</a>.</div></div>";
    include("../includes/footer.php");
    exit();
}

?>

<!-- ESTRUCTURA HTML (FORMULARIO PRE-LLENADO) -->
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card auth-card">
            <div class="card-body">
                <h2 class="text-center mb-4">Modificar Usuario (ID: <?php echo $id_usuario_modificar; ?>)</h2>
                
                <a href="gestion_usuarios.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Volver a la lista</a>

                <?php echo $mensaje; ?>

                <form action="modificar_usuario.php?id_usuario=<?php echo $id_usuario_modificar; ?>" method="POST">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre(s)</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required
                                   value="<?php echo htmlspecialchars($usuario_actual['nombre']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="apellido_paterno" class="form-label">Apellido Paterno</label>
                            <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" required
                                   value="<?php echo htmlspecialchars($usuario_actual['apellido_paterno']); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="apellido_materno" class="form-label">Apellido Materno (Opcional)</label>
                        <input type="text" class="form-control" id="apellido_materno" name="apellido_materno"
                               value="<?php echo htmlspecialchars($usuario_actual['apellido_materno'] ?? ''); ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required
                                   value="<?php echo htmlspecialchars($usuario_actual['fecha_nacimiento']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="genero" class="form-label">Género</label>
                            <select class="form-select" id="genero" name="genero" required>
                                <option value="masculino" <?php echo ($usuario_actual['genero'] == 'masculino') ? 'selected' : ''; ?>>Masculino</option>
                                <option value="femenino" <?php echo ($usuario_actual['genero'] == 'femenino') ? 'selected' : ''; ?>>Femenino</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono (Opcional)</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono"
                               value="<?php echo htmlspecialchars($usuario_actual['telefono'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo htmlspecialchars($usuario_actual['email']); ?>">
                    </div>

                    <hr>
                    <p class="text-muted small">El rol se gestiona en la tabla principal. La contraseña solo la puede cambiar el propio usuario.</p>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="gestion_usuarios.php" class="btn btn-secondary">Cancelar</a>
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