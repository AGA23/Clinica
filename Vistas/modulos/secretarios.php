<?php
// --- SEGURIDAD Y PROCESAMIENTO ---
if ($_SESSION["rol"] !== "Administrador") {
    echo '<script>window.location = "' . BASE_URL . 'inicio";</script>';
    exit();
}

// Procesar acciones POST (Crear y Editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crearSecretario'])) { SecretariosC::CrearSecretarioC(); }
    if (isset($_POST['editarSecretario'])) { SecretariosC::ActualizarSecretarioC(); }
}

// Procesar acción GET (Borrar)
if (isset($_GET["borrar_id"])) { SecretariosC::BorrarSecretarioC(); }

// --- CARGA DE DATOS PARA LA VISTA ---
$secretarios = SecretariosC::VerSecretariosC();
require_once "Controladores/ConsultoriosC.php";
$consultorios = ConsultoriosC::ObtenerListaConsultoriosC();
?>

<!-- Encabezado y Mensajes -->
<section class="content-header">
    <h1>Gestor de Personal de Recepción</h1>
</section>
<section class="content">
    <?php
    if (isset($_SESSION['mensaje_secretarios'])) {
        echo '<div class="alert alert-' . $_SESSION['tipo_mensaje_secretarios'] . ' alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $_SESSION['mensaje_secretarios'] . '</div>';
        unset($_SESSION['mensaje_secretarios'], $_SESSION['tipo_mensaje_secretarios']);
    }
    ?>

   <!-- Tabla Principal -->
<div class="box">
    <div class="box-header">
        <button class="btn btn-primary btn-lg" data-toggle="modal" data-target="#modalCrearSecretario">Crear Nuevo Perfil</button>
    </div>
    <div class="box-body">
        <table class="table table-bordered table-hover table-striped dt-responsive" id="tablaSecretarios">
            <thead>
                <tr>
                    <th style="width: 10px;">#</th>
                    <th>Apellido</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Consultorio Asignado</th>
                    <!-- Ajustamos el ancho para que quepan los botones con texto -->
                    <th style="width: 180px;">Acciones</th> 
                </tr>
            </thead>
            <tbody>
                <?php foreach ($secretarios as $key => $value): ?>
                    <tr>
                        <td><?= ($key + 1) ?></td>
                        <td><?= htmlspecialchars($value["apellido"]) ?></td>
                        <td><?= htmlspecialchars($value["nombre"]) ?></td>
                        <td><?= htmlspecialchars($value["usuario"]) ?></td>
                        <td>
                            <?= !empty($value["nombre_consultorio"]) 
                                ? '<span class="label label-success">' . htmlspecialchars($value["nombre_consultorio"]) . '</span>' 
                                : '<span class="label label-default">Sin Asignar</span>'; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <!-- BOTÓN DE EDITAR CON TEXTO E ÍCONO -->
                                <button class="btn btn-warning btn-sm btnEditarSecretario" 
                                        data-id="<?= $value["id"] ?>" 
                                        data-toggle="modal" 
                                        data-target="#modalEditarSecretario">
                                    <i class="fa fa-pencil"></i> Editar
                                </button>
                                
                                <!-- BOTÓN DE ELIMINAR CON TEXTO E ÍCONO -->
                                <!-- El JavaScript que ya tienes se encargará de la confirmación -->
                                <button class="btn btn-danger btn-sm btnEliminarSecretario" 
                                        data-id="<?= $value["id"] ?>">
                                    <i class="fa fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear (Tu código actual, está bien) -->
<div class="modal fade" id="modalCrearSecretario">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post">
            <div class="modal-header bg-primary"><h4 class="modal-title">Crear Perfil de Recepción</h4></div>
            <div class="modal-body">
                <input type="hidden" name="crearSecretario" value="ok">
                <div class="form-group"><label>Apellido:</label><input type="text" class="form-control" name="apellido" required></div>
                <div class="form-group"><label>Nombre:</label><input type="text" class="form-control" name="nombre" required></div>
                <div class="form-group"><label>Usuario:</label><input type="text" class="form-control" name="usuario" required></div>
                <div class="form-group"><label>Contraseña:</label><input type="password" class="form-control" name="clave" required></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Crear Perfil</button></div>
        </form>
    </div></div>
</div>

<!-- ¡NUEVO MODAL DE EDICIÓN! -->
<div class="modal fade" id="modalEditarSecretario">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post">
            <div class="modal-header bg-warning"><h4 class="modal-title">Editar Perfil de Recepción</h4></div>
            <div class="modal-body">
                <input type="hidden" name="editarSecretario" value="ok">
                <input type="hidden" name="idSecretario" id="idSecretarioE">
                <div class="form-group"><label>Apellido:</label><input type="text" class="form-control" name="apellidoE" id="apellidoE" required></div>
                <div class="form-group"><label>Nombre:</label><input type="text" class="form-control" name="nombreE" id="nombreE" required></div>
                <div class="form-group"><label>Usuario:</label><input type="text" class="form-control" name="usuarioE" id="usuarioE" required></div>
                <div class="form-group"><label>Nueva Contraseña:</label><input type="password" class="form-control" name="claveE" placeholder="Dejar en blanco para no cambiar"></div>
                <div class="form-group">
                    <label>Asignar a Consultorio:</label>
                    <select class="form-control" name="consultorioE" id="consultorioE">
                        <option value="">Sin Asignar</option>
                        <?php foreach ($consultorios as $consultorio): ?>
                            <option value="<?= $consultorio["id"] ?>"><?= htmlspecialchars($consultorio["nombre"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning">Guardar Cambios</button></div>
        </form>
    </div></div>
</div>

<!-- JavaScript Específico -->
<script>
$(document).ready(function() {
    // Inicialización de la tabla con DataTables en español.
    $('#tablaSecretarios').DataTable({
        "responsive": true,
        "autoWidth": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json"
        }
    });

    // --- MANEJADOR PARA EL BOTÓN DE ELIMINAR ---
    $('#tablaSecretarios').on('click', '.btnEliminarSecretario', function() {
        // Obtenemos los datos desde los atributos data-* del botón.
        var idSecretario = $(this).data('id');
        var nombreSecretario = $(this).data('nombre'); // Obtenemos el nombre.

        // Usamos SweetAlert2 para una confirmación más profesional.
        Swal.fire({
            title: '¿Está seguro?',
            // Mensaje de confirmación personalizado.
            html: "¡El perfil de <strong>" + nombreSecretario + "</strong> será eliminado permanentemente!", 
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, ¡eliminar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            // Si el usuario confirma la acción...
            if (result.isConfirmed) {
                // Redirigimos a la URL de borrado.
                window.location.href = 'secretarios?borrar_id=' + idSecretario;
            }
        });
    });

    // --- MANEJADOR PARA EL BOTÓN DE EDITAR ---
    $('#tablaSecretarios').on('click', '.btnEditarSecretario', function() {
        // Obtenemos el ID del secretario a editar.
        var idSecretario = $(this).data('id');
        
        // Petición AJAX para obtener los datos y llenar el modal.
        $.ajax({
            url: 'Ajax/secretariosA.php',
            method: 'POST',
            data: { 
                'accion': 'verSecretario', 
                'id': idSecretario 
            },
            dataType: 'json',
            success: function(respuesta) {
                // Si la petición AJAX fue exitosa y trajo datos...
                if (respuesta.success) {
                    // Llenamos cada campo del modal de edición.
                    $('#idSecretarioE').val(respuesta.datos.id);
                    $('#apellidoE').val(respuesta.datos.apellido);
                    $('#nombreE').val(respuesta.datos.nombre);
                    $('#usuarioE').val(respuesta.datos.usuario);
                    // Seleccionamos la opción correcta en el menú desplegable de consultorios.
                    $('#consultorioE').val(respuesta.datos.id_consultorio);
                } else {
                    // Si hubo un error del lado del servidor (ej. secretario no encontrado).
                    Swal.fire('Error', respuesta.error || 'No se pudieron cargar los datos.', 'error');
                }
            },
            error: function() {
                // Si la petición AJAX falla por un problema de red o del servidor.
                Swal.fire('Error de Comunicación', 'No se pudo contactar con el servidor.', 'error');
            }
        });
    });
});
</script>