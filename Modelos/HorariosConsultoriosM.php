<?php


require_once "ConexionBD.php";

class HorariosConsultoriosM {

    public static function ObtenerHorariosPorConsultorio($id_consultorio) {
        $pdo = ConexionBD::getInstancia(); // ✅ conexión correcta
        $stmt = $pdo->prepare("SELECT * FROM horarios_consultorios WHERE id_consultorio = :id_consultorio");
        $stmt->bindParam(":id_consultorio", $id_consultorio, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
