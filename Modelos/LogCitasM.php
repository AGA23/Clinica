<?php
require_once "ConexionBD.php";

class LogCitasM {

    // Obtener todos los logs
    static public function VerLogsCitasM() {
        $pdo = ConexionBD::cBD()->prepare("SELECT lc.*, c.fecha, c.hora, d.nombre AS doctor, p.nombre AS paciente
                                           FROM historial_cambios_citas lc
                                           JOIN citas c ON lc.id_cita = c.id
                                           JOIN doctores d ON c.id_doctor = d.id
                                           JOIN pacientes p ON c.id_paciente = p.id
                                           ORDER BY lc.fecha_cambio DESC");
        $pdo->execute();
        return $pdo->fetchAll();
    }

    // Registrar un nuevo cambio en la cita
    static public function RegistrarCambioCitaM($id_cita, $campo_modificado, $valor_anterior, $valor_nuevo, $usuario_modifico, $id_usuario, $rol_usuario) {
        $pdo = ConexionBD::cBD()->prepare("INSERT INTO historial_cambios_citas
            (id_cita, fecha_cambio, campo_modificado, valor_anterior, valor_nuevo, usuario_modifico, id_usuario, rol_usuario)
            VALUES (:id_cita, NOW(), :campo_modificado, :valor_anterior, :valor_nuevo, :usuario_modifico, :id_usuario, :rol_usuario)");

        $pdo->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
        $pdo->bindParam(":campo_modificado", $campo_modificado, PDO::PARAM_STR);
        $pdo->bindParam(":valor_anterior", $valor_anterior, PDO::PARAM_STR);
        $pdo->bindParam(":valor_nuevo", $valor_nuevo, PDO::PARAM_STR);
        $pdo->bindParam(":usuario_modifico", $usuario_modifico, PDO::PARAM_STR);
        $pdo->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $pdo->bindParam(":rol_usuario", $rol_usuario, PDO::PARAM_STR);

        return $pdo->execute();
    }
}
