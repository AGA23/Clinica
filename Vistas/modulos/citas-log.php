<?php
// Verificación de seguridad para que solo el admin y secretarias puedan entrar
if ($_SESSION["rol"] !== "Administrador" && $_SESSION["rol"] !== "Secretario") {
    echo '<script> window.location = "inicio"; </script>';
    exit(); // Es importante usar exit() después de la redirección
}
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Historial de Cambios en Citas
            <small>Registro de auditoría completo</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Historial de Citas</li>
        </ol>
    </section>

    <section class="content">
        <div class="box">
            <div class="box-body">
                <table class="table table-bordered table-striped table-hover dt-responsive" id="tablaLogsCitas">
                    <thead>
                        <tr>
                            <th style="width: 10px;">ID Log</th>
                            <th>ID Cita</th>
                            <th>Usuario Modificó</th>
                            <th>Rol</th>
                            <th>Fecha</th>
                            <th>Campo Modificado</th>
                            <th>Valor Anterior</th>
                            <th>Valor Nuevo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Llamamos al controlador estático para obtener los datos
                        $logs = LogCitasC::VerLogsCitasC();

                        foreach ($logs as $log) {
                            echo '<tr>
                                    <td>' . htmlspecialchars($log["id"]) . '</td>
                                    <td><a href="#">' . htmlspecialchars($log["id_cita"]) . '</a></td>
                                    <td>' . htmlspecialchars($log["usuario_modifico"]) . '</td>
                                    <td><span class="label label-primary">' . htmlspecialchars($log["rol_usuario"]) . '</span></td>
                                    <td>' . date('d/m/Y H:i', strtotime($log["fecha_cambio"])) . '</td>
                                    <td><strong>' . htmlspecialchars($log["campo_modificado"]) . '</strong></td>
                                    <td><small>' . nl2br(htmlspecialchars($log["valor_anterior"])) . '</small></td>
                                    <td><small>' . nl2br(htmlspecialchars($log["valor_nuevo"])) . '</small></td>
                                  </tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- El script se coloca aquí. Tu index.php lo moverá al lugar correcto. -->
<script>
$(document).ready(function() {
    $('#tablaLogsCitas').DataTable({
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "responsive": true,
        "autoWidth": false,
        "order": [[ 4, "desc" ]] // Ordenar por la columna de fecha (índice 4) de forma descendente
    });
});
</script>