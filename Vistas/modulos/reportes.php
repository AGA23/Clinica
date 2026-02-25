<?php
// En Vistas/modulos/reportes.php (VERSIÓN FINAL CON GRÁFICOS CORREGIDOS Y MODAL DETALLADO)

// 1. SEGURIDAD DE ROL
if (!isset($_SESSION["rol"]) || !in_array($_SESSION["rol"], ["Secretario", "Administrador"])) {
    echo '<script>window.location = "inicio";</script>';
    return;
}

// 2. OBTENCIÓN DE DATOS CENTRALIZADA
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-t');
$id_consultorio = $_GET['id_consultorio'] ?? null;

// LLAMADA ÚNICA AL CONTROLADOR
$datos_reportes = ReportesC::obtenerDatosCompletosReporte($fecha_desde, $fecha_hasta, $id_consultorio);

// 3. LISTA DE CONSULTORIOS (solo para Admin)
$consultorios = ($_SESSION['rol']==='Administrador') ? ReportesM::ObtenerListaConsultorios() : [];
?>

<section class="content-header">
    <h1>Reportes y Estadísticas</h1>
    <?php if($_SESSION['rol']==='Secretario'): ?>
        <small>Mostrando datos únicamente de: <strong><?= htmlspecialchars($_SESSION['nombre_consultorio'] ?? 'Mi Consultorio') ?></strong></small>
    <?php else: ?>
        <small>Visión general de la clínica o por consultorio</small>
    <?php endif; ?>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Reportes</li></ol>
</section>

<section class="content">

    <!-- FORMULARIO DE FILTROS -->
    <div class="box box-primary">
        <div class="box-header with-border"><h3><i class="fa fa-filter"></i> Filtros del Reporte</h3></div>
        <div class="box-body">
            <form method="GET" class="form-inline">
                <input type="hidden" name="url" value="reportes">
                
                <?php if($_SESSION['rol']==='Administrador'): ?>
                <div class="form-group" style="margin-right: 15px;">
                    <label>Consultorio:</label>
                    <select name="id_consultorio" id="id_consultorio" class="form-control">
                        <option value="">-- Todos los Consultorios --</option>
                        <?php if(!empty($consultorios)): foreach($consultorios as $c):
                            $selected = ($id_consultorio == $c['id']) ? 'selected' : ''; ?>
                            <option value="<?= $c['id'] ?>" <?= $selected ?>><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group" style="margin-right: 15px;">
                    <label>Desde:</label>
                    <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>" required>
                </div>
                <div class="form-group" style="margin-right: 15px;">
                    <label>Hasta:</label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Aplicar Filtros</button>
                <a href="<?= BASE_URL ?>reportes" class="btn btn-default">Limpiar</a>
            </form>
        </div>
    </div>

    <!-- WIDGETS DE RESUMEN -->
    <div class="row">
        <div class="col-lg-3 col-xs-6"><div class="small-box bg-aqua"><div class="inner"><h3><?= $datos_reportes['widgets']['total_citas'] ?? 0 ?></h3><p>Citas Totales</p></div><div class="icon"><i class="fa fa-calendar"></i></div></div></div>
        <div class="col-lg-3 col-xs-6"><div class="small-box bg-green"><div class="inner"><h3><?= $datos_reportes['widgets']['citas_completadas'] ?? 0 ?></h3><p>Citas Completadas</p></div><div class="icon"><i class="fa fa-check"></i></div></div></div>
        <div class="col-lg-3 col-xs-6"><div class="small-box bg-red"><div class="inner"><h3><?= $datos_reportes['widgets']['citas_canceladas'] ?? 0 ?></h3><p>Citas Canceladas</p></div><div class="icon"><i class="fa fa-times"></i></div></div></div>
        <div class="col-lg-3 col-xs-6"><div class="small-box bg-yellow"><div class="inner"><h3><?= $datos_reportes['widgets']['tasa_cancelacion'] ?? 0 ?>%</h3><p>Tasa de Cancelación</p></div><div class="icon"><i class="fa fa-line-chart"></i></div></div></div>
    </div>

    <!-- GRÁFICOS -->
    <div class="row">
        <div class="col-md-8">
            <div class="box box-success"><div class="box-header with-border"><h3>Flujo de Pacientes por Mes</h3></div><div class="box-body"><canvas id="grafFlujoPacientes" style="height: 250px;"></canvas></div></div>
        </div>
        <div class="col-md-4">
            <div class="box box-info"><div class="box-header with-border"><h3>Pacientes Nuevos vs Recurrentes</h3></div><div class="box-body"><canvas id="grafPacientesNuevos" style="height: 250px;"></canvas></div></div>
        </div>
    </div>

    <!-- ANÁLISIS DETALLADO DE PACIENTES -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border"><h3 class="box-title">Análisis Detallado de Pacientes</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped dt-responsive" width="100%">
                        <thead>
                            <tr>
                                <th>Paciente (ID)</th>
                                <th>Visitas Completadas</th>
                                <th>Citas Canceladas</th>
                                <th>Tasa de Cancelación (%)</th>
                                <th>Tratamiento Frecuente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($datos_reportes['analisis_pacientes'])): foreach($datos_reportes['analisis_pacientes'] as $p): ?>
                                <tr>
                                    <td><a href="#" class="link-perfil-paciente" data-id-paciente="<?= $p['id_paciente'] ?>"><?= htmlspecialchars($p['paciente']) ?></a> (ID: <?= $p['id_paciente'] ?>)</td>
                                    <td><?= $p['visitas_completadas'] ?></td>
                                    <td><?= $p['citas_canceladas'] ?></td>
                                    <td><span class="badge bg-red"><?= round($p['tasa_cancelacion'], 1) ?>%</span></td>
                                    <td><?= htmlspecialchars($p['tratamiento_frecuente'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="5" class="text-center text-muted">No hay datos de pacientes para el período seleccionado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLAS ADICIONALES -->
    <div class="row">
        <div class="col-md-6"><div class="box"><div class="box-header with-border"><h3>Doctores con más Bloqueos</h3></div><div class="box-body no-padding">
             <table class="table table-striped"><tr><th>Doctor</th><th>Bloqueos</th></tr>
                <?php if(!empty($datos_reportes['bloqueos_doctores'])): foreach($datos_reportes['bloqueos_doctores'] as $b): ?>
                    <tr><td><?= htmlspecialchars($b['doctor']) ?></td><td><span class="badge bg-purple"><?= $b['total'] ?></span></td></tr>
                <?php endforeach; else: ?><tr><td colspan="2" class="text-center text-muted">No hay datos</td></tr><?php endif; ?>
            </table>
        </div></div></div>
        <div class="col-md-6"><div class="box"><div class="box-header with-border"><h3>Horarios con más Cancelaciones</h3></div><div class="box-body no-padding">
             <table class="table table-striped"><tr><th>Hora</th><th>Cancelaciones</th></tr>
                <?php if(!empty($datos_reportes['horarios_cancel'])): foreach($datos_reportes['horarios_cancel'] as $h): ?>
                    <tr><td><?= $h['hora'] ?>:00 - <?= $h['hora']+1 ?>:00</td><td><span class="badge bg-orange"><?= $h['total'] ?></span></td></tr>
                <?php endforeach; else: ?><tr><td colspan="2" class="text-center text-muted">No hay datos</td></tr><?php endif; ?>
            </table>
        </div></div></div>
    </div>

</section>

<!-- MODAL PERFIL DE PACIENTE -->
<div class="modal fade" id="modal-perfil-paciente">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Perfil Analítico del Paciente: <strong id="perfil-nombre-paciente"></strong></h4>
            </div>
            <div class="modal-body" id="perfil-modal-body">
                <p class="text-center" style="padding: 40px 0;"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i></p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function() {
    // Inicializar DataTables
    $('.dt-responsive').DataTable({
        "language": { "url": "<?= BASE_URL ?>Vistas/plugins/datatables/Spanish.json" },
        "responsive": true,
        "autoWidth": false,
        "order": []
    });

    // --- GRÁFICOS ---
    var flujoData = <?= json_encode($datos_reportes['grafico_flujo_pacientes'] ?? ['labels'=>[], 'data'=>[]]) ?>;
    if(flujoData.labels.length>0){
        new Chart(document.getElementById('grafFlujoPacientes').getContext('2d'), {
            type:'line',
            data:{labels:flujoData.labels,datasets:[{label:'Pacientes por mes',data:flujoData.data,backgroundColor:'rgba(60,141,188,0.2)',borderColor:'rgba(60,141,188,1)',borderWidth:2,fill:true,tension:0.1}]},
            options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true}}}
        });
    }

    var pieData = <?= json_encode($datos_reportes['grafico_pie_pacientes'] ?? ['labels'=>[], 'data'=>[]]) ?>;
    if(pieData.data && (pieData.data[0]>0 || pieData.data[1]>0)){
        new Chart(document.getElementById('grafPacientesNuevos').getContext('2d'), {
            type:'doughnut',
            data:{labels:pieData.labels,datasets:[{data:pieData.data,backgroundColor:['#00a65a','#f39c12']}]},
            options:{responsive:true,maintainAspectRatio:false}
        });
    }

    // --- MODAL PERFIL PACIENTE CON SCROLL ---
    $('.dt-responsive').on('click','.link-perfil-paciente',function(e){
        e.preventDefault();
        var idPaciente=$(this).data('id-paciente');
        var modal=$('#modal-perfil-paciente');
        var modalBody=$('#perfil-modal-body');
        modal.modal('show');
        modalBody.html('<p class="text-center" style="padding:40px 0;"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i></p>');

        $.post('<?= BASE_URL ?>ajax/reportes.php',{
            action:'obtener_perfil_paciente',
            id_paciente:idPaciente,
            desde:$('#fecha_desde').val(),
            hasta:$('#fecha_hasta').val()
        }).done(function(r){
            if(r.success){
                var p=r.datos;
                var html=`<div class="row">
                    <div class="col-md-4">
                        <h4>Datos Personales</h4>
                        <ul class="list-group list-group-unbordered">
                            <li class="list-group-item"><strong>ID:</strong> <span class="pull-right">${p.info_basica.id}</span></li>
                            <li class="list-group-item"><strong>Email:</strong> <span class="pull-right">${p.info_basica.correo || 'N/A'}</span></li>
                            <li class="list-group-item"><strong>Teléfono:</strong> <span class="pull-right">${p.info_basica.telefono || 'N/A'}</span></li>
                        </ul>
                    </div>
                    <div class="col-md-8">
                        <h4>Resumen de Actividad <small>(en período)</small></h4>
                        <div class="row">
                            <div class="col-sm-4"><div class="info-box bg-aqua"><span class="info-box-icon"><i class="fa fa-calendar"></i></span><div class="info-box-content"><span class="info-box-text">Total Citas</span><span class="info-box-number">${p.resumen_actividad.total_citas||0}</span></div></div></div>
                            <div class="col-sm-4"><div class="info-box bg-green"><span class="info-box-icon"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">Completadas</span><span class="info-box-number">${p.resumen_actividad.completadas||0}</span></div></div></div>
                            <div class="col-sm-4"><div class="info-box bg-red"><span class="info-box-icon"><i class="fa fa-times"></i></span><div class="info-box-content"><span class="info-box-text">Canceladas</span><span class="info-box-number">${p.resumen_actividad.canceladas||0}</span></div></div></div>
                        </div>
                    </div>
                </div>`;

                // Doctores y Tratamientos con scroll
                html+=`<div class="row">
                    <div class="col-md-6">
                        <h4>Doctores que lo Atendieron</h4>
                        <div style="max-height:200px;overflow-y:auto;border:1px solid #eee;"><ul class="list-group">`;
                (p.doctores_frecuentes.length>0)?p.doctores_frecuentes.forEach(d=>{html+=`<li class="list-group-item">${d.doctor}<span class="badge bg-blue">${d.total}</span></li>`;}):html+=`<li class="list-group-item text-muted">Sin datos.</li>`;
                html+=`</ul></div></div>
                    <div class="col-md-6">
                        <h4>Tratamientos Recibidos</h4>
                        <div style="max-height:200px;overflow-y:auto;border:1px solid #eee;"><ul class="list-group">`;
                (p.tratamientos_frecuentes.length>0)?p.tratamientos_frecuentes.forEach(t=>{html+=`<li class="list-group-item">${t.tratamiento}<span class="badge bg-yellow">${t.total}</span></li>`;}):html+=`<li class="list-group-item text-muted">Sin datos.</li>`;
                html+=`</ul></div></div></div>`;

                // Historial con scroll
                html+=`<h4 style="margin-top:20px;">Historial Completo de Citas en el Período</h4>
                    <div style="max-height:300px;overflow-y:auto;">
                        <table class="table table-condensed table-bordered table-striped">
                            <thead><tr><th>Fecha</th><th>Motivo</th><th>Doctor</th><th>Estado</th></tr></thead>
                            <tbody>`;
                (p.historial_reciente.length>0)?p.historial_reciente.forEach(c=>{html+=`<tr><td>${moment(c.fecha).format('DD/MM/YYYY')}</td><td>${c.motivo}</td><td>${c.doctor}</td><td>${c.estado}</td></tr>`;}):html+=`<tr><td colspan="4" class="text-center text-muted">Sin historial en este período.</td></tr>`;
                html+=`</tbody></table></div>`;

                $('#perfil-nombre-paciente').text(p.info_basica.nyaP);
                modalBody.html(html);

            } else { modalBody.html('<div class="alert alert-danger">'+(r.error||'Error al cargar el perfil')+'</div>'); }
        }).fail(()=>modalBody.html('<div class="alert alert-danger">Error de comunicación con el servidor.</div>'));
    });
});
</script>
<?php $scriptDinamico = ob_get_clean();
