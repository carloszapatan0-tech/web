<?php
/*
 * Archivo: pages/registro.php
 * Página de registro de nuevos pacientes.
 * Incluye validación de frontend (JS) y backend (PHP).
 */

// INCLUIR ARCHIVOS NECESARIOS
// Incluimos el header (que inicia la sesión) y la conexión a la BD.
// Usamos ../ para "subir" un nivel de directorio.
include("../includes/header.php");
include("../includes/db.php");

// INICIALIZAR VARIABLES
// $mensaje guardará los mensajes de error o éxito para mostrar al usuario.
$mensaje = "";

// PROCESAMIENTO DEL FORMULARIO (LÓGICA DE BACKEND)
// Este bloque de código solo se ejecuta si el usuario envió el formulario (método POST).
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- Recolección Segura de Datos ---
    // Recogemos los datos del formulario. trim() elimina espacios al inicio y al final.
    $nombre = trim($_POST['nombre']);
    $apellido_paterno = trim($_POST['apellido_paterno']);
    $apellido_materno = trim($_POST['apellido_materno']);
    $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
    $genero = $_POST['genero'] ?? ''; // '??' previene errores si no se envía.
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // --- Validación de Backend ---
    // Esta validación es crucial, ya que un atacante puede saltarse el JS.
    
    // Lista para guardar errores.
    $errores = [];

    // Comprobamos campos obligatorios.
    if (empty($nombre) || empty($apellido_paterno) || empty($email) || empty($password) || empty($genero) || empty($fecha_nacimiento)) {
        $errores[] = "Los campos con asterisco (*) son obligatorios.";
    }
    // Comprobamos la coincidencia de contraseñas.
    if ($password !== $confirmPassword) {
        $errores[] = "Las contraseñas no coinciden.";
    }
    // Comprobamos la longitud de la contraseña.
    if (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }
    // Comprobamos que el email sea válido.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $errores[] = "El formato del correo electrónico no es válido.";
    }

    // --- Comprobación de Email Duplicado ---
    // Si hasta ahora no hay errores, comprobamos la BD.
    if (empty($errores)) {
        
        // Usamos Prepared Statements para prevenir Inyección SQL.
        $stmt_check_email = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt_check_email->bind_param("s", $email); // 's' = string
        $stmt_check_email->execute();
        $stmt_check_email->store_result(); // Guardamos el resultado.

        // Si num_rows es > 0, el email ya existe.
        if ($stmt_check_email->num_rows > 0) {
            $errores[] = "El correo electrónico ya está registrado. Por favor, usa otro.";
        }
        $stmt_check_email->close();
    }

    // --- Inserción en la Base de Datos ---
    // Si (y solo si) el array de errores sigue vacío, procedemos a registrar.
    if (empty($errores)) {
        
        // ¡SEGURIDAD! Encriptamos la contraseña ANTES de guardarla.
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Preparamos la consulta de inserción (segura).
        $stmt_insert = $conn->prepare("INSERT INTO usuarios 
            (nombre, apellido_paterno, apellido_materno, fecha_nacimiento, genero, telefono, email, password, rol) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $rol = "paciente"; // Asignamos el rol por defecto.

        // Vinculamos todos los parámetros. 's' = string.
        $stmt_insert->bind_param("sssssssss", 
            $nombre, 
            $apellido_paterno, 
            $apellido_materno, 
            $fecha_nacimiento, 
            $genero, 
            $telefono, 
            $email, 
            $hashed_password, // Guardamos el hash, no la contraseña original.
            $rol
        );

        // Ejecutamos la consulta.
        if ($stmt_insert->execute()) {
            // Si fue exitoso, mostramos un mensaje de éxito.
            $mensaje = "<div class='alert alert-success text-center'>
                            ¡Registro exitoso! Ya puedes <a href='login.php' class='alert-link'>iniciar sesión</a>.
                        </div>";
            
            // Limpiamos los datos del formulario para que no se "peguen".
            $_POST = array();

        } else {
            // Si falla la inserción (error inesperado de BD).
            $mensaje = "<div class='alert alert-danger text-center'>Error al registrar el usuario: " . $stmt_insert->error . "</div>";
        }
        $stmt_insert->close();

    } else {
        // Si hubo errores de validación, los formateamos y los mostramos.
        $mensaje = "<div class='alert alert-danger'><ul>";
        foreach ($errores as $error) {
            $mensaje .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $mensaje .= "</ul></div>";
    }
} // Fin del bloque if ($_SERVER["REQUEST_METHOD"] == "POST")

?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card auth-card">
            <div class="card-body">
                <h2 class="text-center mb-4">Registro de Paciente</h2>
                
                <?php echo $mensaje; // Aquí se mostrarán los mensajes de éxito o error de PHP. ?>

                <form id="formRegistro" action="registro.php" method="POST">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre(s) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required 
                               value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="apellido_paterno" class="form-label">Apellido Paterno <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" required
                               value="<?php echo isset($_POST['apellido_paterno']) ? htmlspecialchars($_POST['apellido_paterno']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="apellido_materno" class="form-label">Apellido Materno</label>
                        <input type="text" class="form-control" id="apellido_materno" name="apellido_materno"
                               value="<?php echo isset($_POST['apellido_materno']) ? htmlspecialchars($_POST['apellido_materno']) : ''; ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required
                                   value="<?php echo isset($_POST['fecha_nacimiento']) ? htmlspecialchars($_POST['fecha_nacimiento']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                             <label for="genero" class="form-label">Género <span class="text-danger">*</span></label>
                            <select class="form-select" id="genero" name="genero" required>
                                <option value="" disabled <?php echo !isset($_POST['genero']) ? 'selected' : ''; ?>>Selecciona...</option>
                                <option value="masculino" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'masculino') ? 'selected' : ''; ?>>Masculino</option>
                                <option value="femenino" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'femenino') ? 'selected' : ''; ?>>Femenino</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono"
                               value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div id="passwordHelp" class="form-text"></div>
                    </div>

                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                        <div id="confirmHelp" class="form-text"></div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">Registrarse</button>
                    </div>
                </form>

                <p class="text-center mt-3">
                    ¿Ya tienes cuenta? <a href="login.php">Inicia Sesión aquí</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// INCLUIR PIE DE PÁGINA Y CERRAR CONEXIÓN
// Incluimos el footer (que carga el JS) y cerramos la conexión a la BD.
if(isset($conn)){
    $conn->close();
}
include("../includes/footer.php");
?>