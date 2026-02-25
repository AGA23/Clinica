<?php
// En Ajax/medicamentosA.php (VERSIÓN FINAL, COMPLETA Y UNIFICADA)

// 1. Cargar el entorno global (sesión, autoloader, config).
// Si no usas un loader, asegúrate de que todos los require_once necesarios estén aquí.
require_once "../Modelos/MedicamentosM.php";
require_once "../Controladores/MedicamentosC.php";
require_once "../Modelos/ConexionBD.php"; 
session_start();

// 2. Establecer la cabecera para la respuesta JSON.
// Esto asegura que el navegador siempre interprete la respuesta correctamente.
header('Content-Type: application/json; charset=utf-8');

// 3. Seguridad de roles: Solo Administradores pueden acceder a este endpoint.
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    http_response_code(403); // Código de estado HTTP "Forbidden"
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

// 4. Determinar la acción solicitada y crear instancia del controlador.
$accion = $_POST['action'] ?? '';
$medicamentosC = new MedicamentosC();

// 5. Enrutador de acciones (switch).
switch ($accion) {
    
    // --- ACCIONES PARA PRESENTACIONES (DENTRO DEL MODAL) ---

    case 'obtener_presentaciones':
        $id_farmaco = filter_input(INPUT_POST, 'id_farmaco', FILTER_VALIDATE_INT);
        if ($id_farmaco) {
            $presentaciones = MedicamentosM::ObtenerPresentacionesPorFarmacoM($id_farmaco);
            $respuesta = ['success' => true, 'presentaciones' => $presentaciones];
        } else {
            $respuesta = ['success' => false, 'error' => 'ID de fármaco no válido.'];
        }
        break;

    case 'crear_presentacion':
        $datos = [
            'id_farmaco'    => filter_input(INPUT_POST, 'id_farmaco', FILTER_VALIDATE_INT),
            'presentacion'  => trim($_POST['presentacion'] ?? ''),
            'observaciones' => trim($_POST['observaciones'] ?? ''),
            'es_cronico'    => filter_input(INPUT_POST, 'es_cronico', FILTER_VALIDATE_INT) ? 1 : 0
        ];
        if ($datos['id_farmaco'] && !empty($datos['presentacion'])) {
            $resultado = MedicamentosM::CrearPresentacionM($datos);
            $respuesta = ['success' => $resultado];
        } else {
            $respuesta = ['success' => false, 'error' => 'Faltan datos para crear la presentación.'];
        }
        break;

    case 'aprobar_presentacion':
        $id_presentacion = filter_input(INPUT_POST, 'id_presentacion', FILTER_VALIDATE_INT);
        if ($id_presentacion) {
            $resultado = MedicamentosM::AprobarPresentacionM($id_presentacion);
            $respuesta = ['success' => $resultado];
        } else {
            $respuesta = ['success' => false, 'error' => 'ID de presentación no válido.'];
        }
        break;

    case 'eliminar_presentacion':
        $id_presentacion = filter_input(INPUT_POST, 'id_presentacion', FILTER_VALIDATE_INT);
        if ($id_presentacion) {
            $resultado = MedicamentosM::EliminarPresentacionM($id_presentacion);
            $respuesta = ['success' => $resultado, 'error' => $resultado ? '' : 'No se pudo eliminar.'];
        } else {
            $respuesta = ['success' => false, 'error' => 'ID de presentación no válido.'];
        }
        break;

    // --- ¡NUEVAS ACCIONES PARA FÁRMACOS GENÉRICOS! ---

    case 'aprobar_farmaco':
        $id_farmaco = filter_input(INPUT_POST, 'id_farmaco', FILTER_VALIDATE_INT);
        // Llama al método del controlador que se encarga de la lógica.
        $respuesta = $medicamentosC->AprobarFarmacoC($id_farmaco);
        break;

    case 'rechazar_farmaco':
        $id_farmaco = filter_input(INPUT_POST, 'id_farmaco', FILTER_VALIDATE_INT);
        // Llama al método del controlador que se encarga de la lógica.
        $respuesta = $medicamentosC->RechazarFarmacoC($id_farmaco);
        break;

    // --- CASO POR DEFECTO ---

    default:
        http_response_code(400); // Código de estado HTTP "Bad Request"
        $respuesta = ['success' => false, 'error' => 'Acción no reconocida: ' . htmlspecialchars($accion)];
        break;
}

// 6. Imprimir la respuesta final en formato JSON.
echo json_encode($respuesta);

?>