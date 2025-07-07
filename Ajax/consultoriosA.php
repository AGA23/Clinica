<?php
require_once "../Controladores/consultoriosC.php";
require_once "../Modelos/consultoriosM.php";
session_start();
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_GET["action"])) {

    if ($_GET["action"] === "CambiarEstadoConsultorio") {

        $id_consultorio = $_POST["id_consultorio"] ?? null;
        $fecha = $_POST["fecha"] ?? null;
        $estado = $_POST["estado"] ?? null;
        $motivo = $_POST["motivo"] ?? null;
        $id_usuario = $_SESSION["id"] ?? null;

        if ($id_consultorio && $fecha && $estado && $id_usuario) {
            $resultado = ConsultoriosC::CambiarEstadoManualC(
                $id_consultorio,
                $fecha,
                $estado,
                $motivo,
                $id_usuario
            );

            if ($resultado === "ok") {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "error" => "No se pudo guardar en la base de datos."]);
            }
        } else {
            echo json_encode(["success" => false, "error" => "Faltan datos."]);
        }

    } else {
        echo json_encode(["success" => false, "error" => "Acción no válida."]);
    }

} else {
    echo json_encode(["success" => false, "error" => "Petición no válida."]);
}
