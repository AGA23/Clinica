<?php
require_once 'ConexionBD.php';

class MedicamentosM
{
    public static function ObtenerTodos()
    {
        $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM medicamentos ORDER BY nombre ASC");
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ObtenerCronicos()
    {
        $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM medicamentos WHERE es_cronico = 1 ORDER BY nombre ASC");
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ObtenerNoCronicos()
    {
        $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM medicamentos WHERE es_cronico = 0 ORDER BY nombre ASC");
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ObtenerPorPaciente($id_paciente)
    {
        $pdo = ConexionBD::getInstancia()->prepare("
            SELECT * FROM medicamentos 
            WHERE id_paciente = :id_paciente
            ORDER BY nombre ASC
        ");
        $pdo->bindParam(":id_paciente", $id_paciente, PDO::PARAM_INT);
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function GuardarParaCita($id_paciente, $datos)
    {
        $pdo = ConexionBD::getInstancia()->prepare("
            INSERT INTO medicamentos (id_paciente, nombre, dosis, frecuencia, fecha_inicio, fecha_fin, es_cronico, observaciones)
            VALUES (:id_paciente, :nombre, :dosis, :frecuencia, :fecha_inicio, :fecha_fin, :es_cronico, :observaciones)
        ");

        $pdo->bindParam(":id_paciente", $id_paciente, PDO::PARAM_INT);
        $pdo->bindParam(":nombre", $datos['nombre']);
        $pdo->bindParam(":dosis", $datos['dosis']);
        $pdo->bindParam(":frecuencia", $datos['frecuencia']);
        $pdo->bindParam(":fecha_inicio", $datos['fecha_inicio']);
        $pdo->bindParam(":fecha_fin", $datos['fecha_fin']);
        $pdo->bindParam(":es_cronico", $datos['es_cronico'], PDO::PARAM_INT);
        $pdo->bindParam(":observaciones", $datos['observaciones']);
        return $pdo->execute();
    }
}
