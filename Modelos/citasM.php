<?php


class CitasM {
    private $pdo;

    public function __construct() {
        $this->pdo = ConexionBD::getInstancia();
    }
    
    public static function VerTodasLasCitasM() {
     $sql = "SELECT 
                   c.id, 
                   c.inicio, 
                   c.fin, 
                   c.estado, 
                   c.nyaP, 
                   c.motivo,
                   c.id_doctor,
                   c.id_paciente,
                   c.id_consultorio, -- ¡CAMPO CLAVE AÑADIDO!
                   t.id as id_tratamiento, -- ¡ID DEL TRATAMIENTO AÑADIDO!
                   d.nombre AS doctor_nombre, 
                   d.apellido AS doctor_apellido,
                   co.nombre AS nombre_consultorio
             FROM citas c
             LEFT JOIN doctores d ON c.id_doctor = d.id
             LEFT JOIN consultorios co ON c.id_consultorio = co.id
             LEFT JOIN tratamientos t ON c.motivo = t.nombre -- Unimos por el nombre para obtener el ID
             ";
        
        $params = [];
        
        if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Secretario' && isset($_SESSION['id_consultorio'])) {
            $sql .= " WHERE c.id_consultorio = :id_consultorio";
            $params[':id_consultorio'] = $_SESSION['id_consultorio'];
        }

        $stmt = ConexionBD::getInstancia()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   public function VerCitasPacienteM($id_paciente) {
    try {
        $sql = "SELECT 
                    c.*,  -- Selecciona TODOS los campos de la tabla 'citas'
                    CONCAT(d.nombre, ' ', d.apellido) AS nombre_doctor, 
                    co.nombre AS nombre_consultorio,
                    -- [CRÍTICO] Une los tratamientos específicos de ESTA cita, no todos los del doctor.
                    (SELECT GROUP_CONCAT(t.nombre SEPARATOR ', ') 
                     FROM cita_tratamiento ct 
                     JOIN tratamientos t ON ct.id_tratamiento = t.id 
                     WHERE ct.id_cita = c.id) AS tratamientos
                  FROM 
                    citas c 
                  LEFT JOIN 
                    doctores d ON c.id_doctor = d.id 
                  LEFT JOIN 
                    consultorios co ON c.id_consultorio = co.id 
                  WHERE 
                    c.id_paciente = :id_paciente 
                  ORDER BY 
                    c.inicio DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":id_paciente", $id_paciente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en VerCitasPacienteM: " . $e->getMessage());
        return [];
    }
}


     public static function ObtenerCitasParaAdminM($filtros) {
         try {
             $sql = "SELECT 
    c.id,
    c.inicio,
    c.fin,
    CASE 
        WHEN c.estado IS NULL OR c.estado = '' THEN 'Pendiente'
        ELSE c.estado
    END AS estado,
    c.nyaP,
    c.motivo,
    c.id_doctor,
    c.id_paciente,
    c.id_consultorio,
    t.id AS id_tratamiento,
    d.nombre AS doctor_nombre,
    d.apellido AS doctor_apellido,
    co.nombre AS nombre_consultorio
FROM citas c
LEFT JOIN doctores d ON c.id_doctor = d.id
LEFT JOIN consultorios co ON c.id_consultorio = co.id
LEFT JOIN tratamientos t ON c.motivo = t.nombre
WHERE c.inicio BETWEEN :start AND :end
";
             
             $params = [
                 ':start' => $filtros['start'],
                 ':end' => $filtros['end']
             ];
             
             if (!empty($filtros['id_consultorio'])) {
                 $sql .= " AND c.id_consultorio = :id_consultorio";
                 $params[':id_consultorio'] = $filtros['id_consultorio'];
             }

             if (!empty($filtros['id_doctor'])) {
                 $sql .= " AND c.id_doctor = :id_doctor";
                 $params[':id_doctor'] = $filtros['id_doctor'];
             }
             
             $stmt = ConexionBD::getInstancia()->prepare($sql);
             $stmt->execute($params);
             return $stmt->fetchAll(PDO::FETCH_ASSOC);
         } catch (PDOException $e) {
             // Si hay un error de SQL, lo registramos y devolvemos un array vacío
             // para que el calendario no se rompa.
             error_log("Error en ObtenerCitasParaAdminM: " . $e->getMessage());
             return [];
         }
     }


     public function ObtenerBloqueoM($id_bloqueo) {
     try {
         $stmt = $this->pdo->prepare("
             SELECT 
                 id,
                 id_doctor,
                 fecha_inicio,
                 fecha_fin,
                 motivo
             FROM bloqueos
             WHERE id = :id
         ");
         $stmt->bindParam(':id', $id_bloqueo, PDO::PARAM_INT);
         $stmt->execute();
         return $stmt->fetch(PDO::FETCH_ASSOC);
     } catch (PDOException $e) {
         error_log("Error en ObtenerBloqueoM: " . $e->getMessage());
         return false;
     }
}



     public function ValidarTurnoDoctorM($id_doctor, $inicio, $fin) {
         $dia_semana = date('N', strtotime($inicio));
         $hora_inicio_cita = date('H:i:s', strtotime($inicio));
         $hora_fin_cita = date('H:i:s', strtotime($fin));

         $sql = "SELECT COUNT(*) FROM horarios_doctores 
                 WHERE id_doctor = :id_doctor 
                   AND dia_semana = :dia_semana
                   AND :hora_inicio_cita >= hora_inicio
                   AND :hora_fin_cita <= hora_fin";
         
         $stmt = $this->pdo->prepare($sql);
         $stmt->execute([
             ':id_doctor' => $id_doctor,
             ':dia_semana' => $dia_semana,
             ':hora_inicio_cita' => $hora_inicio_cita,
             ':hora_fin_cita' => $hora_fin_cita
         ]);
         return $stmt->fetchColumn() > 0;
     }



 public function ActualizarCitaM($datosC) {
    try {
        // --- 1. VALIDACIÓN DE FECHA (Única y Correcta) ---
        if (isset($datosC["inicio"])) {
            $fecha_hora_cita_str = $datosC["inicio"];
            try {
                $fecha_hora_cita = new DateTime($fecha_hora_cita_str);
                $ahora = new DateTime();
                
                // Compara la fecha de la cita con la fecha y hora actual.
                if ($fecha_hora_cita < $ahora) {
                    // Si la fecha es pasada, DETIENE la función y devuelve el mensaje de error.
                    return "No se puede reprogramar una cita a una fecha u hora que ya ha pasado.";
                }
            } catch (Exception $e) {
                return "El formato de fecha para la reprogramación no es válido.";
            }
        }
        // Nota: Se eliminó el bloque de validación duplicado "INICIO DE LA VALIDACIÓN DE FECHA UNIVERSAL PARA ACTUALIZACIÓN".

        // --- 2. VALIDACIONES DE DISPONIBILIDAD Y TURNO ---
        if (!$this->VerificarDisponibilidadM($datosC["id_doctor"], $datosC["id_consultorio"], $datosC["inicio"], $datosC["fin"], $datosC['id'])) {
            return "Error: Conflicto de horario. Ya existe otra cita en ese lapso.";
        }
        if (!$this->ValidarTurnoDoctorM($datosC["id_doctor"], $datosC["inicio"], $datosC["fin"])) {
            return "Error: El horario seleccionado está fuera del turno del doctor.";
        }

        // --- 3. ACTUALIZACIÓN (UPDATE) CON CAMPOS DE COBERTURA ---
        $sql = "UPDATE citas SET 
                    id_doctor = :id_doctor, 
                    id_paciente = :id_paciente, 
                    nyaP = :nyaP, 
                    motivo = :motivo, 
                    inicio = :inicio, 
                    fin = :fin,
                    -- 🆕 CAMPOS DE COBERTURA AÑADIDOS
                    id_tipo_pago = :id_tipo_pago,
                    id_cobertura_aplicada = :id_cobertura_aplicada
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        
        // Asignación de parámetros
        $stmt->bindParam(':id_doctor', $datosC['id_doctor'], PDO::PARAM_INT);
        $stmt->bindParam(':id_paciente', $datosC['id_paciente'], PDO::PARAM_INT);
        $stmt->bindParam(':nyaP', $datosC['nyaP'], PDO::PARAM_STR);
        $stmt->bindParam(':motivo', $datosC['motivo'], PDO::PARAM_STR);
        $stmt->bindParam(':inicio', $datosC['inicio'], PDO::PARAM_STR);
        $stmt->bindParam(':fin', $datosC['fin'], PDO::PARAM_STR);
        
        // 🆕 NUEVOS BINDINGS PARA COBERTURA (Asegúrate de que estos campos existan en $datosC)
        $stmt->bindParam(':id_tipo_pago', $datosC['id_tipo_pago'], PDO::PARAM_INT);
        $stmt->bindParam(':id_cobertura_aplicada', $datosC['id_cobertura_aplicada'], PDO::PARAM_INT);
        
        $stmt->bindParam(':id', $datosC['id'], PDO::PARAM_INT);
        
        $stmt->execute();
        
        return true; 
        
    } catch (PDOException $e) {
        error_log("Error en ActualizarCitaM: " . $e->getMessage());
        return "Error de base de datos al actualizar. Por favor, contacte a soporte.";
    }
}

    public function CrearCitaM($tablaBD, $datosC) {
    try {
        // --- VALIDACIONES DE FECHA, DISPONIBILIDAD Y TURNO (Sin Cambios) ---
        if (isset($datosC["inicio"])) {
            $fecha_hora_cita_str = $datosC["inicio"];
            try {
                $fecha_hora_cita = new DateTime($fecha_hora_cita_str);
                $ahora = new DateTime();
                if ($fecha_hora_cita < $ahora) {
                    return "No se puede agendar una cita en una fecha u hora que ya ha pasado.";
                }
            } catch (Exception $e) {
                return "El formato de fecha proporcionado no es válido.";
            }
        }
        if (!$this->VerificarDisponibilidadM($datosC["id_doctor"], $datosC["id_consultorio"], $datosC["inicio"], $datosC["fin"])) {
            return "El horario seleccionado ya está ocupado por otra cita.";
        }
        if (!$this->ValidarTurnoDoctorM($datosC["id_doctor"], $datosC["inicio"], $datosC["fin"])) {
            return "La cita está fuera del horario de trabajo del doctor para ese día.";
        }

        // Si todas las validaciones pasan, se procede con el INSERT.
        $sql = "INSERT INTO `{$tablaBD}` (`id_doctor`, `id_consultorio`, `id_paciente`, `nyaP`, `motivo`, `inicio`, `fin`, `estado`, `observaciones`, `creado_en`,
                                          `id_tipo_pago`, `id_cobertura_aplicada`) 
                VALUES (:id_doctor, :id_consultorio, :id_paciente, :nyaP, :motivo, :inicio, :fin, :estado, :observaciones, NOW(),
                        :id_tipo_pago, :id_cobertura_aplicada)"; // 🆕 Columnas añadidas
        
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
        
        // 🆕 NUEVOS BINDINGS PARA COBERTURA
        $stmt->bindParam(":id_tipo_pago", $datosC["id_tipo_pago"], PDO::PARAM_INT);
        $stmt->bindParam(":id_cobertura_aplicada", $datosC["id_cobertura_aplicada"], PDO::PARAM_INT);

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
    
    public function VerificarDisponibilidadM($id_doctor, $id_consultorio, $inicio, $fin, $id_cita_a_excluir = 0) {
         try {
             $sql = "SELECT COUNT(*) as count FROM citas 
                     WHERE 
                         ((id_doctor = :id_doctor OR id_consultorio = :id_consultorio) 
                         AND (inicio < :fin AND fin > :inicio) 
                         AND estado NOT IN ('Cancelada', 'Completada'))";
             
             if ($id_cita_a_excluir > 0) {
                 $sql .= " AND id != :id_cita_a_excluir";
             }

             $stmt = $this->pdo->prepare($sql);
             $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
             $stmt->bindParam(":id_consultorio", $id_consultorio, PDO::PARAM_INT);
             $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
             $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);
             
             if ($id_cita_a_excluir > 0) {
                  $stmt->bindParam(":id_cita_a_excluir", $id_cita_a_excluir, PDO::PARAM_INT);
             }
             
             $stmt->execute();
             $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
             return $resultado['count'] == 0;
         } catch (PDOException $e) {
             error_log("Error en VerificarDisponibilidadM: " . $e->getMessage());
             return false;
         }
    }

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

    public function ObtenerCitaM($id_cita) {
         try {
             // --- INICIO DE LA MODIFICACIÓN ---
             $sql = "SELECT c.*, 
                              t.id AS id_tratamiento, -- ¡CLAVE! Obtenemos el ID del tratamiento
                              CONCAT(d.nombre, ' ', d.apellido) AS nombre_doctor, 
                              co.nombre AS nombre_consultorio, 
                              CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo_paciente 
                       FROM citas c 
                       LEFT JOIN doctores d ON c.id_doctor = d.id 
                       LEFT JOIN consultorios co ON c.id_consultorio = co.id 
                       LEFT JOIN pacientes p ON c.id_paciente = p.id 
                       LEFT JOIN tratamientos t ON c.motivo = t.nombre -- Unimos por el nombre para obtener el ID
                       WHERE c.id = :id 
                       LIMIT 1";
             // --- FIN DE LA MODIFICACIÓN ---

             $stmt = $this->pdo->prepare($sql);
             $stmt->bindParam(":id", $id_cita, PDO::PARAM_INT);
             if (!$stmt->execute()) { error_log("Error al ejecutar consulta para cita ID: $id_cita"); return false; }
             $cita = $stmt->fetch(PDO::FETCH_ASSOC);
             if (!$cita) { error_log("No se encontró cita con ID: $id_cita"); return false; }
             $cita['inicio_formateado'] = date('d/m/Y H:i', strtotime($cita['inicio']));
             $cita['fin_formateado'] = date('d/m/Y H:i', strtotime($cita['fin']));
             return $cita;
         } catch (PDOException $e) {
             error_log("Error en ObtenerCitaM: ". $e->getMessage());
             return false;
         }
    }

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

    public function CancelarCitaM($id_cita, $motivo_cancelacion, $id_doctor_o_admin) {
         try {
             $this->pdo->beginTransaction();
             $sqlVerificar = "SELECT id, estado, motivo, observaciones FROM citas WHERE id = :id FOR UPDATE";
             $stmtVerificar = $this->pdo->prepare($sqlVerificar);
             $stmtVerificar->bindParam(":id", $id_cita, PDO::PARAM_INT);
             $stmtVerificar->execute();
             $cita = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
             if (!$cita) { $this->pdo->rollBack(); throw new Exception('La cita no existe.'); }
             if ($cita['estado'] !== 'Pendiente') { $this->pdo->rollBack(); throw new Exception('Solo se pueden cancelar citas que estén en estado "Pendiente".'); }
             $nuevo_motivo = $cita['motivo'] . "\nCancelación: " . $motivo_cancelacion;
             $nuevas_observaciones = $cita['observaciones'] . "\nMotivo cancelación: " . $motivo_cancelacion;
             $sqlActualizar = "UPDATE citas SET estado = 'Cancelada', motivo = :motivo, observaciones = :observaciones WHERE id = :id";
             $stmtActualizar = $this->pdo->prepare($sqlActualizar);
             $stmtActualizar->bindParam(":motivo", $nuevo_motivo, PDO::PARAM_STR);
             $stmtActualizar->bindParam(":observaciones", $nuevas_observaciones, PDO::PARAM_STR);
             $stmtActualizar->bindParam(":id", $id_cita, PDO::PARAM_INT);
             if ($stmtActualizar->execute()) {
                 $this->pdo->commit();
                 return true;
             } else {
                 $this->pdo->rollBack();
                 throw new Exception('No se pudo actualizar el estado de la cita en la base de datos.');
             }
         } catch (Exception $e) {
             if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
             error_log("Error en CancelarCitaM: " . $e->getMessage());
             throw $e;
         }
    }


    public static function citasSolapadas($id_doctor, $inicio, $fin) {
        try {
            // IMPORTANTE: 
            // Usamos 'Conexion::conectar()' para ser consistentes con tus
            // archivos 'BloqueosC.php' y 'BloqueosM.php'.
            // Si tu clase CitasM usa 'ConexionBD::getInstancia()', cambia la línea de abajo.
        $pdo = ConexionBD::getInstancia();  
            
            $sql = "SELECT id, inicio, fin, nyaP FROM citas 
                    WHERE id_doctor = :id_doctor 
                    AND estado IN ('Pendiente', 'Confirmada')
                    AND (inicio < :fin AND fin > :inicio)"; // Lógica de superposición
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_doctor' => $id_doctor,
                ':inicio' => $inicio,
                ':fin' => $fin
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en CitasM::citasSolapadas: " . $e->getMessage());
            return []; // Devuelve vacío en caso de error
        }
    }
    public function VerCitasDoctorM($id_doctor) {
         try {
             $sql = "SELECT c.*, p.nombre as nombre_paciente, p.apellido as apellido_paciente, co.nombre as nombre_consultorio FROM citas c LEFT JOIN pacientes p ON c.id_paciente = p.id LEFT JOIN consultorios co ON c.id_consultorio = co.id WHERE c.id_doctor = :id_doctor ORDER BY c.inicio DESC";
             $stmt = $this->pdo->prepare($sql);
             $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
             $stmt->execute();
             return $stmt->fetchAll(PDO::FETCH_ASSOC);
         } catch (PDOException $e) {
             error_log("Error en VerCitasDoctorM: " . $e->getMessage());
             return [];
         }
    }
    
public function FinalizarCitaM($datos, $receta_items = [], $tratamientos = []) {
    $pdo = $this->pdo;

    try {
        // 1. Iniciar la transacción. (La mantienes como la manejas actualmente)
        $pdo->beginTransaction();

        // 2. Actualizar los datos principales de la tabla 'citas'.
        $sql = "UPDATE citas SET 
                    estado = 'Completada', 
                    motivo = :motivo, 
                    observaciones = :observaciones, 
                    peso = :peso,
                    presion_arterial = :presion_arterial,
                    indicaciones_receta = :indicaciones_receta,
                    receta_json = :receta_json,
                    certificado_texto_final = :certificado_texto_final,
                    certificado_uuid = :certificado_uuid,
                    receta_texto_final = :receta_texto_final,
                    receta_uuid = :receta_uuid,
                    -- 🆕 CAMPOS DE COBERTURA AÑADIDOS
                    id_tipo_pago = :id_tipo_pago,
                    id_cobertura_aplicada = :id_cobertura_aplicada
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        
        $exito = $stmt->execute([
            ':id' => $datos['id'],
            ':motivo' => $datos['motivo'],
            ':observaciones' => $datos['observaciones'],
            ':peso' => $datos['peso'],
            ':presion_arterial' => $datos['presion_arterial'],
            ':indicaciones_receta' => $datos['indicaciones_receta'],
            ':receta_json' => $datos['receta_json'],
            ':certificado_texto_final' => $datos['certificado_texto_final'],
            ':certificado_uuid' => $datos['certificado_uuid'],
            ':receta_texto_final' => $datos['receta_texto_final'],
            ':receta_uuid' => $datos['receta_uuid'],
            // 🆕 NUEVOS BINDINGS
            ':id_tipo_pago' => $datos['id_tipo_pago'],
            ':id_cobertura_aplicada' => $datos['id_cobertura_aplicada']
        ]);

        if (!$exito) {
            throw new Exception("Fallo al actualizar la información principal de la cita.");
        }

        // 3. Llamar a los métodos para asociar medicamentos y tratamientos.
        // Nota: estos métodos deben asegurarse de que $receta_items y $tratamientos se usen correctamente
        // ya que el controlador los pasó como argumentos a FinalizarCitaM.
        $this->AsociarMedicamentosFlexibleM($datos['id'], $receta_items);
        $this->AsociarTratamientosACita($datos['id'], $tratamientos);

        // 4. Si todo lo anterior tuvo éxito, confirmar la transacción.
        $pdo->commit();
        return true;

    } catch (Exception $e) {
        // 5. Si algo falló, revertir todos los cambios.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

static public function mdlObtenerDocumentoPorUuid($tabla, $tipo, $uuid) {
    $columnaUuid = ($tipo == 'certificado') ? 'certificado_uuid' : 'receta_uuid';
    $columnaTexto = ($tipo == 'certificado') ? 'certificado_texto_final' : 'receta_texto_final';

    // Se corrige ConexionBD::conectar() por ConexionBD::getInstancia()
    $stmt = ConexionBD::getInstancia()->prepare("SELECT $columnaTexto AS contenido FROM $tabla WHERE $columnaUuid = :uuid");
    $stmt->bindParam(":uuid", $uuid, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * [NUEVO] Verificar un documento por UUID buscando en ambas columnas.
 */
static public function mdlVerificarDocumentoPorUuid($uuid) {
    $stmt = ConexionBD::conectar()->prepare(
        "(SELECT certificado_texto_final AS contenido, 'Certificado Médico' AS tipo_doc FROM citas WHERE certificado_uuid = :uuid1) 
         UNION 
         (SELECT receta_texto_final AS contenido, 'Receta Médica' AS tipo_doc FROM citas WHERE receta_uuid = :uuid2)"
    );
    $stmt->bindParam(":uuid1", $uuid, PDO::PARAM_STR);
    $stmt->bindParam(":uuid2", $uuid, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// [NUEVO] Método auxiliar para obtener los datos de una cita por ID.
static public function ObtenerCitaPorIdM($tabla, $id) {
    $stmt = ConexionBD::conectar()->prepare("SELECT * FROM $tabla WHERE id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    public function RegistrarCambiosCita($id_cita, $campo, $valor_anterior, $valor_nuevo, $usuario, $id_usuario, $rol_usuario) {
    try {
        $sql = "INSERT INTO historial_cambios_citas 
                    (id_cita, fecha_cambio, campo_modificado, valor_anterior, valor_nuevo, usuario_modifico, id_usuario, rol_usuario) 
                VALUES 
                    (:id_cita, NOW(), :campo, :anterior, :nuevo, :usuario, :id_usuario, :rol)";
        
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
        throw new Exception("No se pudo registrar el cambio en el historial de la cita.");
    }
}
    
    public function getPDO() { return $this->pdo; }

    
    public static function ObtenerHorariosDisponiblesM($id_doctor, $fecha) {
        try {
            $dia_semana = date('N', strtotime($fecha));

            // 1. Obtener horario de trabajo
            $sql_horario = "SELECT hora_inicio, hora_fin FROM horarios_doctores WHERE id_doctor = :id_doctor AND dia_semana = :dia_semana LIMIT 1";
            
            // CORRECCIÓN 1: Usar la conexión de BD correcta para este archivo
            $stmt_horario = ConexionBD::getInstancia()->prepare($sql_horario);
            $stmt_horario->execute([':id_doctor' => $id_doctor, ':dia_semana' => $dia_semana]);
            $horario_trabajo = $stmt_horario->fetch(PDO::FETCH_ASSOC);

            if (!$horario_trabajo) { return []; } // Doctor no trabaja ese día

            // 2. Obtener citas agendadas
            $sql_citas = "SELECT inicio, fin FROM citas WHERE id_doctor = :id_doctor AND DATE(inicio) = :fecha AND estado IN ('Pendiente', 'Confirmada')";
            
            // CORRECCIÓN 1: Usar la conexión de BD correcta para este archivo
            $stmt_citas = ConexionBD::getInstancia()->prepare($sql_citas);
            $stmt_citas->execute([':id_doctor' => $id_doctor, ':fecha' => $fecha]);
            $citas_agendadas = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);

            // --- INICIO DE LA MEJORA 3 ---
            // 3. Obtener bloqueos agendados
            
            // CORRECCIÓN 2: Eliminar esta línea. El 'loader.php' ya cargó 'BloqueosM.php'.
            // require_once __DIR__ . '/BloqueosM.php'; // <--- ESTA LÍNEA CAUSA EL ERROR
            
            // Simplemente llamamos al método, el autoloader/loader se encarga
            $bloqueos_ocupados_slots = BloqueosM::ObtenerHorariosOcupadosPorBloqueosM($id_doctor, $fecha);
            // --- FIN DE LA MEJORA 3 ---

            // 4. Generar y filtrar slots
            $slots_disponibles = [];
            $duracion_slot = 30 * 60; // 30 minutos
            $inicio_turno = strtotime($fecha . ' ' . $horario_trabajo['hora_inicio']);
            $fin_turno = strtotime($fecha . ' ' . $horario_trabajo['hora_fin']);

            // Convertir citas en slots ocupados (para unificar la lógica)
            $citas_ocupadas_slots = [];
            foreach ($citas_agendadas as $cita) {
                $inicio = strtotime($cita['inicio']);
                $fin = strtotime($cita['fin']);
                for ($slot = $inicio; $slot < $fin; $slot += $duracion_slot) {
                    $citas_ocupadas_slots[] = date('H:i', $slot);
                }
            }

            // 5. Combinar citas y bloqueos en una sola lista de "no disponibles"
            $slots_ocupados_total = array_unique(array_merge($citas_ocupadas_slots, $bloqueos_ocupados_slots));
            $mapa_ocupados = array_flip($slots_ocupados_total);


            for ($slot_actual_time = $inicio_turno; $slot_actual_time < $fin_turno; $slot_actual_time += $duracion_slot) {
                
                $slot_string = date('H:i', $slot_actual_time);

                // 6. Si el slot NO está en el mapa de ocupados, está disponible
                if (!isset($mapa_ocupados[$slot_string])) {
                    $slots_disponibles[] = $slot_string;
                }
            }
            
            return $slots_disponibles;

        } catch (Exception $e) { // Captura genérica por si la conexión falla
            error_log("Error en ObtenerHorariosDisponiblesM: " . $e->getMessage());
            return []; // Devuelve vacío en caso de error
        }
    }


    public static function ObtenerProximasCitasPaciente($id_paciente, $limite = 3) {
        $sql = "SELECT c.inicio, c.motivo, CONCAT(d.nombre, ' ', d.apellido) AS nombre_doctor, d.foto AS foto_doctor, co.nombre as nombre_consultorio FROM citas c JOIN doctores d ON c.id_doctor = d.id JOIN consultorios co ON c.id_consultorio = co.id WHERE c.id_paciente = :id_paciente AND c.inicio > NOW() AND c.estado = 'Pendiente' ORDER BY c.inicio ASC LIMIT :limite";
        $pdo = ConexionBD::getInstancia()->prepare($sql);
        $pdo->bindParam(':id_paciente', $id_paciente, PDO::PARAM_INT);
        $pdo->bindParam(':limite', $limite, PDO::PARAM_INT);
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }
    



public static function ObtenerHistorialPorCitaM($id_cita) {
    // CORRECCIÓN: Usar getInstancia() en lugar de cBD()
    $pdo = ConexionBD::getInstancia()->prepare("SELECT * FROM historial_cambios_citas WHERE id_cita = :id_cita ORDER BY fecha_cambio DESC");
    $pdo->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
    $pdo->execute();
    return $pdo->fetchAll(PDO::FETCH_ASSOC);
}

public static function ObtenerCitasParaRecordatorio($horas_antes) {
    try {
        $sql = "SELECT 
                     c.id AS id_cita,
                     c.inicio AS inicio_cita,
                     p.nombre AS paciente_nombre,
                     p.apellido AS paciente_apellido,
                     p.correo AS paciente_email,
                     CONCAT(d.nombre, ' ', d.apellido) AS doctor_nombre,
                     co.nombre AS consultorio_nombre
                FROM citas c
                JOIN pacientes p ON c.id_paciente = p.id
                JOIN doctores d ON c.id_doctor = d.id
                JOIN consultorios co ON c.id_consultorio = co.id
                WHERE 
                     c.estado = 'Pendiente'
                     AND c.recordatorio_enviado_fecha IS NULL
                     AND c.inicio > NOW()
                     AND c.inicio <= DATE_ADD(NOW(), INTERVAL :horas_antes HOUR)";
        
        // === [NUEVA LÓGICA DE PERMISOS] ===
        $params = [':horas_antes' => $horas_antes];
        if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Secretario' && isset($_SESSION['id_consultorio'])) {
            $sql .= " AND c.id_consultorio = :id_consultorio";
            $params[':id_consultorio'] = $_SESSION['id_consultorio'];
        }
        // === FIN DE LA LÓGICA DE PERMISOS ===

        $stmt = ConexionBD::getInstancia()->prepare($sql);
        $stmt->execute($params); // Usamos el array de parámetros
        
        // CORRECCIÓN: El bucle foreach para concatenar nombres no es necesario si se usa CONCAT en SQL,
        // pero lo dejamos por si 'apellido_paciente' se usa en otro lado.
        // La consulta original no lo tenía, pero si lo añades, esta lógica es correcta.
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // foreach ($resultados as $key => $fila) {
        //     $resultados[$key]['paciente_nombre'] = $fila['paciente_nombre'] . ' ' . $fila['paciente_apellido'];
        // }

        return $resultados;

    } catch (PDOException $e) {
        error_log("Error en CitasM::ObtenerCitasParaRecordatorio: " . $e->getMessage());
        return [];
    }
}


public static function MarcarRecordatorioEnviado($id_cita) {
    try {
        $stmt = ConexionBD::getInstancia()->prepare(
            "UPDATE citas SET recordatorio_enviado_fecha = NOW() WHERE id = :id_cita"
        );
        $stmt->bindParam(':id_cita', $id_cita, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error en CitasM::MarcarRecordatorioEnviado: " . $e->getMessage());
        return false;
    }
}

public function BuscarEnHistorialM($filtros = []) {
    try {
        // --- 1. PREPARACIÓN ---
        $params = [];
        $resultado_final = [
            'paciente' => null,
            'citas' => []
        ];

        // --- 2. OBTENER DATOS DEL PACIENTE SI SE FILTRÓ POR UNO ---
        if (!empty($filtros['id_paciente'])) {
            // Asumimos que PacientesM::ObtenerPacienteM es estático o necesitas instanciarlo
            // require_once 'PacientesM.php'; // Si no está cargado
            $paciente_data = PacientesM::ObtenerPacienteM($filtros['id_paciente']);
            if ($paciente_data) {
                $paciente_data['alergias'] = PacientesM::ObtenerCondicionesClinicasM($filtros['id_paciente'], 'alergia');
                $paciente_data['enfermedades'] = PacientesM::ObtenerCondicionesClinicasM($filtros['id_paciente'], 'enfermedad');
                $resultado_final['paciente'] = $paciente_data;
            }
        }

        // --- 3. CONSULTA PRINCIPAL DE CITAS ---
        $sql_base = "SELECT 
                           c.id, c.inicio, c.fin, c.motivo, c.observaciones, c.estado,
                           c.certificado_uuid, c.receta_uuid,
                           CONCAT(d.nombre, ' ', d.apellido) AS doctor_atendio,
                           co.nombre AS nombre_consultorio,
                           p.nombre AS nombre_paciente, p.apellido AS apellido_paciente,
                           (SELECT GROUP_CONCAT(t.nombre SEPARATOR ', ') 
                            FROM cita_tratamiento ct 
                            JOIN tratamientos t ON ct.id_tratamiento = t.id 
                            WHERE ct.id_cita = c.id) AS tratamientos_aplicados
                         FROM citas c
                         LEFT JOIN doctores d ON c.id_doctor = d.id
                         LEFT JOIN consultorios co ON c.id_consultorio = co.id
                         LEFT JOIN pacientes p ON c.id_paciente = p.id
                         WHERE 1=1";

        // --- 4. LÓGICA DE SEGURIDAD POR ROL ---
        if (isset($_SESSION['rol'])) {
            $rol = $_SESSION['rol'];
            if ($rol === 'Doctor') {
                $sql_base .= " AND c.id_doctor = :id_doctor_sesion";
                $params[':id_doctor_sesion'] = $_SESSION['id'];
            } 
            elseif ($rol === 'Secretario' && isset($_SESSION['id_consultorio'])) {
                $sql_base .= " AND c.id_consultorio = :id_consultorio_sesion";
                $params[':id_consultorio_sesion'] = $_SESSION['id_consultorio'];
            }
        }

        // --- 5. CONDICIÓN DE HISTORIAL ---
        $sql_base .= " AND c.inicio < NOW()";

        // --- 6. APLICACIÓN DE FILTROS DEL FORMULARIO ---
        if (!empty($filtros['id_paciente'])) {
            $sql_base .= " AND c.id_paciente = :id_paciente";
            $params[':id_paciente'] = $filtros['id_paciente'];
        }
        if (!empty($filtros['id_doctor']) && $_SESSION['rol'] !== 'Doctor') {
            $sql_base .= " AND c.id_doctor = :id_doctor";
            $params[':id_doctor'] = $filtros['id_doctor'];
        }
        if (!empty($filtros['id_tratamiento'])) {
            $sql_base .= " AND EXISTS (
                                 SELECT 1 
                                 FROM cita_tratamiento 
                                 WHERE id_cita = c.id AND id_tratamiento = :id_tratamiento
                             )";
            $params[':id_tratamiento'] = $filtros['id_tratamiento'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql_base .= " AND DATE(c.inicio) >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql_base .= " AND DATE(c.inicio) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['palabra_clave'])) {
            $sql_base .= " AND (c.motivo LIKE :palabra_clave OR c.observaciones LIKE :palabra_clave)";
            $params[':palabra_clave'] = "%" . trim($filtros['palabra_clave']) . "%";
        }
        
        // --- 7. ORDENACIÓN ---
        $sql_base .= " ORDER BY c.inicio DESC";
        
        // --- 8. EJECUCIÓN DE LA CONSULTA PRINCIPAL ---
        $stmt = $this->pdo->prepare($sql_base);
        $stmt->execute($params);
        $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($citas)) {
            return $resultado_final; // paciente (si aplica) + citas vacías
        }

        // --- 9. ENRIQUECIMIENTO DE DATOS CON MEDICAMENTOS ---
        $ids_citas = array_column($citas, 'id');
        $placeholders = implode(',', array_fill(0, count($ids_citas), '?'));
        
        $sql_medicamentos = "SELECT 
                                 cm.id_cita,
                                 cm.dosis,
                                 cm.frecuencia,
                                 cm.instrucciones,
                                 cm.fecha_inicio,
                                 cm.fecha_fin,
                                 COALESCE(
                                     CONCAT(f.nombre_generico, ' - ', mp.presentacion), 
                                     cm.nombre_manual
                                 ) AS nombre_medicamento 
                               FROM 
                                 cita_medicamento cm 
                               LEFT JOIN medicamento_presentaciones mp ON cm.id_presentacion = mp.id 
                               LEFT JOIN farmacos f ON mp.id_farmaco = f.id
                               WHERE cm.id_cita IN ($placeholders)";
                               
        $stmt_meds = $this->pdo->prepare($sql_medicamentos);
        $stmt_meds->execute($ids_citas);
        $medicamentos_bruto = $stmt_meds->fetchAll(PDO::FETCH_ASSOC);

        $medicamentos_map = [];
        foreach ($medicamentos_bruto as $med) {
            $medicamentos_map[$med['id_cita']][] = $med;
        }
        
        foreach ($citas as $key => $cita) {
            $citas[$key]['medicamentos_recetados'] = $medicamentos_map[$cita['id']] ?? [];
        }

        // --- 10. RETORNO FINAL ---
        $resultado_final['citas'] = $citas;
        return $resultado_final;

    } catch (PDOException $e) {
        error_log("Error en BuscarEnHistorialM: " . $e->getMessage());
        return ['paciente' => null, 'citas' => []];
    }
}


public function AsociarTratamientosACita($id_cita, $tratamientos) {
    // Usa la conexión del objeto, que ya está dentro de la transacción.
    $conexion = $this->pdo;
    
    try {
        $stmt_delete = $conexion->prepare("DELETE FROM cita_tratamiento WHERE id_cita = :id_cita");
        $stmt_delete->execute([':id_cita' => $id_cita]);
        
        if (empty($tratamientos)) {
            return true;
        }

        $stmt_insert = $conexion->prepare("INSERT INTO cita_tratamiento (id_cita, id_tratamiento) VALUES (:id_cita, :id_tratamiento)");
        
        foreach ($tratamientos as $id_tratamiento) {
            if (is_numeric($id_tratamiento)) {
                $stmt_insert->execute([
                    ':id_cita' => $id_cita, 
                    ':id_tratamiento' => (int)$id_tratamiento
                ]);
            }
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error en AsociarTratamientosACita: " . $e->getMessage());
        throw new Exception("No se pudieron asociar los tratamientos a la cita.");
    }
}

public function AsociarMedicamentosFlexibleM($id_cita, $receta_items) {
    $conexion = $this->pdo;
    
    try {
        // 1. Borrar las asociaciones de medicamentos antiguas para esta cita.
        $stmt_delete = $conexion->prepare("DELETE FROM cita_medicamento WHERE id_cita = :id_cita");
        $stmt_delete->execute([':id_cita' => $id_cita]);

        if (empty($receta_items)) {
            return true;
        }

        $sql_insert = "INSERT INTO cita_medicamento 
                           (id_cita, id_presentacion, nombre_manual, dosis, frecuencia, instrucciones) 
                         VALUES 
                           (:id_cita, :id_presentacion, :nombre_manual, :dosis, :frecuencia, :instrucciones)";
        $stmt_insert = $conexion->prepare($sql_insert);

        // 2. Recorrer cada medicamento de la receta.
        foreach ($receta_items as $item) {
            $id_presentacion_final = $item['id_presentacion'] ?? null;
            
            // 3. Lógica inteligente para medicamentos manuales.
            if (empty($id_presentacion_final) && !empty($item['nombre_generico']) && !empty($item['presentacion'])) {
                
                // ¡LLAMADA REAL Y ACTIVA!
                // Se llama al método en MedicamentosM para que cree el fármaco/presentación pendiente.
                // require_once 'MedicamentosM.php'; // Si no está cargado
                $id_presentacion_final = MedicamentosM::CrearFarmacoYPresentacionPendienteM(
                    $item['nombre_generico'],
                    $item['presentacion']
                );
            }
            
            // Si después de todo el proceso no tenemos un ID de presentación, no lo insertamos.
            if (empty($id_presentacion_final)) {
                continue;
            }

            // 4. Insertar la relación en la tabla 'cita_medicamento'.
            $stmt_insert->execute([
                ':id_cita'         => $id_cita,
                ':id_presentacion' => $id_presentacion_final,
                ':nombre_manual'   => $item['nombre_completo'] ?? null,
                ':dosis'           => $item['dosis'] ?? null,
                ':frecuencia'      => $item['frecuencia'] ?? null,
                ':instrucciones'   => $item['instrucciones'] ?? null
            ]);
        }
        
        return true;

    } catch (PDOException $e) {
        error_log("Error en AsociarMedicamentosFlexibleM: " . $e->getMessage());
        throw new Exception("No se pudieron asociar los medicamentos a la cita.");
    }
}


static public function ObtenerNombreCoberturaCitaM($id_cobertura_aplicada, $id_tipo_pago) {
        try {
            $pdo = ConexionBD::getInstancia(); 

            // CASO 1: Tiene una afiliación específica (Es una Obra Social/Prepaga guardada)
            if (!empty($id_cobertura_aplicada) && $id_cobertura_aplicada > 0) {
                
                // Hacemos JOIN para traer: Nombre OS + Plan + Nro Afiliado
                $sql = "SELECT 
                            CONCAT(os.nombre, ' [Plan: ', COALESCE(pc.plan, 'S/D'), '] (Nro: ', COALESCE(pc.numero_afiliado, '-'), ')') AS nombre_completo
                        FROM paciente_cobertura pc
                        INNER JOIN obras_sociales os ON pc.id_obra_social = os.id
                        WHERE pc.id = :id_cob";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_cob', $id_cobertura_aplicada, PDO::PARAM_INT);
                $stmt->execute();
                $res = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($res) {
                    return $res['nombre_completo'];
                }
            } 
            
            // CASO 2: No tiene afiliación específica, pero tiene Tipo de Pago (Ej: Particular o OS generica)
            if (!empty($id_tipo_pago) && $id_tipo_pago > 0) {
                $sql = "SELECT nombre FROM obras_sociales WHERE id = :id_pago";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_pago', $id_tipo_pago, PDO::PARAM_INT);
                $stmt->execute();
                $res = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($res) {
                    return $res['nombre'];
                }
            }

            return "Particular / Sin asignar";

        } catch (Exception $e) {
            return "Error al obtener cobertura";
        }
    }



    static public function ObtenerIdsPagoCitaM($tabla, $idCita) {
    // CORRECCIÓN: Usamos la clase ConexionBD y el método getInstancia()
    $stmt = ConexionBD::getInstancia()->prepare("SELECT id_tipo_pago, id_cobertura_aplicada FROM $tabla WHERE id = :id");
    
    $stmt->bindParam(":id", $idCita, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public static function MarcarAusentesAutomaticoM()
{
    // Usar la instancia PDO correcta (ajusta el método si tu clase se llama distinto)
    $pdo = ConexionBD::getInstancia();

    $sql = "
        SELECT id, estado
        FROM citas
        WHERE (estado IS NULL OR estado = '' OR estado = 'Pendiente')
          AND inicio < NOW()
        FOR UPDATE
    ";

    try {
        // Bloquear filas para evitar race conditions (si tu motor lo soporta)
        $pdo->beginTransaction();

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $citasPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$citasPendientes) {
            $pdo->commit();
            return 0;
        }

        $upd = $pdo->prepare("
            UPDATE citas
            SET estado = 'Ausente'
            WHERE id = :id
              AND (estado IS NULL OR estado = '' OR estado = 'Pendiente')
        ");

        $log = $pdo->prepare("
            INSERT INTO historial_cambios_citas
            (id_cita, fecha_cambio, campo_modificado, valor_anterior, valor_nuevo,
             usuario_modifico, id_usuario, rol_usuario)
            VALUES (:id_cita, NOW(), 'estado', :valor_anterior, 'Ausente', 'Sistema', 0, 'Sistema')
        ");

        $total = 0;
        foreach ($citasPendientes as $cita) {
            $id = (int)$cita['id'];
            $valorAnterior = $cita['estado'] ?? '';

            $upd->execute([':id' => $id]);

            if ($upd->rowCount() > 0) {
                $log->execute([
                    ':id_cita' => $id,
                    ':valor_anterior' => $valorAnterior
                ]);
                $total++;
            }
        }

        $pdo->commit();
        return $total;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("MarcarAusentesAutomaticoM error: " . $e->getMessage());
        return 0;
    }
}


}