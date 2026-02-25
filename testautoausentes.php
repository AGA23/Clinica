<?php
// AUTO-MARCAR AUSENTES (FILE PARA CRON O INCLUDE)
date_default_timezone_set('America/Argentina/Cordoba');

require_once __DIR__ . '/../Modelos/ConexionBD.php';

try {
    $pdo = ConexionBD::getInstancia();
    $now = new DateTime('now');
    $hoy = $now->format('Y-m-d');
    $diaSemana = (int)$now->format('N');

    // 1) Obtener horarios de cierre
    $stmtHorarios = $pdo->prepare("
        SELECT id_consultorio, hora_cierre
        FROM horarios_consultorios
        WHERE dia_semana = :dia
    ");
    $stmtHorarios->execute([':dia' => $diaSemana]);
    $horarios = $stmtHorarios->fetchAll(PDO::FETCH_ASSOC);

    if (!$horarios) return;

    $mapCierre = [];
    foreach ($horarios as $h) {
        $horaC = trim($h['hora_cierre'] ?? '');
        if ($horaC === '') continue;
        if (preg_match('/^\d{2}:\d{2}$/', $horaC)) $horaC .= ':00';

        if (!isset($mapCierre[$h['id_consultorio']]) || $horaC > $mapCierre[$h['id_consultorio']]) {
            $mapCierre[$h['id_consultorio']] = $horaC;
        }
    }

    if (empty($mapCierre)) return;

    // 2) Buscar citas con estado Pendiente o NULL
    $stmtCitas = $pdo->prepare("
        SELECT id, id_consultorio, estado, inicio
        FROM citas
        WHERE DATE(inicio) = :hoy
          AND (estado = 'Pendiente' OR estado IS NULL)
    ");
    $stmtCitas->execute([':hoy' => $hoy]);
    $citas = $stmtCitas->fetchAll(PDO::FETCH_ASSOC);

    $total = 0;
    $graciaMinutos = 15;

    foreach ($citas as $cita) {
        $cons = $cita['id_consultorio'];
        if (!isset($mapCierre[$cons])) continue;

        // Hora de cierre del consultorio
        $dtCierre = new DateTime("$hoy {$mapCierre[$cons]}");

        // Inicio + gracia
        $dtInicio = new DateTime($cita['inicio']);
        $dtGracia = clone $dtInicio;
        $dtGracia->modify("+{$graciaMinutos} minutes");

        if ($now >= $dtCierre || $now > $dtGracia) {
            $upd = $pdo->prepare("
                UPDATE citas SET estado = 'Ausente'
                WHERE id = :id AND (estado = 'Pendiente' OR estado IS NULL)
            ");
            $upd->execute([':id' => $cita['id']]);

            if ($upd->rowCount()) {
                $log = $pdo->prepare("
                    INSERT INTO historial_cambios_citas
                        (id_cita, fecha_cambio, campo_modificado, valor_anterior, valor_nuevo, usuario_modifico, id_usuario, rol_usuario)
                    VALUES (:id, NOW(), 'estado', :ant, 'Ausente', 'Sistema', 0, 'Auto')
                ");
                $log->execute([
                    ':id' => $cita['id'],
                    ':ant' => $cita['estado'] ?? 'NULL'
                ]);
                $total++;
            }
        }
    }

    error_log("Auto-Ausentes ejecutado. Marcadas: $total");

} catch (Exception $e) {
    error_log("Auto-Ausentes ERROR: ".$e->getMessage());
}
