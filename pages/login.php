<?php
/*
 * Archivo: pages/login.php
 * Manejar el inicio de sesión de los usuarios.
 */

// INCLUIR ARCHIVOS NECESARIOS
include("../includes/header.php");
include("../includes/db.php");

// MEJORA DE UX: REDIRIGIR SI YA ESTÁ LOGUEADO
// Si el usuario ya tiene una sesión, lo redirigimos a su panel.
if (isset($_SESSION['id_usuario'])) {
    if ($_SESSION['rol_usuario'] == 'administrador') {
        // Si es admin, lo mandamos a su panel.
        header("Location: " . $base_url . "pages/dashboard_admin.php");
    } else {
        // Si es paciente, lo mandamos al suyo.
        header("Location: " . $base_url . "pages/dashboard_paciente.php");
    }
    exit(); // Detiene el script para asegurar la redirección.
}

// INICIALIZAR VARIABLES
$mensaje = "";

// PROCESAMIENTO DEL FORMULARIO
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $mensaje = "<div class='alert alert-danger text-center'>Por favor, ingresa tu correo y contraseña.</div>";
    } else {
        
        $stmt = $conn->prepare("SELECT id_usuario, nombre, email, password, rol FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            
            $usuario = $result->fetch_assoc();

            if (password_verify($password, $usuario['password'])) {
                
                // ¡Contraseña correcta!
                session_regenerate_id(true);

                // Guardar Datos en la Sesión
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre_usuario'] = $usuario['nombre'];
                $_SESSION['rol_usuario'] = $usuario['rol'];

                // Redirección al Panel según el rol.
                if ($usuario['rol'] == 'administrador') {
                    header("Location: " . $base_url . "pages/dashboard_admin.php");
                } else {
                    header("Location: " . $base_url . "pages/dashboard_paciente.php");
                }
                exit(); // Detenemos el script.

            } else {
                $mensaje = "<div class='alert alert-danger text-center'>Correo o contraseña incorrectos.</div>";
            }
        } else {
            $mensaje = "<div class='alert alert-danger text-center'>Correo o contraseña incorrectos.</div>";
        }
        $stmt->close();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card auth-card">
            <div class="card-body">
                <h2 class="text-center mb-4">Iniciar Sesión</h2>
                
                <?php echo $mensaje; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Entrar</button>
                    </div>
                </form>

                <p class="text-center mt-3">
                    ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
                </p>
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