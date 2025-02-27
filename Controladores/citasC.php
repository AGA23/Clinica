<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar permisos de usuario
if (!isset($_SESSION["rol"])) {
    // Redirigir al usuario a la página de inicio de sesión
    echo '<script> window.location = "login"; </script>';
    exit(); // Detener la ejecución del script
}

if ($_SESSION["rol"] != "Secretaria" && $_SESSION["rol"] != "Administrador") {
    echo '<script> window.location = "inicio"; </script>';
    exit(); // Detener la ejecución del script
}

require_once dirname(__DIR__) . "/Modelos/doctoresM.php";  // Asegúrate de que esta ruta sea correcta
require_once dirname(__DIR__) . "/Modelos/consultoriosM.php"; // Incluir el modelo de consultorios
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Gestor de Doctores</h1>
    </section>

    <section class="content">
        <div class="box">
            <div class="box-header">
                <?php
                // Mostrar el botón "Crear Doctor" solo para Administrador o Secretaria
                if ($_SESSION["rol"] == "Administrador" || $_SESSION["rol"] == "Secretaria") {
                    echo '<button class="btn btn-primary btn-lg" data-toggle="modal" data-target="#CrearDoctor">Crear Doctor</button>';
                }
                ?>
            </div>

            <div class="box-body">
                <table class="table table-bordered table-hover table-striped DT">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Apellido</th>
                            <th>Nombre</th>
                            <th>Foto</th>
                            <th>Consultorio</th>
                            <th>Usuario</th>
                            <th>Contraseña</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $columna = null;
                        $valor = null;

                        // Obtener todos los doctores
                        $resultado = DoctoresM::VerDoctoresM("doctores", $columna, $valor);

                        // Verificar si el resultado es un array válido y no está vacío
                        if (is_array($resultado) && !empty($resultado)) {
                            foreach ($resultado as $key => $value) {
                                echo '<tr>
                                        <td>' . ($key + 1) . '</td>
                                        <td>' . (isset($value["apellido"]) ? htmlspecialchars($value["apellido"]) : "No disponible") . '</td>
                                        <td>' . (isset($value["nombre"]) ? htmlspecialchars($value["nombre"]) : "No disponible") . '</td>';

                                // Mostrar la foto del doctor (o una imagen por defecto si no hay foto)
                                $foto = (isset($value["foto"]) && !empty($value["foto"])) ? $value["foto"] : "Vistas/img/defecto.png";
                                echo '<td><img src="' . htmlspecialchars($foto) . '" width="40px"></td>';

                                // Obtener el nombre del consultorio
                                $columna = "id";
                                $valor = isset($value["id_consultorio"]) ? $value["id_consultorio"] : null;
                                $consultorio = ConsultoriosM::VerConsultoriosM("consultorios", $columna, $valor);

                                // Mostrar el nombre del consultorio (o un mensaje si no está disponible)
                                $nombreConsultorio = (is_array($consultorio) && isset($consultorio["nombre"])) ? htmlspecialchars($consultorio["nombre"]) : "Consultorio no disponible";
                                echo '<td>' . $nombreConsultorio . '</td>';

                                // Mostrar el usuario y la contraseña (o un mensaje si no están disponibles)
                                $usuario = isset($value["usuario"]) ? htmlspecialchars($value["usuario"]) : "No disponible";
                                $clave = isset($value["clave"]) ? htmlspecialchars($value["clave"]) : "No disponible";

                                echo '<td>' . $usuario . '</td>
                                      <td>' . $clave . '</td>
                                      <td>
                                          <div class="btn-group">
                                              <button class="btn btn-success EditarDoctor" Did="' . (isset($value["id"]) ? $value["id"] : "") . '" data-toggle="modal" data-target="#EditarDoctor"><i class="fa fa-pencil"></i> Editar</button>
                                              <button class="btn btn-danger EliminarDoctor" Did="' . (isset($value["id"]) ? $value["id"] : "") . '" imgD="' . (isset($value["foto"]) ? $value["foto"] : "") . '"><i class="fa fa-times"></i> Borrar</button>
                                          </div>
                                      </td>
                                  </tr>';
                            }
                        } else {
                            // Mostrar un mensaje si no hay doctores registrados
                            echo '<tr><td colspan="8">No se encontraron doctores registrados.</td></tr>';
                        }
                        ?> 
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- Modal para crear un doctor -->
<div class="modal fade" id="CrearDoctor" tabindex="-1" role="dialog" aria-labelledby="CrearDoctorLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="CrearDoctorLabel">Crear Doctor</h4>
            </div>
            <div class="modal-body">
                <form id="formCrearDoctor">
                    <div class="form-group">
                        <label for="apellidoC">Apellido:</label>
                        <input type="text" class="form-control" id="apellidoC" name="apellidoC" required>
                    </div>
                    <div class="form-group">
                        <label for="nombreC">Nombre:</label>
                        <input type="text" class="form-control" id="nombreC" name="nombreC" required>
                    </div>
                    <div class="form-group">
                        <label for="usuarioC">Usuario:</label>
                        <input type="text" class="form-control" id="usuarioC" name="usuarioC" required>
                    </div>
                    <div class="form-group">
                        <label for="claveC">Contraseña:</label>
                        <input type="password" class="form-control" id="claveC" name="claveC" required>
                    </div>
                    <div class="form-group">
                        <label for="sexoC">Sexo:</label>
                        <select class="form-control" id="sexoC" name="sexoC" required>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>