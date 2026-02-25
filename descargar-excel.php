<?php
// --- ENCENDER ERRORES PARA DEPURACIÓN ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ----------------------------------------

require_once __DIR__ . '/loader.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Secretario'])) {
    die("Acceso denegado. Rol actual: " . ($_SESSION['rol'] ?? 'Ninguno'));
}

$mes = $_GET['mes'] ?? null;
$fecha_desde = $_GET['fecha_desde'] ?? null;
$fecha_hasta = $_GET['fecha_hasta'] ?? null;
$id_consultorio = $_GET['id_consultorio'] ?? null;

if ($id_consultorio === '') {
    $id_consultorio = null;
}

if ($mes) {
    $fecha_desde = $mes . '-01';
    $fecha_hasta = date('Y-m-t', strtotime($fecha_desde));
    $label = "Mes " . $mes;
} else if ($fecha_desde && $fecha_hasta) {
    $label = date('d/m/Y', strtotime($fecha_desde)) . ' al ' . date('d/m/Y', strtotime($fecha_hasta));
} else {
    die("Faltan parámetros de fecha para generar el reporte.");
}

// Verificamos si la clase existe antes de llamarla
if (!class_exists('ReportesC')) {
    die("ERROR FATAL: La clase ReportesC no se está cargando. Revisa tu loader.php.");
}

// Limpiamos el buffer (apagado temporalmente si queremos ver errores impresos)
// if (ob_get_length()) { ob_clean(); } 

$datos = ReportesC::obtenerDatosCompletosReporte($fecha_desde, $fecha_hasta, $id_consultorio);

if (empty($datos)) {
    die("ERROR: El controlador no devolvió datos.");
}

ReportesC::ExportarAExcel($datos, $label);