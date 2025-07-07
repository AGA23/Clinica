<?php
require_once __DIR__ . "/../Modelos/consultoriosM.php";

class ConsultoriosC {
    // Crear Consultorio
    public function CrearConsultorioC() {
        if (isset($_POST["consultorioN"])) {
            $datosC = array("nombre" => $_POST["consultorioN"]);
            $tablaBD = "consultorios";
            
            $respuesta = ConsultoriosM::CrearConsultorioM($tablaBD, $datosC);
            
            if ($respuesta) {
                echo '<script>
                    window.location = "consultorios";
                </script>';
            }
        }
    }

    // Ver Consultorios
    static public function VerConsultoriosC($columna, $valor) {
        $tablaBD = "consultorios";
        return ConsultoriosM::VerConsultoriosM($tablaBD, $columna, $valor);
    }

    // Ver Consultorios Completos
    static public function VerConsultoriosCompletosC() {
        return ConsultoriosM::VerConsultoriosCompletosM();
    }

    // Borrar Consultorio
    public function BorrarConsultorioC() {
        if (isset($_GET["url"]) && $_SESSION["rol"] == "Administrador") {
            $url = explode('/', $_GET["url"]);
            if (end($url) == "consultorios" && isset($url[count($url)-2]) && is_numeric($url[count($url)-2])) {
                $id = $url[count($url)-2];
                $tablaBD = "consultorios";
                
                $respuesta = ConsultoriosM::BorrarConsultorioM($tablaBD, $id);
                
                if ($respuesta) {
                    echo '<script>
                        window.location = "consultorios";
                    </script>';
                }
            }
        }
    }
    

    public function ObtenerHorariosDoctorC() {
        if(isset($_POST['id_doctor'])) {
            $horarios = ConsultoriosM::ObtenerHorariosDoctor($_POST['id_doctor']);
            echo json_encode($horarios);
        }
    }


    // Cambiar Estado del Consultorio
    public static function CambiarEstadoConsultorio() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_consultorio'])) {
            session_start();
            
            if (!isset($_SESSION['id'])) {
                echo json_encode(['success' => false, 'error' => 'Sesión no iniciada']);
                exit;
            }
    
            $id_consultorio = $_POST['id_consultorio'];
            $fecha = $_POST['fecha'];
            $estado = $_POST['estado'];
            $motivo = $_POST['motivo'] ?? '';
            $id_usuario = $_SESSION['id'];
    
            $resultado = ConsultoriosM::CambiarEstadoManualM(
                $id_consultorio, 
                $fecha, 
                $estado, 
                $motivo, 
                $id_usuario
            );
    
            echo json_encode(['success' => $resultado]);
            exit;
        }
    }

    static public function CambiarEstadoManualC($id_consultorio, $fecha, $estado, $motivo, $id_usuario) {
        return ConsultoriosM::CambiarEstadoManualM($id_consultorio, $fecha, $estado, $motivo, $id_usuario);
    }


}




?>