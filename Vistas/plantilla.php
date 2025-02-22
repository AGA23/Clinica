<?php
session_start();
require_once __DIR__ . '/../Controladores/inicioC.php';

// Verifica si la sesión está activa y muestra el estado
if (isset($_SESSION["Ingresar"])) {
  echo "Sesión Ingresar está activa: " . $_SESSION["Ingresar"];
} else {
  echo "Sesión Ingresar no está activa.";
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Clínica Médica</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <?php
  // Llamada directa al método sin utilizar el patrón Singleton
  $favicon = new InicioC();
  $favicon->FaviconC();
  ?>

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
    href="http://localhost/clinica/Vistas/bower_components/fullcalendar/dist/fullcalendar.print.min.css" media="print">
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
</head>

<body class="hold-transition skin-blue sidebar-mini login-page">
  <!-- Site wrapper -->

  <?php
  if (isset($_SESSION["Ingresar"]) && $_SESSION["Ingresar"] == true) {
    echo '<div class="wrapper">';

    include __DIR__ . "/modulos/cabecera.php";

    if ($_SESSION["rol"] == "Secretaria") {
      include __DIR__ . "/modulos/menuSecretaria.php";
    } else if ($_SESSION["rol"] == "Paciente") {
      include __DIR__ . "/modulos/menuPaciente.php";
    } else if ($_SESSION["rol"] == "Doctor") {
      include __DIR__ . "/modulos/menuDoctor.php";
    } else if ($_SESSION["rol"] == "Administrador") {
      include __DIR__ . "/modulos/menuAdmin.php";
    }

    $url = array();
    if (isset($_GET["url"])) {
      $url = explode("/", $_GET["url"]);
      if (
        $url[0] == "inicio" || $url[0] == "salir" ||
        $url[0] == "perfil-Secretaria" || $url[0] == "perfil-S" ||
        $url[0] == "consultorios" || $url[0] == "E-C" ||
        $url[0] == "doctores" || $url[0] == "pacientes" ||
        $url[0] == "perfil-Paciente" || $url[0] == "perfil-P" ||
        $url[0] == "Ver-consultorios" || $url[0] == "Doctor" ||
        $url[0] == "historial" || $url[0] == "perfil-Doctor" ||
        $url[0] == "perfil-D" || $url[0] == "Citas" ||
        $url[0] == "perfil-Administrador" || $url[0] == "perfil-A" ||
        $url[0] == "secretarias" || $url[0] == "inicio-editar"
      ) {
        include __DIR__ . "/modulos/" . $url[0] . ".php";
      }
    } else {
      include __DIR__ . "/modulos/inicio.php";
    }

    echo '</div>';
  } else if (isset($_GET["url"])) {
    if ($_GET["url"] == "seleccionar") {
      include __DIR__ . "/modulos/seleccionar.php";
    } else if ($_GET["url"] == "ingreso-Secretaria") {
      include __DIR__ . "/modulos/ingreso-Secretaria.php";
    } else if ($_GET["url"] == "ingreso-Paciente") {
      include __DIR__ . "/modulos/ingreso-Paciente.php";
    } else if ($_GET["url"] == "ingreso-Doctor") {
      include __DIR__ . "/modulos/ingreso-Doctor.php";
    } else if ($_GET["url"] == "ingreso-Administrador") {
      include __DIR__ . "/modulos/ingreso-Administrador.php";
    }
  } else {
    include __DIR__ . "/modulos/seleccionar.php";
  }
  ?>

  <!-- ./wrapper -->

  <script src="http://localhost/clinica/Vistas/bower_components/jquery/dist/jquery.min.js"></script>
  <script src="http://localhost/clinica/Vistas/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
  <script src="http://localhost/clinica/Vistas/bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
  <script src="http://localhost/clinica/Vistas/bower_components/fastclick/lib/fastclick.js"></script>
  <script src="http://localhost/clinica/Vistas/dist/js/adminlte.min.js"></script>
  <script src="http://localhost/clinica/Vistas/dist/js/demo.js"></script>
  <script src="http://localhost/clinica/Vistas/bower_components/datatables.net/js/jquery.dataTables.js"></script>
  <script src="http://localhost/clinica/Vistas/bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
  <script
    src="http://localhost/clinica/Vistas/bower_components/datatables.net-bs/js/dataTables.responsive.min.js"></script>
  <script
    src="http://localhost/clinica/Vistas/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
  <script src="http://localhost/clinica/Vistas/bower_components/moment/moment.js"></script>
  <script src="http://localhost/clinica/Vistas/bower_components/fullcalendar/dist/fullcalendar.min.js"></script>
  <script src="http://localhost/clinica/Vistas/bower_components/fullcalendar/dist/locale/es.js"></script>
  <script src="http://localhost/clinica/Vistas/js/doctores.js"></script>
  <script src="http://localhost/clinica/Vistas/js/pacientes.js"></script>
  <script src="http://localhost/clinica/Vistas/js/secretarias.js"></script>

  <script>
    $(document).ready(function () {
      $('.sidebar-menu').tree()
    })

    var date = new Date()
    var d = date.getDate(),
      m = date.getMonth(),
      y = date.getFullYear()
    $('#calendar').fullCalendar({

      hiddenDays: [0, 6],

      defaultView: 'agendaWeek',

      events: [

        <?php

        $columna = null;
        $valor = null;

        $resultado = CitasC::VerCitasC($columna, $valor);

        foreach ($resultado as $key => $value) {

          if ($value["id_doctor"] == substr($_GET["url"], 7)) {

            echo '{
                              id: ' . $value["id"] . ',
                              title: "' . $value["nyaP"] . '",
                              start: "' . $value["inicio"] . '",
                              end: "' . $value["fin"] . '",
                              color: "' . $value["color"] . '",
                            },';

          }

        }

        ?>

      ]
    })
  </script>

</body>

</html>
