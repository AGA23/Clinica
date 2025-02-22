<?php

require_once "ConexionBD.php";

class AdminM extends ConexionBD{

    //Ingresar Admin
    static public function IngresarAdminM($tablaBD, $datosC){
        // Cambiar cBD() por getInstancia()
        $pdo = ConexionBD::getInstancia()->prepare("SELECT id, usuario, clave, nombre, apellido, foto, rol FROM $tablaBD WHERE usuario = :usuario");

        $pdo -> bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);

        $pdo->execute();

        return $pdo -> fetch();

        $pdo->close();
        $pdo = null;
    }

    static public function IngresarAdmin2M($tablaBD, $datosC){
        // Cambiar cBD() por getInstancia()
        $pdo = ConexionBD::getInstancia()->prepare("SELECT id, usuario, clave, nombre, apellido, foto, rol FROM $tablaBD WHERE clave = :clave");

        $pdo -> bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);

        $pdo->execute();

        return $pdo -> fetch();

        $pdo->close();
        $pdo = null;
    }

    //Ver Perfil Admin
    static public function VerPerfilAdminM($tablaBD, $id){
        // Cambiar cBD() por getInstancia()
        $pdo = ConexionBD::getInstancia()->prepare("SELECT id, usuario, clave, nombre, apellido, foto FROM $tablaBD WHERE id = :id");

        $pdo -> bindParam(":id", $id, PDO::PARAM_INT);

        $pdo->execute();

        return $pdo -> fetch();

        $pdo->close();
        $pdo = null;
    }

    //Actualizar Perfil
    static public function ActualizarPerfilAdminM($tablaBD, $datosC){
        // Cambiar cBD() por getInstancia()
        $pdo = ConexionBD::getInstancia()->prepare("UPDATE $tablaBD SET usuario = :usuario, clave = :clave, nombre = :nombre, apellido = :apellido, foto = :foto WHERE id = :id");

        $pdo ->bindParam(":id", $datosC["id"], PDO::PARAM_INT);
        $pdo ->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
        $pdo ->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
        $pdo ->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
        $pdo ->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
        $pdo ->bindParam(":foto", $datosC["foto"], PDO::PARAM_STR);

        if($pdo -> execute()){
            return true;
        }

        $pdo->close();
        $pdo = null;
    }

}
?>
