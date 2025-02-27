<?php

require_once "ConexionBD.php";

class DoctoresM extends ConexionBD {

    // Crear Doctores
    static public function CrearDoctorM($tablaBD, $datosC) {
        $pdo = ConexionBD::getInstancia()->prepare("INSERT INTO $tablaBD(apellido, nombre, sexo, id_consultorio, usuario, clave, rol) VALUES(:apellido, :nombre, :sexo, :id_consultorio, :usuario, :clave, :rol)");

        $pdo->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
        $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
        $pdo->bindParam(":sexo", $datosC["sexo"], PDO::PARAM_STR);
        $pdo->bindParam(":id_consultorio", $datosC["id_consultorio"], PDO::PARAM_INT);
        $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
        $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
        $pdo->bindParam(":rol", $datosC["rol"], PDO::PARAM_STR);

        if ($pdo->execute()) {
            return true;
        }

        return false;
    }

    // Mostrar Doctores
    static public function VerDoctoresM($tablaBD, $columna = null, $valor = null) {
        $valid_columns = ['id', 'apellido', 'nombre', 'sexo', 'id_consultorio', 'usuario', 'rol']; // Columnas válidas

        if ($columna != null && in_array($columna, $valid_columns)) {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD WHERE $columna = :$columna");
            $pdo->bindParam(":".$columna, $valor, PDO::PARAM_STR);
            $pdo->execute();
            return $pdo->fetchAll();
        } else {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD");
            $pdo->execute();
            return $pdo->fetchAll();
        }
    }

    // Editar Doctor
    static public function DoctorM($tablaBD, $columna, $valor) {
        $valid_columns = ['id', 'apellido', 'nombre', 'sexo', 'usuario']; // Columnas válidas
        if ($columna != null && in_array($columna, $valid_columns)) {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD WHERE $columna = :$columna");
            $pdo->bindParam(":".$columna, $valor, PDO::PARAM_STR);
            $pdo->execute();
            return $pdo->fetch(PDO::FETCH_ASSOC); // Retorna un solo registro como array asociativo
        }
        return false; // Retorna false si la columna no es válida
    }

    // Actualizar Doctores
    static public function ActualizarDoctorM($tablaBD, $datosC) {
        $sql = "UPDATE $tablaBD SET apellido = :apellido, nombre = :nombre, sexo = :sexo, usuario = :usuario";
        
        // Solo agregar la clave si se proporciona
        if (!empty($datosC["clave"])) {
            $sql .= ", clave = :clave";
        }

        $sql .= " WHERE id = :id";

        $pdo = ConexionBD::getInstancia()->prepare($sql);

        $pdo->bindParam(":id", $datosC["id"], PDO::PARAM_INT);
        $pdo->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
        $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
        $pdo->bindParam(":sexo", $datosC["sexo"], PDO::PARAM_STR);
        $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);

        // Solo vincular la clave si es proporcionada
        if (!empty($datosC["clave"])) {
            $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
        }

        if ($pdo->execute()) {
            return true;
        }

        return false;
    }

    // Eliminar Doctor
    static public function BorrarDoctorM($tablaBD, $id) {
        $pdo = ConexionBD::getInstancia()->prepare("DELETE FROM $tablaBD WHERE id = :id");
        $pdo->bindParam(":id", $id, PDO::PARAM_INT);

        if ($pdo->execute()) {
            return true;
        }

        return false;
    }

    // Iniciar sesión doctor
    static public function IngresarDoctorM($tablaBD, $datosC) {
        $pdo = ConexionBD::getInstancia()->prepare("SELECT usuario, clave, apellido, nombre, sexo, foto, rol, id FROM $tablaBD WHERE usuario = :usuario");
        $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
        $pdo->execute();
        return $pdo->fetch();
    }

    // Ver Perfil Doctor
    static public function VerPerfilDoctorM($tablaBD, $id) {
        $pdo = ConexionBD::getInstancia()->prepare("SELECT usuario, clave, apellido, nombre, sexo, foto, rol, id, horarioE, horarioS, id_consultorio FROM $tablaBD WHERE id = :id");
        $pdo->bindParam(":id", $id, PDO::PARAM_STR);
        $pdo->execute();
        return $pdo->fetch();
    }

    // Actualizar Perfil Doctor
    static public function ActualizarPerfilDoctorM($tablaBD, $datosC) {
        $sql = "UPDATE $tablaBD SET id_consultorio = :id_consultorio, apellido = :apellido, nombre = :nombre, foto = :foto, usuario = :usuario, horarioE = :horarioE, horarioS = :horarioS WHERE id = :id";
        
        // Solo agregar la clave si se proporciona
        if (!empty($datosC["clave"])) {
            $sql .= ", clave = :clave";
        }

        $pdo = ConexionBD::getInstancia()->prepare($sql);

        $pdo->bindParam(":id", $datosC["id"], PDO::PARAM_INT);
        $pdo->bindParam(":id_consultorio", $datosC["id_consultorio"], PDO::PARAM_INT); // Corregido aquí
        $pdo->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
        $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
        $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
        $pdo->bindParam(":foto", $datosC["foto"], PDO::PARAM_STR);
        $pdo->bindParam(":horarioE", $datosC["horarioE"], PDO::PARAM_STR);
        $pdo->bindParam(":horarioS", $datosC["horarioS"], PDO::PARAM_STR);

        // Solo vincular la clave si es proporcionada
        if (!empty($datosC["clave"])) {
            $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
        }

        if ($pdo->execute()) {
            return true;
        }

        return false;
    }
}
?>
