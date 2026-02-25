<?php
require_once "ConexionBD.php";

class HorariosConsultoriosM {

    /**
     * Obtiene los horarios de un consultorio específico.
     *
     * @param int $id_consultorio
     * @return array
     */
    public static function ObtenerHorariosPorConsultorio($id_consultorio) {
        $pdo = ConexionBD::getInstancia();
        
        
        // 1. Usamos el nombre correcto de la columna del día: 'dia_semana'.
        // 2. Usamos los nombres correctos para la hora: 'hora_apertura' y 'hora_cierre'.
        // 3. Usamos alias (AS) para que la salida de datos sea idéntica a la de los horarios del doctor.
        $sql = "SELECT 
                    id_consultorio, 
                    dia_semana, 
                    hora_apertura AS hora_inicio, 
                    hora_cierre AS hora_fin 
                FROM horarios_consultorios 
                WHERE id_consultorio = :id_consultorio";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":id_consultorio", $id_consultorio, PDO::PARAM_INT);
        $stmt->execute(); 

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}