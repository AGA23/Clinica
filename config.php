<?php
// Ruta base para navegador
define('BASE_URL', '/clinica/');

// Ruta absoluta real del sistema (para includes)
define('ROOT_PATH', realpath(__DIR__) . DIRECTORY_SEPARATOR);

// Ruta a vistas
define('VIEWS_PATH', ROOT_PATH . 'Vistas' . DIRECTORY_SEPARATOR);


//RUTA APP
define('RUTA_APP', $_SERVER['DOCUMENT_ROOT'] . '/clinica');
