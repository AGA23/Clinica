<?php
require_once 'ConexionBD.php';

class TratamientosM
{
    public static function ObtenerTodos()
    {
        $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM tratamientos ORDER BY nombre ASC");
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ObtenerPorDoctor($id_doctor)
    {
        $pdo = ConexionBD::getInstancia()->prepare("
            SELECT t.*
            FROM tratamientos t
            INNER JOIN doctor_tratamiento dt ON dt.id_tratamiento = t.id
            WHERE dt.id_doctor = :id_doctor
        ");
        $pdo->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ObtenerTratamientosPorDoctor($id_doctor)
{
    $pdo = ConexionBD::getInstancia()->prepare("
        SELECT t.*
        FROM tratamientos t
        INNER JOIN doctor_tratamiento dt ON dt.id_tratamiento = t.id
        WHERE dt.id_doctor = :id_doctor
        ORDER BY t.nombre ASC
    ");
    $pdo->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
    $pdo->execute();
    return $pdo->fetchAll(PDO::FETCH_ASSOC);
}


    public static function AsociarTratamientosADoctor($id_doctor, $ids_tratamientos)
    {
        $pdo = ConexionBD::getInstancia();

        // Eliminar asociaciones previas
        $stmt = $pdo->prepare("DELETE FROM doctor_tratamiento WHERE id_doctor = :id_doctor");
        $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
        $stmt->execute();

        // Insertar nuevas asociaciones
        $stmt = $pdo->prepare("INSERT INTO doctor_tratamiento (id_doctor, id_tratamiento) VALUES (:id_doctor, :id_tratamiento)");

        foreach ($ids_tratamientos as $id_tratamiento) {
            $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
            $stmt->bindParam(":id_tratamiento", $id_tratamiento, PDO::PARAM_INT);
            $stmt->execute();
        }
    

        return true;
    }
}
