<?php
// En Vistas/modulos/mis-pacientes.php

// 1. SEGURIDAD: Solo para Doctores
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "Doctor") {
    echo '<script>window.location = "inicio";</script>';
    return;
}

// 2. OBTENER DATOS: Se necesita un nuevo método para obtener solo los pacientes de un doctor
$id_doctor = $_SESSION['id'];
$pacientes = PacientesM::ListarPacientesPorDoctorM($id_doctor); // Necesitaremos este nuevo método en el modelo
?>

<section class="content-header">
    <h1>Mis Pacientes</h1>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Mis Pacientes</li></ol>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Listado de Pacientes Atendidos</h3>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table id="tabla-mis-pacientes" class="table table-bordered table-hover table-striped dt-responsive" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Apellido</th>
                            <th>Nombre</th>
                            <th>Documento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php foreach ($pacientes as $key => $value): ?>
    <tr>
        <td><?= ($key + 1) ?></td>
        <td><?= htmlspecialchars($value["apellido"]) ?></td>
        <td><?= htmlspecialchars($value["nombre"]) ?></td>
        <td><?= htmlspecialchars(($value["tipo_documento"] ?? '') . ' ' . ($value["numero_documento"] ?? '')) ?></td>
        <td>
            <!-- === [CORREGIDO] El enlace ahora apunta a la vista para doctores 'historia-clinica' === -->
            <a href="<?= BASE_URL ?>index.php?url=historia-clinica&id=<?= $value["id"] ?>" class="btn btn-success btn-sm">
                <i class="fa fa-heartbeat"></i> Ver Historia Clínica
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php ob_start(); ?>
<script>
$(function() {
    $('#tabla-mis-pacientes').DataTable({
        "language": { "url": "<?= BASE_URL ?>Vistas/plugins/datatables/Spanish.json" }
    });
});
</script>
<?php $scriptDinamico = ob_get_clean(); ?>