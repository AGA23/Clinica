<?php
// En Vistas/modulos/salir.php

// 1. CARGAR LA CONFIGURACIÓN
// Es necesario para tener acceso a la constante BASE_URL.
require_once __DIR__ . '/../../config.php';

// 2. INICIAR LA SESIÓN DE FORMA SEGURA
// Se necesita acceder a la sesión para poder destruirla.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. LIMPIAR TODAS LAS VARIABLES DE SESIÓN
// Vacía el array $_SESSION por completo.
$_SESSION = [];

// 4. BORRAR LA COOKIE DE SESIÓN
// Es una buena práctica de seguridad para asegurar que la cookie del navegador se elimine.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000, // Tiempo en el pasado para que la cookie expire inmediatamente.
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. DESTRUIR LA SESIÓN FINALMENTE
// Invalida el ID de sesión en el servidor.
session_destroy();

// 6. REDIRIGIR A LA PÁGINA DE INICIO (LOGIN)
// CORRECCIÓN DEFINITIVA: Se redirige a la URL raíz (BASE_URL),
// que ahora es manejada por tu index.php como la página de login.
header("Location: " . BASE_URL);
exit(); // Asegura que no se ejecute ningún código adicional.

?>