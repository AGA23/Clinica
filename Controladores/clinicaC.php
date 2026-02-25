<?php


class ClinicaC {

    
    public function VerClinicaC() {
        // Usa el modelo ClinicaM que ya creamos.
        return ClinicaM::ObtenerDatosGlobalesM();
    }

  public function ActualizarClinicaC() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_clinica']) && $_SESSION['rol'] === 'Administrador') {
        
        
        $rutaLogo = $_POST['logoActual']; 

        // Verificamos si se subió un archivo nuevo y si no hubo errores
        if (isset($_FILES['logoNuevo']['tmp_name']) && !empty($_FILES['logoNuevo']['tmp_name']) && $_FILES['logoNuevo']['error'] == 0) {

            // Definimos el directorio de destino
            $directorio = "Vistas/img/plantilla";
            
            // Borramos el logo anterior si existe y no es la imagen por defecto
            if (!empty($_POST['logoActual']) && $_POST['logoActual'] != 'Vistas/img/user-default.png') {
                if(file_exists($_POST['logoActual'])) {
                    unlink($_POST['logoActual']);
                }
            }

            // Creamos un nombre de archivo único para evitar problemas de caché
            // Ej: logo_1678886400.png
            $nombreArchivo = "logo_" . time();
            $tipoArchivo = $_FILES['logoNuevo']['type'];

            if ($tipoArchivo == "image/jpeg") {
                $rutaLogo = $directorio . $nombreArchivo . ".jpg";
            } elseif ($tipoArchivo == "image/png") {
                $rutaLogo = $directorio . $nombreArchivo . ".png";
            } else {
                // Si el tipo de archivo no es válido, detenemos y mostramos un error
                $_SESSION['mensaje_clinica'] = "Error: Formato de imagen no válido. Solo se permiten JPG y PNG.";
                $_SESSION['tipo_mensaje_clinica'] = "danger";
                echo '<script>window.location = "clinica";</script>';
                exit();
            }

            // Movemos el archivo subido al destino final
            if (!move_uploaded_file($_FILES['logoNuevo']['tmp_name'], $rutaLogo)) {
                // Si falla el movimiento, mantenemos el logo anterior
                $rutaLogo = $_POST['logoActual']; 
                // Podrías añadir un mensaje de error aquí también
            }
        }

        // Preparamos los datos para el modelo
        $datos = [
            "intro" => trim($_POST["nombre_clinica"]),
            "cuit" => trim($_POST["cuit_clinica"]),
            "correo" => trim($_POST["correo_clinica"]),
            "logo" => $rutaLogo // Usamos la nueva ruta (o la antigua si no se subió nada)
        ];

        $resultado = ClinicaM::ActualizarDatosGlobalesM($datos);

        if ($resultado) {
            $_SESSION['mensaje_clinica'] = "¡Los datos de la clínica han sido actualizados!";
            $_SESSION['tipo_mensaje_clinica'] = "success";
        } else {
            $_SESSION['mensaje_clinica'] = "Error: No se pudieron actualizar los datos.";
            $_SESSION['tipo_mensaje_clinica'] = "danger";
        }

        echo '<script>window.location = "clinica";</script>';
        exit();
    }
}

}