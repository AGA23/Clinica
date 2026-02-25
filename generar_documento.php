<?php
// generar_documento.php

// 1. Incluimos nuestro cargador principal.
require_once 'loader.php'; 

// Las clases están disponibles gracias a los autoloaders
use Dompdf\Dompdf;
use Dompdf\Options;

if (isset($_GET['tipo']) && isset($_GET['uuid'])) {
    
    $tipo = $_GET['tipo'];
    $uuid = $_GET['uuid'];

    if ($tipo !== 'certificado' && $tipo !== 'receta') {
        die('Tipo de documento no válido.');
    }

    $documento = CitasM::mdlObtenerDocumentoPorUuid('citas', $tipo, $uuid);

    if ($documento && !empty($documento['contenido'])) {
        
        // --- INICIO DE LA CORRECCIÓN DEFINITIVA ---
        $options = new Options();
        
        // Habilitamos la carga de imágenes remotas (URLs HTTP)
        $options->set('isRemoteEnabled', true);
        
        // [CRUCIAL] Establecemos el directorio raíz del proyecto como un directorio seguro.
        // Dompdf ahora podrá acceder a archivos locales dentro de esta carpeta.
        // Se asume que ROOT_PATH está definido en tu config.php.
        $options->set('chroot', ROOT_PATH);
        
        $dompdf = new Dompdf($options);
        // --- FIN DE LA CORRECCIÓN DEFINITIVA ---

        $dompdf->loadHtml($documento['contenido']);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $nombreArchivo = $tipo . "_" . $uuid . ".pdf";
        $dompdf->stream($nombreArchivo, ["Attachment" => false]);

    } else {
        echo "<h1>Documento no encontrado o inválido.</h1>";
    }
} else {
    echo "<h1>Acceso no permitido.</h1>";
}