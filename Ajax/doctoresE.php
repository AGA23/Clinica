<?php
session_start(); // Iniciar sesión al principio

// Verificar permisos de usuario
if ($_SESSION["rol"] != "Secretaria" && $_SESSION["rol"] != "Administrador") {
    echo json_encode(["error" => "No tienes permiso para realizar esta acción"]);
    exit();
}

require_once __DIR__ . "/../Modelos/DoctoresM.php"; // Incluir el modelo

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["Did"])) {
    $Did = filter_var($_POST["Did"], FILTER_VALIDATE_INT); // Validar el ID del doctor

    if ($Did) {
        // Eliminar el doctor
        $resultado = DoctoresM::BorrarDoctorM("doctores", $Did);

        if ($resultado) {
            // Respuesta JSON exitosa
            echo json_encode([
                'success' => true,
                'message' => 'Doctor eliminado correctamente.'
            ]);
        } else {
            // Respuesta JSON de error
            echo json_encode([
                'success' => false,
                'error' => 'No se pudo eliminar el doctor.'
            ]);
        }
    } else {
        // Respuesta JSON de error si el ID no es válido
        echo json_encode([
            'success' => false,
            'error' => 'ID de doctor inválido'
        ]);
    }
} else {
    // Respuesta JSON de error si no se proporcionó un ID
    echo json_encode([
        'success' => false,
        'error' => 'ID de doctor no proporcionado'
    ]);
}
?>