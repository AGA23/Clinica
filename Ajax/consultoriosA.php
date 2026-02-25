<?php
// En Ajax/consultoriosA.php

// 1. Cargar el entorno global (sesión, autoloader, config).
require_once __DIR__ . '/../loader.php';

// 2. Forzar la carga de modelos necesarios para este endpoint.
require_once __DIR__ . '/../Modelos/ConsultoriosM.php';
require_once __DIR__ . '/../Modelos/TratamientosM.php';

// 3. Establecer la cabecera para la respuesta JSON.
header('Content-Type: application/json; charset=utf-8');

// 4. Seguridad de roles: Se verifica el rol dentro de cada acción específica.
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Secretario'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

// 5. Determinar la acción solicitada.
$accion = $_REQUEST['action'] ?? ''; // Usamos $_REQUEST para aceptar GET y POST

// 6. Enrutador de acciones.
switch ($accion) {
    
    // --- Acción para el modal de "Editar Consultorio" ---
    case 'obtenerParaEditar':
        $id = $_POST['id_consultorio'] ?? 0;
        if ($id > 0) {
            // Un secretario solo puede obtener datos de su propio consultorio.
            if ($_SESSION['rol'] === 'Secretario' && $id != $_SESSION['id_consultorio']) {
                echo json_encode(['success' => false, 'error' => 'Permiso denegado.']);
                exit();
            }

            $consultorio = ConsultoriosM::VerConsultorioPorId($id);
            $horarios = ConsultoriosM::ObtenerHorariosParaEdicionM($id);

            if ($consultorio) {
                echo json_encode(['success' => true, 'datos' => $consultorio, 'horarios' => $horarios]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Consultorio no encontrado.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'ID de consultorio inválido.']);
        }
        break;

    // --- Acción para el botón de "Eliminar" (Solo Admin) ---
    case 'eliminar':
        if ($_SESSION['rol'] !== 'Administrador') {
            echo json_encode(['success' => false, 'error' => 'Solo los administradores pueden eliminar consultorios.']);
            exit();
        }
        $id = $_POST['id_consultorio'] ?? 0;
        if ($id > 0) {
            $resultado = ConsultoriosM::BorrarConsultorioM("consultorios", $id);
            if ($resultado) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No se pudo eliminar. El consultorio puede tener doctores o citas asociadas.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'ID de consultorio inválido.']);
        }
        break;

    // --- Acción para el modal de "Cambiar Estado" (Solo Admin) ---
    case 'cambiarEstado':
        if ($_SESSION['rol'] !== 'Administrador') {
            echo json_encode(['success' => false, 'error' => 'Solo los administradores pueden cambiar el estado manual.']);
            exit();
        }
        $id = $_POST['id_consultorio'] ?? 0;
        $estado = $_POST['estado'] ?? '';
        $motivo = $_POST['motivo'] ?? '';
        if ($id > 0) {
            $resultado = ConsultoriosM::ActualizarEstadoManualM($id, $estado, $motivo, $_SESSION['id']);
            echo json_encode(['success' => $resultado]);
        } else {
            echo json_encode(['success' => false, 'error' => 'ID de consultorio inválido.']);
        }
        break;

    // --- Acción para obtener tratamientos (Solo Admin) ---
    case 'obtenerTratamientosParaAsignar':
        if ($_SESSION['rol'] !== 'Administrador') {
            echo json_encode(['success' => false, 'error' => 'Solo los administradores pueden asignar tratamientos.']);
            exit();
        }
        $id_consultorio = $_POST['id_consultorio'] ?? 0;
        if ($id_consultorio > 0) {
            $todos_los_tratamientos = TratamientosM::ListarTratamientosM();
            $tratamientos_asignados = TratamientosM::ObtenerTratamientosAsignadosAConsultorioM($id_consultorio);
            echo json_encode(['success' => true, 'todos' => $todos_los_tratamientos, 'asignados' => $tratamientos_asignados]);
        } else {
            echo json_encode(['success' => false, 'error' => 'ID de consultorio inválido.']);
        }
        break;

    // --- Acción para guardar las asignaciones (Solo Admin) ---
    case 'guardarTratamientosAsignados':
        if ($_SESSION['rol'] !== 'Administrador') {
            echo json_encode(['success' => false, 'error' => 'Solo los administradores pueden asignar tratamientos.']);
            exit();
        }
        $id_consultorio = $_POST['id_consultorio'] ?? 0;
        $ids_tratamientos = json_decode($_POST['ids_tratamientos'] ?? '[]', true);
        if ($id_consultorio > 0) {
            $resultado = TratamientosM::GuardarAsignacionConsultorioM($id_consultorio, $ids_tratamientos);
            echo json_encode(['success' => $resultado]);
        } else {
            echo json_encode(['success' => false, 'error' => 'ID de consultorio inválido.']);
        }
        break;

    // --- Acción por defecto si no se reconoce el 'action' ---
    default:
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'Acción no reconocida en el endpoint de consultorios: ' . htmlspecialchars($accion)]);
        break;
}