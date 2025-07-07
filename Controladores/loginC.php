<?php
declare(strict_types=1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar método
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../Vistas/plantilla.php?error=3");
    exit();
}

// Obtener campos del formulario
$usuario = $_POST['usuario-Ing'] ?? '';
$clave = $_POST['clave-Ing'] ?? '';

if (empty($usuario) || empty($clave)) {
    header("Location: ../Vistas/plantilla.php?error=2");
    exit();
}

// Incluir modelo de login
require_once "../Modelos/loginM.php";

// Consultar al modelo
$resultado = LoginM::VerificarUsuario($usuario, $clave);
if (!$resultado) {
    error_log("⚠️ Login falló - No se encontraron datos para el usuario '$usuario' con esa clave.");
    sleep(1);
    header("Location: ../Vistas/plantilla.php?error=1");
    exit();
} else {
    error_log("✅ Login exitoso - Resultado:");
    error_log(print_r($resultado, true));
}

// Regenerar ID de sesión y guardar datos
session_regenerate_id(true);


// También guardar los datos en $_SESSION["usuario"] para accesos centralizados
$_SESSION["usuario"] = [
    "id" => (int)($resultado["id_usuario"] ?? 0),
    "nombre" => $resultado["nombre"] ?? '',
    "rol" => $resultado["rol"] ?? ''
];


// Establecer sesión común
$_SESSION = [
    "Ingresar" => true,
    "rol" => $resultado["rol"] ?? '',
    "id" => (int)($resultado["id_usuario"] ?? 0),
    "nombre" => $resultado["nombre"] ?? '',
    "apellido" => $resultado["apellido"] ?? '',
    "foto" => $resultado["foto"] ?? '',
    "__ip" => $_SERVER["REMOTE_ADDR"],
    "__user_agent" => $_SERVER["HTTP_USER_AGENT"],
    "__last_activity" => time()
];

// Guardar ID específico según el rol
switch (strtolower($resultado["rol"] ?? '')) {
    case 'doctor':
        $_SESSION["id_doctor"] = (int)($resultado["id_doctor"] ?? 0);
        error_log("🩺 ID doctor guardado en sesión: " . $_SESSION["id_doctor"]);
        break;
    case 'paciente':
        $_SESSION["id_paciente"] = (int)($resultado["id_paciente"] ?? 0);
        error_log("👤 ID paciente guardado en sesión: " . $_SESSION["id_paciente"]);
        break;
    case 'secretaria':
        $_SESSION["id_secretaria"] = (int)($resultado["id_secretaria"] ?? 0);
        error_log("💼 ID secretaria guardado en sesión: " . $_SESSION["id_secretaria"]);
        break;
    case 'administrador':
        $_SESSION["id_admin"] = (int)($resultado["id_admin"] ?? 0);
        error_log("🛠️ ID admin guardado en sesión: " . $_SESSION["id_admin"]);
        break;
}

header("Location: /clinica/index.php");
exit();
