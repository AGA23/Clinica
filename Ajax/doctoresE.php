<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["Ingresar"]) || $_SESSION["Ingresar"] !== true) {
    echo json_encode([
        'success' => false,
        'error' => 'Debes iniciar sesión para realizar esta acción',
        'redirect' => '/clinica/login'
    ]);
    exit();
}

if ($_SESSION["rol"] != "Secretaria" && $_SESSION["rol"] != "Administrador") {
    echo json_encode([
        'success' => false,
        'error' => 'No tienes permisos suficientes'
    ]);
    exit();
}

require_once __DIR__ . "/../Modelos/DoctoresM.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["Did"])) {
    $Did = filter_var($_POST["Did"], FILTER_VALIDATE_INT);

    if (!$Did) {
        echo json_encode([
            'success' => false,
            'error' => 'ID de doctor inválido'
        ]);
        exit();
    }

    // ✅ Eliminar tratamientos asociados
    DoctoresM::EliminarTratamientosDoctorM($Did);

    // Luego eliminar el doctor
    $resultado = DoctoresM::BorrarDoctorM("doctores", $Did);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Doctor eliminado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Error al eliminar el doctor'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Solicitud inválida'
    ]);
}
?>
