<?php

require_once __DIR__ . '/../Modelos/SecretariasM.php'; // Ruta correcta para incluir el modelo de secretarias

class SecretariasC {

    // Ingresar secretaria (por ejemplo, login de secretarias)
    static public function IngresarSecretariaC($usuario, $clave) {
        $tablaBD = "secretarias"; // Nombre de la tabla de las secretarias

        $datosC = array("usuario" => $usuario, "clave" => $clave);
        $respuesta = SecretariasM::IngresarSecretariaM($tablaBD, $datosC);
        
        if ($respuesta) {
            return $respuesta; // Devuelve la información de la secretaria si es válida
        }

        return false; // Si no se encuentra la secretaria
    }

    // Ver perfil de secretaria
    static public function VerPerfilSecretariaC($id) {
        $tablaBD = "secretarias"; // Nombre de la tabla de las secretarias
        return SecretariasM::VerPerfilSecretariaM($tablaBD, $id); // Devuelve el perfil
    }

    // Actualizar perfil secretaria
    static public function ActualizarPerfilSecretariaC($id, $usuario, $clave, $nombre, $apellido, $foto) {
        $tablaBD = "secretarias"; // Nombre de la tabla de las secretarias

        $datosC = array(
            "id" => $id,
            "usuario" => $usuario,
            "clave" => $clave,
            "nombre" => $nombre,
            "apellido" => $apellido,
            "foto" => $foto
        );
        
        return SecretariasM::ActualizarPerfilSecretariaM($tablaBD, $datosC); // Actualiza el perfil
    }

    // Ver todas las secretarias
    static public function VerSecretariasC() {
        $tablaBD = "secretarias"; // Nombre de la tabla de las secretarias
        return SecretariasM::VerSecretariasM($tablaBD); // Devuelve todas las secretarias
    }

    // Crear nueva secretaria
    static public function CrearSecretariaC($nombre, $apellido, $usuario, $clave, $rol) {
        $tablaBD = "secretarias"; // Nombre de la tabla de las secretarias

        $datosC = array(
            "nombre" => $nombre,
            "apellido" => $apellido,
            "usuario" => $usuario,
            "clave" => $clave,
            "rol" => $rol
        );
        
        return SecretariasM::CrearSecretariaM($tablaBD, $datosC); // Crea una nueva secretaria
    }

    // Eliminar secretaria
    static public function EliminarSecretariaC($id) {
        $tablaBD = "secretarias"; // Nombre de la tabla de las secretarias
        return SecretariasM::BorrarSecretariaM($tablaBD, $id); // Elimina una secretaria
    }
}
