<?php
require_once __DIR__ . '/../Modelos/ConexionBD.php';

class ConsultoriosM {
    
    // Crear Consultorio
    static public function CrearConsultorioM($tablaBD, $consultorio) {
        $conexion = ConexionBD::getInstancia();
        $sql = "INSERT INTO $tablaBD (nombre, estado) VALUES (:nombre, 'Disponible')";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":nombre", $consultorio["nombre"], PDO::PARAM_STR);
        return $stmt->execute();
    }

    // Ver Consultorios básicos
    static public function VerConsultoriosM($tablaBD, $columna, $valor) {
        $conexion = ConexionBD::getInstancia();
        
        if ($columna == null && $valor == null) {
            $sql = "SELECT * FROM $tablaBD";
            $stmt = $conexion->prepare($sql);
        } elseif ($valor === null) {
            $sql = "SELECT * FROM $tablaBD WHERE $columna IS NULL";
            $stmt = $conexion->prepare($sql);
        } else {
            $sql = "SELECT * FROM $tablaBD WHERE $columna = :valor";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ver Consultorios completos con información relacionada
    static public function VerConsultoriosCompletosM() {
        $conexion = ConexionBD::getInstancia();
        
         $sql = "SELECT 
        c.id, 
        c.nombre AS consultorio,
        c.estado AS estado_general,
        GROUP_CONCAT(DISTINCT t.nombre SEPARATOR ', ') AS tratamientos,
        CONCAT(d.nombre, ' ', d.apellido) AS medico,
        d.id AS id_doctor,
        ec.estado AS estado_manual,
        ec.motivo,
        ec.fecha AS fecha_estado_manual,
        (SELECT GROUP_CONCAT(CONCAT(hc.dia_semana, ':', hc.hora_apertura, ':', hc.hora_cierre) SEPARATOR '|')
         FROM horarios_consultorios hc 
         WHERE hc.id_consultorio = c.id) AS horario_consultorio,
        (SELECT GROUP_CONCAT(CONCAT(hd.dia_semana, ':', hd.hora_inicio, ':', hd.hora_fin) SEPARATOR '|')
         FROM horarios_doctores hd 
         WHERE hd.id_doctor = d.id) AS horarios_doctor
        FROM consultorios c
        LEFT JOIN doctores d ON d.id_consultorio = c.id
        LEFT JOIN doctor_tratamiento dt ON dt.id_doctor = d.id
        LEFT JOIN tratamientos t ON t.id = dt.id_tratamiento
        LEFT JOIN estados_consultorios ec ON ec.id_consultorio = c.id AND ec.fecha = CURDATE()
        GROUP BY c.id";

        
        $stmt = $conexion->prepare($sql);
        $stmt->execute();
        
        // Depuración: muestra la consulta SQL y los resultados
       
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
       
        
        return $resultados;
    }




    // Verificar disponibilidad del consultorio
    static public function ConsultorioDisponibleM($id_consultorio, $fecha, $hora) {
        $conexion = ConexionBD::getInstancia();
        
        // 1. Verificar estado general del consultorio
        $estadoGeneral = self::obtenerEstadoGeneral($id_consultorio);
        if ($estadoGeneral == 'Mantenimiento') {
            return ['disponible' => false, 'motivo' => 'Consultorio en mantenimiento'];
        }

        // 2. Verificar estado manual para esa fecha
        $estadoManual = self::obtenerEstadoManual($id_consultorio, $fecha);
        if ($estadoManual) {
            return [
                'disponible' => ($estadoManual['estado'] == 'disponible'),
                'motivo' => $estadoManual['motivo'] ?? 'Modificación manual'
            ];
        }

        // 3. Verificar por horarios y citas
        return self::verificarDisponibilidadPorHorarios($id_consultorio, $fecha, $hora);
    }

    // Métodos auxiliares privados
    private static function obtenerEstadoGeneral($id_consultorio) {
        $conexion = ConexionBD::getInstancia();
        $sql = "SELECT estado FROM consultorios WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":id", $id_consultorio, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch()['estado'];
    }

    private static function obtenerEstadoManual($id_consultorio, $fecha) {
        $conexion = ConexionBD::getInstancia();
        $sql = "SELECT estado, motivo FROM estados_consultorios 
                WHERE id_consultorio = :id AND fecha = :fecha";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":id", $id_consultorio, PDO::PARAM_INT);
        $stmt->bindParam(":fecha", $fecha);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private static function verificarDisponibilidadPorHorarios($id_consultorio, $fecha, $hora) {
        $conexion = ConexionBD::getInstancia();
        $dia_semana = date('N', strtotime($fecha));
        
        $sql = "SELECT 1 FROM horarios_consultorios h
                LEFT JOIN citas ct ON ct.id_consultorio = h.id_consultorio 
                                  AND ct.fecha = :fecha
                                  AND (
                                    (:hora BETWEEN ct.inicio AND ct.fin)
                                    OR
                                    (ADDTIME(:hora, '01:00:00') BETWEEN ct.inicio AND ct.fin)
                                  )
                WHERE h.id_consultorio = :id_consultorio
                AND h.dia_semana = :dia_semana
                AND :hora BETWEEN h.hora_inicio AND h.hora_fin
                AND ct.id IS NULL";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":id_consultorio", $id_consultorio, PDO::PARAM_INT);
        $stmt->bindParam(":fecha", $fecha);
        $stmt->bindParam(":hora", $hora);
        $stmt->bindParam(":dia_semana", $dia_semana, PDO::PARAM_INT);
        $stmt->execute();
        
        $disponible = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
        
        return [
            'disponible' => $disponible,
            'motivo' => $disponible ? 'Disponible según horario' : 'No hay horario disponible o está ocupado'
        ];
    }

    // Cambiar estado manual del consultorio
    static public function CambiarEstadoManualM($id_consultorio, $fecha, $estado, $motivo, $id_usuario) {
        $conexion = ConexionBD::getInstancia();
        
        $sql = "INSERT INTO estados_consultorios 
                (id_consultorio, fecha, estado, motivo, id_usuario) 
                VALUES (:id_consultorio, :fecha, :estado, :motivo, :id_usuario)
                ON DUPLICATE KEY UPDATE 
                estado = VALUES(estado), 
                motivo = VALUES(motivo), 
                id_usuario = VALUES(id_usuario)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":id_consultorio", $id_consultorio, PDO::PARAM_INT);
        $stmt->bindParam(":fecha", $fecha);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":motivo", $motivo);
        $stmt->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Borrar Consultorio
    static public function BorrarConsultorioM($tablaBD, $id) {
        $conexion = ConexionBD::getInstancia();
        $sql = "DELETE FROM $tablaBD WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Editar Consultorio
    static public function EditarConsultoriosM($tablaBD, $id) {
        $conexion = ConexionBD::getInstancia();
        $sql = "SELECT * FROM $tablaBD WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar Consultorio
    static public function ActualizarConsultoriosM($tablaBD, $datosC) {
        $conexion = ConexionBD::getInstancia();
        $sql = "UPDATE $tablaBD SET nombre = :nombre, estado = :estado WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
        $stmt->bindParam(":estado", $datosC["estado"], PDO::PARAM_STR);
        $stmt->bindParam(":id", $datosC["id"], PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Actualizar solo el estado del consultorio
    static public function ActualizarEstadoConsultorioM($id, $estado) {
        $conexion = ConexionBD::getInstancia();
        $sql = "UPDATE consultorios SET estado = :estado WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

 public static function VerConsultorioPorId($id) {
    $pdo = ConexionBD::getInstancia();
    $stmt = $pdo->prepare("SELECT * FROM consultorios WHERE id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

}
?>