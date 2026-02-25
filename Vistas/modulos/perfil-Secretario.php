<?php
// En Vistas/modulos/perfil-secretario.php (VERSIÓN FINAL Y COMPLETA)

// 1. Instanciar el controlador una sola vez.
$secretarioController = new SecretariosC();

// 2. PROCESAR EL FORMULARIO SI SE ENVIÓ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizarPerfilSecretario'])) {
    $secretarioController->ActualizarPerfilSecretarioC();
}

// 3. VERIFICAR ROL
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "Secretario") {
    exit("Acceso no autorizado a este módulo.");
}

// 4. CARGAR LOS DATOS PARA MOSTRAR
$secretario = $secretarioController->ObtenerPerfilSecretarioC();

if (!$secretario) {
    $_SESSION['mensaje_perfil'] = "Error crítico: No se pudieron cargar los datos del perfil.";
    $_SESSION['tipo_mensaje_perfil'] = "danger";
}
?>

<section class="content-header">
    <h1>Mi Perfil de Secretario/a</h1>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Mi Perfil</li></ol>
</section>

<section class="content">
    <?php
    if (isset($_SESSION['mensaje_perfil'])) {
        echo '<div class="alert alert-' . $_SESSION['tipo_mensaje_perfil'] . ' alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $_SESSION['mensaje_perfil'] . '</div>';
        unset($_SESSION['mensaje_perfil'], $_SESSION['tipo_mensaje_perfil']);
    }
    ?>

    <?php if ($secretario): ?>
    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-body box-profile">
                    <img class="profile-user-img img-responsive img-circle" 
                         src="<?= !empty($secretario['foto']) ? BASE_URL . htmlspecialchars($secretario['foto']) : BASE_URL . 'Vistas/img/user-default.png' ?>" 
                         alt="Foto de perfil">
                    <h3 class="profile-username text-center"><?= htmlspecialchars($secretario['nombre'] . ' ' . $secretario['apellido']) ?></h3>
                    <p class="text-muted text-center">Personal de Recepción</p>
                    <button class="btn btn-primary btn-block" data-toggle="modal" data-target="#modalEditarPerfilSecretario"><b>Editar Perfil</b></button>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">Información de la Cuenta</h3></div>
                <div class="box-body">
                    <strong><i class="fa fa-user margin-r-5"></i> Usuario</strong>
                    <p class="text-muted"><?= htmlspecialchars($secretario['usuario']) ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</section>

<!-- Modal para Editar Perfil -->
<?php if ($secretario): ?>
<div class="modal fade" id="modalEditarPerfilSecretario">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- ¡CORRECCIÓN CLAVE! El 'action' ahora es correcto. -->
            <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>perfil-secretario">
                <div class="modal-header bg-primary"><h4 class="modal-title">Editar Mi Perfil</h4></div>
                <div class="modal-body">
                    <input type="hidden" name="actualizarPerfilSecretario" value="ok">
                    <input type="hidden" name="idSecretario" value="<?= $secretario['id'] ?>">
                    <input type="hidden" name="claveActual" value="<?= $secretario['clave'] ?>">
                    <input type="hidden" name="fotoActual" value="<?= $secretario['foto'] ?>">
                    <div class="form-group"><label>Nombre *</label><input type="text" class="form-control" name="nombreE" value="<?= htmlspecialchars($secretario['nombre']) ?>" required></div>
                    <div class="form-group"><label>Apellido *</label><input type="text" class="form-control" name="apellidoE" value="<?= htmlspecialchars($secretario['apellido']) ?>" required></div>
                    <div class="form-group"><label>Usuario *</label><input type="text" class="form-control" name="usuarioE" value="<?= htmlspecialchars($secretario['usuario']) ?>" required></div>
                    <div class="form-group"><label>Nueva Contraseña</label><input type="password" class="form-control" name="claveE" placeholder="Dejar en blanco para no cambiar"></div>
                    <div class="form-group"><label>Foto de Perfil</label><input type="file" name="fotoE" class="form-control" accept="image/jpeg,image/png"></div>
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