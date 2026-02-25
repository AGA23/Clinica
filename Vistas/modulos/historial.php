<?php
// 1. VERIFICACIÓN DE SEGURIDAD Y PERMISOS
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "Paciente") {
    echo "<section class='content'><div class='alert alert-danger'>Acceso no autorizado.</div></section>";
    return;
}

// 2. OBTENCIÓN DE DATOS Y PREPARACIÓN
$id_paciente = $_SESSION["id"];
$citasController = new CitasC();
// Se asume que este método ahora trae todos los datos detallados por cita
$todas_las_citas = $citasController->VerCitasPacienteC($id_paciente);

// --- [RESTAURADO] 3. LÓGICA DE FILTRADO EN PHP ---
// Se recogen los valores de los filtros del formulario GET
$filtros = [
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    'id_doctor' => filter_input(INPUT_GET, 'doctor', FILTER_VALIDATE_INT) ?: null,
    'id_consultorio' => filter_input(INPUT_GET, 'consultorio', FILTER_VALIDATE_INT) ?: null,
    'id_tratamiento' => filter_input(INPUT_GET, 'tratamiento', FILTER_VALIDATE_INT) ?: null
];

// Se filtran las citas según los criterios
$citas_pasadas = [];
$ahora = new DateTime();

foreach ($todas_las_citas as $cita) {
    $fecha_cita = new DateTime($cita['inicio']);

    // Condición 1: La cita debe ser del pasado para considerarse historial.
    if ($fecha_cita < $ahora) {
        
        // Condición 2: La cita debe coincidir con los filtros (si están activos).
        $pasa_filtro_fecha_desde = empty($filtros['fecha_desde']) || $cita['inicio'] >= $filtros['fecha_desde'];
        $pasa_filtro_fecha_hasta = empty($filtros['fecha_hasta']) || $cita['inicio'] <= $filtros['fecha_hasta'] . ' 23:59:59';
        $pasa_filtro_doctor = empty($filtros['id_doctor']) || $cita['id_doctor'] == $filtros['id_doctor'];
        $pasa_filtro_consultorio = empty($filtros['id_consultorio']) || $cita['id_consultorio'] == $filtros['id_consultorio'];
        
        // [ADAPTADO] Lógica para filtrar por tratamiento: busca si el ID del tratamiento está en la lista de tratamientos de la cita.
        $pasa_filtro_tratamiento = empty($filtros['id_tratamiento']) || (
            !empty($cita['tratamientos']) && in_array(
                TratamientosM::ObtenerNombreTratamientoM($filtros['id_tratamiento']), // Obtenemos el nombre del tratamiento del filtro
                array_map('trim', explode(',', $cita['tratamientos'])) // Lo buscamos en la lista de tratamientos de la cita
            )
        );

        if ($pasa_filtro_fecha_desde && $pasa_filtro_fecha_hasta && $pasa_filtro_doctor && $pasa_filtro_consultorio && $pasa_filtro_tratamiento) {
            $citas_pasadas[] = $cita;
        }
    }
}

// --- 4. CARGA DE DATOS PARA LOS MENÚS DESPLEGABLES DE FILTROS ---
$doctores = DoctoresC::ListarDoctoresC();
$consultorios = ConsultoriosC::VerConsultoriosC(null, null);
$tratamientos = TratamientosM::ListarTratamientosM();

// --- 5. FUNCIÓN DE AYUDA (HELPER) PARA MOSTRAR ESTADOS ---
function mostrarEstadoHistorial($estado) {
    $clases = ["Completada" => "label-success", "Cancelada" => "label-danger", "Pendiente" => "label-warning"];
    return "<span class='label " . ($clases[$estado] ?? 'label-default') . "'>" . htmlspecialchars($estado) . "</span>";
}
?>

<!-- ========================================================================= -->
<!-- COMIENZA EL HTML DEL MÓDULO                                               -->
<!-- ========================================================================= -->

<section class="content-header">
    <h1>Mi Historial Clínico</h1>
    <ol class="breadcrumb">
        <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
        <li class="active">Mi Historial Clínico</li>
    </ol>
</section>

<section class="content container-fluid">
    <!-- Panel de Filtros, colapsado por defecto -->
    <div class="box box-primary collapsed-box">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-filter"></i> Filtros de Búsqueda</h3>
            <div class="box-tools pull-right">
              <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
        </div>
        <div class="box-body">
            <!-- [CORREGIDO] El action del form debe apuntar a la URL correcta para mantener los parámetros GET -->
            <form method="get" action="<?= BASE_URL ?>index.php?url=historial" class="form-horizontal">
                <!-- Se añade un campo oculto para mantener el parámetro de la URL -->
                <input type="hidden" name="url" value="historial">
                <div class="row">
                    <div class="col-md-3"><div class="form-group"><label>Desde Fecha:</label><input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($filtros['fecha_desde']) ?>"></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Hasta Fecha:</label><input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>"></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Doctor:</label><select name="doctor" class="form-control"><option value="">Todos</option><?php foreach ($doctores as $doc): ?><option value="<?= $doc['id'] ?>" <?= ($filtros['id_doctor'] == $doc['id']) ? 'selected' : '' ?>><?= htmlspecialchars($doc['nombre'] . ' ' . $doc['apellido']) ?></option><?php endforeach; ?></select></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Consultorio:</label><select name="consultorio" class="form-control"><option value="">Todos</option><?php foreach ($consultorios as $con): ?><option value="<?= $con['id'] ?>" <?= ($filtros['id_consultorio'] == $con['id']) ? 'selected' : '' ?>><?= htmlspecialchars($con['nombre']) ?></option><?php endforeach; ?></select></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Tratamiento:</label><select name="tratamiento" class="form-control"><option value="">Todos</option><?php foreach ($tratamientos as $trat): ?><option value="<?= $trat['id'] ?>" <?= ($filtros['id_tratamiento'] == $trat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($trat['nombre']) ?></option><?php endforeach; ?></select></div></div>
                </div>
                <div class="box-footer text-right">
                    <a href="<?= BASE_URL ?>index.php?url=historial" class="btn btn-default">Limpiar Filtros</a>
                    <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados del Historial en formato Acordeón -->
    <?php if (empty($citas_pasadas)): ?>
        <div class="alert alert-info text-center">
            <h4><i class="icon fa fa-info-circle"></i> No se encontraron citas en su historial que coincidan con los filtros.</h4>
            <p>Intente con un rango de fechas más amplio o limpie los filtros.</p>
        </div>
    <?php else: ?>
        <div class="panel-group" id="historial-acordeon" role="tablist" aria-multiselectable="true">
            <?php foreach ($citas_pasadas as $cita): ?>
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="heading<?= $cita['id'] ?>">
                        <h4 class="panel-title">
                            <a role="button" data-toggle="collapse" data-parent="#historial-acordeon" href="#collapse<?= $cita['id'] ?>" aria-expanded="false" class="collapsed" style="display: block; text-decoration: none;">
                                <strong>Fecha: <?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></strong>
                                <small class="text-muted">- Dr(a). <?= htmlspecialchars($cita['nombre_doctor']) ?></small>
                                <span class="pull-right">
                                    <?= mostrarEstadoHistorial($cita['estado']) ?>
                                    <i class="fa fa-chevron-down pull-right" style="margin-left: 10px;"></i>
                                </span>
                            </a>
                        </h4>
                    </div>
                    <div id="collapse<?= $cita['id'] ?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading<?= $cita['id'] ?>">
                        <div class="panel-body">
                            <h4><i class="fa fa-stethoscope"></i> Resumen de la Consulta</h4>
                            <dl class="dl-horizontal">
                                <dt>Consultorio:</dt><dd><?= htmlspecialchars($cita['nombre_consultorio'] ?? 'N/A') ?></dd>
                                <dt>Motivo:</dt><dd><?= htmlspecialchars($cita['motivo'] ?? 'N/A') ?></dd>
                                <dt>Diagnóstico:</dt><dd><?= !empty($cita['observaciones']) ? nl2br(htmlspecialchars($cita['observaciones'])) : 'Sin observaciones.' ?></dd>
                                <dt>Peso Registrado:</dt><dd><?= !empty($cita['peso']) ? htmlspecialchars($cita['peso']) . ' kg' : 'No registrado' ?></dd>
                                <dt>Presión Arterial:</dt><dd><?= !empty($cita['presion_arterial']) ? htmlspecialchars($cita['presion_arterial']) : 'No registrada' ?></dd>
                            </dl>
                            <hr>
                            
                            <?php if (!empty($cita['tratamientos'])): ?>
                                <h4><i class="fa fa-medkit"></i> Tratamientos Aplicados en esta Sesión</h4>
                                <p>
                                    <?php 
                                    $tratamientos_array = explode(',', $cita['tratamientos']);
                                    foreach($tratamientos_array as $trat) {
                                        echo '<span class="label label-primary" style="margin-right: 5px; font-size: 13px;">' . htmlspecialchars(trim($trat)) . '</span>';
                                    }
                                    ?>
                                </p>
                                <hr>
                            <?php endif; ?>
                            
                           <?php 
$medicamentos = !empty($cita['receta_json']) ? json_decode($cita['receta_json'], true) : [];
if (!empty($medicamentos) && is_array($medicamentos)): 
?>
    <h4><i class="fa fa-flask"></i> Medicamentos Recetados</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-condensed">
            <thead><tr style="background-color: #f9f9f9;"><th>Medicamento</th><th>Indicación</th></tr></thead>
            <tbody>
                <?php foreach ($medicamentos as $medicamento): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($medicamento['nombre_completo'] ?? ($medicamento['nombre_recetado'] ?? 'N/A')) ?></strong></td>
                        <td>
                            <?php
                                $indicacion_parts = [];
                                if (!empty($medicamento['dosis'])) $indicacion_parts[] = 'Dosis: ' . htmlspecialchars($medicamento['dosis']);
                                if (!empty($medicamento['frecuencia'])) $indicacion_parts[] = 'Frecuencia: ' . htmlspecialchars($medicamento['frecuencia']);
                                echo implode(', ', $indicacion_parts) ?: 'No especificada';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if(!empty($cita['indicaciones_receta'])): ?>
        <p><strong>Indicaciones Adicionales:</strong> <?= nl2br(htmlspecialchars($cita['indicaciones_receta'])) ?></p>
    <?php endif; ?>
    <hr>
<?php endif; ?>
                            
                            <?php if (!empty($cita['certificado_uuid']) || !empty($cita['receta_uuid'])): ?>
                                <h4><i class="fa fa-files-o"></i> Documentos Adjuntos</h4>
                                <div class="btn-group">
                                    <?php if (!empty($cita['receta_uuid'])): ?>
                                        <a href="<?= BASE_URL ?>generar_documento.php?tipo=receta&uuid=<?= $cita['receta_uuid'] ?>" class="btn btn-danger" target="_blank"><i class="fa fa-file-pdf-o"></i> Ver Receta (PDF)</a>
                                    <?php endif; ?>
                                    <?php if (!empty($cita['certificado_uuid'])): ?>
                                        <a href="<?= BASE_URL ?>generar_documento.php?tipo=certificado&uuid=<?= $cita['certificado_uuid'] ?>" class="btn btn-warning" target="_blank"><i class="fa fa-file-pdf-o"></i> Ver Certificado (PDF)</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>