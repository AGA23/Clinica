<?php
require_once __DIR__ . '/../../config.php';
// 1. Verificar si hay una sesión activa antes de manipularla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Limpiar todas las variables de sesión
$_SESSION = []; // Vacía el array, pero la sesión sigue existiendo

// 3. Borrar la cookie de sesión (recomendado para seguridad)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000, // Tiempo en el pasado para expirar la cookie
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Destruir la sesión y verificar si tuvo éxito
if (session_destroy()) {
    // Redirigir usando una ruta dinámica (ej: con BASE_URL)
    header("Location: " . BASE_URL . "Vistas/plantilla.php");
} else {
    // Opcional: Manejar error (ej: registrar en logs o mostrar mensaje)
    die("Error al cerrar sesión. Por favor, inténtalo de nuevo.");
}

exit(); // Asegura que no se ejecute código adicional
?>