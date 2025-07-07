<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/clinica/');
}

$rolesPermitidos = ["Secretaria", "Administrador", "Doctor", "Paciente"];
if (!isset($_SESSION["Ingresar"]) || $_SESSION["Ingresar"] !== true || !in_array($_SESSION["rol"], $rolesPermitidos)) {
    header("Location: " . BASE_URL . "login");
    exit();
}

require_once __DIR__ . "/../../Controladores/ConsultoriosC.php";

try {
    $consultorios = ConsultoriosC::VerConsultoriosCompletosC();
} catch (PDOException $e) {
    $error = ($_SESSION["rol"] == "Administrador") ? $e->getMessage() : "Error al cargar consultorios";
    $consultorios = [];
}

function estaEnHorarioApertura($horariosStr) {
    if (empty($horariosStr)) return false;
    $horarios = explode('|', $horariosStr);
    $diaActual = (int) date('N');
    $horaActual = (int) date('H') * 60 + (int) date('i');

    foreach ($horarios as $horario) {
        $partes = explode(':', $horario);
        if (count($partes) < 7) continue;

        [$dia, $hIniH, $hIniM, , $hFinH, $hFinM] = array_map('intval', $partes);
        if ($dia === $diaActual) {
            $horaInicio = $hIniH * 60 + $hIniM;
            $horaFin = $hFinH * 60 + $hFinM;
            return ($horaInicio <= $horaActual && $horaActual <= $horaFin);
        }
    }
    return false;
}

function formatearHorario($horariosStr) {
    if (empty($horariosStr)) return '<span class="text-muted">Sin horario</span>';

    $dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    $diaActual = (int) date('N');
    $horarios = explode('|', $horariosStr);

    foreach ($horarios as $horario) {
        $partes = explode(':', $horario);
        if (count($partes) < 7) continue;

        $dia = (int) $partes[0];
        if ($dia === $diaActual) {
            return sprintf(
                '%s %02d:%02d - %02d:%02d',
                $dias[$dia - 1],
                $partes[1], $partes[2],
                $partes[4], $partes[5]
            );
        }
    }
    return '<span class="text-muted">Cerrado hoy</span>';
}
?>

<section class="content-header">
    <h1>Consultorios</h1>
    <ol class="breadcrumb">
        <li><a href="<?= BASE_URL ?>inicio"><i class="fa fa-home"></i> Inicio</a></li>
        <li class="active">Consultorios</li>
    </ol>
</section>

<section class="content">
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i class="fa fa-warning"></i> <?= $error ?>
    </div>
    <?php endif; ?>

    <div class="box box-primary">
        <?php if (in_array($_SESSION["rol"], ["Secretaria", "Administrador"])): ?>
        <div class="box-header">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modal-nuevo-consultorio">
                <i class="fa fa-plus"></i> Nuevo Consultorio
            </button>
        </div>
        <?php endif; ?>

        <div class="box-body">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Médico</th>
                        <th>Horario</th>
                        <th>Estado</th>
                        <?php if (in_array($_SESSION["rol"], ["Secretaria", "Administrador"])): ?>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consultorios as $i => $cons): 
                        $estadoAuto = estaEnHorarioApertura($cons['horario_consultorio'] ?? '') ? 'Abierto' : 'Cerrado';
                        $estadoFinal = $cons['estado_manual'] ?? $estadoAuto;
                        $esManual = isset($cons['estado_manual']);
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($cons['consultorio'] ?? '') ?></td>
                        <td><?= !empty($cons['medico']) ? htmlspecialchars($cons['medico']) : '<span class="text-muted">No asignado</span>' ?></td>
                        <td><?= formatearHorario($cons['horario_consultorio'] ?? '') ?></td>
                        <td>
                            <span class="label estado-<?= $estadoFinal ?> <?= $esManual ? 'estado-manual' : '' ?>">
                                <?= $estadoFinal ?>
                            </span>
                        </td>
                        <?php if (in_array($_SESSION["rol"], ["Secretaria", "Administrador"])): ?>
                        <td>
                            <a href="<?= BASE_URL ?>consultorios/editar/<?= $cons['id'] ?>" class="btn btn-xs btn-success">
                                <i class="fa fa-edit"></i>
                            </a>
                            <button class="btn btn-xs btn-warning btn-cambiar-estado" 
                                    data-id="<?= $cons['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($cons['consultorio'] ?? '') ?>">
                                <i class="fa fa-exchange"></i>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Modal -->
<?php if (in_array($_SESSION["rol"], ["Secretaria", "Administrador"])): ?>
<div class="modal fade" id="modal-nuevo-consultorio">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= BASE_URL ?>consultorios/crear">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Nuevo Consultorio</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre del Consultorio</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.estado-Abierto { background-color: #00a65a; }
.estado-Cerrado { background-color: #dd4b39; }
.estado-Ocupado { background-color: #f39c12; }
.estado-Mantenimiento { background-color: #605ca8; }
.estado-manual::after { content: "*"; color: white; }
</style>

<script>
$(function() {
    $('.table').DataTable({
        language: {
            url: '<?= BASE_URL ?>Vistas/plugins/datatables/Spanish.json'
        }
    });

    $('.btn-cambiar-estado').click(function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        if (confirm(`¿Cambiar estado del consultorio ${nombre}?`)) {
            $.post('<?= BASE_URL ?>api/consultorios/cambiar-estado', { id: id }, function() {
                location.reload();
            });
        }
    });
});
</script>
