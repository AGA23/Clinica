<?php


$url_actual = $_GET['url'] ?? 'inicio';
?>
<aside class="main-sidebar">
  <section class="sidebar">
    <ul class="sidebar-menu" data-widget="tree">
      <li class="header">MENÚ DEL DOCTOR</li>

      <!-- Inicio -->
      <li class="<?= ($url_actual == 'inicio') ? 'active' : ''; ?>">
        <a href="<?= BASE_URL ?>index.php?url=inicio">
          <i class="fa fa-home"></i>
          <span>Inicio</span>
        </a>
      </li>

      <!-- Citas -->
      <li class="<?= ($url_actual == 'citas_doctor') ? 'active' : ''; ?>">
        <a href="<?= BASE_URL ?>index.php?url=citas_doctor">
          <i class="fa fa-calendar-check-o"></i>
          <span>Mi Agenda de Citas</span>
        </a>
      </li>

      <!-- [CORREGIDO] Mis Pacientes (Vista de solo consulta) -->
      <li class="<?= ($url_actual == 'mis-pacientes') ? 'active' : ''; ?>">
        <a href="<?= BASE_URL ?>index.php?url=mis-pacientes">
          <i class="fa fa-users"></i>
          <span>Mis Pacientes</span>
        </a>
      </li>

      <!-- Historial de Búsqueda Avanzada -->
       <li class="<?= ($url_actual == 'historial-d') ? 'active' : ''; ?>">
        <a href="<?= BASE_URL ?>index.php?url=historial-d">
          <i class="fa fa-book"></i>
          <span>Búsqueda de Historial</span>
        </a>
      </li>
    </ul>
  </section>
</aside>