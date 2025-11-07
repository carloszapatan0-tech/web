<?php
/*
 * Archivo: includes/footer.php
 * Cierra las etiquetas HTML y carga los scripts de JavaScript.
 */
?>

    </main> <footer class="bg-dark text-white text-center py-3 mt-4">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Consultorio Dental. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $base_url; ?>js/validation.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const serviceModal = document.getElementById('serviceModal');

        // Solo ejecutar si el modal existe en esta página (servicios.php)
        if (serviceModal) {

            // Función para formatear el costo (ej: 800 -> $800.00 MXN)
            function formatCurrency(value) {
                if (value === null || value === undefined) return 'N/A';
                // Usamos toLocaleString para formato local (puede variar según el navegador)
                return parseFloat(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
            }

            // Evento que se dispara JUSTO ANTES de que el modal se muestre
            serviceModal.addEventListener('show.bs.modal', function (event) {

                // Identificar qué título de tarjeta activó el modal
                const triggerElement = event.relatedTarget;
                const serviceId = triggerElement.getAttribute('data-servicio-id');

                // Obtener los elementos del modal que vamos a rellenar
                const modalTitle = serviceModal.querySelector('#serviceModalLabel');
                const modalImage = serviceModal.querySelector('#modalServiceImage');
                const modalIcon = serviceModal.querySelector('#modalServiceIcon');
                const modalDescription = serviceModal.querySelector('#modalServiceDescription');
                const modalCostItem = serviceModal.querySelector('#modalServiceCostItem');
                const modalCost = serviceModal.querySelector('#modalServiceCost');
                const modalTimeItem = serviceModal.querySelector('#modalServiceTimeItem');
                const modalTime = serviceModal.querySelector('#modalServiceTime');
                const modalAgendarBtn = serviceModal.querySelector('#modalAgendarBtn');

                // Resetear el modal a 'Cargando...'
                modalTitle.textContent = 'Cargando...';
                modalDescription.textContent = 'Cargando descripción...';
                modalImage.style.display = 'none';
                modalImage.src = ''; // Limpiar src por si falla la carga
                modalIcon.style.display = 'block';
                modalCostItem.style.display = 'none';
                modalTimeItem.style.display = 'none';
                modalAgendarBtn.style.display = 'none';

                // Usar fetch para pedir los datos del servicio al servidor
                const fetchUrl = `<?php echo $base_url; ?>api/get_service_details.php?id=${serviceId}`;

                fetch(fetchUrl)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error de red al obtener detalles');
                        }
                        return response.json(); // Esperamos una respuesta JSON
                    })
                    .then(data => {
                        // Rellenar el modal con los datos recibidos
                        if (data && !data.error) {
                            modalTitle.textContent = data.nombre_servicio;
                            modalDescription.textContent = data.descripcion_breve || 'Descripción no disponible.';

                            // Mostrar imagen o icono
                            if (data.foto_url) {
                                modalImage.src = `<?php echo $base_url; ?>img/servicios/${data.foto_url}`;
                                modalImage.alt = `Imagen de ${data.nombre_servicio}`;
                                modalImage.style.display = 'block';
                                modalIcon.style.display = 'none';
                            } else {
                                modalImage.style.display = 'none';
                                modalIcon.style.display = 'block';
                            }

                            // Mostrar costo si existe
                            if (data.costo) {
                                modalCost.textContent = formatCurrency(data.costo);
                                modalCostItem.style.display = 'flex';
                            } else {
                                modalCostItem.style.display = 'none';
                            }

                            // Mostrar tiempo si existe
                            if (data.tiempo_estimado) {
                                modalTime.textContent = data.tiempo_estimado;
                                modalTimeItem.style.display = 'flex';
                            } else {
                                 modalTimeItem.style.display = 'none';
                            }

                            // Mostrar botón 'Agendar' SOLO si el usuario es paciente
                            // Usamos PHP para imprimir la condición directamente en el JS
                            <?php if ($is_logged_in && $rol_usuario === 'paciente'): ?>
                                modalAgendarBtn.href = `<?php echo $base_url; ?>pages/agendar_cita.php?servicio_id=${serviceId}`;
                                modalAgendarBtn.style.display = 'block';
                            <?php endif; ?>

                        } else {
                            // Mostrar error si la API devolvió un error JSON
                            modalTitle.textContent = 'Error';
                            modalDescription.textContent = data.error || 'No se pudieron cargar los detalles del servicio.';
                        }
                    })
                    .catch(error => {
                        // Mostrar error si el fetch falló (ej: red o JSON mal formado)
                        console.error('Error al obtener detalles del servicio:', error);
                        modalTitle.textContent = 'Error de Carga';
                        modalDescription.textContent = 'No se pudo contactar al servidor o hubo un problema al obtener los detalles.';
                    });
            }); // Fin del listener 'show.bs.modal'
        } // Fin if (serviceModal)
    }); // Fin DOMContentLoaded
    </script>
    </body>
</html>