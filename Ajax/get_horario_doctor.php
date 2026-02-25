<?php
session_start();
require_once '../config/ConexionBD.php'; // Asegúrate de que la ruta es correcta

$pdo = ConexionBD::getInstancia();

$id_doctor = $_SESSION['id_doctor']; // o ajusta si usas otro nombre
$fecha = $_POST['fecha'];

$dia_semana = date('w', strtotime($fecha)); // 0 (domingo) a 6 (sábado)

// Consulta el horario del doctor ese día
$stmt = $pdo->prepare("SELECT hora_inicio, hora_fin FROM horarios_doctores WHERE id_doctor = ? AND dia_semana = ?");
$stmt->execute([$id_doctor, $dia_semana]);
$horario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($horario) {
  echo json_encode([
    'existe' => true,
    'hora_inicio' => substr($horario['hora_inicio'], 0, 5),
    'hora_fin' => substr($horario['hora_fin'], 0, 5)
  ]);
} else {
  echo json_encode(['existe' => false]);
}
