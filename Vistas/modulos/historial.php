<?php

// Verificar si el usuario tiene acceso
if ($_SESSION["id"] != substr($_GET["url"], 10)) {
    echo '<script>
        window.location = "inicio";
    </script>';
    return;
}

?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Su Historial de Citas Médicas</h1>
    </section>

    <section class="content">
        <div class="box">
            <div class="box-body">
                <table class="table table-bordered table-hover table-striped DT">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Doctor</th>
                            <th>Consultorio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Crear instancia del controlador de citas
                        $citasController = new CitasC();
                        // Obtener el resultado de las citas
                        $resultado = $citasController->VerCitasC();

                        // Recorrer las citas
                        foreach ($resultado as $key => $value) {
                            // Verificar si el documento de la cita coincide con el del usuario
                            if ($_SESSION["documento"] == $value["documento"]) {
                                echo '<tr>';
                                echo '<td>' . $value["inicio"] . '</td>';

                                // Obtener el nombre del doctor
                                $doctoresController = new DoctoresC();
                                $doctor = $doctoresController->DoctorC("id", $value["id_doctor"]);
                                echo '<td>' . $doctor["apellido"] . ' ' . $doctor["nombre"] . '</td>';

                                // Obtener el nombre del consultorio
                                $consultoriosController = new ConsultoriosC();
                                $consultorio = $consultoriosController->VerConsultoriosC("id", $value["id_consultorio"]);
                                echo '<td>' . $consultorio["nombre"] . '</td>';

                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
