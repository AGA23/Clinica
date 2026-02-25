<?php


require_once __DIR__ . '/ConexionBD.php';

class ConsultoriosM {

    
 public static function VerConsultoriosParaAdmin() {
    $dia_semana_actual = date('N');

    $sql = "SELECT
                co.id,
                co.nombre AS nombre_consultorio,
                co.direccion,
                co.telefono,
                ec.estado AS estado_manual,
                
                (SELECT CONCAT(hc.hora_apertura, '|', hc.hora_cierre) 
                 FROM horarios_consultorios hc 
                 WHERE hc.id_consultorio = co.id AND hc.dia_semana = :dia_semana) AS horario_consultorio_hoy,
                
                
                -- Usamos IFNULL para asegurarnos de que si no hay horario, no se concatene nada.
                -- Y CONCAT_WS para manejar valores nulos de forma segura.
                (SELECT GROUP_CONCAT(
                    CONCAT_WS(':', 
                        CONCAT(d.nombre, ' ', d.apellido), 
                        CONCAT_WS('-', hd.hora_inicio, hd.hora_fin)
                    ) SEPARATOR ';')
                 FROM doctores d
                 JOIN horarios_doctores hd ON d.id = hd.id_doctor
                 WHERE d.id_consultorio = co.id AND hd.dia_semana = :dia_semana
                ) AS doctores_trabajando_hoy
            FROM
                consultorios co
            LEFT JOIN
                estados_consultorios ec ON co.id = ec.id_consultorio AND ec.fecha = CURDATE()";
    
    $params = [':dia_semana' => $dia_semana_actual];

    // La lógica de filtro para Secretario se mantiene, pero la consulta base ahora es correcta
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Secretario' && isset($_SESSION['id_consultorio'])) {
        $sql .= " WHERE co.id = :id_consultorio_sesion";
        $params[':id_consultorio_sesion'] = $_SESSION['id_consultorio'];
    }
    
    $sql .= " GROUP BY co.id ORDER BY co.nombre ASC";

    $pdo = ConexionBD::getInstancia()->prepare($sql);
    $pdo->execute($params);
    return $pdo->fetchAll(PDO::FETCH_ASSOC);
}


public static function ObtenerDatosConsultorioM($id) {
    if (empty($id) || !is_numeric($id)) return false;
    
    // La consulta ya selecciona todos los campos que necesitamos.
    $stmt = ConexionBD::getInstancia()->prepare(
        "SELECT id, nombre, direccion, telefono, email FROM consultorios WHERE id = :id"
    );
    
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public static function ObtenerDatosParaDirectorioPublico() {
    try {
        
        $sql = "SELECT 
                    c.id AS id_sede,
                    c.nombre AS nombre_sede,
                    c.direccion,
                    c.telefono,
                    c.email,
                    -- Agrupamos todos los horarios de apertura del consultorio en un solo campo
                    GROUP_CONCAT(DISTINCT CONCAT(hc.dia_semana, '|', hc.hora_apertura, '|', hc.hora_cierre) ORDER BY hc.dia_semana SEPARATOR ';') AS horarios_sede,
                    -- Agrupamos todos los doctores que trabajan en esta sede y sus horarios
                    GROUP_CONCAT(DISTINCT CONCAT(d.id, ':', d.nombre, ' ', d.apellido, ':', hd.dia_semana, '|', hd.hora_inicio, '|', hd.hora_fin) ORDER BY d.apellido SEPARATOR ';') AS doctores_con_horarios
                FROM 
                    consultorios AS c
                LEFT JOIN 
                    horarios_consultorios AS hc ON c.id = hc.id_consultorio
                LEFT JOIN 
                    doctores AS d ON c.id = d.id_consultorio
                LEFT JOIN 
                    horarios_doctores AS hd ON d.id = hd.id_doctor
                GROUP BY 
                    c.id
                ORDER BY 
                    c.nombre ASC";

        $stmt = ConexionBD::getInstancia()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error en ConsultoriosM::ObtenerDatosParaDirectorioPublico: " . $e->getMessage());
        return false; 
    }
}


public static function ObtenerInformacionCompletaConsultorios() {
    try {
        $sql = "SELECT 
                    d.id AS id_doctor,
                    CONCAT(d.nombre, ' ', d.apellido) AS nombre_doctor,
                    d.foto AS foto_doctor,
                    c.id AS id_consultorio,
                    c.nombre AS nombre_consultorio,
                    c.direccion AS direccion_consultorio,
                    c.telefono AS telefono_consultorio,
                    c.email AS email_consultorio,
                    
                    -- ¡AÑADIDO! Se obtiene el estado Y el motivo manual, separados por '|'.
                    -- CONCAT_WS es seguro y no fallará si el motivo es NULL.
                    (SELECT CONCAT_WS('|', estado, motivo) 
                     FROM estados_consultorios 
                     WHERE id_consultorio = c.id AND fecha = CURDATE()) AS estado_motivo_manual_hoy,

                    -- (El resto de las subconsultas para horarios y tratamientos se quedan igual)
                    (SELECT GROUP_CONCAT(CONCAT(hd.dia_semana, ';', hd.hora_inicio, ';', hd.hora_fin) SEPARATOR '|') 
                     FROM horarios_doctores hd 
                     WHERE hd.id_doctor = d.id) AS horarios_doctor,
                    (SELECT GROUP_CONCAT(t.nombre SEPARATOR ', ') 
                     FROM doctor_tratamiento dt
                     JOIN tratamientos t ON dt.id_tratamiento = t.id
                     WHERE dt.id_doctor = d.id) AS tratamientos_doctor
                FROM 
                    doctores d
                LEFT JOIN 
                    consultorios c ON d.id_consultorio = c.id
                WHERE 
                    d.id_consultorio IS NOT NULL
                ORDER BY
                    c.nombre ASC, d.apellido ASC, d.nombre ASC";

        $stmt = ConexionBD::getInstancia()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error en ConsultoriosM::ObtenerInformacionCompletaConsultorios: " . $e->getMessage());
        return false;
    }
}

    public static function ObtenerHorariosDeConsultorios() {
        $sql = "SELECT co.id AS id_consultorio, co.nombre AS nombre_consultorio, hc.dia_semana, hc.hora_apertura, hc.hora_cierre
                FROM consultorios co JOIN horarios_consultorios hc ON co.id = hc.id_consultorio
                ORDER BY co.nombre ASC, hc.dia_semana ASC";
        $pdo = ConexionBD::getInstancia()->prepare($sql);
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }

   static public function CrearConsultorioM($tablaBD, $datos) {
   
    $sql = "INSERT INTO $tablaBD (nombre, direccion, telefono, email) 
            VALUES (:nombre, :direccion, :telefono, :email)";
            
    $stmt = ConexionBD::getInstancia()->prepare($sql);
    
    $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
    $stmt->bindParam(":direccion", $datos["direccion"], PDO::PARAM_STR);
    $stmt->bindParam(":telefono", $datos["telefono"], PDO::PARAM_STR);
    $stmt->bindParam(":email", $datos["email"], PDO::PARAM_STR);
    
    return $stmt->execute();
}

    static public function VerConsultoriosM($tablaBD, $columna, $valor) {
        if ($columna == null) {
            $stmt = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD");
        } else {
            $stmt = ConexionBD::getInstancia()->prepare("SELECT * FROM $tablaBD WHERE $columna = :valor");
            $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
   public static function VerConsultorioPorId($id) {
    if (empty($id) || !is_numeric($id)) return false;
    $stmt = ConexionBD::getInstancia()->prepare("SELECT id, nombre, direccion, telefono, email FROM consultorios WHERE id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

   static public function ActualizarConsultorioM($tablaBD, $datos) {
 
    $sql_parts = [];
    $params = ['id' => $datos['id']];

    if (isset($datos['nombre'])) {
        $sql_parts[] = "nombre = :nombre";
        $params['nombre'] = $datos['nombre'];
    }
    if (isset($datos['direccion'])) {
        $sql_parts[] = "direccion = :direccion";
        $params['direccion'] = $datos['direccion'];
    }
    if (isset($datos['telefono'])) {
        $sql_parts[] = "telefono = :telefono";
        $params['telefono'] = $datos['telefono'];
    }
    if (isset($datos['email'])) {
        $sql_parts[] = "email = :email";
        $params['email'] = $datos['email'];
    }

    if (empty($sql_parts)) return true; 

    $sql = "UPDATE $tablaBD SET " . implode(', ', $sql_parts) . " WHERE id = :id";
    $stmt = ConexionBD::getInstancia()->prepare($sql);
    
    return $stmt->execute($params);
}
    
    static public function BorrarConsultorioM($tablaBD, $id) {
        $stmt = ConexionBD::getInstancia()->prepare("DELETE FROM $tablaBD WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function ObtenerHorariosParaEdicionM($id_consultorio) {
        $stmt = ConexionBD::getInstancia()->prepare("SELECT dia_semana, hora_apertura, hora_cierre FROM horarios_consultorios WHERE id_consultorio = :id");
        $stmt->bindParam(":id", $id_consultorio, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ActualizarHorariosM($id_consultorio, $horarios) {
        $conexion = ConexionBD::getInstancia();
        try {
            $conexion->beginTransaction();
            $stmt_delete = $conexion->prepare("DELETE FROM horarios_consultorios WHERE id_consultorio = :id");
            $stmt_delete->bindParam(":id", $id_consultorio, PDO::PARAM_INT);
            $stmt_delete->execute();
            $stmt_insert = $conexion->prepare("INSERT INTO horarios_consultorios (id_consultorio, dia_semana, hora_apertura, hora_cierre) VALUES (:id, :dia, :apertura, :cierre)");
            foreach ($horarios as $dia => $h) {
                if (isset($h['activo']) && !empty($h['apertura']) && !empty($h['cierre'])) {
                    $stmt_insert->bindParam(":id", $id_consultorio, PDO::PARAM_INT);
                    $stmt_insert->bindParam(":dia", $dia, PDO::PARAM_INT);
                    $stmt_insert->bindParam(":apertura", $h['apertura'], PDO::PARAM_STR);
                    $stmt_insert->bindParam(":cierre", $h['cierre'], PDO::PARAM_STR);
                    $stmt_insert->execute();
                }
            }
            $conexion->commit();
            return true;
        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error en transacción de horarios: " . $e->getMessage());
            return false;
        }
    }

    public static function ActualizarEstadoManualM($id_consultorio, $estado, $motivo, $id_usuario) {
        $conexion = ConexionBD::getInstancia();
        $hoy = date('Y-m-d');
        if (empty($estado)) {
            $stmt = $conexion->prepare("DELETE FROM estados_consultorios WHERE id_consultorio = :id AND fecha = :fecha");
            $stmt->bindParam(':id', $id_consultorio, PDO::PARAM_INT);
            $stmt->bindParam(':fecha', $hoy, PDO::PARAM_STR);
            return $stmt->execute();
        }
        $sql = "INSERT INTO estados_consultorios (id_consultorio, fecha, estado, motivo, id_usuario)
                VALUES (:id, :fecha, :estado, :motivo, :user)
                ON DUPLICATE KEY UPDATE estado = VALUES(estado), motivo = VALUES(motivo), id_usuario = VALUES(id_usuario)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':id', $id_consultorio, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $hoy, PDO::PARAM_STR);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
        $stmt->bindParam(':motivo', $motivo, PDO::PARAM_STR);
        $stmt->bindParam(':user', $id_usuario, PDO::PARAM_INT);
        return $stmt->execute();
    }


    public static function ListarTodosLosConsultoriosM() {
    try {
        $sql = "SELECT id, nombre, direccion, telefono, email 
                FROM consultorios 
                ORDER BY nombre ASC";
        
        $stmt = ConexionBD::getInstancia()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en ConsultoriosM::ListarTodosLosConsultoriosM: " . $e->getMessage());
        return [];
    }
}
}