<?php
// En Modelos/TratamientosM.php (NUEVO ARCHIVO COMPLETO)

require_once "ConexionBD.php";

class TratamientosM {

    // --- MÉTODOS PARA LA GESTIÓN (CRUD) DE LA LISTA MAESTRA ---

   public static function ListarTratamientosM() {
    // La consulta base obtiene todos los tratamientos
    $sql = "SELECT * FROM tratamientos";
    $params = [];

    
    // Si el rol es Secretario, hacemos un JOIN para obtener solo los de su consultorio.
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Secretario' && isset($_SESSION['id_consultorio'])) {
        $sql = "SELECT t.* FROM tratamientos t
                INNER JOIN consultorio_tratamiento ct ON t.id = ct.id_tratamiento
                WHERE ct.id_consultorio = :id_consultorio";
        $params[':id_consultorio'] = $_SESSION['id_consultorio'];
    }
    
    $sql .= " ORDER BY nombre ASC";
    
    $stmt = ConexionBD::getInstancia()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public static function ObtenerTratamientoM($id) {
        $stmt = ConexionBD::getInstancia()->prepare("SELECT * FROM tratamientos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

   public static function CrearTratamientoM($nombre) {
    $conexion = ConexionBD::getInstancia();
    try {
        $conexion->beginTransaction();

        // 1. Insertar el nuevo tratamiento en la tabla maestra
        $stmt_insert = $conexion->prepare("INSERT INTO tratamientos (nombre) VALUES (:nombre)");
        $stmt_insert->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt_insert->execute();
        
        // Obtenemos el ID del tratamiento recién creado
        $id_nuevo_tratamiento = $conexion->lastInsertId();

        // ¡NUEVA LÓGICA DE ASIGNACIÓN!
        // Si es un secretario, lo asignamos automáticamente a su consultorio.
        if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Secretario' && isset($_SESSION['id_consultorio'])) {
            $stmt_assign = $conexion->prepare(
                "INSERT INTO consultorio_tratamiento (id_consultorio, id_tratamiento) 
                 VALUES (:id_consultorio, :id_tratamiento)"
            );
            $stmt_assign->execute([
                ':id_consultorio' => $_SESSION['id_consultorio'],
                ':id_tratamiento' => $id_nuevo_tratamiento
            ]);
        }
        
        $conexion->commit();
        return true;
    } catch (PDOException $e) {
        $conexion->rollBack();
        // Código '1062' es para entrada duplicada (el tratamiento ya existe)
        if ($e->getCode() == '1062') {
            error_log("Intento de crear tratamiento duplicado: " . $nombre);
        } else {
            error_log("Error en CrearTratamientoM: " . $e->getMessage());
        }
        return false;
    }
}

public static function VerificarPertenenciaTratamientoM($id_tratamiento) {
    if ($_SESSION['rol'] === 'Administrador') {
        return true; // El admin siempre tiene permiso
    }
    if ($_SESSION['rol'] === 'Secretario' && isset($_SESSION['id_consultorio'])) {
        $stmt = ConexionBD::getInstancia()->prepare(
            "SELECT COUNT(*) FROM consultorio_tratamiento 
             WHERE id_consultorio = :id_consultorio AND id_tratamiento = :id_tratamiento"
        );
        $stmt->execute([
            ':id_consultorio' => $_SESSION['id_consultorio'],
            ':id_tratamiento' => $id_tratamiento
        ]);
        return $stmt->fetchColumn() > 0;
    }
    return false; // Por defecto, no hay permiso
}
    public static function ActualizarTratamientoM($id, $nombre) {
        $stmt = ConexionBD::getInstancia()->prepare("UPDATE tratamientos SET nombre = :nombre WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public static function BorrarTratamientoM($id) {
        $stmt = ConexionBD::getInstancia()->prepare("DELETE FROM tratamientos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        try { 
            return $stmt->execute(); 
        } catch (PDOException $e) { 
            // Falla si el tratamiento está asignado a un doctor o en una cita
            return false; 
        }
    }

   public static function ObtenerTratamientosPorConsultorioM($id_consultorio) {
    $stmt = ConexionBD::getInstancia()->prepare(
        "SELECT t.* FROM tratamientos t
         INNER JOIN consultorio_tratamiento ct ON t.id = ct.id_tratamiento
         WHERE ct.id_consultorio = :id_consultorio
         ORDER BY t.nombre ASC"
    );
    $stmt->bindParam(":id_consultorio", $id_consultorio, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public static function ObtenerNombreTratamientoM($id_tratamiento) {
        if (empty($id_tratamiento)) return 'Consulta General';
        $stmt = ConexionBD::getInstancia()->prepare("SELECT nombre FROM tratamientos WHERE id = :id");
        $stmt->bindParam(":id", $id_tratamiento, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() ?: 'Consulta General';
    }

      public static function ObtenerTratamientosAsignadosAConsultorioM($id_consultorio) {
        $stmt = ConexionBD::getInstancia()->prepare("SELECT id_tratamiento FROM consultorio_tratamiento WHERE id_consultorio = :id");
        $stmt->bindParam(":id", $id_consultorio, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    
    public static function GuardarAsignacionConsultorioM($id_consultorio, $ids_tratamientos) {
        $conexion = ConexionBD::getInstancia();
        try {
            $conexion->beginTransaction();
            $stmt_delete = $conexion->prepare("DELETE FROM consultorio_tratamiento WHERE id_consultorio = :id");
            $stmt_delete->execute([':id' => $id_consultorio]);
            if (!empty($ids_tratamientos)) {
                $stmt_insert = $conexion->prepare("INSERT INTO consultorio_tratamiento (id_consultorio, id_tratamiento) VALUES (:id_consultorio, :id_tratamiento)");
                foreach ($ids_tratamientos as $id_trat) {
                    $stmt_insert->execute([':id_consultorio' => $id_consultorio, ':id_tratamiento' => $id_trat]);
                }
            }
            $conexion->commit();
            return true;
        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error en transacción de asignación de tratamientos: " . $e->getMessage());
            return false;
        }
    }
}