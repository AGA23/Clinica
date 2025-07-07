<?php
include VIEWS_PATH . 'modulos/menuDoctor.php';
require_once ROOT_PATH . '/Controladores/citasC.php';
require_once ROOT_PATH . '/Controladores/pacientesC.php';
require_once ROOT_PATH . '/Modelos/CitasM.php';
require_once ROOT_PATH . '/Modelos/HorariosConsultoriosM.php';
require_once ROOT_PATH . '/Modelos/HorariosDoctoresM.php';
require_once ROOT_PATH . '/Modelos/DoctoresM.php';
require_once ROOT_PATH . '/Modelos/ConsultoriosM.php';
require_once ROOT_PATH . '/Modelos/MedicamentosM.php';
require_once ROOT_PATH . '/Modelos/TratamientosM.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Doctor') {
    header("Location: " . BASE_URL . "login");
    exit();
}

$id_doctor = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? null;
if (!$id_doctor) die("Error: ID del doctor no definido en sesión.");

$pacientesController = new PacientesC();
$pacientes = $pacientesController->ListarPacientesC();

$consultorio_id = DoctoresM::ObtenerConsultorioDeDoctor($id_doctor);
$consultorio_nombre = null;
if ($consultorio_id) {
    $consultorio = ConsultoriosM::VerConsultorioPorId($consultorio_id);
    $consultorio_nombre = $consultorio['nombre'] ?? null;
} else {
    $consultorio_id = '';
}

$horarios_doctor = HorariosDoctoresM::ObtenerHorariosPorDoctor($id_doctor);
$horarios_consultorio = $consultorio_id !== '' ? HorariosConsultoriosM::ObtenerHorariosPorConsultorio($consultorio_id) : [];

$citasController = new CitasC();
$citas = $citasController->VerCitasDoctorC($id_doctor);

$citas_proximas = [];
$citas_pasadas_pendientes = [];
$citas_historial = [];
$ahora = date('Y-m-d H:i:s');

foreach ($citas as $cita) {
    if ($cita['inicio'] >= $ahora && $cita['estado'] === 'Pendiente') {
        $citas_proximas[] = $cita;
    } elseif ($cita['inicio'] < $ahora && $cita['estado'] === 'Pendiente') {
        $citas_pasadas_pendientes[] = $cita;
    } else {
        $citas_historial[] = $cita;
    }
}

// Cargar medicamentos (todos)
$medicamentos_disponibles = MedicamentosM::ObtenerTodos();

// Cargar tratamientos del doctor
$tratamientos_disponibles = TratamientosM::ObtenerTratamientosPorDoctor($id_doctor);

// Procesar finalización de cita
if (isset($_POST['guardar_finalizacion'])) {
    $id_cita = $_POST['id_cita_finalizar'];
    $motivo = trim($_POST['motivo']);
    $observaciones = trim($_POST['observaciones']);
    $peso = $_POST['peso'] ?? null;
    $medicamentos = $_POST['medicamentos'] ?? [];
    $tratamientos = $_POST['tratamientos'] ?? [];

    if (empty($motivo) || empty($observaciones)) {
        echo '<script>alert("❌ Debes completar el motivo y las observaciones.");</script>';
        return;
    }

    if ($peso === null || $peso === '' || !is_numeric($peso) || $peso <= 0 || $peso > 500) {
        echo '<script>alert("❌ Debes ingresar un peso válido entre 0.1 y 500 kg.");</script>';
        return;
    }

    $cita_actual = $citasController->ObtenerCitaC($id_cita);

    if (!$cita_actual || $cita_actual['id_doctor'] != $id_doctor) {
        echo '<script>alert("❌ Cita no válida o no pertenece al doctor.");</script>';
        return;
    }

    $fecha_cita = date('Y-m-d', strtotime($cita_actual['inicio']));
    $fecha_actual = date('Y-m-d');

    if ($fecha_cita !== $fecha_actual) {
        echo '<script>alert("⛔ Solo puedes finalizar citas correspondientes al día de hoy.");</script>';
        return;
    }

    $resultado = $citasController->FinalizarCitaC($id_cita, $motivo, $observaciones, $peso, $medicamentos, $tratamientos);

    if (isset($resultado['success'])) {
        echo '<script>alert("✅ ' . htmlspecialchars($resultado['success']) . '"); window.location.href="citas_doctor.php";</script>';
        exit;
    } else {
        echo '<script>alert("❌ ' . htmlspecialchars($resultado['error'] ?? 'Error al finalizar la cita') . '");</script>';
    }
}

// Procesar cancelación
if (isset($_POST['cancelar_cita'])) {
    $id_cita = filter_input(INPUT_POST, 'id_cita', FILTER_VALIDATE_INT);
    $motivo = trim($_POST['motivo_cancelacion'] ?? '');

    if (!$id_cita || empty($motivo)) {
        $_SESSION['mensaje'] = "❌ Debes proporcionar un motivo válido.";
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $resultado = $citasController->CancelarCitaC($id_cita, $motivo);

    $_SESSION['mensaje'] = isset($resultado['success']) ? "✅ " . $resultado['success'] : "❌ " . ($resultado['error'] ?? 'Error al cancelar cita');
    $_SESSION['tipo_mensaje'] = isset($resultado['success']) ? "success" : "danger";

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Validación creación de cita
if (isset($_POST['crear_cita'])) {
    $fecha_cita = $_POST['fecha'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    $hora_fin = $_POST['hora_fin'] ?? '';
    $id_paciente = $_POST['id_paciente'] ?? '';
    $motivo = $_POST['motivo'] ?? '';

    $fecha_hoy = date('Y-m-d');
    $hora_actual = date('H:i');

    if (!$fecha_cita || !$hora_inicio || !$hora_fin || !$id_paciente || !$motivo) {
        $_SESSION['mensaje'] = "❌ Todos los campos son obligatorios.";
        $_SESSION['tipo_mensaje'] = "danger";
    } elseif ($fecha_cita === $fecha_hoy && $hora_inicio <= $hora_actual) {
        $_SESSION['mensaje'] = "❌ No puedes agendar una cita con una hora anterior o igual a la actual.";
        $_SESSION['tipo_mensaje'] = "danger";
    } else {
        $resultado = $citasController->CrearCitaC($_POST);
        $_SESSION['mensaje'] = $resultado === true ? "✅ Cita creada correctamente." : "❌ Error al crear la cita: " . htmlspecialchars($resultado);
        $_SESSION['tipo_mensaje'] = $resultado === true ? "success" : "danger";
    }

    header("Location: citas_doctor.php");
    exit();
}

$fecha_min = date('Y-m-d');
$fecha_max = date('Y-m-d', strtotime('+4 months'));


function etiquetaEstado($estado) {
    $clases = [
        "Pendiente" => "label label-warning",
        "Finalizada" => "label label-success",
        "Cancelada" => "label label-danger"
    ];
    $clase = $clases[$estado] ?? "label label-default";
    return "<span class='$clase'>$estado</span>";
}
// Para usar en JS o modales
$medicamentos_disponibles_json = json_encode($medicamentos_disponibles, JSON_UNESCAPED_UNICODE);
$tratamientos_disponibles_json = json_encode($tratamientos_disponibles, JSON_UNESCAPED_UNICODE);
?>



<!-- Botón para agregar nueva cita -->
<div class="box"><div class="box-body"><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNuevaCita"><i class="fa fa-plus"></i> Agregar Nueva Cita</button></div></div>



<!-- Tabla de citas próximas -->
<div class="box box-primary">
  <div class="box-header with-border">
    <h3 class="box-title">Citas Próximas</h3>
  </div>
  <div class="box-body">
    <?php if (empty($citas_proximas)): ?>
      <div class="alert alert-info">No tienes citas próximas.</div>
    <?php else: ?>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Paciente</th>
            <th>Motivo</th>
            <th>Consultorio</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($citas_proximas as $cita): ?>
            <tr>
              <td><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></td>
              <td><?= htmlspecialchars($cita["nombre_paciente"] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($cita["motivo"]) ?></td>
              <td><?= htmlspecialchars($cita["nombre_consultorio"] ?? 'No asignado') ?></td>
              <td><?= etiquetaEstado($cita["estado"]) ?></td>
              <td>
                <?php if ($cita["estado"] === "Pendiente"): ?>
                  <button type="button" class="btn btn-xs btn-success btnFinalizarCita"
                          data-toggle="modal" 
                          data-target="#modalFinalizarCita"
                          data-id-cita="<?= $cita["id"] ?>"
                          data-motivo="<?= htmlspecialchars($cita["motivo"]) ?>">
                    Finalizar
                  </button>
                  <button type="button" class="btn btn-xs btn-danger btnCancelarCita" 
                          data-toggle="modal" 
                          data-target="#modalCancelarCita" 
                          data-id-cita="<?= $cita["id"] ?>">
                    Cancelar
                  </button>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>


<!-- Tabla citas pasadas pendientes (pendientes vencidas) -->
<div class="box box-warning">
  <div class="box-header with-border">
    <h3 class="box-title">Citas Pendientes Vencidas</h3>
  </div>
  <div class="box-body">
    <?php if (empty($citas_pasadas_pendientes)): ?>
      <div class="alert alert-info">No hay citas pendientes vencidas.</div>
    <?php else: ?>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Paciente</th>
            <th>Motivo</th>
            <th>Consultorio</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($citas_pasadas_pendientes as $cita): ?>
            <tr>
              <td><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></td>
              <td><?= htmlspecialchars($cita["nombre_paciente"] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($cita["motivo"]) ?></td>
              <td><?= htmlspecialchars($cita["nombre_consultorio"] ?? 'No asignado') ?></td>
              <td><?= etiquetaEstado($cita["estado"]) ?></td>
              <td>
                <button type="button" class="btn btn-xs btn-success btnFinalizarCita"
                        data-toggle="modal" 
                        data-target="#modalFinalizarCita"
                        data-id-cita="<?= $cita["id"] ?>"
                        data-motivo="<?= htmlspecialchars($cita["motivo"]) ?>">
                  Finalizar
                </button>
                <button type="button" class="btn btn-xs btn-danger btnCancelarCita" 
                        data-toggle="modal" 
                        data-target="#modalCancelarCita" 
                        data-id-cita="<?= $cita["id"] ?>">
                  Cancelar
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>


<!-- Tabla historial de citas finalizadas/canceladas -->
<div class="box box-default">
  <div class="box-header with-border">
    <h3 class="box-title">Historial de Citas</h3>
  </div>
  <div class="box-body">
    <?php if (empty($citas_historial)): ?>
      <div class="alert alert-info">No hay citas finalizadas o canceladas.</div>
    <?php else: ?>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Paciente</th>
            <th>Motivo</th>
            <th>Consultorio</th>
            <th>Observaciones</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($citas_historial as $cita): ?>
            <tr>
              <td><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></td>
              <td><?= htmlspecialchars($cita["nombre_paciente"] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($cita["motivo"]) ?></td>
              <td><?= htmlspecialchars($cita["nombre_consultorio"] ?? 'No asignado') ?></td>
              <td><?= htmlspecialchars($cita["observaciones"] ?? 'Ninguna') ?></td>
              <td><?= etiquetaEstado($cita["estado"]) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<!-- Formulario para finalizar cita seleccionada -->
<?php if ($cita_seleccionada): ?>
  <div class="box box-success">
    <div class="box-header with-border">
      <h3 class="box-title">Finalizar Cita</h3>
    </div>
    <div class="box-body">
      <form method="post">
        <input type="hidden" name="id_cita_finalizar" value="<?= $cita_seleccionada['id'] ?>">
        
        <div class="form-group">
          <label>Motivo:</label>
          <input type="text" name="motivo" class="form-control" value="<?= htmlspecialchars($cita_seleccionada['motivo']) ?>" required>
        </div>
        
        <div class="form-group">
          <label>Observaciones:</label>
          <textarea name="observaciones" class="form-control" rows="4" required><?= htmlspecialchars($cita_seleccionada['observaciones'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label for="peso">Peso (kg) <span style="color:red;">*</span></label>
          <input type="number" step="0.1" min="0" max="500" name="peso" id="peso" class="form-control" required 
                 value="<?= htmlspecialchars($cita_seleccionada['peso'] ?? '') ?>">
        </div>

        <button type="submit" name="guardar_finalizacion" class="btn btn-success">
          <i class="fa fa-check"></i> Guardar y Finalizar
        </button>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- Modal cancelar cita -->
<div class="modal fade" id="modalCancelarCita" tabindex="-1" role="dialog" aria-labelledby="modalCancelarCitaLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="post" id="formCancelarCita">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="modalCancelarCitaLabel">Cancelar Cita</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_cita" id="modalCancelarCitaId">
          <div class="form-group">
            <label for="motivo_cancelacion">Motivo de cancelación</label>
            <textarea name="motivo_cancelacion" id="motivo_cancelacion" class="form-control" rows="3" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" name="cancelar_cita" class="btn btn-danger">Cancelar Cita</button>
        </div>
      </div>
    </form>
  </div>
</div>



<!-- Modal para crear nueva cita (CON IDs ÚNICOS Y CORRECTOS) -->
<div class="modal fade" id="modalNuevaCita" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="post">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title">Nueva Cita</h5></div>
        <div class="modal-body">
          <input type="hidden" name="id_doctor" value="<?= htmlspecialchars($id_doctor) ?>">
          <input type="hidden" name="id_consultorio" value="<?= htmlspecialchars($consultorio_id) ?>">
          <div class="form-group"><label>Consultorio</label><input type="text" class="form-control" value="<?= htmlspecialchars($consultorio_nombre ?? 'No asignado') ?>" readonly></div>
          <div class="form-group"><label for="fecha_crear">Fecha</label><input type="date" id="fecha_crear" name="fecha" class="form-control" required min="<?= date('Y-m-d') ?>"></div>
          <div class="form-group"><label>Horario Disponible</label><input type="text" id="horario_doctor_referencia" class="form-control" readonly></div>
          <div class="form-group"><label for="hora_inicio_crear">Hora Inicio</label><input type="time" id="hora_inicio_crear" name="hora_inicio" class="form-control" required disabled></div>
          <div class="form-group"><label for="hora_fin_crear">Hora Fin</label><input type="time" id="hora_fin_crear" name="hora_fin" class="form-control" required disabled></div>
          <div class="form-group"><label for="motivo_crear">Motivo</label><input type="text" id="motivo_crear" name="motivo" class="form-control" required></div>
          <div class="form-group"><label for="id_paciente_crear">Paciente</label><select id="id_paciente_crear" name="id_paciente" class="form-control" required><option value="">-- Seleccione un paciente --</option><?php foreach ($pacientes as $paciente): ?><option value="<?= htmlspecialchars($paciente['id']) ?>"><?= htmlspecialchars($paciente['nombre']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" name="crear_cita" class="btn btn-primary"><i class="fa fa-save"></i> Guardar Cita</button></div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Finalizar Cita (CON IDs ÚNICOS Y CORRECTOS) -->
<div class="modal fade" id="modalFinalizarCita" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <form method="post" onsubmit="return validarFinalizacion();">
      <div class="modal-content">
        <div class="modal-header bg-green"><h4 class="modal-title">Finalizar Cita</h4></div>
        <div class="modal-body">
          <input type="hidden" name="id_cita_finalizar" id="id_cita_finalizar">
          <div class="form-group"><label for="motivo_finalizar">Motivo</label><input type="text" class="form-control" name="motivo" id="motivo_finalizar" required></div>
          <div class="form-group"><label for="observaciones_finalizar">Observaciones / Diagnóstico</label><textarea class="form-control" name="observaciones" id="observaciones_finalizar" rows="3" required></textarea></div>
          <div class="form-group"><label for="peso_finalizar">Peso del paciente (kg)</label><input type="number" step="0.1" class="form-control" name="peso" id="peso_finalizar" min="1" max="500" required></div>
          <div class="form-group"><label for="medicamentos">Medicamentos Recetados</label><select class="form-control" name="medicamentos[]" id="medicamentos" multiple style="width: 100%;"><?php foreach ($medicamentos_disponibles as $med): ?><option value="<?= $med['id'] ?>"><?= htmlspecialchars($med['nombre']) ?> <?= $med['es_cronico'] ? "(Crónico)" : "" ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label for="tratamientos">Tratamientos Aplicados</label><select class="form-control" name="tratamientos[]" id="tratamientos" multiple style="width: 100%;"><?php foreach ($tratamientos_disponibles as $trat): ?><option value="<?= $trat['id'] ?>"><?= htmlspecialchars($trat['nombre']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="modal-footer"><button type="submit" name="guardar_finalizacion" class="btn btn-success">Guardar Finalización</button><button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button></div>
      </div>
    </form>
  </div>
</div>