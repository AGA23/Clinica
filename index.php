<?php
// Configuración de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuración base
require_once __DIR__ . '/config.php';

ob_start();

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detectar si el usuario está en la vista de login
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$isLoginPage = strpos($requestUri, 'plantilla.php') !== false;

// Si NO está autenticado, redirigir al login
if (!isset($_SESSION["Ingresar"])) {
    if (!$isLoginPage) {
        header("Location: " . BASE_URL . "Vistas/plantilla.php");
        exit();
    }
} 
// Si SÍ está autenticado, redirigir según el rol
else {
    $rol = $_SESSION["rol"] ?? '';

    // Evitar que usuarios autenticados vean el login
    if ($isLoginPage) {
        switch ($rol) {
            case 'Administrador':
                header("Location: " . BASE_URL . "Vistas/dashboard.php?modulo=inicio");
                break;
            case 'Doctor':
                header("Location: " . BASE_URL . "Vistas/dashboard.php?modulo=inicioDoctor");
                break;
            case 'Secretaria':
                header("Location: " . BASE_URL . "Vistas/dashboard.php?modulo=inicioSecretaria");
                break;
            case 'Paciente':
                header("Location: " . BASE_URL . "Vistas/dashboard.php?modulo=inicioPaciente");
                break;
            default:
                session_destroy();
                header("Location: " . BASE_URL . "Vistas/plantilla.php?error=3");
        }
        exit();
    }
}

// Si llegó al login directamente
if ($isLoginPage) {
    ob_end_clean(); // Limpia el buffer
    require __DIR__ . '/Vistas/plantilla.php';
    exit();
}

// Lógica de enrutamiento por acción (para formularios como actualizarPerfil)
if (isset($_GET["action"])) {
    $action = $_GET["action"];
    
    switch ($action) {
        case "actualizarPerfil":
            require_once ROOT_PATH . "Controladores/pacientesC.php";
            require_once ROOT_PATH . "Modelos/pacientesM.php";

            $paciente = new PacientesC();
            $paciente->ActualizarPerfilPacienteC();
            exit(); // <- importante para evitar que cargue otras vistas
            break;

        default:
            echo "Acción no reconocida.";
            exit();
    }
}


// Carga el dashboard para cualquier otra ruta
require __DIR__ . '/Vistas/dashboard.php';
ob_end_flush();
