<?php

require_once "ConexionBD.php";

class pacientesM extends ConexionBD {

    // Crear Pacientes
    static public function CrearPacienteM($tablaBD, $datosC) {
        $pdo = ConexionBD::getInstancia()->prepare("INSERT INTO $tablaBD(apellido, nombre, usuario, clave, rol) VALUES (:apellido, :nombre, :usuario, :clave, :rol)");

        $pdo->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
        $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
        $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
        $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
        $pdo->bindParam(":rol", $datosC["rol"], PDO::PARAM_STR);

        if ($pdo->execute()) {
            return "ok";
        }

        return "error";
    }

    // Ver Pacientes
    static public function VerPacientesM($tablaBD, $columna, $valor) {
        if ($columna == null) {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD ORDER BY apellido ASC");
            $pdo->execute();
            return $pdo->fetchAll();
        } else {
            $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD WHERE $columna = :$columna ORDER BY apellido ASC");
            $pdo->bindParam(":".$columna, $valor, PDO::PARAM_STR);
            $pdo->execute();
            return $pdo->fetch();
        }
    }

    // Borrar Paciente
    static public function BorrarPacienteM($tablaBD, $id) {
        $pdo = ConexionBD::getInstancia()->prepare("DELETE FROM $tablaBD WHERE id = :id");
        $pdo->bindParam(":id", $id, PDO::PARAM_INT);

        if ($pdo->execute()) {
            return "ok";
        }

        return "error";
    }

   

    // Ingreso Paciente
    static public function IngresoPacienteM($tablaBD, $datosC) {
        $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD WHERE usuario = :usuario");
        $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
        $pdo->execute();
        return $pdo->fetch();
    }

    // Ver Perfil del Paciente
    static public function VerPerfilPacienteM($tablaBD, $id) {
        $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD WHERE id = :id");
        $pdo->bindParam(":id", $id, PDO::PARAM_INT);
        $pdo->execute();
        return $pdo->fetch();
    }

// Método para actualizar paciente
static public function ActualizarPacienteM($tablaBD, $datosC) {
    $sql = "UPDATE $tablaBD SET 
            nombre = :nombre,
            apellido = :apellido,
            usuario = :usuario,
            clave = :clave,
            foto = :foto,
            correo = :correo,
            telefono = :telefono,
            direccion = :direccion
            WHERE id = :id";

    $pdo = ConexionBD::getInstancia()->prepare($sql);

    $pdo->bindParam(":id", $datosC["id"], PDO::PARAM_INT);
    $pdo->bindParam(":nombre", $datosC["nombre"], PDO::PARAM_STR);
    $pdo->bindParam(":apellido", $datosC["apellido"], PDO::PARAM_STR);
    $pdo->bindParam(":usuario", $datosC["usuario"], PDO::PARAM_STR);
    $pdo->bindParam(":clave", $datosC["clave"], PDO::PARAM_STR);
    $pdo->bindParam(":foto", $datosC["foto"], PDO::PARAM_STR);
    $pdo->bindParam(":correo", $datosC["correo"], PDO::PARAM_STR);
    $pdo->bindParam(":telefono", $datosC["telefono"], PDO::PARAM_STR);
    $pdo->bindParam(":direccion", $datosC["direccion"], PDO::PARAM_STR);

    if ($pdo->execute()) {
        return "ok";
    } else {
        // Mostrar el error de la base de datos
        $errorInfo = $pdo->errorInfo();
        return "Error en la consulta: " . $errorInfo[2];
    }
    

}
}
