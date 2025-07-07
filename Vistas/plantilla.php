<?php
require_once __DIR__ . '/../config.php';


// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si el usuario ya está logueado, redirigir al index (y desde ahí al dashboard correspondiente)
if (isset($_SESSION["Ingresar"])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Clínica - Login</title>
    <base href="<?= BASE_URL ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Estilos -->
    <link rel="stylesheet" href="Vistas/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="Vistas/dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="Vistas/bower_components/font-awesome/css/font-awesome.min.css">
</head>
<body class="hold-transition login-page">

    <div class="login-box">
        <div class="login-logo">
            <a href="#"><b>Clínica</b>Médica</a>
        </div>

        <div class="login-box-body">
            <p class="login-box-msg">Inicia sesión para continuar</p>

            <!-- Formulario de login -->
            <form action="<?= BASE_URL ?>Controladores/loginC.php" method="post">
                <div class="form-group has-feedback">
                    <input type="text" name="usuario-Ing" class="form-control" placeholder="Usuario" required>
                    <span class="glyphicon glyphicon-user form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <input type="password" name="clave-Ing" class="form-control" placeholder="Contraseña" required>
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <button type="submit" class="btn btn-primary btn-block btn-flat">Iniciar Sesión</button>
                    </div>
                </div>
            </form>

            <!-- Mensajes de error -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger mt-3" style="margin-top: 10px;">
                    <?php
                    $errors = [
                        1 => 'Usuario o contraseña incorrectos',
                        2 => 'Debe ingresar sus credenciales',
                        3 => 'Sesión expirada o acceso no autorizado'
                    ];
                    echo $errors[$_GET['error']] ?? 'Error desconocido';
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="Vistas/bower_components/jquery/dist/jquery.min.js"></script>
    <script src="Vistas/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
</body>
</html>
