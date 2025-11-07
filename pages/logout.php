<?php
/*
 * Archivo: pages/logout.php
 * Destruir la sesión del usuario de forma segura.
 */

// Iniciar (o reanudar) la sesión existente.
session_start();

// Eliminar todas las variables de la sesión.
// Esto borra $_SESSION['id_usuario'], $_SESSION['nombre_usuario'], etc.
session_unset();

// Destruir la sesión por completo en el servidor.
// Esto invalida el cookie de sesión del usuario.
session_destroy();

// Redirigir al usuario a la página de inicio.
// Usamos ../ para subir un nivel desde 'pages' al directorio raíz.
header("Location: ../index.php");

// Detener la ejecución del script.
exit();
?>