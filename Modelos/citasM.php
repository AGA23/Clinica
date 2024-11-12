<?php 
require_once "ConexionBD.php";

class CitasM {
    private $pdo;

    // Constructor para inicializar la conexión a la base de datos
    public function __construct() {
        // Obtener la instancia de la conexión a la base de datos usando el patrón Singleton
        $this->pdo = ConexionBD::getInstancia();
    }

    // Método para insertar una cita para un paciente
    public function EnviarCitaM($tablaBD, $datosC) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$tablaBD}` (`id_doctor`, `id_consultorio`, `id_paciente`, `nyaP`, `documento`, `inicio`, `fin`)
                VALUES (:id_doctor, :id_consultorio, :id_paciente, :nyaP, :documento, :inicio, :fin)
            ");

            // Vincular parámetros
            $stmt->bindParam(":id_doctor", $datosC["Did"], PDO::PARAM_INT);
            $stmt->bindParam(":id_consultorio", $datosC["Cid"], PDO::PARAM_INT);
            $stmt->bindParam(":id_paciente", $datosC["Pid"], PDO::PARAM_INT);
            $stmt->bindParam(":nyaP", $datosC["nyaC"], PDO::PARAM_STR);
            $stmt->bindParam(":documento", $datosC["documentoC"], PDO::PARAM_STR);
            $stmt->bindParam(":inicio", $datosC["fyhIC"], PDO::PARAM_STR);
            $stmt->bindParam(":fin", $datosC["fyhFC"], PDO::PARAM_STR);

            // Ejecutar consulta
            return $stmt->execute();
        } catch (PDOException $e) {
            // Manejo de errores
            error_log("Error en EnviarCitaM: " . $e->getMessage());
            return false;
        }
    }

    // Método para obtener todas las citas
    public function VerCitasM($tablaBD) {
        try {
            $stmt = $this->pdo->prepare("SELECT `id`, `id_doctor`, `id_consultorio`, `id_paciente`, `nyaP`, `documento`, `inicio`, `fin` FROM `{$tablaBD}`");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Manejo de errores
            error_log("Error en VerCitasM: " . $e->getMessage());
            return [];
        }
    }

    // Método para pedir cita como doctor
    public function PedirCitaDoctorM($tablaBD, $datosC) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$tablaBD}` (`id_doctor`, `id_consultorio`, `nyaP`, `documento`, `inicio`, `fin`)
                VALUES (:id_doctor, :id_consultorio, :nyaP, :documento, :inicio, :fin)
            ");

            // Vincular parámetros
            $stmt->bindParam(":id_doctor", $datosC["Did"], PDO::PARAM_INT);
            $stmt->bindParam(":id_consultorio", $datosC["Cid"], PDO::PARAM_INT);
            $stmt->bindParam(":nyaP", $datosC["nombreP"], PDO::PARAM_STR);
            $stmt->bindParam(":documento", $datosC["documentoP"], PDO::PARAM_STR);
            $stmt->bindParam(":inicio", $datosC["fyhIC"], PDO::PARAM_STR);
            $stmt->bindParam(":fin", $datosC["fyhFC"], PDO::PARAM_STR);

            // Ejecutar consulta
            return $stmt->execute();
        } catch (PDOException $e) {
            // Manejo de errores
            error_log("Error en PedirCitaDoctorM: " . $e->getMessage());
            return false;
        }
    }

    // Método para cancelar una cita
    public function CancelarCitaM($tablaBD, $id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM `{$tablaBD}` WHERE `id` = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);

            // Ejecutar consulta
            return $stmt->execute();
        } catch (PDOException $e) {
            // Manejo de errores
            error_log("Error en CancelarCitaM: " . $e->getMessage());
            return false;
        }
    }

    // Método para cerrar la conexión a la base de datos (no es necesario con el patrón Singleton)
    public function cerrarConexion() {
        // No es necesario cerrar la conexión explícitamente, ya que el patrón Singleton la maneja automáticamente.
    }
}
?>
