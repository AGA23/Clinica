<?php
// En Modelos/PlantillasDocumentosM.php
require_once "ConexionBD.php";

class PlantillasDocumentosM {

   
    static public function ListarPlantillasM() {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("SELECT id, titulo, tipo FROM plantillas_documentos ORDER BY tipo, titulo ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ListarPlantillasM: " . $e->getMessage());
            return [];
        }
    }

   
    static public function CrearPlantillaM($datos) {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("INSERT INTO plantillas_documentos (titulo, tipo, contenido) VALUES (:titulo, :tipo, :contenido)");
            return $stmt->execute([
                ":titulo" => $datos["titulo"],
                ":tipo" => $datos["tipo"],
                ":contenido" => $datos["contenido"]
            ]);
        } catch (PDOException $e) {
            error_log("Error en CrearPlantillaM: " . $e->getMessage());
            return false;
        }
    }


    static public function ObtenerPlantillaPorIdM($id) {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("SELECT * FROM plantillas_documentos WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ObtenerPlantillaPorIdM: " . $e->getMessage());
            return false;
        }
    }

    
    static public function ActualizarPlantillaM($datos) {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("UPDATE plantillas_documentos SET titulo = :titulo, tipo = :tipo, contenido = :contenido WHERE id = :id");
            return $stmt->execute([
                ":id" => $datos["id"],
                ":titulo" => $datos["titulo"],
                ":tipo" => $datos["tipo"],
                ":contenido" => $datos["contenido"]
            ]);
        } catch (PDOException $e) {
            error_log("Error en ActualizarPlantillaM: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Borra una plantilla de la base de datos por su ID.
     * @param int $id
     * @return bool
     */
    static public function BorrarPlantillaM($id) {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("DELETE FROM plantillas_documentos WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en BorrarPlantillaM: " . $e->getMessage());
            return false;
        }
    }
}