<?php
// En Vistas/modulos/perfil-Administrador.php

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "Administrador") {
    echo '<script>window.location = "inicio";</script>';
    exit();
}

require_once __DIR__ . "/../../Controladores/adminC.php"; // Asegúrate que el nombre del controlador es correcto

// --- LÓGICA DE ACTUALIZACIÓN (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizarPerfilAdmin'])) {
    $adminController = new AdminC();
    $adminController->ActualizarPerfilAdminC();
}

// --- CARGA DE DATOS PARA LA VISTA (GET) ---
try {
    $adminC = new AdminC();
    $admin = $adminC->ObtenerPerfilAdminC(); // Necesitarás un método que devuelva los datos del admin
} catch (Exception $e) {
    $_SESSION['mensaje_perfil'] = "Error al cargar los datos del perfil.";
    $_SESSION['tipo_mensaje_perfil'] = "danger";
    $admin = null;
}
?>

<section class="content-header">
    <h1>Mi Perfil de Administrador</h1>
</section>

<section class="content">
    
    <!-- Bloque para mostrar mensajes de éxito o error -->
    <?php
    if (isset($_SESSION['mensaje_perfil'])) {
        echo '<div class="alert alert-' . $_SESSION['tipo_mensaje_perfil'] . ' alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                ' . $_SESSION['mensaje_perfil'] . '
              </div>';
        unset($_SESSION['mensaje_perfil'], $_SESSION['tipo_mensaje_perfil']);
    }
    ?>

    <?php if ($admin): ?>
    <div class="row">
        <div class="col-md-4">
            <!-- Widget de Perfil -->
            <div class="box box-primary">
                <div class="box-body box-profile">
                    <img class="profile-user-img img-responsive img-circle" 
                         src="<?= !empty($admin['foto']) ? $admin['foto'] : 'Vistas/img/user-default.png' ?>" 
                         alt="Foto de perfil">
                    <h3 class="profile-username text-center"><?= htmlspecialchars($admin['nombre'] . ' ' . $admin['apellido']) ?></h3>
                    <p class="text-muted text-center">Administrador del Sistema</p>
                    <button class="btn btn-primary btn-block" data-toggle="modal" data-target="#modalEditarPerfilAdmin"><b>Editar Perfil</b></button>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <!-- Widget de Información -->
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">Información de la Cuenta</h3></div>
                <div class="box-body">
                    <strong><i class="fa fa-user margin-r-5"></i> Usuario de Acceso</strong>
                    <p class="text-muted"><?= htmlspecialchars($admin['usuario']) ?></p>
                    <hr>
                     <strong><i class="fa fa-shield margin-r-5"></i> Rol</strong>
                    <p class="text-muted"><?= htmlspecialchars($admin['rol']) ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-danger"><strong>Error:</strong> No se pudieron cargar los datos del perfil.</div>
    <?php endif; ?>
</section>

<!-- Modal para Editar Perfil -->
<?php if ($admin): ?>
<div class="modal fade" id="modalEditarPerfilAdmin">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header bg-primary"><h4 class="modal-title">Editar Mi Perfil</h4></div>
                <div class="modal-body">
                    <input type="hidden" name="actualizarPerfilAdmin" value="ok">
                    <input type="hidden" name="idAdmin" value="<?= $admin['id'] ?>">
                    <input type="hidden" name="claveActual" value="<?= $admin['clave'] ?>">
                    <input type="hidden" name="fotoActual" value="<?= $admin['foto'] ?>">

                    <div class="form-group"><label>Nombre *</label><input type="text" class="form-control" name="nombreE" value="<?= htmlspecialchars($admin['nombre']) ?>" required></div>
                    <div class="form-group"><label>Apellido *</label><input type="text" class="form-control" name="apellidoE" value="<?= htmlspecialchars($admin['apellido']) ?>" required></div>
                    <div class="form-group"><label>Usuario *</label><input type="text" class="form-control" name="usuarioE" value="<?= htmlspecialchars($admin['usuario']) ?>" required></div>
                    <div class="form-group"><label>Nueva Contraseña</label><input type="password" class="form-control" name="claveE" placeholder="Dejar en blanco para no cambiar"></div>
                    <div class="form-group"><label>Foto de Perfil</label><input type="file" name="fotoE" accept="image/jpeg,image/png"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>