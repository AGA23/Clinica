<?php 

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

            if ($resultado) {
                echo json_encode($resultado);  // Si se encuentra el doctor, retorna sus datos
            } else {
                echo json_encode(["error" => "No se encontró el doctor con el ID: " . $this->Did]);
            }
        } catch (Exception $e) {
            error_log("Error en DoctoresA: " . $e->getMessage()); // Log para errores
            echo json_encode(["error" => "Ocurrió un error: " . $e->getMessage()]);
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
        echo json_encode(["error" => "ID inválido"]);
    }
} else {
    echo json_encode(["error" => "No se ha proporcionado un ID"]);
}
?>
