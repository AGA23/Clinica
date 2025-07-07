<?php
// Incluir el archivo secretariasC.php
require_once __DIR__ . "/../../Controladores/secretariasC.php";



// Verificar si el usuario es una secretaria
if ($_SESSION["rol"] != "Secretaria") {
    echo '<script>window.location = "inicio";</script>';
    exit();
}

// Verificar si el ID de la secretaria está definido
if (!isset($_SESSION['id'])) {
    echo '<div class="alert alert-danger">Error: No se encontró el id de la secretaria.</div>';
    exit();
}

$idSecretaria = $_SESSION['id']; // Obtener el ID de la secretaria desde la sesión

// Procesar la actualización del perfil si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    SecretariasC::ActualizarPerfilSecretariaC();
    exit(); // Detener la ejecución después de procesar la actualización
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Perfil de Secretaria</title>
    <!-- Estilos -->
    <link rel="stylesheet" href="http://localhost/clinica/Vistas/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="http://localhost/clinica/Vistas/dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="http://localhost/clinica/Vistas/dist/css/skins/_all-skins.min.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">
        <!-- Cabecera -->
        <?php include __DIR__ . "/cabecera.php"; ?>

        <!-- Contenido principal -->
        <div class="content-wrapper">
            <section class="content-header">
                <h1>Gestor de Perfil</h1>
            </section>
            <section class="content">
                <div class="box">
                    <div class="box-body">
                        <table class="table table-bordered table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Contraseña</th>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Foto</th>
                                    <th>Editar</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $perfil = SecretariasC::VerPerfilSecretariaC($idSecretaria);

                                if ($perfil) {
                                    echo '<tr>';
                                    echo '<td>' . $perfil['usuario'] . '</td>';
                                    echo '<td>' . $perfil['clave'] . '</td>';
                                    echo '<td>' . $perfil['nombre'] . '</td>';
                                    echo '<td>' . $perfil['apellido'] . '</td>';
                                    echo '<td>';
                                    // Verificar si la foto está definida y no está vacía
                                    if (!empty($perfil['foto'])) {
                                        echo '<img src="http://localhost/clinica/' . $perfil['foto'] . '" alt="Foto" width="50">';
                                    } else {
                                        // Mostrar la foto predeterminada si no hay una foto personalizada
                                        echo '<img src="http://localhost/clinica/Vistas/img/defecto.png" alt="Foto" width="50">';
                                    }
                                    echo '</td>';
                                    echo '<td><button id="btnEditar" class="btn btn-warning">Editar</button></td>';
                                    echo '</tr>';
                                } else {
                                    echo '<tr><td colspan="6">No se encontró el perfil de la secretaria.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>

                        <!-- Formulario de edición -->
                        <form id="formEditar" method="POST" style="display: none;" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $idSecretaria; ?>">
                            <div class="form-group">
                                <label for="usuario">Usuario</label>
                                <input type="text" name="usuario" class="form-control" value="<?php echo $perfil['usuario']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="clave">Contraseña</label>
                                <input type="password" name="clave" class="form-control" value="<?php echo $perfil['clave']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="nombre">Nombre</label>
                                <input type="text" name="nombre" class="form-control" value="<?php echo $perfil['nombre']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="apellido">Apellido</label>
                                <input type="text" name="apellido" class="form-control" value="<?php echo $perfil['apellido']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="foto">Foto</label>
                                <input type="file" name="foto" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-success">Guardar</button>
                        </form>
                    </div>
                </div>
            </section>
        </div>

        <!-- Pie de página -->
        <footer class="main-footer">
            <div class="pull-right hidden-xs">
                <b>Versión</b> 1.0
            </div>
            <strong>Clínica Médica &copy; <?php echo date("Y"); ?>.</strong> Todos los derechos reservados.
        </footer>
    </div>

    <!-- Scripts -->
    <script src="http://localhost/clinica/Vistas/bower_components/jquery/dist/jquery.min.js"></script>
    <script src="http://localhost/clinica/Vistas/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="http://localhost/clinica/Vistas/dist/js/adminlte.min.js"></script>
    <script>
        // Mostrar el formulario de edición al hacer clic en el botón de Editar
        document.getElementById("btnEditar").addEventListener("click", function () {
            document.getElementById("formEditar").style.display = "block";
        });

        // Enviar el formulario mediante AJAX
        $(document).ready(function() {
            $('#formEditar').on('submit', function(e) {
                e.preventDefault(); // Evitar que el formulario se envíe de la manera tradicional

                var formData = new FormData(this); // Crear un objeto FormData con los datos del formulario

                $.ajax({
                    url: '', // Enviar los datos al mismo archivo
                    type: 'POST',
                    data: formData,
                    processData: false, // No procesar los datos
                    contentType: false, // No establecer el tipo de contenido
                    success: function(response) {
                        alert('Perfil actualizado correctamente');
                        location.reload(); // Recargar la página para reflejar los cambios
                    },
                    error: function() {
                        alert('Error al actualizar el perfil');
                    }
                });
            });
        });
    </script>
</body>
</html>