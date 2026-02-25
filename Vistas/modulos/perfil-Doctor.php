<?php
// En Vistas/modulos/perfil-Doctor.php

// El loader.php ya ha iniciado la sesión y cargado las clases necesarias.

// --- LÓGICA DE ACTUALIZACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idDoctor'])) {
    // Para llamar al método, primero creamos una instancia
    $actualizarPerfil = new DoctoresC();
    $actualizarPerfil->ActualizarPerfilDoctorC();
}

// --- 2. VERIFICACIÓN DE ROL PARA ESTE MÓDULO ---
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "Doctor") {
    exit("Acceso no autorizado a este módulo.");
}

// --- CARGA DE DATOS PARA LA VISTA (VERSIÓN CORREGIDA) ---
$doctor = null;
try {
    // 1. Creamos una instancia (un objeto) del controlador.
    $doctorC = new DoctoresC();
    
    // 2. Llamamos al método de instancia usando el operador de flecha '->'.
    $doctor = $doctorC->ObtenerPerfilDoctorC();

    if (!$doctor) {
        throw new Exception("El perfil no fue encontrado.");
    }
} catch (Exception $e) {
    error_log("Error al cargar perfil de doctor: " . $e->getMessage());
    $_SESSION['mensaje_perfil'] = "Error al cargar los datos del perfil.";
    $_SESSION['tipo_mensaje_perfil'] = "danger";
}

?>

<!-- Encabezado de la página -->
<section class="content-header">
    <h1>Mi Perfil Profesional</h1>
</section>

<section class="content">
    
    <!-- Bloque para mostrar mensajes de sesión (éxito/error tras una actualización) -->
    <?php
    if (isset($_SESSION['mensaje_perfil'])) {
        echo '<div class="alert alert-' . $_SESSION['tipo_mensaje_perfil'] . ' alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $_SESSION['mensaje_perfil'] . '</div>';
        unset($_SESSION['mensaje_perfil'], $_SESSION['tipo_mensaje_perfil']);
    }
    ?>

    <?php if ($doctor): ?>
    <div class="row">
        <div class="col-md-4">
            <!-- Widget de Perfil -->
            <div class="box box-primary">
                <div class="box-body box-profile">
                    <img class="profile-user-img img-responsive img-circle" src="<?= !empty($doctor['foto']) ? BASE_URL . htmlspecialchars($doctor['foto']) : BASE_URL . 'Vistas/img/user-default.png' ?>" alt="Foto de perfil">
                    <h3 class="profile-username text-center"><?= htmlspecialchars($doctor['nombre'] . ' ' . $doctor['apellido']) ?></h3>
                    <p class="text-muted text-center"><?= htmlspecialchars($doctor['especialidad'] ?? 'Sin especialidad') ?></p>
                    <ul class="list-group list-group-unbordered">
                        <li class="list-group-item"><b>Usuario</b> <span class="pull-right"><?= htmlspecialchars($doctor['usuario']) ?></span></li>
                        <li class="list-group-item"><b>Email</b><span class="pull-right"><?= htmlspecialchars($doctor['email'] ?? 'No especificado') ?></span></li>
                        <li class="list-group-item"><b>Consultorio</b> <span class="pull-right"><?= htmlspecialchars($doctor['nombre_consultorio'] ?? 'No asignado') ?></span></li>
                        <li class="list-group-item"><b>Matrícula Nacional</b><span class="pull-right"><?= htmlspecialchars($doctor['matricula_nacional'] ?? 'No especificada') ?></span></li>
                        <li class="list-group-item"><b>Matrícula Provincial</b> <span class="pull-right"><?= htmlspecialchars($doctor['matricula_provincial'] ?? 'No especificada') ?></span> </li>
                    </ul>
                    <button class="btn btn-primary btn-block" data-toggle="modal" data-target="#modalEditarPerfilDoctor"><b>Editar Perfil</b></button>
                </div>
            </div>

            <!-- [NUEVO] Widget para mostrar la Firma Digital -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Firma para Documentos</h3>
                </div>
                <div class="box-body text-center">
                    <?php if (!empty($doctor['firma_digital'])): ?>
                        <img src="<?= BASE_URL . htmlspecialchars($doctor['firma_digital']) ?>" class="img-responsive" alt="Firma Digital" style="max-height: 120px; margin: auto; border: 1px solid #eee; padding: 5px;">
                    <?php else: ?>
                        <p class="text-muted">Aún no ha subido una firma digital.</p>
                        <p class="text-muted"><small>Puede agregarla desde "Editar Perfil".</small></p>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        <div class="col-md-8">
            <!-- Widget de Horarios -->
            <div class="box">
                <!-- ... aquí va tu tabla de horarios si la tienes ... -->
                <div class="box-header with-border">
                    <h3 class="box-title">Mis Horarios de Atención</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">Información sobre los horarios de atención del doctor.</p>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-danger"><strong>Error:</strong> No se pudieron cargar los datos del perfil.</div>
    <?php endif; ?>
</section>

<!-- Modal para Editar Perfil del Doctor -->
<?php if ($doctor): ?>
<div class="modal fade" id="modalEditarPerfilDoctor">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header bg-primary"><h4 class="modal-title">Editar Mi Perfil</h4><button type="button" class="close" data-dismiss="modal">×</button></div>
                <div class="modal-body">
                   <!-- Campos ocultos necesarios para la actualización -->
<input type="hidden" name="actualizarPerfilDoctor" value="ok">
<input type="hidden" name="idDoctor" value="<?= $doctor['id'] ?>">
<input type="hidden" name="passwordActual" value="<?= $doctor['clave'] ?>">

<!-- [CORREGIDO] Se usa el operador de coalescencia nula (??) para manejar de forma segura los valores nulos -->
<input type="hidden" name="fotoActual" value="<?= htmlspecialchars($doctor['foto'] ?? '') ?>">
<input type="hidden" name="firmaActual" value="<?= htmlspecialchars($doctor['firma_digital'] ?? '') ?>">
                    
<div class="form-group">
    <label>Nombre *</label>
    <input type="text" class="form-control" name="nombreE" value="<?= htmlspecialchars($doctor['nombre']) ?>" required>
</div>
                    <div class="form-group">
                        <label>Apellido *</label>
                        <input type="text" class="form-control" name="apellidoE" value="<?= htmlspecialchars($doctor['apellido']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" class="form-control" name="emailE" value="<?= htmlspecialchars($doctor['email']) ?>" required>
                    </div>

                    <hr>
                    <div class="form-group">
                        <label>Nueva Contraseña:</label>
                        <input type="password" class="form-control" name="clave" placeholder="Dejar en blanco para no cambiar">
                    </div>
                    <hr>
                    <div class="form-group">
                        <label>Foto de Perfil:</label>
                        <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png">
                         <p class="help-block">Subir una nueva foto reemplazará la actual.</p>
                    </div>

                    <!-- [NUEVO] Campo para subir la Firma Digital -->
                    <div class="form-group">
                        <label>Firma Digital (para Recetas/Certificados):</label>
                        <input type="file" name="firma_digital" class="form-control" accept="image/png">
                        <p class="help-block">
                            Recomendado: Imagen PNG con fondo transparente. Subir una nueva firma reemplazará la actual.
                        </p>
                    </div>
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