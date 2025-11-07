<?php
/*
 * Archivo: pages/agendar_cita.php
 * Formulario para que los pacientes registrados agenden una nueva cita.
 */

include("../includes/header.php");
include("../includes/db.php");

// ¡SEGURIDAD! PROTEGER LA PÁGINA
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'paciente') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}

$id_usuario_actual = $_SESSION['id_usuario'];
$mensaje = "";

// --- OBTENER DATOS PARA EL MENÚ DE SERVICIOS ---
$servicios_result = $conn->query("SELECT id_servicio, nombre_servicio FROM servicios ORDER BY nombre_servicio ASC");

// --- LEER ID DE SERVICIO DE LA URL ---
// Leemos si se pasó un ID de servicio desde la página de servicios.php
$servicio_id_seleccionado = isset($_GET['servicio_id']) ? (int)$_GET['servicio_id'] : 0;

// PROCESAMIENTO DEL FORMULARIO (SI SE ENVÍA - MÉTODO POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recolección y Validación 
    $id_servicio = (int)$_POST['id_servicio'];
    $fecha_cita = $_POST['fecha_cita'];
    $hora_cita = $_POST['hora_cita'];
    $comentarios_paciente = trim($_POST['comentarios_paciente']);

    $errores = [];
    if (empty($id_servicio) || empty($fecha_cita) || empty($hora_cita)) {
        $errores[] = "Servicio, Fecha y Hora son obligatorios.";
    }
    if ($fecha_cita < date("Y-m-d")) {
         $errores[] = "No puedes solicitar una cita en una fecha pasada.";
    }

    // Inserción en la Base de Datos
    if (empty($errores)) {
        $stmt_insert = $conn->prepare("INSERT INTO citas
            (id_usuario, id_servicio, fecha_cita, hora_cita, estado_cita, comentarios_paciente)
            VALUES (?, ?, ?, ?, ?, ?)");

        $estado_inicial = 'Pendiente';

        $stmt_insert->bind_param("iissss",
            $id_usuario_actual, $id_servicio, $fecha_cita, $hora_cita,
            $estado_inicial, $comentarios_paciente
        );

        if ($stmt_insert->execute()) {
            $mensaje = "<div class='alert alert-success'>Tu solicitud de cita ha sido enviada para el " . date("d/m/Y", strtotime($fecha_cita)) . " a las " . date("h:i A", strtotime($hora_cita)) . ". Te contactaremos para confirmar.</div>";
            $_POST = [];
            $servicio_id_seleccionado = 0; // Limpiar preselección si fue exitoso
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al solicitar la cita: " . $stmt_insert->error . "</div>";
        }
        $stmt_insert->close();
    } else {
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
                <h2 class="text-center mb-4">Agendar Nueva Cita</h2>
                <a href="dashboard_paciente.php" class="btn btn-sm btn-outline-secondary mb-3 d-block mx-auto" style="width: fit-content;"><i class="fas fa-arrow-left me-2"></i>Volver a Mi Panel</a>

                <?php echo $mensaje; ?>

                <form action="agendar_cita.php" method="POST">

                    <div class="mb-3">
                        <label for="id_servicio" class="form-label">¿Qué servicio necesitas?</label>
                        <select class="form-select" id="id_servicio" name="id_servicio" required>
                            <option value="">-- Elige un servicio --</option>
                            <?php
                            mysqli_data_seek($servicios_result, 0);
                            ?>
                            <?php while($servicio = $servicios_result->fetch_assoc()): ?>
                                <option value="<?php echo $servicio['id_servicio']; ?>"
                                        <?php
                                        // Comprueba si este servicio coincide con el ID de la URL O con el POST (si hubo error)
                                        if (($servicio_id_seleccionado > 0 && $servicio['id_servicio'] == $servicio_id_seleccionado) ||
                                            (isset($_POST['id_servicio']) && $_POST['id_servicio'] == $servicio['id_servicio'])) {
                                            echo 'selected';
                                        }
                                        ?>>
                                <?php echo htmlspecialchars($servicio['nombre_servicio']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_cita" class="form-label">Fecha deseada:</label>
                            <input type="date" class="form-control" id="fecha_cita" name="fecha_cita" required
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo htmlspecialchars($_POST['fecha_cita'] ?? ''); ?>">
                        </div>
                         <div class="col-md-6 mb-3">
                             <label for="hora_cita" class="form-label">Hora deseada:</label>
                             <input type="time" class="form-control" id="hora_cita" name="hora_cita" required
                                    value="<?php echo htmlspecialchars($_POST['hora_cita'] ?? ''); ?>">
                         </div>
                    </div>

                    <div class="mb-3">
                        <label for="comentarios_paciente" class="form-label">Comentarios adicionales (Opcional):</label>
                        <textarea class="form-control" id="comentarios_paciente" name="comentarios_paciente" rows="3"><?php echo htmlspecialchars($_POST['comentarios_paciente'] ?? ''); ?></textarea>
                         <div class="form-text">Puedes indicar síntomas, preferencias de horario, etc.</div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo $base_url; ?>index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Solicitar Cita</button>
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