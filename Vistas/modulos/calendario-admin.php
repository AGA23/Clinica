<?php
// ==============================================================================
// 1. SEGURIDAD Y PROCESAMIENTO
// ==============================================================================
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "Administrador") {
    echo '<script>window.location = "inicio";</script>';
    exit();
}

// Controladores
if (isset($_POST['crear_cita_secretario'])) { (new CitasC())->CrearCitaSecretarioC(); }
if (isset($_POST['editar_cita_secretario'])) { (new CitasC())->ActualizarCitaC(); }

// ==============================================================================
// 2. OBTENCIÓN DE DATOS
// ==============================================================================
require_once "Controladores/ConsultoriosC.php";
require_once "Controladores/PacientesC.php";

$lista_completa_consultorios = ConsultoriosC::ObtenerListaConsultoriosC();
$pacientes_agenda = PacientesC::ListarPacientesC() ?: [];
?>

<!-- ==============================================================================
     3. MEJORA VISUAL (CSS) - IGUAL QUE SECRETARIOS PERO PARA ADMIN
     ============================================================================== -->
<style>
    /* Eventos más grandes, con sombra y barra lateral de color */
    .fc-event {
        border: none !important;
        border-left: 5px solid rgba(0,0,0,0.3) !important;
        border-radius: 4px !important;
        padding: 6px !important;
        box-shadow: 0 3px 6px rgba(0,0,0,0.16);
        min-height: 65px !important; 
        margin-bottom: 4px !important;
    }
    /* Estilos de texto interno */
    .fc-time { font-weight: bold !important; font-size: 13px !important; display: block; margin-bottom: 2px; }
    .fc-title { font-weight: bold !important; font-size: 14px !important; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    /* Caja de detalles (Motivo y Doctor) */
    .event-details-box {
        margin-top: 5px;
        font-size: 11.5px;
        line-height: 1.3;
        border-top: 1px solid rgba(255,255,255,0.4);
        padding-top: 4px;
        color: #fff;
    }
    .event-details-box i { width: 15px; text-align: center; margin-right: 3px; }
    
    /* Espaciado para que no se peguen los eventos */
    .fc-day-grid-event { margin: 2px 5px !important; }
</style>

<link rel="stylesheet" href="Vistas/bower_components/fullcalendar/dist/fullcalendar.print.min.css" media="print">

<section class="content-header">
    <h1>Calendario Global <small>Administrador</small></h1>
    <ol class="breadcrumb">
        <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
        <li class="active">Calendario Global</li>
    </ol>
</section>

<section class="content">

    <?php if (isset($_SESSION['mensaje_calendario'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['tipo_mensaje_calendario']) ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <?= htmlspecialchars($_SESSION['mensaje_calendario']) ?>
        </div>
        <?php unset($_SESSION['mensaje_calendario'], $_SESSION['tipo_mensaje_calendario']); ?>
    <?php endif; ?>

    <!-- FILTROS SUPERIORES -->
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-filter"></i> Filtrar Calendario</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group">
                        <label>Filtrar por Consultorio:</label>
                        <select id="filtro-consultorio" class="form-control">
                            <option value="">-- Todos los Consultorios --</option>
                            <?php foreach($lista_completa_consultorios as $con): ?>
                                <option value="<?= $con['id'] ?>"><?= htmlspecialchars($con['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label>Filtrar por Doctor:</label>
                        <select id="filtro-doctor" class="form-control" disabled>
                            <option value="">-- Primero seleccione un consultorio --</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button id="limpiar-filtros" class="btn btn-default btn-block">Limpiar Filtros</button>
                </div>
            </div>
        </div>
    </div>

    <!-- CALENDARIO -->
    <div class="box box-solid">
        <div class="box-header with-border">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modal-nueva-cita-admin">
                <i class="fa fa-plus"></i> Nueva Cita
            </button>
        </div>
        <div class="box-body no-padding">
            <div id="calendario-global-admin"></div>
        </div>
    </div>

</section>

<!-- ==========================
 MODAL DETALLE CITA
 ========================== -->
<div class="modal fade" id="modal-detalle-cita-admin">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h4 class="modal-title">Detalles de la Cita</h4></div>
            <div class="modal-body">
                <dl class="dl-horizontal">
                    <dt>Paciente:</dt><dd id="detalle-paciente-admin"></dd>
                    <dt>Doctor:</dt><dd id="detalle-doctor-admin"></dd>
                    <dt>Consultorio:</dt><dd id="detalle-consultorio-admin"></dd>
                    <dt>Fecha y Hora:</dt><dd id="detalle-fecha-admin"></dd>
                    <dt>Motivo:</dt><dd id="detalle-motivo-admin"></dd>
                    <dt>Estado:</dt><dd id="detalle-estado-admin"></dd>
                    <dt>Cobertura:</dt><dd id="detalle-cobertura-admin">Cargando...</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning btn-editar-cita-admin">Editar / Reprogramar</button>
                <button type="button" class="btn btn-danger btn-cancelar-cita-admin">Cancelar Cita</button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================
 MODAL NUEVA CITA
 ========================== -->
<div class="modal fade" id="modal-nueva-cita-admin">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="redirect_url" value="calendario-admin">
                <div class="modal-header"><h4 class="modal-title">Agendar Nueva Cita (Admin)</h4></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Consultorio:</label>
                        <select name="id_consultorio" class="form-control select-consultorio-admin" required>
                            <option value="">Seleccione un consultorio...</option>
                            <?php foreach($lista_completa_consultorios as $con): ?>
                            <option value="<?= $con['id'] ?>"><?= htmlspecialchars($con['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Doctor:</label>
                        <select name="id_doctor" class="form-control select-doctor-admin" required disabled>
                            <option>Seleccione consultorio</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tratamiento (Motivo):</label>
                        <select name="id_tratamiento" class="form-control select-tratamiento-admin" required disabled>
                            <option>Seleccione consultorio</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Paciente:</label>
                        <select name="id_paciente" class="form-control select2 select-paciente-admin select-paciente-nueva-cita-admin" style="width:100%;" required>
                            <option value="">-- Buscar por Nombre o DNI --</option>
                            <?php foreach($pacientes_agenda as $pac): ?>
                            <option value="<?= $pac['id'] ?>">
                                <?= htmlspecialchars($pac['apellido'].' '.$pac['nombre']) ?> - DNI: <?= $pac['numero_documento'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cobertura:</label>
                        <select name="id_tipo_pago" class="form-control select-tipo-pago-admin" required disabled>
                            <option value="">Seleccione paciente primero...</option>
                        </select>
                        <input type="hidden" name="id_cobertura_aplicada" class="input-cobertura-aplicada-admin">
                    </div>
                    <div class="row">
                        <div class="col-md-5"><label>Fecha:</label><input type="date" name="fecha" class="form-control input-fecha-admin" required min="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-4"><label>Hora:</label><select name="hora_inicio" class="form-control select-hora-inicio-admin" required disabled></select></div>
                        <div class="col-md-3"><label>Fin:</label><input type="time" name="hora_fin" class="form-control input-hora-fin-admin" readonly></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary" name="crear_cita_secretario">Guardar Cita</button></div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================
 MODAL EDITAR CITA
 ========================== -->
<div class="modal fade" id="modal-editar-cita-admin">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="redirect_url" value="calendario-admin">
                <input type="hidden" name="id_cita_editar" id="id_cita_editar_admin">
                <div class="modal-header"><h4 class="modal-title">Editar / Reprogramar Cita</h4></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Consultorio:</label>
                        <select name="id_consultorio" id="consultorio_display_editar_admin" class="form-control select-consultorio-admin" required>
                            <?php foreach($lista_completa_consultorios as $con): ?>
                            <option value="<?= $con['id'] ?>"><?= htmlspecialchars($con['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Doctor:</label><select name="id_doctor_editar" id="id_doctor_editar_admin" class="form-control select-doctor-admin" required></select></div>
                    <div class="form-group"><label>Tratamiento:</label><select name="id_tratamiento_editar" id="id_tratamiento_editar_admin" class="form-control select-tratamiento-admin" required></select></div>
                    <div class="form-group">
                        <label>Paciente:</label>
                        <select name="id_paciente_editar" id="id_paciente_editar_admin" class="form-control select2" required>
                            <?php foreach($pacientes_agenda as $pac): ?>
                            <option value="<?= $pac['id'] ?>"><?= htmlspecialchars($pac['apellido'].' '.$pac['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cobertura:</label>
                        <select name="id_tipo_pago_editar" id="id_tipo_pago_editar_admin" class="form-control select-tipo-pago-admin" required></select>
                        <input type="hidden" name="id_cobertura_aplicada_editar" id="id_cobertura_aplicada_editar_admin" class="input-cobertura-aplicada-admin">
                    </div>
                    <div class="row">
                        <div class="col-md-5"><label>Fecha:</label><input type="date" name="fecha_editar" id="fecha_editar_admin" class="form-control input-fecha-admin" required></div>
                        <div class="col-md-4"><label>Hora:</label><select name="hora_inicio_editar" id="hora_inicio_editar_admin" class="form-control select-hora-inicio-admin" required></select></div>
                        <div class="col-md-3"><label>Fin:</label><input type="time" name="hora_fin_editar" id="hora_fin_editar_admin" class="form-control" readonly></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" name="editar_cita_secretario" class="btn btn-primary">Guardar Cambios</button></div>
            </form>
        </div>
    </div>
</div>