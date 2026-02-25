<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Clínica - Iniciar Sesión</title>
    <base href="<?= BASE_URL ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="Vistas/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="Vistas/dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="Vistas/bower_components/font-awesome/css/font-awesome.min.css">
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo"><b>Clínica</b>Médica</div>
        <div class="login-box-body">
            <p class="login-box-msg">Inicia sesión para continuar</p>

            <!-- El action ahora apunta a la raíz (index.php) -->
            <form method="post" action="<?= BASE_URL ?>">
                <div class="form-group has-feedback">
                    <input type="text" name="usuario-Ing" class="form-control" placeholder="Usuario" required>
                    <span class="glyphicon glyphicon-user form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <input type="password" name="clave-Ing" class="form-control" placeholder="Contraseña" required>
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-flat">Iniciar Sesión</button>
            </form>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" style="margin-top: 15px;">
                    <?php
                    $errors = ['1' => 'Usuario o contraseña incorrectos.', '2' => 'Ambos campos son requeridos.'];
                    echo $errors[$_GET['error']] ?? 'Error desconocido.';
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="Vistas/bower_components/jquery/dist/jquery.min.js"></script>
    <script src="Vistas/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
</body>
</html>