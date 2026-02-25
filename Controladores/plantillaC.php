<?php
class PlantillaControlador
{
    public function LlamarPlantilla()
    {
        echo "Punto 2: Dentro de LlamarPlantilla()<br>"; // Mensaje de depuración

        // Ruta absoluta para asegurar que se encuentra el archivo
        $ruta_absoluta = __DIR__ . "/../Vistas/plantilla.php";
        echo "Ruta absoluta utilizada: " . $ruta_absoluta;
        echo "Punto 3: Ruta absoluta calculada: $ruta_absoluta<br>"; // Mensaje de depuración

        // Verificar si el archivo existe
        if (file_exists($ruta_absoluta)) {
            
            include $ruta_absoluta;
            
        } else {
            echo " El archivo plantilla.php no existe.<br>"; // Mensaje de depuración
        }
    }
}

// Crear una instancia del controlador y llamar al método LlamarPlantilla
$controlador = new PlantillaControlador();
$controlador->LlamarPlantilla();
?>
