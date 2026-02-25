<?php
// En Modelos/PacientesM.php (VERSIÓN FINAL, COMPLETA Y UNIFICADA)

require_once "ConexionBD.php";

class PacientesM {

    // --- MÉTODOS PARA LA GESTIÓN (SECRETARÍA/ADMIN) ---

    public static function ListarPacientesM() {
        // 🟢 SE AÑADIÓ EL JOIN CON OBRAS_SOCIALES
        $sql = "SELECT p.*, os.nombre AS nombre_obra_social 
                FROM pacientes p
                LEFT JOIN obras_sociales os ON p.id_obra_social = os.id
                ORDER BY p.apellido ASC, p.nombre ASC";
        
        $stmt = ConexionBD::getInstancia()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function CrearPacienteM($datos) {
        try {
            // 🟢 SE AÑADIERON LOS CAMPOS DE COBERTURA AL INSERT
            $sql = "INSERT INTO pacientes (nombre, apellido, tipo_documento, numero_documento, usuario, clave, rol, correo, id_obra_social, plan, numero_afiliado) 
                    VALUES (:nombre, :apellido, :tipo_documento, :numero_documento, :usuario, :clave, :rol, :correo, :id_obra_social, :plan, :numero_afiliado)";
            
            $stmt = ConexionBD::getInstancia()->prepare($sql);
            
            $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
            $stmt->bindParam(":apellido", $datos["apellido"], PDO::PARAM_STR);
            $stmt->bindParam(":tipo_documento", $datos["tipo_documento"], PDO::PARAM_STR);
            $stmt->bindParam(":numero_documento", $datos["numero_documento"], PDO::PARAM_STR);
            $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
            $stmt->bindParam(":clave", $datos["clave"], PDO::PARAM_STR);
            $stmt->bindParam(":rol", $datos["rol"], PDO::PARAM_STR);
            $stmt->bindParam(":correo", $datos["correo"], PDO::PARAM_STR);
            
            // Nuevos Bindings
            $stmt->bindParam(":id_obra_social", $datos["id_obra_social"], PDO::PARAM_INT);
            $stmt->bindParam(":plan", $datos["plan"], PDO::PARAM_STR);
            $stmt->bindParam(":numero_afiliado", $datos["numero_afiliado"], PDO::PARAM_STR);
            
            return $stmt->execute();

        } catch (PDOException $e) {
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                error_log("Intento de crear paciente con dato duplicado: " . $e->getMessage());
            } else {
                error_log("Error genérico en CrearPacienteM: " . $e->getMessage());
            }
            return false;
        }
    }

    public static function ActualizarPacienteM($datos) {
        try {
            // 🟢 SE AÑADIERON LOS CAMPOS DE COBERTURA AL UPDATE
            $sql = "UPDATE pacientes SET 
                        nombre = :nombre, 
                        apellido = :apellido, 
                        tipo_documento = :tipo_documento, 
                        numero_documento = :numero_documento, 
                        usuario = :usuario, 
                        correo = :correo,
                        id_obra_social = :id_obra_social,
                        plan = :plan,
                        numero_afiliado = :numero_afiliado
                    ";

            if ($datos["clave"] !== null) {
                $sql .= ", clave = :clave";
            }
            $sql .= " WHERE id = :id";

            $stmt = ConexionBD::getInstancia()->prepare($sql);

            $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
            $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
            $stmt->bindParam(":apellido", $datos["apellido"], PDO::PARAM_STR);
            $stmt->bindParam(":tipo_documento", $datos["tipo_documento"], PDO::PARAM_STR);
            $stmt->bindParam(":numero_documento", $datos["numero_documento"], PDO::PARAM_STR);
            $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
            $stmt->bindParam(":correo", $datos["correo"], PDO::PARAM_STR);
            
            // Nuevos Bindings
            $stmt->bindParam(":id_obra_social", $datos["id_obra_social"], PDO::PARAM_INT);
            $stmt->bindParam(":plan", $datos["plan"], PDO::PARAM_STR);
            $stmt->bindParam(":numero_afiliado", $datos["numero_afiliado"], PDO::PARAM_STR);

            if ($datos["clave"] !== null) {
                $stmt->bindParam(":clave", $datos["clave"], PDO::PARAM_STR);
            }

            return $stmt->execute();

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                error_log("Intento de actualizar paciente con duplicados: " . $e->getMessage());
            } else {
                error_log("Error en ActualizarPacienteM: " . $e->getMessage());
            }
            return false;
        }
    }
    
    public static function BorrarPacienteM($id) {
        $stmt = ConexionBD::getInstancia()->prepare("DELETE FROM pacientes WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { return false; }
            error_log("Error en BorrarPacienteM: " . $e->getMessage());
            return false;
        }
    }

    // --- MÉTODOS PARA EL PERFIL DEL PROPIO PACIENTE ---

    
    public static function ActualizarPerfilPacienteM($datosC) {
        $sql = "UPDATE pacientes SET 
                nombre = :nombre, apellido = :apellido, usuario = :usuario, clave = :clave, 
                foto = :foto, correo = :correo, telefono = :telefono, direccion = :direccion
                WHERE id = :id";
        $pdo = ConexionBD::getInstancia()->prepare($sql);
        $pdo->bindParam(":id", $datosC["id"], PDO::PARAM_INT);
        $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
        $pdo->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
        $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
        $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
        $pdo->bindParam(":foto", $datosC["foto"], PDO::PARAM_STR);
        $pdo->bindParam(":correo", $datosC["correo"], PDO::PARAM_STR);
        $pdo->bindParam(":telefono", $datosC["telefono"], PDO::PARAM_STR);
        $pdo->bindParam(":direccion", $datosC["direccion"], PDO::PARAM_STR);
        return $pdo->execute();
    }

    // --- MÉTODOS DE APOYO (PARA AJAX Y OTROS MÓDULOS) ---

    public static function ObtenerPacienteM($id) {
        $stmt = ConexionBD::getInstancia()->prepare("SELECT * FROM pacientes WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function VerificarUsuarioM($usuario) {
        $stmt = ConexionBD::getInstancia()->prepare("SELECT usuario FROM pacientes WHERE usuario = :usuario");
        $stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     
     * Obtiene una lista simple de pacientes (ID y Nombre) para usar en dropdowns.
     * @return array
     */
    public static function ListarPacientesSimpleM() {
        $sql = "SELECT id, nombre, apellido FROM pacientes ORDER BY apellido ASC, nombre ASC";
        $pdo = ConexionBD::getInstancia()->prepare($sql);
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }


     public static function ObtenerNombrePacienteM($id_paciente) {
        if (empty($id_paciente) || !is_numeric($id_paciente)) {
            return ''; // Devuelve una cadena vacía si el ID no es válido
        }

        $stmt = ConexionBD::getInstancia()->prepare("SELECT CONCAT(nombre, ' ', apellido) as nombre_completo FROM pacientes WHERE id = :id");
        $stmt->bindParam(":id", $id_paciente, PDO::PARAM_INT);
        $stmt->execute();
        
        // fetchColumn() devuelve directamente el valor de la primera columna de la fila encontrada,
        // o false si no encuentra nada. El '?:' asegura que siempre devolvamos un string.
        return $stmt->fetchColumn() ?: '';
    }



    public static function GuardarSugerenciaM($id_paciente, $campo, $sugerencia) {
    try {
        $sql = "INSERT INTO sugerencias_cambios_paciente (id_paciente, campo, sugerencia_texto) 
                VALUES (:id_paciente, :campo, :sugerencia)";
        $stmt = ConexionBD::getInstancia()->prepare($sql);
        return $stmt->execute([
            ':id_paciente' => $id_paciente,
            ':campo' => $campo,
            ':sugerencia' => $sugerencia
        ]);
    } catch (PDOException $e) {
        error_log("Error en GuardarSugerenciaM: " . $e->getMessage());
        return false;
    }
}


public static function ObtenerCondicionesClinicasM($id_paciente, $tipo) {
    $sql = "SELECT cc.id, cc.nombre, pc.fecha_verificacion 
            FROM paciente_condicion pc
            JOIN condiciones_clinicas cc ON pc.id_condicion = cc.id
            WHERE pc.id_paciente = :id_paciente AND cc.tipo = :tipo
            ORDER BY cc.nombre ASC";
    $stmt = ConexionBD::getInstancia()->prepare($sql);
    $stmt->execute([':id_paciente' => $id_paciente, ':tipo' => $tipo]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * [NUEVO] Actualiza la lista de condiciones de un paciente.
 */
public static function ActualizarCondicionesClinicasM($id_paciente, $nuevas_condiciones, $tipo) {
    $pdo = ConexionBD::getInstancia();
    try {
        $pdo->beginTransaction();
        
        $sql_delete = "DELETE pc FROM paciente_condicion pc
                       JOIN condiciones_clinicas cc ON pc.id_condicion = cc.id
                       WHERE pc.id_paciente = :id_paciente 
                         AND cc.tipo = :tipo 
                         AND pc.fecha_verificacion IS NULL";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([':id_paciente' => $id_paciente, ':tipo' => $tipo]);

        foreach ($nuevas_condiciones as $nombre_condicion) {
            $nombre_condicion = trim($nombre_condicion);
            if (empty($nombre_condicion)) continue;

            $stmt_find = $pdo->prepare("SELECT id FROM condiciones_clinicas WHERE nombre = :nombre AND tipo = :tipo");
            $stmt_find->execute([':nombre' => $nombre_condicion, ':tipo' => $tipo]);
            $id_condicion = $stmt_find->fetchColumn();

            if (!$id_condicion) {
                $stmt_create = $pdo->prepare("INSERT INTO condiciones_clinicas (nombre, tipo) VALUES (:nombre, :tipo)");
                $stmt_create->execute([':nombre' => $nombre_condicion, ':tipo' => $tipo]);
                $id_condicion = $pdo->lastInsertId();
            }

            $stmt_link = $pdo->prepare("INSERT IGNORE INTO paciente_condicion (id_paciente, id_condicion) VALUES (:id_paciente, :id_condicion)");
            $stmt_link->execute([':id_paciente' => $id_paciente, ':id_condicion' => $id_condicion]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error en ActualizarCondicionesClinicasM: " . $e->getMessage());
        return false;
    }
}

/**
 * [NUEVO] Valida una condición específica para un paciente.
 */
public static function ValidarCondicionClinicaM($id_paciente, $id_condicion, $id_doctor) {
    $sql = "UPDATE paciente_condicion SET 
                verificado_por = :id_doctor, 
                fecha_verificacion = NOW() 
            WHERE id_paciente = :id_paciente AND id_condicion = :id_condicion";
            
    $stmt = ConexionBD::getInstancia()->prepare($sql);
    return $stmt->execute([
        ':id_doctor' => $id_doctor, 
        ':id_paciente' => $id_paciente,
        ':id_condicion' => $id_condicion
    ]);
}

public static function ListarPacientesPorDoctorM($id_doctor) {
    try {
        // Esta consulta busca en las citas para encontrar todos los pacientes
        // únicos (DISTINCT) asociados a un doctor.
        $sql = "SELECT DISTINCT
                    p.id, p.nombre, p.apellido, p.tipo_documento, p.numero_documento
                FROM 
                    pacientes p
                JOIN 
                    citas c ON p.id = c.id_paciente
                WHERE 
                    c.id_doctor = :id_doctor
                ORDER BY 
                    p.apellido ASC, p.nombre ASC";

        $stmt = ConexionBD::getInstancia()->prepare($sql);
        $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error en ListarPacientesPorDoctorM: " . $e->getMessage());
        return [];
    }
}

 static public function ObtenerAfiliacionesPacienteM($id_paciente) {
        try {
            // CORRECCIÓN: Ahora leemos desde la tabla 'pacientes' haciendo JOIN con 'obras_sociales'.
            // Usamos p.id como 'id' de la cobertura para que el JS funcione.
            $sql = "SELECT 
                        p.id AS id, 
                        p.id_obra_social, 
                        p.plan, 
                        p.numero_afiliado, 
                        os.nombre as nombre_os 
                    FROM pacientes p
                    INNER JOIN obras_sociales os ON p.id_obra_social = os.id
                    WHERE p.id = :id_paciente AND p.id_obra_social > 0";
            
            $stmt = ConexionBD::getInstancia()->prepare($sql);
            $stmt->bindParam(":id_paciente", $id_paciente, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}