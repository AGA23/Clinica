<?php


require_once "ConexionBD.php";

class InicioM { 

    
    static public function MostrarInicioM($tablaBD, $id) {
        $pdo = ConexionBD::getInstancia()->prepare("SELECT id, intro, horaE, horaS, direccion, telefono, correo, logo, favicon FROM $tablaBD WHERE id = :id");
        $pdo->bindParam(":id", $id, PDO::PARAM_INT);
        $pdo->execute();
        return $pdo->fetch(PDO::FETCH_ASSOC);
    }
 static public function ObtenerEstadisticasAdminM() {
        $pdo = ConexionBD::getInstancia();
        $estadisticas = [];

        try {
            // 1. Contar el número total de doctores
            $stmt_doctores = $pdo->prepare("SELECT COUNT(*) FROM doctores");
            $stmt_doctores->execute();
            $estadisticas['doctores'] = $stmt_doctores->fetchColumn();

            // 2. Contar el número total de pacientes
            $stmt_pacientes = $pdo->prepare("SELECT COUNT(*) FROM pacientes");
            $stmt_pacientes->execute();
            $estadisticas['pacientes'] = $stmt_pacientes->fetchColumn();

            // 3. Contar el número total de secretarios
            $stmt_secretarios = $pdo->prepare("SELECT COUNT(*) FROM secretarios");
            $stmt_secretarios->execute();
            $estadisticas['secretarios'] = $stmt_secretarios->fetchColumn();

            // 4. Contar las citas (completadas o pendientes) en los últimos 7 días
            // DATEDIFF(CURDATE(), DATE(inicio)) <= 7 filtra los últimos 7 días
            $stmt_citas = $pdo->prepare(
                "SELECT COUNT(*) FROM citas 
                 WHERE estado IN ('Completada', 'Pendiente', 'Confirmada') 
                 AND inicio >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
            );
            $stmt_citas->execute();
            $estadisticas['citas_semana'] = $stmt_citas->fetchColumn();

            return $estadisticas;

        } catch (PDOException $e) {
            // En caso de error, devolvemos un array con ceros para no romper la vista.
            error_log("Error en ObtenerEstadisticasAdminM: " . $e->getMessage());
            return [
                'doctores' => 0,
                'pacientes' => 0,
                'secretarios' => 0,
                'citas_semana' => 0
            ];
        }
    }
   
    static public function ActualizarInicioM($tablaBD, $datosC) {
        $pdo = ConexionBD::getInstancia()->prepare(
            "UPDATE $tablaBD SET intro = :intro, direccion = :direccion, horaE = :horaE, horaS = :horaS, 
             telefono = :telefono, correo = :correo, logo = :logo, favicon = :favicon WHERE id = :id"
        );

        $pdo->bindParam(":id", $datosC["id"], PDO::PARAM_INT);
        $pdo->bindParam(":intro", $datosC["intro"], PDO::PARAM_STR);
        $pdo->bindParam(":direccion", $datosC["direccion"], PDO::PARAM_STR);
        $pdo->bindParam(":horaE", $datosC["horaE"], PDO::PARAM_STR);
        $pdo->bindParam(":horaS", $datosC["horaS"], PDO::PARAM_STR);
        $pdo->bindParam(":telefono", $datosC["telefono"], PDO::PARAM_STR);
        $pdo->bindParam(":correo", $datosC["correo"], PDO::PARAM_STR);
        $pdo->bindParam(":logo", $datosC["logo"], PDO::PARAM_STR);
        $pdo->bindParam(":favicon", $datosC["favicon"], PDO::PARAM_STR);

        return $pdo->execute();
    }

  
    // En Modelos/InicioM.php

    public static function ObtenerDatosDashboardDoctor($id_doctor) {
        $pdo = ConexionBD::getInstancia();
        $datos = [];

        try {
            // --- 1. OBTENER CITAS FUTURAS ---
            // (Solo citas reales, no bloqueos)
            $sql_citas = "SELECT id, nyaP, motivo, inicio, fin, estado 
                          FROM citas 
                          WHERE id_doctor = :id_doctor 
                            AND fin > NOW() 
                            AND estado NOT IN ('Cancelada', 'Completada')";
            
            $stmt_citas = $pdo->prepare($sql_citas);
            $stmt_citas->execute([':id_doctor' => $id_doctor]);
            $citas_futuras = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);

            // --- 2. OBTENER BLOQUEOS FUTUROS ---
            // (Usamos los nombres de columna de tu BD: 'inicio', 'fin')
            $sql_bloqueos = $pdo->prepare("SELECT id, motivo, inicio, fin 
                                          FROM bloqueos_doctor 
                                          WHERE id_doctor = :id_doctor AND fin >= NOW() 
                                          ORDER BY inicio ASC");
            $sql_bloqueos->execute([':id_doctor' => $id_doctor]);
            $bloqueos_futuros_db = $sql_bloqueos->fetchAll(PDO::FETCH_ASSOC);

            // --- 3. PROCESAMIENTO Y CLASIFICACIÓN DE DATOS ---
            $eventos_de_hoy = [];
            $bloqueos_futuros_lista = []; // Para la tabla "Mis Bloqueos"
            $citas_proximas_count = 0;
            $hoy_Ymd = date('Y-m-d');

            // Procesar Citas
            foreach($citas_futuras as $cita) {
                if (date('Y-m-d', strtotime($cita['inicio'])) === $hoy_Ymd) {
                    $eventos_de_hoy[] = $cita; // Añadir a la lista de "hoy"
                }
                $citas_proximas_count++;
            }

            // Procesar Bloqueos
            foreach($bloqueos_futuros_db as $bloqueo) {
                // Añadimos la bandera 'nyaP' => 'BLOQUEADO' para que la VISTA la entienda
                $bloqueo_formateado = [
                    'id' => $bloqueo['id'],
                    'nyaP' => 'BLOQUEADO', // Esta es la bandera clave
                    'motivo' => $bloqueo['motivo'],
                    'inicio' => $bloqueo['inicio'],
                    'fin' => $bloqueo['fin'],
                    'estado' => 'Bloqueado'
                ];

                // Lo añadimos a la lista de bloqueos futuros (para la tabla)
                $bloqueos_futuros_lista[] = $bloqueo_formateado; 

                // Si también es hoy, lo añadimos a la lista de "hoy"
                if (date('Y-m-d', strtotime($bloqueo['inicio'])) === $hoy_Ymd) {
                    $eventos_de_hoy[] = $bloqueo_formateado;
                }
            }

            // Ordenamos la lista combinada de "hoy" por hora de inicio
            usort($eventos_de_hoy, function($a, $b) {
                return strtotime($a['inicio']) - strtotime($b['inicio']);
            });

            // --- 4. DATOS PARA LAS TARJETAS (WIDGETS) ---
            // Contamos solo las citas de hoy (excluyendo bloqueos)
            $datos['citas_hoy'] = 0;
            foreach ($eventos_de_hoy as $evento) {
                if ($evento['nyaP'] !== 'BLOQUEADO') $datos['citas_hoy']++;
            }
            $datos['citas_proximas'] = $citas_proximas_count; // Total de citas futuras

            // 5. Total de Pacientes Únicos Atendidos (historial 'Completada')
            $stmt_pacientes = $pdo->prepare(
                "SELECT COUNT(DISTINCT id_paciente) FROM citas 
                 WHERE id_doctor = :id_doctor AND estado = 'Completada'"
            );
            $stmt_pacientes->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
            $stmt_pacientes->execute();
            $datos['pacientes_atendidos'] = $stmt_pacientes->fetchColumn();

            // 6. Datos para el Gráfico (sin cambios)
            $stmt_grafico = $pdo->prepare(
                "SELECT DATE_FORMAT(inicio, '%Y-%m') as mes, COUNT(*) as total 
                 FROM citas 
                 WHERE id_doctor = :id_doctor AND estado = 'Completada' AND inicio >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                 GROUP BY mes ORDER BY mes ASC"
            );
            $stmt_grafico->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
            $stmt_grafico->execute();
            $datos['grafico_citas'] = $stmt_grafico->fetchAll(PDO::FETCH_ASSOC);

            // 7. Asignar las listas procesadas al resultado final
            $datos['lista_citas_hoy'] = $eventos_de_hoy; // Lista de TODOS los eventos de hoy
            $datos['bloqueos_futuros'] = $bloqueos_futuros_lista; // Lista EXCLUSIVA de bloqueos

            return $datos;

        } catch (PDOException $e) {
            error_log("Error en ObtenerDatosDashboardDoctor: " . $e->getMessage());
            return [
                'citas_hoy' => 0,
                'citas_proximas' => 0,
                'pacientes_atendidos' => 0,
                'lista_citas_hoy' => [],
                'grafico_citas' => [],
                'bloqueos_futuros' => [] // Devolver vacío en caso de error
            ];
        }
    }

 public static function ObtenerDatosDashboardSecretario() {
    $pdo = ConexionBD::getInstancia();
    $datos = [];
    $hoy = date('Y-m-d');

    try {
        // 1. Total de citas para el día de hoy
        $stmt_citas_hoy = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE DATE(inicio) = :hoy");
        $stmt_citas_hoy->bindParam(':hoy', $hoy);
        $stmt_citas_hoy->execute();
        $datos['citas_hoy'] = $stmt_citas_hoy->fetchColumn();

        // 2. CORREGIDO: Se cuenta el total de pacientes, una consulta segura que siempre funciona.
        $datos['total_pacientes'] = $pdo->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();

        // 3. Citas pendientes en el futuro
        $stmt_citas_pendientes = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE estado = 'Pendiente' AND inicio > NOW()");
        $stmt_citas_pendientes->execute();
        $datos['citas_pendientes'] = $stmt_citas_pendientes->fetchColumn();

        // 4. Agenda completa del día
        $stmt_agenda = $pdo->prepare(
            "SELECT c.id, c.inicio, c.nyaP, d.apellido as doctor, co.nombre as consultorio, c.estado
             FROM citas c 
             JOIN doctores d ON c.id_doctor = d.id 
             JOIN consultorios co ON c.id_consultorio = co.id 
             WHERE DATE(c.inicio) = :hoy 
             ORDER BY c.inicio ASC"
        );
        $stmt_agenda->bindParam(':hoy', $hoy);
        $stmt_agenda->execute();
        $datos['agenda_dia'] = $stmt_agenda->fetchAll(PDO::FETCH_ASSOC);

        return $datos;

    } catch (PDOException $e) {
        error_log("Error en ObtenerDatosDashboardSecretario: " . $e->getMessage());
       return [ 
    'citas_hoy' => 0, 
    'total_pacientes' => 0, 
    'citas_pendientes' => 0, 
    'agenda_dia' => [] 
];
    }
}

    
    }
