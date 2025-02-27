<?php

class ConsultoriosC {

    // Crear Consultorio
    public function CrearConsultorioC() {
        if (isset($_POST["consultorioN"])) {
            $tablaBD = "consultorios";
            $consultorio = array("nombre" => $_POST["consultorioN"]);

            $resultado = ConsultoriosM::CrearConsultorioM($tablaBD, $consultorio);

            if ($resultado) {
                echo '<script>
                    window.location = "http://localhost/clinica/consultorios";
                </script>';
            } else {
                echo '<script>
                    alert("Error al crear el consultorio.");
                    window.location = "http://localhost/clinica/consultorios";
                </script>';
            }
        }
    }

    // Ver Consultorios
    static public function VerConsultoriosC($columna, $valor) {
        $tablaBD = "consultorios";
        $resultado = ConsultoriosM::VerConsultoriosM($tablaBD, $columna, $valor);

        if ($resultado === false || empty($resultado)) {
            return false; // No se encontraron resultados
        }

        return $resultado;
    }

    // Borrar Consultorio
    public function BorrarConsultorioC() {
        if (substr($_GET["url"], 13)) {
            $tablaBD = "consultorios";
            $id = substr($_GET["url"], 13);

            $resultado = ConsultoriosM::BorrarConsultorioM($tablaBD, $id);

            if ($resultado) {
                echo '<script>
                    window.location = "http://localhost/clinica/consultorios";
                </script>';
            } else {
                echo '<script>
                    alert("No se puede eliminar el consultorio porque tiene doctores asociados.");
                    window.location = "http://localhost/clinica/consultorios";
                </script>';
            }
        }
    }

    // Editar Consultorio
    public function EditarConsultoriosC() {
        $tablaBD = "consultorios";
        $id = substr($_GET["url"], 4);

        $resultado = ConsultoriosM::EditarConsultoriosM($tablaBD, $id);

        if ($resultado && is_array($resultado)) {
            echo '<div class="form-group">
                <h2>Nombre:</h2>
                <input type="text" class="form-control input-lg" name="consultorioE" value="' . htmlspecialchars($resultado["nombre"]) . '">
                <input type="hidden" class="form-control input-lg" name="Cid" value="' . htmlspecialchars($resultado["id"]) . '">
                <br>
                <button class="btn btn-success" type="submit">Guardar Cambios</button>
            </div>';
        } else {
            echo '<script>
                alert("Error: Consultorio no encontrado.");
                window.location = "http://localhost/clinica/consultorios";
            </script>';
        }
    }

    // Actualizar Consultorio
    public function ActualizarConsultoriosC() {
        if (isset($_POST["consultorioE"])) {
            $tablaBD = "consultorios";
            $datosC = array(
                "id" => $_POST["Cid"],
                "nombre" => $_POST["consultorioE"]
            );

            $resultado = ConsultoriosM::ActualizarConsultoriosM($tablaBD, $datosC);

            if ($resultado) {
                echo '<script>
                    window.location = "http://localhost/clinica/consultorios";
                </script>';
            } else {
                echo '<script>
                    alert("Error al actualizar el consultorio.");
                    window.location = "http://localhost/clinica/consultorios";
                </script>';
            }
        }
    }
}