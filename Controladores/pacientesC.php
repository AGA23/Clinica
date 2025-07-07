<?php
require_once __DIR__ . '/../Modelos/pacientesM.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/clinica/config.php';
require_once ROOT_PATH . '/Modelos/ConexionBD.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class pacientesC {
    
    // Método para actualizar perfil del paciente
    public function ActualizarPerfilPacienteC() {
        if (isset($_POST["actualizarPerfilPaciente"])) {
            $tablaBD = "pacientes";
        
            if (isset($_POST["idPaciente"], $_POST["nombreE"], $_POST["apellidoE"], $_POST["usuarioE"])) {
        
                // Ruta base para imágenes
                $rutaImagenes = ROOT_PATH . 'Vistas/img/pacientes/';
                $urlImagenes = BASE_URL . 'Vistas/img/pacientes/';
        
                // Procesar foto
                $foto = $_POST["fotoActual"];
                if (!empty($_FILES["fotoE"]["tmp_name"])) {
                    if ($_FILES["fotoE"]["error"] != UPLOAD_ERR_OK) {
                        echo '<script>
                                alert("Error al cargar la foto.");
                                window.location = "' . BASE_URL . 'perfil-Paciente";
                              </script>';
                        return;
                    }
    
                    // Si ya hay una foto y se va a cambiar, eliminar la anterior
                    if ($foto != "" && file_exists(ROOT_PATH . $foto)) {
                        unlink(ROOT_PATH . $foto);
                    }
    
                    $archivo = $_FILES["fotoE"]["tmp_name"];
                    $nombreArchivo = $_FILES["fotoE"]["name"];
                    $extension = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
                    $nuevoNombre = "paciente_" . $_POST["idPaciente"] . "_" . time() . "." . $extension;
                    $destinoAbsoluto = $rutaImagenes . $nuevoNombre;
                    $destinoRelativo = 'Vistas/img/pacientes/' . $nuevoNombre;
    
                    // Validar extensiones permitidas
                    $extensionesPermitidas = array("jpg", "jpeg", "png");
                    if (!in_array(strtolower($extension), $extensionesPermitidas)) {
                        echo '<script>
                                alert("Error: Solo se permiten archivos JPG, JPEG o PNG");
                                window.location = "' . BASE_URL . 'perfil-Paciente";
                              </script>';
                        return;
                    }
    
                    // Validar el tamaño máximo del archivo
                    if ($_FILES["fotoE"]["size"] > 2097152) { // 2MB
                        echo '<script>
                                alert("Error: El archivo no debe exceder los 2MB");
                                window.location = "' . BASE_URL . 'perfil-Paciente";
                              </script>';
                        return;
                    }
    
                    // Mover el archivo a la carpeta destino
                    move_uploaded_file($archivo, $destinoAbsoluto);
                    $foto = $destinoRelativo;
                }
    
                // Manejo de la contraseña
                $clave = $_POST["claveE"];
                $claveEncriptada = !empty($clave) ? password_hash($clave, PASSWORD_DEFAULT) : $_POST["claveActual"];
        
                // Preparar los datos a actualizar
                $datosC = array(
                    "id" => $_POST["idPaciente"],
                    "nombre" => $_POST["nombreE"],
                    "apellido" => $_POST["apellidoE"],
                    "usuario" => $_POST["usuarioE"],
                    "clave" => $claveEncriptada,
                    "foto" => $foto,
                    "correo" => $_POST["correoE"] ?? null,
                    "telefono" => $_POST["telefonoE"] ?? null,
                    "direccion" => $_POST["direccionE"] ?? null
                );
    
                // Llamar al modelo para actualizar el paciente
                $resultado = PacientesM::ActualizarPacienteM($tablaBD, $datosC);
        
                if ($resultado == "ok") {
                    // Actualizar los datos en la sesión
                    $_SESSION["nombre"] = $datosC["nombre"];
                    $_SESSION["apellido"] = $datosC["apellido"];
                    $_SESSION["usuario"] = $datosC["usuario"];
                    if ($foto != "") {
                        $_SESSION["foto"] = $foto;
                    }
        
                    // Redirigir a la página de perfil
                    header("Location: " . BASE_URL . "perfil-Paciente");
                    exit();
                } else {
                    echo '<script>
                            alert("Error al actualizar el perfil");
                            window.location = "' . BASE_URL . 'perfil-Paciente";
                          </script>';
                }
            } else {
                echo '<script>
                        alert("Faltan datos en el formulario.");
                        window.location = "' . BASE_URL . 'perfil-Paciente";
                      </script>';
            }
        }
    }
    
    
    public static function ListarPacientesC() {
    try {
        $pdo = ConexionBD::getInstancia();
        $query = "SELECT id, nombre, apellido FROM pacientes ORDER BY nombre ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en ListarPacientesC: " . $e->getMessage());
        return [];
    }
}

    // Registro de nuevo paciente
    public function CrearPacienteC() {
        if (isset($_POST["usuario"])) {
            $tablaBD = "pacientes";

            $clave = $_POST["clave"];
            $claveEncriptada = password_hash($clave, PASSWORD_DEFAULT); // Uso de password_hash

            $datosC = array(
                "usuario" => $_POST["usuario"],
                "clave" => $claveEncriptada,
                "nombre" => $_POST["nombre"],
                "apellido" => $_POST["apellido"],
                "foto" => "", // Foto vacía inicialmente
                "rol" => "Paciente" // Asignación por defecto de rol
            );

            $resultado = PacientesM::CrearPacienteM($tablaBD, $datosC);

            if ($resultado == "ok") {
                echo '<script>
                        window.location = "pacientes";
                      </script>';
            }
        }
    }

    // Ver todos los pacientes
    public static function VerPacientesC($columna, $valor) {
        $tablaBD = "pacientes";
        $resultado = PacientesM::VerPacientesM($tablaBD, $columna, $valor);
        return $resultado;
    }

    // Borrar paciente
    public function BorrarPacienteC() {
        if (isset($_GET["Pid"])) {
            $tablaBD = "pacientes";
            $id = $_GET["Pid"];

            if ($_GET["foto"] != "") {
                unlink($_GET["foto"]); // Eliminar foto si existe
            }

            $resultado = PacientesM::BorrarPacienteM($tablaBD, $id);

            if ($resultado == "ok") {
                echo '<script>
                        window.location = "pacientes";
                      </script>';
            }
        }
    }

    // Actualizar paciente
    public function ActualizarPacienteC() {
        if (isset($_POST["idPaciente"])) {
            $tablaBD = "pacientes";

            $foto = $_POST["fotoActual"];
            // Si hay una nueva foto
            if ($_FILES["fotoE"]["tmp_name"] != "") {
                if ($foto != "") {
                    unlink($foto); // Borrar la foto actual
                }

                $archivo = $_FILES["fotoE"]["tmp_name"];
                $nombreArchivo = basename($_FILES["fotoE"]["name"]);
                $destino = "Vistas/img/pacientes/" . $nombreArchivo;

                move_uploaded_file($archivo, $destino);
                $foto = $destino;
            }

            // Actualización de la clave
            $clave = $_POST["claveE"];
            $claveEncriptada = ($clave != "") ? password_hash($clave, PASSWORD_DEFAULT) : $_POST["claveActual"];

            $datosC = array(
                "id" => $_POST["idPaciente"],
                "usuario" => $_POST["usuarioE"],
                "clave" => $claveEncriptada,
                "nombre" => $_POST["nombreE"],
                "apellido" => $_POST["apellidoE"],
                "foto" => $foto
            );

            $resultado = PacientesM::ActualizarPacienteM($tablaBD, $datosC);

            if ($resultado == "ok") {
                echo '<script>
                        window.location = "perfil-Paciente";
                      </script>';
            }
        }
    }

    // Iniciar sesión del paciente
    public function IngresoPacienteC() {
        if (isset($_POST["usuario-Ing"])) {
            $tablaBD = "pacientes";
            $datosC = array(
                "usuario" => $_POST["usuario-Ing"]
            );

            $resultado = PacientesM::IngresoPacienteM($tablaBD, $datosC);

            if ($resultado && password_verify($_POST["clave-Ing"], $resultado["clave"])) { // Uso de password_verify
                session_start();
                $_SESSION["Ingresar"] = true;
                $_SESSION["id"] = $resultado["id"];
                $_SESSION["usuario"] = $resultado["usuario"];
                $_SESSION["nombre"] = $resultado["nombre"];
                $_SESSION["apellido"] = $resultado["apellido"];
                $_SESSION["foto"] = $resultado["foto"];
                $_SESSION["rol"] = "Paciente";

                echo '<script>
                        window.location = "inicio";
                      </script>';
            } else {
                echo '<br><div class="alert alert-danger">Error al Ingresar</div>';
            }
        }
    }

    // Obtener datos del perfil del paciente actual
    public function VerPerfilPacienteC() {
        if (isset($_SESSION["id"])) {
            $tablaBD = "pacientes";
            $id = $_SESSION["id"];
            $respuesta = PacientesM::VerPerfilPacienteM($tablaBD, $id);
            return $respuesta;
        }

        return null;
    }

    public static function ObtenerNombreCompletoPaciente($id) {
        // Usar getInstancia en lugar de cBD
        $pdo = ConexionBD::getInstancia()->prepare("SELECT nombre FROM pacientes WHERE id = ?");
        $pdo->execute([$id]);
        $paciente = $pdo->fetch();
    
        return $paciente ? $paciente["nombre"] : '';
    }
    // Obtener medicamentos de un paciente (crónicos y no crónicos)
public function ObtenerMedicamentosPacienteC($id_paciente)
{
    return MedicamentosM::ObtenerPorPaciente($id_paciente);
}

// Obtener tratamientos asociados a un doctor
public function ObtenerTratamientosPorDoctorC($id_doctor)
{
    return TratamientosM::ObtenerPorDoctor($id_doctor);
}

// Guardar medicamento para un paciente (nuevo o desde finalización de cita)
public function GuardarMedicamentoPacienteC($id_paciente, $datos)
{
    return MedicamentosM::GuardarParaCita($id_paciente, $datos);
}

    
}
?>
