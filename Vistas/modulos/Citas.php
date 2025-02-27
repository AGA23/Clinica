<?php
// session_start(); // Elimina esta línea si la sesión ya está iniciada

// Verificar si el usuario tiene permiso para ver esta página
if ($_SESSION["id"] != substr($_GET["url"], 6)) {
    echo '<script>window.location = "inicio";</script>';
    return;
}
?>

<div class="content-wrapper">
    <section class="content-header">
        <?php
        $columna = "id";
        $valor = substr($_GET["url"], 6);

        $resultado = DoctoresC::DoctorC($columna, $valor);

        if ($resultado && is_array($resultado)) {
            if ($resultado["sexo"] == "Femenino") {
                echo '<h1>Doctora: ' . $resultado["apellido"] . ' ' . $resultado["nombre"] . '</h1>';
            } else {
                echo '<h1>Doctor: ' . $resultado["apellido"] . ' ' . $resultado["nombre"] . '</h1>';
            }

            $columna = "id";
            $valor = $resultado["id_consultorio"];

            $consultorio = ConsultoriosC::VerConsultoriosC($columna, $valor);

            if ($consultorio && is_array($consultorio)) {
                echo '<br><h1>Consultorio de: ' . $consultorio["nombre"] . '</h1>';
            } else {
                echo '<br><h1>Error: No se encontró el consultorio.</h1>';
            }
        } else {
            echo '<h1>Error: No se encontró el doctor.</h1>';
        }
        ?>
    </section>

    <section class="content">
        <div class="box">
            <div class="box-body">
                <div id="calendar"></div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" rol="dialog" id="CitaModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-body">
                    <div class="box-body">
                        <?php
                        $columna = "id";
                        $valor = substr($_GET["url"], 6);

                        $resultado = DoctoresC::DoctorC($columna, $valor);

                        if ($resultado && is_array($resultado)) {
                            echo '<div class="form-group">
                                <input type="hidden" name="Did" value="' . $resultado["id"] . '">
                            </div>';

                            $columna = "id";
                            $valor = $resultado["id_consultorio"];

                            $consultorio = ConsultoriosC::VerConsultoriosC($columna, $valor);

                            if ($consultorio && is_array($consultorio)) {
                                echo '<div class="form-group">
                                    <input type="hidden" name="Cid" value="' . $consultorio["id"] . '">
                                </div>';
                            }
                        }
                        ?>

                        <div class="form-group">
                            <h2>Seleccionar Paciente:</h2>
                            <?php
                            echo '<select class="form-control input-lg SP">
                                <option value="">Paciente...</option>';

                            $columna = null;
                            $valor = null;

                            $resultado = PacientesC::VerPacientesC($columna, $valor);

                            if ($resultado && is_array($resultado)) {
                                foreach ($resultado as $key => $value) {
                                    echo '<option value="' . $value["id"] . '">' . $value["apellido"] . ' ' . $value["nombre"] . '</option>';
                                }
                            } else {
                                echo '<option value="">No hay pacientes disponibles.</option>';
                            }
                            ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <h2>Documento:</h2>
                            <input type="text" class="form-control input-lg" id="documentoP" name="documentoP" value="" readonly="">
                            <input type="hidden" class="form-control input-lg" id="nombreP" name="nombreP" value="" readonly="">
                        </div>

                        <div class="form-group">
                            <h2>Fecha:</h2>
                            <input type="text" class="form-control input-lg" id="fechaC" value="" readonly>
                        </div>

                        <div class="form-group">
                            <h2>Hora:</h2>
                            <input type="text" class="form-control input-lg" id="horaC" value="" readonly>
                        </div>

                        <div class="form-group">
                            <input type="hidden" class="form-control input-lg" name="fyhIC" id="fyhIC" value="" readonly>
                            <input type="hidden" class="form-control input-lg" name="fyhFC" id="fyhFC" value="" readonly>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <input type="hidden" name="accion" value="pedirCitaDoctor">
                    <button type="submit" class="btn btn-primary">Pedir Cita</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>