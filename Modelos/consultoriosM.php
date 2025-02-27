<?php

require_once "ConexionBD.php";

class ConsultoriosM extends ConexionBD {

    // Crear Consultorio
    static public function CrearConsultorioM($tablaBD, $consultorio) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("INSERT INTO $tablaBD(nombre) VALUES (:nombre)");

            $pdo->bindParam(":nombre", $consultorio["nombre"], PDO::PARAM_STR);

            if ($pdo->execute()) {
                return true;
            } else {
                return false;
            }

        } catch (PDOException $e) {
            error_log("Error al insertar consultorio: " . $e->getMessage());
            return false;
        } finally {
            $pdo = null; // Cerrar la conexión
        }
    }

    // Ver Consultorios
    static public function VerConsultoriosM($tablaBD, $columna, $valor) {
        try {
            if ($columna == null) {
                // Obtener todos los consultorios
                $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD");
                $pdo->execute();
                return $pdo->fetchAll(PDO::FETCH_ASSOC); // Devuelve un array de consultorios
            } else {
                // Obtener un consultorio específico
                $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD WHERE $columna = :$columna");
                $pdo->bindParam(":" . $columna, $valor, PDO::PARAM_STR);
                $pdo->execute();
                return $pdo->fetch(PDO::FETCH_ASSOC); // Devuelve un solo consultorio como array asociativo
            }
        } catch (PDOException $e) {
            error_log("Error al consultar los consultorios: " . $e->getMessage());
            return false;
        } finally {
            $pdo = null; // Cerrar la conexión
        }
    }

    // Verificar si hay doctores asociados a un consultorio
    static public function tieneDoctoresAsociados($id_consultorio) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT COUNT(*) AS total FROM doctores WHERE id_consultorio = :id_consultorio");
            $pdo->bindParam(":id_consultorio", $id_consultorio, PDO::PARAM_INT);
            $pdo->execute();
            $resultado = $pdo->fetch(PDO::FETCH_ASSOC);

            return ($resultado['total'] > 0); // Devuelve true si hay doctores asociados
        } catch (PDOException $e) {
            error_log("Error al verificar doctores asociados: " . $e->getMessage());
            return false;
        } finally {
            $pdo = null; // Cerrar la conexión
        }
    }

    // Borrar Consultorio
    static public function BorrarConsultorioM($tablaBD, $id) {
        try {
            // Verificar si hay doctores asociados
            if (self::tieneDoctoresAsociados($id)) {
                error_log("No se puede eliminar el consultorio porque tiene doctores asociados.");
                return false; // No se puede eliminar
            }

            // Si no hay doctores asociados, proceder con la eliminación
            $pdo = ConexionBD::getInstancia()->prepare("DELETE FROM $tablaBD WHERE id = :id");
            $pdo->bindParam(":id", $id, PDO::PARAM_INT);

            if ($pdo->execute()) {
                return true;
            } else {
                return false;
            }

        } catch (PDOException $e) {
            error_log("Error al borrar el consultorio: " . $e->getMessage());
            return false;
        } finally {
            $pdo = null; // Cerrar la conexión
        }
    }

    // Editar Consultorio
    static public function EditarConsultoriosM($tablaBD, $id) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT id, nombre FROM $tablaBD WHERE id = :id");

            $pdo->bindParam(":id", $id, PDO::PARAM_INT);

            $pdo->execute();
            return $pdo->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al editar consultorio: " . $e->getMessage());
            return false;
        } finally {
            $pdo = null; // Cerrar la conexión
        }
    }

    // Actualizar Consultorio
    static public function ActualizarConsultoriosM($tablaBD, $datosC) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("UPDATE $tablaBD SET nombre = :nombre WHERE id = :id");

            $pdo->bindParam(":id", $datosC["id"], PDO::PARAM_INT);
            $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);

            if ($pdo->execute()) {
                return true;
            } else {
                return false;
            }

        } catch (PDOException $e) {
            error_log("Error al actualizar consultorio: " . $e->getMessage());
            return false;
        } finally {
            $pdo = null; // Cerrar la conexión
        }
    }
}