<?php
/*
 * Archivo: pages/modificar_cita.php
 * Formulario de admin para modificar una cita existente.
 */

include("../includes/header.php");
include("../includes/db.php");

// ¡SEGURIDAD! PROTEGER LA PÁGINA
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}

$mensaje = ""; // Para mensajes.

// OBTENER Y VALIDAR ID DE LA CITA DESDE URL
if (!isset($_GET['id_cita']) || !filter_var($_GET['id_cita'], FILTER_VALIDATE_INT)) {
    // Si no hay ID o no es un número entero, mostramos error y salimos.
    echo "<div class='container mt-5'><div class='alert alert-danger'>ID de cita no válido.</div></div>";
    include("../includes/footer.php");
    exit();
}
$id_cita_modificar = (int)$_GET['id_cita'];

// PROCESAMIENTO DEL FORMULARIO (SI SE ENVÍA - MÉTODO POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Recolección de Datos del Formulario ---
    $id_servicio = (int)$_POST['id_servicio'];
    $fecha_cita = $_POST['fecha_cita'];
    $hora_cita = $_POST['hora_cita'];
    $comentarios_paciente = trim($_POST['comentarios_paciente']);
    // Datos del visitante (solo si la cita original era de visitante)
    $nombre_visitante = isset($_POST['nombre_visitante']) ? trim($_POST['nombre_visitante']) : NULL;
    $telefono_visitante = isset($_POST['telefono_visitante']) ? trim($_POST['telefono_visitante']) : NULL;
    $email_visitante = isset($_POST['email_visitante']) ? trim($_POST['email_visitante']) : NULL;

    // --- Validación de Backend ---
    $errores = [];
    if (empty($id_servicio) || empty($fecha_cita) || empty($hora_cita)) {
        $errores[] = "Servicio, Fecha y Hora son obligatorios.";
    }
    // Validar visitante si aplica (si el campo nombre_visitante existe en el form)
    if (isset($_POST['nombre_visitante']) && empty($nombre_visitante)) {
         $errores[] = "El nombre del visitante no puede estar vacío.";
    }
    // Podríamos añadir más validaciones (ej: formato de email visitante)

    // --- Actualización en la Base de Datos ---
    if (empty($errores)) {
        // Preparamos la consulta UPDATE
        $stmt_update = $conn->prepare("UPDATE citas SET 
            id_servicio = ?, 
            fecha_cita = ?, 
            hora_cita = ?, 
            comentarios_paciente = ?,
            nombre_visitante = ?, 
            telefono_visitante = ?, 
            email_visitante = ?
            WHERE id_cita = ?");

        // 'bind_param': i = integer, s = string
        $stmt_update->bind_param("issssssi", 
            $id_servicio, 
            $fecha_cita, 
            $hora_cita, 
            $comentarios_paciente,
            $nombre_visitante, // Será NULL si la cita era de usuario registrado
            $telefono_visitante, // Ídem
            $email_visitante, // Ídem
            $id_cita_modificar // El ID de la cita que estamos editando
        );

        if ($stmt_update->execute()) {
            $mensaje = "<div class='alert alert-success'>Cita actualizada correctamente. <a href='gestion_citas.php' class='alert-link'>Volver a la lista</a>.</div>";
            // No limpiamos $_POST aquí para que el formulario siga mostrando los datos actualizados
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al actualizar la cita: " . $stmt_update->error . "</div>";
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

// CARGAR DATOS ACTUALES DE LA CITA (PARA MOSTRAR EN EL FORMULARIO - MÉTODO GET)
// Hacemos un JOIN para obtener nombres en lugar de solo IDs.
$stmt_load = $conn->prepare("SELECT c.*, s.nombre_servicio, u.nombre as nombre_usuario, u.apellido_paterno 
                             FROM citas c
                             JOIN servicios s ON c.id_servicio = s.id_servicio
                             LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
                             WHERE c.id_cita = ?");
$stmt_load->bind_param("i", $id_cita_modificar);
$stmt_load->execute();
$cita_actual = $stmt_load->get_result()->fetch_assoc();
$stmt_load->close();

// Si no se encontró la cita con ese ID, mostramos error y salimos.
if (!$cita_actual) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>No se encontró la cita solicitada.</div></div>";
    include("../includes/footer.php");
    exit();
}

// CARGAR LISTA COMPLETA DE SERVICIOS (para el <select>)
$servicios_result = $conn->query("SELECT id_servicio, nombre_servicio FROM servicios ORDER BY nombre_servicio ASC");

?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card auth-card">
            <div class="card-body">
                <h2 class="text-center mb-4">Modificar Cita (ID: <?php echo $id_cita_modificar; ?>)</h2>
                
                <?php echo $mensaje; // Muestra mensajes ?>

                <form action="modificar_cita.php?id_cita=<?php echo $id_cita_modificar; ?>" method="POST">
                    
                    <div class="mb-3">
                        <label class="form-label">Paciente:</label>
                        <input type="text" class="form-control" 
                               value="<?php 
                                    if ($cita_actual['id_usuario']) {
                                        echo htmlspecialchars($cita_actual['nombre_usuario'] . ' ' . $cita_actual['apellido_paterno']) . ' (Registrado)';
                                    } else {
                                        echo htmlspecialchars($cita_actual['nombre_visitante']) . ' (Visitante)';
                                    } 
                               ?>" readonly>
                    </div>

                    <?php if (!$cita_actual['id_usuario']): ?>
                        <fieldset class="border p-3 mb-3">
                            <legend class="w-auto px-2">Datos del Visitante</legend>
                             <div class="mb-3">
                                <label for="nombre_visitante" class="form-label">Nombre:</label>
                                <input type="text" class="form-control" id="nombre_visitante" name="nombre_visitante" 
                                       value="<?php echo htmlspecialchars($cita_actual['nombre_visitante']); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telefono_visitante" class="form-label">Teléfono (Opcional):</label>
                                    <input type="tel" class="form-control" id="telefono_visitante" name="telefono_visitante"
                                           value="<?php echo htmlspecialchars($cita_actual['telefono_visitante']); ?>">
                                </div>
                                 <div class="col-md-6 mb-3">
                                    <label for="email_visitante" class="form-label">Email (Opcional):</label>
                                    <input type="email" class="form-control" id="email_visitante" name="email_visitante"
                                           value="<?php echo htmlspecialchars($cita_actual['email_visitante']); ?>">
                                </div>
                            </div>
                        </fieldset>
                    <?php endif; ?>

                    <hr>

                    <div class="mb-3">
                        <label for="id_servicio" class="form-label">Servicio Requerido:</label>
                        <select class="form-select" id="id_servicio" name="id_servicio" required>
                            <option value="">-- Elige un servicio --</option>
                            <?php while($servicio = $servicios_result->fetch_assoc()): ?>
                                <option value="<?php echo $servicio['id_servicio']; ?>" 
                                        <?php if ($servicio['id_servicio'] == $cita_actual['id_servicio']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($servicio['nombre_servicio']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_cita" class="form-label">Fecha de la Cita:</label>
                            <input type="date" class="form-control" id="fecha_cita" name="fecha_cita" required 
                                   value="<?php echo htmlspecialchars($cita_actual['fecha_cita']); ?>">
                        </div>
                         <div class="col-md-6 mb-3">
                             <label for="hora_cita" class="form-label">Hora de la Cita:</label>
                             <input type="time" class="form-control" id="hora_cita" name="hora_cita" required
                                    value="<?php echo htmlspecialchars($cita_actual['hora_cita']); ?>">
                         </div>
                    </div>

                     <div class="mb-3">
                        <label for="comentarios_paciente" class="form-label">Notas / Comentarios (Opcional):</label>
                        <textarea class="form-control" id="comentarios_paciente" name="comentarios_paciente" rows="2"><?php echo htmlspecialchars($cita_actual['comentarios_paciente']); ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="gestion_citas.php" class="btn btn-secondary">Volver sin Guardar</a>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
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