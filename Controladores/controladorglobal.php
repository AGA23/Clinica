<?php
class ControladorGlobal {

    public static function MarcarCitasAusentesAutomatico() {
        // evitar ejecución en ajax
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) return;

        CitasM::MarcarAusentesAutomaticoM();
    }
}
