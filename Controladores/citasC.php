<?php
require_once __DIR__ . '/../Modelos/CitasM.php';
require_once __DIR__ . '/../Controladores/PacientesC.php';
require_once __DIR__ . '/../Controladores/LogCitasC.php';  // Para usar LogCitasC::RegistrarCambioCitaC()

class CitasC {

    public function VerCitasPacienteC($id_paciente) {
        $citasM = new CitasM();
        return $citasM->VerCitasPacienteM($id_paciente);
    }

    public function CancelarCitaC($id_cita, $motivo_cancelacion) {
        if (!isset($_SESSION['id'])) {
            return ['error' => 'Sesión no válida'];
        }

        if (!is_numeric($id_cita) || $id_cita <= 0) {
            return ['error' => 'ID de cita inválido'];
        }

        $motivo_cancelacion = trim($motivo_cancelacion);
        if (empty($motivo_cancelacion)) {
            return ['error' => 'Debes proporcionar un motivo para cancelar la cita'];
        }

        $cita = $this->ObtenerCitaC($id_cita);
        if (!$cita) {
            return ['error' => 'La cita no existe'];
        }

        if ($cita['estado'] !== 'Pendiente') {
            return ['error' => 'Solo se pueden cancelar citas en estado Pendiente'];
        }

        $usuario_id = $_SESSION['id'];
        $usuario_rol = $_SESSION['rol'];
        $usuario_nombre_completo = $_SESSION["nombre"] . ' ' . $_SESSION["apellido"];

        if (strtolower($usuario_rol) === 'doctor' && $cita['id_doctor'] != $usuario_id) {
            return ['error' => 'No tienes permiso para cancelar esta cita'];
        }

        $citasM = new CitasM();
        $exito = $citasM->CancelarCitaM($id_cita, $motivo_cancelacion, $usuario_id);

        if ($exito) {
            // Registrar cambio en el log de auditoría

            // Cambio de estado
            $citasM->RegistrarCambiosCita(
                $id_cita,
                'estado',
                $cita['estado'],   // valor anterior 'Pendiente'
                'Cancelada',       // valor nuevo
                $usuario_nombre_completo,
                $usuario_id,
                $usuario_rol
            );

            // Registro del motivo de cancelación
            $citasM->RegistrarCambiosCita(
                $id_cita,
                'motivo_cancelacion',
                '',                 // valor anterior vacío
                $motivo_cancelacion,
                $usuario_nombre_completo,
                $usuario_id,
                $usuario_rol
            );

            return ['success' => 'Cita cancelada correctamente'];
        } else {
            return ['error' => 'Error al actualizar la cita en la base de datos'];
        }
    }


    public function ObtenerCitaC($id_cita) {
        if (!is_numeric($id_cita) || $id_cita <= 0) {
            error_log("ID de cita inválido recibido: " . $id_cita);
            return false;
        }

        try {
            $citasM = new CitasM();
            $cita = $citasM->ObtenerCitaM($id_cita);

            if (!$cita) {
                error_log("⚠️ No se encontró la cita con ID: $id_cita");
                return false;
            }

            return $cita;
        } catch (Exception $e) {
            error_log("🚨 Error en ObtenerCitaC: " . $e->getMessage());
            return false;
        }
    }

    public function VerCitasDoctorC($id_doctor) {
        $citasM = new CitasM();
        return $citasM->VerCitasDoctorM($id_doctor); 
    }

public function FinalizarCitaC() {
    if (isset($_POST["id_cita"])) {
        $id_cita = $_POST["id_cita"];
        $motivo = htmlspecialchars($_POST["motivo"] ?? '', ENT_QUOTES, 'UTF-8');
        $observaciones = htmlspecialchars($_POST["observaciones"] ?? '', ENT_QUOTES, 'UTF-8');
        $peso = isset($_POST["peso"]) && is_numeric($_POST["peso"]) ? round($_POST["peso"], 2) : null;

        // Nuevos datos para medicación y tratamientos
        $medicamentos = $_POST["medicamentos"] ?? []; // array de arrays con id_medicamento y datos opcionales
        $tratamientos = $_POST["tratamientos"] ?? []; // array de ids de tratamientos

        $citasM = new CitasM();
        $cita_anterior = $this->ObtenerCitaC($id_cita);

        if (!$cita_anterior) {
            echo '<script>alert("❌ Cita no encontrada.");</script>';
            return;
        }

        if ($_SESSION["rol"] === "doctor" && $cita_anterior["id_doctor"] != $_SESSION["id"]) {
            echo '<script>alert("❌ No tienes permisos para finalizar esta cita.");</script>';
            return;
        }

        // Finalizar cita con motivo, observaciones y peso
        $exito = $citasM->FinalizarCitaM($id_cita, $motivo, $observaciones, $peso);

        if ($exito) {
            // Guardar medicación asociada
            foreach ($medicamentos as $med) {
                // Esperamos al menos un id_medicamento, el resto opcional
                if (!empty($med['id_medicamento'])) {
                    $citasM->AgregarMedicacionACita(
                        $id_cita,
                        $med['id_medicamento'],
                        $med['dosis'] ?? null,
                        $med['frecuencia'] ?? null,
                        $med['fecha_inicio'] ?? null,
                        $med['fecha_fin'] ?? null,
                        $med['observaciones'] ?? null
                    );
                }
            }

            // Guardar tratamientos asociados
            foreach ($tratamientos as $id_tratamiento) {
                if (!empty($id_tratamiento)) {
                    $citasM->AgregarTratamientoACita($id_cita, $id_tratamiento);
                }
            }

            $nombreCompleto = $_SESSION["nombre"] . ' ' . $_SESSION["apellido"];
            $id_usuario = $_SESSION["id"];
            $rol_usuario = $_SESSION["rol"];

            // Registrar cambios de estado y campos
            $citasM->RegistrarCambiosCita(
                $id_cita,
                'estado',
                $cita_anterior["estado"],
                "Completada",
                $nombreCompleto,
                $id_usuario,
                $rol_usuario
            );

            if (!empty($motivo)) {
                $citasM->RegistrarCambiosCita(
                    $id_cita,
                    'motivo_finalizacion',
                    "",
                    $motivo,
                    $nombreCompleto,
                    $id_usuario,
                    $rol_usuario
                );
            }

            if (!empty($observaciones)) {
                $citasM->RegistrarCambiosCita(
                    $id_cita,
                    'observaciones_finalizacion',
                    "",
                    $observaciones,
                    $nombreCompleto,
                    $id_usuario,
                    $rol_usuario
                );
            }

            if (!is_null($peso)) {
                $citasM->RegistrarCambiosCita(
                    $id_cita,
                    'peso',
                    "",
                    $peso,
                    $nombreCompleto,
                    $id_usuario,
                    $rol_usuario
                );
            }

            echo '<script>
                alert("✅ Cita finalizada correctamente.");
                window.location = "citas_doctor";
            </script>';
        } else {
            error_log("Error al finalizar la cita ID $id_cita por el usuario {$_SESSION['id']}.");
            echo '<script>alert("❌ Error al finalizar la cita.");</script>';
        }
    }
}



    public function CrearCitaC() {
        if (isset($_POST["id_paciente"])) {
            $id_paciente    = $_POST["id_paciente"];
            $id_doctor      = $_SESSION["id"];
            $id_consultorio = $_POST["id_consultorio"];
            $fecha          = $_POST["fecha"];
            $inicio         = $fecha . ' ' . $_POST["hora_inicio"] . ':00';
            $fin            = $fecha . ' ' . $_POST["hora_fin"] . ':00';
            $motivo         = $_POST["motivo"] ?? '';
            $observaciones  = $_POST["observaciones"] ?? '';
            $nyaP           = PacientesC::ObtenerNombreCompletoPaciente($id_paciente);

            // 🔒 Validación de rango de fecha permitido (3 meses desde hoy)
            $fechaActual  = date("Y-m-d");
            $fechaLimite  = date("Y-m-d", strtotime("+3 months"));

            if ($fecha < $fechaActual || $fecha > $fechaLimite) {
                echo '<script>alert("❌ La fecha debe estar entre hoy y dentro de los próximos 3 meses.");</script>';
                return false;
            }

            $datosCita = [
                "id_doctor"      => $id_doctor,
                "id_consultorio" => $id_consultorio,
                "id_paciente"    => $id_paciente,
                "nyaP"           => $nyaP,
                "motivo"         => $motivo,
                "inicio"         => $inicio,
                "fin"            => $fin,
                "estado"         => "Pendiente",
                "observaciones"  => $observaciones
            ];

            $citasM = new CitasM();
            $resultado = $citasM->CrearCitaM("citas", $datosCita);

            if ($resultado === true) {
                $pdo = ConexionBD::getInstancia();
                $id_cita = $pdo->lastInsertId();

                $citasM->RegistrarCambiosCita(
                    $id_cita,
                    'cita',
                    '',
                    json_encode($datosCita),
                    $_SESSION["nombre"] . ' ' . $_SESSION["apellido"],
                    $_SESSION["id"],
                    $_SESSION["rol"]
                );

                return true;
            } else {
                return $resultado;
            }
        }
    }
    
    public static function ObtenerMedicamentosDisponibles() {
    $pdo = ConexionBD::getInstancia()->prepare("SELECT id, nombre, es_cronico FROM medicamentos ORDER BY nombre ASC");
    $pdo->execute();
    return $pdo->fetchAll(PDO::FETCH_ASSOC);
}

public static function ObtenerTratamientosDelDoctor($id_doctor) {
    $pdo = ConexionBD::getInstancia()->prepare("
        SELECT t.id, t.nombre 
        FROM tratamientos t 
        INNER JOIN doctor_tratamiento dt ON dt.id_tratamiento = t.id 
        WHERE dt.id_doctor = :id
        ORDER BY t.nombre ASC
    ");
    $pdo->bindParam(":id", $id_doctor, PDO::PARAM_INT);
    $pdo->execute();
    return $pdo->fetchAll(PDO::FETCH_ASSOC);
}
public static function AsociarMedicamentosATratamientos($id_cita, $medicamentos = [], $tratamientos = []) {
    $pdo = ConexionBD::getInstancia();

    try {
        $pdo->beginTransaction();

        // Medicamentos
        if (!empty($medicamentos)) {
            $stmtMed = $pdo->prepare("INSERT INTO cita_medicamento (id_cita, id_medicamento) VALUES (:id_cita, :id_medicamento)");
            foreach ($medicamentos as $med) {
                $stmtMed->execute([
                    ':id_cita' => $id_cita,
                    ':id_medicamento' => $med
                ]);
            }
        }

        // Tratamientos
        if (!empty($tratamientos)) {
            $stmtTrat = $pdo->prepare("INSERT INTO cita_tratamiento (id_cita, id_tratamiento) VALUES (:id_cita, :id_tratamiento)");
            foreach ($tratamientos as $trat) {
                $stmtTrat->execute([
                    ':id_cita' => $id_cita,
                    ':id_tratamiento' => $trat
                ]);
            }
        }

        $pdo->commit();
        return ['success' => 'Medicamentos y tratamientos registrados correctamente.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['error' => 'Error al asociar medicamentos o tratamientos: ' . $e->getMessage()];
    }
}

}
