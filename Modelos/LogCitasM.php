<?php
require_once "ConexionBD.php";

class LogCitasM {

    /**
     * Obtiene todos los registros del historial de cambios de citas.
     * Esta función consulta DIRECTAMENTE la tabla de logs.
     *
     * @return array
     */
    static public function VerLogsCitasM() {
        // La consulta es correcta, apunta a la tabla correcta.
        $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM historial_cambios_citas ORDER BY fecha_cambio DESC");
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un nuevo cambio en el historial de una cita.
     * Es crucial que esta función sea llamada desde los controladores cada vez que se modifica una cita.
     *
     * @param int $id_cita
     * @param string $campo_modificado
     * @param string $valor_anterior
     * @param string $valor_nuevo
     * @param string $usuario_modifico Nombre completo del usuario
     * @param int $id_usuario
     * @param string $rol_usuario
     * @return bool
     */
    static public function RegistrarCambioCitaM($id_cita, $campo_modificado, $valor_anterior, $valor_nuevo, $usuario_modifico, $id_usuario, $rol_usuario) {
        $pdo = ConexionBD::getInstancia()->prepare("INSERT INTO historial_cambios_citas
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
