<?php
// En Vistas/modulos/admins.php

// Seguridad
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "Administrador") {
    echo '<script>window.location = "inicio";</script>';
    return;
}

$adminController = new AdminC();
// Procesar formularios POST si se envían
$adminController->CrearAdminC();
$adminController->ActualizarAdminC();

// Cargar la lista de administradores para la tabla
$admins = AdminC::ListarAdminsC();
?>

<section class="content-header">
    <h1>Gestor de Administradores</h1>
</section>

<section class="content">
    <?php
    if (isset($_SESSION['mensaje_admins'])) {
        echo '<div class="alert alert-' . $_SESSION['tipo_mensaje_admins'] . ' alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $_SESSION['mensaje_admins'] . '</div>';
        unset($_SESSION['mensaje_admins'], $_SESSION['tipo_mensaje_admins']);
    }
    ?>
    <div class="box box-primary">
        <div class="box-header with-border">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modal-nuevo-admin"><i class="fa fa-plus"></i> Nuevo Administrador</button>
        </div>
        <div class="box-body">
            <table id="tabla-admins" class="table table-bordered table-hover dt-responsive" width="100%">
                <thead><tr><th>#</th><th>Apellido</th><th>Nombre</th><th>Usuario</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($admins as $key => $value): ?>
                    <tr>
                        <td><?= ($key + 1) ?></td>
                        <td><?= htmlspecialchars($value["apellido"]) ?></td>
                        <td><?= htmlspecialchars($value["nombre"]) ?></td>
                        <td><?= htmlspecialchars($value["usuario"]) ?></td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-info btn-sm btn-editar-admin" data-id="<?= $value["id"] ?>" data-toggle="modal" data-target="#modal-editar-admin"><i class="fa fa-pencil"></i> Editar</button>
                                <button class="btn btn-danger btn-sm btn-eliminar-admin" data-id="<?= $value["id"] ?>" data-nombre="<?= htmlspecialchars($value['nombre'] . ' ' . $value['apellido']) ?>"><i class="fa fa-trash"></i> Borrar</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Modales -->
<div class="modal fade" id="modal-nuevo-admin">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post"><div class="modal-header"><h4 class="modal-title">Nuevo Administrador</h4></div><div class="modal-body">
            <div class="form-group"><label>Nombre:</label><input type="text" name="nombre" class="form-control" required></div>
            <div class="form-group"><label>Apellido:</label><input type="text" name="apellido" class="form-control" required></div>
            <div class="form-group"><label>Usuario:</label><input type="text" name="usuario" class="form-control" required></div>
            <div class="form-group"><label>Contraseña:</label><input type="password" name="clave" class="form-control" required></div>
        </div><div class="modal-footer"><button type="submit" class="btn btn-primary" name="crear_admin">Guardar</button></div></form>
    </div></div>
</div>

<div class="modal fade" id="modal-editar-admin">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post"><div class="modal-header"><h4 class="modal-title">Editar Administrador</h4></div><div class="modal-body">
            <input type="hidden" name="id_admin_editar" id="id_admin_editar">
            <div class="form-group"><label>Nombre:</label><input type="text" name="nombre_editar" id="nombre_editar" class="form-control" required></div>
            <div class="form-group"><label>Apellido:</label><input type="text" name="apellido_editar" id="apellido_editar" class="form-control" required></div>
            <div class="form-group"><label>Usuario:</label><input type="text" name="usuario_editar" id="usuario_editar" class="form-control" required></div>
            <div class="form-group"><label>Nueva Contraseña:</label><input type="password" name="clave_editar" class="form-control" placeholder="Dejar en blanco para no cambiar"></div>
        </div><div class="modal-footer"><button type="submit" class="btn btn-primary" name="editar_admin">Guardar Cambios</button></div></form>
    </div></div>
</div>

<?php ob_start(); ?>
<script>
$(function() {
    $('#tabla-admins').DataTable({ "language": { "url": "<?= BASE_URL ?>Vistas/plugins/datatables/Spanish.json" }});

    $('#tabla-admins tbody').on('click', '.btn-editar-admin', function() {
        var idAdmin = $(this).data('id');
        $.post('<?= BASE_URL ?>ajax/adminA.php?action=obtener', { id_admin: idAdmin })
        .done(function(r) {
            if (r.success) {
                $('#id_admin_editar').val(r.datos.id);
                $('#nombre_editar').val(r.datos.nombre);
                $('#apellido_editar').val(r.datos.apellido);
                $('#usuario_editar').val(r.datos.usuario);
            }
        });
    });

    $('#tabla-admins tbody').on('click', '.btn-eliminar-admin', function() {
        var idAdmin = $(this).data('id');
        var nombre = $(this).data('nombre');
        Swal.fire({ title: '¿Está seguro?', text: `¡El administrador '${nombre}' será eliminado!`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, eliminar' })
        .then((result) => {
            if (result.isConfirmed) {
                $.post('<?= BASE_URL ?>ajax/adminA.php?action=eliminar', { id_admin: idAdmin })
                .done(function(r){ if(r.success){ location.reload(); } else { Swal.fire('Error', r.error, 'error'); }});
            }
        });
    });
});
</script>
<?php $scriptDinamico = ob_get_clean(); ?>