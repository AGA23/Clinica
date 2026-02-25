<?php

class ConexionBD {

    private static $instancia = null; // Instancia única de la conexión
    private $bd; // Variable para almacenar la conexión PDO

    // Constructor privado para evitar la creación de instancias externas
    private function __construct() {
        try {
            // Establecer la conexión a la base de datos
            $this->bd = new PDO("mysql:host=localhost;dbname=clinica", "root", "");
            $this->bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Manejo de errores
            $this->bd->exec("set names utf8"); // Establecer la codificación de caracteres
        } catch (PDOException $e) {
            // Manejo de errores de conexión
            die("Error de conexión: " . $e->getMessage());
        }
    }

    // Método para obtener la instancia de la conexión
    public static function getInstancia() {
        if (self::$instancia === null) {
            self::$instancia = new ConexionBD(); // Crear una nueva instancia si no existe
        }
        return self::$instancia->bd; // Retornar la conexión
    }
}
?>