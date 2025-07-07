<?php

require_once "ConexionBD.php";

class HorariosDoctoresM {

    public static function ObtenerHorariosPorDoctor($id_doctor) {
        $pdo = ConexionBD::getInstancia(); // ✅ conexión correcta
        $stmt = $pdo->prepare("SELECT * FROM horarios_doctores WHERE id_doctor = :id_doctor");
        $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
