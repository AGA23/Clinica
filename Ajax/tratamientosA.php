<?php
// En Ajax/tratamientosA.php (VERSIÓN FINAL, COMPLETA Y SEGURA)

// 1. Cargar el entorno global (sesión, autoloader, config).
require_once __DIR__ . '/../loader.php';

// 2. Forzar la carga del modelo para máxima fiabilidad en AJAX.
require_once __DIR__ . '/../Modelos/TratamientosM.php';

// 3. Establecer la cabecera para la respuesta JSON.
header('Content-Type: application/json; charset=utf-8');

// 4. Seguridad de roles: solo Secretario y Administrador pueden acceder.
$rolesPermitidos = ['Secretario', 'Administrador'];
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $rolesPermitidos)) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

// 5. Determinar la acción solicitada.
$accion = $_GET['action'] ?? '';

// 6. Enrutador de acciones.
switch ($accion) {
    
    // Acción para llenar el modal de edición
    case 'obtener':
        $id = $_POST['id_tratamiento'] ?? 0;

        // --- ¡NUEVA VERIFICACIÓN DE SEGURIDAD! ---
        // Antes de devolver los datos, verificamos que el secretario tenga permiso para verlos.
        if (!TratamientosM::VerificarPertenenciaTratamientoM($id)) {
            echo json_encode(['success' => false, 'error' => 'Permiso denegado sobre este tratamiento.']);
            exit();
        }
        
        $tratamiento = TratamientosM::ObtenerTratamientoM($id);
        echo json_encode(['success' => (bool)$tratamiento, 'datos' => $tratamiento]);
        break;

    // Acción para eliminar un tratamiento
    case 'eliminar':
        $id = $_POST['id_tratamiento'] ?? 0;

        // --- ¡NUEVA VERIFICACIÓN DE SEGURIDAD! ---
        // Antes de borrar, verificamos que el secretario tenga permiso.
        if (!TratamientosM::VerificarPertenenciaTratamientoM($id)) {
            echo json_encode(['success' => false, 'error' => 'Permiso denegado para eliminar este tratamiento.']);
            exit();
        }

        $resultado = TratamientosM::BorrarTratamientoM($id);
        echo json_encode(['success' => $resultado, 'error' => $resultado ? '' : 'No se pudo eliminar. El tratamiento puede estar en uso.']);
        break;

    // Acción por defecto si no se reconoce el 'action'
    default:
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'Acción no reconocida.']);
        break;
}