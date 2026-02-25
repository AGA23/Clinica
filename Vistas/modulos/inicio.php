<?php
// Este archivo ahora actúa como un simple "lanzador".
// No contiene HTML. Su única responsabilidad es crear una instancia
// del controlador de inicio y llamar al método principal.

// El autoloader (desde loader.php) se encargará de encontrar la clase InicioC.
$inicioController = new InicioC();
$inicioController->MostrarInicioC();

?>