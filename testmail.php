<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // PHPMailer desde Composer

$mail = new PHPMailer(true);

try {
    // --- CONFIGURACIÓN SMTP BREVO ---
    $mail->isSMTP();
    $mail->Host       = 'smtp-relay.brevo.com';       // Servidor SMTP Brevo
    $mail->SMTPAuth   = true;
    $mail->Username   = '95d5d2001@smtp-brevo.com';   // Usuario SMTP proporcionado por Brevo
    $mail->Password   = '9Ev4jBOdKpIAW1Mc';             // La contraseña/API que generaste
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
    $mail->Port       = 587;                           // Puerto STARTTLS

    // --- DEBUG OPCIONAL ---
    $mail->SMTPDebug = 2;          // Muestra la comunicación con el servidor
    $mail->Debugoutput = 'echo';   // Mostrar en pantalla

    // --- REMITENTE Y DESTINATARIO ---
    $mail->setFrom('clinicamedica1341@gmail.com', 'Clínica Educativa');
    $mail->addAddress('zerolegion66@gmail.com', 'Paciente');

    // --- CONTENIDO DEL CORREO ---
    $mail->isHTML(true);
    $mail->Subject = 'Recordatorio de cita médica';
    $mail->Body    = '<b>Hola, este es una prueba de phpmailer testmail.php.</b>';
    $mail->AltBody = 'Hola, este es un recordatorio de tu cita médica.';

    $mail->send();
    echo "✅ Correo enviado correctamente";
} catch (Exception $e) {
    echo "❌ Error: {$mail->ErrorInfo}";
}
