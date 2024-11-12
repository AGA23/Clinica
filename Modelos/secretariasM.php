<?php

require_once "ConexionBD.php"; // Ruta correcta para incluir la clase ConexionBD

class SecretariasM extends ConexionBD {

    // Ingreso Secretarias
    static public function IngresarSecretariaM($tablaBD, $datosC) {
        try {
            // Verificar tanto usuario como clave
            $pdo = ConexionBD::getInstancia()->prepare("SELECT usuario, clave, nombre, apellido, foto, rol, id FROM $tablaBD WHERE usuario = :usuario AND clave = :clave");
            $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
            $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
            
            if ($pdo->execute()) {
                // Si encuentra una fila, retorna los datos
                if ($pdo->rowCount() > 0) {
                    return $pdo->fetch();
                }
                return false; // No encontró el usuario o la clave no coincide
            }
        } catch (PDOException $e) {
            // Manejar errores de conexión o consulta
            echo "Error en la consulta: " . $e->getMessage();
        }

        return false; // Si falla la ejecución
    }

    // Ver perfil secretaria
    static public function VerPerfilSecretariaM($tablaBD, $id) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT usuario, clave, nombre, apellido, foto, rol, id FROM $tablaBD WHERE id = :id");
            $pdo->bindParam(":id", $id, PDO::PARAM_INT);

            if ($pdo->execute()) {
                return $pdo->fetch();
            }
        } catch (PDOException $e) {
            echo "Error en la consulta: " . $e->getMessage();
        }

        return false;
    }

    // Actualizar Perfil Secretaria
    static public function ActualizarPerfilSecretariaM($tablaBD, $datosC) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("UPDATE $tablaBD SET usuario = :usuario, clave = :clave, nombre = :nombre, apellido = :apellido, foto = :foto WHERE id = :id");
            
            $pdo->bindParam(":id", $datosC["id"], PDO::PARAM_INT);
            $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
            $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
            $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
            $pdo->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
            $pdo->bindParam(":foto", $datosC["foto"], PDO::PARAM_STR);
            
            return $pdo->execute();
        } catch (PDOException $e) {
            echo "Error en la consulta: " . $e->getMessage();
        }

        return false;
    }

    // Mostrar Secretarias
    static public function VerSecretariasM($tablaBD) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD ORDER BY apellido ASC");
            
            if ($pdo->execute()) {
                return $pdo->fetchAll();
            }
        } catch (PDOException $e) {
            echo "Error en la consulta: " . $e->getMessage();
        }

        return []; // Retorna un array vacío si falla la ejecución
    }

    // Crear Secretarias
    static public function CrearSecretariaM($tablaBD, $datosC) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("INSERT INTO $tablaBD (nombre, apellido, usuario, clave, rol) VALUES (:nombre, :apellido, :usuario, :clave, :rol)");
            
            $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
            $pdo->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
            $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
            $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
            $pdo->bindParam(":rol", $datosC["rol"], PDO::PARAM_STR);
            
            return $pdo->execute();
        } catch (PDOException $e) {
            echo "Error en la consulta: " . $e->getMessage();
        }

        return false;
    }

    // Borrar Secretarias
    static public function BorrarSecretariaM($tablaBD, $id) {
        try {
            $pdo = ConexionBD::getInstancia()->prepare("DELETE FROM $tablaBD WHERE id = :id");
            $pdo->bindParam(":id", $id, PDO::PARAM_INT);
            
            return $pdo->execute();
        } catch (PDOException $e) {
            echo "Error en la consulta: " . $e->getMessage();
        }

        return false;
    }
}
?>
