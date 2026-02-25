<?php
// En ajax/reportes.php (VERSIÓN FINAL Y ROBUSTA)

// 1. CARGAR EL ENTORNO
// Usamos __DIR__ para asegurar que las rutas sean correctas desde la carpeta 'ajax'.
// Si tienes un archivo 'loader.php' en la raíz, es la mejor opción.
// Si no, cargamos los archivos necesarios manualmente.
require_once __DIR__ . '/../Modelos/ConexionBD.php';
require_once __DIR__ . '/../Modelos/ReportesM.php';
session_start();

// 2. SEGURIDAD DE ROL
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Secretario'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no permitido.']);
    exit();
}

// 3. ESTABLECER CABECERA JSON
header('Content-Type: application/json');

// 4. LEER LA ACCIÓN Y ENRUTAR
$accion = $_POST['action'] ?? '';

switch ($accion) {
    case 'obtener_perfil_paciente':
        // Recoger y validar los datos de entrada
        $id_paciente = filter_input(INPUT_POST, 'id_paciente', FILTER_VALIDATE_INT);
        $desde = $_POST['desde'] ?? date('Y-m-01');
        $hasta = $_POST['hasta'] ?? date('Y-m-t');

        if ($id_paciente) {
            // Llamar al método del modelo
            $perfil = ReportesM::ObtenerPerfilAnaliticoPaciente($id_paciente, $desde, $hasta);
            
            if ($perfil) {
                // Si todo fue bien, enviar los datos
                echo json_encode(['success' => true, 'datos' => $perfil]);
            } else {
                // Si el modelo devolvió 'false' (ej. paciente no encontrado)
                echo json_encode(['success' => false, 'error' => 'No se encontraron datos analíticos para este paciente en el período seleccionado.']);
            }
        } else {
            // Si el ID del paciente no es válido
            echo json_encode(['success' => false, 'error' => 'ID de paciente no válido.']);
        }
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no reconocida en este endpoint.']);
        break;
}
?>