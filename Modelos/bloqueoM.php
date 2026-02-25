<?php
// En Modelos/BloqueosM.php (VERSIÓN FINAL CORREGIDA)

class BloqueosM {

    /**
     * Obtiene los bloqueos para el FullCalendar, con filtros.
     */
    public static function ObtenerBloqueosM($filtros) {
        try {
            $pdo = ConexionBD::getInstancia();
            
            $sql = "SELECT b.id, b.id_doctor, b.motivo, b.inicio, b.fin, d.nombre, d.apellido
                    FROM bloqueos_doctor b 
                    LEFT JOIN doctores d ON b.id_doctor = d.id
                    WHERE 1=1";
            
            $params = []; // <-- El array de parámetros se inicializa aquí

            if (!empty($filtros['start']) && !empty($filtros['end'])) {
                 $sql .= " AND b.fin >= :start AND b.inicio <= :end";
                 
                 // --- INICIO DE LA CORRECCIÓN ---
                 // Faltaba añadir los filtros de fecha al array de parámetros
                 $params[':start'] = $filtros['start'];
                 $params[':end'] = $filtros['end'];
                 // --- FIN DE LA CORRECCIÓN ---

            } else {
                 $sql .= " AND b.fin >= NOW()";
            }

            if (!empty($filtros['id_consultorio'])) {
                $sql .= " AND d.id_consultorio = :id_consultorio";
                $params[':id_consultorio'] = $filtros['id_consultorio'];
            }
            if (!empty($filtros['id_doctor'])) {
                $sql .= " AND b.id_doctor = :id_doctor";
                $params[':id_doctor'] = $filtros['id_doctor'];
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params); // <-- Ahora $params SÍ incluye :start y :end
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en ObtenerBloqueosM: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Inserta un nuevo bloqueo, validando conflictos con citas.
     */
    public function CrearBloqueoM($datos) {
        $pdo = ConexionBD::getInstancia();

        try {
            // --- CORRECCIÓN DE COLUMNAS (según tu imagen) ---
            if (strtotime($datos['inicio']) >= strtotime($datos['fin'])) {
                return "Error: La hora de inicio debe ser anterior a la hora de fin.";
            }

            $sql_citas = "SELECT COUNT(*) FROM citas 
                          WHERE id_doctor = :id_doctor 
                          AND estado IN ('Pendiente', 'Confirmada')
                          AND (inicio < :fin AND fin > :inicio)";
            $stmt_citas = $pdo->prepare($sql_citas);
            $stmt_citas->execute([
                ':id_doctor' => $datos['id_doctor'],
                ':inicio' => $datos['inicio'], // Corregido
                ':fin' => $datos['fin']       // Corregido
            ]);
            if ($stmt_citas->fetchColumn() > 0) {
                return "Error: El bloqueo se superpone con una cita ya agendada.";
            }

            // --- CORRECCIÓN DE COLUMNAS (según tu imagen) ---
            $sql_bloqueos = "SELECT COUNT(*) FROM bloqueos_doctor 
                             WHERE id_doctor = :id_doctor 
                             AND (inicio < :fin AND fin > :inicio)"; // Corregido
            $stmt_bloqueos = $pdo->prepare($sql_bloqueos);
            $stmt_bloqueos->execute([
                ':id_doctor' => $datos['id_doctor'],
                ':inicio' => $datos['inicio'], // Corregido
                ':fin' => $datos['fin']       // Corregido
            ]);
            if ($stmt_bloqueos->fetchColumn() > 0) {
                return "Error: El bloqueo se superpone con otro bloqueo ya existente.";
            }

            // --- CORRECCIÓN DE COLUMNAS (según tu imagen) ---
            $sql_insert = "INSERT INTO bloqueos_doctor (id_doctor, inicio, fin, motivo, creado_por) 
                           VALUES (:id_doctor, :inicio, :fin, :motivo, :creado_por)"; // Corregido
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                ':id_doctor' => $datos['id_doctor'],
                ':inicio' => $datos['inicio'],     // Corregido
                ':fin' => $datos['fin'],         // Corregido
                ':motivo' => $datos['motivo'],
                ':creado_por' => $datos['id_doctor'] // Corregido
            ]);
            
            return true;

        } catch (PDOException $e) {
            error_log("Error en CrearBloqueoM: " . $e->getMessage());
            return "Error interno al guardar el bloqueo.";
        }
    }

    /**
     * Obtiene los slots ocupados por bloqueos para un doctor y fecha.
     */
    public static function ObtenerHorariosOcupadosPorBloqueosM($id_doctor, $fecha) {
        try {
            $pdo = ConexionBD::getInstancia();
            
            // --- CORRECCIÓN DE COLUMNAS (según tu imagen) ---
            $sql = "SELECT inicio, fin FROM bloqueos_doctor 
                    WHERE id_doctor = :id_doctor AND DATE(inicio) = :fecha"; // Corregido
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id_doctor' => $id_doctor, ':fecha' => $fecha]);
            $bloqueos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $slots_ocupados = [];
            $duracion_slot = 30 * 60; 

            foreach ($bloqueos as $bloqueo) {
                $inicio = strtotime($bloqueo['inicio']); // Corregido
                $fin = strtotime($bloqueo['fin']);     // Corregido
                for ($slot = $inicio; $slot < $fin; $slot += $duracion_slot) {
                    $slots_ocupados[] = date('H:i', $slot);
                }
            }
            
            return $slots_ocupados;

        } catch (PDOException $e) {
            error_log("Error en ObtenerHorariosOcupadosPorBloqueosM: " . $e->getMessage());
            return [];
        }
    }

   public function EliminarBloqueoM($id_bloqueo, $id_usuario, $rol) {
        try {
            $pdo = ConexionBD::getInstancia();
            
            // --- MODIFICACIÓN 1: Obtener la fecha de inicio ---
            // (Usando los nombres de columna de tu BD: 'inicio')
            $sql_verif = "SELECT id_doctor, inicio FROM bloqueos_doctor WHERE id = :id";
            $stmt_verif = $pdo->prepare($sql_verif);
            $stmt_verif->execute([':id' => $id_bloqueo]);
            $bloqueo = $stmt_verif->fetch(PDO::FETCH_ASSOC);

            if (!$bloqueo) {
                return "Error: El bloqueo que intenta eliminar no existe.";
            }

            if ($rol === 'Doctor' && $bloqueo['id_doctor'] != $id_usuario) {
                return "Error: No tiene permisos para eliminar este bloqueo.";
            }

            // --- MODIFICACIÓN 2: Lógica de tiempo (NUEVA) ---
            // Solo los 'Administrador' pueden borrar bloqueos pasados o en curso.
            // Los 'Doctor' solo pueden borrar bloqueos futuros.
            if ($rol === 'Doctor') {
                $inicio_bloqueo = strtotime($bloqueo['inicio']);
                $ahora = time();
                
                // Si la hora de inicio del bloqueo ya pasó (o es ahora mismo)
                if ($inicio_bloqueo <= $ahora) {
                    return "Error: Los doctores no pueden eliminar bloqueos que ya han comenzado o están en el pasado. Solo se pueden eliminar bloqueos futuros.";
                }
            }
            // (El Administrador se salta esta validación y puede borrarlo)
            // --- FIN DE LA MODIFICACIÓN ---


            $sql_delete = "DELETE FROM bloqueos_doctor WHERE id = :id";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->execute([':id' => $id_bloqueo]);

            return true;

        } catch (PDOException $e) {
            error_log("Error en EliminarBloqueoM: " . $e->getMessage());
            return "Error interno al eliminar el bloqueo.";
        }
    }
}