<?php
// En Ajax/citasA.php (VERSIÓN FINAL Y CORREGIDA)

// 1. Cargar el entorno global (sesión, autoloader, config).
require_once __DIR__ . '/../loader.php';

// 2. Cabecera JSON (solo si no se enviaron antes)
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

// 3. Seguridad de roles.
$rolesPermitidos = ['Administrador', 'Secretario', 'Doctor'];
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $rolesPermitidos)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

// -----------------------------------------------------------------------------
// 4. Función de seguridad para secretarios
// -----------------------------------------------------------------------------
function verificarPermisoCita($id_cita)
{
    if ($_SESSION['rol'] === 'Secretario' && isset($_SESSION['id_consultorio'])) {

        $cita = (new CitasM())->ObtenerCitaM($id_cita);

        if (!$cita || $cita['id_consultorio'] != $_SESSION['id_consultorio']) {
            echo json_encode([
                'success' => false,
                'error'   => 'Permiso denegado sobre esta cita.'
            ]);
            exit();
        }
    }
}

// -----------------------------------------------------------------------------
// 5. Acción solicitada
// -----------------------------------------------------------------------------
$accion = $_REQUEST['action'] ?? '';

try {

    switch ($accion) {

        // =========================================================================
        // 🔵 OBTENER NOMBRE DE COBERTURA – Detalle de cita
        // =========================================================================
        case 'obtenerNombreCobertura':
            $id_cita = intval($_POST['id_cita'] ?? 0);

            if ($id_cita <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                break;
            }

            $respuesta = CitasC::ObtenerNombreCoberturaC($id_cita);
            echo json_encode($respuesta);
            break;


        // =========================================================================
        // 🟢 OBTENER AFILIACIONES DEL PACIENTE (CORREGIDO)
        // 🟢 DEVUELVE SOLO LAS OBRAS SOCIALES + PLANES DEL PACIENTE
        // =========================================================================
        case 'obtenerAfiliacionesPaciente':

            if (!headers_sent()) { ob_clean(); }

            $id_paciente = intval($_POST['id_paciente'] ?? 0);

            if ($id_paciente <= 0) {
                echo json_encode([
                    'success'      => false,
                    'afiliaciones' => [],
                    'error'        => 'ID de paciente inválido'
                ]);
                break;
            }

            require_once __DIR__ . '/../Modelos/PacientesM.php';

            try {
                // ESTE MÉTODO ES EL ÚNICO POSIBLE
                // No hay ningún otro lugar que devuelva obras sociales globales
                // De aquí depende la corrección total.
                $afiliaciones = PacientesM::ObtenerAfiliacionesPacienteM($id_paciente);

                if (!is_array($afiliaciones)) {
                    $afiliaciones = [];
                }

                echo json_encode([
                    'success'      => true,
                    'afiliaciones' => $afiliaciones
                ]);

            } catch (Exception $ex) {
                http_response_code(500);
                echo json_encode([
                    'success'      => false,
                    'afiliaciones' => [],
                    'error'        => 'Error en consulta de afiliaciones',
                    'message'      => $ex->getMessage()
                ]);
            }

            break;


        // =========================================================================
        // 🔴 BLOQUEOS
        // =========================================================================
        case 'eliminarBloqueo':
            $resultado = (new BloqueosC())->EliminarBloqueoC();
            echo json_encode($resultado);
            break;


        case 'obtenerCitasAdmin':
            // 1. Permitir Administrador o Secretario
            if ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Secretario') {
                echo json_encode([]); exit();
            }

            // 2. Lógica de filtro: 
            // Si es Secretario: Forzamos su consultorio de sesión.
            // Si es Admin: Usamos el del selector (si hay uno) o traemos todos.
            if ($_SESSION['rol'] === 'Secretario') {
                $id_consultorio_filtro = $_SESSION['id_consultorio'];
            } else {
                $id_consultorio_filtro = (!empty($_GET['id_consultorio'])) ? $_GET['id_consultorio'] : null;
            }

            $filtros = [
                'start'          => $_GET['start'] ?? date('Y-m-01'),
                'end'            => $_GET['end']   ?? date('Y-m-t'),
                'id_consultorio' => $id_consultorio_filtro,
                'id_doctor'      => $_GET['id_doctor'] ?? null
            ];

            $citas = CitasM::ObtenerCitasParaAdminM($filtros);
            $eventos = [];
            $colores = ['Pendiente'=>'#f39c12','Confirmada'=>'#00c0ef','Completada'=>'#00a65a','Cancelada'=>'#f56954','Ausente'=>'#8e44ad'];

            foreach ($citas as $cita) {
                $eventos[] = [
                    'id'    => $cita['id'],
                    'title' => ($cita['nyaP'] ?? 'Paciente'),
                    'start' => $cita['inicio'],
                    'end'   => $cita['fin'],
                    'color' => $colores[$cita['estado']] ?? '#777',
                    'extendedProps' => [
                        'paciente'      => $cita['nyaP'],
                        'doctor'        => 'Dr(a). ' . $cita['doctor_nombre'] . ' ' . $cita['doctor_apellido'],
                        'consultorio'   => $cita['nombre_consultorio'],
                        'motivo'        => $cita['motivo'],
                        'estado'        => $cita['estado'],
                        'id_consultorio'=> $cita['id_consultorio']
                    ]
                ];
            }
            $bloqueos = BloqueosC::VerBloqueosFullCalendar($filtros);
            echo json_encode(array_merge($eventos, is_array($bloqueos) ? $bloqueos : []));
            break;


        // =========================================================================
        // 🟡 HORARIOS
        // =========================================================================
        case 'obtenerHorariosDisponibles':

            $id_doctor = intval($_POST['id_doctor'] ?? 0);
            $fecha     = $_POST['fecha'] ?? '';

            if ($id_doctor <= 0 || empty($fecha)) {
                echo json_encode(['success' => false, 'error' => 'Faltan datos.']);
                break;
            }

            $horarios = CitasC::ObtenerHorariosDisponiblesC($id_doctor, $fecha);

            echo json_encode(['success' => true, 'horarios' => $horarios]);
            break;


        // =========================================================================
        // 🟤 DOCTORES POR CONSULTORIO
        // =========================================================================
        case 'obtenerDoctoresPorConsultorio':

            $id_consultorio = intval($_POST['id_consultorio'] ?? 0);

            if ($id_consultorio <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID no válido']);
                break;
            }

            $doctores = DoctoresM::ListarDoctoresPorConsultorioM($id_consultorio);
            echo json_encode(['success' => true, 'doctores' => $doctores]);
            break;


        // =========================================================================
        // 🟠 TRATAMIENTOS POR CONSULTORIO
        // =========================================================================
        case 'obtenerTratamientosPorConsultorio':

            $id_consultorio = intval($_POST['id_consultorio'] ?? 0);

            if ($id_consultorio <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID no válido']);
                break;
            }

            $tratamientos = TratamientosM::ObtenerTratamientosPorConsultorioM($id_consultorio);
            echo json_encode(['success' => true, 'tratamientos' => $tratamientos]);
            break;


        // =========================================================================
        // 🟩 DETALLES DE CITA
        // =========================================================================
        case 'obtenerDetallesCita':

            $id_cita = intval($_POST['id_cita'] ?? 0);

            if ($id_cita <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                break;
            }

            verificarPermisoCita($id_cita);

            $cita = (new CitasM())->ObtenerCitaM($id_cita);

            echo json_encode([
                'success' => (bool)$cita,
                'datos'   => $cita
            ]);
            break;


        // =========================================================================
        // 🔻 CANCELAR CITA
        // =========================================================================
        case 'cancelarCita':

            $id_cita = intval($_POST['id_cita'] ?? 0);
            $motivo  = $_POST['motivo'] ?? 'Cancelada por usuario';

            if ($id_cita <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                break;
            }

            verificarPermisoCita($id_cita);

            $resultado = (new CitasC())->CancelarCitaC($id_cita, $motivo);
            echo json_encode($resultado);
            break;


        // =========================================================================
        // ❌ ACCIÓN DESCONOCIDA
        // =========================================================================
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
            break;
    }

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error'   => 'Error fatal en el endpoint AJAX.',
        'message' => $e->getMessage()
    ]);
}
