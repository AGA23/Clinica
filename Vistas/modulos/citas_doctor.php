<?php
// En Vistas/modulos/citas_doctor.php

// ==============================================================================
// 1. VERIFICACIÓN DE SEGURIDAD Y CONFIGURACIÓN
// ==============================================================================
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Doctor') {
    echo "<section class='content'><div class='alert alert-danger'>Acceso no autorizado.</div></section>";
    return;
}

$id_doctor = $_SESSION['id'] ?? null;
if (!$id_doctor) die("Error crítico: ID del doctor no definido en la sesión.");
$redirect_url = BASE_URL . "citas_doctor";

// ==============================================================================
// 2. PROCESAMIENTO DE FORMULARIOS (POST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $citasController = new CitasC();

    if (isset($_POST['guardar_finalizacion'])) {
        $_POST['idCita'] = $_POST['id_cita_finalizar'];
        $resultado = $citasController->FinalizarCitaC();
        $_SESSION['mensaje_citas_doctor'] = $resultado['success'] ?? $resultado['error'];
        $_SESSION['tipo_mensaje_citas_doctor'] = isset($resultado['success']) ? "success" : "danger";
    
    } elseif (isset($_POST['cancelar_cita'])) {
        $id_cita = filter_input(INPUT_POST, 'id_cita', FILTER_VALIDATE_INT);
        $motivo = trim($_POST['motivo_cancelacion'] ?? '');
        $resultado = $citasController->CancelarCitaC($id_cita, $motivo);
        $_SESSION['mensaje_citas_doctor'] = $resultado['success'] ?? $resultado['error'];
        $_SESSION['tipo_mensaje_citas_doctor'] = isset($resultado['success']) ? "success" : "danger";
    
    } elseif (isset($_POST['crear_cita_doctor'])) {
        $resultado = $citasController->CrearCitaC();
        $_SESSION['mensaje_citas_doctor'] = ($resultado === true) ? "¡Cita creada correctamente!" : "Error al crear la cita: " . $resultado;
        $_SESSION['tipo_mensaje_citas_doctor'] = ($resultado === true) ? "success" : "danger";
    
    } elseif (isset($_POST['editar_cita_doctor'])) {
        $_POST['editar_cita_secretario'] = true; 
        (new CitasC())->ActualizarCitaC();
    }

    echo '<script>window.location.href = "' . $redirect_url . '";</script>';
 
    exit();
}

// ==============================================================================
// 3. CARGA DE DATOS PARA LA VISTA
// ==============================================================================
$citasController = new CitasC();
$citas = $citasController->VerCitasDoctorC($id_doctor);

$pacientes_agenda = (new PacientesC())->ListarPacientesC();

// Carga de Medicamentos
$presentaciones_aprobadas = MedicamentosM::ObtenerTodasPresentacionesAprobadasM();
$presentaciones_agrupadas = [];
foreach ($presentaciones_aprobadas as $pres) {
    $presentaciones_agrupadas[$pres['nombre_generico']][] = $pres;
}

// Datos del Consultorio y Tratamientos
$consultorio_id = DoctoresM::ObtenerConsultorioDeDoctor($id_doctor);
$consultorio_nombre = $consultorio_id ? (ConsultoriosM::VerConsultorioPorId($consultorio_id)['nombre'] ?? 'No asignado') : 'No asignado';
$tratamientos_disponibles = $consultorio_id ? TratamientosM::ObtenerTratamientosPorConsultorioM($consultorio_id) : [];

// Carga de Obras Sociales
$lista_obras_sociales = [];
if (class_exists('ObrasSocialesC')) {
    $lista_obras_sociales = ObrasSocialesC::ObtenerTodasC();
} elseif (class_exists('obrasocialesC')) {
    $lista_obras_sociales = obrasocialesC::ObtenerTodasC();
}

// Carga de Plantillas (PDF)
$plantillasController = new PlantillasC();
$plantillasCertificados = $plantillasController->MostrarPlantillasC('certificado');
$plantillasRecetas = $plantillasController->MostrarPlantillasC('receta');

// ==============================================================================
// 4. CLASIFICACIÓN DE CITAS
// ==============================================================================
$citas_proximas = [];
$citas_pasadas_pendientes = [];
$citas_historial = [];
$ahora = new DateTime();

foreach ($citas as $cita) {
    $fecha_cita = new DateTime($cita['inicio']);
    if ($fecha_cita >= $ahora && $cita['estado'] === 'Pendiente') {
        $citas_proximas[] = $cita;
    } elseif ($fecha_cita < $ahora && $cita['estado'] === 'Pendiente') {
        $citas_pasadas_pendientes[] = $cita;
    } else {
        $citas_historial[] = $cita;
    }
}

function etiquetaEstado($estado) {
    $clases = ["Pendiente" => "label-warning", "Completada" => "label-success", "Cancelada" => "label-danger"];
    $returnEstado = isset($clases[$estado]) ? $clases[$estado] : "label-default";
    return "<span class='label $returnEstado'>$estado</span>";
}
?>

<section class="content-header">
    <h1>Mi Agenda de Citas</h1>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Mi Agenda</li></ol>
</section>

<section class="content">
    
    <?php if (isset($_SESSION['mensaje_citas_doctor'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['tipo_mensaje_citas_doctor']) ?> alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>     
        <?= htmlspecialchars($_SESSION['mensaje_citas_doctor']) ?>
    </div>
    <?php unset($_SESSION['mensaje_citas_doctor'], $_SESSION['tipo_mensaje_citas_doctor']); endif; ?>

    <div class="box">
        <div class="box-body">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNuevaCita"><i class="fa fa-plus"></i> Agregar Nueva Cita</button>
        </div>
    </div>
    
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Citas Próximas</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Fecha</th><th>Paciente</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php if (empty($citas_proximas)): ?>
                        <tr><td colspan="5" class="text-center">No tienes citas próximas.</td></tr>
                    <?php else: foreach ($citas_proximas as $cita): ?>
                    <tr>
                        <td><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></td>
                        <td><?= htmlspecialchars($cita["nyaP"] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($cita["motivo"]) ?></td>
                        <td><?= etiquetaEstado($cita["estado"]) ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-xs btn-info btnEditarCitaDoctor" data-toggle="modal" data-target="#modalEditarCitaDoctor" data-id-cita="<?= $cita["id"] ?>">Editar</button>
                                <button type="button" class="btn btn-xs btn-success btnFinalizarCita" data-toggle="modal" data-target="#modalFinalizarCita" data-id-cita="<?= $cita["id"] ?>" data-motivo="<?= htmlspecialchars($cita["motivo"]) ?>">Finalizar</button>
                                <button type="button" class="btn btn-xs btn-danger btnCancelarCita" data-toggle="modal" data-target="#modalCancelarCita" data-id-cita="<?= $cita["id"] ?>">Cancelar</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Citas Pendientes Vencidas</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                 <thead><tr><th>Fecha</th><th>Paciente</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr></thead>
                 <tbody>
                    <?php if (empty($citas_pasadas_pendientes)): ?>
                        <tr><td colspan="5" class="text-center">No hay citas pendientes vencidas.</td></tr>
                    <?php else: foreach ($citas_pasadas_pendientes as $cita): ?>
                    <tr>
                        <td><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></td>
                        <td><?= htmlspecialchars($cita["nyaP"] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($cita["motivo"]) ?></td>
                        <td><?= etiquetaEstado($cita["estado"]) ?></td>
                        <td><button type="button" class="btn btn-xs btn-danger btnCancelarCita" data-toggle="modal" data-target="#modalCancelarCita" data-id-cita="<?= $cita["id"] ?>">Cancelar</button></td>
                    </tr>
                    <?php endforeach; endif; ?>
                 </tbody>
            </table>
        </div>
    </div>

    <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Historial de Citas</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped dt-responsive" width="100%">
                <thead><tr><th>Fecha</th><th>Paciente</th><th>Motivo/Cancelación</th><th>Diagnóstico</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php if (empty($citas_historial)): ?>
                        <tr><td colspan="5" class="text-center">No hay citas en el historial.</td></tr>
                    <?php else: foreach ($citas_historial as $cita): ?>
                    <tr>
                        <td><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></td>
                        <td><?= htmlspecialchars($cita["nyaP"] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($cita["motivo"] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($cita["observaciones"] ?? 'N/A') ?></td>
                        <td><?= etiquetaEstado($cita["estado"]) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalCancelarCita"><div class="modal-dialog" role="document"><form method="post" action="<?= $redirect_url ?>"><div class="modal-content"><div class="modal-header bg-danger"><h5 class="modal-title">Cancelar Cita</h5></div><div class="modal-body"><input type="hidden" name="id_cita" id="modalCancelarCitaId"><div class="form-group"><label>Motivo</label><textarea name="motivo_cancelacion" class="form-control" rows="3" required></textarea></div></div><div class="modal-footer"><button type="submit" name="cancelar_cita" class="btn btn-danger">Confirmar</button></div></div></form></div></div>

    <div class="modal fade" id="modalNuevaCita">
        <div class="modal-dialog" role="document">
            <form method="post" action="<?= $redirect_url ?>">
                <div class="modal-content">
                    <div class="modal-header bg-primary"><h5 class="modal-title">Nueva Cita</h5></div>
                    <div class="modal-body">
                        <input type="hidden" name="id_doctor" value="<?= htmlspecialchars($id_doctor) ?>">
                        <input type="hidden" name="id_consultorio" value="<?= htmlspecialchars($consultorio_id) ?>">
                        
                        <div class="form-group"><label>Mi Consultorio</label><input type="text" class="form-control" value="<?= htmlspecialchars($consultorio_nombre) ?>" readonly></div>
                        
                        <div class="form-group">
                            <label for="id_paciente_crear">Paciente</label>
                            <select id="id_paciente_crear" name="id_paciente" class="form-control select2" style="width: 100%;" required>
                                <option value="">-- Seleccione un paciente --</option>
                                <?php foreach ($pacientes_agenda as $paciente): ?>
                                    <option value="<?= htmlspecialchars($paciente['id']) ?>">
                                        <?= htmlspecialchars($paciente['apellido'] . ' ' . $paciente['nombre']) ?> - DNI: <?= htmlspecialchars($paciente['numero_documento']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group"><label for="id_tratamiento_crear">Tratamiento (Motivo)</label><select id="id_tratamiento_crear" name="id_tratamiento" class="form-control select-tratamiento" required><option value="">-- Seleccione un tratamiento --</option><?php foreach ($tratamientos_disponibles as $trat): ?><option value="<?= $trat['id'] ?>"><?= htmlspecialchars($trat['nombre']) ?></option><?php endforeach; ?></select></div>
                        
                        <div class="row">
                            <div class="col-md-12"> 
                                <div class="form-group">
                                    <label>Cobertura / Medio de Pago:</label>
                                    <select name="id_tipo_pago" id="id_tipo_pago_crear" class="form-control select-tipo-pago" required disabled>
                                        <option value="">Seleccione un paciente primero...</option>
                                    </select>
                                    <input type="hidden" name="id_cobertura_aplicada" class="input-cobertura-aplicada">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label for="fecha_crear">Fecha</label><input type="date" id="fecha_crear" name="fecha" class="form-control input-fecha" required min="<?= date('Y-m-d') ?>"></div></div>
                            <div class="col-md-6"><div class="form-group"><label for="hora_inicio_crear">Horarios Disponibles</label><select id="hora_inicio_crear" name="hora_inicio" class="form-control select-hora-inicio" required disabled><option value="">-- Seleccione una fecha --</option></select></div></div>
                        </div>
                        <input type="hidden" name="hora_fin" id="hora_fin_crear">
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button><button type="submit" name="crear_cita_doctor" class="btn btn-primary">Guardar Cita</button></div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalEditarCitaDoctor">
        <div class="modal-dialog" role="document">
            <form method="post" action="<?= $redirect_url ?>">
                <div class="modal-content">
                    <div class="modal-header bg-info"><h5 class="modal-title">Reprogramar Cita</h5></div>
                    <div class="modal-body">
                        <input type="hidden" name="id_cita_editar" id="id_cita_editar_doctor">
                        <input type="hidden" name="id_doctor_editar" value="<?= htmlspecialchars($id_doctor) ?>">
                        <input type="hidden" name="id_consultorio" id="id_consultorio_editar_doctor">
                        
                        <div class="form-group"><label>Paciente</label><input type="text" id="paciente_editar_doctor" class="form-control" readonly><input type="hidden" name="id_paciente_editar" id="id_paciente_editar_doctor" class="select-paciente-editar-cita"></div>
                        
                        <div class="row">
                            <div class="col-md-12"> 
                                <div class="form-group">
                                    <label>Tipo de Cobertura / Pago:</label>
                                    <select name="id_tipo_pago_editar" id="id_tipo_pago_editar_doctor" class="form-control select-tipo-pago" required>
                                        <option value="">Cargando datos del paciente...</option>
                                    </select>
                                    <input type="hidden" name="id_cobertura_aplicada_editar" id="id_cobertura_aplicada_editar_doctor" class="input-cobertura-aplicada">
                                </div>
                            </div>
                        </div>

                        <div class="form-group"><label>Tratamiento (Motivo)</label><select name="id_tratamiento_editar" id="id_tratamiento_editar_doctor" class="form-control select-tratamiento" required><?php foreach ($tratamientos_disponibles as $trat): ?><option value="<?= $trat['id'] ?>"><?= htmlspecialchars($trat['nombre']) ?></option><?php endforeach; ?></select></div>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label for="fecha_editar_doctor">Nueva Fecha</label><input type="date" id="fecha_editar_doctor" name="fecha_editar" class="form-control input-fecha" required min="<?= date('Y-m-d') ?>"></div></div>
                            <div class="col-md-6"><div class="form-group"><label for="hora_inicio_editar_doctor">Nuevos Horarios</label><select id="hora_inicio_editar_doctor" name="hora_inicio_editar" class="form-control select-hora-inicio" required disabled><option value="">-- Seleccione fecha --</option></select></div></div>
                        </div>
                        <input type="hidden" name="hora_fin_editar" id="hora_fin_editar_doctor">
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button><button type="submit" name="editar_cita_doctor" class="btn btn-info">Guardar Cambios</button></div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalFinalizarCita">
        <div class="modal-dialog modal-lg" role="document">
            <form method="post" action="<?= $redirect_url ?>">
                <div class="modal-content">
                    <div class="modal-header bg-green"><h4 class="modal-title">Finalizar Cita</h4></div>
                    <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                        <input type="hidden" name="id_cita_finalizar" id="id_cita_finalizar">
                        
                        <div class="alert alert-info" style="padding: 10px; margin-bottom: 15px;">
                            <strong>Facturación:</strong> Verifique la cobertura antes de cerrar la cita.
                            <div class="row" style="margin-top: 10px;">
                                <div class="col-md-6">
                                    <select name="id_tipo_pago_finalizar" id="id_tipo_pago_finalizar" class="form-control select-tipo-pago" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach($lista_obras_sociales as $os): ?>
                                            <option value="<?= $os['id'] ?>"><?= htmlspecialchars($os['nombre']) ?> (<?= strtoupper($os['tipo']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="hidden" name="id_cobertura_aplicada_finalizar" id="id_cobertura_aplicada_finalizar">
                                    <input type="hidden" id="id_paciente_finalizar" class="select-paciente-finalizar"> 
                                </div>
                            </div>
                        </div>

                        <div class="form-group"><label for="observaciones_finalizar">Diagnóstico / Observaciones Clínicas</label><textarea class="form-control" name="observaciones" id="observaciones_finalizar" rows="3" required></textarea></div>
                        <div class="row"><div class="col-md-6"><div class="form-group"><label for="peso_finalizar">Peso (kg)</label><input type="number" step="0.1" class="form-control" name="peso" id="peso_finalizar"></div></div><div class="col-md-6"><div class="form-group"><label for="presion_arterial_finalizar">Presión Arterial (ej. 120/80)</label><input type="text" class="form-control" name="presion_arterial" id="presion_arterial_finalizar" placeholder="Sistólica/Diastólica"></div></div></div>
                        <hr><h5 style="font-weight: bold;">Receta Médica Estructurada</h5>
                        <div class="row"><div class="col-md-9"><div class="form-group"><label>Añadir desde Inventario</label><select class="form-control" id="select-medicamento-inventario"><option value="">Seleccione un fármaco...</option><?php foreach ($presentaciones_agrupadas as $farmaco_generico => $presentaciones): ?><optgroup label="<?= htmlspecialchars($farmaco_generico) ?>"><?php foreach ($presentaciones as $pres): ?><option value="<?= $pres['id'] ?>" data-nombre="<?= htmlspecialchars($farmaco_generico . ' - ' . $pres['presentacion']) ?>"><?= htmlspecialchars($pres['presentacion']) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select></div></div><div class="col-md-3"><label>&nbsp;</label><button type="button" class="btn btn-success btn-block" id="btn-anadir-de-inventario">Añadir</button></div></div>
                        <details><summary style="cursor: pointer; color: #3c8dbc;"><i class="fa fa-plus-circle"></i> O añadir medicamento manualmente</summary><div class="well well-sm" style="margin-top: 10px;"><div class="row"><div class="col-sm-6"><div class="form-group"><label>Fármaco Genérico</label><input type="text" id="manual-farmaco" class="form-control" placeholder="Ej: Ibuprofeno"></div></div><div class="col-sm-6"><div class="form-group"><label>Presentación</label><input type="text" id="manual-presentacion" class="form-control" placeholder="Ej: 800mg Comprimidos"></div></div></div><div class="row"><div class="col-sm-6"><div class="form-group"><label>Dosis</label><input type="text" id="manual-dosis" class="form-control" placeholder="Ej: 1 comprimido"></div></div><div class="col-sm-6"><div class="form-group"><label>Frecuencia / Duración</label><input type="text" id="manual-frecuencia" class="form-control" placeholder="Ej: cada 8 horas por 7 días"></div></div></div><button type="button" class="btn btn-primary btn-sm pull-right" id="btn-anadir-manual">Añadir a Receta</button><div class="clearfix"></div></div></details>
                        <hr><h6>Receta Actual</h6><table class="table table-bordered"><thead><tr><th>Medicamento</th><th>Dosis</th><th>Frecuencia</th><th>Instrucciones</th><th style="width: 40px;"></th></tr></thead><tbody id="tbody-receta-actual"></tbody></table><input type="hidden" name="receta_json" id="receta_json_hidden"><div class="form-group"><label for="indicaciones_receta_finalizar">Indicaciones Adicionales (para PDF)</label><textarea class="form-control" name="indicaciones_receta" id="indicaciones_receta_finalizar" rows="2" placeholder="Ej: Beber abundante líquido..."></textarea></div>
                        <hr><h5 style="font-weight: bold;">Tratamientos Aplicados en Consulta</h5><div class="form-group"><select class="form-control" name="tratamientos[]" multiple style="width: 100%;"><?php foreach ($tratamientos_disponibles as $trat): ?><option value="<?= $trat['id'] ?>"><?= htmlspecialchars($trat['nombre']) ?></option><?php endforeach; ?></select></div>
                        <hr><h5 style="font-weight: bold;">Documentos Adicionales (PDF)</h5>
                        <div class="form-group"><label class="checkbox-inline"><input type="checkbox" id="generar_certificado_check" value="1"> Generar Certificado</label><label class="checkbox-inline" style="margin-left: 20px;"><input type="checkbox" id="generar_receta_check" value="1"> Generar Receta (Formato Libre)</label></div>
                        <div id="contenedor_certificado" class="documento-opcional" style="display:none; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-top: 15px; background-color: #f9f9f9;"><div class="form-group"><label>Elegir Plantilla de Certificado:</label><select class="form-control" id="select_plantilla_certificado"><option value="">Seleccionar plantilla...</option><?php foreach ($plantillasCertificados as $plantilla): ?><option value="<?= $plantilla['id'] ?>"><?= htmlspecialchars($plantilla['titulo']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="certificado_texto_final">Contenido del Certificado:</label><textarea class="form-control" name="certificado_texto_final" id="certificado_texto_final" rows="10"></textarea><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#modalDiasReposo" style="margin-top: 10px;"><i class="fa fa-calendar-plus-o"></i> Asistente de Reposo Laboral</button></div></div>
                        <div id="contenedor_receta_pdf" class="documento-opcional" style="display:none; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-top: 15px; background-color: #f9f9f9;"><div class="form-group"><label for="select_plantilla_receta">Elegir Plantilla de Receta:</label><select class="form-control" id="select_plantilla_receta"><option value="">Seleccionar plantilla...</option><?php foreach ($plantillasRecetas as $plantilla): ?><option value="<?= $plantilla['id'] ?>"><?= htmlspecialchars($plantilla['titulo']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="receta_texto_final">Contenido de la Receta:</label><textarea class="form-control" name="receta_texto_final" id="receta_texto_final" rows="10"></textarea></div></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button><button type="submit" name="guardar_finalizacion" class="btn btn-success">Guardar Finalización</button></div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalDiasReposo"><div class="modal-dialog modal-sm"><div class="modal-content"><div class="modal-header"><h4 class="modal-title">Configurar Reposo Laboral</h4></div><div class="modal-body"><div class="form-group"><label for="dias_reposo_input">Indicar cantidad de días de reposo:</label><input type="number" id="dias_reposo_input" class="form-control" min="1" value="1"></div><p class="text-muted">El sistema calculará las fechas automáticamente.</p></div><div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btn-aplicar-reposo">Aplicar al Certificado</button></div></div></div></div>
</section>

<?php ob_start(); ?>
<script>

// 1. ESPERAMOS A QUE EL NAVEGADOR CARGUE EL HTML
document.addEventListener("DOMContentLoaded", function() {

    // 2. SISTEMA DE ESPERA: Revisamos cada 50ms si jQuery ya llegó
    var esperaJQuery = setInterval(function() {
        if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.dataTable !== 'undefined') {
            // ¡Ya llegó! Detenemos la espera y arrancamos
            clearInterval(esperaJQuery);
            iniciarScriptDoctores(window.jQuery);
        }
    }, 50);

    // 3. AQUÍ VA TU CÓDIGO (Encapsulado para seguridad)
    function iniciarScriptDoctores($) {
        console.log("✅ jQuery detectado. Iniciando script de Doctor...");

        // =========================================================================
        // CONFIGURACIÓN E INICIALIZACIÓN
        // =========================================================================
        
        // DataTables
        $('.dt-responsive').DataTable({
            "language": { "url": "<?= BASE_URL ?>Vistas/plugins/datatables/Spanish.json" }
        });

        // Función segura para leer respuestas JSON
        function safeParse(r) {
            if (typeof r === 'string') {
                try { return JSON.parse(r); } catch (e) { return {}; }
            }
            return r;
        }

        // Inicializar Select2 en los modales al abrirse
        // (Esto evita el error de que el buscador salga aplastado)
        $('.modal').on('shown.bs.modal', function() {
            $(this).find('.select2').select2({
                dropdownParent: $(this),
                width: '100%',
                placeholder: "Buscar...",
                allowClear: true,
                language: "es"
            });
        });

        // =========================================================================
        // 🆕 LÓGICA: NUEVA CITA
        // =========================================================================
        
        // A. DETECTOR DE SELECCIÓN DE PACIENTE
        // Usamos delegación $('body').on(...) para asegurar que funcione siempre
        $('body').on('select2:select', '#id_paciente_crear', function(e) {
            var idPaciente = e.params.data.id;
            console.log("🆕 Nueva Cita - Paciente Seleccionado:", idPaciente);
            
            // Llamamos a cargar coberturas pasando el modal de Nueva Cita
            cargarCoberturas(idPaciente, $('#modalNuevaCita'), null, null);
        });

        // B. DETECTOR DE CAMBIO DE FECHA
        $('#fecha_crear').on('change', function() {
            var fecha = $(this).val();
            // Buscamos el ID del doctor en el input hidden de este formulario
            var idDoctor = $(this).closest('form').find('input[name="id_doctor"]').val();
            console.log("📅 Nueva Cita - Fecha:", fecha);
            cargarHorarios(idDoctor, fecha, $('#hora_inicio_crear'));
        });

        // C. DETECTOR DE CAMBIO DE HORA
        $('#hora_inicio_crear').on('change', function() {
            calcularFin($(this).val(), $('#hora_fin_crear'));
        });

        // =========================================================================
        // ✏️ LÓGICA: EDITAR CITA
        // =========================================================================
        
        $('body').on('click', '.btnEditarCitaDoctor', function() {
            var idCita = $(this).data('id-cita');
            
            $.post('<?= BASE_URL ?>ajax/citasA.php', { action: 'obtenerDetallesCita', id_cita: idCita })
            .done(function(r) {
                r = safeParse(r);
                if (r.success) {
                    var d = r.datos;
                    var modal = $('#modalEditarCitaDoctor');

                    // Llenar campos simples
                    modal.find('#id_cita_editar_doctor').val(d.id);
                    modal.find('#id_consultorio_editar_doctor').val(d.id_consultorio);
                    modal.find('#id_paciente_editar_doctor').val(d.id_paciente);
                    modal.find('#paciente_editar_doctor').val(d.nombre_completo_paciente);
                    modal.find('#id_tratamiento_editar_doctor').val(d.id_tratamiento);

                    // Cargar Coberturas
                    cargarCoberturas(d.id_paciente, modal, d.id_tipo_pago, d.id_cobertura_aplicada);

                    // Cargar Fechas y Horarios
                    var fechaFmt = moment(d.inicio).format('YYYY-MM-DD');
                    var horaFmt = moment(d.inicio).format('HH:mm');
                    
                    modal.find('#fecha_editar_doctor').val(fechaFmt);
                    
                    // Cargar horarios en el select de editar
                    cargarHorarios(d.id_doctor, fechaFmt, modal.find('#hora_inicio_editar_doctor'), horaFmt);
                }
            });
        });

        // Listeners específicos del modal Editar
        $('#fecha_editar_doctor').on('change', function() {
            var fecha = $(this).val();
            var idDoctor = $('#modalEditarCitaDoctor input[name="id_doctor_editar"]').val();
            cargarHorarios(idDoctor, fecha, $('#hora_inicio_editar_doctor'));
        });

        $('#hora_inicio_editar_doctor').on('change', function() {
            calcularFin($(this).val(), $('#hora_fin_editar_doctor'));
        });

        // =========================================================================
        // ⚙️ FUNCIONES AJAX COMPARTIDAS
        // =========================================================================

        function cargarCoberturas(idPaciente, modal, prePago, preCob) {
            var select = modal.find('.select-tipo-pago');
            var hidden = modal.find('.input-cobertura-aplicada');

            // Feedback visual: Bloquear mientras carga
            select.html('<option>Cargando...</option>').prop('disabled', true);
            hidden.val('');

            $.post('<?= BASE_URL ?>ajax/citasA.php', { action: 'obtenerAfiliacionesPaciente', id_paciente: idPaciente })
            .done(function(r) {
                r = safeParse(r);
                
                select.empty().append('<option value="">-- Seleccione --</option>');
                select.append('<option value="1" data-cobertura="">PARTICULAR (Pago Directo)</option>');

                if(r.success && r.afiliaciones) {
                    r.afiliaciones.forEach(function(a) {
                        var txt = a.nombre_os + " [Plan: " + (a.plan || 'S/D') + "]";
                        select.append('<option value="' + a.id_obra_social + '" data-cobertura="' + a.id + '">' + txt + '</option>');
                    });
                }
                
                // ¡DESBLOQUEAR!
                select.prop('disabled', false);

                // Si estamos editando, recuperar valores
                if(prePago) {
                    select.val(prePago).trigger('change'); // trigger('change') actualiza Select2 si es visual
                    hidden.val(preCob || '');
                }
            });
        }

        function cargarHorarios(idDoctor, fecha, selectObj, horaPre) {
            selectObj.html('<option>Cargando...</option>').prop('disabled', true);
            
            $.post('<?= BASE_URL ?>ajax/citasA.php', { action: 'obtenerHorariosDisponibles', id_doctor: idDoctor, fecha: fecha })
            .done(function(r) {
                r = safeParse(r);
                selectObj.empty().append('<option value="">Seleccione Hora...</option>');
                
                if (r.success && r.horarios) {
                    r.horarios.forEach(function(h) {
                        selectObj.append('<option value="' + h + '">' + h + '</option>');
                    });
                    
                    // Si la hora actual ya no está disponible (ej: es del pasado), la agregamos visualmente para no perderla al editar
                    if(horaPre && selectObj.find("option[value='"+horaPre+"']").length === 0) {
                        selectObj.append('<option value="'+horaPre+'">'+horaPre+' (Actual)</option>');
                    }
                    
                    selectObj.prop('disabled', false);
                    if(horaPre) selectObj.val(horaPre);
                } else {
                    selectObj.append('<option>Sin cupos</option>');
                }
            });
        }

        function calcularFin(horaInicio, inputFinObj) {
            if(!horaInicio) return;
            var d = new Date("2000-01-01 " + horaInicio);
            d.setMinutes(d.getMinutes() + 30);
            var hFin = d.toTimeString().substring(0,5);
            inputFinObj.val(hFin);
        }

        // Sincronizar el input hidden cuando el usuario cambia el select manualmente
        $(document).on('change', '.select-tipo-pago', function() {
            var idCob = $(this).find(':selected').data('cobertura');
            $(this).closest('.modal').find('.input-cobertura-aplicada').val(idCob || '');
        });

        // Resetear modal al cerrar
        $('#modalNuevaCita').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            // Limpiar Select2 visualmente
            $(this).find('.select2').val(null).trigger('change');
            $(this).find('.select-tipo-pago').html('<option>Seleccione paciente...</option>').prop('disabled', true);
        });

        // -------------------------------------------------------------------------
        // 💊 LÓGICA DE RECETAS (MANTENIDA)
        // -------------------------------------------------------------------------
        var recetaActual = [];
        function renderizarReceta() {
            var tbody = $('#tbody-receta-actual').empty();
            if (recetaActual.length === 0) { tbody.append('<tr><td colspan="5" class="text-center text-muted">Sin medicamentos.</td></tr>'); }
            else {
                recetaActual.forEach(function(item, index) {
                    var fila = '<tr><td>'+item.nombre_completo+'</td><td><input class="form-control input-sm receta-dosis" data-index="'+index+'" value="'+(item.dosis||'')+'"></td><td><input class="form-control input-sm receta-frecuencia" data-index="'+index+'" value="'+(item.frecuencia||'')+'"></td><td><input class="form-control input-sm receta-instrucciones" data-index="'+index+'" value="'+(item.instrucciones||'')+'"></td><td><button type="button" class="btn btn-danger btn-xs btn-quitar-medicamento" data-index="'+index+'"><i class="fa fa-trash"></i></button></td></tr>';
                    tbody.append(fila);
                });
            }
            $('#receta_json_hidden').val(JSON.stringify(recetaActual));
        }
        $('#btn-anadir-de-inventario').on('click', function() {
            var sel = $('#select-medicamento-inventario');
            if(!sel.val()) return;
            recetaActual.push({id_presentacion: sel.val(), nombre_completo: sel.find(':selected').data('nombre'), dosis:'', frecuencia:'', instrucciones:''});
            renderizarReceta(); sel.val('');
        });
        $('#tbody-receta-actual').on('click', '.btn-quitar-medicamento', function() { recetaActual.splice($(this).data('index'), 1); renderizarReceta(); });
        
        // Finalizar y Cancelar
        $('#modalFinalizarCita').on('show.bs.modal', function() { $(this).find('form')[0].reset(); recetaActual = []; renderizarReceta(); });
        $('.btnFinalizarCita').on('click', function(){ $('#id_cita_finalizar').val($(this).data('id-cita')); });
        $('.btnCancelarCita').on('click', function(){ $('#modalCancelarCitaId').val($(this).data('id-cita')); });
        
        // Plantillas PDF
        $('#generar_certificado_check').change(function() { $('#contenedor_certificado').toggle(this.checked); });
        $('#generar_receta_check').change(function() { $('#contenedor_receta_pdf').toggle(this.checked); });
        function cargarPlantilla(selectId, textareaId) {
            $(selectId).change(function(){
                var id = $(this).val();
                if(id) $.post('<?= BASE_URL ?>index.php?url=ajax/plantillas', {action:'obtenerContenido', id_plantilla:id}, function(r){ r=safeParse(r); $(textareaId).val(r.contenido||''); });
            });
        }
        cargarPlantilla('#select_plantilla_certificado', '#certificado_texto_final');
        cargarPlantilla('#select_plantilla_receta', '#receta_texto_final');
        
        // Asistente Reposo
        $('#btn-aplicar-reposo').on('click', function() {
            var dias = parseInt($('#dias_reposo_input').val());
            if (isNaN(dias) || dias < 1) return;
            var textarea = $('#certificado_texto_final');
            var contenido = textarea.val(); 
            var fi = new Date(); var ff = new Date(); ff.setDate(fi.getDate() + (dias - 1));
            var fmt = d => d.toLocaleDateString('es-AR');
            textarea.val(contenido.replace(/{DIAS_REPOSO}/g, dias).replace(/{FECHA_INICIO_REPOSO}/g, fmt(fi)).replace(/{FECHA_FIN_REPOSO}/g, fmt(ff)));
            $('#modalDiasReposo').modal('hide');
        });
    }
});
</script>
<?php $scriptDinamico = ob_get_clean(); 
echo $scriptDinamico;
?>