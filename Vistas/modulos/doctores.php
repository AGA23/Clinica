<?php

if ($_SESSION["rol"] != "Secretaria" && $_SESSION["rol"] != "Administrador") {
    echo '<script> window.location = "inicio"; </script>';
    return;
}

?>

<div class="content-wrapper">

    <section class="content-header">
        <h1>Gestor de Doctores</h1>
    </section>

    <section class="content">
        
        <div class="box">
            
            <div class="box-header">
                <button class="btn btn-primary btn-lg" data-toggle="modal" data-target="#CrearDoctor">Crear Doctor</button>
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
                            <th>Editar / Borrar</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php
                        $columna = null;
                        $valor = null;

                        // Verificar si el resultado de la consulta es un array válido
                        $resultado = DoctoresC::VerDoctoresC($columna, $valor);

                        // Verificar que no sea un valor booleano (false)
                        if (is_array($resultado)) {
                            foreach ($resultado as $key => $value) {
                                echo '<tr>
                                        <td>' . ($key + 1) . '</td>
                                        <td>' . $value["apellido"] . '</td>
                                        <td>' . $value["nombre"] . '</td>';

                                // Comprobar si la foto está vacía, si es así asignar imagen por defecto
                                $foto = empty($value["foto"]) ? "Vistas/img/defecto.png" : $value["foto"];
                                echo '<td><img src="' . $foto . '" width="40px"></td>';

                                // Consulta para obtener el nombre del consultorio
                                $columna = "id";
                                $valor = $value["id_consultorio"];
                                $consultorio = ConsultoriosC::VerConsultoriosC($columna, $valor);

                                // Verificar si se obtiene un consultorio válido
                                $nombreConsultorio = (is_array($consultorio)) ? $consultorio["nombre"] : "Consultorio no disponible";
                                echo '<td>' . $nombreConsultorio . '</td>';

                                echo '<td>' . $value["usuario"] . '</td>
                                      <td>' . $value["clave"] . '</td>
                                      <td>
                                          <div class="btn-group">
                                              <button class="btn btn-success EditarDoctor" Did="' . $value["id"] . '" data-toggle="modal" data-target="#EditarDoctor"><i class="fa fa-pencil"></i> Editar</button>
                                              <button class="btn btn-danger EliminarDoctor" Did="' . $value["id"] . '" imgD="' . $value["foto"] . '"><i class="fa fa-times"></i> Borrar</button>
                                          </div>
                                      </td>
                                  </tr>';
                            }
                        } else {
                            echo '<tr><td colspan="8">No se encontraron doctores registrados.</td></tr>';
                        }

                        ?> 

                    </tbody>
                </table>

            </div>

        </div>

    </section>

</div>
