<?php
// En Vistas/modulos/pacientes.php

// 1. SEGURIDAD
if (!isset($_SESSION["rol"]) || !in_array($_SESSION["rol"], ["Secretario", "Doctor", "Administrador"])) {
    echo '<script>window.location = "inicio";</script>';
    return;
}

// 2. PROCESAR FORMULARIOS
if (isset($_POST["crear_paciente"])) { (new PacientesC())->CrearPacienteC(); }
if (isset($_POST["editar_paciente"])) { (new PacientesC())->ActualizarPacienteC(); }

// 3. OBTENER DATOS
$pacientes = PacientesC::ListarPacientesC();

// 🆕 Obtener Obras Sociales para los selectores (Carga segura)
$obrasSociales = [];
if (class_exists('ObrasSocialesC')) {
    $obrasSociales = ObrasSocialesC::ObtenerTodasC();
}
?>

<section class="content-header">
    <h1>Gestor de Pacientes</h1>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Pacientes</li></ol>
</section>

<section class="content">
    <?php
    if (isset($_SESSION['mensaje_pacientes'])) {
        echo '<div class="alert alert-' . $_SESSION['tipo_mensaje_pacientes'] . ' alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $_SESSION['mensaje_pacientes'] . '</div>';
        unset($_SESSION['mensaje_pacientes'], $_SESSION['tipo_mensaje_pacientes']);
    }
    ?>
    
    <div class="box box-primary">
        <div class="box-header with-border">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modal-nuevo-paciente">
                <i class="fa fa-plus"></i> Nuevo Paciente
            </button>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table id="tabla-pacientes" class="table table-bordered table-hover table-striped dt-responsive" width="100%">
                    <thead>
                        <tr>
                            <th style="width: 10px;">#</th>
                            <th>Apellido</th>
                            <th>Nombre</th>
                            <th>Documento</th>
                            <th>Obra Social</th> 
                            <th>Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pacientes as $key => $value): ?>
                        <tr>
                            <td><?= ($key + 1) ?></td>
                            <td><?= htmlspecialchars($value["apellido"]) ?></td>
                            <td><?= htmlspecialchars($value["nombre"]) ?></td>
                            <td><?= htmlspecialchars(($value["tipo_documento"] ?? '') . ' ' . ($value["numero_documento"] ?? 'N/A')) ?></td>
                            <!-- Muestra la OS si el query la trae, sino N/A -->
                            <td><?= htmlspecialchars($value["nombre_obra_social"] ?? 'N/A') ?></td> 
                            <td><?= htmlspecialchars($value["usuario"]) ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-info btn-sm btn-editar-paciente" 
                                            data-id="<?= $value["id"] ?>" 
                                            data-toggle="modal" 
                                            data-target="#modal-editar-paciente">
                                        <i class="fa fa-pencil"></i> Editar
                                    </button>
                                    <button class="btn btn-danger btn-sm btn-eliminar-paciente" 
                                            data-id="<?= $value["id"] ?>" 
                                            data-nombre="<?= htmlspecialchars($value['nombre'] . ' ' . $value['apellido']) ?>">
                                        <i class="fa fa-trash"></i> Borrar
                                    </button>
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
<!-- MODAL NUEVO PACIENTE (CORREGIDO CON IDs)                                  -->
<!-- ========================================================================= -->
<div class="modal fade" id="modal-nuevo-paciente">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Registrar Nuevo Paciente</h4>
                </div>
                <div class="modal-body">
                    
                    <!-- DATOS PERSONALES -->
                    <div class="form-group"><label>Nombre:</label><input type="text" name="nombre" class="form-control" required></div>
                    <div class="form-group"><label>Apellido:</label><input type="text" name="apellido" class="form-control" required></div>
                    
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Tipo Documento:</label>
                                <select name="tipo_documento" class="form-control" required>
                                    <option value="DNI">DNI</option><option value="Pasaporte">Pasaporte</option><option value="Cédula">Cédula</option><option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="form-group">
                                <label>Número Documento:</label>
                                <input type="text" name="numero_documento" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <!-- 🆕 SECCIÓN COBERTURA -->
                    <h4 class="text-primary" style="font-size:16px; border-bottom:1px solid #ddd; padding-bottom:5px;">Cobertura Médica</h4>
                    
                    <div class="form-group">
                        <label>Obra Social / Prepaga:</label>
                        <!-- 🛑 AGREGADO id="nuevo_os" -->
                        <select name="nuevoIdObraSocial" id="nuevo_os" class="form-control select-obra-social" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($obrasSociales as $os): ?>
                                <option value="<?= $os['id'] ?>"><?= htmlspecialchars($os['nombre']) ?> (<?= strtoupper($os['tipo']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Plan:</label>
                                <!-- 🛑 AGREGADO id="nuevo_plan" -->
                                <select name="nuevoPlan" id="nuevo_plan" class="form-control select-plan" disabled>
                                    <option value="">Seleccione OS primero</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nro. Afiliado:</label>
                                <input type="text" name="nuevoNumeroAfiliado" class="form-control" placeholder="Opcional">
                            </div>
                        </div>
                    </div>

                    <hr>
                    <!-- DATOS DE CUENTA -->
                    <div class="form-group"><label>Usuario (Login):</label><input type="text" name="usuario" id="usuario_crear" class="form-control" required></div>
                    <div class="form-group"><label>Contraseña:</label><input type="password" name="clave" class="form-control" placeholder="Mínimo 6 caracteres" required></div>
                    <div class="form-group"><label>Correo Electrónico:</label><input type="email" name="correo" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" name="crear_paciente">Guardar Paciente</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- ========================================================================= -->
<!-- MODAL EDITAR PACIENTE                                                     -->
<!-- ========================================================================= -->
<div class="modal fade" id="modal-editar-paciente">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header" style="background:#f39c12; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Editar Paciente</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_paciente_editar" id="id_paciente_editar">
                    
                    <div class="form-group"><label>Nombre:</label><input type="text" name="nombre_editar" id="nombre_editar" class="form-control" required></div>
                    <div class="form-group"><label>Apellido:</label><input type="text" name="apellido_editar" id="apellido_editar" class="form-control" required></div>
                    
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Tipo Documento:</label>
                                <select name="tipo_documento_editar" id="tipo_documento_editar" class="form-control" required>
                                    <option value="DNI">DNI</option><option value="Pasaporte">Pasaporte</option><option value="Cédula">Cédula</option><option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="form-group">
                                <label>Número Documento:</label>
                                <input type="text" name="numero_documento_editar" id="numero_documento_editar" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <!-- 🆕 SECCIÓN COBERTURA EDITAR -->
                    <h4 class="text-primary" style="font-size:16px; border-bottom:1px solid #ddd; padding-bottom:5px;">Cobertura Médica</h4>
                    
                    <div class="form-group">
                        <label>Obra Social / Prepaga:</label>
                        <select name="editarIdObraSocial" id="id_obra_social_editar" class="form-control select-obra-social" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($obrasSociales as $os): ?>
                                <option value="<?= $os['id'] ?>"><?= htmlspecialchars($os['nombre']) ?> (<?= strtoupper($os['tipo']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Plan:</label>
                                <select name="editarPlan" id="plan_editar" class="form-control select-plan" disabled>
                                    <option value="">Seleccione OS primero</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nro. Afiliado:</label>
                                <input type="text" name="editarNumeroAfiliado" id="numero_afiliado_editar" class="form-control">
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="form-group"><label>Usuario:</label><input type="text" name="usuario_editar" id="usuario_editar" class="form-control" required></div>
                    <div class="form-group"><label>Nueva Contraseña:</label><input type="password" name="clave_editar" class="form-control" placeholder="Dejar en blanco para no cambiar"></div>
                    <div class="form-group"><label>Correo Electrónico:</label><input type="email" name="correo_editar" id="correo_editar" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" name="editar_paciente">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function() {
    // Inicializar Tabla
    var tablaPacientes = $('#tabla-pacientes').DataTable({ "language": { "url": "<?= BASE_URL ?>Vistas/plugins/datatables/Spanish.json" }});

    // =========================================================================
    // 🟢 FUNCIÓN PARA CARGAR PLANES
    // =========================================================================
    function cargarPlanes(idOS, $selectDestino, planPreseleccionado = null) {
        
        // Feedback visual inmediato
        $selectDestino.html('<option value="">Cargando...</option>');
        $selectDestino.prop('disabled', true);

        if(idOS > 0) {
            $.ajax({
                url: '<?= BASE_URL ?>ajax/pacientesA.php',
                method: 'POST',
                data: { action: 'obtenerPlanes', id_obra_social: idOS },
                dataType: 'json',
                success: function(r) {
                    
                    $selectDestino.empty(); // Limpiar

                    if(r.success && r.planes && r.planes.length > 0) {
                        $selectDestino.append('<option value="">Seleccionar Plan</option>');
                        
                        r.planes.forEach(function(plan){
                            // Usamos nombre_plan como valor
                            $selectDestino.append(`<option value="${plan.nombre_plan}">${plan.nombre_plan}</option>`);
                        });

                        // Pre-seleccionar si estamos editando
                        if(planPreseleccionado) {
                            $selectDestino.val(planPreseleccionado);
                        }
                    } else {
                        // Si no hay planes, mostrar opción genérica
                        $selectDestino.append('<option value="General" selected>General / Único</option>');
                    }

                    // SIEMPRE HABILITAR AL FINAL
                    $selectDestino.prop('disabled', false);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error AJAX:", textStatus);
                    // En caso de error, habilitar como manual
                    $selectDestino.empty().append('<option value="General">General (Manual)</option>');
                    $selectDestino.prop('disabled', false); 
                }
            });
        } else {
            $selectDestino.html('<option value="">Seleccione OS primero</option>');
            $selectDestino.prop('disabled', true);
        }
    }

    // =========================================================================
    // EVENTOS DE SELECCIÓN (NUEVO Y EDITAR)
    // =========================================================================

    // 1. Modal Nuevo Paciente
    $('#nuevo_os').on('change', function(){
        cargarPlanes($(this).val(), $('#nuevo_plan'));
    });

    // 2. Modal Editar Paciente
    $('#id_obra_social_editar').on('change', function(){
        cargarPlanes($(this).val(), $('#plan_editar'));
    });

    // =========================================================================
    // LÓGICA PARA CARGAR DATOS AL EDITAR
    // =========================================================================
    $('#tabla-pacientes tbody').on('click', '.btn-editar-paciente', function() {
        var pacienteId = $(this).data('id');
        
        $.post('<?= BASE_URL ?>ajax/pacientesA.php', { action: 'obtener', id_paciente: pacienteId })
        .done(function(response) {
            // Parseo seguro
            if(typeof response === 'string') response = JSON.parse(response);

            if (response.success) {
                var datos = response.datos;
                
                // Cargar datos básicos
                $('#id_paciente_editar').val(datos.id);
                $('#nombre_editar').val(datos.nombre);
                $('#apellido_editar').val(datos.apellido);
                $('#tipo_documento_editar').val(datos.tipo_documento);
                $('#numero_documento_editar').val(datos.numero_documento);
                $('#usuario_editar').val(datos.usuario);
                $('#correo_editar').val(datos.correo);

                // Cargar Cobertura
                if (datos.id_obra_social) {
                    $('#id_obra_social_editar').val(datos.id_obra_social);
                    $('#numero_afiliado_editar').val(datos.numero_afiliado);
                    
                    // Llamar a la función para cargar los planes y seleccionar el correcto
                    cargarPlanes(datos.id_obra_social, $('#plan_editar'), datos.plan);
                } else {
                    // Resetear si no tiene
                    $('#id_obra_social_editar').val('');
                    $('#plan_editar').html('<option>Seleccione OS primero</option>').prop('disabled', true);
                    $('#numero_afiliado_editar').val('');
                }

            } else { Swal.fire('Error', 'Error al cargar datos.', 'error'); }
        });
    });

    // =========================================================================
    // ELIMINAR Y VERIFICAR (CÓDIGO EXISTENTE)
    // =========================================================================
    $('#tabla-pacientes tbody').on('click', '.btn-eliminar-paciente', function() {
        var pacienteId = $(this).data('id');
        var nombre = $(this).data('nombre');
        Swal.fire({
            title: '¿Eliminar a ' + nombre + '?', text: "No se podrá deshacer.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sí, borrar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= BASE_URL ?>ajax/pacientesA.php', { action: 'eliminar', id_paciente: pacienteId })
                .done(function(r) {
                    if(typeof r === 'string') r = JSON.parse(r);
                    if (r.success) { Swal.fire('Eliminado', '', 'success').then(() => { location.reload(); }); }
                    else { Swal.fire('Error', r.error, 'error'); }
                });
            }
        });
    });

    $('#usuario_crear').on('change', function() {
        var usuario = $(this).val();
        if (usuario !== '') {
            $.post('<?= BASE_URL ?>ajax/pacientesA.php', { action: 'verificarUsuario', usuario: usuario })
            .done(function(r) {
                if(typeof r === 'string') r = JSON.parse(r);
                if (r.existe) {
                    Swal.fire({ title: 'Usuario en uso', text: 'El usuario ya existe.', icon: 'warning' });
                    $(this).val('');
                }
            });
        }
    });
});
</script>
<?php
$scriptDinamico = ob_get_clean();
?>