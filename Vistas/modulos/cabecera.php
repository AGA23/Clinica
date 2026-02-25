<?php
// En Vistas/modulos/cabecera.php

// --- OBTENER DATOS DE LA CLÍNICA ---
// Se asume que la clase ClinicaM ya está cargada por el loader.php.
// Llamamos al método que obtiene los datos globales.
$infoClinica = ClinicaM::ObtenerDatosGlobalesM();

// Guardamos el nombre en una variable para usarla en el HTML.
// Se incluye un valor por defecto ('Clínica Médica') por si la consulta falla.
$nombreClinica = $infoClinica['nombre_clinica'] ?? 'Clínica Médica';

?>
<header class="main-header">
  
  <a href="<?= BASE_URL ?>index.php?url=inicio" class="logo">
    <span class="logo-mini"><b>CM</b></span>
    <!-- El texto del logo ahora es dinámico, pero la estructura HTML es la original -->
    <span class="logo-lg"><b><?= htmlspecialchars($nombreClinica) ?></b></span>
  </a>

  <nav class="navbar navbar-static-top">
    
    <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
      <span class="sr-only">Toggle navigation</span>
    </a>

    <div class="navbar-custom-menu">
      <ul class="nav navbar-nav">
        
        <li class="dropdown user user-menu">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <?php
            $foto = $_SESSION["foto"] ?? "";
            $rutaFoto = !empty($foto) ? BASE_URL . $foto : BASE_URL . 'Vistas/img/user-default.png';
            ?>
            <img src="<?= $rutaFoto ?>" class="user-image" alt="Foto de Usuario">
            <span class="hidden-xs">
              <?= htmlspecialchars(($_SESSION["nombre"] ?? "") . " " . ($_SESSION["apellido"] ?? "")); ?>
            </span>
          </a>

          <ul class="dropdown-menu">
            
            <li class="user-header">
              <img src="<?= $rutaFoto ?>" class="img-circle" alt="Foto de Usuario">
              <p>
                <?= htmlspecialchars(($_SESSION["nombre"] ?? "") . " " . ($_SESSION["apellido"] ?? "")); ?>
                <small>Rol: <?= htmlspecialchars($_SESSION["rol"] ?? ""); ?></small>
              </p>
            </li>
            
            <li class="user-footer">
              <div class="pull-left">
                <?php
                  $rol = $_SESSION["rol"] ?? "";
                  $perfiles = [
                      "Secretario"    => "perfil-secretario", 
                      "Administrador" => "perfil-Administrador",
                      "Doctor"        => "perfil-Doctor",
                      "Paciente"      => "perfil-Paciente"
                  ];
                  $nombreModulo = $perfiles[$rol] ?? 'inicio';
                  $urlPerfil = BASE_URL . "index.php?url=" . $nombreModulo;
                ?>
                <a href="<?= $urlPerfil ?>" class="btn btn-primary btn-flat">Mi Perfil</a>
              </div>
              <div class="pull-right">
                <a href="<?= BASE_URL ?>Vistas/modulos/salir.php" class="btn btn-danger btn-flat">Salir</a>
              </div>
            </li>
          </ul>
        </li>
        
      </ul>
    </div>
  </nav>
</header>