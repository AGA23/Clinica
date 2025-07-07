<?php
session_start();
define('BASE_URL', '/clinica/');

if (!isset($_SESSION["Ingresar"])) {
    echo "Sesión no activa. Rol no definido.";
    exit;
}

if (!isset($_SESSION["Ingresar"]) || $_SESSION["Ingresar"] !== true || !in_array($_SESSION["rol"], ["Secretaria", "Administrador"])) {
    header("Location: " . BASE_URL . "inicio");
    exit();
}

require_once __DIR__ . "/../../Controladores/ConsultoriosC.php";

$error = null;
try {
    $consultorios = ConsultoriosC::VerConsultoriosC(null, null);
} catch (Exception $e) {
    $error = "No se pudieron cargar los consultorios";
    $consultorios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Gestor de Consultorios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?= BASE_URL ?>Vistas/dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>Vistas/bower_components/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>Vistas/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

    <?php include __DIR__ . "/cabecera.php"; ?>
    <?php include __DIR__ . "/menu" . ucfirst($_SESSION["rol"]) . ".php"; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <h1>Gestor de Consultorios</h1>
        </section>

        <section class="content">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="box box-primary">
                <div class="box-header">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#modal-nuevo-consultorio">
                        <i class="fa fa-plus"></i> Nuevo Consultorio
                    </button>
                </div>

                <div class="box-body">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                        <tr>
                            <th>N°</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($consultorios as $i => $value): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($value["nombre"]) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= BASE_URL ?>consultorios/editar/<?= $value["id"] ?>" class="btn btn-success btn-sm">
                                            <i class="fa fa-pencil"></i> Editar
                                        </a>
                                        <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?= $value["id"] ?>" data-nombre="<?= htmlspecialchars($value["nombre"]) ?>">
                                            <i class="fa fa-trash"></i> Borrar
                                        </button>
                                        <button class="btn btn-warning btn-sm btn-cambiar-estado" data-id="<?= $value["id"] ?>" data-nombre="<?= htmlspecialchars($value["nombre"]) ?>">
                                            <i class="fa fa-exchange"></i> Cambiar Estado
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <!-- Modal Nuevo Consultorio -->
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

    <!-- Modal Cambiar Estado -->
    <div class="modal fade" id="modal-estado-consultorio">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-cambiar-estado">
                    <div class="modal-header">
                        <h4 class="modal-title">Cambiar Estado del Consultorio</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_consultorio" id="estado-id-consultorio">
                        <div class="form-group">
                            <label>Estado</label>
                            <select name="estado" class="form-control" required>
                                <option value="">Seleccione un estado</option>
                                <option value="Habilitado">Habilitado</option>
                                <option value="Inhabilitado">Inhabilitado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha</label>
                            <input type="date" name="fecha" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Motivo</label>
                            <textarea name="motivo" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-save"></i> Guardar Cambio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        <div class="pull-right hidden-xs"><b>Versión</b> 1.0</div>
        <strong>Clínica &copy; <?= date('Y') ?></strong>
    </footer>
</div>

<script src="<?= BASE_URL ?>Vistas/bower_components/jquery/dist/jquery.min.js"></script>
<script src="<?= BASE_URL ?>Vistas/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="<?= BASE_URL ?>Vistas/bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="<?= BASE_URL ?>Vistas/dist/js/adminlte.min.js"></script>

<script>
$(function () {
    $('.table').DataTable({
        language: {
            url: '<?= BASE_URL ?>Vistas/plugins/datatables/Spanish.json'
        }
    });

    $('.btn-eliminar').click(function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        if (confirm(`¿Está seguro que desea eliminar el consultorio "${nombre}"?`)) {
            $.post('<?= BASE_URL ?>api/consultorios/eliminar', { id }, function () {
                location.reload();
            }).fail(function () {
                alert('No se pudo eliminar el consultorio');
            });
        }
    });

    $('.btn-cambiar-estado').click(function () {
        const id = $(this).data('id');
        $('#estado-id-consultorio').val(id);
        $('#modal-estado-consultorio').modal('show');
    });

    $('#form-cambiar-estado').submit(function (e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.post('<?= BASE_URL ?>Ajax/consultoriosA.php', formData + '&accion=cambiarEstado', function (respuesta) {
            alert(respuesta);
            location.reload();
        }).fail(function () {
            alert('Error al cambiar el estado del consultorio');
        });
    });
});
</script>
</body>
</html>
