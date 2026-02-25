<?php
// En Vistas/modulos/clinica.php

// Seguridad: Solo el Administrador puede acceder.
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "Administrador") {
    echo '<script>window.location = "inicio";</script>';
    return;
}

$clinicaController = new ClinicaC();

// Procesar el formulario si se envió.
$clinicaController->ActualizarClinicaC();

// Cargar los datos actuales de la clínica para mostrarlos.
$clinica = $clinicaController->VerClinicaC();

// Aseguramos un array vacío si la carga falla para evitar errores
if (!$clinica) {
    $clinica = [];
}
?>
<section class="content-header">
    <h1>Configuración de la Clínica</h1>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Clínica</li></ol>
</section>

<section class="content">
    <?php
    if (isset($_SESSION['mensaje_clinica'])) {
        echo '<div class="alert alert-' . $_SESSION['tipo_mensaje_clinica'] . ' alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $_SESSION['mensaje_clinica'] . '</div>';
        unset($_SESSION['mensaje_clinica'], $_SESSION['tipo_mensaje_clinica']);
    }
    ?>
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Datos Generales de la Empresa</h3>
        </div>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="actualizar_clinica" value="ok">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nombre de la Clínica (para mostrar):</label>
                            <input type="text" name="nombre_clinica" class="form-control" value="<?= htmlspecialchars($clinica['nombre_clinica'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>CUIT:</label>
                            <input type="text" name="cuit_clinica" class="form-control" value="<?= htmlspecialchars($clinica['cuit_clinica'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Email General de Contacto:</label>
                            <input type="email" name="correo_clinica" class="form-control" value="<?= htmlspecialchars($clinica['correo'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                     <div class="col-md-6">
                        <div class="form-group">
                            <label>Logo Principal (para documentos, etc.):</label>
                            <input type="file" name="logoNuevo" class="form-control" accept="image/png,image/jpeg">
                            <input type="hidden" name="logoActual" value="<?= htmlspecialchars($clinica['logo_clinica'] ?? '') ?>">
                            <p class="help-block">Subir un nuevo archivo reemplazará el logo actual.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Logo Actual:</strong></p>
                        <?php if(!empty($clinica['logo_clinica'])): ?>
                            <img src="<?= BASE_URL . htmlspecialchars($clinica['logo_clinica']) ?>" class="img-responsive" style="max-height: 50px; border: 1px solid #ddd; padding: 5px; background-color: #f9f9f9;">
                        <?php else: ?>
                            <p class="text-muted">No hay logo subido.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="box-footer text-right">
                <button type="submit" class="btn btn-primary btn-flat">Guardar Cambios</button>
            </div>
        </form>
    </div>
</section>