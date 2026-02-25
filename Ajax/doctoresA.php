<?php
// En Ajax/doctoresA.php (VERSIÓN FINAL Y COMPLETA CON LÓGICA ESTRICTA)

// 1. Cargar el entorno global (sesión, autoloader, config).
require_once __DIR__ . '/../loader.php';

// 2. Forzar la carga de modelos si el autoloader es inconsistente.
//    Esto asegura que las clases siempre estén disponibles.
require_once __DIR__ . '/../Modelos/DoctoresM.php';
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

// 5. Función de seguridad reutilizable para verificar permisos por consultorio.
function verificarPermisoDoctor($id_doctor) {
    if ($_SESSION['rol'] === 'Secretario') {
        $doctor = DoctoresM::ObtenerDoctorM($id_doctor);
        if (!$doctor || $doctor['id_consultorio'] != $_SESSION['id_consultorio']) {
            echo json_encode(['success' => false, 'error' => 'Permiso denegado sobre este doctor.']);
            exit();
        }
    }
}

// 6. Determinar la acción solicitada.
$accion = $_GET['action'] ?? '';

// 7. Enrutador de acciones.
switch ($accion) {
    
    case 'obtener':
        $id = $_POST['id_doctor'] ?? 0;
        verificarPermisoDoctor($id);
        $doctor = DoctoresM::ObtenerDoctorM($id);
        echo $doctor ? json_encode(['success' => true, 'datos' => $doctor]) : json_encode(['success' => false, 'error' => 'Doctor no encontrado.']);
        break;

    case 'eliminar':
        $id = $_POST['id_doctor'] ?? 0;
        verificarPermisoDoctor($id);
        $resultado = DoctoresM::BorrarDoctorM($id);
        echo json_encode(['success' => $resultado, 'error' => $resultado ? '' : 'No se pudo eliminar. El doctor puede tener citas asociadas.']);
        break;

    case 'obtenerHorarios':
        $id = $_POST['id_doctor'] ?? 0;
        verificarPermisoDoctor($id);
        $horarios = DoctoresM::ObtenerHorariosDoctorM($id);
        echo json_encode(['success' => true, 'horarios' => $horarios]);
        break;

    case 'guardarHorarios':
        $id = $_POST['id_doctor'] ?? 0;
        $horarios = json_decode($_POST['horarios'] ?? '[]', true);
        verificarPermisoDoctor($id);
        $resultado = DoctoresM::GuardarHorariosDoctorM($id, $horarios);
        echo json_encode(['success' => $resultado, 'error' => $resultado ? '' : 'No se pudieron guardar los horarios. Verifique los triggers.']);
        break;

    // --- ¡CASO MODIFICADO PARA IMPLEMENTAR LA LÓGICA ESTRICTA! ---
    case 'obtenerTratamientosParaAsignar':
        $id_doctor = $_POST['id_doctor'] ?? 0;
        verificarPermisoDoctor($id_doctor);
        
        // 1. Obtenemos los datos completos del doctor para saber a qué consultorio pertenece.
        $doctor = DoctoresM::ObtenerDoctorM($id_doctor);

        // 2. Verificación de seguridad: si el doctor no existe o no tiene consultorio, no se pueden asignar tratamientos.
        if (!$doctor || empty($doctor['id_consultorio'])) {
            // Se envía una respuesta exitosa pero con listas vacías y un mensaje de error.
            echo json_encode([
                'success' => true,
                'todos' => [], 
                'asignados' => [],
                'error_message' => 'Este doctor no tiene un consultorio asignado. Por favor, asígnele uno antes de gestionar sus tratamientos.'
            ]);
            exit();
        }
        
        $id_consultorio_del_doctor = $doctor['id_consultorio'];

        // 3. Obtenemos solo los tratamientos DISPONIBLES para el consultorio de ese doctor.
        $tratamientos_disponibles_en_consultorio = TratamientosM::ObtenerTratamientosPorConsultorioM($id_consultorio_del_doctor);
        
        // 4. Obtenemos los tratamientos que el doctor ya tiene asignados personalmente.
        $tratamientos_ya_asignados_al_doctor = DoctoresM::ObtenerTratamientosAsignadosM($id_doctor);
        
        // 5. Se envían las listas al JavaScript.
        echo json_encode([
            'success' => true, 
            'todos' => $tratamientos_disponibles_en_consultorio, // Enviamos la lista FILTRADA
            'asignados' => $tratamientos_ya_asignados_al_doctor
        ]);
        break;
    // --- FIN DEL CASO MODIFICADO ---

    case 'guardarTratamientosAsignados':
        $id_doctor = $_POST['id_doctor'] ?? 0;
        verificarPermisoDoctor($id_doctor);
        
        $ids_tratamientos = json_decode($_POST['ids_tratamientos'] ?? '[]', true);
        
        // Llama al modelo para guardar los cambios
        $resultado = DoctoresM::GuardarTratamientosAsignadosM($id_doctor, $ids_tratamientos);
        
        echo json_encode(['success' => $resultado]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no reconocida.']);
        break;
}