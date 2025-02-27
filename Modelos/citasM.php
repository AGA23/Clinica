<?php

require_once "ConexionBD.php";

require_once __DIR__ . '/../vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class CitasM {

    private $pdo;

    public function __construct() {
        $this->pdo = ConexionBD::getInstancia();
    }

    // Insertar una cita para un paciente
    public function EnviarCitaM($tablaBD, $datosC) {
        try {
            $sql = "INSERT INTO `{$tablaBD}` (`id_doctor`, `id_consultorio`, `id_paciente`, `nyaP`, `documento`, `inicio`, `fin`, `estado`)
                    VALUES (:id_doctor, :id_consultorio, :id_paciente, :nyaP, :documento, :inicio, :fin, :estado)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":id_doctor", $datosC["Did"], PDO::PARAM_INT);
            $stmt->bindParam(":id_consultorio", $datosC["Cid"], PDO::PARAM_INT);
            $stmt->bindParam(":id_paciente", $datosC["Pid"], PDO::PARAM_INT);
            $stmt->bindParam(":nyaP", $datosC["nyaC"], PDO::PARAM_STR);
            $stmt->bindParam(":documento", $datosC["documentoC"], PDO::PARAM_STR);
            $stmt->bindParam(":inicio", $datosC["fyhIC"], PDO::PARAM_STR);
            $stmt->bindParam(":fin", $datosC["fyhFC"], PDO::PARAM_STR);
            $stmt->bindValue(":estado", 'disponible', PDO::PARAM_STR); // Estado predeterminado
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en EnviarCitaM: " . $e->getMessage());
            return false;
        }
    }

    // Obtener todas las citas (con filtro opcional)
    public function VerCitasM($tablaBD, $columna = null, $valor = null) {
        try {
            $sql = "SELECT * FROM $tablaBD";
            if ($columna && $valor) {
                $sql .= " WHERE $columna = :valor";
            }
            $stmt = $this->pdo->prepare($sql);
            if ($columna && $valor) {
                $stmt->execute(['valor' => $valor]);
            } else {
                $stmt->execute();
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en VerCitasM: " . $e->getMessage());
            return [];
        }
    }

    // Verificar disponibilidad de un horario
    public function VerificarDisponibilidad($fyhIC, $fyhFC) {
        try {
            $sql = "SELECT COUNT(*) as count FROM citas WHERE inicio = :inicio AND fin = :fin AND estado = 'disponible'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['inicio' => $fyhIC, 'fin' => $fyhFC]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado['count'] == 0; // Devuelve true si el horario está disponible
        } catch (PDOException $e) {
            error_log("Error en VerificarDisponibilidad: " . $e->getMessage());
            return false;
        }
    }

    // Bloquear un horario temporalmente
    public function BloquearHorario($fyhIC, $fyhFC) {
        try {
            $sql = "UPDATE citas SET estado = 'bloqueado' WHERE inicio = :inicio AND fin = :fin";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['inicio' => $fyhIC, 'fin' => $fyhFC]);
        } catch (PDOException $e) {
            error_log("Error en BloquearHorario: " . $e->getMessage());
            return false;
        }
    }

    // Liberar un horario bloqueado
    public function LiberarHorario($fyhIC, $fyhFC) {
        try {
            $sql = "UPDATE citas SET estado = 'disponible' WHERE inicio = :inicio AND fin = :fin";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['inicio' => $fyhIC, 'fin' => $fyhFC]);
        } catch (PDOException $e) {
            error_log("Error en LiberarHorario: " . $e->getMessage());
            return false;
        }
    }

    // Cancelar una cita
    public function CancelarCitaM($tablaBD, $id) {
        try {
            $sql = "DELETE FROM `{$tablaBD}` WHERE `id` = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en CancelarCitaM: " . $e->getMessage());
            return false;
        }
    }

    // Enviar correo con PHPMailer
    public function EnviarCorreoPHPMailer($destinatario, $asunto, $mensaje) {
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Servidor SMTP de Gmail
            $mail->SMTPAuth = true;
            $mail->Username = 'tu_correo@gmail.com'; // Tu correo Gmail
            $mail->Password = 'tu_contraseña'; // Tu contraseña de Gmail
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Destinatario y contenido
            $mail->setFrom('tu_correo@gmail.com', 'Clínica Médica');
            $mail->addAddress($destinatario);
            $mail->Subject = $asunto;
            $mail->Body = $mensaje;

            // Enviar el correo
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error al enviar el correo: " . $mail->ErrorInfo);
            return false;
        }
    }
}
?>