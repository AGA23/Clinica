<?php


require_once "ConexionBD.php";

class AdminM { 

    // --- MÉTODOS EXISTENTES (MEJORADOS) ---

    //Ingresar Admin (adaptado para usar contraseñas seguras)
    static public function IngresarAdminM($tablaBD, $datosC){
        $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD WHERE usuario = :usuario");
        $pdo -> bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
        $pdo->execute();
        return $pdo -> fetch(PDO::FETCH_ASSOC);
    }

    //Ver Perfil Admin (mantenemos tu método existente para el perfil)
    static public function VerPerfilAdminM($tablaBD, $id){
        $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD WHERE id = :id");
        $pdo -> bindParam(":id", $id, PDO::PARAM_INT);
        $pdo->execute();
        return $pdo -> fetch(PDO::FETCH_ASSOC);
    }

    //Actualizar Perfil Admin
    static public function ActualizarPerfilAdminM($tablaBD, $datosC){
        $pdo = ConexionBD::getInstancia()->prepare("UPDATE $tablaBD SET usuario = :usuario, clave = :clave, nombre = :nombre, apellido = :apellido, foto = :foto WHERE id = :id");
        $pdo ->bindParam(":id", $datosC["id"], PDO::PARAM_INT);
        $pdo ->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
        $pdo ->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
        $pdo ->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
        $pdo ->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
        $pdo ->bindParam(":foto", $datosC["foto"], PDO::PARAM_STR);
        return $pdo->execute();
    }



  
    
    public static function ListarAdminsM() {
        $stmt = ConexionBD::getInstancia()->prepare(
            "SELECT * FROM administradores WHERE id != :id_actual ORDER BY apellido ASC, nombre ASC"
        );
        $stmt->bindParam(":id_actual", $_SESSION['id'], PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public static function CrearAdminM($datos) {
        try {
            $sql = "INSERT INTO administradores (nombre, apellido, usuario, clave, rol) 
                    VALUES (:nombre, :apellido, :usuario, :clave, :rol)";
            $stmt = ConexionBD::getInstancia()->prepare($sql);
            return $stmt->execute([
                ":nombre"   => $datos["nombre"],
                ":apellido" => $datos["apellido"],
                ":usuario"  => $datos["usuario"],
                ":clave"    => $datos["clave"],
                ":rol"      => "Administrador"
            ]);
        } catch (PDOException $e) {
            error_log("Error en CrearAdminM: " . $e->getMessage());
            return false;
        }
    }

    
    public static function ActualizarAdminM($datos) {
        try {
            $sql = "UPDATE administradores SET nombre = :nombre, apellido = :apellido, usuario = :usuario";
            $params = [
                "id"       => $datos["id"],
                "nombre"   => $datos["nombre"],
                "apellido" => $datos["apellido"],
                "usuario"  => $datos["usuario"]
            ];
            if ($datos["clave"] !== null) {
                $sql .= ", clave = :clave";
                $params["clave"] = $datos["clave"];
            }
            $sql .= " WHERE id = :id";
            $stmt = ConexionBD::getInstancia()->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error en ActualizarAdminM: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Borra un administrador de la base de datos.
     */
    public static function BorrarAdminM($id) {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("DELETE FROM administradores WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en BorrarAdminM: " . $e->getMessage());
            return false;
        }
    }
}