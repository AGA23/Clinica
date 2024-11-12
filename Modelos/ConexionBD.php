<?php

class ConexionBD {

    private static $instancia = null;
    private $bd;

    private function __construct() {
        $this->bd = new PDO("mysql:host=localhost;dbname=clinica", "root", "");
        $this->bd->exec("set names utf8");
    }

    public static function getInstancia() {
        if (self::$instancia === null) {
            self::$instancia = new ConexionBD();
        }
        return self::$instancia->bd;
    }
}
