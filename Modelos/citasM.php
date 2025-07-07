<?php
require_once ROOT_PATH . '/Modelos/HorariosConsultoriosM.php';

require_once "ConexionBD.php";

class CitasM {
    private $pdo;

    public function __construct() {
        $this->pdo = ConexionBD::getInstancia();
    }

    // Obtener todas las citas de un paciente con info de doctor, tratamientos y consultorio
    public function VerCitasPacienteM($id_paciente) {
        try {
            $sql = "SELECT c.*, 
                           CONCAT(d.nombre, ' ', d.apellido) AS nombre_doctor,
                           GROUP_CONCAT(t.nombre SEPARATOR ', ') AS tratamientos,
                           co.nombre AS nombre_consultorio
                    FROM citas c
                    LEFT JOIN doctores d ON c.id_doctor = d.id
                    LEFT JOIN doctor_tratamiento dt ON d.id = dt.id_doctor
                    LEFT JOIN tratamientos t ON dt.id_tratamiento = t.id
                    LEFT JOIN consultorios co ON c.id_consultorio = co.id
                    WHERE c.id_paciente = :id_paciente
                    GROUP BY c.id
                    ORDER BY c.inicio DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":id_paciente", $id_paciente, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en VerCitasPacienteM: " . $e->getMessage());
            return [];
        }
    }

    // Crear una nueva cita médica
    public function CrearCitaM($tablaBD, $datosC) {
        try {
            if (
                !$this->VerificarDisponibilidadM(
                    $datosC["id_doctor"], 
                    $datosC["id_consultorio"], 
                    $datosC["inicio"], 
                    $datosC["fin"]
                )
            ) {
                return "El horario seleccionado ya está ocupado por otra cita.";
            }
    
            if (
                !$this->HorarioClinicaYDoctorDisponible(
                    $datosC["id_doctor"],
                    $datosC["id_consultorio"],
                    $datosC["inicio"],
                    $datosC["fin"]
                )
            ) {
                return "La cita está fuera del horario permitido del consultorio o del doctor.";
            }
    
            $sql = "INSERT INTO `{$tablaBD}` 
                    (`id_doctor`, `id_consultorio`, `id_paciente`, `nyaP`, `motivo`, 
                     `inicio`, `fin`, `estado`, `observaciones`, `creado_en`)
                    VALUES (:id_doctor, :id_consultorio, :id_paciente, :nyaP, :motivo, 
                            :inicio, :fin, :estado, :observaciones, NOW())";
    
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":id_doctor", $datosC["id_doctor"], PDO::PARAM_INT);
            $stmt->bindParam(":id_consultorio", $datosC["id_consultorio"], PDO::PARAM_INT);
            $stmt->bindParam(":id_paciente", $datosC["id_paciente"], PDO::PARAM_INT);
            $stmt->bindParam(":nyaP", $datosC["nyaP"], PDO::PARAM_STR);
            $stmt->bindParam(":motivo", $datosC["motivo"], PDO::PARAM_STR);
            $stmt->bindParam(":inicio", $datosC["inicio"], PDO::PARAM_STR);
            $stmt->bindParam(":fin", $datosC["fin"], PDO::PARAM_STR);
            $stmt->bindParam(":estado", $datosC["estado"], PDO::PARAM_STR);
            $stmt->bindParam(":observaciones", $datosC["observaciones"], PDO::PARAM_STR);
    
            if ($stmt->execute()) {
                return true;
            } else {
                return "No se pudo guardar la cita. Intente nuevamente.";
            }
    
        } catch (PDOException $e) {
            error_log("Error en CrearCitaM: " . $e->getMessage());
            return "Error interno del sistema al guardar la cita.";
        }
    }
    

    // Verificar si el horario está disponible
    public function VerificarDisponibilidadM($id_doctor, $id_consultorio, $inicio, $fin) {
        try {
            $sql = "SELECT COUNT(*) as count FROM citas 
                    WHERE ((id_doctor = :id_doctor OR id_consultorio = :id_consultorio)
                    AND ((inicio < :fin AND fin > :inicio))
                    AND estado NOT IN ('Cancelada', 'Completada'))";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
            $stmt->bindParam(":id_consultorio", $id_consultorio, PDO::PARAM_INT);
            $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
            $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);
            $stmt->execute();

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado['count'] == 0;
        } catch (PDOException $e) {
            error_log("Error en VerificarDisponibilidadM: " . $e->getMessage());
            return false;
        }
    }

    // Actualizar el estado de una cita
    public function ActualizarEstadoCitaM($id_cita, $estado) {
        try {
            $sql = "UPDATE citas SET estado = :estado WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id_cita, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en ActualizarEstadoCitaM: " . $e->getMessage());
            return false;
        }
    }

    // Obtener detalles de una cita específica
    public function ObtenerCitaM($id_cita) {
        try {
            $sql = "SELECT 
                        c.id,
                        c.id_doctor,
                        c.especialidad,
                        c.id_consultorio,
                        c.id_paciente,
                        c.nyaP,
                        c.motivo,
                        c.inicio,
                        c.fin,
                        c.estado,
                        c.creado_en,
                        c.observaciones,
                        CONCAT(d.nombre, ' ', d.apellido) AS nombre_doctor,
                        co.nombre AS nombre_consultorio,
                        CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo_paciente
                    FROM citas c
                    LEFT JOIN doctores d ON c.id_doctor = d.id
                    LEFT JOIN consultorios co ON c.id_consultorio = co.id
                    LEFT JOIN pacientes p ON c.id_paciente = p.id
                    WHERE c.id = :id
                    LIMIT 1";
    
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":id", $id_cita, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                error_log("Error al ejecutar consulta para cita ID: $id_cita");
                return false;
            }
    
            $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$cita) {
                error_log("No se encontró cita con ID: $id_cita");
                return false;
            }
    
            // Formatear fechas para mejor visualización
            $cita['inicio_formateado'] = date('d/m/Y H:i', strtotime($cita['inicio']));
            $cita['fin_formateado'] = date('d/m/Y H:i', strtotime($cita['fin']));
            
            return $cita;
    
        } catch (PDOException $e) {
            error_log("Error en ObtenerCitaM: " . $e->getMessage());
            return false;
        }
    }

    // Obtener nombre completo del paciente
    public function obtenerNombrePaciente($id_paciente) {
        try {
            $sql = "SELECT CONCAT(nombre, ' ', apellido) as nombre FROM pacientes WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":id", $id_paciente, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado['nombre'] ?? '';
        } catch (PDOException $e) {
            error_log("Error en obtenerNombrePaciente: " . $e->getMessage());
            return '';
        }
    }

    // Cancelar una cita
   // Cancelar una cita con motivo de cancelación
public function CancelarCitaM($id_cita, $motivo_cancelacion, $id_doctor) {
    try {
        // Primero verificar sin transacción
        $sqlVerificar = "SELECT id FROM citas WHERE id = :id AND id_doctor = :id_doctor";
        $stmt = $this->pdo->prepare($sqlVerificar);
        $stmt->bindParam(":id", $id_cita, PDO::PARAM_INT);
        $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
        
        if (!$stmt->execute() || $stmt->rowCount() === 0) {
            return false;
        }

        // Iniciar transacción solo para la actualización
        $this->pdo->beginTransaction();

        $sqlActualizar = "UPDATE citas SET 
                        estado = 'Cancelada',
                        motivo = CONCAT('Cancelación: ', :motivo),
                        observaciones = CONCAT(IFNULL(observaciones, ''), 
                                        '\nMotivo cancelación: ', :motivo)
                      WHERE id = :id";

        $stmt = $this->pdo->prepare($sqlActualizar);
        $stmt->bindParam(":motivo", $motivo_cancelacion, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id_cita, PDO::PARAM_INT);
        
        $resultado = $stmt->execute();
        
        if ($resultado) {
            $this->pdo->commit();
            return true;
        } else {
            $this->pdo->rollBack();
            return false;
        }

    } catch (PDOException $e) {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        error_log("Error en CancelarCitaM: " . $e->getMessage());
        return false;
    }
}


    // Obtener todas las citas de un doctor
    public function VerCitasDoctorM($id_doctor) {
        try {
            // Selección de todas las citas, sin importar el estado
            $sql = "SELECT c.*, 
                           p.nombre as nombre_paciente,
                           p.apellido as apellido_paciente,
                           co.nombre as nombre_consultorio
                    FROM citas c
                    LEFT JOIN pacientes p ON c.id_paciente = p.id
                    LEFT JOIN consultorios co ON c.id_consultorio = co.id
                    WHERE c.id_doctor = :id_doctor
                    ORDER BY c.inicio DESC";
    
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
            $stmt->execute();
    
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en VerCitasDoctorM: " . $e->getMessage());
            return [];
        }
    }
    

    public function FinalizarCitaM($id_cita, $motivo, $observaciones, $peso = null) {
    try {
        // Verificar si la cita existe
        $sqlCheck = "SELECT estado FROM citas WHERE id = :id";
        $stmtCheck = $this->pdo->prepare($sqlCheck);
        $stmtCheck->bindParam(":id", $id_cita, PDO::PARAM_INT);
        $stmtCheck->execute();

        $cita = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$cita) {
            error_log("FinalizarCitaM: No se encontró la cita con ID $id_cita.");
            return "No se encontró la cita especificada.";
        }

        if ($cita['estado'] === 'Cancelada') {
            return "No se puede finalizar una cita cancelada.";
        }

        if ($cita['estado'] === 'Completada') {
            return "Esta cita ya ha sido finalizada anteriormente.";
        }

        // Actualizar estado, observaciones, motivo y peso
        $sql = "UPDATE citas 
                SET estado = 'Completada', 
                    motivo = :motivo,
                    observaciones = :observaciones,
                    peso = :peso
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":motivo", $motivo, PDO::PARAM_STR);
        $stmt->bindParam(":observaciones", $observaciones, PDO::PARAM_STR);
        $stmt->bindParam(":peso", $peso, PDO::PARAM_STR); // Null si no se provee
        $stmt->bindParam(":id", $id_cita, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        } else {
            error_log("FinalizarCitaM: Fallo al ejecutar el UPDATE para la cita ID $id_cita.");
            return "No se pudo finalizar la cita. Intente nuevamente.";
        }
    } catch (PDOException $e) {
        error_log("Error en FinalizarCitaM: " . $e->getMessage());
        return "Ocurrió un error al finalizar la cita.";
    }
}

    
public function RegistrarCambiosCita($id_cita, $campo, $valor_anterior, $valor_nuevo, $usuario, $id_usuario, $rol_usuario) {
    try {
        $sql = "INSERT INTO historial_cambios_citas 
                (id_cita, fecha_cambio, campo_modificado, valor_anterior, valor_nuevo, usuario_modifico, id_usuario, rol_usuario)
                VALUES (:id_cita, NOW(), :campo, :anterior, :nuevo, :usuario, :id_usuario, :rol)";
        $stmt = $this->pdo->prepare($sql);

        $stmt->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
        $stmt->bindParam(":campo", $campo, PDO::PARAM_STR);
        $stmt->bindParam(":anterior", $valor_anterior, PDO::PARAM_STR);
        $stmt->bindParam(":nuevo", $valor_nuevo, PDO::PARAM_STR);
        $stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);
        $stmt->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":rol", $rol_usuario, PDO::PARAM_STR);

        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error en RegistrarCambiosCita: " . $e->getMessage());
        return false;
    }
}




public function AgregarMedicacionACita($id_cita, $id_medicamento, $dosis = null, $frecuencia = null, $fecha_inicio = null, $fecha_fin = null, $observaciones = null) {
    try {
        $sql = "INSERT INTO medicacion_cita (id_cita, id_medicamento, dosis, frecuencia, fecha_inicio, fecha_fin, observaciones)
                VALUES (:id_cita, :id_medicamento, :dosis, :frecuencia, :fecha_inicio, :fecha_fin, :observaciones)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_cita', $id_cita, PDO::PARAM_INT);
        $stmt->bindParam(':id_medicamento', $id_medicamento, PDO::PARAM_INT);
        $stmt->bindParam(':dosis', $dosis);
        $stmt->bindParam(':frecuencia', $frecuencia);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->bindParam(':observaciones', $observaciones);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error en AgregarMedicacionACita: " . $e->getMessage());
        return false;
    }
}

public function AgregarTratamientoACita($id_cita, $id_tratamiento) {
    try {
        $sql = "INSERT INTO cita_tratamientos (id_cita, id_tratamiento) VALUES (:id_cita, :id_tratamiento)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_cita', $id_cita, PDO::PARAM_INT);
        $stmt->bindParam(':id_tratamiento', $id_tratamiento, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error en AgregarTratamientoACita: " . $e->getMessage());
        return false;
    }
}




public function HorarioClinicaYDoctorDisponible($id_doctor, $id_consultorio, $inicio, $fin) {
    try {
        $fecha = date('Y-m-d', strtotime($inicio));
        $hora_inicio = date('H:i:s', strtotime($inicio));
        $hora_fin    = date('H:i:s', strtotime($fin));

        // Obtener el día de la semana con 1 = lunes, ..., 7 = domingo
        $dia_semana = date('w', strtotime($fecha));
        $dia_semana = ($dia_semana == 0) ? 7 : $dia_semana;

        // 1. Verificar si el consultorio está habilitado ese día y horario
        $sql_consultorio = "SELECT 1 FROM horarios_consultorios 
                            WHERE id_consultorio = :id_consultorio 
                              AND dia_semana = :dia_semana 
                              AND :hora_inicio >= hora_apertura
                              AND :hora_fin <= hora_cierre";
        $stmt = $this->pdo->prepare($sql_consultorio);
        $stmt->bindParam(":id_consultorio", $id_consultorio, PDO::PARAM_INT);
        $stmt->bindParam(":dia_semana", $dia_semana, PDO::PARAM_INT);
        $stmt->bindParam(":hora_inicio", $hora_inicio, PDO::PARAM_STR);
        $stmt->bindParam(":hora_fin", $hora_fin, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return false; // Consultorio no disponible ese día u horario
        }

        // 2. Verificar si el doctor trabaja ese día y horario en ese consultorio
        $sql_doctor = "SELECT 1 FROM horarios_doctores 
                       WHERE id_doctor = :id_doctor 
                         AND id_consultorio = :id_consultorio
                         AND dia_semana = :dia_semana
                         AND :hora_inicio >= hora_inicio 
                         AND :hora_fin <= hora_fin";
        $stmt = $this->pdo->prepare($sql_doctor);
        $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
        $stmt->bindParam(":id_consultorio", $id_consultorio, PDO::PARAM_INT);
        $stmt->bindParam(":dia_semana", $dia_semana, PDO::PARAM_INT);
        $stmt->bindParam(":hora_inicio", $hora_inicio, PDO::PARAM_STR);
        $stmt->bindParam(":hora_fin", $hora_fin, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return false; // Doctor no trabaja en ese horario
        }

        // 3. Verificar que el doctor no tenga otra cita en ese horario
        $sql_citas = "SELECT 1 FROM citas 
                      WHERE id_doctor = :id_doctor 
                        AND estado IN ('Pendiente', 'Confirmada') 
                        AND (
                            (inicio < :fin AND fin > :inicio)
                        )";
        $stmt = $this->pdo->prepare($sql_citas);
        $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
        $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
        $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return false; // Ya tiene una cita en ese horario
        }

        // Si pasó todas las validaciones, está disponible
        return true;

    } catch (PDOException $e) {
        error_log("❌ Error en HorarioClinicaYDoctorDisponible: " . $e->getMessage());
        return false;
    }
}


    

    public static function ObtenerHistorialCambiosM() {
    $pdo = ConexionBD::cBD()->prepare("SELECT * FROM log_citas ORDER BY fecha_cambio DESC");
    $pdo->execute();
    return $pdo->fetchAll(PDO::FETCH_ASSOC);
}
public static function ObtenerHistorialPorCitaM($id_cita) {
    $pdo = ConexionBD::cBD()->prepare("SELECT * FROM log_citas WHERE id_cita = :id_cita ORDER BY fecha_cambio DESC");
    $pdo->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
    $pdo->execute();
    return $pdo->fetchAll(PDO::FETCH_ASSOC);
}

public function getPDO() {
    return $this->pdo;
}
public static function ObtenerHorariosPorConsultorio($id_consultorio) {
    $pdo = ConexionBD::getInstancia()->prepare("SELECT dia, hora_inicio, hora_fin FROM horarios_consultorios WHERE id_consultorio = :id");
    $pdo->bindParam(":id", $id_consultorio, PDO::PARAM_INT);
    $pdo->execute();
    return $pdo->fetchAll(PDO::FETCH_ASSOC);
}

public static function ObtenerHorariosPorDoctor($id_doctor) {
    $pdo = ConexionBD::getInstancia()->prepare("
        SELECT dia_semana, id_consultorio, hora_inicio, hora_fin
        FROM horario_doctores
        WHERE id_doctor = :id
    ");
    $pdo->bindParam(":id", $id_doctor, PDO::PARAM_INT);
    $pdo->execute();
    return $pdo->fetchAll(PDO::FETCH_ASSOC);
}


    
}
