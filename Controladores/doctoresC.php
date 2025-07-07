<?php
require_once __DIR__ . '/../Modelos/DoctoresM.php';

class DoctoresC {

    public static function VerDoctoresC() {
        try {
            return DoctoresM::VerDoctoresBasicosM();
        } catch (Exception $e) {
            error_log("Error en VerDoctoresC: " . $e->getMessage());
            return [];
        }
    }

    public static function DoctorC($columna, $valor) {
        try {
            return DoctoresM::DoctorBasicoM($columna, $valor);
        } catch (Exception $e) {
            error_log("Error en DoctorC: " . $e->getMessage());
            return false;
        }
    }

    public function CrearDoctorC() {
        if (isset($_POST["apellidoC"]) && isset($_POST["nombreC"]) && isset($_POST["usuarioC"]) && isset($_POST["claveC"]) && isset($_POST["sexoC"])) {
            $tablaBD = "doctores";
            $datosC = array(
                "apellido" => $_POST["apellidoC"],
                "nombre" => $_POST["nombreC"],
                "usuario" => $_POST["usuarioC"],
                "clave" => $_POST["claveC"],
                "sexo" => $_POST["sexoC"],
                "rol" => "Doctor",
                "foto" => "Vistas/img/defecto.png"
            );

            if ($this->validarDatos($datosC)) {
                $idDoctor = DoctoresM::CrearDoctorM($tablaBD, $datosC);

                if ($idDoctor) {
                    if (isset($_POST["horarios"])) {
                        $horarios = json_decode($_POST["horarios"], true);
                        DoctoresM::AsignarHorariosDoctorM($idDoctor, $horarios);
                    }

                    // ✅ Asignar tratamientos
                    if (isset($_POST["tratamientos"])) {
                        $tratamientos = json_decode($_POST["tratamientos"], true);
                        DoctoresM::AsignarTratamientosDoctorM($idDoctor, $tratamientos);
                    }

                    echo json_encode([
                        "success" => true,
                        "message" => "Doctor creado exitosamente",
                        "id_doctor" => $idDoctor
                    ]);
                } else {
                    echo json_encode(["error" => "Error al crear el doctor"]);
                }
            } else {
                echo json_encode(["error" => "Datos inválidos"]);
            }
        } else {
            echo json_encode(["error" => "Faltan datos requeridos"]);
        }
    }

    public function ActualizarDoctorC() {
        if (isset($_POST["Did"])) {
            $tablaBD = "doctores";
            $datosC = array(
                "id" => $_POST["Did"],
                "apellido" => $_POST["apellidoE"],
                "nombre" => $_POST["nombreE"],
                "usuario" => $_POST["usuarioE"],
                "clave" => $_POST["claveE"],
                "sexo" => $_POST["sexoE"]
            );

            if ($this->validarDatos($datosC)) {
                $resultado = DoctoresM::ActualizarDoctorM($tablaBD, $datosC);

                if (isset($_POST["horarios"])) {
                    $horarios = json_decode($_POST["horarios"], true);
                    DoctoresM::AsignarHorariosDoctorM($_POST["Did"], $horarios);
                }

                // ✅ Actualizar tratamientos
                if (isset($_POST["tratamientos"])) {
                    $tratamientos = json_decode($_POST["tratamientos"], true);
                    DoctoresM::AsignarTratamientosDoctorM($_POST["Did"], $tratamientos);
                }

                echo json_encode([
                    "success" => $resultado,
                    "message" => $resultado ? "Doctor actualizado" : "Error al actualizar"
                ]);
            } else {
                echo json_encode(["error" => "Datos inválidos"]);
            }
        } else {
            echo json_encode(["error" => "ID no proporcionado"]);
        }
    }

    public function BorrarDoctorC() {
        if (isset($_POST["Did"])) {
            $idDoctor = $_POST["Did"];

            DoctoresM::EliminarHorariosDoctorM($idDoctor);
            DoctoresM::EliminarTratamientosDoctorM($idDoctor); // ✅ eliminar relaciones de tratamientos
            $resultado = DoctoresM::BorrarDoctorM("doctores", $idDoctor);

            if (isset($_POST["imgD"]) && file_exists($_POST["imgD"])) {
                unlink($_POST["imgD"]);
            }

            echo json_encode([
                "success" => $resultado,
                "message" => $resultado ? "Doctor eliminado" : "Error al eliminar"
            ]);
        } else {
            echo json_encode(["error" => "ID no proporcionado"]);
        }
    }

    public function GestionarHorariosC() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            $idDoctor = $_POST['id_doctor'] ?? 0;

            switch ($accion) {
                case 'obtener':
                    $horarios = DoctoresM::ObtenerHorariosDoctorM($idDoctor);
                    echo json_encode($horarios);
                    break;

                case 'guardar':
                    if (isset($_POST['horarios'])) {
                        $horarios = json_decode($_POST['horarios'], true);
                        $resultado = DoctoresM::AsignarHorariosDoctorM($idDoctor, $horarios);
                        echo json_encode(["success" => $resultado]);
                    }
                    break;

                default:
                    echo json_encode(["error" => "Acción no válida"]);
            }
        }
    }

    private function validarDatos($datos) {
        $requeridos = ["apellido", "nombre", "usuario", "clave", "sexo"];
        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                return false;
            }
        }
        return true;
    }
}
