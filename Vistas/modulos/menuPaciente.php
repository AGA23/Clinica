<?php
// En Vistas/modulos/menuPaciente.php

$url_actual = $_GET['url'] ?? 'inicio';
?>
<aside class="main-sidebar">
    <section class="sidebar">
      <ul class="sidebar-menu" data-widget="tree">
        <li class="header">PORTAL DEL PACIENTE</li>

        <!-- Inicio / Dashboard -->
        <li class="<?= ($url_actual == 'inicio') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=inicio">
            <i class="fa fa-home"></i> <span>Inicio</span>
          </a>
        </li>
        
        <!-- Mi Perfil (Datos de Cuenta) -->
        <li class="<?= ($url_actual == 'perfil-Paciente') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=perfil-Paciente">
            <i class="fa fa-user-circle-o"></i>
            <span>Mi Perfil</span>
          </a>
        </li>

        <!-- [NUEVO] Mi Historia Clínica (Alergias/Enfermedades) -->
        <li class="<?= ($url_actual == 'mi-historia-clinica') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=mi-historia-clinica">
            <i class="fa fa-heartbeat"></i>
            <span>Mi Historia Clínica</span>
          </a>
        </li>

        <!-- Historial de Citas -->
        <li class="<?= ($url_actual == 'historial') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=historial">
            <i class="fa fa-archive"></i>
            <span>Historial de Citas</span>
          </a>
        </li>
        
        <!-- Directorio de Consultorios -->
        <li class="<?= ($url_actual == 'Ver-consultorios') ? 'active' : ''; ?>">
          <a href="<?= BASE_URL ?>index.php?url=Ver-consultorios">
            <i class="fa fa-hospital-o"></i>
            <span>Consultorios</span>
          </a>
        </li>

      </ul>
    </section>
</aside>