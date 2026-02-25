<?php
// En Controladores/BloqueoC.php

class BloqueosC {

    /**
     * Método estático que crea el bloqueo en la BD.
     * Llamado por CrearBloqueoDoctorC
     */
    public static function crearBloqueo($id_doctor, $id_consultorio, $inicio, $fin, $motivo, $creado_por) {
        
        // 1. Validar solapamiento con citas (Esto ya estaba bien)
        $citas = CitasM::citasSolapadas($id_doctor, $inicio, $fin);
        if (!empty($citas)) {
            throw new Exception("No se puede bloquear: hay citas programadas en este horario.");
        }

        // 2. Insertar el bloqueo
        
        // --- CORRECCIÓN DE SQL (BASADO EN TU IMAGEN) ---
        // Se usan los nombres de columna: 'inicio', 'fin', 'creado_por'
        // Se añade la columna faltante: 'id_consultorio'
        $sql = "INSERT INTO bloqueos_doctor (id_doctor, id_consultorio, inicio, fin, motivo, creado_por)
                 VALUES (:id_doctor, :id_consultorio, :inicio, :fin, :motivo, :creado_por)";
        
        $pdo = ConexionBD::getInstancia(); 
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(":id_doctor", $id_doctor, PDO::PARAM_INT);
        $stmt->bindParam(":id_consultorio", $id_consultorio, PDO::PARAM_INT); // <-- Se añadió
        $stmt->bindParam(":inicio", $inicio);
        $stmt->bindParam(":fin", $fin);
        $stmt->bindParam(":motivo", $motivo);
        $stmt->bindParam(":creado_por", $creado_por, PDO::PARAM_INT); // <-- Se corrigió el nombre
        // --- FIN DE CORRECCIÓN ---

        $stmt->execute();
        
        return $pdo->lastInsertId();
    }


    /**
     * Obtiene los bloqueos (usado por VerBloqueosFullCalendar)
     */
    public static function VerBloqueosC() {
        return BloqueosM::ObtenerBloqueosM(['start' => date('Y-m-d'), 'end' => date('Y-m-d', strtotime('+5 years'))]);
    }


    /**
     * Formatea los bloqueos para FullCalendar
     */
   /**
     * Formatea los bloqueos para FullCalendar
     */
    public static function VerBloqueosFullCalendar($filtros = []) {
        
        $bloqueos = BloqueosM::ObtenerBloqueosM($filtros); 
        $eventos = [];

        foreach ($bloqueos as $b) {
            
            // --- INICIO DE LA MODIFICACIÓN ---
            // Preparamos los datos que el JS necesitará
            $doctor_nombre = 'Dr(a). ' . htmlspecialchars($b['nombre'] ?? '') . ' ' . htmlspecialchars($b['apellido'] ?? 'N/A');
            
            $eventos[] = [
                'id' => $b['id'], 
                'title' => 'BLOQUEO - ' . $doctor_nombre, // Título simple
                'start' => $b['inicio'], // Columna 'inicio' de tu BD
                'end' => $b['fin'],     // Columna 'fin' de tu BD
                'color' => '#f56954', // rojo para bloqueos
                'extendedProps' => [
                    'motivo' => htmlspecialchars($b['motivo']),
                    'id_doctor' => $b['id_doctor'],
                    'tipo' => 'bloqueo',
                    
                    // --- DATOS NUEVOS (Estos son los que faltan) ---
                    'doctor_nombre' => $doctor_nombre,
                    'hora_inicio' => date('H:i', strtotime($b['inicio'])),
                    'hora_fin' => date('H:i', strtotime($b['fin']))
                ]
            ];
            // --- FIN DE LA MODIFICACIÓN ---
        }

        return $eventos;
    }

    /**
     * Procesa el formulario del dashboard del doctor para crear un bloqueo.
     * (Llamado desde inicioC.php)
     */
    public function CrearBloqueoDoctorC() {
        
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Doctor') {
            $_SESSION['mensaje_dashboard_doctor'] = "Error: Acción no permitida.";
            $_SESSION['tipo_mensaje_dashboard_doctor'] = "danger";
            return;
        }
        if (empty($_POST['bloqueo_fecha']) || empty($_POST['bloqueo_inicio']) || empty($_POST['bloqueo_fin']) || empty($_POST['bloqueo_motivo'])) {
            $_SESSION['mensaje_dashboard_doctor'] = "Error: Faltan datos (fecha, inicio, fin o motivo).";
            $_SESSION['tipo_mensaje_dashboard_doctor'] = "danger";
            return;
        }
        
        $id_doctor = $_SESSION['id'];
        $inicio = $_POST['bloqueo_fecha'] . ' ' . $_POST['bloqueo_inicio'];
        $fin = $_POST['bloqueo_fecha'] . ' ' . $_POST['bloqueo_fin'];
        $motivo = $_POST['bloqueo_motivo'];
        $creado_por = $_SESSION['id']; 

        try {
            // --- CORRECCIÓN: OBTENER EL ID DEL CONSULTORIO ---
            // El 'id_consultorio' es requerido por la tabla, según tu imagen.
            $doctor_info = DoctoresM::ObtenerDoctorM($id_doctor); 
            if (!$doctor_info || empty($doctor_info['id_consultorio'])) {
                 throw new Exception("Error: No se pudo encontrar el consultorio asignado al doctor.");
            }
            $id_consultorio = $doctor_info['id_consultorio'];
            // --- FIN CORRECCIÓN ---

            // Pasamos el $id_consultorio al método estático
            self::crearBloqueo($id_doctor, $id_consultorio, $inicio, $fin, $motivo, $creado_por);
            
            $_SESSION['mensaje_dashboard_doctor'] = "¡Horario bloqueado correctamente!";
            $_SESSION['tipo_mensaje_dashboard_doctor'] = "success";
        } catch (Exception $e) {
            $_SESSION['mensaje_dashboard_doctor'] = $e->getMessage();
            $_SESSION['tipo_mensaje_dashboard_doctor'] = "danger";
        }
    }

    /**
     * Procesa la llamada AJAX para eliminar un bloqueo.
     * (Llamado desde citasA.php en el 'case eliminarBloqueo')
     */
    public function EliminarBloqueoC() {
        if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'Doctor' && $_SESSION['rol'] !== 'Administrador')) {
            return ['success' => false, 'error' => 'Permiso denegado.'];
        }

        $id_bloqueo = $_POST['id_cita'] ?? 0; 
        if (empty($id_bloqueo)) {
             $id_bloqueo = $_POST['id_bloqueo'] ?? 0;
        }
        if (empty($id_bloqueo)) {
            return ['success' => false, 'error' => 'No se recibió el ID del bloqueo.'];
        }

        $id_bloqueo = str_replace('bloqueo-', '', $id_bloqueo);

        // Asumimos que BloqueosM ya fue corregido para usar 'bloqueos_doctor'
        $resultado = (new BloqueosM())->EliminarBloqueoM($id_bloqueo, $_SESSION['id'], $_SESSION['rol']);

        if ($resultado === true) {
            return ['success' => true, 'message' => 'Bloqueo eliminado.'];
        } else {
            return ['success' => false, 'error' => $resultado]; 
        }
    }
}