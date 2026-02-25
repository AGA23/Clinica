<?php
// En Vistas/modulos/consultorios.php

// Procesamiento de formularios POST
if (isset($_POST["crear_consultorio"])) { (new ConsultoriosC())->CrearConsultorioC(); }
if (isset($_POST["editar_consultorio"])) { (new ConsultoriosC())->ActualizarConsultorioC(); }

// Carga de datos para la tabla principal
$consultorios = ConsultoriosC::VerParaAdminC();

// Función helper para mostrar el estado del consultorio
function obtenerEstadoVisual($consultorio) {
    $hora_actual = time();

    if (!empty($consultorio['estado_manual'])) {
        $texto = ucfirst($consultorio['estado_manual']);
        return ['texto' => $texto, 'clase' => 'estado-' . strtolower($texto), 'es_manual' => true];
    }

    if (empty($consultorio['horario_consultorio_hoy'])) {
        return ['texto' => 'Cerrado', 'clase' => 'estado-Cerrado', 'es_manual' => false];
    }

    $horario_parts = explode('|', $consultorio['horario_consultorio_hoy']);
    if (count($horario_parts) < 2) {
        return ['texto' => 'Cerrado (Horario Inválido)', 'clase' => 'estado-Cerrado', 'es_manual' => false];
    }

    list($apertura, $cierre) = $horario_parts;

    if ($hora_actual < strtotime($apertura) || $hora_actual >= strtotime($cierre)) {
        return ['texto' => 'Cerrado (Fuera de Horario)', 'clase' => 'estado-Cerrado', 'es_manual' => false];
    }

    if (empty($consultorio['doctores_trabajando_hoy'])) {
        return ['texto' => 'Disponible (Sin Doctores)', 'clase' => 'estado-Disponible-Sin-Doctores', 'es_manual' => false];
    }

    $doctores_hoy = explode(';', $consultorio['doctores_trabajando_hoy']);
    foreach ($doctores_hoy as $doctor_turno) {
        $partes_doc = explode(':', $doctor_turno);
        if (count($partes_doc) < 2) continue;
        $horas_doc = explode('-', end($partes_doc));
        if (count($horas_doc) < 2) continue;
        list($inicio_doc, $fin_doc) = $horas_doc;
        if ($hora_actual >= strtotime($inicio_doc) && $hora_actual < strtotime($fin_doc)) {
            return ['texto' => 'Abierto', 'clase' => 'estado-Abierto', 'es_manual' => false];
        }
    }

    return ['texto' => 'Disponible (Sin Doctores)', 'clase' => 'estado-Disponible-Sin-Doctores', 'es_manual' => false];
}
?>

<section class="content-header">
    <h1>Gestor de Sedes y Consultorios</h1>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Sedes</li></ol>
</section>

<section class="content">
    <?php if (isset($_SESSION['mensaje_consultorios'])): ?>
        <div class="alert alert-<?= $_SESSION['tipo_mensaje_consultorios'] ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <?= $_SESSION['mensaje_consultorios'] ?>
        </div>
        <?php unset($_SESSION['mensaje_consultorios'], $_SESSION['tipo_mensaje_consultorios']); ?>
    <?php endif; ?>
    
    <div class="box box-primary">
        <div class="box-header with-border">
            <?php if ($_SESSION['rol'] === 'Administrador'): ?>
                <button class="btn btn-primary" data-toggle="modal" data-target="#modal-nuevo-consultorio"><i class="fa fa-plus"></i> Nueva Sede</button>
            <?php endif; ?>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table id="tabla-consultorios" class="table table-bordered table-hover table-striped dt-responsive" width="100%">
                    <thead>
                        <tr>
                            <th style="width: 10px;">#</th>
                            <th>Nombre Sede</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Estado Actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultorios as $i => $cons): ?>
                            <?php $estado = obtenerEstadoVisual($cons); ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($cons['nombre_consultorio']) ?></td>
                                <td><?= htmlspecialchars($cons['direccion'] ?? 'No especificada') ?></td>
                                <td><?= htmlspecialchars($cons['telefono'] ?? 'No especificado') ?></td>
                                <td><span class="label <?= $estado['clase'] ?> <?= $estado['es_manual'] ? 'estado-manual' : '' ?>"><?= $estado['texto'] ?></span></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-info btn-sm btn-editar" data-id="<?= $cons['id'] ?>" data-toggle="modal" data-target="#modal-editar-consultorio"><i class="fa fa-pencil"></i> Editar</button>
                                        <?php if ($_SESSION['rol'] === 'Administrador'): ?>
                                            <button class="btn btn-success btn-sm btn-asignar-tratamientos" data-id="<?= $cons['id'] ?>" data-nombre="<?= htmlspecialchars($cons['nombre_consultorio']) ?>"><i class="fa fa-tags"></i> Tratamientos</button>
                                            <button class="btn btn-warning btn-sm btn-cambiar-estado" data-id="<?= $cons['id'] ?>" data-nombre="<?= htmlspecialchars($cons['nombre_consultorio']) ?>"><i class="fa fa-exchange"></i> Estado</button>
                                            <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?= $cons['id'] ?>" data-nombre="<?= htmlspecialchars($cons['nombre_consultorio']) ?>"><i class="fa fa-trash"></i> Borrar</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- ====================== MODALES ====================== -->
<?php if ($_SESSION['rol'] === 'Administrador'): ?>
    <!-- Modal Nuevo Consultorio -->
    <div class="modal fade" id="modal-nuevo-consultorio">
        <div class="modal-dialog"><div class="modal-content">
            <form method="post" action="">
                <div class="modal-header"><h4 class="modal-title">Nueva Sede</h4></div>
                <div class="modal-body">
                    <div class="form-group"><label>Nombre de la Sede</label><input type="text" name="nombre" class="form-control" required placeholder="Ej: Sede Centro"></div>
                    <div class="form-group"><label>Dirección</label><input type="text" name="direccion" class="form-control" placeholder="Ej: Av. Principal 123"></div>
                    <div class="form-group"><label>Teléfono</label><input type="text" name="telefono" class="form-control" placeholder="Ej: 11-1234-5678"></div>
                    <div class="form-group"><label>Email de Contacto</label><input type="email" name="email" class="form-control" placeholder="Ej: centro@clinica.com"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" name="crear_consultorio">Guardar</button></div>
            </form>
        </div></div>
    </div>

    <!-- Modal Cambiar Estado Manual -->
    <div class="modal fade" id="modal-estado-consultorio">
        <div class="modal-dialog"><div class="modal-content">
            <form id="form-cambiar-estado">
                <div class="modal-header"><h4 class="modal-title">Cambiar Estado Manual</h4></div>
                <div class="modal-body">
                    <p>Establecer un estado manual para <strong id="nombre-consultorio-modal"></strong>.</p>
                    <input type="hidden" name="id_consultorio" id="estado-id-consultorio">
                    <div class="form-group">
                        <label>Nuevo Estado</label>
                        <select name="estado" class="form-control">
                            <option value="">Quitar Estado Manual (Volver a Horario)</option>
                            <option value="disponible">Disponible (Forzar)</option>
                            <option value="ocupado">Ocupado</option>
                            <option value="mantenimiento">Mantenimiento</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Motivo (opcional)</label><textarea name="motivo" class="form-control" rows="2" placeholder="Ej: Limpieza profunda..."></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-warning">Guardar Cambio</button></div>
            </form>
        </div></div>
    </div>

    <!-- Modal Asignar Tratamientos -->
    <div class="modal fade" id="modal-asignar-tratamientos-consultorio">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h4 class="modal-title">Asignar Tratamientos a: <strong id="nombre-consultorio-tratamientos"></strong></h4></div>
                <div class="modal-body">
                    <input type="hidden" id="id-consultorio-tratamientos">
                    <p class="text-muted">Seleccione todos los tratamientos que se ofrecen en este consultorio.</p>
                    <div class="form-group" id="contenedor-checkboxes-tratamientos" style="max-height: 400px; overflow-y: auto;">
                        <p class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-guardar-asignacion-consultorio">Guardar Asignación</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Editar Consultorio -->
<div class="modal fade" id="modal-editar-consultorio">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header"><h4 class="modal-title">Editar Sede</h4></div>
                <div class="modal-body">
                    <input type="hidden" name="id_consultorio_editar" id="id_consultorio_editar">
                    <div class="form-group">
                        <label>Nombre de la Sede</label>
                        <input type="text" name="nombre_editar" id="nombre_consultorio_editar" class="form-control" required <?= ($_SESSION['rol'] !== 'Administrador') ? 'readonly' : '' ?>>
                    </div>
                    <div class="form-group"><label>Dirección</label><input type="text" name="direccion_editar" id="direccion_consultorio_editar" class="form-control"></div>
                    <div class="form-group"><label>Teléfono</label><input type="text" name="telefono_editar" id="telefono_consultorio_editar" class="form-control"></div>
                    <div class="form-group"><label>Email de Contacto</label><input type="email" name="email_editar" id="email_consultorio_editar" class="form-control"></div>
                    <hr>
                    <h4 class="box-title">Gestionar Horarios de Apertura</h4>
                    <table class="table table-bordered">
                        <thead><tr><th>Día</th><th style="width: 40px;">Activo</th><th>Hora Apertura</th><th>Hora Cierre</th></tr></thead>
                        <tbody id="tabla-horarios-editar">
                            <?php $dias = ['1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado','7'=>'Domingo']; ?>
                            <?php foreach($dias as $num => $nombre): ?>
                                <tr>
                                    <td><?= $nombre ?></td>
                                    <td><input type="checkbox" name="horario[<?= $num ?>][activo]"></td>
                                    <td><input type="time" name="horario[<?= $num ?>][apertura]" class="form-control" disabled></td>
                                    <td><input type="time" name="horario[<?= $num ?>][cierre]" class="form-control" disabled></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" name="editar_consultorio">Guardar Cambios</button></div>
            </form>
        </div>
    </div>
</div>

<style>
.estado-Abierto, .estado-disponible { background-color: #00a65a !important; }
.estado-Cerrado, .estado-ocupado { background-color: #f39c12 !important; }
.estado-mantenimiento { background-color: #605ca8 !important; }
.estado-Disponible-Sin-Doctores { background-color: #00c0ef !important; color: white !important; } 
.estado-manual::after { content: " (M)"; font-weight: bold; }
</style>

<?php ob_start(); ?>
<script>
$(function() {
    var tabla = $('#tabla-consultorios').DataTable({ "language": { "url": "<?= BASE_URL ?>Vistas/plugins/datatables/Spanish.json" }});

    // Editar consultorio
    $('#tabla-consultorios tbody').on('click', '.btn-editar', function() {
        var id = $(this).data('id');
        $.post('<?= BASE_URL ?>ajax/consultoriosA.php?action=obtenerParaEditar', { id_consultorio: id })
        .done(function(r) {
            if (r.success) {
                $('#id_consultorio_editar').val(id);
                $('#nombre_consultorio_editar').val(r.datos.nombre);
                $('#direccion_consultorio_editar').val(r.datos.direccion);
                $('#telefono_consultorio_editar').val(r.datos.telefono);
                $('#email_consultorio_editar').val(r.datos.email);

                var tablaHorarios = $('#tabla-horarios-editar');
                tablaHorarios.find('input[type="time"]').val('').prop('disabled', true);
                tablaHorarios.find('input[type="checkbox"]').prop('checked', false);

                if(r.horarios){
                    r.horarios.forEach(function(h) {
                        if(!h.dia_semana) return;
                        var cb = tablaHorarios.find('input[name="horario['+ h.dia_semana +'][activo]"]');
                        cb.prop('checked', true);
                        var fila = cb.closest('tr');
                        fila.find('input[name*="[apertura]"]').val(h.hora_apertura || '').prop('disabled', false);
                        fila.find('input[name*="[cierre]"]').val(h.hora_cierre || '').prop('disabled', false);
                    });
                }
            } else { Swal.fire('Error', r.error || 'No se pudieron cargar los datos.', 'error'); }
        }).fail(function() { Swal.fire('Error', 'Error de comunicación con el servidor.', 'error'); });
    });

    $('#tabla-horarios-editar').on('change', 'input[type="checkbox"]', function() {
        $(this).closest('tr').find('input[type="time"]').prop('disabled', !this.checked);
    });

    <?php if ($_SESSION['rol'] === 'Administrador'): ?>
        // Eliminar consultorio
        $('#tabla-consultorios tbody').on('click', '.btn-eliminar', function() {
            var id = $(this).data('id');
            var nombre = $(this).data('nombre');
            Swal.fire({ title: '¿Está seguro?', text: "¡El consultorio '" + nombre + "' será eliminado!", icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, ¡bórralo!'})
            .then((result) => {
                if(result.isConfirmed){
                    $.post('<?= BASE_URL ?>ajax/consultoriosA.php?action=eliminar', { id_consultorio: id })
                    .done(function (r) { if(r.success){ location.reload(); } else { Swal.fire('Error', r.error || 'No se pudo eliminar.', 'error'); } })
                    .fail(function () { Swal.fire('Error', 'Error de comunicación.', 'error'); });
                }
            });
        });

        // Cambiar estado manual
        $('#tabla-consultorios tbody').on('click', '.btn-cambiar-estado', function() {
            $('#estado-id-consultorio').val($(this).data('id'));
            $('#nombre-consultorio-modal').text($(this).data('nombre'));
            $('#modal-estado-consultorio').modal('show');
        });

        $('#form-cambiar-estado').submit(function (e) {
            e.preventDefault();
            $.post('<?= BASE_URL ?>ajax/consultoriosA.php?action=cambiarEstado', $(this).serialize())
            .done(function(r){ if(r.success){ location.reload(); } else { Swal.fire('Error', r.error || 'No se pudo cambiar el estado.', 'error'); } })
            .fail(function(){ Swal.fire('Error', 'Error de comunicación con el servidor.', 'error'); });
        });

        // Asignar tratamientos
        $('#tabla-consultorios tbody').on('click', '.btn-asignar-tratamientos', function() {
            var idConsultorio = $(this).data('id');
            var nombreConsultorio = $(this).data('nombre');

            $('#id-consultorio-tratamientos').val(idConsultorio);
            $('#nombre-consultorio-tratamientos').text(nombreConsultorio);
            $('#modal-asignar-tratamientos-consultorio').modal('show');

            var container = $('#contenedor-checkboxes-tratamientos').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');

            $.post('<?= BASE_URL ?>ajax/consultoriosA.php?action=obtenerTratamientosParaAsignar', { id_consultorio: idConsultorio })
            .done(function(r){
                container.empty();
                if(r.success && r.todos.length > 0){
                    r.todos.forEach(function(t){
                        var isChecked = r.asignados.map(String).includes(t.id.toString());
                        container.append(`<div class="checkbox"><label><input type="checkbox" value="${t.id}" ${isChecked ? 'checked' : ''}> ${t.nombre}</label></div>`);
                    });
                } else { container.append('<p class="text-muted">No hay tratamientos en el sistema para asignar.</p>'); }
            }).fail(function(){ container.html('<p class="text-danger">Error al cargar los tratamientos.</p>'); });
        });

        $('#btn-guardar-asignacion-consultorio').click(function() {
            var idConsultorio = $('#id-consultorio-tratamientos').val();
            var ids = [];
            $('#contenedor-checkboxes-tratamientos input:checked').each(function(){ ids.push($(this).val()); });

            $.post('<?= BASE_URL ?>ajax/consultoriosA.php?action=guardarTratamientosAsignados', { id_consultorio: idConsultorio, ids_tratamientos: JSON.stringify(ids) })
            .done(function(r){
                if(r.success){
                    $('#modal-asignar-tratamientos-consultorio').modal('hide');
                    Swal.fire('¡Guardado!', 'La lista de tratamientos del consultorio ha sido actualizada.', 'success');
                } else { Swal.fire('Error', 'No se pudo guardar la asignación.', 'error'); }
            }).fail(function(){ Swal.fire('Error', 'Error de comunicación con el servidor.', 'error'); });
        });
    <?php endif; ?>
});
</script>
<?php $scriptDinamico = ob_get_clean(); ?>
