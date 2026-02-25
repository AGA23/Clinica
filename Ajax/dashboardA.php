<?php
// En Ajax/consultoriosA.php
require_once __DIR__ . '/../loader.php';
header('Content-Type: application/json');

$rolesPermitidos = ['Administrador']; // Solo Admins
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $rolesPermitidos)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

$accion = $_GET['action'] ?? '';
switch ($accion) {
    // ... (tus casos existentes como 'obtenerParaEditar', 'eliminar', 'cambiarEstado') ...

    case 'obtenerTratamientosParaAsignar':
        $id_consultorio = $_POST['id_consultorio'] ?? 0;
        if ($id_consultorio > 0) {
            $todos = TratamientosM::ListarTratamientosM();
            $asignados = TratamientosM::ObtenerTratamientosAsignadosAConsultorioM($id_consultorio);
            echo json_encode(['success' => true, 'todos' => $todos, 'asignados' => $asignados]);
        } else {
            echo json_encode(['success' => false, 'error' => 'ID de consultorio inválido.']);
        }
        break;

    case 'guardarTratamientosAsignados':
        $id_consultorio = $_POST['id_consultorio'] ?? 0;
        $ids_tratamientos = json_decode($_POST['ids_tratamientos'] ?? '[]', true);
        if ($id_consultorio > 0) {
            $resultado = TratamientosM::GuardarAsignacionConsultorioM($id_consultorio, $ids_tratamientos);
            echo json_encode(['success' => $resultado]);
        } else {
            echo json_encode(['success' => false, 'error' => 'ID de consultorio inválido.']);
        }
        break;
}