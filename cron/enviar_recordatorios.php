<?php
// En /cron/enviar_recordatorios.php

// --- 1. VERIFICACIÓN DE SEGURIDAD ---
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Acceso no autorizado.");
}

// --- 2. CARGAR EL ENTORNO COMPLETO DE LA APLICACIÓN ---
// Esto carga tu config.php, el autoloader de Composer, y tus modelos.
require_once __DIR__ . '/../loader.php';

// Importar las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- INICIO DEL SCRIPT ---
echo "======================================================\n";
echo "--- Iniciando Tarea de Envío de Recordatorios (" . date('Y-m-d H:i:s') . ") ---\n";

// --- 3. OBTENER LA CONFIGURACIÓN DEL RECORDATORIO ---
// Llama al modelo de plantillas para saber cuántas horas antes enviar.
$plantilla = plantillacorreoM::ObtenerPlantillaPorIdentificador('recordatorio_cita');
if (!$plantilla || empty($plantilla['tiempo_envio_horas'])) {
    die("ERROR: No se encontró la plantilla 'recordatorio_cita'. Tarea abortada.\n");
}
$horas_antes = (int)$plantilla['tiempo_envio_horas'];
echo "Configuración detectada: Enviar recordatorios ~{$horas_antes} horas antes.\n";

// --- 4. BUSCAR CITAS QUE NECESITEN RECORDATORIO ---
// Llama al modelo de citas para obtener la lista de citas pendientes.
$citas_a_notificar = CitasM::ObtenerCitasParaRecordatorio($horas_antes);
if (empty($citas_a_notificar)) {
    die("No hay citas que requieran notificación en este momento. Tarea finalizada.\n");
}
echo "Se encontraron " . count($citas_a_notificar) . " citas para notificar.\n\n";

// --- 5. PROCESAR Y ENVIAR CADA CORREO ---
$enviados_exitosamente = 0;
foreach ($citas_a_notificar as $cita) {
    echo "Procesando Cita ID: {$cita['id_cita']} | Para: {$cita['paciente_email']}...";
    
    if (empty($cita['paciente_email']) || !filter_var($cita['paciente_email'], FILTER_VALIDATE_EMAIL)) {
        echo " [ERROR: Email inválido. Marcando como enviado.]\n";
        CitasM::MarcarRecordatorioEnviado($cita['id_cita']);
        continue;
    }

    // Preparar placeholders
    $fecha_hora_formateada = date("d/m/Y \a \l\a\s H:i", strtotime($cita['inicio_cita']));
    $placeholders = [
        '{PACIENTE_NOMBRE}'     => $cita['paciente_nombre'],
        '{DOCTOR_NOMBRE}'       => $cita['doctor_nombre'],
        '{CONSULTORIO_NOMBRE}'  => $cita['consultorio_nombre'],
        '{CITA_FECHA_HORA}'     => $fecha_hora_formateada
    ];
    
    $asunto = str_replace(array_keys($placeholders), array_values($placeholders), $plantilla['asunto']);
    $cuerpo = str_replace(array_keys($placeholders), array_values($placeholders), $plantilla['cuerpo_html']);

    $mail = new PHPMailer(true);
    try {
        // --- CONFIGURACIÓN SMTP USANDO CONSTANTES DE config.php ---
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        // --- REMITENTE Y DESTINATARIO ---
        // Brevo a veces requiere que el 'From' sea de un dominio verificado.
        // Si tu SMTP_USERNAME no es un buen email para mostrar, puedes usar otro verificado.
       $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($cita['paciente_email'], $cita['paciente_nombre']);

        // --- CONTENIDO DEL CORREO ---
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo;
        $mail->CharSet = 'UTF-8';

        $mail->send();
        
        // --- 6. MARCAR LA CITA COMO NOTIFICADA ---
        CitasM::MarcarRecordatorioEnviado($cita['id_cita']);
        
        echo " [ÉXITO]\n";
        $enviados_exitosamente++;
    } catch (Exception $e) {
        echo " [ERROR DE ENVÍO: {$mail->ErrorInfo}]\n";
    }
}

echo "\n--- Proceso finalizado. Correos enviados exitosamente: $enviados_exitosamente ---\n";
echo "======================================================\n";
?>