<?php
// En Vistas/modulos/Ver-consultorios.php (VERSIÓN ADAPTADA — apariencia original, mejoras aplicadas)

// --- 1. OBTENCIÓN Y AGRUPACIÓN DE DATOS PARA EL DIRECTORIO DE DOCTORES ---
$directorio_completo = ConsultoriosC::VerDirectorioMedicoC();
$consultorios_agrupados = [];

// La lógica agrupa a los doctores por el nombre de su consultorio.
// NUEVO: procesa el campo estado + motivo (estado_motivo_manual_hoy) si viene.
if (is_array($directorio_completo)) {
    foreach ($directorio_completo as $doctor) {
        $nombre_consultorio = $doctor['nombre_consultorio'];

        // Procesamos estado|motivo (si existe) — formato esperado: "cerrado|por mantenimiento"
        $estado_motivo_raw = $doctor['estado_motivo_manual_hoy'] ?? null;
        $estado_manual = null;
        $motivo_manual = null;
        if ($estado_motivo_raw) {
            $partes = explode('|', $estado_motivo_raw, 2);
            $estado_manual = trim($partes[0]);
            $motivo_manual = isset($partes[1]) ? trim($partes[1]) : null;
        }

        if (!isset($consultorios_agrupados[$nombre_consultorio])) {
            $consultorios_agrupados[$nombre_consultorio] = [
                'nombre'         => $nombre_consultorio,
                'direccion'      => $doctor['direccion_consultorio'],
                'telefono'       => $doctor['telefono_consultorio'],
                'email'          => $doctor['email_consultorio'],
                'estado_manual'  => $estado_manual,   // guardamos solo el estado (o null)
                'motivo_manual'  => $motivo_manual,   // guardamos el motivo (o null)
                'doctores'       => []
            ];
        } else {
            // Si ya existe la entrada, y no tiene estado_manual todavía, pero este doctor sí trae info,
            // preferimos conservar el estado_manual si ya estaba; si no, asignamos el nuevo.
            if (empty($consultorios_agrupados[$nombre_consultorio]['estado_manual']) && $estado_manual) {
                $consultorios_agrupados[$nombre_consultorio]['estado_manual'] = $estado_manual;
                $consultorios_agrupados[$nombre_consultorio]['motivo_manual'] = $motivo_manual;
            }
        }

        // Añadimos el doctor actual a la lista de su consultorio correspondiente.
        $consultorios_agrupados[$nombre_consultorio]['doctores'][] = $doctor;
    }
}

// --- 2. OBTENER Y PROCESAR DATOS PARA LA GRILLA DE HORARIOS DE SEDES ---
$horarios_consultorios_raw = ConsultoriosC::VerHorariosConsultoriosC();
$horarios_por_consultorio = [];
$dias_semana_nombres = ['1' => 'Lunes', '2' => 'Martes', '3' => 'Miércoles', '4' => 'Jueves', '5' => 'Viernes', '6' => 'Sábado', '7' => 'Domingo'];

if (is_array($horarios_consultorios_raw)) {
    foreach ($horarios_consultorios_raw as $horario) {
        $nombre = $horario['nombre_consultorio'];
        $dia = $horario['dia_semana'];
        $hora_formateada = date("g:i A", strtotime($horario['hora_apertura'])) . ' - ' . date("g:i A", strtotime($horario['hora_cierre']));
        $horarios_por_consultorio[$nombre][$dia] = $hora_formateada;
    }
}

// --- 3. FUNCIÓN DE FORMATEO DE HORARIOS DE DOCTOR ---
function formatearHorariosDoctor($horariosStr) {
    if (empty($horariosStr)) {
        return '<li class="list-group-item">Horarios no disponibles</li>';
    }
    $dias_semana_nombres = ['1' => 'Lunes', '2' => 'Martes', '3' => 'Miércoles', '4' => 'Jueves', '5' => 'Viernes', '6' => 'Sábado', '7' => 'Domingo'];
    $html_items = [];
    $horarios_individuales = explode('|', $horariosStr);
    foreach ($horarios_individuales as $horario) {
        $partes = explode(';', $horario);
        if (count($partes) === 3) {
            $dia_idx = $partes[0];
            $hora_inicio_formateada = date("g:i A", strtotime($partes[1]));
            $hora_fin_formateada = date("g:i A", strtotime($partes[2]));
            if (isset($dias_semana_nombres[$dia_idx])) {
                $html_items[] = '<li class="list-group-item"><strong>' . $dias_semana_nombres[$dia_idx] . ':</strong><span class="pull-right">' . $hora_inicio_formateada . ' - ' . $hora_fin_formateada . '</span></li>';
            }
        }
    }
    return empty($html_items) ? '<li class="list-group-item">Horarios no disponibles</li>' : implode('', $html_items);
}

// --- 4. FUNCIÓN PARA MOSTRAR TRATAMIENTOS COMO ETIQUETAS ---
function mostrarTratamientosComoEtiquetas($tratamientos_str) {
    if (empty($tratamientos_str)) {
        return '<span class="text-muted">No especificados</span>';
    }
    $tratamientos = explode(',', $tratamientos_str);
    $html = '';
    foreach ($tratamientos as $tratamiento) {
        $html .= '<span class="label label-info">' . htmlspecialchars(trim($tratamiento)) . '</span> ';
    }
    return $html;
}
?>

<section class="content-header">
    <h1>Directorio Médico y Horarios de la Clínica</h1>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Directorio</li></ol>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-clock-o"></i> Horarios de Operación por Sede</h3></div>
        <div class="box-body">
            <?php if (empty($horarios_por_consultorio)): ?>
                <div class="alert alert-warning">No se encontraron horarios generales definidos para las sedes.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover text-center">
                        <thead><tr style="background-color: #f8f8f8;"><th class="text-left">Sede</th><?php foreach ($dias_semana_nombres as $nombre_dia): ?><th><?= $nombre_dia ?></th><?php endforeach; ?></tr></thead>
                        <tbody>
                            <?php foreach ($horarios_por_consultorio as $nombre_consultorio => $horarios): ?>
                                <?php 
                                // Tomamos estado y motivo desde la agrupación (si existe)
                                $estado_manual_hoy = $consultorios_agrupados[$nombre_consultorio]['estado_manual'] ?? null;
                                $motivo_manual_hoy = $consultorios_agrupados[$nombre_consultorio]['motivo_manual'] ?? null;
                                ?>
                                <tr>
                                    <td class="text-left">
                                        <strong><?= htmlspecialchars($nombre_consultorio) ?></strong>
                                        <?php if ($estado_manual_hoy): ?>
                                            <!-- estado manual mostrado en la celda de la sede (misma apariencia original) -->
                                            <span class="label label-danger pull-right" style="font-size: 12px; margin-top: 3px;" data-toggle="tooltip" title="<?= htmlspecialchars($motivo_manual_hoy) ?>">
                                                <i class="fa fa-warning"></i> Hoy: <?= htmlspecialchars(ucfirst($estado_manual_hoy)) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <?php for ($i = 1; $i <= 7; $i++): ?>
                                        <td>
                                            <?php if ($estado_manual_hoy): ?>
                                                <!-- Si hay estado manual, mostramos el estado (con motivo en tooltip) en lugar del horario -->
                                                <span class="label label-default" data-toggle="tooltip" title="<?= htmlspecialchars($motivo_manual_hoy) ?>">
                                                    <?= htmlspecialchars(ucfirst($estado_manual_hoy)) ?>
                                                </span>
                                            <?php elseif (isset($horarios[$i])): ?>
                                                <!-- Mantener estilo original para horarios -->
                                                <span class="label bg-green"><?= $horarios[$i] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Cerrado</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-user-md"></i> Directorio de Profesionales por Sede</h3></div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Buscar Doctor por Nombre:</label><div class="input-group"><span class="input-group-addon"><i class="fa fa-search"></i></span><input type="text" id="filtro-nombre" class="form-control" placeholder="Escriba un nombre..."></div></div></div>
                <div class="col-md-6"><div class="form-group"><label>Filtrar por Tratamiento:</label><div class="input-group"><span class="input-group-addon"><i class="fa fa-tags"></i></span><input type="text" id="filtro-tratamiento" class="form-control" placeholder="Escriba un tratamiento..."></div></div></div>
            </div>
        </div>
    </div>

    <div class="row" id="directorio-medico-container">
        <?php if (empty($consultorios_agrupados)): ?>
             <div class="col-xs-12"><div class="alert alert-info">No hay doctores disponibles en el directorio en este momento.</div></div>
        <?php else: ?>
            <?php foreach ($consultorios_agrupados as $data): ?>
                <div class="col-md-12">
                    <h2 class="page-header" style="border-color: #3c8dbc; margin-top: 20px; margin-bottom: 20px;">
                        <i class="fa fa-hospital-o"></i> <?= htmlspecialchars($data['nombre']) ?>
                        <small class="pull-right" style="font-size: 14px; color: #666;">
                            <i class="fa fa-map-marker"></i> <?= htmlspecialchars($data['direccion'] ?? 'Dirección no disponible') ?> | 
                            <i class="fa fa-phone"></i> <?= htmlspecialchars($data['telefono'] ?? 'Teléfono no disponible') ?>
                        </small>
                    </h2>
                </div>
                <?php if (empty($data['doctores'])): ?>
                    <div class="col-md-12"><p class="text-muted" style="margin-left: 15px;">No hay doctores asignados a esta sede.</p></div>
                <?php else: ?>
                    <?php foreach ($data['doctores'] as $doctor): ?>
                        <div class="col-md-6 doctor-card" 
                             data-nombre="<?= strtolower(htmlspecialchars($doctor['nombre_doctor'])) ?>" 
                             data-tratamientos="<?= strtolower(htmlspecialchars($doctor['tratamientos_doctor'] ?? '')) ?>">
                            <div class="box box-widget widget-user">
                                <div class="widget-user-header bg-aqua-active">
                                    <h3 class="widget-user-username"><?= htmlspecialchars($doctor['nombre_doctor']) ?></h3>
                                    <h5 class="widget-user-desc">&nbsp;</h5>
                                </div>
                                <div class="widget-user-image">
                                    <img class="img-circle" 
                                         src="<?= !empty($doctor['foto_doctor']) ? BASE_URL . $doctor['foto_doctor'] : BASE_URL . 'Vistas/img/user-default.png' ?>" 
                                         alt="Foto del Doctor">
                                </div>
                                <div class="box-footer">
                                    <div class="row">
                                        <div class="col-sm-12 border-bottom">
                                            <div class="description-block">
                                                <h5 class="description-header">Tratamientos</h5>
                                                <div class="tratamientos-tags">
                                                    <?= mostrarTratamientosComoEtiquetas($doctor['tratamientos_doctor']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="description-block">
                                                <h5 class="description-header">Horarios de Atención</h5>
                                                <ul class="list-group list-group-unbordered text-center" style="margin-top: 10px;">
                                                    <?= formatearHorariosDoctor($doctor['horarios_doctor']) ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<style>
.tratamientos-tags { padding: 5px 10px 15px 10px; text-align: center; line-height: 2; }
.tratamientos-tags .label { margin: 2px; font-size: 13px; font-weight: 500; }
.box-footer .border-bottom { border-bottom: 1px solid #f4f4f4; }
</style>

<?php ob_start(); ?>
<script>
$(function() {
    // Inicializa tooltips (motivo del estado manual)
    $('[data-toggle="tooltip"]').tooltip();

    // Filtro simple para doctor cards (mismo comportamiento que tenías)
    function aplicarFiltros() {
        var nombreFiltro = $('#filtro-nombre').val().toLowerCase().trim();
        var tratamientoFiltro = $('#filtro-tratamiento').val().toLowerCase().trim();
        
        $('.doctor-card').each(function() {
            var card = $(this);
            var nombreDoctor = card.data('nombre');
            var tratamientosDoctor = card.data('tratamientos');
            
            var nombreMatch = nombreDoctor.includes(nombreFiltro);
            var tratamientoMatch = tratamientosDoctor.includes(tratamientoFiltro);
            
            if (nombreMatch && tratamientoMatch) {
                card.show(300);
            } else {
                card.hide(300);
            }
        });
    }
    $('#filtro-nombre, #filtro-tratamiento').on('keyup', aplicarFiltros);
});
</script>
<?php $scriptDinamico = ob_get_clean(); ?>
