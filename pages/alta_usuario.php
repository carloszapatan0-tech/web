<?php
/*
 * Archivo: pages/alta_usuario.php
 * Formulario de administrador para crear (dar de alta) nuevos usuarios.
 */

include("../includes/header.php"); // Carga el header y session_start()
include("../includes/db.php");

// ¡SEGURIDAD! PROTEGER LA PÁGINA
// Solo los administradores pueden estar aquí.
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}

$mensaje = ""; // Para mensajes de éxito o error.

// PROCESAMIENTO DEL FORMULARIO (LÓGICA DE BACKEND)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- Recolección Segura de Datos ---
    $nombre = trim($_POST['nombre']);
    $apellido_paterno = trim($_POST['apellido_paterno']);
    $apellido_materno = trim($_POST['apellido_materno']);
    $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
    $genero = $_POST['genero'] ?? '';
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Contraseña asignada por el admin
    $rol = $_POST['rol'] ?? 'paciente'; // Rol asignado por el admin

    // --- Validación de Backend ---
    $errores = [];

    if (empty($nombre) || empty($apellido_paterno) || empty($email) || empty($password) || empty($genero) || empty($fecha_nacimiento) || empty($rol)) {
        $errores[] = "Todos los campos (excepto Ap. Materno y Teléfono) son obligatorios.";
    }
    if (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $errores[] = "El formato del correo electrónico no es válido.";
    }

    // --- Comprobación de Email Duplicado ---
    if (empty($errores)) {
        $stmt_check_email = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();

        if ($stmt_check_email->num_rows > 0) {
            $errores[] = "El correo electrónico ya está registrado.";
        }
        $stmt_check_email->close();
    }

    // --- Inserción en la Base de Datos ---
    if (empty($errores)) {
        
        // Encriptamos la contraseña que el admin asignó
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt_insert = $conn->prepare("INSERT INTO usuarios 
            (nombre, apellido_paterno, apellido_materno, fecha_nacimiento, genero, telefono, email, password, rol) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt_insert->bind_param("sssssssss", 
            $nombre, $apellido_paterno, $apellido_materno, $fecha_nacimiento, 
            $genero, $telefono, $email, $hashed_password, $rol
        );

        if ($stmt_insert->execute()) {
            $mensaje = "<div class='alert alert-success'>Usuario creado exitosamente.</div>";
            // Limpiamos los datos del formulario si fue exitoso
            $_POST = array();
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al crear el usuario: " . $stmt_insert->error . "</div>";
        }
        $stmt_insert->close();

    } else {
        // Si hubo errores, los mostramos
        $mensaje = "<div class='alert alert-danger'><ul>";
        foreach ($errores as $error) {
            $mensaje .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $mensaje .= "</ul></div>";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card auth-card">
            <div class="card-body">
                <h2 class="text-center mb-4">Dar de Alta Nuevo Usuario</h2>
                
                <?php echo $mensaje; // Muestra mensajes de éxito/error ?>

                <form action="alta_usuario.php" method="POST" novalidate>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre(s)</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required 
                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="apellido_paterno" class="form-label">Apellido Paterno</label>
                            <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" required
                                   value="<?php echo htmlspecialchars($_POST['apellido_paterno'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="apellido_materno" class="form-label">Apellido Materno (Opcional)</label>
                        <input type="text" class="form-control" id="apellido_materno" name="apellido_materno"
                               value="<?php echo htmlspecialchars($_POST['apellido_materno'] ?? ''); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required
                                   value="<?php echo htmlspecialchars($_POST['fecha_nacimiento'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="genero" class="form-label">Género</label>
                            <select class="form-select" id="genero" name="genero" required>
                                <option value="" disabled <?php echo !isset($_POST['genero']) ? 'selected' : ''; ?>>Selecciona...</option>
                                <option value="masculino" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'masculino') ? 'selected' : ''; ?>>Masculino</option>
                                <option value="femenino" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'femenino') ? 'selected' : ''; ?>>Femenino</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono (Opcional)</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono"
                               value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                    </div>

                    <hr>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña Provisional</label>
                        <input type="text" class="form-control" id="password" name="password" required>
                        <div class="form-text">El usuario podrá cambiar esta contraseña después. Mínimo 6 caracteres.</div>
                    </div>

                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol del Usuario</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="paciente" selected>Paciente</option>
                            <option value="administrador">Administrador</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="gestion_usuarios.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// INCLUIR PIE DE PÁGINA Y CERRAR CONEXIÓN
if(isset($conn)){
    $conn->close();
}
include("../includes/footer.php");
?>