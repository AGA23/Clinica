<?php
// Habilitar la visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../Controladores/doctoresC.php";
require_once "../Modelos/doctoresM.php";

class DoctoresA {
    public $Did;

    public function EDoctorA() {
        try {
            $columna = "id";
            $valor = $this->Did;

            // Log para verificar si el valor de Did es correcto
            error_log("Recibiendo Did: " . $valor);

            $resultado = DoctoresC::DoctorC($columna, $valor);

            // Verificar lo que retorna DoctoresC::DoctorC
            error_log("Resultado de DoctoresC::DoctorC: " . print_r($resultado, true));

            if ($resultado) {
                // Respuesta JSON exitosa
                echo json_encode([
                    'success' => true,
                    'data' => $resultado
                ]);
            } else {
                // Respuesta JSON de error
                echo json_encode([
                    'success' => false,
                    'error' => 'No se encontró el doctor con el ID: ' . $this->Did
                ]);
            }
        } catch (Exception $e) {
            // Respuesta JSON de error en caso de excepción
            error_log("Error en DoctoresA: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Ocurrió un error: ' . $e->getMessage()
            ]);
        }
    }
}

if (isset($_POST["Did"])) {
    $Did = filter_var($_POST["Did"], FILTER_VALIDATE_INT);  // Validar que Did sea un número entero

    if ($Did) {
        $eD = new DoctoresA();
        $eD->Did = $Did;  // Asignar el valor validado
        $eD->EDoctorA();
    } else {
        // Respuesta JSON de error si el ID no es válido
        echo json_encode([
            'success' => false,
            'error' => 'ID inválido o no es un número entero'
        ]);
    }
} else {
    // Respuesta JSON de error si no se proporcionó un ID
    echo json_encode([
        'success' => false,
        'error' => 'No se ha proporcionado un ID'
    ]);
}
?>