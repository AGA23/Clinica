<?php


require_once __DIR__ . '/../loader.php';
require_once __DIR__ . '/../Modelos/PlantillasM.php';

header('Content-Type: application/json; charset=utf-8');

// Seguridad: Solo los doctores pueden cargar plantillas en este contexto.
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Doctor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

$accion = $_POST['action'] ?? '';
$id_plantilla = $_POST['id_plantilla'] ?? 0;

// Tu JS antiguo usa 'idPlantilla', el nuevo 'id_plantilla'.
// Para ser compatible con ambos, hacemos esto:
if (isset($_POST['idPlantilla'])) {
    $id_plantilla = $_POST['idPlantilla'];
}

if ($accion === 'obtenerContenido' && $id_plantilla > 0) {
    $plantilla = PlantillasM::ObtenerContenidoPlantillaM($id_plantilla);
    if ($plantilla) {
        echo json_encode(['success' => true, 'contenido' => $plantilla['contenido']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Contenido de plantilla no encontrado.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Acción o ID no válidos.']);
}