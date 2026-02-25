<?php
require_once __DIR__ . '/../loader.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

$accion = $_POST['accion'] ?? '';

if ($accion === 'verSecretario') {
    $id = $_POST['id'] ?? 0;
    if ($id > 0) {
        $secretario = SecretariosM::VerUnSecretarioM($id); // Llama al nuevo método del modelo
        if ($secretario) {
            echo json_encode(['success' => true, 'datos' => $secretario]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Secretario no encontrado.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID no válido.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Acción no reconocida.']);
}