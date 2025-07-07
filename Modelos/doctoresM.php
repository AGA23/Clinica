<?php
require_once "ConexionBD.php";

class DoctoresM extends ConexionBD {
   // ✅ Lista básica de doctores con sus tratamientos (reemplaza especialidad)
   public static function VerDoctoresBasicosM() {
    try {
        $pdo = ConexionBD::getInstancia();
        $sql = "SELECT d.id, CONCAT(d.nombre, ' ', d.apellido) AS nombre,
                       GROUP_CONCAT(t.nombre SEPARATOR ', ') AS tratamientos
                FROM doctores d
                LEFT JOIN doctor_tratamiento dt ON d.id = dt.id_doctor
                LEFT JOIN tratamientos t ON dt.id_tratamiento = t.id
                WHERE d.estado = 'activo'
                GROUP BY d.id
                ORDER BY d.apellido, d.nombre";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en VerDoctoresBasicosM: " . $e->getMessage());
        return [];
    }
}

// ✅ Información básica de un solo doctor, con sus tratamientos
public static function DoctorBasicoM($columna, $valor) {
    try {
        $pdo = ConexionBD::getInstancia();
        $sql = "SELECT d.id, CONCAT(d.nombre, ' ', d.apellido) AS nombre,
                       GROUP_CONCAT(t.nombre SEPARATOR ', ') AS tratamientos
                FROM doctores d
                LEFT JOIN doctor_tratamiento dt ON d.id = dt.id_doctor
                LEFT JOIN tratamientos t ON dt.id_tratamiento = t.id
                WHERE d.$columna = :valor AND d.estado = 'activo'
                GROUP BY d.id
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":valor", $valor);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en DoctorBasicoM: " . $e->getMessage());
        return false;
    }
}

    /* Métodos existentes para gestión completa de doctores */
    static public function CrearDoctorM($tablaBD, $datosC) {
        $pdo = ConexionBD::getInstancia()->prepare("INSERT INTO $tablaBD(apellido, nombre, sexo, usuario, clave, rol, foto) 
        VALUES(:apellido, :nombre, :sexo, :usuario, :clave, :rol, :foto)");

        $pdo->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
        $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
        $pdo->bindParam(":sexo", $datosC["sexo"], PDO::PARAM_STR);
        $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
        $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
        $pdo->bindParam(":rol", $datosC["rol"], PDO::PARAM_STR);
        $pdo->bindParam(":foto", $datosC["foto"], PDO::PARAM_STR);

        if ($pdo->execute()) {
            return ConexionBD::getInstancia()->lastInsertId();
        }
        return false;
    }

    static public function AsignarHorariosDoctorM($idDoctor, $horarios) {
        $pdo = ConexionBD::getInstancia();
        
        try {
            $pdo->beginTransaction();
            
            $stmtDelete = $pdo->prepare("DELETE FROM horarios_doctores WHERE id_doctor = :id_doctor");
            $stmtDelete->bindParam(":id_doctor", $idDoctor, PDO::PARAM_INT);
            $stmtDelete->execute();
            
            $stmtInsert = $pdo->prepare("INSERT INTO horarios_doctores 
                                        (id_doctor, id_consultorio, dia_semana, hora_inicio, hora_fin) 
                                        VALUES (:id_doctor, :id_consultorio, :dia_semana, :hora_inicio, :hora_fin)");
            
            foreach ($horarios as $horario) {
                $stmtInsert->bindParam(":id_doctor", $idDoctor, PDO::PARAM_INT);
                $stmtInsert->bindParam(":id_consultorio", $horario['id_consultorio'], PDO::PARAM_INT);
                $stmtInsert->bindParam(":dia_semana", $horario['dia_semana'], PDO::PARAM_INT);
                $stmtInsert->bindParam(":hora_inicio", $horario['hora_inicio'], PDO::PARAM_STR);
                $stmtInsert->bindParam(":hora_fin", $horario['hora_fin'], PDO::PARAM_STR);
                $stmtInsert->execute();
            }
            
            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error en AsignarHorariosDoctorM: " . $e->getMessage());
            return false;
        }
    }

    static public function ObtenerHorariosDoctorM($idDoctor) {
        $pdo = ConexionBD::getInstancia()->prepare("SELECT hd.*, c.nombre as consultorio 
                                                   FROM horarios_doctores hd
                                                   JOIN consultorios c ON hd.id_consultorio = c.id
                                                   WHERE hd.id_doctor = :id_doctor
                                                   ORDER BY hd.dia_semana, hd.hora_inicio");
        $pdo->bindParam(":id_doctor", $idDoctor, PDO::PARAM_INT);
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function EliminarHorariosDoctorM($idDoctor) {
        $pdo = ConexionBD::getInstancia()->prepare("DELETE FROM horarios_doctores WHERE id_doctor = :id_doctor");
        $pdo->bindParam(":id_doctor", $idDoctor, PDO::PARAM_INT);
        return $pdo->execute();
    }

    static public function VerDoctoresM($tablaBD, $columna = null, $valor = null) {
        $valid_columns = ['id', 'apellido', 'nombre', 'sexo', 'usuario', 'rol'];
    
        $sql = "SELECT d.*, 
                (SELECT GROUP_CONCAT(CONCAT(h.dia_semana, ':', h.hora_inicio, '-', h.hora_fin) SEPARATOR '|') 
                 FROM horarios_doctores h WHERE h.id_doctor = d.id) as horarios,
                (SELECT GROUP_CONCAT(t.nombre SEPARATOR ', ') 
                 FROM doctor_tratamiento dt 
                 INNER JOIN tratamientos t ON dt.id_tratamiento = t.id 
                 WHERE dt.id_doctor = d.id) as tratamientos
                FROM $tablaBD d";
    
        if ($columna != null && in_array($columna, $valid_columns)) {
            $sql .= " WHERE d.$columna = :$columna";
            $pdo = ConexionBD::getInstancia()->prepare($sql);
            $pdo->bindParam(":$columna", $valor, PDO::PARAM_STR);
        } else {
            $pdo = ConexionBD::getInstancia()->prepare($sql);
        }
    
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }
    

    static public function DoctorM($tablaBD, $columna, $valor) {
        $pdo = ConexionBD::getInstancia()->prepare("SELECT d.*, 
                                                  (SELECT GROUP_CONCAT(CONCAT(h.dia_semana, ':', h.hora_inicio, '-', h.hora_fin, ':', h.id_consultorio) SEPARATOR '|') 
                                                   FROM horarios_doctores h WHERE h.id_doctor = d.id) as horarios
                                                  FROM $tablaBD d 
                                                  WHERE d.$columna = :$columna");
        $pdo->bindParam(":$columna", $valor, PDO::PARAM_STR);
        $pdo->execute();
        return $pdo->fetch(PDO::FETCH_ASSOC);
    }

    static public function ActualizarDoctorM($tablaBD, $datosC) {
        $sql = "UPDATE $tablaBD SET 
                apellido = :apellido, 
                nombre = :nombre, 
                sexo = :sexo, 
                usuario = :usuario";
        
        if (!empty($datosC["clave"])) {
            $sql .= ", clave = :clave";
        }
        
        if (!empty($datosC["foto"])) {
            $sql .= ", foto = :foto";
        }
        
        $sql .= " WHERE id = :id";

        $pdo = ConexionBD::getInstancia()->prepare($sql);
        
        $pdo->bindParam(":id", $datosC["id"], PDO::PARAM_INT);
        $pdo->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
        $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
        $pdo->bindParam(":sexo", $datosC["sexo"], PDO::PARAM_STR);
        $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);

        if (!empty($datosC["clave"])) {
            $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
        }
        
        if (!empty($datosC["foto"])) {
            $pdo->bindParam(":foto", $datosC["foto"], PDO::PARAM_STR);
        }

        return $pdo->execute();
    }

    static public function BorrarDoctorM($tablaBD, $id) {
        $pdo = ConexionBD::getInstancia()->prepare("DELETE FROM $tablaBD WHERE id = :id");
        $pdo->bindParam(":id", $id, PDO::PARAM_INT);
        return $pdo->execute();
    }

    // Asignar tratamientos (borra los anteriores)
public static function AsignarTratamientosDoctorM($idDoctor, $tratamientos) {
    try {
        $pdo = ConexionBD::getInstancia();
        $pdo->beginTransaction();

        // Eliminar tratamientos previos
        $stmtDel = $pdo->prepare("DELETE FROM doctor_tratamiento WHERE id_doctor = :id_doctor");
        $stmtDel->bindParam(":id_doctor", $idDoctor, PDO::PARAM_INT);
        $stmtDel->execute();

        // Insertar nuevos
        $stmtIns = $pdo->prepare("INSERT INTO doctor_tratamiento (id_doctor, id_tratamiento) VALUES (:id_doctor, :id_tratamiento)");
        foreach ($tratamientos as $idTratamiento) {
            $stmtIns->bindParam(":id_doctor", $idDoctor, PDO::PARAM_INT);
            $stmtIns->bindParam(":id_tratamiento", $idTratamiento, PDO::PARAM_INT);
            $stmtIns->execute();
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error en AsignarTratamientosDoctorM: " . $e->getMessage());
        return false;
    }
}

// Eliminar tratamientos del doctor
public static function EliminarTratamientosDoctorM($idDoctor) {
    $pdo = ConexionBD::getInstancia()->prepare("DELETE FROM doctor_tratamiento WHERE id_doctor = :id_doctor");
    $pdo->bindParam(":id_doctor", $idDoctor, PDO::PARAM_INT);
    return $pdo->execute();
}


public static function ObtenerConsultorioDeDoctor($id_doctor) {
        $pdo = ConexionBD::getInstancia();
        $stmt = $pdo->prepare("SELECT id_consultorio FROM doctores WHERE id = :id_doctor");
        $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn(); // Retorna solo el valor id_consultorio o false si no hay resultado
    }

}