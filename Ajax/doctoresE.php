<?php
session_start();
require_once "../Modelos/DoctoresM.php";  // Incluye el modelo para interactuar con la base de datos
require_once "../Modelos/ConsultoriosM.php"; // Si usas consultas relacionadas con los consultorios

// Log para verificar sesión
error_log("Rol del usuario: " . $_SESSION["rol"]);

// Asegúrate de que el usuario tenga permisos adecuados
if ($_SESSION["rol"] != "Secretaria" && $_SESSION["rol"] != "Administrador") {
    echo json_encode(["error" => "No tienes permiso para realizar esta acción"]);
    exit();
}

if (isset($_POST["Did"])) {
    $idDoctor = $_POST["Did"];
    $apellido = $_POST["apellidoE"];
    $nombre = $_POST["nombreE"];
    $usuario = $_POST["usuarioE"];
    $clave = $_POST["claveE"];
    $sexo = $_POST["sexoE"];

    // Log para verificar que los datos se recibieron correctamente
    error_log("Datos recibidos: ID=" . $idDoctor . ", Apellido=" . $apellido);

    // Aquí puedes agregar la lógica para actualizar la información del doctor
    $resultado = DoctoresM::ActualizarDoctor($idDoctor, $apellido, $nombre, $usuario, $clave, $sexo);

    if ($resultado) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Hubo un error al actualizar los datos del doctor"]);
    }
} else {
    echo json_encode(["success" => false, "error" => "ID de doctor no proporcionado"]);
}
?>
