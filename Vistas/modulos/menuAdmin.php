<?php
// En Vistas/modulos/menuAdmin.php
$url_actual = $_GET['url'] ?? 'inicio';
?>
<aside class="main-sidebar">
    <section class="sidebar">
      <ul class="sidebar-menu" data-widget="tree">
        <li class="header">ADMINISTRACIÓN GENERAL</li>

        <!-- Inicio -->
        <li class="<?= ($url_actual == 'inicio') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=inicio">
            <i class="fa fa-home"></i> <span>Inicio</span>
          </a>
        </li>
         <!-- Obras sociales -->
        <li>
    <a href="obras-sociales">
        <i class="fa fa-medkit"></i>
        <span>Obras Sociales</span>
    </a>
</li>
        <!-- Calendario Global -->
        <li class="<?= ($url_actual == 'calendario-admin') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=calendario-admin">
            <i class="fa fa-calendar-plus-o"></i> <span>Calendario Global</span>
          </a>
        </li>

        <!-- Historial de Pacientes -->
        <li class="<?= ($url_actual == 'historial-d') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=historial-d">
            <i class="fa fa-book"></i> <span>Historial Clínico</span>
          </a>
        </li>

        
        <!-- Reportes -->
    
       <li class="treeview <?= (in_array($url_actual, ['reportes', 'reportes-admin'])) ? 'active menu-open' : ''; ?>">
  <a href="#">
    <i class="fa fa-pie-chart"></i> <span>Reportes</span>
    <span class="pull-right-container">
      <i class="fa fa-angle-left pull-right"></i>
    </span>
  </a>
  <ul class="treeview-menu">
    <!-- Enlace a la vista de reportes en vivo de toda la clínica -->
    <li class="<?= ($url_actual == 'reportes') ? 'active' : ''; ?>">
      <a href="<?= BASE_URL ?>index.php?url=reportes"><i class="fa fa-circle-o"></i> Reportes en Vivo</a>
    </li>
    <!-- Enlace a la vista de gestión de reportes guardados -->
     <li class="<?= ($url_actual == 'exportar-reportes') ? 'active' : ''; ?>">
      <a href="<?= BASE_URL ?>exportar-reportes"><i class="fa fa-circle-o"></i> Gestión y Exportación</a>
      </li>
  </ul>
</li>

        <li class="header">GESTIÓN DE RECURSOS</li>

        <!-- Personal de Recepción -->
        <li class="<?= ($url_actual == 'secretarios') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=secretarios">
            <i class="fa fa-id-card-o"></i> <span>Personal de Recepción</span>
          </a>
        </li>
        
        <!-- Admins -->
        <li class="<?= ($url_actual == 'admins') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=admins">
            <i class="fa fa-key"></i> <span>Administradores</span>
          </a>
        </li>

        <!-- Doctores -->
        <li class="<?= ($url_actual == 'doctores') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=doctores">
            <i class="fa fa-user-md"></i> <span>Doctores</span>
          </a>
        </li>

        <!-- Consultorios -->
        <li class="<?= ($url_actual == 'consultorios') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=consultorios">
            <i class="fa fa-hospital-o"></i> <span>Consultorios</span>
          </a>
        </li>

        <!-- Tratamientos -->
        <li class="<?= ($url_actual == 'tratamientos') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=tratamientos">
            <i class="fa fa-medkit"></i> <span>Tratamientos</span>
          </a>
        </li>

        <!-- Medicamentos -->
        <li class="<?= ($url_actual == 'medicamentos') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=medicamentos">
            <i class="fa fa-flask"></i> <span>Medicamentos</span>
          </a>
        </li>

        <!-- Pacientes -->
        <li class="<?= ($url_actual == 'pacientes') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=pacientes">
            <i class="fa fa-users"></i> <span>Pacientes</span>
          </a>
        </li>

        <li class="header">AUDITORÍA Y SISTEMA</li>
        
        <!-- Logs de Citas -->
        <li class="<?= ($url_actual == 'citas-log') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=citas-log">
            <i class="fa fa-history"></i> <span>Historial de Cambios</span>
          </a>
        </li>

        <!-- Plantillas de Correo -->
        <li class="<?= ($url_actual == 'plantillas-correo') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=plantillas-correo">
            <i class="fa fa-envelope-o"></i> <span>Plantillas de Correo</span>
          </a>
        </li>

        <!-- Datos de la Clínica -->
        <li class="<?= ($url_actual == 'clinica') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=clinica">
            <i class="fa fa-cogs"></i> <span>Datos de la Clínica</span>
          </a>
        </li>

        <!-- Plantillas de Documentos -->
        <li class="<?= ($url_actual == 'plantillas-documentos') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=plantillas-documentos">
            <i class="fa fa-file-text-o"></i> <span>Plantillas de Documentos</span>
          </a>
        </li>
      </ul>
    </section>
</aside>