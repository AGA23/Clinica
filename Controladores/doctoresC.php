<?php

class DoctoresC {

    // Crear Doctores
    public function CrearDoctorC() {
        if (isset($_POST["apellidoC"]) && isset($_POST["nombreC"]) && isset($_POST["usuarioC"]) && isset($_POST["claveC"]) && isset($_POST["sexoC"])) {
            $tablaBD = "doctores";
            $datosC = array(
                "apellido" => $_POST["apellidoC"],
                "nombre" => $_POST["nombreC"],
                "usuario" => $_POST["usuarioC"],
                "clave" => $_POST["claveC"],
                "sexo" => $_POST["sexoC"],
                "rol" => "Doctor" // Asignar el rol de doctor
            );

            // Validar datos antes de crear el doctor
            if ($this->validarDatos($datosC)) {
                $resultado = DoctoresM::CrearDoctorM($tablaBD, $datosC);

                if ($resultado) {
                    echo json_encode(["success" => true, "message" => "Doctor creado exitosamente."]);
                } else {
                    echo json_encode(["error" => "Error al crear el doctor."]);
                }
            } else {
                echo json_encode(["error" => "Datos inválidos."]);
            }
        } else {
            echo json_encode(["error" => "No se han proporcionado todos los datos necesarios."]);
        }
    }

    // Mostrar Doctores
    static public function VerDoctoresC($columna, $valor) {
        $tablaBD = "doctores";
        $resultado = DoctoresM::VerDoctoresM($tablaBD, $columna, $valor);
        return $resultado;
    }

    // Editar Doctor
    static public function DoctorC($columna, $valor) {
        $tablaBD = "doctores";
        $resultado = DoctoresM::DoctorM($tablaBD, $columna, $valor);
        return $resultado;
    }

    // Actualizar Doctor
    public function ActualizarDoctorC() {
        if (isset($_POST["Did"])) {
            $tablaBD = "doctores";
            $datosC = array(
                "id" => $_POST["Did"],
                "apellido" => $_POST["apellidoE"],
                "nombre" => $_POST["nombreE"],
                "sexo" => $_POST["sexoE"],
                "usuario" => $_POST["usuarioE"],
                "clave" => $_POST["claveE"]
            );

            // Validar datos antes de actualizar el doctor
            if ($this->validarDatos($datosC)) {
                $resultado = DoctoresM::ActualizarDoctorM($tablaBD, $datosC);

                if ($resultado) {
                    echo json_encode(["success" => true, "message" => "Doctor actualizado exitosamente."]);
                } else {
                    echo json_encode(["error" => "Error al actualizar el doctor."]);
                }
            } else {
                echo json_encode(["error" => "Datos inválidos."]);
            }
        } else {
            echo json_encode(["error" => "No se ha proporcionado un ID."]);
        }
    }

    // Borrar Doctor
    public function BorrarDoctorC() {
        if (isset($_POST["Did"])) {
            $tablaBD = "doctores";
            $id = $_POST["Did"];

            // Verificar si se proporciona la ruta de la imagen y eliminarla
            if (isset($_POST["imgD"]) && $_POST["imgD"] != "") {
                $imgPath = $_POST["imgD"];
                if (file_exists($imgPath)) {
                    unlink($imgPath);
                }
            }

            // Llamar al modelo para eliminar el doctor
            $resultado = DoctoresM::BorrarDoctorM($tablaBD, $id);

            // Devolver respuesta en formato JSON
            if ($resultado) {
                echo json_encode(["success" => true, "message" => "Doctor eliminado exitosamente."]);
            } else {
                echo json_encode(["error" => "Error al eliminar el doctor."]);
            }
        } else {
            echo json_encode(["error" => "No se ha proporcionado un ID."]);
        }
    }

    // Iniciar sesión doctor
    public function IngresarDoctorC() {
        if (isset($_POST["usuario-Ing"])) {
            if (preg_match('/^[a-zA-Z0-9]+$/', $_POST["usuario-Ing"]) && preg_match('/^[a-zA-Z0-9]+$/', $_POST["clave-Ing"])) {
                $tablaBD = "doctores";
                $datosC = array(
                    "usuario" => $_POST["usuario-Ing"],
                    "clave" => $_POST["clave-Ing"]
                );

                $resultado = DoctoresM::IngresarDoctorM($tablaBD, $datosC);

                if ($resultado && isset($resultado["usuario"]) && isset($resultado["clave"]) && $resultado["usuario"] == $_POST["usuario-Ing"] && $resultado["clave"] == $_POST["clave-Ing"]) {
                    $_SESSION["Ingresar"] = true;
                    $_SESSION["id"] = $resultado["id"];
                    $_SESSION["usuario"] = $resultado["usuario"];
                    $_SESSION["clave"] = $resultado["clave"];
                    $_SESSION["apellido"] = $resultado["apellido"];
                    $_SESSION["nombre"] = $resultado["nombre"];
                    $_SESSION["sexo"] = $resultado["sexo"];
                    $_SESSION["foto"] = $resultado["foto"];
                    $_SESSION["rol"] = $resultado["rol"];

                    echo json_encode(["success" => true, "message" => "Inicio de sesión exitoso."]);
                } else {
                    echo json_encode(["error" => "Usuario o contraseña incorrectos."]);
                }
            } else {
                echo json_encode(["error" => "Datos de usuario o contraseña inválidos."]);
            }
        } else {
            echo json_encode(["error" => "No se han proporcionado datos de inicio de sesión."]);
        }
    }

    // Ver Perfil Doctor
    public function VerPerfilDoctorC() {
        $tablaBD = "doctores";
        $id = $_SESSION["id"];
        $resultado = DoctoresM::VerPerfilDoctorM($tablaBD, $id);

        if ($resultado && is_array($resultado)) {
            echo '<tr>
                <td>' . htmlspecialchars($resultado["usuario"]) . '</td>
                <td>' . htmlspecialchars($resultado["clave"]) . '</td>
                <td>' . htmlspecialchars($resultado["nombre"]) . '</td>
                <td>' . htmlspecialchars($resultado["apellido"]) . '</td>';

            if ($resultado["foto"] == "") {
                echo '<td><img src="Vistas/img/defecto.png" width="40px"></td>';
            } else {
                echo '<td><img src="' . htmlspecialchars($resultado["foto"]) . '" width="40px"></td>';
            }

            $columna = "id";
            $valor = $resultado["id_consultorio"];
            $consultorio = ConsultoriosC::VerConsultoriosC($columna, $valor);

            if ($consultorio && is_array($consultorio)) {
                echo '<td>' . htmlspecialchars($consultorio["nombre"]) . '</td>';
            } else {
                echo '<td>Error: Consultorio no encontrado.</td>';
            }

            echo '<td>
                    Desde: ' . htmlspecialchars($resultado["horarioE"]) . '
                    <br>
                    Hasta: ' . htmlspecialchars($resultado["horarioS"]) . '
                  </td>
                  <td>
                    <a href="http://localhost/clinica/perfil-D/' . htmlspecialchars($resultado["id"]) . '">
                        <button class="btn btn-success"><i class="fa fa-pencil"></i></button>
                    </a>
                  </td>
                </tr>';
        } else {
            echo json_encode(["error" => "No se encontró el perfil del doctor."]);
        }
    }

    // Editar Perfil Doctor
    public function EditarPerfilDoctorC() {
        $tablaBD = "doctores";
        $id = $_SESSION["id"];
        $resultado = DoctoresM::VerPerfilDoctorM($tablaBD, $id);

        if ($resultado && is_array($resultado)) {
            echo '<form method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 col-xs-12">
                            <h2>Nombre:</h2>
                            <input type="text" class="input-lg" name="nombrePerfil" value="' . htmlspecialchars($resultado["nombre"]) . '">
                            <input type="hidden" name="Did" value="' . htmlspecialchars($resultado["id"]) . '">	
                            <h2>Apellido:</h2>
                            <input type="text" class="input-lg" name="apellidoPerfil" value="' . htmlspecialchars($resultado["apellido"]) . '">
                            <h2>Usuario:</h2>
                            <input type="text" class="input-lg" name="usuarioPerfil" value="' . htmlspecialchars($resultado["usuario"]) . '">
                            <h2>Contraseña:</h2>
                            <input type="text" class="input-lg" name="clavePerfil" value="' . htmlspecialchars($resultado["clave"]) . '">';

            $columna = "id";
            $valor = $resultado["id_consultorio"];
            $consultorio = ConsultoriosC::VerConsultoriosC($columna, $valor);

            if ($consultorio && is_array($consultorio)) {
                echo '<h2>Consultorio Actual: ' . htmlspecialchars($consultorio["nombre"]) . '</h2>';
            } else {
                echo '<h2>Error: Consultorio no encontrado.</h2>';
            }

            echo '<h3>Cambiar Consultorio</h3>
                  <select class="input-lg" name="consultorioPerfil">';

            $columna = null;
            $valor = null;
            $consultorios = ConsultoriosC::VerConsultoriosC($columna, $valor);

            if ($consultorios && is_array($consultorios)) {
                foreach ($consultorios as $key => $value) {
                    echo '<option value="' . htmlspecialchars($value["id"]) . '">' . htmlspecialchars($value["nombre"]) . '</option>';
                }
            } else {
                echo '<option value="">No hay consultorios disponibles.</option>';
            }

            echo '</select>
                  <div class="form-group">
                      <h2>Horario:</h2>
                      Desde: <input type="time" class="input-lg" name="hePerfil" value="' . htmlspecialchars($resultado["horarioE"]) . '">
                      Hasta: <input type="time" class="input-lg" name="hsPerfil" value="' . htmlspecialchars($resultado["horarioS"]) . '">
                  </div>
                </div>
                <div class="col-md-6 col-xs-12">
                    <br><br>
                    <input type="file" name="imgPerfil">
                    <br>';

            if ($resultado["foto"] == "") {
                echo '<img src="http://localhost/clinica/Vistas/img/defecto.png" class="img-responsive" width="200px">';
            } else {
                echo '<img src="http://localhost/clinica/' . htmlspecialchars($resultado["foto"]) . '" class="img-responsive" width="200px">';
            }

            echo '<input type="hidden" name="imgActual" value="' . htmlspecialchars($resultado["foto"]) . '">
                  <br><br>
                  <button type="submit" class="btn btn-success">Guardar Cambios</button>
                </div>
              </div>
            </form>';
        } else {
            echo json_encode(["error" => "No se encontró el perfil del doctor."]);
        }
    }

    // Actualizar Perfil Doctor
    public function ActualizarPerfilDoctorC() {
        if (isset($_POST["Did"])) {
            $rutaImg = $_POST["imgActual"];

            if (isset($_FILES["imgPerfil"]["tmp_name"]) && !empty($_FILES["imgPerfil"]["tmp_name"])) {
                // Verificar si la imagen actual existe antes de eliminarla
                if (!empty($_POST["imgActual"]) && file_exists($_POST["imgActual"])) {
                    unlink($_POST["imgActual"]); // Eliminar la imagen actual
                }

                // Crear la carpeta si no existe
                $carpetaDestino = "Vistas/img/Doctores/";
                if (!file_exists($carpetaDestino)) {
                    mkdir($carpetaDestino, 0777, true); // Crear la carpeta con permisos de escritura
                }

                // Generar un nombre único para la nueva imagen
                $nombre = mt_rand(100, 999);
                $rutaImg = $carpetaDestino . "Doc-" . $nombre;

                // Procesar la imagen según su tipo
                if ($_FILES["imgPerfil"]["type"] == "image/png") {
                    $rutaImg .= ".png";
                    $foto = imagecreatefrompng($_FILES["imgPerfil"]["tmp_name"]);
                    imagepng($foto, $rutaImg);
                } elseif ($_FILES["imgPerfil"]["type"] == "image/jpeg") {
                    $rutaImg .= ".jpg";
                    $foto = imagecreatefromjpeg($_FILES["imgPerfil"]["tmp_name"]);
                    imagejpeg($foto, $rutaImg);
                } else {
                    echo json_encode(["error" => "Formato de imagen no soportado. Use PNG o JPEG."]);
                    return;
                }
            }

            $tablaBD = "doctores";
            $datosC = array(
                "id" => $_POST["Did"],
                "nombre" => $_POST["nombrePerfil"],
                "apellido" => $_POST["apellidoPerfil"],
                "usuario" => $_POST["usuarioPerfil"],
                "clave" => $_POST["clavePerfil"],
                "consultorio" => $_POST["consultorioPerfil"],
                "horarioE" => $_POST["hePerfil"],
                "horarioS" => $_POST["hsPerfil"],
                "foto" => $rutaImg
            );

            // Validar datos antes de actualizar el perfil
            if ($this->validarDatos($datosC)) {
                $resultado = DoctoresM::ActualizarPerfilDoctorM($tablaBD, $datosC);

                if ($resultado) {
                    echo json_encode(["success" => true, "message" => "Perfil actualizado exitosamente."]);
                } else {
                    echo json_encode(["error" => "Error al actualizar el perfil."]);
                }
            } else {
                echo json_encode(["error" => "Datos inválidos."]);
            }
        } else {
            echo json_encode(["error" => "No se ha proporcionado un ID."]);
        }
    }

    // Método para validar datos
    private function validarDatos($datos) {
        foreach ($datos as $key => $value) {
            if (empty($value)) {
                return false;
            }
        }
        return true;
    }
}