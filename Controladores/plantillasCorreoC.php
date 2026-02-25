<?php

class PlantillasCorreoC {

 
public function ActualizarPlantillaC() {
    // Se ejecuta solo si se ha enviado el formulario y tenemos un identificador.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['identificador'])) {
        
        // 1. Seguridad: Verificar que el usuario tiene permisos para esta acción.
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Secretario', 'Administrador'])) {
            $_SESSION['mensaje_plantillas'] = "Acción no permitida. No tiene los permisos necesarios.";
            $_SESSION['tipo_mensaje_plantillas'] = "danger";
            echo '<script>window.location = "plantillas-correo";</script>';
            exit();
        }

        // 2. Recopilar y validar los datos del formulario.
        $datos = [
            'identificador' => $_POST['identificador'],
            'asunto' => trim($_POST['asunto']),
            'cuerpo_html' => $_POST['cuerpo_html'],
            'tiempo_envio_horas' => filter_input(INPUT_POST, 'tiempo_envio_horas', FILTER_VALIDATE_INT)
        ];

        // 3. Validar que los datos esenciales no estén vacíos.
        if (empty($datos['asunto']) || empty($datos['cuerpo_html']) || $datos['tiempo_envio_horas'] === false) {
            $_SESSION['mensaje_plantillas'] = "Error: Faltan campos obligatorios o el tiempo de envío no es válido.";
            $_SESSION['tipo_mensaje_plantillas'] = "danger";
        } else {
            // 4. Llamar al modelo para que actualice la base de datos.
            // [CORREGIDO] Se usa el nombre de clase correcto: "plantillascorreoM"
            $resultado = plantillacorreoM::ActualizarPlantillaM($datos);

            if ($resultado) {
                $_SESSION['mensaje_plantillas'] = "¡Plantilla de correo actualizada correctamente!";
                $_SESSION['tipo_mensaje_plantillas'] = "success";
            } else {
                $_SESSION['mensaje_plantillas'] = "Error: No se pudo actualizar la plantilla en la base de datos.";
                $_SESSION['tipo_mensaje_plantillas'] = "danger";
            }
        }

        // 5. Redirigir a la misma página para mostrar el mensaje.
        echo '<script>window.location = "plantillas-correo";</script>';
        exit();
    }
}
   
} 