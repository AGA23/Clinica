<?php
// En Vistas/modulos/doctores.php

if (!isset($_SESSION["rol"]) || !in_array($_SESSION["rol"], ["Secretario", "Administrador"])) {
    echo '<script>window.location = "inicio";</script>';
    return;
}
if (isset($_POST["crear_doctor"])) { (new DoctoresC())->CrearDoctorC(); }
if (isset($_POST["editar_doctor"])) { (new DoctoresC())->ActualizarDoctorC(); }

$doctores = DoctoresC::ListarDoctoresC(); 
$lista_completa_consultorios = ConsultoriosC::ObtenerListaConsultoriosC();
$consultorios_para_crear = ($_SESSION['rol'] === 'Administrador') 
    ? $lista_completa_consultorios 
    : [ConsultoriosM::VerConsultorioPorId($_SESSION['id_consultorio'])];
?>

<section class="content-header">
    <h1>Gestor de Doctores</h1>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Doctores</li></ol>
</section>

<section class="content">
    <?php
    if (isset($_SESSION['mensaje_doctores'])) {
        echo '<div class="alert alert-' . $_SESSION['tipo_mensaje_doctores'] . ' alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $_SESSION['mensaje_doctores'] . '</div>';
        unset($_SESSION['mensaje_doctores'], $_SESSION['tipo_mensaje_doctores']);
    }
    ?>
    <div class="box box-primary">
        <div class="box-header with-border">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modal-nuevo-doctor"><i class="fa fa-plus"></i> Nuevo Doctor</button>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table id="tabla-doctores" class="table table-bordered table-hover table-striped dt-responsive" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Apellido</th>
                            <th>Nombre</th>
                            <th>Consultorio</th>
                            <th>Matrícula Nacional</th>
                            <th>Matrícula Provincial</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctores as $key => $value): ?>
                        <tr>
                            <td><?= ($key + 1) ?></td>
                            <td><?= htmlspecialchars($value["apellido"]) ?></td>
                            <td><?= htmlspecialchars($value["nombre"]) ?></td>
                            <td><?= htmlspecialchars($value["nombre_consultorio"] ?? 'Sin asignar') ?></td>
                            <td><?= htmlspecialchars($value["matricula_nacional"] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($value["matricula_provincial"] ?? 'N/A') ?></td>
                            <td>
                                 <div class="btn-group">
                                    <button class="btn btn-info btn-sm btn-editar-doctor" data-id="<?= $value["id"] ?>" data-toggle="modal" data-target="#modal-editar-doctor"><i class="fa fa-pencil"></i> Editar</button>
                                    <button class="btn btn-warning btn-sm btn-gestionar-horarios" data-id="<?= $value["id"] ?>" data-nombre="<?= htmlspecialchars($value['nombre'] . ' ' . $value['apellido']) ?>"><i class="fa fa-clock-o"></i> Horarios</button>
                                    <button class="btn btn-success btn-sm btn-asignar-tratamientos" data-id="<?= $value["id"] ?>" data-nombre="<?= htmlspecialchars($value['nombre'] . ' ' . $value['apellido']) ?>"><i class="fa fa-tags"></i> Tratamientos</button>
                                    <button class="btn btn-danger btn-sm btn-eliminar-doctor" data-id="<?= $value["id"] ?>" data-nombre="<?= htmlspecialchars($value['nombre'] . ' ' . $value['apellido']) ?>"><i class="fa fa-trash"></i> Borrar</button>
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

<!-- ========================================================================= -->
<!-- MODALES, ESTILOS Y SCRIPTS                                                -->
<!-- ========================================================================= -->

<!-- Modal Nuevo Doctor -->
<div class="modal fade" id="modal-nuevo-doctor">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="">
            <div class="modal-header"><h4 class="modal-title">Registrar Nuevo Doctor</h4></div>
            <div class="modal-body">
                <div class="form-group"><label>Nombre:</label><input type="text" name="nombre" class="form-control" required></div>
                <div class="form-group"><label>Apellido:</label><input type="text" name="apellido" class="form-control" required></div>
                <div class="form-group"><label>Correo Electrónico:</label><input type="email" name="email" class="form-control"></div>
                <div class="form-group"><label>Usuario:</label><input type="text" name="usuario" class="form-control" required></div>
                <div class="form-group"><label>Contraseña:</label><input type="password" name="clave" class="form-control" required></div>
                <div class="form-group"><label>Sexo:</label><select name="sexo" class="form-control"><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select></div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>Matrícula Nacional:</label><input type="text" name="matricula_nacional" class="form-control"></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Matrícula Provincial:</label><input type="text" name="matricula_provincial" class="form-control"></div></div>
                </div>
                <div class="form-group"><label>Consultorio:</label><select name="id_consultorio" class="form-control" required><option value="">Seleccione...</option><?php foreach ($consultorios_para_crear as $c): if($c) { ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option><?php } endforeach; ?></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" name="crear_doctor">Guardar</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Editar Doctor -->
<div class="modal fade" id="modal-editar-doctor">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="">
            <div class="modal-header"><h4 class="modal-title">Editar Doctor</h4></div>
            <div class="modal-body">
                <input type="hidden" name="id_doctor_editar" id="id_doctor_editar">
                <div class="form-group"><label>Nombre:</label><input type="text" name="nombre_editar" id="nombre_editar" class="form-control" required></div>
                <div class="form-group"><label>Apellido:</label><input type="text" name="apellido_editar" id="apellido_editar" class="form-control" required></div>
                <div class="form-group"><label>Correo Electrónico:</label><input type="email" name="email_editar" id="email_editar" class="form-control"></div>
                <div class="form-group"><label>Usuario:</label><input type="text" name="usuario_editar" id="usuario_editar" class="form-control" required></div>
                <div class="form-group"><label>Nueva Contraseña:</label><input type="password" name="clave_editar" class="form-control" placeholder="Dejar en blanco para no cambiar"></div>
                <div class="form-group"><label>Sexo:</label><select name="sexo_editar" id="sexo_editar" class="form-control"><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select></div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>Matrícula Nacional:</label><input type="text" name="matricula_nacional_editar" id="matricula_nacional_editar" class="form-control"></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Matrícula Provincial:</label><input type="text" name="matricula_provincial_editar" id="matricula_provincial_editar" class="form-control"></div></div>
                </div>
                <div class="form-group"><label>Consultorio:</label><select name="id_consultorio_editar" id="id_consultorio_editar" class="form-control" required><option value="">Seleccione un Consultorio...</option><?php foreach ($lista_completa_consultorios as $c): if($c) { ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option><?php } endforeach; ?></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" name="editar_doctor">Guardar Cambios</button></div>
        </form>
    </div></div>
</div>

<!-- [NUEVO] Modal para Gestionar Horarios -->
<div class="modal fade" id="modal-gestionar-horarios">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h4 class="modal-title">Gestionar Horarios de <strong id="nombre-doctor-horarios"></strong></h4></div>
        <div class="modal-body">
            <input type="hidden" id="id-doctor-horarios">
            <table class="table table-bordered"><thead><tr><th>Día</th><th style="width: 40px;">Activo</th><th>Hora Inicio</th><th>Hora Fin</th></tr></thead>
            <tbody id="tabla-horarios-doctor"></tbody></table>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btn-guardar-horarios-doctor">Guardar Horarios</button></div>
    </div></div>
</div>

<!-- [NUEVO] Modal para Asignar Tratamientos -->
<div class="modal fade" id="modal-asignar-tratamientos">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h4 class="modal-title">Asignar Tratamientos a <strong id="nombre-doctor-tratamientos"></strong></h4></div>
        <div class="modal-body">
            <input type="hidden" id="id-doctor-tratamientos">
            <p class="text-muted">Seleccione los tratamientos que realiza este doctor.</p>
            <div id="contenedor-checkboxes-tratamientos" style="max-height: 400px; overflow-y: auto;"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btn-guardar-asignacion-doctor">Guardar Asignación</button></div>
    </div></div>
</div>

<?php ob_start(); ?>
<script>
$(function() {
    var tablaDoctores = $('#tabla-doctores').DataTable({ "language": { "url": "<?= BASE_URL ?>Vistas/plugins/datatables/Spanish.json" }});

    // --- LÓGICA PARA EDITAR DOCTOR ---
    $('#tabla-doctores tbody').on('click', '.btn-editar-doctor', function() {
        var idDoctor = $(this).data('id');
        $.post('<?= BASE_URL ?>ajax/doctoresA.php?action=obtener', { id_doctor: idDoctor })
        .done(function(response) {
            if (response.success) {
                var d = response.datos;
                $('#id_doctor_editar').val(d.id);
                $('#nombre_editar').val(d.nombre);
                $('#apellido_editar').val(d.apellido);
                $('#email_editar').val(d.email);
                $('#usuario_editar').val(d.usuario);
                $('#sexo_editar').val(d.sexo);
                $('#id_consultorio_editar').val(d.id_consultorio);
                $('#matricula_nacional_editar').val(d.matricula_nacional);
                $('#matricula_provincial_editar').val(d.matricula_provincial);
            } else { Swal.fire('Error', 'No se pudieron cargar los datos del doctor.', 'error'); }
        }).fail(function() { Swal.fire('Error', 'Error de comunicación con el servidor.', 'error'); });
    });

    // --- LÓGICA PARA GESTIONAR HORARIOS ---
    $('#tabla-doctores tbody').on('click', '.btn-gestionar-horarios', function() {
        var idDoctor = $(this).data('id');
        $('#id-doctor-horarios').val(idDoctor);
        $('#nombre-doctor-horarios').text($(this).data('nombre'));
        var tablaBody = $('#tabla-horarios-doctor').html('<tr><td colspan="4" class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</td></tr>');
        
        $.post('<?= BASE_URL ?>ajax/doctoresA.php?action=obtenerHorarios', { id_doctor: idDoctor })
        .done(function(r) {
            tablaBody.empty();
            var dias = {1:'Lunes', 2:'Martes', 3:'Miércoles', 4:'Jueves', 5:'Viernes', 6:'Sábado', 7:'Domingo'};
            var horariosDoctor = r.horarios.reduce((acc, h) => { acc[h.dia_semana] = {inicio: h.hora_inicio, fin: h.hora_fin}; return acc; }, {});

            for (let i = 1; i <= 7; i++) {
                var activo = horariosDoctor[i] ? 'checked' : '';
                var disabled = horariosDoctor[i] ? '' : 'disabled';
                var inicio = horariosDoctor[i] ? horariosDoctor[i].inicio : '';
                var fin = horariosDoctor[i] ? horariosDoctor[i].fin : '';
                tablaBody.append(`<tr><td>${dias[i]}</td><td><input type="checkbox" name="activo" data-dia="${i}" ${activo}></td><td><input type="time" name="inicio" class="form-control" data-dia="${i}" value="${inicio}" ${disabled}></td><td><input type="time" name="fin" class="form-control" data-dia="${i}" value="${fin}" ${disabled}></td></tr>`);
            }
            $('#modal-gestionar-horarios').modal('show');
        });
    });

    $('#tabla-horarios-doctor').on('change', 'input[type="checkbox"]', function() {
        $(this).closest('tr').find('input[type="time"]').prop('disabled', !this.checked);
    });

    $('#btn-guardar-horarios-doctor').on('click', function() {
        var idDoctor = $('#id-doctor-horarios').val();
        var horarios = [];
        $('#tabla-horarios-doctor tr').each(function() {
            var fila = $(this);
            if (fila.find('input[type="checkbox"]').is(':checked')) {
                horarios.push({ dia_semana: fila.find('input[name="activo"]').data('dia'), hora_inicio: fila.find('input[name="inicio"]').val(), hora_fin: fila.find('input[name="fin"]').val() });
            }
        });
        $.post('<?= BASE_URL ?>ajax/doctoresA.php?action=guardarHorarios', { id_doctor: idDoctor, horarios: JSON.stringify(horarios) })
        .done(function(r){ if(r.success) { $('#modal-gestionar-horarios').modal('hide'); Swal.fire('¡Guardado!', 'Los horarios han sido actualizados.', 'success'); } else { Swal.fire('Error', 'No se pudo guardar.', 'error'); }});
    });

    // --- LÓGICA PARA ASIGNAR TRATAMIENTOS ---
    $('#tabla-doctores tbody').on('click', '.btn-asignar-tratamientos', function() {
        var idDoctor = $(this).data('id');
        $('#id-doctor-tratamientos').val(idDoctor);
        $('#nombre-doctor-tratamientos').text($(this).data('nombre'));
        var container = $('#contenedor-checkboxes-tratamientos').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');
        
        $.post('<?= BASE_URL ?>ajax/doctoresA.php?action=obtenerTratamientosParaAsignar', { id_doctor: idDoctor })
        .done(function(r){
            container.empty();
            if(r.error_message) { container.append(`<div class="alert alert-warning">${r.error_message}</div>`); }
            if(r.success && r.todos) {
                r.todos.forEach(t => container.append(`<div class="checkbox"><label><input type="checkbox" value="${t.id}" ${r.asignados.includes(t.id.toString()) ? 'checked' : ''}> ${t.nombre}</label></div>`));
            }
        });
        $('#modal-asignar-tratamientos').modal('show');
    });

    $('#btn-guardar-asignacion-doctor').on('click', function() {
        var idDoctor = $('#id-doctor-tratamientos').val();
        var ids = [];
        $('#contenedor-checkboxes-tratamientos input:checked').each(function() { ids.push($(this).val()); });
        $.post('<?= BASE_URL ?>ajax/doctoresA.php?action=guardarTratamientosAsignados', { id_doctor: idDoctor, ids_tratamientos: JSON.stringify(ids) })
        .done(function(r){ if(r.success) { $('#modal-asignar-tratamientos').modal('hide'); Swal.fire('¡Guardado!', 'Los tratamientos han sido actualizados.', 'success'); } else { Swal.fire('Error', 'No se pudo guardar.', 'error'); }});
    });

    // --- LÓGICA PARA ELIMINAR DOCTOR ---
    $('#tabla-doctores tbody').on('click', '.btn-eliminar-doctor', function() {
        var idDoctor = $(this).data('id');
        var nombreDoctor = $(this).data('nombre');
        
        Swal.fire({ title: '¿Está seguro?', text: "¡El doctor '" + nombreDoctor + "' será eliminado!", icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, ¡eliminar!' })
        .then((result) => {
            if (result.isConfirmed) {
                $.post('<?= BASE_URL ?>ajax/doctoresA.php?action=eliminar', { id_doctor: idDoctor })
                .done(function(response) {
                    if (response.success) {
                        Swal.fire('¡Eliminado!', 'El doctor ha sido eliminado.', 'success').then(() => { location.reload(); });
                    } else {
                        Swal.fire('Error', response.error || 'No se pudo eliminar.', 'error');
                    }
                }).fail(function() { Swal.fire('Error', 'Error de comunicación.', 'error'); });
            }
        });
    });
});
</script>
<?php $scriptDinamico = ob_get_clean(); ?>