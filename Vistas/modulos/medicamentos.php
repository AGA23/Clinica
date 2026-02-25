<?php
// En Vistas/modulos/medicamentos.php (VERSIÓN FINAL INTEGRADA)

if ($_SESSION["rol"] !== "Administrador") {
    echo '<script>window.location = "inicio";</script>';
    exit();
}
// require_once "Controladores/MedicamentosC.php"; // Ya no es necesario si usas autoloader

// Lógica de procesamiento POST para crear fármacos aprobados directamente
if (isset($_POST["crear_farmaco"])) {
    (new MedicamentosC())->CrearFarmacoC();
}
// Cargar la lista de FÁRMACOS genéricos (el método ya trae el estado)
$farmacos = MedicamentosM::ListarFarmacosM();
?>

<section class="content-header">
    <h1>Gestor de Fármacos y Presentaciones</h1>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Medicamentos</li></ol>
</section>

<section class="content">
    <?php if (isset($_SESSION['mensaje_medicamentos'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['tipo_mensaje_medicamentos']) ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <?= htmlspecialchars($_SESSION['mensaje_medicamentos']) ?>
        </div>
    <?php 
        unset($_SESSION['mensaje_medicamentos'], $_SESSION['tipo_mensaje_medicamentos']);
    endif; 
    ?>
    
    <div class="box box-primary">
        <div class="box-header with-border">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modal-nuevo-farmaco">
                <i class="fa fa-plus"></i> Nuevo Fármaco (Aprobado)
            </button>
        </div>
        <div class="box-body">
            <table id="tabla-farmacos" class="table table-bordered table-hover dt-responsive" width="100%">
                <thead>
                    <tr>
                        <th>Nombre Genérico / Fármaco</th>
                        <!-- ¡NUEVO! Columna para el estado del fármaco principal -->
                        <th>Estado</th>
                        <th style="width: 280px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($farmacos as $farmaco): ?>
                    <!-- Se añade la clase 'warning' a la fila si el fármaco está pendiente de aprobación -->
                    <tr class="<?= ($farmaco['estado'] == 'pendiente') ? 'warning' : '' ?>">
                        <td><strong><?= htmlspecialchars($farmaco['nombre_generico']) ?></strong></td>
                        <td>
                            <!-- ¡NUEVO! Etiqueta visual para el estado -->
                            <?php if ($farmaco['estado'] == 'pendiente'): ?>
                                <span class="label label-warning">Pendiente de Aprobación</span>
                            <?php else: ?>
                                <span class="label label-success">Aprobado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <!-- Botón para gestionar presentaciones (siempre visible) -->
                                <button class="btn btn-info btn-sm btn-gestionar-presentaciones" 
                                        data-id-farmaco="<?= $farmaco['id'] ?>"
                                        data-nombre-farmaco="<?= htmlspecialchars($farmaco['nombre_generico']) ?>"
                                        data-toggle="modal" data-target="#modal-gestionar-presentaciones">
                                    <i class="fa fa-th-list"></i> Presentaciones
                                </button>
                                <!-- Botones de acción solo para fármacos pendientes -->
                                <?php if ($farmaco['estado'] == 'pendiente'): ?>
                                    <button class="btn btn-success btn-sm btn-aprobar-farmaco" data-id-farmaco="<?= $farmaco['id'] ?>">
                                        <i class="fa fa-check"></i> Aprobar Fármaco
                                    </button>
                                    <button class="btn btn-danger btn-sm btn-rechazar-farmaco" data-id-farmaco="<?= $farmaco['id'] ?>">
                                        <i class="fa fa-trash"></i> Rechazar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Modal para Crear un Nuevo Fármaco Genérico (sin cambios) -->
<div class="modal fade" id="modal-nuevo-farmaco">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post">
            <div class="modal-header"><h4 class="modal-title">Crear Nuevo Fármaco Genérico</h4></div>
            <div class="modal-body">
                <input type="hidden" name="crear_farmaco" value="ok">
                <div class="form-group">
                    <label>Nombre del Fármaco (ej. Ibuprofeno, Paracetamol):</label>
                    <input type="text" name="nombre_generico" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar Fármaco</button></div>
        </form>
    </div></div>
</div>

<!-- Modal para Gestionar las Presentaciones de un Fármaco (sin cambios) -->
<div class="modal fade" id="modal-gestionar-presentaciones">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h4 class="modal-title">Presentaciones de: <strong id="nombre-farmaco-modal"></strong></h4></div>
            <div class="modal-body">
                <input type="hidden" id="id-farmaco-actual">
                <div id="contenedor-tabla-presentaciones">
                    <p class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>
                </div>
                <hr>
                <h5><i class="fa fa-plus-circle"></i> Añadir Nueva Presentación</h5>
                <form id="form-nueva-presentacion" class="form-horizontal">
                    <div class="form-group"><label class="col-sm-2 control-label">Presentación</label><div class="col-sm-10"><input type="text" id="nueva-presentacion-nombre" class="form-control" placeholder="Ej: 600mg Comprimidos Recubiertos" required></div></div>
                    <div class="form-group"><label class="col-sm-2 control-label">Observaciones</label><div class="col-sm-10"><textarea id="nueva-presentacion-obs" class="form-control" rows="2"></textarea></div></div>
                    <div class="form-group"><div class="col-sm-offset-2 col-sm-10"><div class="checkbox"><label><input type="checkbox" id="nueva-presentacion-cronico"> Es medicamento crónico</label></div></div></div>
                    <div class="form-group"><div class="col-sm-offset-2 col-sm-10"><button type="submit" class="btn btn-success">Añadir Presentación</button></div></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function(){
    $('#tabla-farmacos').DataTable();

    // --- CÓDIGO PARA GESTIONAR PRESENTACIONES (SIN CAMBIOS) ---
    $('#modal-gestionar-presentaciones').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var idFarmaco = button.data('id-farmaco');
        var nombreFarmaco = button.data('nombre-farmaco');
        
        var modal = $(this);
        modal.find('#id-farmaco-actual').val(idFarmaco);
        modal.find('#nombre-farmaco-modal').text(nombreFarmaco);
        
        cargarPresentaciones(idFarmaco);
    });

    function cargarPresentaciones(idFarmaco) {
        var container = $('#contenedor-tabla-presentaciones').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');
        // Usamos el ajax endpoint correcto
        $.post('<?= BASE_URL ?>ajax/medicamentosA.php', { action: 'obtener_presentaciones', id_farmaco: idFarmaco })
        .done(function(r) {
            container.empty();
            if (r.success && r.presentaciones.length > 0) {
                var tabla = '<table class="table table-striped"><thead><tr><th>Presentación</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';
                r.presentaciones.forEach(function(p) {
                    var estadoLabel = p.estado === 'aprobado' ? '<span class="label label-success">Aprobado</span>' : '<span class="label label-warning">Pendiente</span>';
                    var botonAprobar = p.estado === 'pendiente' ? `<button class="btn btn-success btn-xs btn-aprobar-presentacion" data-id-presentacion="${p.id}"><i class="fa fa-check"></i> Aprobar</button>` : '';
                    tabla += `<tr><td>${p.presentacion}</td><td>${estadoLabel}</td><td><div class="btn-group">${botonAprobar} <button class="btn btn-danger btn-xs btn-eliminar-presentacion" data-id-presentacion="${p.id}"><i class="fa fa-trash"></i></button></div></td></tr>`;
                });
                container.html(tabla + '</tbody></table>');
            } else { container.html('<div class="alert alert-info">Este fármaco no tiene presentaciones registradas.</div>'); }
        });
    }

    $('#form-nueva-presentacion').on('submit', function(e) {
        e.preventDefault();
        var idFarmaco = $('#id-farmaco-actual').val();
        var presentacion = $('#nueva-presentacion-nombre').val();
        var observaciones = $('#nueva-presentacion-obs').val();
        var esCronico = $('#nueva-presentacion-cronico').is(':checked') ? 1 : 0;

        $.post('<?= BASE_URL ?>ajax/medicamentosA.php', { action: 'crear_presentacion', id_farmaco: idFarmaco, presentacion: presentacion, observaciones: observaciones, es_cronico: esCronico })
        .done(function(r) {
            if (r.success) {
                $('#form-nueva-presentacion')[0].reset();
                cargarPresentaciones(idFarmaco);
            }
        });
    });
    
    $('#contenedor-tabla-presentaciones').on('click', '.btn-aprobar-presentacion, .btn-eliminar-presentacion', function() {
        var idPresentacion = $(this).data('id-presentacion');
        var idFarmaco = $('#id-farmaco-actual').val();
        var accion = $(this).hasClass('btn-aprobar-presentacion') ? 'aprobar_presentacion' : 'eliminar_presentacion';

        $.post('<?= BASE_URL ?>ajax/medicamentosA.php', { action: accion, id_presentacion: idPresentacion })
        .done(function(r) { if (r.success) { cargarPresentaciones(idFarmaco); } });
    });

    // --- ¡NUEVO! LÓGICA PARA APROBAR Y RECHAZAR FÁRMACOS ---
    $('#tabla-farmacos').on('click', '.btn-aprobar-farmaco, .btn-rechazar-farmaco', function() {
        var idFarmaco = $(this).data('id-farmaco');
        var esAprobar = $(this).hasClass('btn-aprobar-farmaco');
        var accion = esAprobar ? 'aprobar_farmaco' : 'rechazar_farmaco';
        var textoConfirmacion = esAprobar ? 'El fármaco y todas sus presentaciones pendientes serán aprobados y visibles para los doctores.' : 'El fármaco y TODAS sus presentaciones (aprobadas y pendientes) serán eliminados permanentemente.';
        var confirmButtonText = esAprobar ? 'Sí, ¡aprobar!' : 'Sí, ¡eliminar!';
        
        Swal.fire({
            title: '¿Está seguro?',
            text: textoConfirmacion,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Se usa el endpoint AJAX correcto
                $.post('<?= BASE_URL ?>ajax/medicamentosA.php', { action: accion, id_farmaco: idFarmaco })
                .done(function(r) {
                    if (r.success) {
                        Swal.fire('¡Éxito!', 'La operación se completó correctamente.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', r.error || 'No se pudo completar la operación.', 'error');
                    }
                }).fail(function(){
                     Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
                });
            }
        });
    });

});
</script>
<?php $scriptDinamico = ob_get_clean(); ?>