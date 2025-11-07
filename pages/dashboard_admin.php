<?php
/*
 * Archivo: pages/dashboard_admin.php
 * Panel principal para el administrador.
 */

include("../includes/header.php");
include("../includes/db.php");

// ¡SEGURIDAD! PROTEGER LA PÁGINA
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}
?>

<!-- CONTENIDO DEL PANEL DE ADMIN -->
<div class="container mt-5">
    <h1 class="display-5">Panel de Administración</h1>
    <p class="lead">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?>.</p>
    <hr>

    <!-- Layout de 2x2 (usando col-md-6) -->
    <div class="row">
        
        <!-- Tarjeta Gestión Usuarios -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><i class="fas fa-users-cog me-2"></i>Gestión de Usuarios</h5>
                    <p class="card-text">Ver, dar de alta, modificar roles y eliminar usuarios del sistema.</p>
                    <a href="<?php echo $base_url; ?>pages/gestion_usuarios.php" class="btn btn-primary mt-auto">Ir a Usuarios</a>
                </div>
            </div>
        </div>

        <!-- Tarjeta Gestión Citas -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                 <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>Gestión de Citas</h5>
                    <p class="card-text">Visualizar, añadir, modificar, confirmar o eliminar las citas agendadas.</p>
                    <a href="<?php echo $base_url; ?>pages/gestion_citas.php" class="btn btn-primary mt-auto">Ir a Citas</a>
                </div>
            </div>
        </div>

        <!-- Tarjeta Gestión Servicios -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                 <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><i class="fas fa-briefcase-medical me-2"></i>Gestión de Servicios</h5>
                    <p class="card-text">Añadir, editar o eliminar los servicios ofrecidos en el consultorio.</p>
                    <a href="<?php echo $base_url; ?>pages/gestion_servicios.php" class="btn btn-primary mt-auto">Ir a Servicios</a>
                </div>
            </div>
        </div>

        <!-- ***** Gestión Personal ***** -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                 <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><i class="fas fa-users me-2"></i>Gestión de Personal</h5>
                    <p class="card-text">Añade o edita los perfiles de los especialistas y estudiantes.</p>
                    <a href="<?php echo $base_url; ?>pages/gestion_personal.php" class="btn btn-primary mt-auto">Ir a Personal</a>
                </div>
            </div>
        </div>

    </div> <!-- Fin .row -->
</div> <!-- Fin .container -->

<?php
// INCLUIR PIE DE PÁGINA
if(isset($conn)){
    $conn->close();
}
include("../includes/footer.php");
?>