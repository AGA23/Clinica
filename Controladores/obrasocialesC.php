<?php

class ObrasSocialesC {

    // 1. CREAR
    public function CrearObraSocialC() {
        if (isset($_POST["nombre_nuevo"])) {
            
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ .-]+$/', $_POST["nombre_nuevo"])) {
                
                $tabla = "obras_sociales";
                $datos = array(
                    "nombre" => $_POST["nombre_nuevo"],
                    "sigla"  => $_POST["sigla_nuevo"], // Nuevo campo
                    "tipo"   => $_POST["tipo_nuevo"],
                    "cuit"   => $_POST["cuit_nuevo"]
                );

                $respuesta = ObrasSocialesM::CrearObraSocialM($tabla, $datos);

                if ($respuesta == true) {
                    echo '<script>
                        Swal.fire({
                            icon: "success",
                            title: "Guardado correctamente",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then(function(result){
                            if(result.value){ window.location = "obras-sociales"; }
                        });
                    </script>';
                }
            } else {
                echo '<script>Swal.fire("Error", "El nombre no puede llevar caracteres especiales", "error");</script>';
            }
        }
    }

    // 2. EDITAR
    public function EditarObraSocialC() {
        if (isset($_POST["id_editar"])) {
            
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ .-]+$/', $_POST["nombre_editar"])) {
                
                $tabla = "obras_sociales";
                $datos = array(
                    "id"     => $_POST["id_editar"],
                    "nombre" => $_POST["nombre_editar"],
                    "sigla"  => $_POST["sigla_editar"], // Nuevo campo
                    "tipo"   => $_POST["tipo_editar"],
                    "cuit"   => $_POST["cuit_editar"]
                );

                $respuesta = ObrasSocialesM::EditarObraSocialM($tabla, $datos);

                if ($respuesta == true) {
                    echo '<script>
                        Swal.fire({
                            icon: "success",
                            title: "Actualizado correctamente",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then(function(result){
                            if(result.value){ window.location = "obras-sociales"; }
                        });
                    </script>';
                }
            } else {
                echo '<script>Swal.fire("Error", "Error en los datos ingresados", "error");</script>';
            }
        }
    }

    // 3. BORRAR
    public function BorrarObraSocialC() {
        if (isset($_GET["idObraSocial"])) {
            $id = $_GET["idObraSocial"];

            if ($id == 1) { // Proteger "Particular"
                echo '<script>
                    Swal.fire("Error", "No se puede eliminar la opción Particular.", "error")
                    .then(function(){ window.location = "obras-sociales"; });
                </script>';
                return;
            }

            $tabla = "obras_sociales";
            $respuesta = ObrasSocialesM::BorrarObraSocialM($tabla, $id);

            if ($respuesta == true) {
                echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "Borrado correctamente",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then(function(result){
                        if(result.value){ window.location = "obras-sociales"; }
                    });
                </script>';
            }
        }
    }

    static public function ObtenerTodasC() {
        return ObrasSocialesM::ObtenerTodasM("obras_sociales");
    }
    
    static public function ObtenerNombrePorCitaC($idCita) {
        return ObrasSocialesM::ObtenerNombrePorCitaM("citas", "obras_sociales", $idCita);
    }

    public function CrearPlanC() {
        if (isset($_POST["nuevo_plan_nombre"])) {
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ .-]+$/', $_POST["nuevo_plan_nombre"])) {
                
                $datos = array(
                    "id_obra_social" => $_POST["id_os_plan"], // ID oculto en el modal
                    "nombre_plan"    => $_POST["nuevo_plan_nombre"]
                );

                $respuesta = ObrasSocialesM::CrearPlanM($datos);

                if ($respuesta == true) {
                    echo '<script>
                        Swal.fire({
                            icon: "success",
                            title: "Plan agregado correctamente",
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function(){
                            window.location = "obras-sociales";
                        });
                    </script>';
                }
            }
        }
    }

    public function BorrarPlanC() {
        if (isset($_GET["idPlan"])) {
            $respuesta = ObrasSocialesM::BorrarPlanM($_GET["idPlan"]);
            if ($respuesta == true) {
                echo '<script>
                    window.location = "obras-sociales";
                </script>';
            }
        }
    }

    // Método estático para llenar selects en otras vistas (Pacientes)
    static public function ObtenerPlanesPorOSC($id_os) {
        return ObrasSocialesM::VerPlanesM($id_os);
    }
}