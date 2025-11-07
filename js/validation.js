/*
 * Archivo: js/validation.js
 * Realizar validaciones de formulario en el lado del cliente
 * para mejorar la experiencia de usuario en la página de registro.
 */

// 'DOMContentLoaded' es un evento que espera a que todo el HTML
// de la página se haya cargado antes de ejecutar el script.
// Esto previene errores si el script intenta buscar un formulario que aún no existe.
document.addEventListener('DOMContentLoaded', function() {

    // BUSCAR EL FORMULARIO DE REGISTRO
    // Buscamos en la página un elemento con el ID 'formRegistro'.
    const formRegistro = document.querySelector('#formRegistro');

    // EJECUTAR CÓDIGO SOLO SI ESTAMOS EN LA PÁGINA DE REGISTRO
    // Este 'if' es clave: como este script se carga en TODAS las páginas (gracias al footer),
    // nos aseguramos de que el código de validación solo se ejecute si encuentra
    // el formulario de registro, evitando errores en 'index.php' o 'login.php'.
    if (formRegistro) {
        
        // Seleccionamos los campos con los que vamos a trabajar.
        const password = document.querySelector('#password');
        const confirmPassword = document.querySelector('#confirmPassword');
        
        // Seleccionamos los 'divs' donde mostraremos los mensajes de error/ayuda.
        const passwordHelp = document.querySelector('#passwordHelp');
        const confirmHelp = document.querySelector('#confirmHelp');

        /**
         * Función que se ejecuta CADA VEZ que el usuario suelta una tecla
         * en cualquiera de los campos de contraseña.
         */
        function validarContraseñas() {
            
            // --- Validación de Longitud (en el primer campo) ---
            if (password.value.length > 0 && password.value.length < 6) {
                // Si la contraseña es muy corta, muestra un error.
                passwordHelp.textContent = 'Debe tener al menos 6 caracteres.';
                passwordHelp.className = 'form-text text-danger'; // Clase de Bootstrap para error.
            } else if (password.value.length >= 6) {
                // Si es válida, muestra un mensaje de éxito.
                passwordHelp.textContent = 'Contraseña válida.';
                passwordHelp.className = 'form-text text-success'; // Clase de Bootstrap para éxito.
            } else {
                // Si está vacío, no muestra nada.
                passwordHelp.textContent = '';
                passwordHelp.className = 'form-text';
            }

            // --- Validación de Coincidencia (en el segundo campo) ---
            if (confirmPassword.value.length > 0) {
                if (password.value !== confirmPassword.value) {
                    // Si no coinciden, muestra un error.
                    confirmHelp.textContent = 'Las contraseñas no coinciden.';
                    confirmHelp.className = 'form-text text-danger';
                } else {
                    // Si coinciden, muestra un mensaje de éxito.
                    confirmHelp.textContent = 'Las contraseñas coinciden.';
                    confirmHelp.className = 'form-text text-success';
                }
            } else {
                // Si está vacío, no muestra nada.
                confirmHelp.textContent = '';
                confirmHelp.className = 'form-text';
            }
        }

        // ASIGNAR LOS EVENTOS
        // 'keyup' es el evento que se dispara cuando el usuario suelta una tecla.
        // Asignamos nuestra función 'validarContraseñas' a ambos campos.
        password.addEventListener('keyup', validarContraseñas);
        confirmPassword.addEventListener('keyup', validarContraseñas);
    }

});