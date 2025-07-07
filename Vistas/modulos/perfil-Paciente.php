<?php
// Incluir configuración al inicio
require_once $_SERVER['DOCUMENT_ROOT'] . '/clinica/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/clinica/Controladores/pacientesC.php';

// Verificar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación y rol
if (!isset($_SESSION['Ingresar'])) {
    header("Location: " . BASE_URL . "login");
    exit();
}

if ($_SESSION['rol'] != 'Paciente') {
    header("Location: " . BASE_URL . "inicio");
    exit();
}

// Manejo de mensajes de éxito/error
$alertType = '';
$alertMessage = '';

// Capturar errores del GET
if (isset($_GET['success'])) {
    $alertType = 'success';
    $alertMessage = 'Perfil actualizado correctamente';
} elseif (isset($_GET['error'])) {
    $alertType = 'danger';
    switch ($_GET['error']) {
        case 'actualizacion':
            $alertMessage = 'Error al actualizar el perfil';
            break;
        case 'formato_imagen':
            $alertMessage = 'Solo se permiten imágenes JPG, JPEG o PNG';
            break;
        case 'tamano_imagen':
            $alertMessage = 'La imagen no debe exceder los 2MB';
            break;
        default:
            $alertMessage = 'Ocurrió un error al procesar la solicitud';
    }
}

// Obtener datos del paciente
try {
    $pacienteC = new PacientesC();
    $paciente = $pacienteC->VerPerfilPacienteC();
} catch (Exception $e) {
    $alertType = 'danger';
    $alertMessage = 'Ocurrió un error al obtener los datos del perfil: ' . $e->getMessage();
    $paciente = null;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Perfil - Clínica</title>
    <base href="<?= BASE_URL ?>">

    <!-- CSS -->
    <link rel="stylesheet" href="Vistas/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="Vistas/dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="Vistas/dist/css/skins/skin-blue.min.css">
    <link rel="stylesheet" href="Vistas/bower_components/font-awesome/css/font-awesome.min.css">
    <style>
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #3c8dbc;
        }
        .info-box {
            min-height: 100px;
            margin-bottom: 20px;
        }
        .profile-buttons {
            margin-top: 15px;
        }
    </style>
</head>

<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">
        <!-- Cabecera -->
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/clinica/Vistas/modulos/cabecera.php'; ?>

        <!-- Menú lateral -->
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/clinica/Vistas/modulos/menuPaciente.php'; ?>

        <!-- Contenido principal -->
        <div class="content-wrapper">
            <section class="content-header">
                <h1>Mi Perfil</h1>
                <ol class="breadcrumb">
                    <li><a href="<?= BASE_URL ?>inicio"><i class="fa fa-home"></i> Inicio</a></li>
                    <li class="active">Perfil</li>
                </ol>
            </section>

            <section class="content">
                <?php if (!empty($alertMessage)): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-<?= $alertType ?> alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-<?= $alertType == 'success' ? 'check' : 'ban' ?>"></i> Alerta!</h4>
                            <?= $alertMessage ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($paciente): ?>
                <div class="row">
                    <div class="col-md-3">
                        <!-- Foto de perfil -->
                        <div class="box box-primary">
                            <div class="box-body box-profile">
                                <img class="profile-img" src="<?= !empty($paciente['foto']) ? $paciente['foto'] : 'Vistas/img/defecto.png' ?>" alt="Foto de perfil">
                                <h3 class="profile-username text-center"><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']) ?></h3>
                                <p class="text-muted text-center">Paciente</p>
                                
                                <div class="profile-buttons">
                                    <button class="btn btn-primary btn-block" data-toggle="modal" data-target="#modalEditarPerfil">
                                        <i class="fa fa-edit"></i> Editar Perfil
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-9">
                        <!-- Información del paciente -->
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Información Personal</h3>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-blue"><i class="fa fa-user"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Nombre Completo</span>
                                                <span class="info-box-number"><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-blue"><i class="fa fa-at"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Usuario</span>
                                                <span class="info-box-number"><?= htmlspecialchars($paciente['usuario']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-blue"><i class="fa fa-envelope"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Correo Electrónico</span>
                                                <span class="info-box-number"><?= !empty($paciente['correo']) ? htmlspecialchars($paciente['correo']) : 'No registrado' ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-blue"><i class="fa fa-phone"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Teléfono</span>
                                                <span class="info-box-number"><?= !empty($paciente['telefono']) ? htmlspecialchars($paciente['telefono']) : 'No registrado' ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-blue"><i class="fa fa-map-marker"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Dirección</span>
                                                <span class="info-box-number"><?= !empty($paciente['direccion']) ? htmlspecialchars($paciente['direccion']) : 'No registrada' ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-danger">
                            <strong>Error:</strong> No se pudieron cargar los datos del perfil.
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Modal para editar perfil -->
        <div class="modal fade" id="modalEditarPerfil" tabindex="-1" role="dialog" aria-labelledby="modalEditarPerfilLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>index.php?action=actualizarPerfil">

                <!-- Agregar este hidden input aquí 👇 -->
                <input type="hidden" name="actualizarPerfilPaciente" value="ok">

                <div class="modal-header bg-primary">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="modalEditarPerfilLabel">Editar Perfil</h4>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="idPaciente" value="<?= $paciente['id'] ?>">
                    <input type="hidden" name="fotoActual" value="<?= $paciente['foto'] ?>">
                    <input type="hidden" name="claveActual" value="<?= $paciente['clave'] ?>">

                    <div class="form-group">
                        <label for="nombreE">Nombre *</label>
                        <input type="text" class="form-control" id="nombreE" name="nombreE" value="<?= htmlspecialchars($paciente['nombre']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="apellidoE">Apellido *</label>
                        <input type="text" class="form-control" id="apellidoE" name="apellidoE" value="<?= htmlspecialchars($paciente['apellido']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="usuarioE">Usuario *</label>
                        <input type="text" class="form-control" id="usuarioE" name="usuarioE" value="<?= htmlspecialchars($paciente['usuario']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="claveE">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="claveE" name="claveE" placeholder="Dejar en blanco para no cambiar">
                        <p class="help-block">Mínimo 6 caracteres</p>
                    </div>

                    <div class="form-group">
                        <label for="correoE">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correoE" name="correoE" value="<?= htmlspecialchars($paciente['correo'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="telefonoE">Teléfono</label>
                        <input type="text" class="form-control" id="telefonoE" name="telefonoE" value="<?= htmlspecialchars($paciente['telefono'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="direccionE">Dirección</label>
                        <textarea class="form-control" id="direccionE" name="direccionE" rows="2"><?= htmlspecialchars($paciente['direccion'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="fotoE">Foto de Perfil</label>
                        <input type="file" class="form-control" id="fotoE" name="fotoE" accept="image/jpeg,image/png">
                        <p class="help-block">Formatos permitidos: JPG, PNG. Tamaño máximo: 2MB</p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>

            </form>
        </div>
    </div>
</div>


        <!-- Pie de página -->
        <footer class="main-footer">
            <div class="pull-right hidden-xs">
                <b>Versión</b> 1.0.0
            </div>
            <strong>Clínica &copy; <?= date('Y') ?>.</strong> Todos los derechos reservados.
        </footer>
    </div>

    <!-- JS -->
    <script src="Vistas/bower_components/jquery/dist/jquery.min.js"></script>
    <script src="Vistas/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="Vistas/dist/js/adminlte.min.js"></script>
    <script>
        $(document).ready(function() {
            // Mostrar vista previa de la imagen seleccionada
            $('#fotoE').change(function() {
                var input = this;
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('.profile-img').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            });

            // Validación básica del formulario
            $('form').submit(function(e) {
                var nombre = $('#nombreE').val().trim();
                var apellido = $('#apellidoE').val().trim();
                var usuario = $('#usuarioE').val().trim();
                var clave = $('#claveE').val();
                
                if (nombre === '' || apellido === '' || usuario === '') {
                    e.preventDefault();
                    alert('Los campos marcados con * son obligatorios');
                    return false;
                }
                
                if (clave !== '' && clave.length < 6) {
                    e.preventDefault();
                    alert('La contraseña debe tener al menos 6 caracteres');
                    return false;
                }
                
                // Mostrar indicador de carga
                $('.modal-footer button[type="submit"]').html('<i class="fa fa-spinner fa-spin"></i> Guardando...').prop('disabled', true);
            });
        });
    </script>
</body>
</html>
