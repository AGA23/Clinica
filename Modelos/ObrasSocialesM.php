<?php

require_once "ConexionBD.php";

class ObrasSocialesM {

    // 1. MOSTRAR (READ)
    static public function ObtenerTodasM($tabla) {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("SELECT * FROM $tabla ORDER BY id ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // 2. CREAR (CREATE)
    static public function CrearObraSocialM($tabla, $datos) {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("INSERT INTO $tabla (nombre, sigla, tipo, cuit) VALUES (:nombre, :sigla, :tipo, :cuit)");
            
            $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
            $stmt->bindParam(":sigla", $datos["sigla"], PDO::PARAM_STR);
            $stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
            $stmt->bindParam(":cuit", $datos["cuit"], PDO::PARAM_STR);

            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    // 3. EDITAR (UPDATE)
    static public function EditarObraSocialM($tabla, $datos) {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("UPDATE $tabla SET nombre = :nombre, sigla = :sigla, tipo = :tipo, cuit = :cuit WHERE id = :id");

            $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
            $stmt->bindParam(":sigla", $datos["sigla"], PDO::PARAM_STR);
            $stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
            $stmt->bindParam(":cuit", $datos["cuit"], PDO::PARAM_STR);
            $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    // 4. BORRAR (DELETE)
    static public function BorrarObraSocialM($tabla, $id) {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("DELETE FROM $tabla WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Helper para el calendario
    static public function ObtenerNombrePorCitaM($tablaCitas, $tablaObras, $idCita) {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("
                SELECT O.nombre as nombre_obra_social
                FROM $tablaObras O
                INNER JOIN $tablaCitas C ON C.id_tipo_pago = O.id
                WHERE C.id = :id_cita
            ");
            $stmt->bindParam(":id_cita", $idCita, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }

    // 🟢 MÉTODO CRÍTICO PARA TU AJAX DE PACIENTES
    static public function VerPlanesM($id_obra_social) {
        try {
            // Asegurarse de que la tabla 'planes_obras' existe en la BD
            $stmt = ConexionBD::getInstancia()->prepare("SELECT * FROM planes_obras WHERE id_obra_social = :id_os ORDER BY nombre_plan ASC");
            $stmt->bindParam(":id_os", $id_obra_social, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Si hay error, devolvemos array vacío para que el JS no se rompa
            return [];
        }
    }

    // Crear un plan nuevo
    static public function CrearPlanM($datos) {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("INSERT INTO planes_obras (id_obra_social, nombre_plan) VALUES (:id_os, :nombre)");
            $stmt->bindParam(":id_os", $datos["id_obra_social"], PDO::PARAM_INT);
            $stmt->bindParam(":nombre", $datos["nombre_plan"], PDO::PARAM_STR);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    // Borrar un plan
    static public function BorrarPlanM($id_plan) {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("DELETE FROM planes_obras WHERE id = :id");
            $stmt->bindParam(":id", $id_plan, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
}