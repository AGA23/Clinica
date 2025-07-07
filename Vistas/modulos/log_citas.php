<?php
session_start();

if (!isset($_SESSION["rol"]) || ($_SESSION["rol"] !== "Secretaria" && $_SESSION["rol"] !== "Administrador")) {
    echo '<script>window.location = "inicio";</script>';
    exit();
}

require_once "header.php";
require_once "menu.php";
require_once "Controladores/CitasC.php";
require_once "Modelos/CitasM.php";

$log = CitasM::ObtenerHistorialCambiosM();
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Historial de Cambios en Citas</h1>
    </section>

    <section class="content">
        <div class="card card-primary card-outline">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabla_log" class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>ID Cita</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Campo</th>
                                <th>Antes</th>
                                <th>Después</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($log as $registro): ?>
                                <tr>
                                    <td><?= $registro["id_log"] ?></td>
                                    <td><?= $registro["id_cita"] ?></td>
                                    <td><?= $registro["usuario"] ?></td>
                                    <td><?= ucfirst($registro["accion"]) ?></td>
                                    <td><?= $registro["campo_modificado"] ?></td>
                                    <td>
                                        <small><?= nl2br(htmlspecialchars($registro["valor_anterior"])) ?></small>
                                    </td>
                                    <td>
                                        <small><?= nl2br(htmlspecialchars($registro["valor_nuevo"])) ?></small>
                                    </td>
                                    <td><?= $registro["fecha_cambio"] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div> <!-- /.table-responsive -->
            </div> <!-- /.card-body -->
        </div> <!-- /.card -->
    </section>
</div>

<?php require_once "footer.php"; ?>

<script>
    $(document).ready(function() {
        $('#tabla_log').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            }
        });
    });
</script>
