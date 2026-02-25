<?php
// En /Ajax/pacientesA.php

// 1. Cargar el entorno (solo si no está cargado)
if(!defined('BASE_URL')) {
    require_once __DIR__ . '/../loader.php';
}

// 2. Cabecera JSON
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

class AjaxPacientes {

    // ... (Tus métodos obtenerPaciente, eliminarPaciente, etc. MANTENLOS IGUAL) ...
    public function obtenerPaciente() {
        if (!isset($_SESSION['rol'])) { echo json_encode(['success'=>false]); return; }
        $id = $_POST["id_paciente"] ?? 0;
        $res = PacientesM::ObtenerPacienteM($id);
        echo json_encode(['success' => (bool)$res, 'datos' => $res]);
    }
    
    public function eliminarPaciente() {
        $id = $_POST["id_paciente"] ?? 0;
        $res = PacientesM::BorrarPacienteM($id);
        echo json_encode(['success' => $res]);
    }

    public function verificarUsuario() {
        $u = $_POST["usuario"] ?? '';
        $res = PacientesM::VerificarUsuarioM($u);
        echo json_encode(['existe' => (bool)$res]);
    }
    
    public function sugerirCambio() { echo json_encode(['success'=>false]); }
    public function validarCondicionDoctor() { echo json_encode(['success'=>false]); }

    /**
     * 🟢 SOLUCIÓN DEFINITIVA: OBTENER PLANES
     */
    public function obtenerPlanes() {
        ob_clean(); // Limpiar basura

        $id_obra_social = $_POST["id_obra_social"] ?? 0;

        if ($id_obra_social > 0) {
            
            // 1. Intentar usar la clase si ya está cargada por el loader
            if (class_exists('ObrasSocialesM')) {
                $planes = ObrasSocialesM::VerPlanesM($id_obra_social);
                echo json_encode(['success' => true, 'planes' => $planes]);
                return;
            }
            
            // 2. Si no, cargar manualmente (Ruta exacta)
            // Prueba con mayúscula primero (Estándar PSR)
            $rutaModelo = __DIR__ . '/../Modelos/ObrasSocialesM.php';
            if (!file_exists($rutaModelo)) {
                // Si falla, prueba con minúscula (Tu caso posible)
                $rutaModelo = __DIR__ . '/../Modelos/obrasocialesM.php';
            }

            if (file_exists($rutaModelo)) {
                require_once $rutaModelo;
                
                // Verificar nombre de la clase (puede ser ObrasSocialesM o obrasocialesM)
                if (class_exists('ObrasSocialesM')) {
                    $planes = ObrasSocialesM::VerPlanesM($id_obra_social);
                } else {
                    // Fallback por si la clase se llama distinto
                    $planes = []; 
                }
                
                echo json_encode(['success' => true, 'planes' => $planes]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Archivo del modelo no encontrado.']);
            }

        } else {
            echo json_encode(['success' => false, 'planes' => []]);
        }
        exit();
    }
}

// --- ENRUTADOR ---
$accion = $_REQUEST['action'] ?? '';
$ajax = new AjaxPacientes();

ob_start(); // Buffer para atrapar errores

try {
    switch ($accion) {
        case 'obtener': $ajax->obtenerPaciente(); break;
        case 'eliminar': $ajax->eliminarPaciente(); break;
        case 'verificarUsuario': $ajax->verificarUsuario(); break;
        case 'sugerirCambio': $ajax->sugerirCambio(); break;
        case 'validarCondicionDoctor': $ajax->validarCondicionDoctor(); break;
        
        // CASO CLAVE
        case 'obtenerPlanes': $ajax->obtenerPlanes(); break;
            
        default: 
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Acción desconocida']); 
            break;
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>