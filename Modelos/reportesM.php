<?php
// En Modelos/ReportesM.php (VERSIÓN FINAL Y COMPLETA CON TODOS LOS MÉTODOS)

require_once "ConexionBD.php";


// ===================================
// USO DE PhpSpreadsheet NAMESPACES
// ===================================
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class ReportesM {

    // --- MÉTODOS PARA EL DASHBOARD DE REPORTES EN VIVO ---

    public static function WidgetsPorConsultorioSeleccion($desde, $hasta, $id_consultorio = null) {
        try {
            $sql = "SELECT 
                        COUNT(c.id) as total_citas,
                        SUM(CASE WHEN c.estado = 'Completada' THEN 1 ELSE 0 END) as citas_completadas,
                        SUM(CASE WHEN c.estado = 'Cancelada' THEN 1 ELSE 0 END) as citas_canceladas
                    FROM citas c
                    WHERE DATE(c.inicio) BETWEEN :desde AND :hasta";
            
            $params = [':desde' => $desde, ':hasta' => $hasta];
            if (!empty($id_consultorio)) {
                $sql .= " AND c.id_consultorio = :id_consultorio";
                $params[':id_consultorio'] = $id_consultorio;
            }

            $stmt = ConexionBD::getInstancia()->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            $data['total_citas'] = (int)($data['total_citas'] ?? 0);
            $data['citas_completadas'] = (int)($data['citas_completadas'] ?? 0);
            $data['citas_canceladas'] = (int)($data['citas_canceladas'] ?? 0);
            $data['tasa_cancelacion'] = ($data['total_citas'] > 0) ? round(($data['citas_canceladas'] / $data['total_citas']) * 100, 2) : 0;
            
            return $data;
        } catch (PDOException $e) {
            error_log("Error en WidgetsPorConsultorioSeleccion: " . $e->getMessage());
            return ['total_citas' => 0, 'citas_completadas' => 0, 'citas_canceladas' => 0, 'tasa_cancelacion' => 0];
        }
    }

    public static function FlujoPacientesPorPeriodo($desde, $hasta, $id_consultorio = null) {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(c.inicio, '%Y-%m') as mes, 
                        COUNT(DISTINCT c.id_paciente) as total
                    FROM citas c
                    WHERE c.estado = 'Completada' AND DATE(c.inicio) BETWEEN :desde AND :hasta";
            
            $params = [':desde' => $desde, ':hasta' => $hasta];
            if (!empty($id_consultorio)) {
                $sql .= " AND c.id_consultorio = :id_consultorio";
                $params[':id_consultorio'] = $id_consultorio;
            }
            $sql .= " GROUP BY mes ORDER BY mes ASC";

            $stmt = ConexionBD::getInstancia()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en FlujoPacientesPorPeriodo: " . $e->getMessage());
            return [];
        }
    }

    public static function PacientesNuevosVsRecurrentes($desde, $hasta, $id_consultorio = null) {
        try {
            $sql = "SELECT
                        SUM(CASE WHEN primera_cita_paciente.fecha_primera_cita >= :desde THEN 1 ELSE 0 END) as nuevos,
                        SUM(CASE WHEN primera_cita_paciente.fecha_primera_cita < :desde THEN 1 ELSE 0 END) as recurrentes
                    FROM citas c
                    JOIN (
                        SELECT id_paciente, MIN(DATE(inicio)) as fecha_primera_cita
                        FROM citas
                        GROUP BY id_paciente
                    ) as primera_cita_paciente ON c.id_paciente = primera_cita_paciente.id_paciente
                    WHERE c.estado = 'Completada' AND DATE(c.inicio) BETWEEN :desde AND :hasta";
            
            $params = [':desde' => $desde, ':hasta' => $hasta];
            if (!empty($id_consultorio)) {
                $sql .= " AND c.id_consultorio = :id_consultorio";
                $params[':id_consultorio'] = $id_consultorio;
            }

            $stmt = ConexionBD::getInstancia()->prepare($sql);
            $stmt->execute($params);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'nuevos' => (int)($resultado['nuevos'] ?? 0),
                'recurrentes' => (int)($resultado['recurrentes'] ?? 0)
            ];
        } catch (PDOException $e) {
            error_log("Error en PacientesNuevosVsRecurrentes: " . $e->getMessage());
            return ['nuevos' => 0, 'recurrentes' => 0];
        }
    }

    public static function AnalisisDetalladoPacientes($desde, $hasta, $id_consultorio = null) {
        try {
            $sql = "SELECT 
                        p.id as id_paciente,
                        c.nyaP as paciente,
                        COUNT(c.id) as total_citas,
                        SUM(CASE WHEN c.estado = 'Completada' THEN 1 ELSE 0 END) as visitas_completadas,
                        SUM(CASE WHEN c.estado = 'Cancelada' THEN 1 ELSE 0 END) as citas_canceladas,
                        IF(COUNT(c.id) > 0, (SUM(CASE WHEN c.estado = 'Cancelada' THEN 1 ELSE 0 END) / COUNT(c.id)) * 100, 0) as tasa_cancelacion,
                        (SELECT t.nombre 
                         FROM cita_tratamiento ct 
                         JOIN tratamientos t ON ct.id_tratamiento = t.id 
                         WHERE ct.id_cita IN (SELECT id FROM citas WHERE id_paciente = p.id AND DATE(inicio) BETWEEN :desde_sub AND :hasta_sub)
                         GROUP BY ct.id_tratamiento 
                         ORDER BY COUNT(*) DESC 
                         LIMIT 1) as tratamiento_frecuente
                    FROM pacientes p
                    JOIN citas c ON p.id = c.id_paciente
                    WHERE DATE(c.inicio) BETWEEN :desde AND :hasta";
            
            $params = [':desde' => $desde, ':hasta' => $hasta, ':desde_sub' => $desde, ':hasta_sub' => $hasta];
            if (!empty($id_consultorio)) {
                $sql .= " AND c.id_consultorio = :id_consultorio";
                $params[':id_consultorio'] = $id_consultorio;
            }
            $sql .= " GROUP BY p.id ORDER BY visitas_completadas DESC, tasa_cancelacion ASC";

            $stmt = ConexionBD::getInstancia()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en AnalisisDetalladoPacientes: " . $e->getMessage());
            return [];
        }
    }

    public static function AnalisisDetalladoTratamientos($desde, $hasta, $id_consultorio = null) {
        try {
            $sql = "SELECT 
                        t.nombre as tratamiento,
                        COUNT(ct.id) as cantidad_usos,
                        (SELECT CONCAT(d.nombre, ' ', d.apellido) 
                         FROM citas c_sub
                         JOIN doctores d ON c_sub.id_doctor = d.id
                         JOIN cita_tratamiento ct_sub ON c_sub.id = ct_sub.id_cita
                         WHERE ct_sub.id_tratamiento = t.id AND DATE(c_sub.inicio) BETWEEN :desde AND :hasta
                         GROUP BY c_sub.id_doctor
                         ORDER BY COUNT(*) DESC
                         LIMIT 1) as doctor_principal
                    FROM tratamientos t
                    JOIN cita_tratamiento ct ON t.id = ct.id_tratamiento
                    JOIN citas c ON ct.id_cita = c.id
                    WHERE DATE(c.inicio) BETWEEN :desde AND :hasta";
            
            $params = [':desde' => $desde, ':hasta' => $hasta];
            if (!empty($id_consultorio)) {
                $sql .= " AND c.id_consultorio = :id_consultorio";
                $params[':id_consultorio'] = $id_consultorio;
            }
            $sql .= " GROUP BY t.id ORDER BY cantidad_usos DESC";

            $stmt = ConexionBD::getInstancia()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en AnalisisDetalladoTratamientos: " . $e->getMessage());
            return [];
        }
    }

    public static function HorariosConMasCancelaciones($desde, $hasta, $id_consultorio = null) {
        try {
            $sql = "SELECT HOUR(c.inicio) as hora, COUNT(*) as total
                    FROM citas c
                    WHERE c.estado = 'Cancelada' AND DATE(c.inicio) BETWEEN :desde AND :hasta";
            $params = [':desde' => $desde, ':hasta' => $hasta];
            if (!empty($id_consultorio)) {
                $sql .= " AND c.id_consultorio = :id_consultorio";
                $params[':id_consultorio'] = $id_consultorio;
            }
            $sql .= " GROUP BY hora ORDER BY total DESC LIMIT 5";
            $stmt = ConexionBD::getInstancia()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en HorariosConMasCancelaciones: " . $e->getMessage());
            return [];
        }
    }

 public static function DoctoresConMasBloqueos($desde, $hasta, $id_consultorio = null) {
        try {
            
            $sql = "SELECT CONCAT(d.nombre, ' ', d.apellido) as doctor, COUNT(*) as total
                    FROM bloqueos_doctor b
                    JOIN doctores d ON b.id_doctor = d.id
                    WHERE DATE(b.inicio) BETWEEN :desde AND :hasta";
            
            $params = [':desde' => $desde, ':hasta' => $hasta];
            
            if (!empty($id_consultorio)) {
                $sql .= " AND d.id_consultorio = :id_consultorio";
                $params[':id_consultorio'] = $id_consultorio;
            }
            
            $sql .= " GROUP BY b.id_doctor ORDER BY total DESC LIMIT 5";
            
            $stmt = ConexionBD::getInstancia()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en DoctoresConMasBloqueos: " . $e->getMessage());
            return [];
        }
    }

    
    public static function ObtenerListaConsultorios() {
        try {
            $stmt = ConexionBD::getInstancia()->prepare("SELECT id, nombre FROM consultorios ORDER BY nombre ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ObtenerListaConsultorios: " . $e->getMessage());
            return [];
        }
    }

    // --- MÉTODO PARA EL MODAL DE PERFIL ANALÍTICO (DRILL-DOWN) ---
    public static function ObtenerPerfilAnaliticoPaciente($id_paciente, $desde, $hasta) {
        $pdo = ConexionBD::getInstancia();
        $perfil = [];

        try {
            // 1. Datos básicos del paciente
            $stmt_paciente = $pdo->prepare("SELECT id, CONCAT(nombre, ' ', apellido) as nyaP, correo, telefono FROM pacientes WHERE id = :id");
            $stmt_paciente->execute([':id' => $id_paciente]);
            $perfil['info_basica'] = $stmt_paciente->fetch(PDO::FETCH_ASSOC);

            if (!$perfil['info_basica']) return false;

            // 2. Resumen de actividad en el período
            $stmt_resumen = $pdo->prepare("SELECT COUNT(*) as total_citas, SUM(CASE WHEN estado = 'Completada' THEN 1 ELSE 0 END) as completadas, SUM(CASE WHEN estado = 'Cancelada' THEN 1 ELSE 0 END) as canceladas FROM citas WHERE id_paciente = :id AND DATE(inicio) BETWEEN :desde AND :hasta");
            $stmt_resumen->execute([':id' => $id_paciente, ':desde' => $desde, ':hasta' => $hasta]);
            $perfil['resumen_actividad'] = $stmt_resumen->fetch(PDO::FETCH_ASSOC);

            // 3. Doctores que lo han atendido
            $stmt_doctores = $pdo->prepare(
                "SELECT CONCAT(d.nombre, ' ', d.apellido) as doctor, COUNT(*) as total 
                 FROM citas c JOIN doctores d ON c.id_doctor = d.id 
                 WHERE c.id_paciente = :id AND c.estado = 'Completada' AND DATE(c.inicio) BETWEEN :desde AND :hasta 
                 GROUP BY c.id_doctor ORDER BY total DESC"
            );
            $stmt_doctores->execute([':id' => $id_paciente, ':desde' => $desde, ':hasta' => $hasta]);
            $perfil['doctores_frecuentes'] = $stmt_doctores->fetchAll(PDO::FETCH_ASSOC);

            // 4. Tratamientos recibidos
            $stmt_tratamientos = $pdo->prepare(
                "SELECT t.nombre as tratamiento, COUNT(*) as total 
                 FROM cita_tratamiento ct JOIN tratamientos t ON ct.id_tratamiento = t.id JOIN citas c ON ct.id_cita = c.id 
                 WHERE c.id_paciente = :id AND c.estado = 'Completada' AND DATE(c.inicio) BETWEEN :desde AND :hasta 
                 GROUP BY ct.id_tratamiento ORDER BY total DESC"
            );
            $stmt_tratamientos->execute([':id' => $id_paciente, ':desde' => $desde, ':hasta' => $hasta]);
            $perfil['tratamientos_frecuentes'] = $stmt_tratamientos->fetchAll(PDO::FETCH_ASSOC);
            
            // 5. Historial de citas
            $stmt_historial = $pdo->prepare(
                "SELECT DATE(inicio) as fecha, motivo, c.estado, CONCAT(d.nombre, ' ', d.apellido) as doctor 
                 FROM citas c JOIN doctores d ON c.id_doctor = d.id 
                 WHERE c.id_paciente = :id AND DATE(c.inicio) BETWEEN :desde AND :hasta 
                 ORDER BY inicio DESC"
            );
            $stmt_historial->execute([':id' => $id_paciente, ':desde' => $desde, ':hasta' => $hasta]);
            $perfil['historial_reciente'] = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);

            return $perfil;
        } catch (PDOException $e) {
            error_log("Error en ObtenerPerfilAnaliticoPaciente: " . $e->getMessage());
            return false;
        }
    }

    // Generar reportes

    public static function ObtenerReportesMensualesGuardados($anio) {
        try {
            $sql = "SELECT * FROM reportes_mensuales 
                    WHERE anio = :anio 
                    ORDER BY mes DESC, nombre_consultorio ASC";
            
            $stmt = ConexionBD::getInstancia()->prepare($sql);
            $stmt->execute([':anio' => $anio]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en ObtenerReportesMensualesGuardados: " . $e->getMessage());
            // Devolver un array vacío en caso de error para no romper la vista.
            return [];
        }
    }

    public static function GenerarReporteMensual($anio, $mes) {

        // ¡ATENCIÓN! Usar ConexionBD::getInstancia() o un método que retorne la instancia de PDO. 
        // He cambiado cBD() por getInstancia()->prepare(...) asumiendo que es el método correcto.
        $pdo = ConexionBD::getInstancia()->prepare("
            SELECT 
                c.id_consultorio,
                co.nombre AS nombre_consultorio,
                SUM(CASE WHEN c.estado = 'Completada' THEN 1 ELSE 0 END) AS citas_completadas,
                SUM(CASE WHEN c.estado = 'Cancelada' THEN 1 ELSE 0 END) AS citas_canceladas,
                COUNT(*) AS total_citas
            FROM citas c
            INNER JOIN consultorios co ON c.id_consultorio = co.id
            WHERE YEAR(c.inicio) = :anio AND MONTH(c.inicio) = :mes
            GROUP BY c.id_consultorio, co.nombre
        ");
        
        // ¡CORRECCIÓN! Usé c.inicio en lugar de c.fecha_cita, ya que tus otros métodos usan 'inicio'.
        // También cambié el join de id_consultorio a id en la tabla consultorios, que es el patrón en tus otros métodos.
        $pdo->execute([':anio' => $anio, ':mes' => $mes]);
        $resultados = $pdo->fetchAll(PDO::FETCH_ASSOC);

        if (!$resultados) {
            return [];
        }

        // Insertar o actualizar los resultados en reportes_mensuales
        $insert_stmt = ConexionBD::getInstancia()->prepare("
            INSERT INTO reportes_mensuales 
            (anio, mes, id_consultorio, nombre_consultorio, citas_completadas, citas_canceladas, total_citas)
            VALUES (:anio, :mes, :id_consultorio, :nombre, :completadas, :canceladas, :total)
            ON DUPLICATE KEY UPDATE
                citas_completadas = VALUES(citas_completadas),
                citas_canceladas = VALUES(citas_canceladas),
                total_citas = VALUES(total_citas)
        ");

        foreach ($resultados as $fila) {
            $insert_stmt->execute([
                ':anio' => $anio,
                ':mes' => $mes,
                ':id_consultorio' => $fila['id_consultorio'],
                ':nombre' => $fila['nombre_consultorio'],
                ':completadas' => $fila['citas_completadas'],
                ':canceladas' => $fila['citas_canceladas'],
                ':total' => $fila['total_citas']
            ]);
        }

        return $resultados;
    }

    // =============================
    //   EXPORTAR A EXCEL (FINAL)
    // =============================
    /**
     * Exporta datos de resumen (clave => valor) a un archivo Excel con un gráfico de barras.
     * @param array $datos Array asociativo de métricas (ej. ['total_citas' => 50, 'citas_completadas' => 45])
     * @param string $mes_anio Cadena para el título del gráfico y nombre del archivo (ej. "Noviembre 2025")
     */
   public static function ExportarAExcel($paquete, $titulo_reporte)
{
    if (empty($paquete)) {
        exit("Error: No hay datos para exportar.");
    }

    $spreadsheet = new Spreadsheet();

    // ============================================================
    // HOJA 1: RESUMEN
    // ============================================================
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Resumen");

    $sheet->setCellValue('A1', 'Métrica');
    $sheet->setCellValue('B1', 'Valor');
    $sheet->getStyle('A1:B1')->getFont()->setBold(true);

    $fila = 2;
    foreach ($paquete['resumen'] as $k => $v) {
        $sheet->setCellValue("A$fila", ucfirst(str_replace('_',' ', $k)));
        $sheet->setCellValue("B$fila", $v);
        $fila++;
    }
    $fin_datos = $fila - 1;
    $num_puntos = $fin_datos - 1;

    // --------- Gráfico ---------
    $labels = [ new DataSeriesValues('String', "Resumen!A2:A$fin_datos", null, $num_puntos) ];
    $values = [ new DataSeriesValues('Number', "Resumen!B2:B$fin_datos", null, $num_puntos) ];

    $series = new DataSeries(
        DataSeries::TYPE_BARCHART,
        DataSeries::GROUPING_CLUSTERED,
        range(0, count($values)-1),
        $labels,
        [],
        $values
    );

    $plotArea = new PlotArea(null, [$series]);
    $chart = new Chart(
        'Resumen Chart',
        new Title("Resumen – $titulo_reporte"),
        new Legend(Legend::POSITION_RIGHT, null, false),
        $plotArea
    );

    $chart->setTopLeftPosition('D2');
    $chart->setBottomRightPosition('L20');
    $sheet->addChart($chart);

    // ============================================================
    // HOJA 2: FLUJO MENSUAL
    // ============================================================
    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle("Flujo mensual");

    $sheet2->setCellValue('A1', 'Mes');
    $sheet2->setCellValue('B1', 'Pacientes');

    $fila = 2;
    foreach ($paquete['flujo'] as $row) {
        $sheet2->setCellValue("A$fila", $row['mes']);
        $sheet2->setCellValue("B$fila", $row['total']);
        $fila++;
    }

    $fin = $fila - 1;
    $num = $fin - 1;

    // --------- Gráfico de flujo ---------
    $labels = [ new DataSeriesValues('String', "Flujo mensual!A2:A$fin", null, $num) ];
    $values = [ new DataSeriesValues('Number', "Flujo mensual!B2:B$fin", null, $num) ];

    $series = new DataSeries(
        DataSeries::TYPE_LINECHART,
        DataSeries::GROUPING_STANDARD,
        range(0, count($values)-1),
        $labels,
        [],
        $values
    );

    $plot = new PlotArea(null, [$series]);
    $chart = new Chart(
        'Flujo Chart',
        new Title("Flujo de Pacientes – $titulo_reporte"),
        new Legend(Legend::POSITION_RIGHT, null, false),
        $plot
    );

    $chart->setTopLeftPosition('D2');
    $chart->setBottomRightPosition('M20');
    $sheet2->addChart($chart);

    // ============================================================
    // HOJA 3: DETALLE PACIENTES
    // ============================================================
    $sheet3 = $spreadsheet->createSheet();
    $sheet3->setTitle("Pacientes");

    $sheet3->fromArray(
        array_merge([array_keys($paquete['detalle_pacientes'][0] ?? [])],
                    array_map('array_values', $paquete['detalle_pacientes'])),
        null,
        "A1"
    );

    // ============================================================
    // HOJA 4: DETALLE TRATAMIENTOS
    // ============================================================
    $sheet4 = $spreadsheet->createSheet();
    $sheet4->setTitle("Tratamientos");

    $sheet4->fromArray(
        array_merge([array_keys($paquete['detalle_tratamientos'][0] ?? [])],
                    array_map('array_values', $paquete['detalle_tratamientos'])),
        null,
        "A1"
    );

    // ============================================================
    // HOJA 5: HORARIOS CON MÁS CANCELACIONES
    // ============================================================
    $sheet5 = $spreadsheet->createSheet();
    $sheet5->setTitle("Horarios cancelaciones");

    $sheet5->fromArray(
        array_merge([array_keys($paquete['horarios_cancel'][0] ?? [])],
                    array_map('array_values', $paquete['horarios_cancel'])),
        null,
        "A1"
    );

    // ============================================================
    // HOJA 6: DOCTORES CON MÁS BLOQUEOS
    // ============================================================
    $sheet6 = $spreadsheet->createSheet();
    $sheet6->setTitle("Doctores bloqueos");

    $sheet6->fromArray(
        array_merge([array_keys($paquete['doctores_bloqueos'][0] ?? [])],
                    array_map('array_values', $paquete['doctores_bloqueos'])),
        null,
        "A1"
    );

    // ============================================================
    // DESCARGA DEL ARCHIVO
    // ============================================================
    $nombre = "Reporte_$titulo_reporte.xlsx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$nombre\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->setIncludeCharts(true);
    $writer->save('php://output');
    exit();
}
    
}

?>