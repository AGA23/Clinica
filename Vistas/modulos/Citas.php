<?php
// Verificar sesión y permisos
session_start();
if (!isset($_SESSION["Ingresar"])) {
    header("Location: /CLINICA/login");
    exit();
}

// Obtener ID del paciente desde la sesión
$id_paciente = $_SESSION["id"];

// Cargar controladores
require_once $_SERVER["DOCUMENT_ROOT"] . '/CLINICA/Controladores/citasC.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/CLINICA/Controladores/doctoresC.php';

$citasController = new CitasC();
$doctores = DoctoresC::VerDoctoresC();

// Procesar cancelación de cita
if (isset($_GET["cancelar"])) {
    $resultado_cancelacion = $citasController->CancelarCitaC($_GET["cancelar"]);
    if (isset($resultado_cancelacion['error'])) {
        $error_cancelacion = $resultado_cancelacion['error']; // Capturamos el error de cancelación
    } else {
        $mensaje_cancelacion = $resultado_cancelacion['success'] ?? 'Cita cancelada correctamente'; // O el mensaje de éxito
    }
    header("Location: citas"); // o $_SERVER['PHP_SELF']
    exit();
}

// Obtener citas del paciente
$citas = $citasController->VerCitasPacienteC($id_paciente);

// Filtrar citas
$filtro_doctor = $_GET['doctor'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';

// Clasificar citas
$citas_pasadas = [];
$citas_proximas = [];
$ahora = date('Y-m-d H:i:s');

foreach ($citas as $cita) {
    $es_filtro_doctor = empty($filtro_doctor) || $cita['id_doctor'] == $filtro_doctor;
    $es_filtro_fecha = empty($filtro_fecha) || strpos($cita['inicio'], $filtro_fecha) !== false;

    if ($es_filtro_doctor && $es_filtro_fecha) {
        if ($cita['inicio'] < $ahora) {
            $citas_pasadas[] = $cita;
        } else {
            $citas_proximas[] = $cita;
        }
    }
}

// Función para mostrar estados con colores
function etiquetaEstado($estado) {
    switch ($estado) {
        case 'Completada': return '<span class="label label-success">Completada</span>';
        case 'Pendiente': return '<span class="label label-warning">Pendiente</span>';
        case 'Cancelada': return '<span class="label label-danger">Cancelada</span>';
        default: return '<span class="label label-default">' . htmlspecialchars($estado) . '</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Citas</title>
    <link rel="stylesheet" href="/CLINICA/Vistas/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
    <?php 
    include $_SERVER["DOCUMENT_ROOT"] . '/CLINICA/Vistas/modulos/cabecera.php';
    include $_SERVER["DOCUMENT_ROOT"] . '/CLINICA/Vistas/modulos/menuPaciente.php';
    ?>

    <div class="content-wrapper">
        <section class="content">
            <h3>Mis Citas</h3>

            <!-- Mostrar mensajes de error o éxito -->
            <?php if (isset($error_cancelacion)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_cancelacion) ?></div>
            <?php elseif (isset($mensaje_cancelacion)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje_cancelacion) ?></div>
            <?php endif; ?>

            <!-- Filtros -->
            <form method="get" class="form-inline" style="margin-bottom: 20px;">
                <label for="doctor">Doctor:</label>
                <select name="doctor" id="doctor" class="form-control" style="margin: 0 10px;">
                    <option value="">Todos</option>
                    <?php foreach ($doctores as $doc): ?>
                        <option value="<?= $doc['id'] ?>" <?= ($filtro_doctor == $doc['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($doc['nombre']) ?> (<?= htmlspecialchars($doc['tratamientos']) ?>)

                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="fecha">Fecha:</label>
                <input type="date" name="fecha" id="fecha" class="form-control" value="<?= htmlspecialchars($filtro_fecha) ?>" style="margin: 0 10px;">

                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="historial" class="btn btn-default">Limpiar</a>
            </form>

            <!-- Próximas Citas -->
            <div class="box box-success">
                <div class="box-header with-border">
                    <h4 class="box-title">Citas Próximas</h4>
                </div>
                <div class="box-body">
                    <?php if (empty($citas_proximas)): ?>
                        <div class="alert alert-info">No hay citas próximas.</div>
                    <?php else: ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Doctor</th>
                                    <th>Tratamientos</th>
                                    <th>Consultorio</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($citas_proximas as $cita): ?>
                                    <tr>
                                        <td><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></td>
                                        <td><?= htmlspecialchars($cita["nombre_doctor"]) ?></td>
                                        <td><?= htmlspecialchars($cita["tratamientos"] ?? 'No especificado') ?></td>
                                        <td><?= htmlspecialchars($cita["nombre_consultorio"] ?? 'No asignado') ?></td>
                                        <td><?= htmlspecialchars($cita["motivo"]) ?></td>
                                        <td><?= etiquetaEstado($cita["estado"]) ?></td>
                                        <td>
                                            <?php if ($cita["estado"] == "Pendiente"): ?>
                                                <a href="?cancelar=<?= $cita['id'] ?>" class="btn btn-danger btn-xs" 
                                                   onclick="return confirm('¿Estás seguro de cancelar esta cita?')">
                                                    Cancelar
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historial de Citas -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h4 class="box-title">Historial de Citas</h4>
                </div>
                <div class="box-body">
                    <?php if (empty($citas_pasadas)): ?>
                        <div class="alert alert-info">No hay citas pasadas.</div>
                    <?php else: ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Doctor</th>
                                    <th>Tratamientos</th>
                                    <th>Consultorio</th>
                                    <th>Motivo</th>
                                    <th>Observaciones</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($citas_pasadas as $cita): ?>
                                    <tr>
                                        <td><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></td>
                                        <td><?= htmlspecialchars($cita["nombre_doctor"]) ?></td>
                                        <td><?= htmlspecialchars($cita["tratamientos"] ?? 'No especificado') ?></td>
                                        <td><?= htmlspecialchars($cita["nombre_consultorio"] ?? 'No asignado') ?></td>
                                        <td><?= htmlspecialchars($cita["motivo"]) ?></td>
                                        <td><?= htmlspecialchars($cita["observaciones"]) ?></td>
                                        <td><?= etiquetaEstado($cita["estado"]) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="/CLINICA/Vistas/bower_components/jquery/dist/jquery.min.js"></script>
<script src="/CLINICA/Vistas/dist/js/adminlte.min.js"></script>
</body>
</html>
