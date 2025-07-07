<?php

if (!isset($_SESSION["Ingresar"])) {
    header("Location: /CLINICA/login");
    exit();
}

$id_paciente = $_SESSION["id"];

require_once $_SERVER["DOCUMENT_ROOT"] . '/CLINICA/Controladores/citasC.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/CLINICA/Controladores/doctoresC.php';

// Establecer la zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires'); // Asegúrate de usar la zona horaria correcta

$citasC = new CitasC();
$citas = $citasC->VerCitasPacienteC($id_paciente);
$doctores = DoctoresC::VerDoctoresC();

// Filtros
$filtro_doctor = $_GET['doctor'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';
$ahora = date('Y-m-d H:i:s');

$citas_pasadas = [];
$citas_proximas = [];

// Filtrar citas según los parámetros
foreach ($citas as $cita) {
    $fecha_cita = date('Y-m-d', strtotime($cita['inicio']));
    $pasa_filtro_doctor = empty($filtro_doctor) || $cita['id_doctor'] == $filtro_doctor;
    $pasa_filtro_fecha = empty($filtro_fecha) || $fecha_cita === $filtro_fecha;

    if ($pasa_filtro_doctor && $pasa_filtro_fecha) {
        if ($cita['inicio'] < $ahora) {
            $citas_pasadas[] = $cita;
        } else {
            $citas_proximas[] = $cita;
        }
    }
}

function mostrarEstado($estado) {
    switch ($estado) {
        case 'Completada': return '<span class="label label-success">Completada</span>';
        case 'Pendiente': return '<span class="label label-warning">Pendiente</span>';
        case 'Cancelada': return '<span class="label label-danger">Cancelada</span>';
        default: return '<span class="label label-default">' . htmlspecialchars($estado) . '</span>';
    }
}

// Ordenar citas próximas y pasadas
usort($citas_proximas, fn($a, $b) => strtotime($a['inicio']) <=> strtotime($b['inicio']));
usort($citas_pasadas, fn($a, $b) => strtotime($b['inicio']) <=> strtotime($a['inicio']));

?>

<div class="content-wrapper">
    <section class="content container-fluid">
        <h3>Mis Citas</h3>

        <!-- Filtros -->
        <form method="get" class="form-inline" style="margin-bottom: 20px;">
            <label>Doctor:</label>
            <select name="doctor" class="form-control" style="margin: 0 10px;">
                <option value="">Todos</option>
                <?php foreach ($doctores as $doc): ?>
                    <option value="<?= $doc['id'] ?>" <?= ($filtro_doctor == $doc['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($doc['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Fecha:</label>
            <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($filtro_fecha) ?>" style="margin: 0 10px;">

            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="historial" class="btn btn-default" style="margin-left: 10px;">Limpiar</a>
        </form>

        <!-- Citas Próximas -->
        <div class="box box-success">
            <div class="box-header with-border">
                <h4 class="box-title">Citas Próximas</h4>
            </div>
            <div class="box-body">
                <?php if (empty($citas_proximas)): ?>
                    <div class="alert alert-info">No hay citas próximas.</div>
                <?php else: ?>
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Doctor</th>
                                <th>Tratamientos del Doctor</th>
                                <th>Consultorio</th>
                                <th>Motivo</th>
                                <th>Observaciones</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($citas_proximas as $cita): ?>
                                <tr>
                                    <td><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></td>
                                    <td><?= htmlspecialchars($cita["nombre_doctor"]) ?></td>
                                    <td><?= htmlspecialchars($cita["tratamientos"] ?? 'No especificado') ?></td>
                                    <td><?= htmlspecialchars($cita["nombre_consultorio"] ?? 'Sin asignar') ?></td>
                                    <td><?= htmlspecialchars($cita["motivo"]) ?></td>
                                    <td><?= htmlspecialchars($cita["observaciones"]) ?></td>
                                    <td><?= mostrarEstado($cita["estado"]) ?></td>
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
                    <div class="alert alert-info">No hay citas anteriores.</div>
                <?php else: ?>
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Doctor</th>
                                <th>Tratamientos del Doctor</th>
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
                                    <td><?= htmlspecialchars($cita["nombre_consultorio"] ?? 'Sin asignar') ?></td>
                                    <td><?= htmlspecialchars($cita["motivo"]) ?></td>
                                    <td><?= htmlspecialchars($cita["observaciones"]) ?></td>
                                    <td><?= mostrarEstado($cita["estado"]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
