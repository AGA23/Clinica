<?php
// En Controladores/PlantillasCorreoC.php

class PlantillasCorreoC {

    /**
     * Procesa la actualización de una plantilla de correo desde el formulario de gestión.
     * Recoge los datos del POST, los valida y llama al modelo para guardarlos.
     * Al final, redirige de vuelta a la página de gestión con un mensaje de estado.
     */
    public function ActualizarPlantillaC() {
        // Se ejecuta solo si se ha enviado el formulario y tenemos un identificador.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['identificador'])) {
            
            // 1. Seguridad: Verificar que el usuario tiene permisos para esta acción.
            if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Secretario', 'Administrador'])) {
                // Si no tiene permisos, guardamos un mensaje de error y redirigimos.
                $_SESSION['mensaje_plantillas'] = "Acción no permitida. No tiene los permisos necesarios.";
                $_SESSION['tipo_mensaje_plantillas'] = "danger";
                // Usamos una redirección con JavaScript como en tus otros controladores.
                echo '<script>window.location = "plantillas-correo";</script>';
                exit();
            }

            // 2. Recopilar y validar los datos del formulario.
            $datos = [
                'identificador' => $_POST['identificador'],
                'asunto' => trim($_POST['asunto']),
                'cuerpo_html' => $_POST['cuerpo_html'], // CKEditor ya limpia bastante bien el HTML.
                'tiempo_envio_horas' => filter_input(INPUT_POST, 'tiempo_envio_horas', FILTER_VALIDATE_INT)
            ];

            // 3. Validar que los datos esenciales no estén vacíos.
            if (empty($datos['asunto']) || empty($datos['cuerpo_html']) || $datos['tiempo_envio_horas'] === false) {
                $_SESSION['mensaje_plantillas'] = "Error: Faltan campos obligatorios o el tiempo de envío no es válido.";
                $_SESSION['tipo_mensaje_plantillas'] = "danger";
            } else {
                // 4. Llamar al modelo para que actualice la base de datos.
                $resultado = PlantillasCorreoM::ActualizarPlantillaM($datos);

                if ($resultado) {
                    $_SESSION['mensaje_plantillas'] = "¡Plantilla de correo actualizada correctamente!";
                    $_SESSION['tipo_mensaje_plantillas'] = "success";
                } else {
                    $_SESSION['mensaje_plantillas'] = "Error: No se pudo actualizar la plantilla en la base de datos.";
                    $_SESSION['tipo_mensaje_plantillas'] = "danger";
                }
            }

            // 5. Redirigir a la misma página para mostrar el mensaje y evitar reenvío de formulario.
            echo '<script>window.location = "plantillas-correo";</script>';
            exit();
        }
    }

} 