<?php
require_once "ConexionBD.php";

class LoginM
{
    public static function VerificarUsuario(string $usuario, string $clave): array|false
    {
        $conexion = ConexionBD::getInstancia();
    
        // Consulta unificada que incluye el id específico de cada tipo
        $sql = "
            SELECT id AS id_usuario, id AS id_admin, NULL AS id_doctor, NULL AS id_paciente, NULL AS id_secretaria, usuario, rol, nombre, apellido, foto 
            FROM administradores 
            WHERE usuario = :usuario AND clave = :clave
    
            UNION ALL
    
            SELECT id AS id_usuario, NULL AS id_admin, id AS id_doctor, NULL AS id_paciente, NULL AS id_secretaria, usuario, rol, nombre, apellido, foto 
            FROM doctores 
            WHERE usuario = :usuario2 AND clave = :clave2
    
            UNION ALL
    
            SELECT id AS id_usuario, NULL AS id_admin, NULL AS id_doctor, id AS id_paciente, NULL AS id_secretaria, usuario, rol, nombre, apellido, foto 
            FROM pacientes 
            WHERE usuario = :usuario3 AND clave = :clave3
    
            UNION ALL
    
            SELECT id AS id_usuario, NULL AS id_admin, NULL AS id_doctor, NULL AS id_paciente, id AS id_secretaria, usuario, rol, nombre, apellido, foto 
            FROM secretarias 
            WHERE usuario = :usuario4 AND clave = :clave4
        ";
    
        $stmt = $conexion->prepare($sql);
        $params = [
            ':usuario' => $usuario,
            ':clave' => $clave,
            ':usuario2' => $usuario,
            ':clave2' => $clave,
            ':usuario3' => $usuario,
            ':clave3' => $clave,
            ':usuario4' => $usuario,
            ':clave4' => $clave
        ];
    
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
