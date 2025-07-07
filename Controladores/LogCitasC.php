<?php
class LogCitasC {

    // Ver todos los logs
    static public function VerLogsCitasC() {
        return LogCitasM::VerLogsCitasM();
    }

    // Registrar un cambio en la cita
    static public function RegistrarCambioCitaC($id_cita, $campo, $valor_anterior, $valor_nuevo) {
        // Información del usuario desde la sesión
        $usuario = $_SESSION["usuario"];
        $id_usuario = $_SESSION["id"];
        $rol_usuario = $_SESSION["rol"];

        return LogCitasM::RegistrarCambioCitaM(
            $id_cita, $campo, $valor_anterior, $valor_nuevo, $usuario, $id_usuario, $rol_usuario
        );
    }
}
