<?php
// En /Ajax/adminA.php

// 1. Cargar el entorno global de la aplicación.
require_once __DIR__ . '/../loader.php';

// 2. Establecer la cabecera para la respuesta JSON.
header('Content-Type: application/json; charset=utf-8');

// 3. Seguridad de Roles: Solo un Administrador puede realizar estas acciones.
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

// 4. Determinar la acción solicitada.
// Usamos $_REQUEST para aceptar tanto GET (?action=...) como POST.
$accion = $_REQUEST['action'] ?? '';

// 5. Enrutador de acciones.
switch ($accion) {
    
    /**
     * Acción para obtener los datos de un administrador específico.
     * Útil para rellenar formularios de edición sin recargar la página.
     */
    case 'obtener':
        $id_admin = $_POST['id_admin'] ?? 0;
        if ($id_admin > 0) {
            // Se asume que el modelo se llama AdminM
            $admin = AdminM::ObtenerPerfilAdminM($id_admin); // Reutilizamos el método del perfil
            if ($admin) {
                // Por seguridad, nunca devolvemos la contraseña, ni siquiera hasheada.
                unset($admin['clave']);
                echo json_encode(['success' => true, 'datos' => $admin]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Administrador no encontrado.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'ID de administrador inválido.']);
        }
        break;

    /**
     * Acción para eliminar un administrador.
     */
    case 'eliminar':
        $id_admin = $_POST['id_admin'] ?? 0;
        
        // Medida de seguridad: Un administrador no puede eliminarse a sí mismo.
        if ($id_admin == $_SESSION['id']) {
            echo json_encode(['success' => false, 'error' => 'No puede eliminar su propia cuenta.']);
            exit();
        }

        if ($id_admin > 0) {
            // Se asume que el modelo se llama AdminM y que el controlador es AdminC
            $adminController = new AdminC(); 
            // Podríamos crear un método en AdminC para manejar la eliminación.
            // Por simplicidad, llamamos al modelo directamente.
            $resultado = AdminM::BorrarAdminM($id_admin); // Necesitaremos este método en el modelo.
            
            if ($resultado) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No se pudo eliminar al administrador.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'ID de administrador inválido.']);
        }
        break;

    /**
     * Acción para verificar si un nombre de usuario ya está en uso.
     * Útil para validación en tiempo real en el formulario de creación.
     */
    case 'verificarUsuario':
        $usuario = trim($_POST['usuario'] ?? '');
        if (!empty($usuario)) {
            // Reutilizamos el método de login del modelo, ya que busca por usuario.
            $existe = AdminM::IngresarAdminM("administradores", ["usuario" => $usuario]);
            echo json_encode(['existe' => (bool)$existe]);
        } else {
            echo json_encode(['existe' => false]);
        }
        break;

    // --- Acción por defecto si no se reconoce el 'action' ---
    default:
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'Acción no reconocida: ' . htmlspecialchars($accion)]);
        break;
}