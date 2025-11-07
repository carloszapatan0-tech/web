<?php
/*
 * Archivo: pages/gestionar_expediente.php
 * Formulario de admin para crear o actualizar el expediente
 * médico (diagnóstico, tratamiento) de una cita.
 */

include("../includes/header.php");
include("../includes/db.php");

// --- SEGURIDAD Y VALIDACIÓN DE ENTRADA ---
// Proteger página solo para admin
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}

// Validar que el ID de la cita en la URL sea un número entero válido
if (!isset($_GET['id_cita']) || !filter_var($_GET['id_cita'], FILTER_VALIDATE_INT)) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ID de cita no válido. <a href='gestion_citas.php'>Volver</a>.</div></div>";
    include("../includes/footer.php");
    exit();
}
$id_cita_expediente = (int)$_GET['id_cita'];

$mensaje = ""; // Para mensajes de éxito o error

// --- LÓGICA DE GUARDADO (POST) ---
// Se ejecuta cuando el admin envía el formulario con los datos del expediente.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Recolección de Datos ---
    $diagnostico = trim($_POST['diagnostico']);
    $tratamiento = trim($_POST['tratamiento_realizado']);
    $notas = trim($_POST['notas_doctor']);

    // --- Lógica "UPSERT" (INSERT... ON DUPLICATE KEY UPDATE) ---
    // Esta consulta es muy eficiente.
    // Intenta INSERTAR un nuevo registro.
    // Si falla porque la 'id_cita' ya existe,
    //    entonces ejecuta la parte de UPDATE en su lugar.
    $stmt_upsert = $conn->prepare("
        INSERT INTO expedientes (id_cita, diagnostico, tratamiento_realizado, notas_doctor)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            diagnostico = VALUES(diagnostico),
            tratamiento_realizado = VALUES(tratamiento_realizado),
            notas_doctor = VALUES(notas_doctor)
    ");
    
    // bind_param: i = integer, s = string, s = string, s = string
    $stmt_upsert->bind_param("isss", $id_cita_expediente, $diagnostico, $tratamiento, $notas);

    if ($stmt_upsert->execute()) {
        $mensaje = "<div class='alert alert-success'>Expediente guardado correctamente.</div>";

        // --- Tarea Automática: Marcar Cita como 'Completada' ---
        // Después de guardar un expediente, actualizamos la cita a 'Completada'.
        $stmt_update_cita = $conn->prepare("UPDATE citas SET estado_cita = 'Completada' WHERE id_cita = ?");
        $stmt_update_cita->bind_param("i", $id_cita_expediente);
        if ($stmt_update_cita->execute()) {
            $mensaje .= "<div class='alert alert-info'>La cita ha sido marcada como 'Completada'.</div>";
        }
        $stmt_update_cita->close();
        
    } else {
        $mensaje = "<div class='alert alert-danger'>Error al guardar el expediente: " . $stmt_upsert->error . "</div>";
    }
    $stmt_upsert->close();
}

// --- CARGAR DATOS (GET) ---
// Obtenemos la información de la cita Y del expediente (si existe).
// Usamos LEFT JOIN en 'expedientes' porque puede que aún no exista un registro allí.
// 
$stmt_load = $conn->prepare("
    SELECT 
        c.id_cita, c.fecha_cita, c.hora_cita,
        s.nombre_servicio,
        u.nombre as nombre_usuario, u.apellido_paterno,
        c.nombre_visitante,
        e.diagnostico, e.tratamiento_realizado, e.notas_doctor
    FROM citas c
    JOIN servicios s ON c.id_servicio = s.id_servicio
    LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
    LEFT JOIN expedientes e ON c.id_cita = e.id_cita
    WHERE c.id_cita = ?
");
$stmt_load->bind_param("i", $id_cita_expediente);
$stmt_load->execute();
$datos_cita = $stmt_load->get_result()->fetch_assoc();
$stmt_load->close();

// Si la cita no existe, detenemos la página.
if (!$datos_cita) {
    echo "<div class'container mt-5'><div class='alert alert-danger'>No se encontró la cita solicitada. <a href='gestion_citas.php'>Volver</a>.</div></div>";
    include("../includes/footer.php");
    exit();
}
?>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Expediente Médico de Cita (ID: <?php echo $id_cita_expediente; ?>)</h3>
            </div>
            <div class="card-body p-4">

                <a href="gestion_citas.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Volver a Gestión de Citas</a>

                <?php echo $mensaje; // Muestra mensajes de éxito/error ?>
                
                <fieldset class="border p-3 mb-4 rounded">
                    <legend class="w-auto px-2 fs-6">Información de la Cita</legend>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Paciente:</strong> 
                                <?php 
                                if ($datos_cita['nombre_usuario']) {
                                    echo htmlspecialchars($datos_cita['nombre_usuario'] . ' ' . $datos_cita['apellido_paterno']);
                                } else {
                                    echo htmlspecialchars($datos_cita['nombre_visitante']) . " (Visitante)";
                                } 
                                ?>
                            </p>
                            <p class="mb-0"><strong>Servicio:</strong> <?php echo htmlspecialchars($datos_cita['nombre_servicio']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Fecha:</strong> <?php echo date("d/m/Y", strtotime($datos_cita['fecha_cita'])); ?></p>
                            <p class="mb-0"><strong>Hora:</strong> <?php echo date("h:i A", strtotime($datos_cita['hora_cita'])); ?></p>
                        </div>
                    </div>
                </fieldset>

                <form action="gestionar_expediente.php?id_cita=<?php echo $id_cita_expediente; ?>" method="POST">
                    
                    <div class="mb-3">
                        <label for="diagnostico" class="form-label fs-5">Diagnóstico</label>
                        <textarea class="form-control" id="diagnostico" name="diagnostico" rows="4"><?php 
                            // Pre-llenamos el campo si ya existen datos
                            echo htmlspecialchars($datos_cita['diagnostico'] ?? ''); 
                        ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="tratamiento_realizado" class="form-label fs-5">Tratamiento Realizado</label>
                        <textarea class="form-control" id="tratamiento_realizado" name="tratamiento_realizado" rows="4"><?php 
                            echo htmlspecialchars($datos_cita['tratamiento_realizado'] ?? ''); 
                        ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notas_doctor" class="form-label fs-5">Notas Adicionales / Receta</label>
                        <textarea class="form-control" id="notas_doctor" name="notas_doctor" rows="3"><?php 
                            echo htmlspecialchars($datos_cita['notas_doctor'] ?? ''); 
                        ?></textarea>
                    </div>

                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="imprimir_expediente.php?id_cita=<?php echo $id_cita_expediente; ?>" class="btn btn-secondary" target="_blank">
                            <i class="fas fa-print me-2"></i>Imprimir Expediente
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Guardar Expediente
                        </button>
                    </div>
                </form>

            </div> </div> </div> </div> <?php
// --- CIERRE DE CONEXIONES ---
if(isset($conn)){
    $conn->close();
}
include("../includes/footer.php");
?>