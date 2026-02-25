<?php
// En Vistas/modulos/menuSecretarios.php
$url_actual = $_GET['url'] ?? 'inicio';

// --- SOLUCIÓN: Definir la variable faltante ---
// Asignamos la ruta a la que debe ir el botón 'Reportes'.
// Si tu ruta se llama 'reportes-citas' o algo diferente, cámbialo aquí.
$url_reporte = 'reportes'; 
?>

<aside class="main-sidebar">
    <section class="sidebar">
      <ul class="sidebar-menu" data-widget="tree">
        <li class="header">PANEL DE RECEPCIÓN</li>

        <!-- Inicio / Dashboard -->
        <li class="<?= ($url_actual == 'inicio') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>inicio">
            <i class="fa fa-dashboard"></i> <span>Dashboard</span>
          </a>
        </li>

        <!-- Calendario de Citas -->
        <li class="<?= ($url_actual == 'calendario-citas') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>calendario-citas">
            <i class="fa fa-calendar"></i>
            <span>Calendario de Citas</span>
          </a>
        </li>

        <!-- Historial Clínico -->
        <li class="<?= ($url_actual == 'historial-d') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>historial-d">
            <i class="fa fa-book"></i>
            <span>Historial Clínico</span>
          </a>
        </li>

        <!-- Reportes (Aquí estaba el error) -->
        <li class="<?= ($url_actual == $url_reporte) ? 'active' : ''; ?>">
          <a href="<?= BASE_URL . $url_reporte ?>">
            <i class="fa fa-pie-chart"></i>
            <span>Reportes</span>
          </a>
        </li>
        
        <li class="header">GESTIÓN</li>

        <!-- Pacientes -->
        <li class="<?= ($url_actual == 'pacientes') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>pacientes">
            <i class="fa fa-users"></i>
            <span>Pacientes</span>
          </a>
        </li>

        <!-- Doctores (limitado a su consultorio) -->
        <li class="<?= ($url_actual == 'doctores') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>doctores">
            <i class="fa fa-user-md"></i>
            <span>Doctores</span>
          </a>
        </li>

        <!-- Consultorios (solo puede editar el suyo) -->
        <li class="<?= ($url_actual == 'consultorios') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>consultorios">
            <i class="fa fa-hospital-o"></i>
            <span>Mi Consultorio</span>
          </a>
        </li>

        <!-- Tratamientos (limitado a su consultorio) -->
        <li class="<?= ($url_actual == 'tratamientos') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>tratamientos">
            <i class="fa fa-medkit"></i> 
            <span>Tratamientos</span>
          </a>
        </li>

        <li class="header">CONFIGURACIÓN</li>

        <!-- Plantillas de Correos -->
        <li class="<?= ($url_actual == 'plantillas-correo') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>plantillas-correo">
            <i class="fa fa-envelope-o"></i> 
            <span>Plantillas de Correos</span>
          </a>
        </li>

      </ul>
    </section>
</aside>