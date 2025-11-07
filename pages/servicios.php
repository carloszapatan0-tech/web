<?php
/*
 * Archivo: pages/servicios.php
 * Página pública que muestra los servicios ofrecidos.
 */

include("../includes/header.php");
include("../includes/db.php");

// OBTENER LOS SERVICIOS DE LA BASE DE DATOS
$result_servicios = $conn->query("SELECT * FROM servicios ORDER BY nombre_servicio ASC");

?>

<div class="container mt-5">
    <div class="row text-center mb-4">
        <div class="col">
            <h2>Nuestros Servicios Odontológicos</h2>
            <p class="lead">Haz clic en el nombre de un servicio para ver más detalles.</p> </div>
    </div>

    <div class="row">
        <?php
        // MOSTRAR CADA SERVICIO EN UNA TARJETA
        if ($result_servicios && $result_servicios->num_rows > 0):
            while ($servicio = $result_servicios->fetch_assoc()):
            ?>
                <div class="col-md-4 mb-4 d-flex align-items-stretch">
                    <div class="card h-100 shadow-sm w-100">
                        <?php if (!empty($servicio['foto_url'])): ?>
                            <img src="<?php echo $base_url . 'img/servicios/' . htmlspecialchars($servicio['foto_url']); ?>"
                                 class="card-img-top"
                                 alt="Foto de <?php echo htmlspecialchars($servicio['nombre_servicio']); ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="text-center py-5 bg-light border-bottom"> <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary"
                                style="cursor: pointer;"
                                data-bs-toggle="modal"
                                data-bs-target="#serviceModal"
                                data-servicio-id="<?php echo $servicio['id_servicio']; ?>">
                                <?php echo htmlspecialchars($servicio['nombre_servicio']); ?>
                            </h5>
                            <p class="card-text text-muted flex-grow-1"><?php echo htmlspecialchars($servicio['descripcion_breve'] ?? 'Descripción no disponible.'); ?></p>
                            <ul class="list-group list-group-flush mt-auto">
                                <?php if (!empty($servicio['costo'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0"> Costo Aprox:
                                        <span class="badge bg-success rounded-pill">$<?php echo number_format($servicio['costo'], 2); ?> MXN</span>
                                    </li>
                                <?php endif; ?>
                                <?php if (!empty($servicio['tiempo_estimado'])): ?>
                                     <li class="list-group-item d-flex justify-content-between align-items-center px-0"> Tiempo Estimado:
                                        <span class="badge bg-info rounded-pill"><?php echo htmlspecialchars($servicio['tiempo_estimado']); ?></span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                         <?php if ($is_logged_in && $rol_usuario === 'paciente'): ?>
                            <div class="card-footer text-center">
                                <a href="agendar_cita.php?servicio_id=<?php echo $servicio['id_servicio']; ?>" class="btn btn-primary btn-sm">Agendar este Servicio</a>
                            </div>
                         <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col">
                <div class="alert alert-warning text-center">
                    Actualmente no hay servicios registrados. Vuelve a consultar más tarde.
                </div>
            </div>
        <?php endif; ?>
    </div> </div> <div class="modal fade" id="serviceModal" tabindex="-1" aria-labelledby="serviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="serviceModalLabel">Cargando...</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <img id="modalServiceImage" src="" alt="Imagen del Servicio" class="img-fluid rounded shadow-sm" style="max-height: 300px; display: none;">
                    <i id="modalServiceIcon" class="fas fa-image fa-5x text-muted" style="display: block;"></i>
                </div>
                <p id="modalServiceDescription">Cargando descripción...</p>
                <hr>
                <h6>Detalles:</h6>
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center" id="modalServiceCostItem" style="display: none;">
                        Costo Aprox: <span id="modalServiceCost" class="badge bg-success rounded-pill fs-6"></span> </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center" id="modalServiceTimeItem" style="display: none;">
                        Tiempo Estimado: <span id="modalServiceTime" class="badge bg-info rounded-pill fs-6"></span> </li>
                    </ul>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="agendar_cita.php" id="modalAgendarBtn" class="btn btn-primary" style="display: none;">Agendar este Servicio</a>
            </div>
        </div>
    </div>
</div>
<?php
// INCLUIR PIE DE PÁGINA Y CERRAR CONEXIÓN
if(isset($conn)){
    $conn->close();
}
include("../includes/footer.php");
?>