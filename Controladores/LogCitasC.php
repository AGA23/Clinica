<?php
// No necesitas require_once aquí gracias a tu autoloader en loader.php

class LogCitasC {

    /**
     * Devuelve todos los logs para ser usados en la vista.
     * Llama al método del modelo.
     */
    static public function VerLogsCitasC() {
        return LogCitasM::VerLogsCitasM();
    }

    /**
     * Registra un cambio en la cita, obteniendo los datos del usuario desde la sesión.
     *
     * @param int $id_cita
     * @param string $campo El campo que se modificó (ej. 'estado', 'Medicamento Recetado')
     * @param string $valor_anterior
     * @param string $valor_nuevo El nuevo valor o la descripción del item añadido.
     * @return bool
     */
    static public function RegistrarCambioCitaC($id_cita, $campo, $valor_anterior, $valor_nuevo) {
        // Obtenemos la información del usuario que está realizando la acción desde la sesión
        $usuario_nombre_completo = $_SESSION["nombre"] . ' ' . $_SESSION["apellido"];
        $id_usuario = $_SESSION["id"];
        $rol_usuario = $_SESSION["rol"];

        return LogCitasM::RegistrarCambioCitaM(
            $id_cita,
            $campo,
            $valor_anterior,
            $valor_nuevo,
            $usuario_nombre_completo,
            $id_usuario,
            $rol_usuario
        );
    }
}