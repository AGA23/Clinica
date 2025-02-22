<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start(); // Iniciar la sesión solo si no está ya activa
}

if (!empty($_POST['usuario-Ing']) && !empty($_POST['clave-Ing'])) {
  $usuario = $_POST['usuario-Ing'];
  $clave = $_POST['clave-Ing'];

  require_once 'Controladores/secretariasC.php';
  $ingreso = new SecretariasC();
  $respuesta = $ingreso->IngresarSecretariaC($usuario, $clave);

  if ($respuesta) {
    $_SESSION['usuario'] = $usuario;
    $_SESSION['Ingresar'] = true;
    $_SESSION['rol'] = 'Secretaria';
    $_SESSION['id'] = $respuesta['id']; // Almacenar el id en la sesión
    echo "<script>window.location = '/clinica/Vistas/plantilla.php';</script>";
    exit();
  } else {
    echo "<div class='alert alert-danger'>Usuario o contraseña incorrectos.</div>";
  }
} else {
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<div class='alert alert-warning'>Por favor, ingresa tu usuario y contraseña.</div>";
  }
}
?>

<div class="login-box">
  <div class="login-logo">
    <a href="#"><b>Clínica Médica</b></a>
  </div>
  <div class="login-box-body">
    <p class="login-box-msg">Ingresar como Secretaria</p>

    <form method="post">
      <div class="form-group has-feedback">
        <input type="text" class="form-control" name="usuario-Ing" placeholder="Usuario">
        <span class="glyphicon glyphicon-user form-control-feedback"></span>
      </div>

      <div class="form-group has-feedback">
        <input type="password" class="form-control" name="clave-Ing" placeholder="Contraseña">
        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
      </div>

      <div class="row">
        <div class="col-xs-12">
          <button type="submit" class="btn btn-primary btn-block btn-flat">Ingresar</button>
        </div>
      </div>
    </form>
  </div>
</div>