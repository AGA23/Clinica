<?php


// 1. Cargar el entorno y la seguridad de la sesión.
require_once __DIR__ . '/../loader.php';

// 2. Establecer la cabecera para la respuesta JSON.
header('Content-Type: application/json; charset=utf-8');

// 3. Seguridad de Roles: Solo usuarios autorizados pueden ejecutar esta acción.
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Secretario'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

// 4. Lógica para ejecutar el script de envío.
try {
    // Obtenemos la ruta al ejecutable de PHP y al script de cron.
    // NOTA: En XAMPP, la ruta a php.exe puede variar.
    $php_executable = 'F:\\xampp\\php\\php.exe'; // ¡AJUSTA ESTA RUTA A TU XAMPP!
    $script_path = ROOT_PATH . 'cron/enviar_recordatorios.php';

    // Verificamos que ambos archivos existan.
    if (!file_exists($php_executable)) {
        throw new Exception("El ejecutable de PHP no se encontró en la ruta especificada: " . $php_executable);
    }
    if (!file_exists($script_path)) {
        throw new Exception("El script de envío de recordatorios no se encontró.");
    }

    // Ejecutamos el comando en segundo plano y capturamos la salida.
    // '2>&1' redirige la salida de error a la salida estándar para que podamos capturarla.
    $comando = escapeshellcmd($php_executable) . ' ' . escapeshellarg($script_path) . ' 2>&1';
    
    // Capturamos la salida del script para mostrarla al usuario.
    ob_start();
    passthru($comando, $return_code);
    $salida = ob_get_clean();

    // Verificamos si el comando se ejecutó correctamente (código de salida 0).
    if ($return_code === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'El proceso de envío de recordatorios se ha ejecutado.',
            'log' => nl2br(htmlspecialchars($salida)) // Convertimos la salida de la terminal a HTML
        ]);
    } else {
        throw new Exception("El script de envío falló con código de error: " . $return_code);
    }

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'error' => 'Ocurrió un error al ejecutar el proceso de envío.',
        'message' => $e->getMessage(),
        'log' => isset($salida) ? nl2br(htmlspecialchars($salida)) : ''
    ]);
}
?>