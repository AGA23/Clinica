<?php
// En config.php

/**
 * @const BASE_URL
 * La ruta base para el NAVEGADOR (URL).
 * Se usa en enlaces <a>, <link>, <script>, action de formularios y redirecciones header().
 * Siempre debe terminar con una barra '/'.
 */
// [CORRECCIÓN IMPORTANTE] Para Dompdf y la consistencia general, es mejor usar la URL completa.
define('BASE_URL', 'http://localhost/clinica/');

/**
 * @const ROOT_PATH
 * La ruta absoluta real en el SISTEMA DE ARCHIVOS del servidor.
 * Es la forma más segura para hacer require_once, include, file_exists, etc.
 * Termina con el separador de directorios correcto para el sistema operativo.
 */
define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);

/**
 * @const VIEWS_PATH
 * Un atajo conveniente para la carpeta de Vistas, construido sobre ROOT_PATH.
 */
define('VIEWS_PATH', ROOT_PATH . 'Vistas' . DIRECTORY_SEPARATOR);


// --- CONFIGURACIÓN PARA ENVÍO DE CORREO (SMTP) ---
define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', '95d5d2001@smtp-brevo.com');
define('SMTP_PASSWORD', '9Ev4jBOdKpIAW1Mc');

// [NUEVO] Define el email y nombre que verán los pacientes
define('SMTP_FROM_EMAIL', 'clinicamedica1341@gmail.com'); // El email que quieres que vean como remitente
define('SMTP_FROM_NAME', 'Clínica Médica');

?>