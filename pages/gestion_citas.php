<?php
/*
 * Archivo: pages/gestion_citas.php
 * Panel de admin para Ver, Aceptar, Modificar y Eliminar citas.
 */

include("../includes/header.php"); // Carga el header, la sesión y la configuración base.
include("../includes/db.php");     // Carga la conexión a la base de datos.

// --- SEGURIDAD ---
// Se asegura de que solo los usuarios con rol 'administrador' puedan ver esta página.
// Si un paciente o visitante intenta entrar, lo redirige al login.
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}

// Variable para mostrar mensajes de éxito o error al usuario.
$mensaje = "";

// --- LÓGICA DE ACCIONES (POST) ---
// Este bloque de código se ejecuta SOLO si el administrador ha enviado un formulario
// desde esta misma página (ya sea para cambiar el estado o para eliminar una cita).
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- ACCIÓN: CAMBIAR ESTADO DE CITA ---
    // Verifica si se envió el formulario del 'select' de estado.
    if (isset($_POST['id_cita_estado']) && isset($_POST['nuevo_estado'])) {
        $id_cita_actualizar = $_POST['id_cita_estado'];
        $nuevo_estado = $_POST['nuevo_estado'];
        // Medida de seguridad: define los únicos valores permitidos para el estado.
        $estados_validos = ['Pendiente', 'Confirmada', 'Cancelada', 'Completada'];

        // Si el estado recibido es uno de los válidos, procede a actualizar.
        if (in_array($nuevo_estado, $estados_validos)) {
            $stmt_update = $conn->prepare("UPDATE citas SET estado_cita = ? WHERE id_cita = ?");
            $stmt_update->bind_param("si", $nuevo_estado, $id_cita_actualizar);
            if ($stmt_update->execute()) {
                $mensaje = "<div class='alert alert-success'>Estado de la cita actualizado.</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al actualizar el estado.</div>";
            }
            $stmt_update->close();
        } else {
             $mensaje = "<div class='alert alert-danger'>Estado no válido.</div>";
        }
    }

    // --- ACCIÓN: ELIMINAR CITA ---
    // Verifica si se envió el formulario del botón de eliminar.
    if (isset($_POST['id_cita_delete'])) {
        $id_cita_eliminar = $_POST['id_cita_delete'];
        
        // Prepara y ejecuta la consulta DELETE de forma segura.
        // Gracias a 'ON DELETE CASCADE' en la tabla 'expedientes', si borramos
        // esta cita, su expediente asociado (si existe) también se borrará.
        $stmt_delete = $conn->prepare("DELETE FROM citas WHERE id_cita = ?");
        $stmt_delete->bind_param("i", $id_cita_eliminar);
        if ($stmt_delete->execute()) {
            $mensaje = "<div class='alert alert-success'>Cita eliminada correctamente.</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al eliminar la cita.</div>";
        }
        $stmt_delete->close();
    }
}


// --- LÓGICA DE BÚSQUEDA Y PAGINACIÓN (GET) ---
// Esta sección prepara los datos para MOSTRAR la tabla de citas.

// --- Configuración de Paginación ---
$resultados_por_pagina = 10; // ¿Cuántas citas mostrar por página?
// Lee el número de página de la URL (ej: ?pagina=2), si no existe, es la página 1.
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) { $pagina_actual = 1; }
// Calcula desde qué registro empezar en la consulta SQL.
// Para página 1, empieza en 0. Para página 2, empieza en 10, etc.
$inicio = ($pagina_actual - 1) * $resultados_por_pagina;

// --- Configuración de Búsqueda ---
// Lee el término de búsqueda de la URL (ej: ?search=Juan).
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql_where = ""; // Fragmento SQL para la condición WHERE (inicialmente vacío).
$params = [];    // Array para los parámetros de la consulta preparada.
$types = "";     // String para los tipos de los parámetros ('s' para string, 'i' para integer).

// Si el admin buscó algo, construimos la condición WHERE.
if (!empty($search_term)) {
    // Buscamos el término en el nombre/apellido del usuario, nombre del visitante o nombre del servicio.
    $sql_where = " WHERE (u.nombre LIKE ? OR u.apellido_paterno LIKE ? OR c.nombre_visitante LIKE ? OR s.nombre_servicio LIKE ?)";
    $like_term = "%" . $search_term . "%"; // Añadimos '%' para buscar coincidencias parciales.
    array_push($params, $like_term, $like_term, $like_term, $like_term);
    $types = "ssss"; // 4 strings.
}

// --- Construcción de la Consulta Principal ---
// Fragmento SQL que une las tablas para poder obtener los nombres (de usuario y servicio).
// Usamos LEFT JOIN para usuarios, porque una cita puede no tener un usuario asociado (si es visitante).
$sql_from_join = "FROM citas c 
                  LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario 
                  JOIN servicios s ON c.id_servicio = s.id_servicio";

// Contar el TOTAL de resultados para la paginación.
$stmt_total = $conn->prepare("SELECT COUNT(c.id_cita) as total " . $sql_from_join . $sql_where);
if (!empty($params)) {
    $stmt_total->bind_param($types, ...$params);
}
$stmt_total->execute();
$total_citas = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_citas / $resultados_por_pagina); // Calcula cuántas páginas habrá.
$stmt_total->close();

// Obtener solo las citas para la PÁGINA ACTUAL.
$sql_select = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.estado_cita, 
                      s.nombre_servicio, 
                      u.nombre as nombre_usuario, u.apellido_paterno, 
                      c.nombre_visitante ";
// Ordena por fecha y hora descendente y limita los resultados para la paginación.
$sql_order_limit = " ORDER BY c.fecha_cita DESC, c.hora_cita DESC LIMIT ?, ?";

// Añadimos los tipos y parámetros para el LIMIT.
$types .= "ii"; // 'i' por el ?,? del LIMIT
array_push($params, $inicio, $resultados_por_pagina);

// Ejecutamos la consulta final para obtener los datos a mostrar.
$stmt = $conn->prepare($sql_select . $sql_from_join . $sql_where . $sql_order_limit);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado_citas = $stmt->get_result();

?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2>Gestión de Citas</h2>
        <a href="agendar_cita_admin.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Añadir Nueva Cita
        </a>
    </div>
    <a href="dashboard_admin.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Volver al Panel</a>

    <?php echo $mensaje; // Muestra mensajes de éxito/error. ?>

    <div class="card mb-4">
        <div class="card-body">
            <form action="gestion_citas.php" method="GET" class="d-flex">
                <input class="form-control me-2" type="search" placeholder="Buscar por paciente o servicio..." 
                       name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                <button class="btn btn-primary" type="submit">Buscar</button>
                <a href="gestion_citas.php" class="btn btn-secondary ms-2">Limpiar</a>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Paciente / Visitante</th>
                    <th>Servicio</th>
                    <th>Fecha/Hora</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado_citas->num_rows > 0): // Si hay citas, las mostramos. ?>
                    <?php while ($cita = $resultado_citas->fetch_assoc()): // Recorremos cada cita. ?>
                        <tr>
                            <td><?php echo $cita['id_cita']; ?></td>
                            <td>
                                <?php // Muestra el nombre del paciente y si es Registrado o Visitante.
                                if ($cita['nombre_usuario']) {
                                    echo htmlspecialchars($cita['nombre_usuario'] . ' ' . $cita['apellido_paterno']) . " <span class='badge bg-light text-dark'>Registrado</span>";
                                } else {
                                    echo htmlspecialchars($cita['nombre_visitante']) . " <span class='badge bg-light text-dark'>Visitante</span>";
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($cita['nombre_servicio']); ?></td>
                            <td>
                                <?php echo date("d/m/Y", strtotime($cita['fecha_cita'])); ?>
                                <small class="text-muted d-block"><?php echo date("h:i A", strtotime($cita['hora_cita'])); ?></small>
                            </td>
                            <td>
                                <form action="gestion_citas.php?pagina=<?php echo $pagina_actual; ?>&search=<?php echo htmlspecialchars($search_term); ?>" method="POST">
                                    <input type="hidden" name="id_cita_estado" value="<?php echo $cita['id_cita']; ?>">
                                    <div class="input-group input-group-sm">
                                        <select name="nuevo_estado" class="form-select" onchange="this.form.submit()">
                                            <option value="Pendiente" <?php echo ($cita['estado_cita'] == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="Confirmada" <?php echo ($cita['estado_cita'] == 'Confirmada') ? 'selected' : ''; ?>>Confirmada</option>
                                            <option value="Cancelada" <?php echo ($cita['estado_cita'] == 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                                            <option value="Completada" <?php echo ($cita['estado_cita'] == 'Completada') ? 'selected' : ''; ?>>Completada</option>
                                        </select>
                                    </div>
                                </form>
                            </td>
                            <td class="text-center">
                                <?php if ($cita['estado_cita'] == 'Confirmada' || $cita['estado_cita'] == 'Completada'): ?>
                                    <a href="gestionar_expediente.php?id_cita=<?php echo $cita['id_cita']; ?>" class="btn btn-info btn-sm" title="Ver/Editar Expediente">
                                        <i class="fas fa-file-medical"></i> </a>
                                <?php else: ?>
                                    <button class="btn btn-info btn-sm" title="El expediente se activa al confirmar la cita" disabled>
                                        <i class="fas fa-file-medical"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <a href="modificar_cita.php?id_cita=<?php echo $cita['id_cita']; ?>" class="btn btn-primary btn-sm" title="Modificar Cita">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <form action="gestion_citas.php?pagina=<?php echo $pagina_actual; ?>&search=<?php echo htmlspecialchars($search_term); ?>" method="POST" class="d-inline-block" 
                                      onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta cita permanentemente?');">
                                    <input type="hidden" name="id_cita_delete" value="<?php echo $cita['id_cita']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Eliminar Cita">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: // Si no hay citas, muestra un mensaje. ?>
                    <tr>
                        <td colspan="6" class="text-center">No se encontraron citas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_paginas > 1): ?>
    <nav aria-label="Navegación de páginas" class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php if($pagina_actual <= 1){ echo 'disabled'; } ?>">
                <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&search=<?php echo urlencode($search_term); ?>">Anterior</a>
            </li>
            <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?php if($pagina_actual == $i) {echo 'active'; } ?>">
                    <a class="page-link" href="?pagina=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php if($pagina_actual >= $total_paginas) { echo 'disabled'; } ?>">
                <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&search=<?php echo urlencode($search_term); ?>">Siguiente</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php
// --- CIERRE DE CONEXIONES ---
$stmt->close();
$conn->close();
include("../includes/footer.php");
?>