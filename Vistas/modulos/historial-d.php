<?php
// 1. SEGURIDAD: Verificar rol permitido.
if (!isset($_SESSION["rol"]) || !in_array($_SESSION["rol"], ["Secretario", "Doctor", "Administrador"])) {
    echo '<script>window.location = "inicio";</script>';
    return;
}

// 2. CARGA DE DATOS PARA LOS FILTROS
$pacientes_disponibles    = PacientesC::ListarPacientesC();
$tratamientos_disponibles = TratamientosM::ListarTratamientosM();
$doctores_disponibles     = ($_SESSION['rol'] !== 'Doctor') ? DoctoresC::ListarDoctoresC() : [];

// 3. RECOGER FILTROS DEL FORMULARIO
$filtros = [
    'id_paciente'    => filter_input(INPUT_GET, 'id_paciente', FILTER_VALIDATE_INT),
    'id_doctor'      => filter_input(INPUT_GET, 'id_doctor', FILTER_VALIDATE_INT),
    'id_tratamiento' => filter_input(INPUT_GET, 'id_tratamiento', FILTER_VALIDATE_INT),
    'fecha_desde'    => $_GET['fecha_desde'] ?? '',
    'fecha_hasta'    => $_GET['fecha_hasta'] ?? '',
    'palabra_clave'  => trim($_GET['palabra_clave'] ?? '')
];

// --- LÓGICA DE BÚSQUEDA ---
$resultado_busqueda  = null;
$busqueda_realizada  = false;
$paciente_seleccionado = null;
$historial_citas     = [];

// Si el usuario es Doctor, la búsqueda se hace siempre.
// Si es Admin/Secretario, solo si aplica algún filtro.
if ($_SESSION['rol'] === 'Doctor' || !empty(array_filter($filtros))) {
    $busqueda_realizada = true;
    $resultado_busqueda = (new CitasC())->VerHistorialClinicoC($filtros);
    $paciente_seleccionado = $resultado_busqueda['paciente'] ?? null;
    $historial_citas       = $resultado_busqueda['citas'] ?? [];
}

// 5. FUNCIÓN HELPER PARA ESTADOS
function etiquetaEstado($estado) {
    $clases = [
        "Completada" => "label-success",
        "Cancelada"  => "label-danger",
        "Pendiente"  => "label-warning"
    ];
    return "<span class='label " . ($clases[$estado] ?? 'label-default') . "'>" . htmlspecialchars($estado) . "</span>";
}
?>

<section class="content-header">
    <h1>Búsqueda de Historial Clínico</h1>
    <ol class="breadcrumb">
        <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
        <li class="active">Historial Clínico</li>
    </ol>
</section>

<section class="content">
    <!-- Panel de Filtros -->
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-search"></i> Filtros de Búsqueda Avanzada</h3></div>
        <div class="box-body">
            <form method="get" action="">
                <input type="hidden" name="url" value="historial-d">
                <div class="row">
                    <!-- Paciente con Select2 -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Paciente:</label>
                            <select name="id_paciente" class="form-control select2">
                                <option value="">Todos los Pacientes</option>
                                <?php foreach ($pacientes_disponibles as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= ($filtros['id_paciente'] == $p['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['apellido'] . ', ' . $p['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <?php if ($_SESSION['rol'] !== 'Doctor'): ?>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Doctor:</label>
                                <select name="id_doctor" class="form-control">
                                    <option value="">Todos los Doctores</option>
                                    <?php foreach ($doctores_disponibles as $d): ?>
                                        <option value="<?= $d['id'] ?>" <?= ($filtros['id_doctor'] == $d['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($d['apellido'] . ', ' . $d['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tratamiento:</label>
                            <select name="id_tratamiento" class="form-control">
                                <option value="">Todos los Tratamientos</option>
                                <?php foreach ($tratamientos_disponibles as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= ($filtros['id_tratamiento'] == $t['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3"><div class="form-group"><label>Desde Fecha:</label><input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($filtros['fecha_desde']) ?>"></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Hasta Fecha:</label><input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>"></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Buscar en Motivo/Diagnóstico:</label><input type="text" name="palabra_clave" class="form-control" placeholder="Ej: Migraña, control, fractura..." value="<?= htmlspecialchars($filtros['palabra_clave']) ?>"></div></div>
                </div>
                <div class="box-footer text-right">
                    <a href="<?= BASE_URL ?>historial-d" class="btn btn-default">Limpiar Búsqueda</a>
                    <button type="submit" class="btn btn-primary">Buscar en Historial</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Panel Resumen del Paciente -->
<?php if ($paciente_seleccionado): ?>
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-user"></i> Resumen del Paciente</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <!-- Columna de la foto -->
            <div class="col-md-3 text-center">
                <?php 
                // --- Lógica de la Foto Corregida ---
                
                // 1. Establecemos una ruta por defecto segura.
                $rutaFoto = 'Vistas/img/pacientes/default.png'; 

                // 2. Verificamos si el paciente tiene una foto registrada en la BD.
                if (!empty($paciente_seleccionado['foto'])) {
                    // 3. Verificamos si ese archivo realmente existe en el servidor.
                    //    Esto previene mostrar iconos de imagen rota.
                    if (file_exists($paciente_seleccionado['foto'])) {
                        // Si existe, usamos la ruta de la foto del paciente.
                        $rutaFoto = $paciente_seleccionado['foto'];
                    }
                }
                ?>
                <img src="<?= BASE_URL . htmlspecialchars($rutaFoto) ?>" 
                     alt="Foto de <?= htmlspecialchars($paciente_seleccionado['nombre']) ?>" 
                     class="img-thumbnail" style="max-width:180px; border-radius:10px;">
            </div>

            <!-- Columna con datos -->
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-6">
                        <h4><strong><?= htmlspecialchars($paciente_seleccionado['nombre'] . ' ' . $paciente_seleccionado['apellido']) ?></strong></h4>
                        <p class="text-muted" style="margin-left: 5px;">
                            <strong>Documento:</strong> 
                            <?= htmlspecialchars($paciente_seleccionado['tipo_documento'] . ' ' . $paciente_seleccionado['numero_documento']) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fa fa-warning text-red"></i> Alergias:</strong><br>
                        <?php if(empty($paciente_seleccionado['alergias'])): ?>
                            <span class="text-muted">Ninguna registrada</span>
                        <?php else: foreach($paciente_seleccionado['alergias'] as $item): ?>
                            <span class="label label-danger" style="margin-right: 5px;"><?= htmlspecialchars($item['nombre']) ?></span>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="row" style="margin-top:15px;">
                    <div class="col-md-12">
                        <strong><i class="fa fa-medkit text-blue"></i> Enfermedades Preexistentes:</strong><br>
                        <?php if(empty($paciente_seleccionado['enfermedades'])): ?>
                            <span class="text-muted">Ninguna registrada</span>
                        <?php else: foreach($paciente_seleccionado['enfermedades'] as $item): ?>
                            <span class="label label-warning" style="margin-right: 5px;"><?= htmlspecialchars($item['nombre']) ?></span>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

    <!-- Panel de Resultados -->
    <?php if ($busqueda_realizada): ?>
        <div class="box box-solid">
            <div class="box-header with-border"><h3 class="box-title">Resultados de la Búsqueda</h3></div>
            <div class="box-body">
                <?php if (empty($historial_citas)): ?>
                    <div class="alert alert-warning text-center">
                        <h4><i class="fa fa-exclamation-triangle"></i> No se encontraron registros que coincidan con su búsqueda.</h4>
                        <?php if ($_SESSION['rol'] === 'Doctor'): ?>
                            <p>Recuerde que solo se muestra el historial de sus propios pacientes.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="panel-group" id="historial-acordeon">
                        <?php foreach ($historial_citas as $cita): ?>
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" data-parent="#historial-acordeon" href="#collapse<?= $cita['id'] ?>" class="collapsed" style="display: block; width: 100%; text-decoration: none;">
                                        <strong>Fecha: <?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></strong>
                                        <small class="text-muted">- Paciente: <?= htmlspecialchars($cita['nombre_paciente'] . ' ' . $cita['apellido_paciente']) ?></small>
                                        <span class="pull-right"><?= etiquetaEstado($cita['estado']) ?><i class="fa fa-chevron-down pull-right" style="margin-left: 10px;"></i></span>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapse<?= $cita['id'] ?>" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <dl class="dl-horizontal">
                                        <dt>Atendido por:</dt><dd><?= htmlspecialchars($cita['doctor_atendio']) ?></dd>
                                        <dt>Consultorio:</dt><dd><?= htmlspecialchars($cita['nombre_consultorio'] ?? 'N/A') ?></dd>
                                        <dt>Motivo:</dt><dd><?= htmlspecialchars($cita['motivo'] ?? 'N/A') ?></dd>
                                        <dt>Diagnóstico/Obs:</dt><dd><?= !empty($cita['observaciones']) ? nl2br(htmlspecialchars($cita['observaciones'])) : 'N/A' ?></dd>
                                    </dl>

                                    <div class="text-right" style="margin-bottom: 15px;">
                                        <?php if (!empty($cita['receta_uuid'])): ?>
                                            <a href="<?= BASE_URL ?>generar_documento.php?tipo=receta&uuid=<?= $cita['receta_uuid'] ?>" target="_blank" class="btn btn-sm btn-danger">
                                                <i class="fa fa-file-pdf-o"></i> Ver Receta (PDF)
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($cita['certificado_uuid'])): ?>
                                            <a href="<?= BASE_URL ?>generar_documento.php?tipo=certificado&uuid=<?= $cita['certificado_uuid'] ?>" target="_blank" class="btn btn-sm btn-warning">
                                                <i class="fa fa-file-pdf-o"></i> Ver Certificado (PDF)
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <hr>

                                    <?php if (!empty($cita['tratamientos_aplicados'])): ?>
                                        <h4><i class="fa fa-medkit"></i> Tratamientos Aplicados</h4>
                                        <p><?= htmlspecialchars($cita['tratamientos_aplicados']) ?></p><hr>
                                    <?php endif; ?>

                                    <?php if (!empty($cita['medicamentos_recetados'])): ?>
                                        <h4><i class="fa fa-flask"></i> Medicamentos Recetados (Fármacos)</h4>
                                        <table class="table table-condensed table-bordered">
                                            <thead><tr><th>Medicamento</th><th>Dosis</th><th>Frecuencia</th><th>Instrucciones</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($cita['medicamentos_recetados'] as $med): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($med['nombre_medicamento'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($med['dosis'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($med['frecuencia'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($med['instrucciones'] ?? '-') ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif($_SESSION['rol'] !== 'Doctor'): ?>
        <div class="alert alert-info text-center"><h4><i class="fa fa-arrow-up"></i> Utilice los filtros para iniciar una búsqueda en el historial clínico.</h4></div>
    <?php endif; ?>
</section>

<?php ob_start(); ?>
<script>
$(function() {
    // Inicializar Select2 en el selector de pacientes
    $('.select2').select2();
});
</script>
<?php $scriptDinamico = ob_get_clean(); ?>
