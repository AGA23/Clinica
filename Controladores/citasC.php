<?php
// Esto debe ir en la parte superior del archivo, antes de cualquier salida
header('Content-Type: text/html; charset=UTF-8'); // Esto asegura que el contenido sea en UTF-8, por ejemplo.

class CitasC {

    // Instancia de CitasM para acceder a los métodos no estáticos
    private $citasM;

    // Constructor para inicializar CitasM
    public function __construct() {
        $this->citasM = new CitasM(); // Crear instancia de CitasM
    }

    // Pedir Cita Paciente
    public function EnviarCitaC() {
        // Verificar si las variables necesarias están definidas y sanitizar los datos
        if (isset($_POST["Did"], $_POST["Pid"], $_POST["nyaC"], $_POST["Cid"], $_POST["documentoC"], $_POST["fyhIC"], $_POST["fyhFC"])) {
            $tablaBD = "citas";
            $Did = filter_var($_POST["Did"], FILTER_SANITIZE_NUMBER_INT); // Sanitizar datos
            $Pid = filter_var($_POST["Pid"], FILTER_SANITIZE_NUMBER_INT);
            $nyaC = htmlspecialchars($_POST["nyaC"], ENT_QUOTES, 'UTF-8');
            $Cid = filter_var($_POST["Cid"], FILTER_SANITIZE_NUMBER_INT);
            $documentoC = filter_var($_POST["documentoC"], FILTER_SANITIZE_STRING);
            $fyhIC = filter_var($_POST["fyhIC"], FILTER_SANITIZE_STRING); // Verificar formato si es necesario
            $fyhFC = filter_var($_POST["fyhFC"], FILTER_SANITIZE_STRING); // Verificar formato si es necesario

            // Preparar datos para enviar a CitasM
            $datosC = array(
                "Did" => $Did,
                "Pid" => $Pid,
                "nyaC" => $nyaC,
                "Cid" => $Cid,
                "documentoC" => $documentoC,
                "fyhIC" => $fyhIC,
                "fyhFC" => $fyhFC
            );

            // Usar el método no estático de CitasM
            $resultado = $this->citasM->EnviarCitaM($tablaBD, $datosC);

            // Responder según el resultado de la operación
            if ($resultado) {
                echo 'Cita registrada correctamente.';
            } else {
                echo 'Error al registrar la cita.';
            }
        } else {
            echo 'Faltan datos requeridos.';
        }
    }

    // Mostrar Citas
    public function VerCitasC() {
        $tablaBD = "citas";
        $resultado = $this->citasM->VerCitasM($tablaBD);
        return $resultado;
    }

    // Pedir cita como doctor
    public function PedirCitaDoctorC() {
        // Verificar si las variables necesarias están definidas y sanitizar los datos
        if (isset($_POST["Did"], $_POST["Cid"], $_POST["nombreP"], $_POST["documentoP"], $_POST["fyhIC"], $_POST["fyhFC"])) {
            $tablaBD = "citas";
            $Did = filter_var($_POST["Did"], FILTER_SANITIZE_NUMBER_INT);
            $Cid = filter_var($_POST["Cid"], FILTER_SANITIZE_NUMBER_INT);
            $nombreP = htmlspecialchars($_POST["nombreP"], ENT_QUOTES, 'UTF-8');
            $documentoP = filter_var($_POST["documentoP"], FILTER_SANITIZE_STRING);
            $fyhIC = filter_var($_POST["fyhIC"], FILTER_SANITIZE_STRING);
            $fyhFC = filter_var($_POST["fyhFC"], FILTER_SANITIZE_STRING);

            // Preparar datos para enviar a CitasM
            $datosC = array(
                "Did" => $Did,
                "Cid" => $Cid,
                "nombreP" => $nombreP,
                "documentoP" => $documentoP,
                "fyhIC" => $fyhIC,
                "fyhFC" => $fyhFC
            );

            // Usar el método no estático de CitasM
            $resultado = $this->citasM->PedirCitaDoctorM($tablaBD, $datosC);

            // Responder según el resultado de la operación
            if ($resultado) {
                echo 'Cita pedida correctamente.';
            } else {
                echo 'Error al pedir la cita.';
            }
        } else {
            echo 'Faltan datos requeridos.';
        }
    }

    // Cancelar Cita
    public function CancelarCitaC() {
        // Verificar si el id de la cita está definido y sanitizar
        if (isset($_POST["id_Cita"])) {
            $tablaBD = "citas";
            $id = filter_var($_POST["id_Cita"], FILTER_SANITIZE_NUMBER_INT);

            // Usar el método no estático de CitasM
            $resultado = $this->citasM->CancelarCitaM($tablaBD, $id);

            // Responder según el resultado de la operación
            if ($resultado) {
                echo 'Cita cancelada correctamente.';
            } else {
                echo 'Error al cancelar la cita.';
            }
        } else {
            echo 'ID de cita no proporcionado.';
        }
    }
}
?>
