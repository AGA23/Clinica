<?php
// En Controladores/TratamientosC.php (NUEVO ARCHIVO COMPLETO)

class TratamientosC {

    public static function ListarTratamientosC() {
        
        return TratamientosM::ListarTratamientosM();
    }

    public function CrearTratamientoC() {
        if (isset($_POST["crear_tratamiento"])) {
            $nombre = trim($_POST['nombre']);
            if (!empty($nombre)) {
                $respuesta = TratamientosM::CrearTratamientoM($nombre);
                $_SESSION['mensaje_tratamientos'] = $respuesta ? "¡Tratamiento creado correctamente!" : "Error al crear el tratamiento.";
                $_SESSION['tipo_mensaje_tratamientos'] = $respuesta ? "success" : "danger";
            }
            echo '<script>window.location = "tratamientos";</script>';
            exit();
        }
    }

    public function ActualizarTratamientoC() {
        if (isset($_POST["editar_tratamiento"])) {
            $id = $_POST['id_tratamiento_editar'];
            $nombre = trim($_POST['nombre_editar']);

            // ¡NUEVA VERIFICACIÓN DE PERMISO!
            if (!TratamientosM::VerificarPertenenciaTratamientoM($id)) {
                 $_SESSION['mensaje_tratamientos'] = "Error: Permiso denegado para editar este tratamiento.";
                 $_SESSION['tipo_mensaje_tratamientos'] = "danger";
                 echo '<script>window.location = "tratamientos";</script>';
                 exit();
            }

            if (!empty($id) && !empty($nombre)) {
                $respuesta = TratamientosM::ActualizarTratamientoM($id, $nombre);
                $_SESSION['mensaje_tratamientos'] = $respuesta ? "¡Tratamiento actualizado!" : "Error al actualizar.";
                $_SESSION['tipo_mensaje_tratamientos'] = $respuesta ? "success" : "danger";
            }
            echo '<script>window.location = "tratamientos";</script>';
            exit();
        }
    }
}