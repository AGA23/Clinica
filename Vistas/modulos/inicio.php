<?php

$inicio = new InicioC();
$inicio->MostrarInicioC();

if ($_SESSION["rol"] === "Administrador") {
    echo '<a href="' . BASE_URL . 'admin/editar-inicio" class="btn btn-success btn-lg">Editar</a>';
}

echo '<a href="' . BASE_URL . strtolower($_SESSION["rol"]) . '/perfil" class="btn btn-primary btn-lg">Ver Perfil</a>';
?>