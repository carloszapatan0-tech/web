<?php
/*
 * Archivo: includes/header.php
 */

// GESTIÓN DE LA SESIÓN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$base_url = "/";

// VARIABLES DE SESIÓN
$is_logged_in = isset($_SESSION['id_usuario']);
$rol_usuario = $is_logged_in ? $_SESSION['rol_usuario'] : '';

/**
 * Función que devuelve un título de página dinámico.
 */
function getPageTitle() {
    $script_name = basename($_SERVER['SCRIPT_NAME']);
    switch ($script_name) {
        case 'index.php': return 'Inicio | Consultorio Dental';
        case 'login.php': return 'Iniciar Sesión';
        case 'registro.php': return 'Registro de Paciente';
        case 'dashboard_admin.php': return 'Panel de Administración';
        case 'dashboard_paciente.php': return 'Mi Panel';
        case 'servicios.php': return 'Nuestros Servicios';
        default: return 'Consultorio Dental';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getPageTitle(); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="<?php echo $base_url; ?>css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $base_url; ?>">Consultorio Dental</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>pages/servicios.php">Servicios</a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Hola, <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <?php if ($rol_usuario == 'administrador'): ?>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>pages/dashboard_admin.php">Panel de Admin</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>pages/dashboard_paciente.php">Mi Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>pages/logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-2">
                            <a class="btn btn-outline-light btn-sm" href="<?php echo $base_url; ?>pages/registro.php">Registrarse</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-light btn-sm" href="<?php echo $base_url; ?>pages/login.php">Iniciar Sesión</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container mt-4">