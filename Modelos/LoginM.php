<?php

require_once "ConexionBD.php";

class LoginM extends ConexionBD {

    // Método para autenticar a cualquier tipo de usuario
    static public function AutenticarUsuarioM($usuario, $clave) {
        // Definir las tablas y roles
        $tablas = [
            "admin" => "Administrador",
            "doctores" => "Doctor",
            "pacientes" => "Paciente",
            "secretarias" => "Secretaria"
        ];

        // Recorrer las tablas y verificar las credenciales
        foreach ($tablas as $tabla => $rol) {
            try {
                $pdo = ConexionBD::getInstancia()->prepare("SELECT id, usuario, clave, nombre, apellido, foto, rol FROM $tabla WHERE usuario = :usuario");
                $pdo->bindParam(":usuario", $usuario, PDO::PARAM_STR);
                $pdo->execute();

                $resultado = $pdo->fetch();

                if ($resultado) {
                    // Verificar la contraseña
                    if (password_verify($clave, $resultado["clave"])) {
                        // Agregar el rol al resultado
                        $resultado["rol"] = $rol;
                        return $resultado;
                    } else {
                        error_log("Contraseña incorrecta para el usuario: " . $usuario);
                    }
                }
            } catch (PDOException $e) {
                error_log("Error en la consulta SQL: " . $e->getMessage());
            }
        }

        return false; // Si no se encuentra el usuario en ninguna tabla
    }
}
?>