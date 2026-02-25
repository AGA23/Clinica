<?php
// En Controladores/PlantillasDocumentosC.php

class PlantillasDocumentosC {

    /**
     * Obtiene la lista de plantillas desde el modelo para el endpoint AJAX.
     * @return array La lista de plantillas.
     */
    public function ListarPlantillasC() {
        return PlantillasDocumentosM::ListarPlantillasM();
    }

  public function CrearPlantillaC() {
    if (isset($_POST["tituloCrear"])) {
        if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["tituloCrear"])) {

            // Guardamos el HTML crudo que viene de CKEditor
            $contenidoCrudo = $_POST["contenidoCrear"];

            $datos = [
                "titulo" => $_POST["tituloCrear"],
                "tipo"   => $_POST["tipoCrear"],
                "contenido" => $contenidoCrudo
            ];

            $respuesta = PlantillasDocumentosM::CrearPlantillaM($datos);

            if ($respuesta) {
                echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "¡La plantilla ha sido creada correctamente!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then(function(result){
                        if(result.value){
                            window.location = "plantillas-documentos";
                        }
                    });
                </script>';
            } else {
                echo '<script>
                    Swal.fire({
                        icon: "error",
                        title: "Error al guardar",
                        text: "No se pudo crear la plantilla en la base de datos.",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    });
                </script>';
            }

        } else {
            echo '<script>
                Swal.fire({
                    icon: "error",
                    title: "¡El título no puede contener caracteres especiales!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                });
            </script>';
        }
    }
}

public function ActualizarPlantillaC() {
    if (isset($_POST["idPlantillaEditar"])) {
        if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["tituloEditar"])) {

            // Guardamos HTML crudo sin escapar
            $contenidoCrudo = $_POST["contenidoEditar"];

            $datos = [
                "id" => $_POST["idPlantillaEditar"],
                "titulo" => $_POST["tituloEditar"],
                "tipo"   => $_POST["tipoEditar"],
                "contenido" => $contenidoCrudo
            ];

            $respuesta = PlantillasDocumentosM::ActualizarPlantillaM($datos);

            if ($respuesta) {
                echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "¡La plantilla ha sido actualizada correctamente!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then(function(result){
                        if(result.value){
                            window.location = "plantillas-documentos";
                        }
                    });
                </script>';
            } else {
                echo '<script>
                    Swal.fire({
                        icon: "error",
                        title: "Error al actualizar",
                        text: "No se pudo guardar los cambios en la base de datos.",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    });
                </script>';
            }

        } else {
             echo '<script>
                Swal.fire({
                    icon: "error",
                    title: "¡El título no puede contener caracteres especiales!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                });
            </script>';
        }
    }
}


    public function BorrarPlantillaC($id) {
        if (is_numeric($id) && $id > 0) {
            $respuesta = PlantillasDocumentosM::BorrarPlantillaM($id);
            return ["status" => ($respuesta == "ok") ? "success" : "error"];
        }
        return ["status" => "error", "message" => "ID no válido"];
    }

  public static function ObtenerEjemploPlantilla($tipo) {
        $ejemplos = [
            "receta" => '<div style="font-family: Arial, sans-serif;">
<h3>{CLINICA_NOMBRE}</h3>
<p>{CLINICA_DIRECCION} - Tel: {CLINICA_TELEFONO} - CUIT: {CLINICA_CUIT}</p>
<hr>
<h4>RECETA MÉDICA</h4>
<p>Paciente: <strong>{PACIENTE_NOMBRE}</strong> ({PACIENTE_DNI})</p>
<p>Diagnóstico: <strong>{DIAGNOSTICO}</strong></p>
<p>Medicación:</p>
<ul>{LISTA_MEDICAMENTOS}</ul>
<p>Indicaciones: {INDICACIONES_ADICIONALES}</p>
<p>Doctor: {DOCTOR_NOMBRE} - M.N. {MATRICULA_NACIONAL}</p>
<br><br>
<img src="{URL_FIRMA_DOCTOR}" alt="Firma del Doctor" style="height: 150px; width: auto;">
<p>_________________________<br>Dr./Dra. {DOCTOR_NOMBRE}</p>
</div>'
,
            "certificado" => '<div style="font-family: Arial, sans-serif; text-align: center;">
<h3>{CLINICA_NOMBRE}</h3>
<p>{CLINICA_DIRECCION}<br>Tel: {CLINICA_TELEFONO} - CUIT: {CLINICA_CUIT}</p>
<hr>
<h4>CERTIFICADO MÉDICO</h4>
<p style="text-align: right;">Fecha: {FECHA_EMISION}</p>
<p style="text-align: left;">
Yo, Dr./Dra. <strong>{DOCTOR_NOMBRE}</strong> (M.N. {MATRICULA_NACIONAL}), certifico que el/la paciente <strong>{PACIENTE_NOMBRE}</strong> ({PACIENTE_DNI}), ha sido atendido/a por un cuadro de <strong>{DIAGNOSTICO}</strong>.
</p>
<p style="text-align: left;">
Se indica reposo por <strong>{DIAS_REPOSO}</strong> días, desde el {FECHA_INICIO_REPOSO} hasta el {FECHA_FIN_REPOSO} inclusive.
</p>
<br><br>
<img src="{URL_FIRMA_DOCTOR}" alt="Firma del Doctor" style="height: 150px; width: auto;">
<p>_________________________<br>Dr./Dra. {DOCTOR_NOMBRE}</p>
</div>'
        ];

        return $ejemplos[$tipo] ?? '';
    }

    // Método para obtener los placeholders
    public static function ObtenerPlaceholders($tipo) {
        $placeholders = [
            "receta" => [
                '{CLINICA_NOMBRE}','{CLINICA_CUIT}','{SEDE_DIRECCION}','{SEDE_TELEFONO}','{SEDE_EMAIL}',
                '{DOCTOR_NOMBRE}','{MATRICULA_NACIONAL}','{MATRICULA_PROVINCIAL}','{URL_FIRMA_DOCTOR}',
                '{PACIENTE_NOMBRE}','{PACIENTE_DNI}','{FECHA_EMISION}','{DIAGNOSTICO}','{LISTA_MEDICAMENTOS}','{INDICACIONES_ADICIONALES}'
            ],
            "certificado" => [
                '{CLINICA_NOMBRE}','{CLINICA_CUIT}','{SEDE_DIRECCION}','{SEDE_TELEFONO}','{SEDE_EMAIL}',
                '{DOCTOR_NOMBRE}','{MATRICULA_NACIONAL}','{MATRICULA_PROVINCIAL}','{URL_FIRMA_DOCTOR}',
                '{PACIENTE_NOMBRE}','{PACIENTE_DNI}','{FECHA_EMISION}','{DIAGNOSTICO}','{DIAS_REPOSO}','{FECHA_INICIO_REPOSO}','{FECHA_FIN_REPOSO}'
            ]
        ];

        return $placeholders[$tipo] ?? [];
    }
}

