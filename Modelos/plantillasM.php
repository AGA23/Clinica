<?php
// En Modelos/PlantillasM.php

require_once 'ConexionBD.php';

class PlantillasM {

    /**
     * Obtiene todas las plantillas de un tipo específico (ej. 'receta' o 'certificado').
     */
    static public function ObtenerPlantillasPorTipoM($tipo) {
        $stmt = ConexionBD::getInstancia()->prepare(
            "SELECT id, titulo FROM plantillas_documentos WHERE tipo = :tipo ORDER BY titulo ASC"
        );
        $stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el contenido de una plantilla específica por su ID.
     */
    static public function ObtenerContenidoPlantillaM($id) {
        $stmt = ConexionBD::getInstancia()->prepare("SELECT contenido FROM plantillas_documentos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>