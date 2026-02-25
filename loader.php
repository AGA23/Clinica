<?php
// --- PASO 1: AUTOLOADER DE COMPOSER ---
$autoloaderPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloaderPath)) {
    require_once $autoloaderPath;
} else {
    die("Error crítico: No se encontró vendor/autoload.php");
}

// --- PASO 2: CONFIG ---
require_once __DIR__ . '/config.php';

// --- PASO 3: SESIÓN ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- PASO 4: AUTOLOAD MVC ---
spl_autoload_register(function ($nombreClase) {
    $paths = [
        __DIR__ . "/Modelos/$nombreClase.php",
        __DIR__ . "/Controladores/$nombreClase.php"
    ];

    foreach ($paths as $archivo) {
        if (file_exists($archivo)) {
            require_once $archivo;
            return;
        }
    }
});

// --- PASO 5: CARGAS EXPLÍCITAS ---
require_once __DIR__ . '/Modelos/ClinicaM.php';
require_once __DIR__ . '/Modelos/ConsultoriosM.php';
require_once __DIR__ . '/Modelos/plantillacorreoM.php';
require_once __DIR__ . '/Modelos/AdminM.php';
require_once __DIR__ . '/Modelos/plantillas-documentosM.php';
require_once __DIR__ . '/Controladores/plantillas-documentosC.php';
require_once __DIR__ . '/Modelos/ReportesM.php';
require_once __DIR__ . '/Controladores/ReportesC.php';
require_once __DIR__ . '/Modelos/BloqueoM.php';
require_once __DIR__ . '/Controladores/BloqueoC.php';
require_once __DIR__ . '/Controladores/obrasocialesC.php';
require_once __DIR__ . '/Modelos/ObrasSocialesM.php';
require_once __DIR__ . '/Controladores/ControladorGlobal.php';

// --- PASO 6: SEGURIDAD ---
if (!empty($_SESSION['Ingresar']) && $_SESSION['Ingresar'] === true) {

    $rolesPermitidos = ['Administrador', 'Doctor', 'Paciente', 'Secretario'];

    if (empty($_SESSION['rol']) || !in_array($_SESSION['rol'], $rolesPermitidos)) {
        session_destroy();
        header("Location: " . BASE_URL . "index.php?error=3");
        exit;
    }

    // --- PASO 7: CITAS AUSENTES (cada 5 min) ---
    if (
        !isset($_SESSION['last_run_ausentes']) ||
        time() - $_SESSION['last_run_ausentes'] > 300
    ) {
        ControladorGlobal::MarcarCitasAusentesAutomatico();
        $_SESSION['last_run_ausentes'] = time();
    }
}
