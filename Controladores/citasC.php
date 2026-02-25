<?php
// En Controladores/CitasC.php (VERSIÓN FINAL, COMPLETA Y UNIFICADA)

class CitasC {

    // --- MÉTODOS PARA OBTENER DATOS (VISTAS Y AJAX) ---

    public function VerCitasC() {
        return CitasM::VerTodasLasCitasM();
    }

    public function VerCitasPacienteC($id_paciente) {
        return (new CitasM())->VerCitasPacienteM($id_paciente);
    }

    public function ObtenerCitaC($id_cita) {
        if (!is_numeric($id_cita) || $id_cita <= 0) { return false; }
        return (new CitasM())->ObtenerCitaM($id_cita);
    }

    public function VerCitasDoctorC($id_doctor) {
        return (new CitasM())->VerCitasDoctorM($id_doctor); 
    }
    
 
    public function FinalizarCitaC() {
    // 1. VERIFICACIÓN INICIAL DEL FORMULARIO
    if (!isset($_POST["id_cita_finalizar"])) {
        return ['error' => 'Error crítico: No se recibió el ID de la cita.'];
    }

    $id_cita = $_POST["id_cita_finalizar"];
    $citasM = new CitasM();

    try {
        // --- 1. OBTENCIÓN DE DATOS AUXILIARES Y VERIFICACIÓN DE PERMISOS ---
        $cita_anterior = $citasM->ObtenerCitaM($id_cita);
        if (!$cita_anterior) {
            throw new Exception("La cita que intenta finalizar no fue encontrada.");
        }

        if ($_SESSION["rol"] === "Doctor" && $cita_anterior["id_doctor"] != $_SESSION["id"]) {
            throw new Exception("No tiene permisos para finalizar esta cita.");
        }

        $paciente = PacientesM::ObtenerPacienteM($cita_anterior['id_paciente']);
        $doctor = DoctoresM::ObtenerDoctorM($cita_anterior['id_doctor']);
        
        // === Se llaman a los modelos correctos (Sin cambios) ===
        $infoClinica = ClinicaM::ObtenerDatosGlobalesM();
        $infoSede = ConsultoriosM::ObtenerDatosConsultorioM($cita_anterior['id_consultorio']);

        // --- 2. RECOPILACIÓN Y SANITIZACIÓN DE DATOS DEL FORMULARIO ---
        $motivo_final = htmlspecialchars($_POST["motivo"] ?? $cita_anterior['motivo']);
        $observaciones = htmlspecialchars($_POST["observaciones"] ?? '');
        $peso = !empty($_POST["peso"]) ? $_POST["peso"] : null;
        $presion_arterial = !empty($_POST["presion_arterial"]) ? htmlspecialchars($_POST["presion_arterial"]) : null;
        $indicaciones = $_POST["indicaciones_receta"] ?? null;
        $receta_items = json_decode($_POST['receta_json'] ?? '[]', true);
        $tratamientos_aplicados_ids = $_POST["tratamientos"] ?? [];
        $certificado_plantilla = $_POST['certificado_texto_final'] ?? null;
        $receta_plantilla = $_POST['receta_texto_final'] ?? null;
        
        // --- 🆕 CAPTURA DE COBERTURA FINAL (Permite override final) ---
        // Se toma el valor enviado por el formulario, o se mantiene el valor anterior si no se envió nada.
        $id_tipo_pago_final = $_POST["id_tipo_pago_finalizar"] ?? ($cita_anterior['id_tipo_pago'] ?? null);
        $id_cobertura_aplicada_final = $_POST["id_cobertura_aplicada_finalizar"] ?? ($cita_anterior['id_cobertura_aplicada'] ?? null);
        // ------------------------------------------------------------

        // --- 3. PROCESAMIENTO DE PLANTILLAS PARA DOCUMENTOS ---
        $certificado_texto_final = null;
        $receta_texto_final = null;
        $certificado_uuid = null;
        $receta_uuid = null;

        $listaMedicamentosHTML = "No se indicaron medicamentos.";
        if (!empty($receta_items)) {
            $listaMedicamentosHTML = "<ul style='padding-left: 20px; margin: 0;'>";
            foreach ($receta_items as $item) {
                $nombre = htmlspecialchars($item['nombre_completo'] ?? ($item['nombre_recetado'] ?? 'Medicamento'));
                $dosis = htmlspecialchars($item['dosis'] ?? '');
                $frecuencia = htmlspecialchars($item['frecuencia'] ?? '');
                $listaMedicamentosHTML .= "<li style='margin-bottom: 5px;'><strong>{$nombre}</strong><br>Indicación: {$dosis}, {$frecuencia}</li>";
            }
            $listaMedicamentosHTML .= "</ul>";
        }

        $placeholders = [
            '{CLINICA_NOMBRE}', '{CLINICA_CUIT}', '{CLINICA_DIRECCION}', '{CLINICA_TELEFONO}',
            '{SEDE_NOMBRE}', '{SEDE_DIRECCION}', '{SEDE_TELEFONO}', '{SEDE_EMAIL}',
            '{DOCTOR_NOMBRE}', '{MATRICULA_NACIONAL}', '{MATRICULA_PROVINCIAL}', '{URL_FIRMA_DOCTOR}',
            '{PACIENTE_NOMBRE}', '{PACIENTE_DNI}',
            '{FECHA_EMISION}', '{DIAGNOSTICO}', '{LISTA_MEDICAMENTOS}', '{INDICACIONES_ADICIONALES}'
        ];

        $pacienteDNI = !empty($paciente["numero_documento"]) ? (($paciente["tipo_documento"] ?? 'DNI') . ' ' . $paciente["numero_documento"]) : 'N/A';
        
        $rutaFirmaAbsoluta = str_replace('\\', '/', getcwd() . '/' . ($doctor["firma_digital"] ?? 'Vistas/img/firmas/default.png'));

        // === Se usan las claves correctas de los arrays $infoClinica e $infoSede ===
        $replacements = [
            $infoClinica["nombre_clinica"] ?? 'N/A', $infoClinica["cuit_clinica"] ?? 'N/A', $infoSede["direccion"] ?? 'N/A', $infoSede["telefono"] ?? 'N/A',
            $infoSede["nombre"] ?? 'N/A', $infoSede["direccion"] ?? 'N/A', $infoSede["telefono"] ?? 'N/A', $infoSede["email"] ?? 'N/A',
            ($doctor["nombre"] ?? '') . ' ' . ($doctor["apellido"] ?? ''), $doctor["matricula_nacional"] ?? 'N/A', $doctor["matricula_provincial"] ?? 'N/A',
            $rutaFirmaAbsoluta,
            ($paciente["nombre"] ?? '') . ' ' . ($paciente["apellido"] ?? ''), $pacienteDNI,
            date('d/m/Y'), nl2br($observaciones),
            $listaMedicamentosHTML, nl2br(htmlspecialchars($indicaciones))
        ];

        if (!empty(trim($certificado_plantilla))) {
            $certificado_texto_final = str_replace($placeholders, $replacements, $certificado_plantilla);
            $certificado_uuid = $this->generate_uuid_v4();
        }

        if (!empty(trim($receta_plantilla))) {
            $receta_texto_final = str_replace($placeholders, $replacements, $receta_plantilla);
            $receta_uuid = $this->generate_uuid_v4();
        }

        // --- 4. PREPARAR ARRAY DE DATOS PARA ENVIAR AL MODELO ---
        $datosParaFinalizar = [
            'id' => $id_cita,
            'motivo' => $motivo_final,
            'observaciones' => $observaciones,
            'peso' => $peso,
            'presion_arterial' => $presion_arterial,
            'indicaciones_receta' => $indicaciones,
            'certificado_texto_final' => $certificado_texto_final,
            'certificado_uuid' => $certificado_uuid,
            'receta_texto_final' => $receta_texto_final,
            'receta_uuid' => $receta_uuid,
            'receta_json' => $_POST['receta_json'] ?? '[]',
            
            // 🆕 INCLUSIÓN DE DATOS DE COBERTURA FINALES
            'id_tipo_pago' => $id_tipo_pago_final,
            'id_cobertura_aplicada' => $id_cobertura_aplicada_final
        ];

        // --- 5. LLAMADA A LOS MÉTODOS DEL MODELO ---
        // FinalizarCitaM debe manejar la transacción (BEGIN/COMMIT/ROLLBACK)
        $citasM->FinalizarCitaM($datosParaFinalizar, $receta_items, $tratamientos_aplicados_ids); 

        // --- 6. REGISTRO DE CAMBIOS (LOGS) ---
        $usuario_log = $_SESSION["nombre"] . ' ' . $_SESSION["apellido"];
        $id_usuario_log = $_SESSION["id"];
        $rol_usuario_log = $_SESSION["rol"];

        // LOG: Estado
        $citasM->RegistrarCambiosCita($id_cita, 'Estado', $cita_anterior['estado'], 'Completada', $usuario_log, $id_usuario_log, $rol_usuario_log);
        
        // 🆕 LOG: Cobertura (solo si cambió al finalizar)
        $coberturaAnteriorID = $cita_anterior['id_cobertura_aplicada'] ?? $cita_anterior['id_tipo_pago'];
        $coberturaNuevaID = $datosParaFinalizar['id_cobertura_aplicada'] ?? $datosParaFinalizar['id_tipo_pago'];

        if ($coberturaAnteriorID != $coberturaNuevaID) {
            // Asumiendo que CitasM::ObtenerNombreCoberturaCitaM existe en el modelo
            $nombreOSAnterior = $citasM->ObtenerNombreCoberturaCitaM($cita_anterior['id_cobertura_aplicada'], $cita_anterior['id_tipo_pago']); 
            $nombreOSNuevo = $citasM->ObtenerNombreCoberturaCitaM($datosParaFinalizar['id_cobertura_aplicada'], $datosParaFinalizar['id_tipo_pago']); 
            $citasM->RegistrarCambiosCita($id_cita, 'Cobertura Final', $nombreOSAnterior, $nombreOSNuevo, $usuario_log, $id_usuario_log, $rol_usuario_log);
        }

        // LOG: Signos vitales y clínicos
        if ($peso) $citasM->RegistrarCambiosCita($id_cita, 'Signo Vital', 'Peso registrado', $peso . ' kg', $usuario_log, $id_usuario_log, $rol_usuario_log);
        if ($presion_arterial) $citasM->RegistrarCambiosCita($id_cita, 'Signo Vital', 'Presión Arterial', $presion_arterial, $usuario_log, $id_usuario_log, $rol_usuario_log);
        if ($observaciones) $citasM->RegistrarCambiosCita($id_cita, 'Diagnóstico', 'Añadido', substr($observaciones, 0, 100) . '...', $usuario_log, $id_usuario_log, $rol_usuario_log);

        // LOG: Receta/Medicamentos y Documentos
        foreach ($receta_items as $item) {
            $nombre = $item['nombre_completo'] ?? ($item['nombre_recetado'] ?? 'Medicamento');
            $desc = $nombre . (!empty($item['dosis']) ? " (Dosis: " . $item['dosis'] . ")" : "");
            $citasM->RegistrarCambiosCita($id_cita, 'Receta', 'Medicamento añadido', $desc, $usuario_log, $id_usuario_log, $rol_usuario_log);
        }
        if ($certificado_uuid) $citasM->RegistrarCambiosCita($id_cita, 'Documento', 'Certificado generado', 'ID: ' . $certificado_uuid, $usuario_log, $id_usuario_log, $rol_usuario_log);
        if ($receta_uuid) $citasM->RegistrarCambiosCita($id_cita, 'Documento', 'Receta generada', 'ID: ' . $receta_uuid, $usuario_log, $id_usuario_log, $rol_usuario_log);

        // --- El commit es manejado dentro de CitasM::FinalizarCitaM ---

        return ['success' => 'Cita finalizada y documentos generados correctamente.'];

    } catch (Exception $e) {
        // El rollback es manejado dentro de CitasM::FinalizarCitaM
        error_log("Error al finalizar la cita ID $id_cita: " . $e->getMessage());
        return ['error' => 'No se pudo finalizar la cita: ' . $e->getMessage()];
    }
}


    public function VerHistorialClinicoC($filtros) {
        return (new CitasM())->BuscarEnHistorialM($filtros);
    }

    public static function ListarDoctoresParaAgendaC() {
        if (!isset($_SESSION['rol'])) return [];
        if ($_SESSION['rol'] === 'Administrador') { return DoctoresM::ListarDoctoresM(); }
        elseif ($_SESSION['rol'] === 'Secretario' && isset($_SESSION['id_consultorio'])) {
            return DoctoresM::ListarDoctoresPorConsultorioM($_SESSION['id_consultorio']);
        }
        return [];
    }

 public static function ObtenerHorariosDisponiblesC($id_doctor, $fecha) {
        
        
        $horarios_finales = CitasM::ObtenerHorariosDisponiblesM($id_doctor, $fecha);
        
        return $horarios_finales;
    }

    // --- MÉTODOS PARA PROCESAR ACCIONES ---

 

    public function CrearCitaC() {
    if (isset($_POST["crear_cita_doctor"])) {
        
        // --- VALIDACIÓN 1: PERMISOS DE ROL Y SESIÓN (Sin Cambios) ---
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Doctor' || !isset($_SESSION['id'])) {
            return "Error de permisos: Esta acción solo está permitida para doctores o sesión inválida.";
        }
        
        // --- VALIDACIÓN 2: DATOS OBLIGATORIOS DEL FORMULARIO ---
        $campos_requeridos = ['id_consultorio', 'id_paciente', 'id_tratamiento', 'fecha', 'hora_inicio', 'hora_fin', 'id_tipo_pago']; 
        foreach ($campos_requeridos as $campo) {
            if (!isset($_POST[$campo]) || empty(trim($_POST[$campo]))) {
                return "Error: Faltan datos para crear la cita. Campo requerido: " . $campo;
            }
        }
        
        // --- 🆕 Captura de Cobertura ---
        $id_tipo_pago = $_POST["id_tipo_pago"]; 
        $id_cobertura_aplicada = $_POST["id_cobertura_aplicada"] ?? null; 
        // ---------------------------------

        // --- PREPARACIÓN DE DATOS PARA EL MODELO ---
        $datosCita = [
            "id_doctor"      => $_SESSION["id"],
            "id_consultorio" => $_POST["id_consultorio"],
            "id_paciente"    => $_POST["id_paciente"],
            "nyaP"           => PacientesM::ObtenerNombrePacienteM($_POST["id_paciente"]),
            "motivo"         => TratamientosM::ObtenerNombreTratamientoM($_POST["id_tratamiento"]),
            "inicio"         => $_POST["fecha"] . ' ' . $_POST["hora_inicio"] . ':00',
            "fin"            => $_POST["fecha"] . ' ' . $_POST["hora_fin"] . ':00',
            "estado"         => "Pendiente",
            "observaciones"  => "",
            
            // 🆕 INCLUSIÓN DE DATOS DE COBERTURA
            "id_tipo_pago"             => $id_tipo_pago,
            "id_cobertura_aplicada"    => $id_cobertura_aplicada
        ];

        // --- LLAMADA AL MODELO PARA LA CREACIÓN ---
        $citasM = new CitasM();
        $resultado = $citasM->CrearCitaM("citas", $datosCita);

        // --- MANEJO DEL RESULTADO ---
        if ($resultado === true) {
            $id_cita = $citasM->getPDO()->lastInsertId();
            $this->registrarLogCreacionCita($id_cita, $datosCita);
            return true;
        } else {
            return $resultado;
        }
    }
    
    return false;
}
    
    function generate_uuid_v4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }


    public function CancelarCitaC($id_cita, $motivo_cancelacion) {
        // 1. Validaciones iniciales
        if (!isset($_SESSION['id'])) {
            return ['error' => 'Sesión no válida. Por favor, inicie sesión de nuevo.'];
        }
        if (!is_numeric($id_cita) || $id_cita <= 0) {
            return ['error' => 'ID de cita inválido.'];
        }
        
        // Asigna un motivo por defecto si viene vacío.
        $motivo_final = !empty(trim($motivo_cancelacion)) ? trim($motivo_cancelacion) : 'Cancelada sin motivo específico.';

        try {
            $citasM = new CitasM();
            
            // 2. Obtener datos de la cita para verificar permisos
            $cita_anterior = $citasM->ObtenerCitaM($id_cita);
            if (!$cita_anterior) {
                return ['error' => 'La cita que intenta cancelar no existe.'];
            }
            
            // --- 3. ¡Comprobaciones de Seguridad por Rol! ---
            $rol_usuario = $_SESSION['rol'];
            $id_usuario = $_SESSION['id'];
            $tiene_permiso = false;

            if ($rol_usuario === 'Administrador') {
                $tiene_permiso = true;
            } elseif ($rol_usuario === 'Doctor' && $cita_anterior['id_doctor'] == $id_usuario) {
                $tiene_permiso = true;
            } elseif ($rol_usuario === 'Secretario' && isset($_SESSION['id_consultorio']) && $cita_anterior['id_consultorio'] == $_SESSION['id_consultorio']) {
                $tiene_permiso = true;
            } elseif ($rol_usuario === 'Paciente' && $cita_anterior['id_paciente'] == $id_usuario) {
                $tiene_permiso = true;
            }

            if (!$tiene_permiso) {
                return ['error' => 'No tiene los permisos necesarios para cancelar esta cita.'];
            }
            
            // 4. Llamar al modelo para ejecutar la cancelación en la base de datos
            $exito = $citasM->CancelarCitaM($id_cita, $motivo_final, $id_usuario);

            if ($exito) {
                // 5. Registrar la acción en el historial de cambios
                $citasM->RegistrarCambiosCita(
                    $id_cita,
                    'Estado',
                    $cita_anterior['estado'], // Valor anterior (ej. 'Pendiente')
                    'Cancelada',              // Valor nuevo
                    $_SESSION["nombre"] . ' ' . $_SESSION["apellido"],
                    $_SESSION["id"],
                    $_SESSION["rol"]
                );

                // Opcional: Registrar un segundo log con el motivo para mayor claridad
                $citasM->RegistrarCambiosCita(
                    $id_cita,
                    'Nota de Cancelación',
                    '',
                    $motivo_final,
                    $_SESSION["nombre"] . ' ' . $_SESSION["apellido"],
                    $_SESSION["id"],
                    $_SESSION["rol"]
                );

                return ['success' => 'Cita cancelada correctamente.'];
            }
            
            // Si el modelo falla por una razón no capturada
            return ['error' => 'Error inesperado al intentar cancelar la cita.'];

        } catch (Exception $e) {
            // Captura excepciones del modelo (ej. 'ya está cancelada') y las devuelve como error.
            return ['error' => $e->getMessage()];
        }
    }


    private function registrarLogCreacionCita($id_nueva_cita, $datosCita) {
        // Obtenemos el nombre del doctor de forma segura
        $nombreDoctor = DoctoresM::ObtenerNombrePorIdM($datosCita['id_doctor']);

        // El nombre del paciente ya viene en $datosCita['nyaP'], lo reutilizamos.
        $nombrePaciente = $datosCita['nyaP'];

        // Construimos el mensaje descriptivo y humano
        $descripcionLog = "Cita creada para '{$nombrePaciente}' con Dr(a). '{$nombreDoctor}'. Motivo: " . $datosCita['motivo'];

        // Usamos la función que ya existe en CitasM para registrar el cambio
        (new CitasM())->RegistrarCambiosCita(
            $id_nueva_cita,
            'Creación de Cita',    // Campo: más descriptivo que "cita"
            'N/A',                 // Valor anterior
            $descripcionLog,       // ¡El nuevo mensaje legible!
            $_SESSION["nombre"] . ' ' . $_SESSION["apellido"],
            $_SESSION["id"],
            $_SESSION["rol"]
        );
    }

    public function CrearCitaAdminC() {
        if (isset($_POST["id_consultorio"]) && $_SESSION['rol'] === 'Administrador') {
            
            $id_consultorio = $_POST["id_consultorio"];
            
            // Verificación básica
            if (empty($id_consultorio)) {
                $_SESSION['mensaje_calendario'] = "Error: Debe seleccionar un consultorio.";
                $_SESSION['tipo_mensaje_calendario'] = "danger";
                echo '<script>window.location = "calendario-admin";</script>';
                exit();
            }

            $datosCita = [
                "id_doctor"             => $_POST["id_doctor"],
                "id_consultorio"        => $id_consultorio,
                "id_paciente"           => $_POST["id_paciente"],
                "nyaP"                  => PacientesM::ObtenerNombrePacienteM($_POST["id_paciente"]),
                "motivo"                => TratamientosM::ObtenerNombreTratamientoM($_POST["id_tratamiento"]),
                "inicio"                => $_POST["fecha"] . ' ' . $_POST["hora_inicio"] . ':00',
                "fin"                   => $_POST["fecha"] . ' ' . $_POST["hora_fin"] . ':00',
                "estado"                => "Pendiente",
                "observaciones"         => "",
                "id_tipo_pago"          => $_POST["id_tipo_pago"] ?? null,
                "id_cobertura_aplicada" => $_POST["id_cobertura_aplicada"] ?? null
            ];
            
            $citasM = new CitasM();
            $resultado = $citasM->CrearCitaM("citas", $datosCita);

            if ($resultado === true) {
                $id_nueva_cita = $citasM->getPDO()->lastInsertId();
                $this->registrarLogCreacionCita($id_nueva_cita, $datosCita);
                $_SESSION['mensaje_calendario'] = "¡Cita creada con éxito como Administrador! 🎉";
                $_SESSION['tipo_mensaje_calendario'] = "success";
            } else {
                $_SESSION['mensaje_calendario'] = "Error: " . $resultado;
                $_SESSION['tipo_mensaje_calendario'] = "danger";
            }

            echo '<script>window.location = "calendario-admin";</script>';
            exit();
        }
    }

   public function CrearCitaSecretarioC() {
        if (isset($_POST["crear_cita_secretario"])) {
            
            // LÓGICA DE DETECCIÓN:
            if ($_SESSION['rol'] === 'Secretario') {
                // El secretario está atado a su consultorio de sesión
                $id_consultorio = $_SESSION['id_consultorio'] ?? null;
            } else {
                // El Admin usa el que eligió en el modal
                $id_consultorio = $_POST["id_consultorio"] ?? null;
            }

            if (empty($id_consultorio)) {
                $_SESSION['mensaje_calendario'] = "Error: Consultorio no detectado.";
                $_SESSION['tipo_mensaje_calendario'] = "danger";
                echo '<script>window.location = "'.$_POST['redirect_url'].'";</script>';
                exit();
            }

            $datosCita = [
                "id_doctor"      => $_POST["id_doctor"],
                "id_consultorio" => $id_consultorio,
                "id_paciente"    => $_POST["id_paciente"],
                "nyaP"           => PacientesM::ObtenerNombrePacienteM($_POST["id_paciente"]),
                "motivo"         => TratamientosM::ObtenerNombreTratamientoM($_POST["id_tratamiento"]),
                "inicio"         => $_POST["fecha"] . ' ' . $_POST["hora_inicio"] . ':00',
                "fin"            => $_POST["fecha"] . ' ' . $_POST["hora_fin"] . ':00',
                "estado"         => "Pendiente",
                "observaciones"  => "",
                "id_tipo_pago"   => $_POST["id_tipo_pago"] ?? null,
                "id_cobertura_aplicada" => $_POST["id_cobertura_aplicada"] ?? null
            ];
            
            $resultado = (new CitasM())->CrearCitaM("citas", $datosCita);

            if ($resultado === true) {
                $_SESSION['mensaje_calendario'] = "¡Cita creada con éxito! 🎉";
                $_SESSION['tipo_mensaje_calendario'] = "success";
            } else {
                $_SESSION['mensaje_calendario'] = "Error: " . $resultado;
                $_SESSION['tipo_mensaje_calendario'] = "danger";
            }

            echo '<script>window.location = "'.$_POST['redirect_url'].'";</script>';
            exit();
        }
    }

    public function ActualizarCitaC() {
        if (isset($_POST["editar_cita_secretario"])) {
            $id_cita = $_POST["id_cita_editar"];
            $citasM = new CitasM();

            // 1. OBTENER DATOS ANTERIORES PARA LOGS
            $cita_anterior = $citasM->ObtenerCitaM($id_cita);
            if (!$cita_anterior) {
                $_SESSION['mensaje_calendario'] = "Error: Cita no encontrada.";
                $_SESSION['tipo_mensaje_calendario'] = "danger";
                echo '<script>window.location = "calendario-citas";</script>';
                exit();
            }

            // 2. RESTRICCIÓN DE SEGURIDAD (Mismo consultorio)
            if ($_SESSION['rol'] === 'Secretario' && $cita_anterior['id_consultorio'] != $_SESSION['id_consultorio']) {
                $_SESSION['mensaje_calendario'] = "Error: Permiso denegado.";
                $_SESSION['tipo_mensaje_calendario'] = "danger";
                echo '<script>window.location = "calendario-citas";</script>';
                exit();
            }

            // 3. NUEVOS DATOS
            $datosNuevos = [
                "id" => $id_cita,
                "id_doctor" => $_POST["id_doctor_editar"],
                "id_paciente" => $_POST["id_paciente_editar"],
                "id_consultorio" => $cita_anterior['id_consultorio'], // El consultorio no cambia en edición
                "nyaP" => PacientesM::ObtenerNombrePacienteM($_POST["id_paciente_editar"]),
                "motivo" => TratamientosM::ObtenerNombreTratamientoM($_POST["id_tratamiento_editar"]),
                "inicio" => $_POST["fecha_editar"] . ' ' . $_POST["hora_inicio_editar"] . ':00',
                "fin" => $_POST["fecha_editar"] . ' ' . $_POST["hora_fin_editar"] . ':00',
                "id_tipo_pago" => $_POST["id_tipo_pago_editar"] ?? null,
                "id_cobertura_aplicada" => $_POST["id_cobertura_aplicada_editar"] ?? null
            ];

            $resultado = $citasM->ActualizarCitaM($datosNuevos);

            if ($resultado === true) {
                $_SESSION['mensaje_calendario'] = "Cita reprogramada con éxito. 🎉";
                $_SESSION['tipo_mensaje_calendario'] = "success";
                
                // --- 📑 RECUPERAMOS TODOS TUS LOGS DETALLADOS ---
                $usuario_log = $_SESSION["nombre"] . ' ' . $_SESSION["apellido"];
                $id_usuario_log = $_SESSION["id"];
                $rol_usuario_log = $_SESSION["rol"];
                
                // LOG: Cobertura
                $cobAnt = $cita_anterior['id_cobertura_aplicada'] ?? $cita_anterior['id_tipo_pago'];
                $cobNue = $datosNuevos['id_cobertura_aplicada'] ?? $datosNuevos['id_tipo_pago'];
                if ($cobAnt != $cobNue) {
                    $nAnt = $citasM->ObtenerNombreCoberturaCitaM($cita_anterior['id_cobertura_aplicada'], $cita_anterior['id_tipo_pago']); 
                    $nNue = $citasM->ObtenerNombreCoberturaCitaM($datosNuevos['id_cobertura_aplicada'], $datosNuevos['id_tipo_pago']); 
                    $citasM->RegistrarCambiosCita($id_cita, 'Cobertura Asignada', $nAnt, $nNue, $usuario_log, $id_usuario_log, $rol_usuario_log);
                }
                
                // LOG: Doctor
                if ($cita_anterior['id_doctor'] != $datosNuevos['id_doctor']) {
                    $dAnt = DoctoresM::ObtenerNombrePorIdM($cita_anterior['id_doctor']);
                    $dNue = DoctoresM::ObtenerNombrePorIdM($datosNuevos['id_doctor']);
                    $citasM->RegistrarCambiosCita($id_cita, 'Doctor Asignado', $dAnt, $dNue, $usuario_log, $id_usuario_log, $rol_usuario_log);
                }
                
                // LOG: Fecha/Hora
                if ($cita_anterior['inicio'] != $datosNuevos['inicio']) {
                    $citasM->RegistrarCambiosCita($id_cita, 'Fecha/Hora', date('d/m/Y H:i', strtotime($cita_anterior['inicio'])), date('d/m/Y H:i', strtotime($datosNuevos['inicio'])), $usuario_log, $id_usuario_log, $rol_usuario_log);
                }

                // LOG: Motivo
                if ($cita_anterior['motivo'] != $datosNuevos['motivo']) {
                    $citasM->RegistrarCambiosCita($id_cita, 'Motivo', $cita_anterior['motivo'], $datosNuevos['motivo'], $usuario_log, $id_usuario_log, $rol_usuario_log);
                }

            } else {
                $_SESSION['mensaje_calendario'] = "Error: " . $resultado;
                $_SESSION['tipo_mensaje_calendario'] = "danger";
            }

            echo '<script>window.location = "calendario-citas";</script>';
            exit();
        }
    }

 static public function ObtenerNombreCoberturaC($idCita) {
        
        // 1. Buscamos los IDs guardados en la cita
        $tablaCitas = "citas";
        $datosCita = CitasM::ObtenerIdsPagoCitaM($tablaCitas, $idCita);

        if ($datosCita) {
            // 2. Pedimos el nombre formateado al modelo usando esos IDs
            // Usamos los índices correctos del array asociativo
            $nombre = CitasM::ObtenerNombreCoberturaCitaM(
                $datosCita['id_cobertura_aplicada'], 
                $datosCita['id_tipo_pago']
            );
            
            return array("success" => true, "nombre" => $nombre);
        } else {
            return array("success" => false, "nombre" => "Cita no encontrada");
        }
    }

    public static function MarcarAusentesAutomaticoC()
{
    return CitasM::MarcarAusentesAutomaticoM();
}
}