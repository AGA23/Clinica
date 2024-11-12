<?php

require_once "ConexionBD.php";

class ConsultoriosM extends ConexionBD {

    //Crear Consultorio
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
            // Manejo de errores de base de datos
            echo "Error al insertar consultorio: " . $e->getMessage();
            return false;
        } finally {
            $pdo = null; // Cerrar la conexión
        }
    }

    //Ver Consultorios
    static public function VerConsultoriosM($tablaBD, $columna, $valor) {
        try {
            if ($columna == null) {
                $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD");
                $pdo->execute();
                return $pdo->fetchAll();
            } else {
                $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD WHERE $columna = :$columna");
                $pdo->bindParam(":".$columna, $valor, PDO::PARAM_STR);
                $pdo->execute();
                return $pdo->fetch();
            }

        } catch (PDOException $e) {
            // Manejo de errores de base de datos
            echo "Error al consultar los consultorios: " . $e->getMessage();
            return false;
        } finally {
            $pdo = null; // Cerrar la conexión
        }
    }

    //Borrar Consultorio
    static public function BorrarConsultorioM($tablaBD, $id) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("DELETE FROM $tablaBD WHERE id = :id");

            $pdo->bindParam(":id", $id, PDO::PARAM_INT);

            if ($pdo->execute()) {
                return true;
            } else {
                return false;
            }

        } catch (PDOException $e) {
            // Manejo de errores de base de datos
            echo "Error al borrar el consultorio: " . $e->getMessage();
            return false;
        } finally {
            $pdo = null; // Cerrar la conexión
        }
    }

    //Editar Consultorio
    static public function EditarConsultoriosM($tablaBD, $id) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT id, nombre FROM $tablaBD WHERE id = :id");

            $pdo->bindParam(":id", $id, PDO::PARAM_INT);

            $pdo->execute();
            return $pdo->fetch();

        } catch (PDOException $e) {
            // Manejo de errores de base de datos
            echo "Error al editar consultorio: " . $e->getMessage();
            return false;
        } finally {
            $pdo = null; // Cerrar la conexión
        }
    }

    //Actualizar Consultorio
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
            // Manejo de errores de base de datos
            echo "Error al actualizar consultorio: " . $e->getMessage();
            return false;
        } finally {
            $pdo = null; // Cerrar la conexión
        }
    }
}
?>
