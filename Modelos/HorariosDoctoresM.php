<?php
require_once "ConexionBD.php";

class HorariosDoctoresM {

    public static function ObtenerHorariosPorDoctor($id_doctor) {
        $pdo = ConexionBD::getInstancia();
        
       
        $sql = "SELECT 
                    id_doctor,
                    id_consultorio, 
                    dia_semana, 
                    hora_inicio, 
                    hora_fin 
                FROM horarios_doctores 
                WHERE id_doctor = :id_doctor";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}