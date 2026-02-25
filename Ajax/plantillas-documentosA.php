<?php
// Ajax/plantillas-documentosA.php

require_once __DIR__ . '/../loader.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    http_response_code(403);
    echo json_encode(['data'=>[],'error'=>'Acceso no autorizado']);
    exit();
}

$accion = $_REQUEST['action'] ?? '';

switch($accion){

    case 'listar':
        $plantillas = (new PlantillasDocumentosC())->ListarPlantillasC();
        echo json_encode(["data"=>$plantillas]);
        break;

    case 'obtener':
        $id = $_POST['id_plantilla'] ?? 0;
        $plantilla = PlantillasDocumentosM::ObtenerPlantillaPorIdM($id);
        echo json_encode($plantilla);
        break;

    case 'eliminar':
        $id = $_POST['id_plantilla'] ?? 0;
        $respuesta = (new PlantillasDocumentosC())->BorrarPlantillaC($id);
        echo json_encode($respuesta);
        break;

    case 'obtenerEjemplo':
        $tipo = $_POST['tipo'] ?? '';
        $ejemplo = PlantillasDocumentosC::ObtenerEjemploPlantilla($tipo);
        $placeholders = PlantillasDocumentosC::ObtenerPlaceholders($tipo);
        echo json_encode([
            'ejemplo' => $ejemplo,
            'placeholders' => $placeholders
        ]);
        break;

    default:
        echo json_encode(['data'=>[],'error'=>'Acción no válida']);
        break;
}
