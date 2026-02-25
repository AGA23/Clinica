<?php


class ConsultoriosC {

     public static function VerDirectorioMedicoC() {
        return ConsultoriosM::ObtenerInformacionCompletaConsultorios();
    }

     public static function VerHorariosConsultoriosC() {
        return ConsultoriosM::ObtenerHorariosDeConsultorios();
    }
   static public function VerParaAdminC() {
        return ConsultoriosM::VerConsultoriosParaAdmin();
    }
    public function CrearConsultorioC() {
    // Solo el Administrador puede crear sedes
    if (isset($_POST["crear_consultorio"]) && $_SESSION['rol'] === 'Administrador') {
        
        // Recogemos todos los datos del nuevo formulario
        $datos = [
            "nombre" => trim($_POST["nombre"]),
            "direccion" => trim($_POST["direccion"]),
            "telefono" => trim($_POST["telefono"]),
            "email" => trim($_POST["email"])
        ];

        // Validación simple para el nombre
        if (!empty($datos['nombre'])) {
            $respuesta = ConsultoriosM::CrearConsultorioM("consultorios", $datos);
            $_SESSION['mensaje_consultorios'] = $respuesta ? "¡Nueva sede creada correctamente!" : "Error al crear la sede.";
            $_SESSION['tipo_mensaje_consultorios'] = $respuesta ? "success" : "danger";
        } else {
            $_SESSION['mensaje_consultorios'] = "Error: El nombre de la sede es obligatorio.";
            $_SESSION['tipo_mensaje_consultorios'] = "danger";
        }
        
        echo '<script>window.location = "consultorios";</script>';
        exit();
    }
}

public static function VerDirectorioPublicoC() {
    
    $datos_clinica = InicioM::ObtenerDatosParaDocumentosM(); 
    $datos_sedes = ConsultoriosM::ObtenerDatosParaDirectorioPublico();
    return [
        'clinica' => $datos_clinica,
        'sedes' => $datos_sedes
    ];
}
    
public function BorrarConsultorioC($id) {
  
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
        return ['success' => false, 'error' => 'Acción no permitida. Permisos insuficientes.'];
    }
    if (!is_numeric($id) || $id <= 0) {
        return ['success' => false, 'error' => 'El ID del consultorio proporcionado no es válido.'];
    }
    $resultado = ConsultoriosM::BorrarConsultorioM("consultorios", $id);

    if ($resultado) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'No se pudo eliminar la sede. Es posible que tenga doctores, citas u otros registros asociados.'];
    }
}


static public function VerConsultoriosC($columna, $valor) {
    $tabla = "consultorios";
    $respuesta = ConsultoriosM::VerConsultoriosM($tabla, $columna, $valor);
    return $respuesta;
}


    static public function ObtenerListaConsultoriosC() {
        
        return ConsultoriosM::VerConsultoriosM("consultorios", null, null);
    }

   public function ActualizarConsultorioC() {
    if (isset($_POST["editar_consultorio"])) {
        $id = $_POST["id_consultorio_editar"];

        // Validación de Permisos: Secretario solo puede editar su propia sede
        if ($_SESSION['rol'] === 'Secretario' && $id != $_SESSION['id_consultorio']) {
            $_SESSION['mensaje_consultorios'] = "Error: Permiso denegado.";
            $_SESSION['tipo_mensaje_consultorios'] = "danger";
            echo '<script>window.location = "consultorios";</script>';
            exit();
        }

        // Preparamos el array de datos con los nuevos campos
        $datos = [
            "id" => $id,
            "nombre" => trim($_POST["nombre_editar"]),
            "direccion" => trim($_POST["direccion_editar"]),
            "telefono" => trim($_POST["telefono_editar"]),
            "email" => trim($_POST["email_editar"])
        ];

        // El Secretario no puede cambiar el nombre, solo el Admin
        if ($_SESSION['rol'] !== 'Administrador') {
            unset($datos['nombre']); // Quitamos el nombre del array de datos si es un secretario
        }
        
        $horarios = $_POST["horario"] ?? [];

        if (!empty($id)) {
            // Actualizamos los datos principales de la sede
            ConsultoriosM::ActualizarConsultorioM("consultorios", $datos);
            
            // Actualizamos los horarios
            ConsultoriosM::ActualizarHorariosM($id, $horarios);

            $_SESSION['mensaje_consultorios'] = "¡Sede actualizada correctamente!";
            $_SESSION['tipo_mensaje_consultorios'] = "success";
        } else {
            $_SESSION['mensaje_consultorios'] = "Error: El ID no puede estar vacío.";
            $_SESSION['tipo_mensaje_consultorios'] = "danger";
        }
        
        echo '<script>window.location = "consultorios";</script>';
        exit();
    }
}


   
}