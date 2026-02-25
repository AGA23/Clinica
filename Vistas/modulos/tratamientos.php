<?php
// En Vistas/modulos/tratamientos.php (NUEVO ARCHIVO COMPLETO)

if (!isset($_SESSION["rol"]) || !in_array($_SESSION["rol"], ["Secretario", "Administrador"])) {
    echo '<script>window.location = "inicio";</script>';
    return;
}
if (isset($_POST["crear_tratamiento"])) { (new TratamientosC())->CrearTratamientoC(); }
if (isset($_POST["editar_tratamiento"])) { (new TratamientosC())->ActualizarTratamientoC(); }
$tratamientos = TratamientosC::ListarTratamientosC();
?>
<section class="content-header">
    <h1>Gestor de Tratamientos</h1>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Tratamientos</li></ol>
</section>
<section class="content">
    <?php if (isset($_SESSION['mensaje_tratamientos'])) { echo '<div class="alert alert-' . $_SESSION['tipo_mensaje_tratamientos'] . ' alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $_SESSION['mensaje_tratamientos'] . '</div>'; unset($_SESSION['mensaje_tratamientos'], $_SESSION['tipo_mensaje_tratamientos']); } ?>
    <div class="box box-primary">
        <div class="box-header with-border"><button class="btn btn-primary" data-toggle="modal" data-target="#modal-nuevo-tratamiento"><i class="fa fa-plus"></i> Nuevo Tratamiento</button></div>
        <div class="box-body">
            <div class="table-responsive">
                <table id="tabla-tratamientos" class="table table-bordered table-hover table-striped dt-responsive" width="100%">
                    <thead><tr><th style="width: 10px;">#</th><th>Nombre del Tratamiento</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($tratamientos as $key => $value): ?>
                        <tr>
                            <td><?= ($key + 1) ?></td>
                            <td><?= htmlspecialchars($value["nombre"]) ?></td>
                            <td><div class="btn-group"><button class="btn btn-info btn-sm btn-editar-tratamiento" data-id="<?= $value["id"] ?>" data-toggle="modal" data-target="#modal-editar-tratamiento"><i class="fa fa-pencil"></i> Editar</button><button class="btn btn-danger btn-sm btn-eliminar-tratamiento" data-id="<?= $value["id"] ?>" data-nombre="<?= htmlspecialchars($value['nombre']) ?>"><i class="fa fa-trash"></i> Borrar</button></div></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Modal Nuevo Tratamiento -->
<div class="modal fade" id="modal-nuevo-tratamiento">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action=""><div class="modal-header"><h4 class="modal-title">Crear Nuevo Tratamiento</h4></div><div class="modal-body"><div class="form-group"><label>Nombre:</label><input type="text" name="nombre" class="form-control" required></div></div><div class="modal-footer"><button type="submit" class="btn btn-primary" name="crear_tratamiento">Guardar</button></div></form>
    </div></div>
</div>

<!-- Modal Editar Tratamiento -->
<div class="modal fade" id="modal-editar-tratamiento">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action=""><div class="modal-header"><h4 class="modal-title">Editar Tratamiento</h4></div><div class="modal-body"><input type="hidden" name="id_tratamiento_editar" id="id_tratamiento_editar"><div class="form-group"><label>Nombre:</label><input type="text" name="nombre_editar" id="nombre_tratamiento_editar" class="form-control" required></div></div><div class="modal-footer"><button type="submit" class="btn btn-primary" name="editar_tratamiento">Guardar Cambios</button></div></form>
    </div></div>
</div>

<?php ob_start(); ?>
<script>
$(function() {
    var tabla = $('#tabla-tratamientos').DataTable({ "language": { "url": "<?= BASE_URL ?>Vistas/plugins/datatables/Spanish.json" }});

    $('#tabla-tratamientos tbody').on('click', '.btn-editar-tratamiento', function() {
        var id = $(this).data('id');
        $.post('<?= BASE_URL ?>Ajax/tratamientosA.php?action=obtener', { id_tratamiento: id })
        .done(function(r) { if(r.success) { $('#id_tratamiento_editar').val(r.datos.id); $('#nombre_tratamiento_editar').val(r.datos.nombre); }});
    });

    $('#tabla-tratamientos tbody').on('click', '.btn-eliminar-tratamiento', function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre');
        Swal.fire({ title: '¿Está seguro?', text: "¡El tratamiento '" + nombre + "' será eliminado!", icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, ¡bórralo!'})
        .then((result) => {
            if (result.isConfirmed) {
                $.post('<?= BASE_URL ?>Ajax/tratamientosA.php?action=eliminar', { id_tratamiento: id })
                .done(r => { if (r.success) { location.reload(); } else { Swal.fire('Error', r.error, 'error'); } });
            }
        });
    });
});
</script>
<?php $scriptDinamico = ob_get_clean(); ?>