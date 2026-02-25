<?php
// En Modelos/plantillacorreoM.php

require_once "ConexionBD.php";

/**
 * Clase Modelo para gestionar las plantillas de correo electrónico.
 * Interactúa con la tabla 'plantillas_correo'.
 */
class PlantillaCorreoM {

    /**
     * Obtiene los datos de una plantilla de correo específica por su identificador único.
     * @param string $identificador El identificador de la plantilla (ej: 'recordatorio_cita').
     * @return array|false Un array con los datos de la plantilla o false si no se encuentra.
     */
    public static function ObtenerPlantillaPorIdentificador($identificador) {
        try {
            // Se usa SELECT * para asegurar que se obtienen todas las columnas,
            // incluyendo la nueva 'tiempo_envio_horas'.
            $sql = "SELECT * FROM plantillas_correo WHERE identificador = :identificador LIMIT 1";
            
            $stmt = ConexionBD::getInstancia()->prepare($sql);
            $stmt->bindParam(":identificador", $identificador, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // En caso de error de base de datos, se registra en el log para depuración.
            error_log("Error en PlantillasCorreoM::ObtenerPlantillaPorIdentificador: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza una plantilla de correo en la base de datos.
     * @param array $datos Un array asociativo con los datos a actualizar.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public static function ActualizarPlantillaM($datos) {
        try {
            $sql = "UPDATE plantillas_correo SET 
                        asunto = :asunto, 
                        cuerpo_html = :cuerpo_html, 
                        tiempo_envio_horas = :tiempo_envio_horas 
                    WHERE identificador = :identificador";
            
            $stmt = ConexionBD::getInstancia()->prepare($sql);
            
            $stmt->bindParam(":identificador", $datos["identificador"], PDO::PARAM_STR);
            $stmt->bindParam(":asunto", $datos["asunto"], PDO::PARAM_STR);
            $stmt->bindParam(":cuerpo_html", $datos["cuerpo_html"], PDO::PARAM_STR);
            $stmt->bindParam(":tiempo_envio_horas", $datos["tiempo_envio_horas"], PDO::PARAM_INT);
            
            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error en PlantillaCorreoM::ActualizarPlantillaM: " . $e->getMessage());
            return false;
        }
    }

} // Fin de la clase PlantillasCorreoM