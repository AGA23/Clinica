<header class="main-header">
  <!-- Logo -->
  <a href="<?php echo BASE_URL; ?>inicio" class="logo">
    <span class="logo-mini"><b>C M</b></span>
    <span class="logo-lg"><b>CLÍNICA MÉDICA</b></span>
  </a>

  <nav class="navbar navbar-static-top">
    <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </a>

    <div class="navbar-custom-menu">
      <ul class="nav navbar-nav">
        <li class="dropdown user user-menu">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <?php
           
            $foto = $_SESSION["foto"] ?? "";
            if (empty($foto)) {
              echo '<img src="'.BASE_URL.'Vistas/img/defecto.png" class="user-image" alt="User Image">';
            } else {
              echo '<img src="'.BASE_URL.$foto.'" class="user-image" alt="User Image">';
            }
            ?>
            
         
            <span class="hidden-xs">
              <?php echo htmlspecialchars(($_SESSION["nombre"] ?? "") . " " . ($_SESSION["apellido"] ?? "")); ?>
            </span>
          </a>

          <ul class="dropdown-menu">
            <li class="user-footer">
              <div class="pull-left">
                <?php
                $rol = $_SESSION["rol"] ?? "";
                $perfiles = [
                  "Secretaria" => "secretaria/perfil",
                  "Administrador" => "admin/perfil",
                  "Doctor" => "doctor/perfil",
                  "Paciente" => "paciente/perfil"
                ];
                $rutaPerfil = $perfiles[$rol] ?? BASE_URL;
                ?>
                <a href="<?php echo BASE_URL; ?>Vistas/modulos/perfil-Paciente.php" class="btn btn-primary btn-flat">Perfil</a>
              </div>
              <div class="pull-right">
              <a href="<?php echo BASE_URL; ?>Vistas/modulos/salir.php" class="btn btn-danger btn-flat">Salir</a>
              </div>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>
</header>