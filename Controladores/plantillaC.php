<?php

class Plantilla {

    // Método para llamar la plantilla
    public function LlamarPlantilla() {
        // Ruta del archivo de la plantilla
        $ruta = "Vistas\plantilla.php";
        
        // Verificar si el archivo plantilla.php existe
        if (file_exists($ruta)) {
            // Si el archivo existe, incluirlo
            include $ruta;
        } else {
            // Si no existe el archivo, manejar el error
            error_log("Error: El archivo plantilla.php no se encuentra en la ruta especificada.");
            
            // Puedes redirigir al usuario a una página de error, o mostrar un mensaje en el navegador
            echo "<div class='alert alert-danger'>Error: No se pudo cargar la plantilla. El archivo no fue encontrado.</div>";
            
            // Opcionalmente, podrías redirigir a la página de inicio o a una página de error
            // echo "<script>window.location = 'index.php';</script>";
        }
    }

}
?>
