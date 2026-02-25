<?php
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    echo '<script>window.location = "inicio";</script>';
    return;
}

if (isset($_POST['generar_reporte_mensual'])) {
    (new ReportesC())->GenerarReporteManualC('exportar-reportes');
}

$anio_seleccionado   = $_GET['anio'] ?? date('Y');
$reportes_guardados  = ReportesM::ObtenerReportesMensualesGuardados($anio_seleccionado);
$consultorios_filtro = ReportesM::ObtenerListaConsultorios();
?>

<section class="content-header">
    <h1>Gestión y Exportación de Reportes</h1>
    <ol class="breadcrumb">
        <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
        <li class="active">Exportar Reportes</li>
    </ol>
</section>

<section class="content">

<?php if (isset($_SESSION['mensaje_reportes'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['tipo_mensaje_reportes']) ?> alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?= htmlspecialchars($_SESSION['mensaje_reportes']) ?>
    </div>
<?php unset($_SESSION['mensaje_reportes'], $_SESSION['tipo_mensaje_reportes']); ?>
<?php endif; ?>

<!-- ================= EXPORTAR POR RANGO ================= -->
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-file-excel-o"></i> Exportar Reporte Personalizado a Excel
        </h3>
    </div>
    <div class="box-body">
        <div class="form-inline">

            <div class="form-group" style="margin:5px;">
                <label>Desde:</label>
                <input type="date" id="export-fecha-desde" class="form-control"
                       value="<?= date('Y-m-01') ?>">
            </div>

            <div class="form-group" style="margin:5px;">
                <label>Hasta:</label>
                <input type="date" id="export-fecha-hasta" class="form-control"
                       value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group" style="margin:5px;">
                <label>Consultorio:</label>
                <select id="export-id-consultorio" class="form-control">
                    <option value="">-- Todos los Consultorios --</option>
                    <?php foreach($consultorios_filtro as $c): ?>
                        <option value="<?= $c['id'] ?>">
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="button" id="btn-exportar-rango"
                    class="btn btn-success" style="margin:5px;">
                <i class="fa fa-download"></i> Exportar Ahora
            </button>

        </div>
    </div>
</div>

<!-- ================= REPORTES MENSUALES ================= -->
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-archive"></i> Gestión de Reportes Mensuales Guardados
        </h3>
    </div>

    <div class="box-body">

        <form method="post" class="form-inline" style="margin-bottom:20px;">
            <div class="form-group">
                <label>Generar/Actualizar Reporte para el Mes:</label>
                <input type="month" id="mes_anio" name="mes_anio"
                       class="form-control" value="<?= date('Y-m') ?>" required>
            </div>

            <button type="submit" name="generar_reporte_mensual"
                    class="btn btn-primary">
                <i class="fa fa-refresh"></i> Generar / Actualizar
            </button>

            <button type="button" id="btn-descargar-mensual"
                    class="btn btn-success">
                <i class="fa fa-file-excel-o"></i> Generar y Descargar Excel
            </button>
        </form>

        <hr>

        <form method="get" class="form-inline pull-right">
            <input type="hidden" name="url" value="exportar-reportes">
            <div class="form-group">
                <label>Ver año:</label>
                <input type="number" name="anio" class="form-control"
                       value="<?= htmlspecialchars($anio_seleccionado) ?>"
                       min="2020" max="<?= date('Y') ?>">
                <button type="submit" class="btn btn-default btn-sm">
                    Filtrar
                </button>
            </div>
        </form>

        <h4>Mostrando Reportes para el Año <?= htmlspecialchars($anio_seleccionado) ?></h4>

        <div class="table-responsive">
            <table class="table table-bordered table-striped dt-responsive" width="100%">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th>Consultorio</th>
                        <th>Completadas</th>
                        <th>Canceladas</th>
                        <th>Total</th>
                        <th>Generado</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(!empty($reportes_guardados)): ?>
                    <?php foreach($reportes_guardados as $reporte): ?>
                        <tr>
                            <td><?= DateTime::createFromFormat('!m', $reporte['mes'])->format('F') ?></td>
                            <td><?= htmlspecialchars($reporte['nombre_consultorio']) ?></td>
                            <td><?= $reporte['citas_completadas'] ?></td>
                            <td class="text-red"><?= $reporte['citas_canceladas'] ?></td>
                            <td><strong><?= $reporte['total_citas'] ?></strong></td>
                            <td><?= date("d/m/Y H:i", strtotime($reporte['generado_en'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            No hay reportes.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</section>

<?php ob_start(); ?>
<script>
$(document).ready(function() {

    // =========================
    // EXPORTAR POR RANGO
    // =========================
    $('#btn-exportar-rango').on('click', function() {

        var fd  = $('#export-fecha-desde').val();
        var fh  = $('#export-fecha-hasta').val();
        var idc = $('#export-id-consultorio').val();

        if (!fd || !fh) {
            alert('Seleccione un rango válido.');
            return;
        }

        var urlDescarga = BASE_URL +
            "index.php?url=exportar-reportes&action=exportar_excel" +
            "&fecha_desde=" + fd +
            "&fecha_hasta=" + fh;

        if (idc) {
            urlDescarga += "&id_consultorio=" + idc;
        }

        window.open(urlDescarga, '_blank');
    });

    // =========================
    // EXPORTAR MENSUAL
    // =========================
    $('#btn-descargar-mensual').on('click', function() {

        var mes = $('#mes_anio').val();

        if (!mes) {
            alert('Seleccione un mes.');
            return;
        }

        var urlDescarga = BASE_URL +
            "index.php?url=exportar-reportes&action=exportar_excel" +
            "&mes=" + mes;

        window.open(urlDescarga, '_blank');
    });

    // =========================
    // DATATABLE
    // =========================
    try {
        $('.dt-responsive').DataTable({
            language: {
                url: BASE_URL + "Vistas/plugins/datatables/Spanish.json"
            },
            order: [[5, "desc"]]
        });
    } catch (err) {
        console.warn("Tabla no inicializada:", err);
    }

});
</script>
<?php $scriptDinamico = ob_get_clean(); ?>