<?php
// Habilitar reporte de errores
error_reporting(E_ALL); // Reportar todos los errores
ini_set('display_errors', 1); // Mostrar errores en pantalla
ini_set('log_errors', 1); // Registrar errores en el archivo de log
ini_set('error_log', __DIR__ . '/php_errors.log'); // Especificar la ruta del archivo de log

session_start(); // Iniciar sesión

// Depuración: Mostrar datos de la sesión
error_log("Sesión iniciada. Datos de sesión: " . print_r($_SESSION, true));

// Verificar si el usuario ya ha iniciado sesión
if (isset($_SESSION["Ingresar"]) && $_SESSION["Ingresar"] == true) {
    error_log("Usuario ha iniciado sesión. Rol: " . $_SESSION["rol"]);

    // Redirigir al usuario a la página correspondiente según su rol
    switch ($_SESSION["rol"]) {
        case "Administrador":
            error_log("Redirigiendo a admin.php");
            if (file_exists("admin.php")) {
                header("Location: admin.php");
                exit();
            } else {
                error_log("Error: admin.php no existe.");
            }
            break;
        case "Doctor":
            error_log("Redirigiendo a doctores.php");
            if (file_exists("doctores.php")) {
                header("Location: doctores.php");
                exit();
            } else {
                error_log("Error: doctores.php no existe.");
            }
            break;
        case "Paciente":
            error_log("Redirigiendo a pacientes.php");
            if (file_exists("pacientes.php")) {
                header("Location: pacientes.php");
                exit();
            } else {
                error_log("Error: pacientes.php no existe.");
            }
            break;
        case "Secretaria":
            error_log("Redirigiendo a secretarias.php");
            if (file_exists("secretarias.php")) {
                header("Location: secretarias.php");
                exit();
            } else {
                error_log("Error: secretarias.php no existe.");
            }
            break;
        default:
            error_log("Rol no válido. Redirigiendo a login.php");
            header("Location: login.php");
            exit();
    }
} else {
    error_log("Usuario no ha iniciado sesión.");
}

// Si no hay sesión activa, mostrar el formulario de inicio de sesión
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Clínica Médica</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <!-- Favicon -->
  <?php
  // Llamada directa al método sin utilizar el patrón Singleton
  $favicon = new InicioC();
  $favicon->FaviconC();
  ?>

  <!-- Estilos -->
  <link rel="stylesheet" href="http://localhost/clinica/Vistas/bower_components/bootstrap/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="http://localhost/clinica/Vistas/bower_components/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="http://localhost/clinica/Vistas/bower_components/Ionicons/css/ionicons.min.css">
  <link rel="stylesheet" href="http://localhost/clinica/Vistas/dist/css/AdminLTE.min.css">
  <link rel="stylesheet" href="http://localhost/clinica/Vistas/dist/css/skins/_all-skins.min.css">
  <link rel="stylesheet"
    href="http://localhost/clinica/Vistas/bower_components/datatables.net-bs/css/responsive.bootstrap.min.css">
  <link rel="stylesheet"
    href="http://localhost/clinica/Vistas/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css">
  <link rel="stylesheet" href="http://localhost/clinica/Vistas/bower_components/fullcalendar/dist/fullcalendar.min.css">
  <link rel="stylesheet"
    href=" http://localhost/clinica/Vistas/bower_components/fullcalendar/dist/fullcalendar.print.min.css" media="print">
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
</head>

<body class="hold-transition skin-blue sidebar-mini login-page">
  <!-- Formulario de inicio de sesión -->
  <div class="login-box">
    <div class="login-logo">
      <a href="#"><b>Clínica Médica</b></a>
    </div>
    <!-- /.login-logo -->
    <div class="login-box-body">
      <p class="login-box-msg">Iniciar Sesión</p>

      <form method="post" action="index.php?ruta=login">
        <div class="form-group has-feedback">
          <input type="text" class="form-control" name="usuario" placeholder="Usuario" required>
          <span class="glyphicon glyphicon-user form-control-feedback"></span>
        </div>

        <div class="form-group has-feedback">
          <input type="password" class="form-control" name="clave" placeholder="Contraseña" required>
          <span class="glyphicon glyphicon-lock form-control-feedback"></span>
        </div>

        <div class="row">
          <div class="col-xs-12">
            <button type="submit" class="btn btn-primary btn-block btn-flat">Ingresar</button>
          </div>
        </div>
      </form>

      <?php
      // Procesar el formulario de login si se envió
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
          error_log("Formulario de login enviado.");

          require_once "Controladores/inicioC.php";
          $login = new InicioC();
          $login->ctrLogin();
      }
      ?>
    </div>
    <!-- /.login-box-body -->
  </div>
  <!-- /.login-box -->

  <!-- Scripts -->
  <script src="http://localhost/clinica/Vistas/bower_components/jquery/dist/jquery.min.js"></script>
  <script src="http://localhost/clinica/Vistas/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
  <script src="http://localhost/clinica/Vistas/bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
  <script src="http://localhost/clinica/Vistas/bower_components/fastclick/lib/fastclick.js"></script>
  <script src="http://localhost/clinica/Vistas/dist/js/adminlte.min.js"></script>
  <script src="http://localhost/clinica/Vistas/dist/js/demo.js"></script>
</body>

</html>