<?php
/*
 * Archivo: pages/agendar_cita_admin.php
 * Formulario de admin para agendar nuevas citas
 * (para usuarios registrados o visitantes).
 */

include("../includes/header.php"); // Carga el header y session_start()
include("../includes/db.php");

// ¡SEGURIDAD! PROTEGER LA PÁGINA
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}

$mensaje = ""; // Para mensajes.

// --- OBTENER DATOS PARA LOS MENÚS DESPLEGABLES ---
// Lista de Servicios
$servicios_result = $conn->query("SELECT id_servicio, nombre_servicio FROM servicios ORDER BY nombre_servicio ASC");

// Lista de Pacientes Registrados (Solo rol 'paciente')
$pacientes_result = $conn->query("SELECT id_usuario, nombre, apellido_paterno FROM usuarios WHERE rol = 'paciente' ORDER BY nombre ASC");

// PROCESAMIENTO DEL FORMULARIO (LÓGICA DE BACKEND)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Recolección de Datos ---
    $tipo_paciente = $_POST['tipo_paciente'] ?? ''; // 'registrado' o 'visitante'
    $id_usuario = ($_POST['id_usuario'] != '') ? (int)$_POST['id_usuario'] : NULL;
    
    // Datos del visitante (solo si se seleccionó 'visitante')
    $nombre_visitante = ($tipo_paciente === 'visitante') ? trim($_POST['nombre_visitante']) : NULL;
    $telefono_visitante = ($tipo_paciente === 'visitante') ? trim($_POST['telefono_visitante']) : NULL;
    $email_visitante = ($tipo_paciente === 'visitante') ? trim($_POST['email_visitante']) : NULL;

    // Datos comunes de la cita
    $id_servicio = (int)$_POST['id_servicio'];
    $fecha_cita = $_POST['fecha_cita'];
    $hora_cita = $_POST['hora_cita'];
    $comentarios_paciente = trim($_POST['comentarios_paciente']); // Admin puede añadir notas aquí

    // --- Validación de Backend ---
    $errores = [];

    // Validar tipo de paciente seleccionado
    if ($tipo_paciente === 'registrado' && $id_usuario === NULL) {
        $errores[] = "Debes seleccionar un paciente registrado.";
    }
    if ($tipo_paciente === 'visitante' && empty($nombre_visitante)) {
        $errores[] = "Debes ingresar el nombre del visitante.";
    }
    // Validar campos comunes
    if (empty($id_servicio) || empty($fecha_cita) || empty($hora_cita)) {
        $errores[] = "Servicio, Fecha y Hora son obligatorios.";
    }
    // Validar que la fecha no sea pasada
    if ($fecha_cita < date("Y-m-d")) {
         $errores[] = "No puedes agendar una cita en una fecha pasada.";
    }
    // (Podríamos añadir validación de hora, ej: dentro de horario laboral)

    // --- Inserción en la Base de Datos ---
    if (empty($errores)) {
        $stmt_insert = $conn->prepare("INSERT INTO citas 
            (id_usuario, id_servicio, fecha_cita, hora_cita, estado_cita, nombre_visitante, telefono_visitante, email_visitante, comentarios_paciente) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $estado_inicial = 'Confirmada'; // El admin agenda citas ya confirmadas

        // 'bind_param' necesita referencias, no podemos usar NULL directamente para id_usuario
        $id_usuario_bind = $id_usuario; 

        // i = integer, s = string
        $stmt_insert->bind_param("iisssssss", 
            $id_usuario_bind, // Puede ser NULL si es visitante
            $id_servicio, 
            $fecha_cita, 
            $hora_cita, 
            $estado_inicial, 
            $nombre_visitante, // NULL si es registrado
            $telefono_visitante, // NULL si es registrado
            $email_visitante, // NULL si es registrado
            $comentarios_paciente
        );

        if ($stmt_insert->execute()) {
            $mensaje = "<div class='alert alert-success'>Cita agendada exitosamente para el " . date("d/m/Y", strtotime($fecha_cita)) . " a las " . date("h:i A", strtotime($hora_cita)) . ".</div>";
            $_POST = []; // Limpiar formulario
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al agendar la cita: " . $stmt_insert->error . "</div>";
        }
        $stmt_insert->close();
    } else {
        // Mostrar errores de validación
        $mensaje = "<div class='alert alert-danger'><ul>";
        foreach ($errores as $error) {
            $mensaje .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $mensaje .= "</ul></div>";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card auth-card">
            <div class="card-body">
                <h2 class="text-center mb-4">Agendar Nueva Cita (Admin)</h2>
                
                <?php echo $mensaje; // Muestra mensajes ?>

                <form action="agendar_cita_admin.php" method="POST">
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo de Paciente:</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_paciente" id="tipo_registrado" value="registrado" checked>
                                <label class="form-check-label" for="tipo_registrado">Paciente Registrado</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_paciente" id="tipo_visitante" value="visitante">
                                <label class="form-check-label" for="tipo_visitante">Visitante (No Registrado)</label>
                            </div>
                        </div>
                    </div>

                    <div id="campos_registrado" class="mb-3">
                        <label for="id_usuario" class="form-label">Selecciona Paciente:</label>
                        <select class="form-select" id="id_usuario" name="id_usuario">
                            <option value="">-- Elige un paciente --</option>
                            <?php while($paciente = $pacientes_result->fetch_assoc()): ?>
                                <option value="<?php echo $paciente['id_usuario']; ?>">
                                    <?php echo htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido_paterno']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div id="campos_visitante" style="display: none;">
                        <div class="mb-3">
                            <label for="nombre_visitante" class="form-label">Nombre del Visitante:</label>
                            <input type="text" class="form-control" id="nombre_visitante" name="nombre_visitante">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefono_visitante" class="form-label">Teléfono Visitante (Opcional):</label>
                                <input type="tel" class="form-control" id="telefono_visitante" name="telefono_visitante">
                            </div>
                             <div class="col-md-6 mb-3">
                                <label for="email_visitante" class="form-label">Email Visitante (Opcional):</label>
                                <input type="email" class="form-control" id="email_visitante" name="email_visitante">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="id_servicio" class="form-label">Servicio Requerido:</label>
                        <select class="form-select" id="id_servicio" name="id_servicio" required>
                            <option value="">-- Elige un servicio --</option>
                            <?php mysqli_data_seek($servicios_result, 0); // Reinicia el puntero del resultado ?>
                            <?php while($servicio = $servicios_result->fetch_assoc()): ?>
                                <option value="<?php echo $servicio['id_servicio']; ?>">
                                    <?php echo htmlspecialchars($servicio['nombre_servicio']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_cita" class="form-label">Fecha de la Cita:</label>
                            <input type="date" class="form-control" id="fecha_cita" name="fecha_cita" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                         <div class="col-md-6 mb-3">
                             <label for="hora_cita" class="form-label">Hora de la Cita:</label>
                             <input type="time" class="form-control" id="hora_cita" name="hora_cita" required>
                         </div>
                    </div>

                     <div class="mb-3">
                        <label for="comentarios_paciente" class="form-label">Notas / Comentarios (Opcional):</label>
                        <textarea class="form-control" id="comentarios_paciente" name="comentarios_paciente" rows="2"></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="gestion_citas.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">Agendar Cita</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Seleccionamos los radio buttons y los divs a controlar
    const radioRegistrado = document.getElementById('tipo_registrado');
    const radioVisitante = document.getElementById('tipo_visitante');
    const camposRegistrado = document.getElementById('campos_registrado');
    const camposVisitante = document.getElementById('campos_visitante');
    
    // Inputs dentro de los divs (para hacerlos required/not required)
    const selectUsuario = document.getElementById('id_usuario');
    const inputNombreVisitante = document.getElementById('nombre_visitante');

    function toggleCampos() {
        if (radioRegistrado.checked) {
            camposRegistrado.style.display = 'block'; // Muestra campos registrado
            camposVisitante.style.display = 'none';   // Oculta campos visitante
            selectUsuario.required = true;           // El select es obligatorio
            inputNombreVisitante.required = false;   // El nombre visitante no
        } else {
            camposRegistrado.style.display = 'none';  // Oculta campos registrado
            camposVisitante.style.display = 'block'; // Muestra campos visitante
            selectUsuario.required = false;          // El select no es obligatorio
            inputNombreVisitante.required = true;    // El nombre visitante sí
        }
    }

    // Añadimos un 'listener' a los radio buttons para que llamen a la función cuando cambien
    radioRegistrado.addEventListener('change', toggleCampos);
    radioVisitante.addEventListener('change', toggleCampos);

    // Ejecutamos la función una vez al cargar la página para establecer el estado inicial
    toggleCampos(); 
</script>

<?php
// INCLUIR PIE DE PÁGINA Y CERRAR CONEXIÓN
if(isset($conn)){
    $conn->close();
}
include("../includes/footer.php");
?>