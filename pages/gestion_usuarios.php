<?php
/*
 * Archivo: pages/gestion_usuarios.php
 * Panel de admin para Ver, Modificar (rol y datos) y Eliminar (baja) usuarios.
 */

include("../includes/header.php");
include("../includes/db.php");

// ¡SEGURIDAD! PROTEGER LA PÁGINA
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'administrador') {
    header("Location: " . $base_url . "pages/login.php");
    exit();
}

$mensaje = "";

// LÓGICA DE ACCIONES (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- ACCIÓN: MODIFICAR ROL ---
    if (isset($_POST['id_usuario_rol'])) {
        $id_usuario_actualizar = $_POST['id_usuario_rol'];
        $nuevo_rol = $_POST['nuevo_rol'];

        if ($id_usuario_actualizar == $_SESSION['id_usuario'] && $nuevo_rol !== 'administrador') {
            $mensaje = "<div class='alert alert-danger'>No puedes quitarte tu propio rol de administrador.</div>";
        } else {
            $stmt_update = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id_usuario = ?");
            $stmt_update->bind_param("si", $nuevo_rol, $id_usuario_actualizar);
            if ($stmt_update->execute()) {
                $mensaje = "<div class='alert alert-success'>Rol del usuario actualizado.</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al actualizar el rol.</div>";
            }
            $stmt_update->close();
        }
    }

    // --- ACCIÓN: DAR DE BAJA (ELIMINAR) ---
    if (isset($_POST['id_usuario_delete'])) {
        $id_usuario_eliminar = $_POST['id_usuario_delete'];

        if ($id_usuario_eliminar == $_SESSION['id_usuario']) {
            $mensaje = "<div class='alert alert-danger'>No puedes eliminar tu propia cuenta.</div>";
        } else {
            $stmt_delete = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt_delete->bind_param("i", $id_usuario_eliminar);
            if ($stmt_delete->execute()) {
                $mensaje = "<div class='alert alert-success'>Usuario eliminado (dado de baja) correctamente.</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al eliminar el usuario.</div>";
            }
            $stmt_delete->close();
        }
    }
}

// LÓGICA DE BÚSQUEDA Y PAGINACIÓN (GET)
$resultados_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) { $pagina_actual = 1; }
$inicio = ($pagina_actual - 1) * $resultados_por_pagina;

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql_where = "";
$params = [];
$types = "";

if (!empty($search_term)) {
    $sql_where = " WHERE nombre LIKE ? OR apellido_paterno LIKE ? OR email LIKE ?";
    $like_term = "%" . $search_term . "%";
    array_push($params, $like_term, $like_term, $like_term);
    $types = "sss";
}

// Contar Total de Usuarios
$stmt_total = $conn->prepare("SELECT COUNT(id_usuario) as total FROM usuarios" . $sql_where);
if (!empty($params)) {
    $stmt_total->bind_param($types, ...$params);
}
$stmt_total->execute();
$total_usuarios = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_usuarios / $resultados_por_pagina);
$stmt_total->close();

// Obtener Usuarios para la Página Actual
$sql_select = "SELECT id_usuario, nombre, apellido_paterno, email, rol, fecha_registro FROM usuarios";
$sql_order_limit = " ORDER BY fecha_registro DESC LIMIT ?, ?";
$types .= "ii";
array_push($params, $inicio, $resultados_por_pagina);

$stmt = $conn->prepare($sql_select . $sql_where . $sql_order_limit);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado_usuarios = $stmt->get_result();

?>

<!-- ESTRUCTURA HTML -->
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2>Gestión de Usuarios</h2>
        <a href="alta_usuario.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Dar de Alta Usuario
        </a>
    </div>
    
    <!-- BOTÓN DE VOLVER -->
    <a href="dashboard_admin.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Volver al Panel</a>

    <?php echo $mensaje; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form action="gestion_usuarios.php" method="GET" class="d-flex">
                <input class="form-control me-2" type="search" placeholder="Buscar por nombre, apellido o email..." 
                       name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                <button class="btn btn-primary" type="submit">Buscar</button>
                <a href="gestion_usuarios.php" class="btn btn-secondary ms-2">Limpiar</a>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre Completo</th>
                    <th>Email</th>
                    <th>Fecha Registro</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado_usuarios->num_rows > 0): ?>
                    <?php while ($usuario = $resultado_usuarios->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $usuario['id_usuario']; ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido_paterno']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo date("d/m/Y", strtotime($usuario['fecha_registro'])); ?></td>
                            
                            <!-- COLUMNA PARA MODIFICAR ROL -->
                            <td>
                                <form action="gestion_usuarios.php?pagina=<?php echo $pagina_actual; ?>&search=<?php echo htmlspecialchars($search_term); ?>" method="POST" class="d-flex">
                                    <input type="hidden" name="id_usuario_rol" value="<?php echo $usuario['id_usuario']; ?>">
                                    <select name="nuevo_rol" class="form-select form-select-sm me-2">
                                        <option value="paciente" <?php echo ($usuario['rol'] == 'paciente') ? 'selected' : ''; ?>>Paciente</option>
                                        <option value="administrador" <?php echo ($usuario['rol'] == 'administrador') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                    <button type="submit" class="btn btn-secondary btn-sm">Guardar</button>
                                </form>
                            </td>

                            <!-- COLUMNA PARA ACCIONES (MODIFICAR Y ELIMINAR) -->
                            <td>
                                <!-- BOTÓN: MODIFICAR DATOS -->
                                <a href="modificar_usuario.php?id_usuario=<?php echo $usuario['id_usuario']; ?>" class="btn btn-primary btn-sm me-1" title="Modificar Datos del Usuario">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <!-- Formulario para DAR DE BAJA (ELIMINAR) -->
                                <form action="gestion_usuarios.php?pagina=<?php echo $pagina_actual; ?>&search=<?php echo htmlspecialchars($search_term); ?>" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Estás seguro de que quieres eliminar a este usuario? Esta acción no se puede deshacer.');">
                                    <input type="hidden" name="id_usuario_delete" value="<?php echo $usuario['id_usuario']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Eliminar Usuario">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No se encontraron usuarios.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <nav aria-label="Navegación de páginas">
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
</div>

<?php
$stmt->close();
$conn->close();
include("../includes/footer.php");
?>