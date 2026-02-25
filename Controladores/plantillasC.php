<?php


class PlantillasC {

    /**
     * Obtiene la lista de plantillas para los selectores.
     */
    public function MostrarPlantillasC($tipo) {
        return PlantillasM::ObtenerPlantillasPorTipoM($tipo);
    }
}
?>