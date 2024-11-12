<div class="login-box">
  <div class="login-logo">
    <a href="#"><b>Clínica Médica</b></a>
  </div>
  <!-- /.login-logo -->
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
        <!-- /.col -->
        <div class="col-xs-12">
          <button type="submit" class="btn btn-primary btn-block btn-flat">Ingresar</button>
        </div>
        <!-- /.col -->
      </div>

      <?php
      // Verifica si el formulario fue enviado y los campos no están vacíos
      if (!empty($_POST['usuario-Ing']) && !empty($_POST['clave-Ing'])) {
        $usuario = $_POST['usuario-Ing'];  // Obtén el usuario del formulario 1
        $clave = $_POST['clave-Ing'];  // Obtén la clave del formulario
      
        // Asegúrate de incluir la clase SecretariasC con la ruta correcta
        require_once 'Controladores\secretariasC.php';  // Ruta correcta a la clase
      
        $ingreso = new SecretariasC();
        $respuesta = $ingreso->IngresarSecretariaC($usuario, $clave); // Pasa los parámetros
      
        if ($respuesta) {
          session_start(); // Iniciar la sesión
          $_SESSION['usuario'] = $usuario; // Almacenar el usuario en la sesión
          echo "<script>window.location = '/clinica/Vistas/plantilla.php';</script>";

          exit();
        } else {
          echo "<div class='alert alert-danger'>Usuario o contraseña incorrectos.</div>";
        }
      } else {
        // Mensaje si los campos están vacíos
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
          echo "<div class='alert alert-warning'>Por favor, ingresa tu usuario y contraseña.</div>";
        }
      }
      ?>
    </form>
  </div>
  <!-- /.login-box-body -->
</div>