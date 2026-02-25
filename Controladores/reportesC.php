<?php
// Controladores/ReportesC.php

// autoload (phpoffice + otras libs)
require_once __DIR__ . '/../vendor/autoload.php';

// modelos
require_once __DIR__ . '/../Modelos/ReportesM.php';

class ReportesC {

    // obtener datos (dashboard)
    public static function obtenerDatosCompletosReporte($fecha_desde, $fecha_hasta, $id_consultorio_filtro = null) {
        $id_consultorio_final = null;
        if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Secretario') {
            $id_consultorio_final = $_SESSION['id_consultorio'];
        } elseif (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Administrador') {
            $id_consultorio_final = $id_consultorio_filtro;
        }

        // crudos
        $datos = [];
        $datos['widgets']               = ReportesM::WidgetsPorConsultorioSeleccion($fecha_desde, $fecha_hasta, $id_consultorio_final);
        $flujo_pacientes_raw            = ReportesM::FlujoPacientesPorPeriodo($fecha_desde, $fecha_hasta, $id_consultorio_final);
        $pacientes_nuevos_raw           = ReportesM::PacientesNuevosVsRecurrentes($fecha_desde, $fecha_hasta, $id_consultorio_final);
        $datos['analisis_pacientes']    = ReportesM::AnalisisDetalladoPacientes($fecha_desde, $fecha_hasta, $id_consultorio_final);
        $datos['analisis_tratamientos'] = ReportesM::AnalisisDetalladoTratamientos($fecha_desde, $fecha_hasta, $id_consultorio_final);
        $datos['horarios_cancel']       = ReportesM::HorariosConMasCancelaciones($fecha_desde, $fecha_hasta, $id_consultorio_final);
        $datos['bloqueos_doctores']     = ReportesM::DoctoresConMasBloqueos($fecha_desde, $fecha_hasta, $id_consultorio_final);

        // formateo flujo
        $labels_flujo = [];
        $data_flujo   = [];
        if (!empty($flujo_pacientes_raw)) {
            foreach ($flujo_pacientes_raw as $item) {
                $labels_flujo[] = date('M Y', strtotime($item['mes'] . '-01'));
                $data_flujo[]   = (int)$item['total'];
            }
        }
        $datos['grafico_flujo_pacientes'] = ['labels' => $labels_flujo, 'data' => $data_flujo];

        // pie pacientes
        $datos['grafico_pie_pacientes'] = [
            'labels' => ['Pacientes Nuevos', 'Pacientes Recurrentes'],
            'data'   => [(int)($pacientes_nuevos_raw['nuevos'] ?? 0), (int)($pacientes_nuevos_raw['recurrentes'] ?? 0)]
        ];

        return $datos;
    }

    
   // generar reporte manual (admin)
    public function GenerarReporteManualC($url_redirect = 'reportes-admin') {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador' || !isset($_POST['mes_anio'])) {
            $_SESSION['mensaje_reportes'] = "Acción no permitida o faltan datos.";
            $_SESSION['tipo_mensaje_reportes'] = "danger";
            echo '<script>window.location="' . BASE_URL . $url_redirect . '";</script>';
            exit();
        }

        $mes_anio = $_POST['mes_anio'];
        if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $mes_anio, $m)) {
            $_SESSION['mensaje_reportes'] = "Formato de fecha inválido. Use AAAA-MM.";
            $_SESSION['tipo_mensaje_reportes'] = "danger";
            echo '<script>window.location="' . BASE_URL . $url_redirect . '";</script>';
            exit();
        }

        $anio = intval($m[1]);
        $mes  = intval($m[2]);

        try {
            $datos_generados = ReportesM::GenerarReporteMensual($anio, $mes);

            if ($datos_generados === false) {
                throw new Exception("Error interno al generar el reporte. Revisa los logs.");
            }

            if (empty($datos_generados)) {
                $_SESSION['mensaje_reportes'] = "No se encontraron citas para " . DateTime::createFromFormat('!m', $mes)->format('F') . " de $anio.";
                $_SESSION['tipo_mensaje_reportes'] = "warning";

                if (isset($_POST['exportar_excel'])) {
                    $this->ExportarAExcel(self::obtenerDatosCompletosReporte(date("$anio-$mes-01"), date("$anio-$mes-t"), null), "$anio-$mes");
                    // ExportarAExcel hace exit()
                }

                echo '<script>window.location="' . BASE_URL . $url_redirect . '?anio=' . $anio . '";</script>';
                exit();
            }

            $_SESSION['mensaje_reportes'] = "¡Reporte para " . DateTime::createFromFormat('!m', $mes)->format('F') . " de $anio generado/actualizado con éxito!";
            $_SESSION['tipo_mensaje_reportes'] = "success";

            if (isset($_POST['exportar_excel'])) {
                $this->ExportarAExcel(self::obtenerDatosCompletosReporte(date("$anio-$mes-01"), date("$anio-$mes-t"), null), "$anio-$mes");
            }

            echo '<script>window.location="' . BASE_URL . $url_redirect . '?anio=' . $anio . '";</script>';
            exit();

        } catch (Exception $e) {
            error_log("ReportesC::GenerarReporteManualC - " . $e->getMessage());
            $_SESSION['mensaje_reportes'] = "Error al generar el reporte: " . $e->getMessage();
            $_SESSION['tipo_mensaje_reportes'] = "danger";
            echo '<script>window.location="' . BASE_URL . $url_redirect . '";</script>';
            exit();
        }
    }

    public static function ExportarAExcel(array $datos_reportes, string $mes_anio)
    {
        if (empty($datos_reportes)) {
            exit("Error: No hay datos para exportar.");
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()->setCreator("Sistema Clínico")->setTitle("Reporte Ejecutivo");

        $estiloTitulo = [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2C3E50']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];
        $estiloCabecera = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEAEDED']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];
        $bordeThick = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFBDC3C7']]]];

        // ====================================================================
        // HOJA 1: DASHBOARD EJECUTIVO
        // ====================================================================
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dashboard Ejecutivo');

        $sheet->setCellValue('A1', 'REPORTE EJECUTIVO DE RENDIMIENTO CLÍNICO');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1:D1')->applyFromArray($estiloTitulo);
        $sheet->setCellValue('A2', 'Período analizado: ' . $mes_anio);
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        $widgets = $datos_reportes['widgets'] ?? ['total_citas'=>0,'citas_completadas'=>0,'citas_canceladas'=>0,'tasa_cancelacion'=>0];
        $tasa_decimal = (float)($widgets['tasa_cancelacion'] ?? 0) / 100.0;

        $sheet->setCellValue('A4', 'Métricas Principales');
        $sheet->mergeCells('A4:B4');
        $sheet->getStyle('A4:B4')->applyFromArray($estiloTitulo);
        $sheet->getStyle('A4:B4')->getFill()->getStartColor()->setARGB('FF2980B9');

        $sheet->fromArray([
            ['Citas Totales', (int)($widgets['total_citas'] ?? 0)],
            ['Citas Completadas', (int)($widgets['citas_completadas'] ?? 0)],
            ['Citas Canceladas', (int)($widgets['citas_canceladas'] ?? 0)],
            ['Tasa de Cancelación', $tasa_decimal]
        ], null, 'A5');
        
        $sheet->getStyle('B8')->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle('A5:B8')->applyFromArray($bordeThick);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        // ====================================================================
        // HOJA OCULTA PARA GRÁFICOS
        // ====================================================================
        $sheetData = $spreadsheet->createSheet();
        $sheetData->setTitle('DataGraficos');
        $sheetData->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

        $flujo = $datos_reportes['grafico_flujo_pacientes'] ?? ['labels'=>[], 'data'=>[]];
        $sheetData->setCellValue('A1', 'Mes'); $sheetData->setCellValue('B1', 'Pacientes');
        $r = 2;
        foreach ($flujo['labels'] as $i => $label) {
            $sheetData->setCellValue("A{$r}", $label); $sheetData->setCellValue("B{$r}", (int)($flujo['data'][$i] ?? 0)); $r++;
        }
        $rowCountFlujo = max(0, $r - 2);

        $pie = $datos_reportes['grafico_pie_pacientes'] ?? ['labels'=>[], 'data'=>[]];
        $sheetData->setCellValue('D1', 'Tipo'); $sheetData->setCellValue('E1', 'Cantidad');
        $r2 = 2;
        foreach ($pie['labels'] as $i => $lab) {
            $sheetData->setCellValue("D{$r2}", $lab); $sheetData->setCellValue("E{$r2}", (int)($pie['data'][$i] ?? 0)); $r2++;
        }
        $pieCount = max(0, $r2 - 2);

        // GRAFICOS
        if ($rowCountFlujo > 0) {
            $series1 = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(\PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_LINECHART, \PhpOffice\PhpSpreadsheet\Chart\DataSeries::GROUPING_STANDARD, range(0, 0), [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', "DataGraficos!\$B\$1", null, 1)], [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', "DataGraficos!\$A\$2:\$A\$" . (1 + $rowCountFlujo), null, $rowCountFlujo)], [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number', "DataGraficos!\$B\$2:\$B\$" . (1 + $rowCountFlujo), null, $rowCountFlujo)]);
            $chart1 = new \PhpOffice\PhpSpreadsheet\Chart\Chart('flujo', new \PhpOffice\PhpSpreadsheet\Chart\Title('Flujo de Pacientes'), new \PhpOffice\PhpSpreadsheet\Chart\Legend(\PhpOffice\PhpSpreadsheet\Chart\Legend::POSITION_BOTTOM, null, false), new \PhpOffice\PhpSpreadsheet\Chart\PlotArea(null, [$series1]));
            $chart1->setTopLeftPosition('D4'); $chart1->setBottomRightPosition('K16');
            $sheet->addChart($chart1);
        }
        if ($pieCount > 0 && array_sum($pie['data']) > 0) {
            $series2 = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(\PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_PIECHART, null, range(0, 0), [], [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', "DataGraficos!\$D\$2:\$D\$" . (1 + $pieCount), null, $pieCount)], [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number', "DataGraficos!\$E\$2:\$E\$" . (1 + $pieCount), null, $pieCount)]);
            $chart2 = new \PhpOffice\PhpSpreadsheet\Chart\Chart('pie', new \PhpOffice\PhpSpreadsheet\Chart\Title('Nuevos vs Recurrentes'), new \PhpOffice\PhpSpreadsheet\Chart\Legend(\PhpOffice\PhpSpreadsheet\Chart\Legend::POSITION_RIGHT, null, false), new \PhpOffice\PhpSpreadsheet\Chart\PlotArea(null, [$series2]));
            $chart2->setTopLeftPosition('D18'); $chart2->setBottomRightPosition('K30');
            $sheet->addChart($chart2);
        }

        // ====================================================================
        // HOJA 2: PACIENTES
        // ====================================================================
        $sheetPac = $spreadsheet->createSheet(); $sheetPac->setTitle('Pacientes');
        $sheetPac->setCellValue('A1', 'ANÁLISIS DE PACIENTES'); $sheetPac->mergeCells('A1:E1'); $sheetPac->getStyle('A1:E1')->applyFromArray($estiloTitulo);
        $sheetPac->fromArray(['Paciente', 'Visitas Completadas', 'Citas Canceladas', 'Tasa Cancelación', 'Tratamiento Frecuente'], null, 'A2');
        $sheetPac->getStyle('A2:E2')->applyFromArray($estiloCabecera);

        $data_pac = [];
        foreach ($datos_reportes['analisis_pacientes'] ?? [] as $p) {
            $data_pac[] = [($p['paciente'] ?? 'N/A').(isset($p['id_paciente']) ? " (ID:{$p['id_paciente']})" : ''), (int)($p['visitas_completadas'] ?? 0), (int)($p['citas_canceladas'] ?? 0), (float)($p['tasa_cancelacion'] ?? 0) / 100.0, $p['tratamiento_frecuente'] ?? 'N/A'];
        }
        if (!empty($data_pac)) {
            $sheetPac->fromArray($data_pac, null, 'A3');
            $sheetPac->getStyle('D3:D'.(count($data_pac)+2))->getNumberFormat()->setFormatCode('0.00%');
            $sheetPac->getStyle('A2:E'.(count($data_pac)+2))->applyFromArray($bordeThick);
        }
        foreach(range('A','E') as $col) $sheetPac->getColumnDimension($col)->setAutoSize(true);

        // ====================================================================
        // HOJA 3: TRATAMIENTOS
        // ====================================================================
        $sheetTrat = $spreadsheet->createSheet(); $sheetTrat->setTitle('Tratamientos');
        $sheetTrat->setCellValue('A1', 'TRATAMIENTOS MÁS REALIZADOS'); $sheetTrat->mergeCells('A1:C1'); $sheetTrat->getStyle('A1:C1')->applyFromArray($estiloTitulo);
        $sheetTrat->getStyle('A1:C1')->getFill()->getStartColor()->setARGB('FF27AE60'); // Verde
        $sheetTrat->fromArray(['Tratamiento', 'Cantidad de Usos', 'Doctor Principal'], null, 'A2');
        $sheetTrat->getStyle('A2:C2')->applyFromArray($estiloCabecera);

        $data_trat = [];
        foreach ($datos_reportes['analisis_tratamientos'] ?? [] as $t) {
            $data_trat[] = [$t['tratamiento'] ?? 'N/A', (int)($t['cantidad_usos'] ?? 0), $t['doctor_principal'] ?? 'Varios'];
        }
        if (!empty($data_trat)) {
            $sheetTrat->fromArray($data_trat, null, 'A3');
            $sheetTrat->getStyle('A2:C'.(count($data_trat)+2))->applyFromArray($bordeThick);
        }
        foreach(range('A','C') as $col) $sheetTrat->getColumnDimension($col)->setAutoSize(true);

        // ====================================================================
        // HOJA 4: HORARIOS
        // ====================================================================
        $sheetHor = $spreadsheet->createSheet(); $sheetHor->setTitle('Cancelaciones por Hora');
        $sheetHor->setCellValue('A1', 'HORARIOS CON MÁS CANCELACIONES'); $sheetHor->mergeCells('A1:B1'); $sheetHor->getStyle('A1:B1')->applyFromArray($estiloTitulo);
        $sheetHor->getStyle('A1:B1')->getFill()->getStartColor()->setARGB('FFE67E22'); // Naranja
        $sheetHor->fromArray(['Hora', 'Total Cancelaciones'], null, 'A2');
        $sheetHor->getStyle('A2:B2')->applyFromArray($estiloCabecera);

        $data_hor = [];
        foreach ($datos_reportes['horarios_cancel'] ?? [] as $h) {
            $data_hor[] = [str_pad($h['hora'] ?? 0, 2, '0', STR_PAD_LEFT).':00 hrs', (int)($h['total'] ?? 0)];
        }
        if (!empty($data_hor)) {
            $sheetHor->fromArray($data_hor, null, 'A3');
            $sheetHor->getStyle('A2:B'.(count($data_hor)+2))->applyFromArray($bordeThick);
        }
        foreach(range('A','B') as $col) $sheetHor->getColumnDimension($col)->setAutoSize(true);

        // ====================================================================
        // HOJA 5: DOCTORES
        // ====================================================================
        $sheetDoc = $spreadsheet->createSheet(); $sheetDoc->setTitle('Bloqueos por Doctor');
        $sheetDoc->setCellValue('A1', 'DOCTORES CON MÁS BLOQUEOS'); $sheetDoc->mergeCells('A1:B1'); $sheetDoc->getStyle('A1:B1')->applyFromArray($estiloTitulo);
        $sheetDoc->getStyle('A1:B1')->getFill()->getStartColor()->setARGB('FF8E44AD'); // Morado
        $sheetDoc->fromArray(['Doctor', 'Total Bloqueos'], null, 'A2');
        $sheetDoc->getStyle('A2:B2')->applyFromArray($estiloCabecera);

        $data_doc = [];
        foreach ($datos_reportes['bloqueos_doctores'] ?? [] as $d) {
            $data_doc[] = [$d['doctor'] ?? 'N/A', (int)($d['total'] ?? 0)];
        }
        if (!empty($data_doc)) {
            $sheetDoc->fromArray($data_doc, null, 'A3');
            $sheetDoc->getStyle('A2:B'.(count($data_doc)+2))->applyFromArray($bordeThick);
        }
        foreach(range('A','B') as $col) $sheetDoc->getColumnDimension($col)->setAutoSize(true);

        // ====================================================================
        // DESCARGA
        // ====================================================================
        $spreadsheet->setActiveSheetIndex(0);
        if (ob_get_length()) ob_clean(); 
        
        $filename = 'Reporte_Clinica_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $writer->save('php://output');
        exit();
    }


    public function manejarExportarExcelGET()
{
    if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Secretario'])) {
        exit("Acceso denegado.");
    }

    $mes = $_GET['mes'] ?? null;
    $fecha_desde = $_GET['fecha_desde'] ?? null;
    $fecha_hasta = $_GET['fecha_hasta'] ?? null;
    $id_consultorio = $_GET['id_consultorio'] ?? null;

    if ($id_consultorio === '') {
        $id_consultorio = null;
    }

    // EXPORTAR POR MES
    if ($mes) {
        if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $mes)) {
            exit("Formato de mes inválido.");
        }

        $fecha_desde = $mes . '-01';
        $fecha_hasta = date('Y-m-t', strtotime($fecha_desde));
        $label = "Mes " . $mes;
    }

    // EXPORTAR POR RANGO
    elseif ($fecha_desde && $fecha_hasta) {
        $label = date('d/m/Y', strtotime($fecha_desde)) . ' al ' . date('d/m/Y', strtotime($fecha_hasta));
    }

    else {
        exit("Faltan parámetros para exportar.");
    }

    $datos = self::obtenerDatosCompletosReporte($fecha_desde, $fecha_hasta, $id_consultorio);

    if (empty($datos)) {
        exit("No hay datos para exportar.");
    }

    self::ExportarAExcel($datos, $label);
}
} // class
