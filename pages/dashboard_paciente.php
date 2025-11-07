<?php
/*
 * Archivo: pages/dashboard_paciente.php
 * Panel principal para el paciente.
 */

include("../includes/header.php"); // Carga el header y session_start()
include("../includes/db.php");

// ¡SEGURIDAD! PROTEGER LA PÁGINA
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'paciente') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}
?>

<div class="container mt-5">
    <h1 class="display-5">Mi Panel de Paciente</h1>
    <p class="lead">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?>.</p>
    <hr>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                 <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><i class="fas fa-calendar-plus me-2"></i>Agendar Nueva Cita</h5>
                    <p class="card-text">Busca un horario disponible y solicita tu próxima visita.</p>
                    <a href="<?php echo $base_url; ?>pages/agendar_cita.php" class="btn btn-success mt-auto">Agendar Ahora</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                 <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><i class="fas fa-history me-2"></i>Mis Citas</h5>
                    <p class="card-text">Revisa el historial y el estado de todas tus citas.</p>
                    <a href="<?php echo $base_url; ?>pages/mis_citas.php" class="btn btn-primary mt-auto">Ver Mis Citas</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// INCLUIR PIE DE PÁGINA
if(isset($conn)){
    $conn->close();
}
include("../includes/footer.php");
?>