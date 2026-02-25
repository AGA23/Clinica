<?php
require_once ROOT_PATH . '/Modelos/plantillacorreoM.php';

// --- Seguridad ---
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Secretario', 'Administrador'])) {
    echo "<section class='content'><div class='alert alert-danger'>Acceso no autorizado.</div></section>";
    return;
}

// --- Actualización ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['identificador'])) {
    $plantillasController = new PlantillasCorreoC();
    $plantillasController->ActualizarPlantillaC();
}

// --- Carga de datos ---
$plantilla = plantillacorreoM::ObtenerPlantillaPorIdentificador('recordatorio_cita');
if (!$plantilla) {
    $plantilla = [
        'asunto' => '',
        'cuerpo_html' => '',
        'tiempo_envio_horas' => 24,
        'descripcion' => 'Plantilla para recordatorios de citas.'
    ];
}
?>

<section class="content-header">
    <h1>Gestor de Plantillas de Correo</h1>
    <ol class="breadcrumb">
        <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
        <li class="active">Plantillas de Correo</li>
    </ol>
</section>

<section class="content">
    <?php if (isset($_SESSION['mensaje_plantillas'])): ?>
        <div class="alert alert-<?= $_SESSION['tipo_mensaje_plantillas'] ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h4><i class="icon fa fa-<?= $_SESSION['tipo_mensaje_plantillas'] == 'success' ? 'check' : 'ban' ?>"></i> Notificación</h4>
            <?= $_SESSION['mensaje_plantillas'] ?>
        </div>
        <?php unset($_SESSION['mensaje_plantillas'], $_SESSION['tipo_mensaje_plantillas']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="box box-primary shadow-lg">
                <div class="box-header with-border bg-gradient-primary text-white">
                    <h3 class="box-title"><i class="fa fa-pencil-square-o"></i> Editar Plantilla: Recordatorio de Cita</h3>
                </div>
                <form method="post" action="<?= BASE_URL ?>index.php?url=plantillas-correo">
                    <div class="box-body">
                        <input type="hidden" name="identificador" value="recordatorio_cita">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="asunto">Asunto del Correo</label>
                                    <input type="text" id="asunto" name="asunto" class="form-control" value="<?= htmlspecialchars($plantilla['asunto']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tiempo_envio_horas">Enviar Recordatorio:</label>
                                    <select id="tiempo_envio_horas" name="tiempo_envio_horas" class="form-control">
                                        <option value="8" <?= ($plantilla['tiempo_envio_horas'] == 8) ? 'selected' : '' ?>>8 horas antes</option>
                                        <option value="12" <?= ($plantilla['tiempo_envio_horas'] == 12) ? 'selected' : '' ?>>12 horas antes</option>
                                        <option value="24" <?= ($plantilla['tiempo_envio_horas'] == 24) ? 'selected' : '' ?>>1 día antes (24 hs)</option>
                                        <option value="48" <?= ($plantilla['tiempo_envio_horas'] == 48) ? 'selected' : '' ?>>2 días antes (48 hs)</option>
                                        <option value="72" <?= ($plantilla['tiempo_envio_horas'] == 72) ? 'selected' : '' ?>>3 días antes (72 hs)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="cuerpo_html">Cuerpo del Correo (HTML)</label>
                            <textarea id="cuerpo_html" name="cuerpo_html" class="form-control" rows="15" required><?= htmlspecialchars($plantilla['cuerpo_html']) ?></textarea>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-success pull-right btn-flat shadow-sm"><i class="fa fa-save"></i> Guardar Plantilla</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="box box-solid shadow-sm">
                <div class="box-header with-border bg-info text-white">
                    <h3 class="box-title"><i class="fa fa-tags"></i> Variables Disponibles</h3>
                </div>
                <div class="box-body">
                    <p class="text-light" style="font-size: 13px;">Use estas etiquetas en su plantilla:</p>
                    <ul class="placeholder-list">
                        <li><code class="bg-warning text-dark">{PACIENTE_NOMBRE}</code></li>
                        <li><code class="bg-warning text-dark">{DOCTOR_NOMBRE}</code></li>
                        <li><code class="bg-warning text-dark">{CONSULTORIO_NOMBRE}</code></li>
                        <li><code class="bg-warning text-dark">{CITA_FECHA_HORA}</code></li>
                    </ul>
                </div>
            </div>

            <div class="box box-warning shadow-lg">
                <div class="box-header with-border bg-gradient-warning text-dark">
                    <h3 class="box-title"><i class="fa fa-paper-plane"></i> Envío Manual</h3>
                </div>
                <div class="box-body">
                    <p class="text-dark">Ejecute el proceso de envío de recordatorios.</p>
                    <button id="btn-enviar-recordatorios" class="btn btn-danger btn-block btn-flat shadow-sm"><i class="fa fa-cogs"></i> Ejecutar Ahora</button>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="modal-resultado-envio">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-secondary text-white">
                <h4 class="modal-title">Resultado del Proceso de Envío</h4>
            </div>
            <div class="modal-body">
                <div id="log-salida" style="background-color:#1e282c;color:#fff;padding:15px;border-radius:5px;font-family:monospace;max-height:400px;overflow-y:auto;white-space:pre-wrap;font-size:12px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-flat" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
.content-wrapper { background-color:#e6f2ff; }
.box { border-radius:8px; }
.placeholder-list li { margin-bottom:10px; }
.placeholder-list code { padding:6px 10px; border-radius:6px; font-weight:600; font-size:14px; }
</style>

<script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/bootstrap.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/adminlte.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/sweetalert2.all.min.js"></script>

<!-- CKEditor versión original que funciona -->
<!-- CKEditor -->
<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
<script>
CKEDITOR.replace('contenidoCrear', {
    allowedContent: true, // permite HTML crudo
    height: 400,
    removePlugins: 'elementspath',
    resize_enabled: false
});
CKEDITOR.replace('contenidoEditar', {
    allowedContent: true, // permite HTML crudo
    height: 400,
    removePlugins: 'elementspath',
    resize_enabled: false
});
</script>
<script>
$(document).ready(function(){
    CKEDITOR.replace('cuerpo_html'); // Usamos la versión que funciona

    $('#btn-enviar-recordatorios').on('click', function(){
        var btn = $(this);
        var logContainer = $('#log-salida');
        var modal = $('#modal-resultado-envio');

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Ejecutando...');
        logContainer.html('Iniciando proceso...');
        modal.modal('show');

        $.post('<?= BASE_URL ?>index.php?url=ajax/ejecutar_envios')
        .done(function(response){
            if(response.success){
                logContainer.html(response.log);
                Swal.fire('¡Proceso completado!', response.message, 'success');
            } else {
                logContainer.html('<span style="color:#ff8b8b;">ERROR: '+(response.error||'Desconocido')+'</span><br>' + (response.log||''));
                Swal.fire('Error', response.error || 'Error desconocido', 'error');
            }
        })
        .fail(function(jqXHR){
            logContainer.html('<span style="color:#ff8b8b;">Error de comunicación. Código: '+jqXHR.status+'</span>');
            Swal.fire('Error de Conexión','No se pudo conectar con el servidor.','error');
        })
        .always(function(){
            btn.prop('disabled', false).html('<i class="fa fa-cogs"></i> Ejecutar Ahora');
        });
    });
});
</script>
