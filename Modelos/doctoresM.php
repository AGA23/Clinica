<?php
// En Modelos/DoctoresM.php (VERSIÓN FINAL, COMPLETA Y SIN REDUNDANCIAS)

require_once "ConexionBD.php";

class DoctoresM {

    public static function ListarDoctoresM() {
        $sql = "SELECT d.*, co.nombre as nombre_consultorio FROM doctores d LEFT JOIN consultorios co ON d.id_consultorio = co.id";
        $params = [];
        if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Secretario' && isset($_SESSION['id_consultorio'])) {
            $sql .= " WHERE d.id_consultorio = :id_consultorio";
            $params[':id_consultorio'] = $_SESSION['id_consultorio'];
        }
        $sql .= " ORDER BY d.apellido, d.nombre";
        $stmt = ConexionBD::getInstancia()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ObtenerDoctorM($id) {
        $stmt = ConexionBD::getInstancia()->prepare("SELECT * FROM doctores WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function CrearDoctorM($datos) {
    try {
        // === INICIO DE LA MODIFICACIÓN (Consulta SQL) ===
        $sql = "INSERT INTO doctores (nombre, apellido, email, sexo, usuario, clave, id_consultorio, rol, matricula_nacional, matricula_provincial) 
                VALUES (:nombre, :apellido, :email, :sexo, :usuario, :clave, :id_consultorio, :rol, :matricula_nacional, :matricula_provincial)";
        // === FIN DE LA MODIFICACIÓN ===
        
        $stmt = ConexionBD::getInstancia()->prepare($sql);

        $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindParam(":apellido", $datos["apellido"], PDO::PARAM_STR);
        $stmt->bindParam(":email", $datos["email"], PDO::PARAM_STR);
        $stmt->bindParam(":sexo", $datos["sexo"], PDO::PARAM_STR);
        $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
        $stmt->bindParam(":clave", $datos["clave"], PDO::PARAM_STR);
        $stmt->bindParam(":id_consultorio", $datos["id_consultorio"], PDO::PARAM_INT);
        $stmt->bindParam(":rol", $datos["rol"], PDO::PARAM_STR);

        // === INICIO DE LA MODIFICACIÓN (bindParam) ===
        $stmt->bindParam(":matricula_nacional", $datos["matricula_nacional"], PDO::PARAM_STR);
        $stmt->bindParam(":matricula_provincial", $datos["matricula_provincial"], PDO::PARAM_STR);
        // === FIN DE LA MODIFICACIÓN ===
        
        return $stmt->execute();
        
    } catch (PDOException $e) {
        // El error 1062 es para 'Duplicate entry'. Indica que un campo unique ya existe.
        if ($e->errorInfo[1] == 1062) {
            error_log("Intento de crear doctor con datos duplicados: " . $e->getMessage());
        } else {
            error_log("Error en CrearDoctorM: " . $e->getMessage());
        }
        return false;
    }
}

   public static function ActualizarDoctorM($datos) {
    // === INICIO DE LA MODIFICACIÓN (Consulta SQL) ===
    $sql = "UPDATE doctores SET 
                nombre = :nombre, 
                apellido = :apellido, 
                email = :email, 
                sexo = :sexo, 
                usuario = :usuario, 
                id_consultorio = :id_consultorio,
                matricula_nacional = :matricula_nacional,
                matricula_provincial = :matricula_provincial
                firma_digital = :firma_digital 
            ";
    // === FIN DE LA MODIFICACIÓN ===
            
    $params = [
        "id" => $datos["id"], 
        "nombre" => $datos["nombre"], 
        "apellido" => $datos["apellido"], 
        "email" => $datos["email"], 
        "sexo" => $datos["sexo"], 
        "usuario" => $datos["usuario"], 
        "id_consultorio" => $datos["id_consultorio"],

        // === INICIO DE LA MODIFICACIÓN (Parámetros) ===
        "matricula_nacional" => $datos["matricula_nacional"],
        "matricula_provincial" => $datos["matricula_provincial"]
        // === FIN DE LA MODIFICACIÓN ===
    ];

    if ($datos["clave"] !== null) {
        $sql .= ", clave = :clave";
        $params['clave'] = $datos['clave'];
    }
    $sql .= " WHERE id = :id";
    $stmt = ConexionBD::getInstancia()->prepare($sql);
    return $stmt->execute($params);
}
    
    public static function BorrarDoctorM($id) {
        $stmt = ConexionBD::getInstancia()->prepare("DELETE FROM doctores WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        try { return $stmt->execute(); }
        catch (PDOException $e) { return false; }
    }

    public static function ObtenerHorariosDoctorM($idDoctor) {
        $stmt = ConexionBD::getInstancia()->prepare("SELECT * FROM horarios_doctores WHERE id_doctor = :id_doctor ORDER BY dia_semana, hora_inicio");
        $stmt->bindParam(":id_doctor", $idDoctor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function GuardarHorariosDoctorM($idDoctor, $horarios) {
        $conexion = ConexionBD::getInstancia();
        try {
            $conexion->beginTransaction();
            $stmt_delete = $conexion->prepare("DELETE FROM horarios_doctores WHERE id_doctor = :id");
            $stmt_delete->execute([':id' => $idDoctor]);
            $stmt_insert = $conexion->prepare("INSERT INTO horarios_doctores (id_doctor, dia_semana, hora_inicio, hora_fin) VALUES (:id, :dia, :inicio, :fin)");
            foreach ($horarios as $h) {
                if (!empty($h['hora_inicio']) && !empty($h['hora_fin'])) {
                    $stmt_insert->execute([':id' => $idDoctor, ':dia' => $h['dia_semana'], ':inicio' => $h['hora_inicio'], ':fin' => $h['hora_fin']]);
                }
            }
            $conexion->commit();
            return true;
        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error en transacción de horarios de doctor: " . $e->getMessage());
            return false;
        }
    }
    
    public static function ListarDoctoresPorConsultorioM($id_consultorio) {
        $sql = "SELECT id, nombre, apellido FROM doctores WHERE id_consultorio = :id_consultorio ORDER BY apellido ASC, nombre ASC";
        $stmt = ConexionBD::getInstancia()->prepare($sql);
        $stmt->bindParam(":id_consultorio", $id_consultorio, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  
public static function ObtenerPerfilDoctorM($id_doctor) {
    // [MODIFICADO] Añadimos 'd.firma_digital' a la lista de columnas seleccionadas.
    $sql = "SELECT d.id, d.apellido, d.nombre, d.foto, d.usuario, d.clave, d.email, 
                   d.matricula_nacional, d.matricula_provincial,
                   d.firma_digital, 
                   co.nombre AS nombre_consultorio 
            FROM doctores d 
            LEFT JOIN consultorios co ON d.id_consultorio = co.id 
            WHERE d.id = :id_doctor LIMIT 1";
            
    $pdo = ConexionBD::getInstancia()->prepare($sql);
    $pdo->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
    $pdo->execute();
    return $pdo->fetch(PDO::FETCH_ASSOC);
}
public static function ActualizarPerfilM($datos) {
    
    $sql = "UPDATE doctores SET 
                nombre = :nombre, 
                apellido = :apellido, 
                email = :email, 
                clave = :clave, 
                foto = :foto,
                firma_digital = :firma_digital 
            WHERE id = :id";
            
    $pdo = ConexionBD::getInstancia()->prepare($sql);
    
    $pdo->bindParam(":id", $datos["id"], PDO::PARAM_INT);
    $pdo->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
    $pdo->bindParam(":apellido", $datos["apellido"], PDO::PARAM_STR);
    $pdo->bindParam(":email", $datos["email"], PDO::PARAM_STR);
    $pdo->bindParam(":clave", $datos["clave"], PDO::PARAM_STR);
    $pdo->bindParam(":foto", $datos["foto"], PDO::PARAM_STR);
    $pdo->bindParam(":firma_digital", $datos["firma_digital"], PDO::PARAM_STR);
    
    return $pdo->execute();
}

    // --- MÉTODOS PARA LA RELACIÓN DOCTOR-TRATAMIENTO ---

    public static function ObtenerTratamientosPorDoctorM($id_doctor) {
        $sql = "SELECT t.id, t.nombre FROM tratamientos t INNER JOIN doctor_tratamiento dt ON t.id = dt.id_tratamiento WHERE dt.id_doctor = :id_doctor ORDER BY t.nombre ASC";
        $stmt = ConexionBD::getInstancia()->prepare($sql);
        $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ObtenerTratamientosAsignadosM($id_doctor) {
        $stmt = ConexionBD::getInstancia()->prepare("SELECT id_tratamiento FROM doctor_tratamiento WHERE id_doctor = :id");
        $stmt->bindParam(":id", $id_doctor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
public static function ObtenerConsultorioDeDoctor($id_doctor) {
        $stmt = ConexionBD::getInstancia()->prepare("SELECT id_consultorio FROM doctores WHERE id = :id_doctor");
        $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
        $stmt->execute();
        
        // fetchColumn() es perfecto para esto: devuelve el valor de la primera columna de la fila encontrada,
        // o false si no encuentra ninguna fila, que es un resultado claro y fácil de manejar.
        return $stmt->fetchColumn();
    }


    /**
     * ¡ESTA ES LA ÚNICA FUNCIÓN QUE FALTABA!
     * Guarda (borra e inserta) las asignaciones de tratamientos para un doctor.
     */
    public static function GuardarTratamientosAsignadosM($id_doctor, $ids_tratamientos) {
        $conexion = ConexionBD::getInstancia();
        try {
            $conexion->beginTransaction();
            // 1. Borrar todas las asignaciones antiguas
            $stmt_delete = $conexion->prepare("DELETE FROM doctor_tratamiento WHERE id_doctor = :id");
            $stmt_delete->execute([':id' => $id_doctor]);
            
            // 2. Insertar las nuevas asignaciones (si las hay)
            if (!empty($ids_tratamientos)) {
                $stmt_insert = $conexion->prepare("INSERT INTO doctor_tratamiento (id_doctor, id_tratamiento) VALUES (:id_doctor, :id_tratamiento)");
                foreach ($ids_tratamientos as $id_trat) {
                    $stmt_insert->execute([':id_doctor' => $id_doctor, ':id_tratamiento' => $id_trat]);
                }
            }
            
            $conexion->commit();
            return true;
        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error en transacción de tratamientos de doctor: " . $e->getMessage());
            return false;
        }
    }

     
    public static function ObtenerNombrePorIdM($id_doctor) {
        if (empty($id_doctor) || !is_numeric($id_doctor)) {
            return 'Doctor no especificado';
        }
        $stmt = ConexionBD::getInstancia()->prepare("SELECT CONCAT(nombre, ' ', apellido) as nombre_completo FROM doctores WHERE id = :id");
        $stmt->bindParam(":id", $id_doctor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() ?: 'Doctor Desconocido'; // Devuelve el nombre o un texto por defecto
    }
}