<?php


require_once "ConexionBD.php";

class SecretariosM { // Cambio de nombre de la clase

    // ===================================================================
    // MÉTODOS PARA EL PERFIL (VISTA 'perfil-Secretarios.php')
    // ===================================================================

    /**
     * Obtiene el perfil de un secretario/a específico por su ID.
     *
     * @param int $id
     * @return array|false
     */
    public static function ObtenerPerfilSecretarioM($id) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM secretarios WHERE id = :id");
            $pdo->bindParam(":id", $id, PDO::PARAM_INT);
            $pdo->execute();
            return $pdo->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ObtenerPerfilSecretarioM: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el perfil de un secretario/a en la base de datos.
     *
     * @param array $datos
     * @return bool
     */
    public static function ActualizarPerfilSecretarioM($datos) {
        try {
            $sql = "UPDATE secretarios SET 
                        nombre = :nombre, apellido = :apellido, usuario = :usuario, 
                        clave = :clave, foto = :foto 
                    WHERE id = :id";
            
            $pdo = ConexionBD::getInstancia()->prepare($sql);
            
            $pdo->bindParam(":id", $datos["id"], PDO::PARAM_INT);
            $pdo->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
            $pdo->bindParam(":apellido", $datos["apellido"], PDO::PARAM_STR);
            $pdo->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
            $pdo->bindParam(":clave", $datos["clave"], PDO::PARAM_STR);
            $pdo->bindParam(":foto", $datos["foto"], PDO::PARAM_STR);
            
            return $pdo->execute();
        } catch (PDOException $e) {
            error_log("Error en ActualizarPerfilSecretarioM: " . $e->getMessage());
            return false;
        }
    }

    // ===================================================================
    // MÉTODOS DE GESTIÓN PARA ADMINISTRADORES
    // ===================================================================

    /**
     * Obtiene los datos de un secretario/a por su usuario para el login.
     *
     * @param string $usuario
     * @return array|false
     */
    static public function IngresarSecretarioM($usuario) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM secretarios WHERE usuario = :usuario");
            $pdo->bindParam(":usuario", $usuario, PDO::PARAM_STR);
            $pdo->execute();
            return $pdo->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en IngresarSecretarioM: " . $e->getMessage());
            return false;
        }
    }

    
     static public function VerSecretariosM() {
        try {
            $pdo = ConexionBD::getInstancia()->prepare(
                "SELECT S.*, C.nombre AS nombre_consultorio 
                 FROM secretarios AS S
                 LEFT JOIN consultorios AS C ON S.id_consultorio = C.id
                 ORDER BY S.apellido, S.nombre"
            );
            $pdo->execute();
            return $pdo->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en VerSecretariosM: " . $e->getMessage());
            return [];
        }
    }

   static public function ActualizarSecretarioAdminM($datosC) {
        try {
            $sql = "UPDATE secretarios SET apellido = :apellido, nombre = :nombre, usuario = :usuario, id_consultorio = :id_consultorio";
            
            if (!empty($datosC["clave"])) {
                $sql .= ", clave = :clave";
            }
            
            $sql .= " WHERE id = :id";
            
            $pdo = ConexionBD::getInstancia()->prepare($sql);
            
            $pdo->bindParam(":id", $datosC["id"], PDO::PARAM_INT);
            $pdo->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
            $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
            $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
            $pdo->bindValue(":id_consultorio", $datosC["id_consultorio"] ?: null, PDO::PARAM_INT);

            if (!empty($datosC["clave"])) {
                $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
            }

            return $pdo->execute();
        } catch (PDOException $e) {
            error_log("Error en ActualizarSecretarioAdminM: " . $e->getMessage());
            return false;
        }
    }
    static public function CrearSecretarioM($datosC) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("INSERT INTO secretarios (nombre, apellido, usuario, clave, rol) VALUES (:nombre, :apellido, :usuario, :clave, :rol)");
            
            $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
            $pdo->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
            $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
            $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
            $pdo->bindParam(":rol", $datosC["rol"], PDO::PARAM_STR);
            
            return $pdo->execute();
        } catch (PDOException $e) {
            error_log("Error en CrearSecretarioM: " . $e->getMessage());
            return false;
        }
    }

    static public function VerUnSecretarioM($id) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM secretarios WHERE id = :id");
            $pdo->bindParam(":id", $id, PDO::PARAM_INT);
            $pdo->execute();
            return $pdo->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en VerUnSecretarioM: " . $e->getMessage());
            return false;
        }
    }

    
    static public function BorrarSecretarioM($id) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("DELETE FROM secretarios WHERE id = :id");
            $pdo->bindParam(":id", $id, PDO::PARAM_INT);
            return $pdo->execute();
        } catch (PDOException $e) {
            error_log("Error en BorrarSecretarioM: " . $e->getMessage());
            return false;
        }
    }
}
?>