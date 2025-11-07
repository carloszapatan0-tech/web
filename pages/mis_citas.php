<?php
/*
 * Archivo: pages/mis_citas.php
 * Página para que los pacientes vean su historial de citas.
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

// OBTENER EL HISTORIAL DE CITAS DEL PACIENTE
$stmt = $conn->prepare("SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.estado_cita, c.comentarios_paciente, s.nombre_servicio
                         FROM citas c
                         JOIN servicios s ON c.id_servicio = s.id_servicio
                         WHERE c.id_usuario = ?
                         ORDER BY c.fecha_cita DESC, c.hora_cita DESC");
$stmt->bind_param("i", $id_usuario_actual);
$stmt->execute();
$resultado_citas = $stmt->get_result();

?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h2 class="text-center mb-4">Mi Historial de Citas</h2>
            <a href="dashboard_paciente.php" class="btn btn-sm btn-outline-secondary mb-3 d-block mx-auto" style="width: fit-content;"><i class="fas fa-arrow-left me-2"></i>Volver a Mi Panel</a>

            <?php echo $mensaje; ?>

            <?php if ($resultado_citas->num_rows > 0): ?>
                <div class="table-responsive shadow-sm">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Servicio</th>
                                <th>Estado</th>
                                <th>Comentarios</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($cita = $resultado_citas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date("d/m/Y", strtotime($cita['fecha_cita'])); ?></td>
                                    <td><?php echo date("h:i A", strtotime($cita['hora_cita'])); ?></td>
                                    <td><?php echo htmlspecialchars($cita['nombre_servicio']); ?></td>
                                    <td>
                                        <?php
                                        $estado = htmlspecialchars($cita['estado_cita']);
                                        $clase_badge = '';
                                        switch ($estado) {
                                            case 'Pendiente': $clase_badge = 'bg-warning text-dark'; break;
                                            case 'Confirmada': $clase_badge = 'bg-success'; break;
                                            case 'Cancelada': $clase_badge = 'bg-danger'; break;
                                            case 'Completada': $clase_badge = 'bg-info text-dark'; break;
                                            default: $clase_badge = 'bg-secondary'; break;
                                        }
                                        echo "<span class='badge {$clase_badge}'>{$estado}</span>";
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($cita['comentarios_paciente'] ?? 'N/A'); ?></td>
                                    <td class="text-center">
                                        <a href="imprimir_cita.php?id_cita=<?php echo $cita['id_cita']; ?>" class="btn btn-secondary btn-sm" title="Imprimir Comprobante" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center shadow-sm" role="alert">
                    Aún no tienes citas registradas en tu historial.
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="agendar_cita.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-calendar-plus me-2"></i>Agendar Nueva Cita
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$stmt->close();
if(isset($conn)){
    $conn->close();
}
include("../includes/footer.php");
?>