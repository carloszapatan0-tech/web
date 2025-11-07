<?php
/*
 * Archivo: index.php
 * Página principal del sitio.
 */

include("includes/header.php");
include("includes/db.php");

// Hacemos la consulta para obtener TODOS los datos del personal
$personal_result = $conn->query("SELECT * FROM personal ORDER BY id_personal ASC");
$personal_array = [];
if ($personal_result) {
    while ($row = $personal_result->fetch_assoc()) {
        $personal_array[] = $row;
    }
}

?>

<div class="container mt-5">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="display-4">Bienvenido al Consultorio Dental</h1>
            <p class="lead">Gestiona tu salud dental con nuestra plataforma. Registra tu cuenta y accede a nuestros servicios.</p>
            <hr class="my-4">

            <?php if ($is_logged_in): ?>
                <h3 class="mb-3">
                    Hola, <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?>.
                </h3>
                <?php if ($rol_usuario == 'administrador'): ?>
                    <a href="<?php echo $base_url; ?>pages/dashboard_admin.php" class="btn btn-primary btn-lg">Ir a mi Panel de Admin</a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>pages/dashboard_paciente.php" class="btn btn-primary btn-lg">Ir a mi Panel</a>
                <?php endif; ?>
                <a href="<?php echo $base_url; ?>pages/logout.php" class="btn btn-secondary btn-lg">Cerrar Sesión</a>
            <?php else: ?>
                <p>Para comenzar, por favor regístrate o inicia sesión.</p>
                <a href="<?php echo $base_url; ?>pages/login.php" class="btn btn-primary btn-lg">Iniciar Sesión</a>
                <a href="<?php echo $base_url; ?>pages/registro.php" class="btn btn-secondary btn-lg">Registrarse</a>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6 text-center">
            <img src="<?php echo $base_url; ?>img/consultorio_dental_angelica.png" 
                 class="img-fluid rounded shadow-sm" 
                 alt="Imagen del Consultorio Dental">
        </div>
    </div>
</div>

<hr class="my-5">

<div class="container mb-5">
    <h2 class="text-center mb-4">Nuestro Equipo</h2>
    <div class="row justify-content-center">
        <?php 
        if (!empty($personal_array)): 
        ?>
            <?php 
            foreach ($personal_array as $persona): 
            ?>
                <div class="col-md-4 mb-4 d-flex align-items-stretch">
                    <div class="card h-100 shadow-sm w-100">
                        
                        <img src="<?php echo $base_url . 'img/personal/' . htmlspecialchars($persona['foto_url']); ?>" 
                             class="card-img-top mx-auto mt-3 rounded-circle" 
                             alt="Foto de <?php echo htmlspecialchars($persona['nombre']); ?>" 
                             style="width: 150px; height: 150px; object-fit: cover;"> 
                        <div class="card-body text-center d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($persona['nombre']); ?></h5>
                            
                            <?php if (!empty($persona['semestre'])): ?>
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($persona['semestre']); ?></h6>
                            <?php elseif (!empty($persona['puesto'])): ?>
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($persona['puesto']); ?></h6>
                            <?php endif; ?>

                            <ul class="list-group list-group-flush text-start mt-3" style="font-size: 0.9em;">
                                
                                <?php if (!empty($persona['fecha_nacimiento'])): ?>
                                    <?php
                                        $fecha_nac = new DateTime($persona['fecha_nacimiento']);
                                        $hoy = new DateTime();
                                        $edad = $hoy->diff($fecha_nac)->y;
                                    ?>
                                    <li class="list-group-item px-0">
                                        <strong>Edad:</strong> <?php echo $edad; ?> años
                                    </li>
                                <?php endif; ?>

                                <?php if (!empty($persona['email'])): ?>
                                    <li class="list-group-item px-0">
                                        <strong>Email:</strong> 
                                        <a href="mailto:<?php echo htmlspecialchars($persona['email']); ?>"><?php echo htmlspecialchars($persona['email']); ?></a>
                                    </li>
                                <?php endif; ?>

                                <?php if (!empty($persona['pregunta_profesion'])): ?>
                                    <li class="list-group-item px-0 mt-2">
                                        <strong class="d-block">¿Por qué elegiste la profesión?</strong>
                                        <small class="text-muted fst-italic">"<?php echo nl2br(htmlspecialchars($persona['pregunta_profesion'])); ?>"</small>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php 
            endforeach; 
            ?>
        <?php else: ?>
            <p class="text-center text-muted">No se encontró información del equipo.</p>
        <?php endif; ?>
    </div>
</div>

<?php
if(isset($conn)){
    $conn->close();
}
include("includes/footer.php");
?>