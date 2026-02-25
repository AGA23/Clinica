<?php


// --- LÓGICA DE LA PÁGINA DE PERFIL ---

// 1. PROCESAR LA ACTUALIZACIÓN SI SE ENVIÓ EL FORMULARIO
// El controlador se encargará de validar, guardar y redirigir con un mensaje.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizarPerfilPaciente'])) {
    // El autoloader encontrará la clase 'PacientesC' automáticamente.
    $pacienteController = new PacientesC();
    $pacienteController->ActualizarPerfilPacienteC();
}

// 2. VERIFICAR ROL PARA ESTE MÓDULO ESPECÍFICO
// La seguridad global ya fue verificada por loader.php, esta es una capa extra.
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "Paciente") {
    exit("Acceso no autorizado a este módulo.");
}

// 3. CARGAR LOS DATOS PARA MOSTRAR EN LA VISTA
$paciente = null; // Se inicializa para evitar errores de variable no definida.
try {
    // El autoloader también se encarga de 'PacientesC' aquí.
    $pacienteC = new PacientesC();
    $paciente = $pacienteC->VerPerfilPacienteC(); // Intenta obtener los datos del paciente.

    // Si el método no devuelve un paciente válido, se considera un error.
    if (!$paciente) {
        throw new Exception("El perfil del paciente no fue encontrado en la base de datos.");
    }

} catch (Exception $e) {
    // Si ocurre cualquier error, se registra y se prepara un mensaje para el usuario.
    error_log("Error al cargar perfil de paciente: " . $e->getMessage());
    $_SESSION['mensaje_perfil'] = "No se pudieron cargar los datos del perfil. Por favor, intente de nuevo.";
    $_SESSION['tipo_mensaje_perfil'] = "danger";
    // $paciente se mantiene como null.
}
?>

<!-- El código HTML del módulo empieza aquí. Es un fragmento que se insertará en la plantilla. -->
<section class="content-header">
    <h1>Mi Perfil</h1>
</section>

<section class="content">
    
    <!-- Muestra mensajes de éxito/error guardados en la sesión (ej: después de actualizar) -->
    <?php
    if (isset($_SESSION['mensaje_perfil'])) {
        echo '<div class="alert alert-' . $_SESSION['tipo_mensaje_perfil'] . ' alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $_SESSION['mensaje_perfil'] . '</div>';
        // Se limpia el mensaje para que no se muestre de nuevo si se recarga la página.
        unset($_SESSION['mensaje_perfil'], $_SESSION['tipo_mensaje_perfil']);
    }
    ?>

    <!-- Si la carga de datos del paciente fue exitosa, se muestra el perfil -->
    <?php if ($paciente): ?>
    <div class="row">
        <div class="col-md-4">
            <!-- Widget de Perfil -->
            <div class="box box-primary">
                <div class="box-body box-profile">
                    <img class="profile-user-img img-responsive img-circle" src="<?= !empty($paciente['foto']) ? BASE_URL . htmlspecialchars($paciente['foto']) : BASE_URL . 'Vistas/img/user-default.png' ?>" alt="Foto de perfil">
                    <h3 class="profile-username text-center"><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']) ?></h3>
                    <p class="text-muted text-center">Paciente</p>
                    <button class="btn btn-primary btn-block" data-toggle="modal" data-target="#modalEditarPerfil"><b>Editar Perfil</b></button>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <!-- Información Personal -->
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">Mi Información</h3></div>
                <div class="box-body">
                    <strong><i class="fa fa-user margin-r-5"></i> Usuario</strong>
                    <p class="text-muted"><?= htmlspecialchars($paciente['usuario']) ?></p><hr>

                    <!-- === [MODIFICADO] Se añade el documento de identidad === -->
                    <strong><i class="fa fa-id-card-o margin-r-5"></i> Documento de Identidad</strong>
                    <p class="text-muted">
                        <?= htmlspecialchars(($paciente['tipo_documento'] ?? '') . ' ' . ($paciente['numero_documento'] ?? 'No registrado')) ?>
                        <br>
                        <small>Para modificar este dato, por favor contacte a la administracion .</small>
                    </p><hr>

                    <strong><i class="fa fa-envelope margin-r-5"></i> Correo Electrónico</strong>
                    <p class="text-muted"><?= !empty($paciente['correo']) ? htmlspecialchars($paciente['correo']) : 'No registrado' ?></p><hr>
                    <strong><i class="fa fa-phone margin-r-5"></i> Teléfono</strong>
                    <p class="text-muted"><?= !empty($paciente['telefono']) ? htmlspecialchars($paciente['telefono']) : 'No registrado' ?></p><hr>
                    <strong><i class="fa fa-map-marker margin-r-5"></i> Dirección</strong>
                    <p class="text-muted"><?= !empty($paciente['direccion']) ? htmlspecialchars($paciente['direccion']) : 'No registrada' ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <!-- Si la variable $paciente es null, se muestra un mensaje de error claro -->
        <div class="alert alert-danger"><strong>Error:</strong> No se pudieron cargar los datos del perfil en este momento.</div>
    <?php endif; ?>
</section>

<!-- Modal para Editar Perfil del Paciente (solo se renderiza si hay datos de paciente) -->
<?php if ($paciente): ?>
<div class="modal fade" id="modalEditarPerfil">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>index.php?url=perfil-Paciente">
                <div class="modal-header bg-primary"><h4 class="modal-title">Editar Mi Perfil</h4><button type="button" class="close" data-dismiss="modal">×</button></div>
                <div class="modal-body">
                    <input type="hidden" name="actualizarPerfilPaciente" value="ok">
                    <input type="hidden" name="idPaciente" value="<?= $paciente['id'] ?>">
                    <input type="hidden" name="fotoActual" value="<?= $paciente['foto'] ?>">
                    <input type="hidden" name="claveActual" value="<?= $paciente['clave'] ?>">

                    <div class="form-group"><label>Nombre *</label><input type="text" class="form-control" name="nombreE" value="<?= htmlspecialchars($paciente['nombre']) ?>" required></div>
                    <div class="form-group"><label>Apellido *</label><input type="text" class="form-control" name="apellidoE" value="<?= htmlspecialchars($paciente['apellido']) ?>" required></div>
                    <div class="form-group"><label>Usuario *</label><input type="text" class="form-control" name="usuarioE" value="<?= htmlspecialchars($paciente['usuario']) ?>" required></div>
                    <div class="form-group"><label>Nueva Contraseña</label><input type="password" class="form-control" name="claveE" placeholder="Dejar en blanco para no cambiar"><p class="help-block">Mínimo 6 caracteres</p></div>
                    <div class="form-group"><label>Correo</label><input type="email" class="form-control" name="correoE" value="<?= htmlspecialchars($paciente['correo'] ?? '') ?>"></div>
                    <div class="form-group"><label>Teléfono</label><input type="text" class="form-control" name="telefonoE" value="<?= htmlspecialchars($paciente['telefono'] ?? '') ?>"></div>
                    <div class="form-group"><label>Dirección</label><textarea class="form-control" name="direccionE" rows="2"><?= htmlspecialchars($paciente['direccion'] ?? '') ?></textarea></div>
                    <div class="form-group"><label>Foto</label><input type="file" name="fotoE" class="form-control" accept="image/jpeg,image/png"><p class="help-block">Formatos JPG, PNG. Máx 2MB</p></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>