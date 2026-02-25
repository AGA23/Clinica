<?php
// En Controladores/PacientesC.php (VERSIÓN FINAL Y CORREGIDA)

class PacientesC {

    // --- MÉTODOS PARA LA GESTIÓN (SECRETARÍA/ADMIN) ---

    public static function ListarPacientesC() {
        return PacientesM::ListarPacientesM();
    }
    
    public static function ObtenerPacienteC($id) {
        return PacientesM::ObtenerPacienteM($id);
    }

    public static function VerificarUsuarioC($usuario) {
        return PacientesM::VerificarUsuarioM($usuario);
    }

    // 1. CREAR PACIENTE
     public function CrearPacienteC() {
        if (isset($_POST["crear_paciente"])) {
            
            // Captura de datos de cobertura
            $id_obra_social = !empty($_POST["nuevoIdObraSocial"]) ? $_POST["nuevoIdObraSocial"] : null;
            $plan = !empty($_POST["nuevoPlan"]) ? $_POST["nuevoPlan"] : null;
            // Usamos trim para asegurar que no sean solo espacios
            $numero_afiliado = !empty($_POST["nuevoNumeroAfiliado"]) ? trim($_POST["nuevoNumeroAfiliado"]) : null;

            // --- 🛑 VALIDACIÓN DE SEGURIDAD: AFILIADO OBLIGATORIO ---
            // Si se eligió una OS (que no sea Particular ID 1) y el afiliado está vacío...
            if ($id_obra_social && $id_obra_social != 1 && empty($numero_afiliado)) {
                $_SESSION['mensaje_pacientes'] = "Error: El número de afiliado es obligatorio para Obras Sociales o Prepagas.";
                $_SESSION['tipo_mensaje_pacientes'] = "warning";
                echo '<script>window.location = "pacientes";</script>';
                exit();
            }
            // --------------------------------------------------------

            $datos = [
                "nombre" => trim($_POST['nombre']),
                "apellido" => trim($_POST['apellido']),
                "tipo_documento" => $_POST['tipo_documento'],
                "numero_documento" => trim($_POST['numero_documento']),
                "usuario" => trim($_POST['usuario']),
                "clave" => trim($_POST['clave']),
                "correo" => trim($_POST['correo']),
                "rol" => "Paciente",
                
                // Nuevos campos
                "id_obra_social" => $id_obra_social,
                "plan" => $plan,
                "numero_afiliado" => $numero_afiliado
            ];

            if (!empty($datos['nombre']) && !empty($datos['apellido']) && !empty($datos['numero_documento']) && !empty($datos['usuario']) && !empty($datos['clave'])) {
                $datos["clave"] = password_hash($datos["clave"], PASSWORD_DEFAULT);
                
                $respuesta = PacientesM::CrearPacienteM($datos);
                
                $_SESSION['mensaje_pacientes'] = $respuesta ? "¡Paciente creado correctamente!" : "Error al crear paciente. El DNI o usuario ya podría existir.";
                $_SESSION['tipo_mensaje_pacientes'] = $respuesta ? "success" : "danger";
            } else {
                $_SESSION['mensaje_pacientes'] = "Error: Faltan campos obligatorios.";
                $_SESSION['tipo_mensaje_pacientes'] = "danger";
            }
            echo '<script>window.location = "pacientes";</script>';
            exit();
        }
    }

    // 2. ACTUALIZAR (EDITAR) PACIENTE
    public function ActualizarPacienteC() {
        if (isset($_POST["editar_paciente"])) {
            
            // Captura de datos de cobertura (Inputs del modal editar)
            $id_obra_social = !empty($_POST["editarIdObraSocial"]) ? $_POST["editarIdObraSocial"] : null;
            $plan = !empty($_POST["editarPlan"]) ? $_POST["editarPlan"] : null;
            $numero_afiliado = !empty($_POST["editarNumeroAfiliado"]) ? trim($_POST["editarNumeroAfiliado"]) : null;

            // --- 🛑 VALIDACIÓN DE SEGURIDAD: AFILIADO OBLIGATORIO ---
            if ($id_obra_social && $id_obra_social != 1 && empty($numero_afiliado)) {
                $_SESSION['mensaje_pacientes'] = "Error: El número de afiliado es obligatorio para Obras Sociales o Prepagas.";
                $_SESSION['tipo_mensaje_pacientes'] = "warning";
                echo '<script>window.location = "pacientes";</script>';
                exit();
            }
            // --------------------------------------------------------

            $datos = [
                "id" => $_POST['id_paciente_editar'],
                "nombre" => trim($_POST['nombre_editar']),
                "apellido" => trim($_POST['apellido_editar']),
                "tipo_documento" => $_POST['tipo_documento_editar'],
                "numero_documento" => trim($_POST['numero_documento_editar']),
                "usuario" => trim($_POST['usuario_editar']),
                "correo" => trim($_POST['correo_editar']),
                
                // Nuevos campos
                "id_obra_social" => $id_obra_social,
                "plan" => $plan,
                "numero_afiliado" => $numero_afiliado
            ];
            
            // Clave solo si se cambia
            $datos["clave"] = !empty(trim($_POST['clave_editar'])) ? password_hash(trim($_POST['clave_editar']), PASSWORD_DEFAULT) : null;
            
            $respuesta = PacientesM::ActualizarPacienteM($datos);

            $_SESSION['mensaje_pacientes'] = $respuesta ? "¡Paciente actualizado correctamente!" : "Error al actualizar. El DNI o usuario podría pertenecer a otro paciente.";
            $_SESSION['tipo_mensaje_pacientes'] = $respuesta ? "success" : "danger";

            echo '<script>window.location = "pacientes";</script>';
            exit();
        }
    }

    public function BorrarPacienteC($id) {
        if ($id > 0) {
            $resultado = PacientesM::BorrarPacienteM($id);
            if ($resultado) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'No se pudo eliminar. Verifique que el paciente no tenga citas asociadas.'];
            }
        }
        return ['success' => false, 'error' => 'ID de paciente inválido.'];
    }

    // --- MÉTODOS PARA EL PERFIL DEL PROPIO PACIENTE ---

    public function ActualizarPerfilPacienteC() {
        if (isset($_POST["actualizarPerfilPaciente"])) {
            $id_paciente = $_POST["idPaciente"];

            if ($_SESSION['id'] != $id_paciente || $_SESSION['rol'] !== 'Paciente') {
                $_SESSION['mensaje_perfil'] = "Acción no permitida.";
                $_SESSION['tipo_mensaje_perfil'] = "danger";
                header("Location: " . BASE_URL . "inicio");
                exit();
            }

            $rutaFoto = $_POST["fotoActual"];
            // (Lógica de foto omitida por brevedad, mantener original)

            $claveActualizada = $_POST["claveActual"];
            if (!empty(trim($_POST["claveE"]))) {
                $claveActualizada = password_hash(trim($_POST["claveE"]), PASSWORD_DEFAULT);
            }

            $datosC = [
                "id" => $id_paciente,
                "nombre" => trim($_POST["nombreE"]),
                "apellido" => trim($_POST["apellidoE"]),
                "usuario" => trim($_POST["usuarioE"]),
                "correo" => trim($_POST["correoE"] ?? null),
                "telefono" => trim($_POST["telefonoE"] ?? null),
                "direccion" => trim($_POST["direccionE"] ?? null),
                "clave" => $claveActualizada,
                "foto" => $rutaFoto
            ];

            $resultado = PacientesM::ActualizarPerfilPacienteM($datosC);

            if ($resultado) {
                $_SESSION["nombre"] = $datosC["nombre"];
                $_SESSION["apellido"] = $datosC["apellido"];
                $_SESSION["usuario"] = $datosC["usuario"];
                $_SESSION["foto"] = $datosC["foto"];
                $_SESSION['mensaje_perfil'] = "¡Perfil actualizado correctamente!";
                $_SESSION['tipo_mensaje_perfil'] = "success";
            } else {
                $_SESSION['mensaje_perfil'] = "Error al actualizar el perfil.";
                $_SESSION['tipo_mensaje_perfil'] = "danger";
            }
            
            header("Location: " . BASE_URL . "perfil-Paciente");
            exit();
        }
    }

    public function VerPerfilPacienteC() {
        if (isset($_SESSION["id"]) && $_SESSION['rol'] === 'Paciente') {
            return PacientesM::ObtenerPacienteM($_SESSION["id"]);
        }
        return null;
    }

    // --- MÉTODOS DE APOYO ---

    public static function ListarTodosLosPacientesC() {
        return PacientesM::ListarPacientesSimpleM();
    }

   public function ActualizarInfoClinicaC() {
    if (isset($_POST['actualizar_info_clinica']) && $_SESSION['rol'] === 'Paciente') {
        $alergias = $_POST['alergias'] ?? [];
        $enfermedades = $_POST['enfermedades'] ?? [];
        
        $resultado1 = PacientesM::ActualizarCondicionesClinicasM($_SESSION['id'], $alergias, 'alergia');
        $resultado2 = PacientesM::ActualizarCondicionesClinicasM($_SESSION['id'], $enfermedades, 'enfermedad');
        
        if ($resultado1 && $resultado2) {
            $_SESSION['mensaje_historia_clinica'] = "¡Su información clínica ha sido actualizada!";
            $_SESSION['tipo_mensaje_historia_clinica'] = "success";
        } else {
            $_SESSION['mensaje_historia_clinica'] = "Error al guardar los cambios.";
            $_SESSION['tipo_mensaje_historia_clinica'] = "danger";
        }
        echo '<script>window.location = "mi-historia-clinica";</script>';
        exit();
    }
}

    public function GuardarSugerenciaCambioC($id_paciente, $campo, $sugerencia) {
        if ($id_paciente > 0 && !empty($campo) && !empty($sugerencia)) {
            $resultado = PacientesM::GuardarSugerenciaM($id_paciente, $campo, $sugerencia);
            if ($resultado) {
                return ['success' => true];
            }
        }
        return ['success' => false, 'error' => 'No se pudo guardar la sugerencia.'];
    }

    public function ValidarCondicionClinicaDoctorC($id_paciente, $id_condicion, $id_doctor) {
        if ($id_paciente > 0 && $id_condicion > 0) {
            $resultado = PacientesM::ValidarCondicionClinicaM($id_paciente, $id_condicion, $id_doctor);
            if ($resultado) {
                return ['success' => true];
            }
        }
        return ['success' => false, 'error' => 'Datos inválidos para la validación.'];
    }

    public function VerPacientePorIdC($id_paciente) {
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Doctor', 'Secretario', 'Administrador'])) {
            return false;
        }
        if (!is_numeric($id_paciente) || $id_paciente <= 0) {
            return false;
        }
        return PacientesM::ObtenerPacienteM($id_paciente);
    }
}