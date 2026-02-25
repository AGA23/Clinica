<?php
// En Controladores/InicioC.php (VERSIÓN FINAL, COMPLETA Y FUNCIONAL)

class InicioC
{
    /**
     * Punto de entrada principal para el módulo 'inicio'.
     */
    public function MostrarInicioC()
    {
        // Comprobar si se está enviando el formulario de bloqueo del dashboard del doctor
        if (isset($_POST["accion"]) && $_POST["accion"] === "bloquear_horario") {
    
            // 1. Asegurar que el controlador de bloqueos esté cargado
            // CORRECCIÓN APLICADA: Usar __DIR__ para la ruta
            
            
            // 2. Llamar al controlador y método correctos
            $controladorBloqueos = new BloqueosC();
            $controladorBloqueos->CrearBloqueoDoctorC(); // Este método pondrá los mensajes en la sesión

            // 3. Redirigir siempre a 'inicio' para limpiar el POST y mostrar el mensaje
            echo '<script>window.location = "inicio";</script>';
            exit(); 
        }
    
        if (!isset($_SESSION['rol'])) { return; }

        switch ($_SESSION['rol']) {
            case 'Administrador': $this->MostrarDashboardAdmin(); break;
            case 'Secretario':    $this->MostrarDashboardSecretario(); break;
            case 'Doctor':        $this->MostrarDashboardDoctor(); break;
            case 'Paciente':      $this->MostrarDashboardPaciente(); break;
            default:              $this->MostrarBienvenidaGenerica(); break;
        }
    }
    // --- 1. DASHBOARD DEL ADMINISTRADOR ---
     private function MostrarDashboardAdmin() {
       
        // Llamamos a nuestro nuevo método en el modelo para obtener las estadísticas reales.
        $stats = InicioM::ObtenerEstadisticasAdminM();

       
        echo <<<HTML
        <section class="content-header">
            <h1>Panel de Control del Sistema</h1>
            <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Admin</li></ol>
        </section>
        <section class="content">
            <div class="row">
                <div class="col-lg-3 col-xs-6">
                    <div class="small-box bg-aqua">
                        <div class="inner">
                            <h3>{$stats['doctores']}</h3>
                            <p>Doctores Registrados</p>
                        </div>
                        <div class="icon"><i class="fa fa-user-md"></i></div>
                        <a href="doctores" class="small-box-footer">Ver más <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-xs-6">
                    <div class="small-box bg-green">
                        <div class="inner">
                            <h3>{$stats['pacientes']}</h3>
                            <p>Pacientes Totales</p>
                        </div>
                        <div class="icon"><i class="fa fa-users"></i></div>
                        <a href="pacientes" class="small-box-footer">Ver más <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-xs-6">
                    <div class="small-box bg-yellow">
                        <div class="inner">
                            <h3>{$stats['secretarios']}</h3>
                            <p>Personal de Recepción</p>
                        </div>
                        <div class="icon"><i class="fa fa-id-card-o"></i></div>
                        <a href="secretarios" class="small-box-footer">Ver más <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-xs-6">
                    <div class="small-box bg-red">
                        <div class="inner">
                            <h3>{$stats['citas_semana']}</h3>
                            <p>Citas (Últimos 7 días)</p>
                        </div>
                        <div class="icon"><i class="fa fa-calendar-check-o"></i></div>
                        <a href="citas-log" class="small-box-footer">Ver historial <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">Acciones Principales de Administración</h3></div>
                <div class="box-body text-center">
                    <a href="doctores" class="btn btn-app"><i class="fa fa-user-md"></i> Gestionar Doctores</a>
                    <a href="pacientes" class="btn btn-app"><i class="fa fa-users"></i> Gestionar Pacientes</a>
                    <a href="secretarios" class="btn btn-app"><i class="fa fa-id-card-o"></i> Gestionar Recepción</a>
                    <a href="consultorios" class="btn btn-app"><i class="fa fa-hospital-o"></i> Gestionar Consultorios</a>
                    <a href="citas-log" class="btn btn-app"><i class="fa fa-history"></i> Historial de Citas</a>
                </div>
            </div>
        </section>
HTML;
    }

    // --- 2. DASHBOARD DEL SECRETARIO (MEJORADO Y MODIFICADO) ---
    private function MostrarDashboardSecretario() {
        $datos = InicioM::ObtenerDatosDashboardSecretario();
        $etiquetaEstadoCita = fn($e) => "<span class='label label-" . (["Completada"=>"success", "Cancelada"=>"danger", "Pendiente"=>"warning"][$e] ?? 'default') . "'>" . htmlspecialchars($e) . "</span>";
        
        echo <<<HTML
        <section class="content-header">
            <h1>Panel de Recepción<small>Centro de Operaciones</small></h1>
            <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Recepción</li></ol>
        </section>
        <section class="content">
            <div class="row">
                <div class="col-md-7">
                    <div class="box box-info">
                        <div class="box-header with-border"><h3 class="box-title">Agenda del Día</h3></div>
                        <div class="box-body table-responsive no-padding">
                            <table class="table table-hover">
                                <thead><tr><th>Hora</th><th>Paciente / Evento</th><th>Doctor</th><th>Estado / Motivo</th></tr></thead>
                                <tbody>
HTML;
        if (empty($datos['agenda_dia'])) { 
            echo '<tr><td colspan="4" class="text-center">No hay actividad programada para hoy.</td></tr>'; 
        } else { 
            // --- INICIO DE LA MODIFICACIÓN ---
            // Iteramos sobre todos los eventos (citas y bloqueos)
            foreach($datos['agenda_dia'] as $evento) { 
                
                // Verificamos si es un bloqueo usando la misma bandera que el dashboard del doctor
                if (isset($evento['nyaP']) && $evento['nyaP'] === 'BLOQUEADO') {
                    // Es un BLOQUEO
                    echo '<tr class="danger">'; // Estilo rojo para el bloqueo
                    echo '<td>'.date("H:i A", strtotime($evento['inicio'])).'</td>';
                    echo '<td><strong><i class="fa fa-lock"></i> HORARIO BLOQUEADO</strong></td>';
                    echo '<td>Dr(a). '.htmlspecialchars($evento['doctor']).'</td>'; 
                    echo '<td><span class="label label-danger">' . htmlspecialchars($evento['motivo']) . '</span></td>';
                    echo '</tr>';
                } else {
                    // Es una CITA (comportamiento original)
                    echo '<tr>';
                    echo '<td>'.date("H:i A", strtotime($evento['inicio'])).'</td>';
                    echo '<td>'.htmlspecialchars($evento['nyaP']).'</td>';
                    echo '<td>Dr(a). '.htmlspecialchars($evento['doctor']).'</td>';
                    echo '<td>' . $etiquetaEstadoCita($evento['estado']) . '</td>';
                    echo '</tr>';
                }
            } 
            // --- FIN DE LA MODIFICACIÓN ---
        }
        echo <<<HTML
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="row">
                        <div class="col-sm-6 col-xs-12"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-calendar"></i></span><div class="info-box-content"><span class="info-box-text">Citas de Hoy</span><span class="info-box-number">{$datos['citas_hoy']}</span></div></div></div>
                        <div class="col-sm-6 col-xs-12"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-phone"></i></span><div class="info-box-content"><span class="info-box-text">Citas Futuras</span><span class="info-box-number">{$datos['citas_pendientes']}</span></div></div></div>
                    </div>
                    <div class="box box-primary">
                        <div class="box-header with-border"><h3 class="box-title">Acciones Principales</h3></div>
                        <div class="box-body text-center">
                            <a href="calendario-citas" class="btn btn-app"><i class="fa fa-calendar-plus-o"></i> Agendar Cita</a>
                            <a href="pacientes" class="btn btn-app"><i class="fa fa-user-plus"></i> Gestionar Pacientes</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
HTML;
    }

        private function MostrarDashboardDoctor() {
        $id_doctor = $_SESSION['id'];
        $datos = InicioM::ObtenerDatosDashboardDoctor($id_doctor);
        
        $datos_grafico = is_array($datos['grafico_citas']) ? $datos['grafico_citas'] : [];
        $meses_grafico = json_encode(array_column($datos_grafico, 'mes'));
        $totales_grafico = json_encode(array_column($datos_grafico, 'total'));
        
        $siguiente_evento = !empty($datos['lista_citas_hoy']) ? $datos['lista_citas_hoy'][0] : null;

        $presentaciones_agrupadas = MedicamentosM::ObtenerPresentacionesAprobadasAgrupadasM();
        $tratamientos_disponibles = DoctoresM::ObtenerTratamientosPorDoctorM($id_doctor);

        
        $ajax_url_citas = BASE_URL . "ajax/citas";
        $script_final = "
        <script>
        $(function () { 
            var ajaxUrl = '{$ajax_url_citas}';
            var barChartCanvas = $('#barChart').get(0).getContext('2d'); 
            new Chart(barChartCanvas, { 
                type:'bar', 
                data:{ 
                    labels: {$meses_grafico}, 
                    datasets: [{ 
                        label:'Citas Completadas', 
                        backgroundColor:'rgba(60,148,188,0.9)', 
                        data: {$totales_grafico} 
                    }] 
                }, 
                options:{ responsive:true, maintainAspectRatio:false, scales:{ yAxes:[{ticks:{beginAtZero:true, callback:function(v){if(v%1===0){return v;}}}}]}}
            }); 
            
            $('.btnFinalizarCita').on('click', function(){
                $('#id_cita_finalizar').val($(this).data('id-cita'));
                $('#motivo_finalizar').val($(this).data('motivo'));
            });

            $('body').on('click', '.btn-desbloquear-horario', function() {
                var idCitaBloqueo = $(this).data('id-cita');
                Swal.fire({
                    title: '¿Eliminar este Bloqueo?',
                    text: \"El horario volverá a estar disponible.\",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, ¡eliminar!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(ajaxUrl, {
                            action: 'eliminarBloqueo',
                            id_cita: idCitaBloqueo
                        })
                        .done(function(r) {
                            if (r.success) {
                                Swal.fire('¡Eliminado!', 'El bloqueo ha sido eliminado.', 'success').then(() => { location.reload(); });
                            } else {
                                Swal.fire('Error', r.error || 'No se pudo eliminar el bloqueo.', 'error');
                            }
                        }).fail(function(jqXHR) {
                            console.error(\"Fallo AJAX al desbloquear. Respuesta del servidor:\", jqXHR.responseText);
                            Swal.fire('Error', 'Error de comunicación. Revisa la consola.', 'error');
                        });
                    }
                });
            });
        });
        </script>
        ";

        echo <<<HTML
        <section class="content-header">
            <h1>Dashboard<small>Panel de Control</small></h1>
        </section>
        <section class="content">
            <div class="row">
                <div class="col-lg-4 col-xs-6"><div class="small-box bg-aqua"><div class="inner"><h3>{$datos['citas_hoy']}</h3><p>Citas para Hoy</p></div><div class="icon"><i class="fa fa-calendar-check-o"></i></div></div></div>
                <div class="col-lg-4 col-xs-6"><div class="small-box bg-green"><div class="inner"><h3>{$datos['citas_proximas']}</h3><p>Próximas Citas</p></div><div class="icon"><i class="fa fa-calendar-plus-o"></i></div></div></div>
                <div class="lg-4 col-xs-6"><div class="small-box bg-yellow"><div class="inner"><h3>{$datos['pacientes_atendidos']}</h3><p>Pacientes Históricos</p></div><div class="icon"><i class="fa fa-users"></i></div></div></div>
            </div>
            <div class="row">
                <div class="col-md-7">
                    <div class="box box-primary">
                        <div class="box-header with-border"><h3 class="box-title">Panel de Acción Principal</h3></div>
                        <div class="box-body">
HTML;
        if ($siguiente_evento) {
            if ($siguiente_evento['nyaP'] === 'BLOQUEADO') {
                echo '<p><strong>Próximo Evento:</strong> <span class="text-danger">HORARIO BLOQUEADO</span> a las ' . date("H:i A", strtotime($siguiente_evento['inicio'])) . '</p>';
                echo '<p><strong>Motivo:</strong> ' . htmlspecialchars($siguiente_evento['motivo']) . '</p>';
                echo '<button class="btn btn-lg btn-danger btn-block btn-desbloquear-horario" data-id-cita="' . $siguiente_evento['id'] . '"><i class="fa fa-unlock"></i> Desbloquear Horario</button>';
            } else {
                echo '<p><strong>Siguiente Consulta:</strong> ' . htmlspecialchars($siguiente_evento['nyaP']) . ' a las ' . date("H:i A", strtotime($siguiente_evento['inicio'])) . '</p>';
                echo '<button class="btn btn-lg btn-success btn-block btnFinalizarCita" data-toggle="modal" data-target="#modalFinalizarCita" data-id-cita="' . $siguiente_evento['id'] . '" data-motivo="' . htmlspecialchars($siguiente_evento['motivo']) . '"><i class="fa fa-play-circle"></i> Iniciar y Finalizar Consulta</button>';
            }
        } else {
            echo '<p class="text-muted">No tiene más eventos programados para hoy.</p>';
        }
        echo <<<HTML
                        <hr>
                        <button class="btn btn-warning pull-right" data-toggle="modal" data-target="#modalBloquearHorario"><i class="fa fa-lock"></i> Planificar Bloqueo</button>
                    </div>
                </div>

                <div class="box box-danger" style="margin-top: 20px;">
                    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-lock"></i> Mis Horarios Bloqueados (Futuros)</h3></div>
                    <div class="box-body no-padding">
                        <table class="table table-condensed">
                            <thead><tr><th>Fecha y Hora</th><th>Motivo</th><th style="width: 40px">Acción</th></tr></thead>
                            <tbody>
HTML;
    if (empty($datos['bloqueos_futuros'])) {
        echo '<tr><td colspan="3" class="text-center text-muted">No tiene horarios bloqueados planificados.</td></tr>';
    } else {
        foreach ($datos['bloqueos_futuros'] as $bloqueo) {
            echo '<tr><td><span class="label label-danger">' . date("d/m/Y H:i", strtotime($bloqueo['inicio'])) . '</span></td><td>' . htmlspecialchars($bloqueo['motivo']) . '</td><td><button class="btn btn-xs btn-default btn-desbloquear-horario" data-id-cita="' . $bloqueo['id'] . '" title="Eliminar este bloqueo"><i class="fa fa-unlock"></i></button></td></tr>';
        }
    }
    echo <<<HTML
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="box box-danger"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-bell"></i> Alertas</h3></div><div class="box-body"><div class="alert alert-info" style="margin-bottom:0;">No hay nuevas alertas.</div></div></div>
                <div class="box box-success" style="margin-top: 20px;"><div class="box-header with-border"><h3 class="box-title">Actividad (Últimos 6 Meses)</h3></div><div class="box-body"><div class="chart"><canvas id="barChart" style="height:230px"></canvas></div></div></div>
            </div>
        </div>
    </section>

    <!-- Modal para Bloquear Horario -->
    <div class="modal fade" id="modalBloquearHorario">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header bg-yellow"><h4 class="modal-title">Planificar Bloqueo de Horario</h4></div>
                    <div class="modal-body">
                        <p>Crear un bloqueo en su agenda. Nadie podrá agendar citas en este lapso.</p>
                        <input type="hidden" name="accion" value="bloquear_horario">
                        <div class="form-group">
                            <label>Seleccione el Día</label>
                            <select name="bloqueo_fecha" class="form-control" required>
HTML;
    $hoy = new DateTime(); $dias_mostrados = 0; $dias_a_mostrar = 7;
    $dias_semana_es = ["", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"];
    while ($dias_mostrados < $dias_a_mostrar) {
        if ($hoy->format('N') >= 1 && $hoy->format('N') <= 6) {
            $fecha_valor = $hoy->format('Y-m-d');
            $fecha_texto = $dias_semana_es[$hoy->format('N')] . ' ' . $hoy->format('d/m');
            echo "<option value='{$fecha_valor}'>{$fecha_texto}</option>";
            $dias_mostrados++;
        }
        $hoy->modify('+1 day');
    }
    echo <<<HTML
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-xs-6"><div class="form-group"><label>Hora de Inicio</label><input type="time" name="bloqueo_inicio" class="form-control" required></div></div>
                            <div class="col-xs-6"><div class="form-group"><label>Hora de Fin</label><input type="time" name="bloqueo_fin" class="form-control" required></div></div>
                        </div>
                        <div class="form-group"><label>Motivo del Bloqueo</label><input type="text" name="bloqueo_motivo" class="form-control" placeholder="Ej: Conferencia, trámite personal..." required></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-warning">Confirmar Bloqueo</button></div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal para Finalizar Cita -->
    <div class="modal fade" id="modalFinalizarCita">
        <div class="modal-dialog modal-lg" role="document">
            <form method="post" action="citas_doctor">
                <div class="modal-content">
                    <div class="modal-header bg-green"><h4 class="modal-title">Finalizar Cita</h4></div>
                    <div class="modal-body">
                        <input type="hidden" name="id_cita_finalizar" id="id_cita_finalizar">
                        <div class="form-group"><label>Motivo Final de la Consulta</label><input type="text" class="form-control" name="motivo" id="motivo_finalizar" required></div>
                        <div class="form-group"><label>Diagnóstico General / Observaciones</label><textarea class="form-control" name="observaciones" id="observaciones_finalizar" rows="3" required></textarea></div>
                        <div class="form-group"><label>Peso del paciente (kg)</label><input type="number" step="0.1" class="form-control" name="peso" id="peso_finalizar" min="1" max="500"></div>
                        <div class="form-group"><label>Presión Arterial (ej. 120/80)</label><input type="text" class="form-control" name="presion_arterial" placeholder="Sistólica/Diastólica"></div>
                        <hr><h5 style="font-weight: bold;">Receta Médica</h5>
                        <div class="form-group">
                            <label>Seleccionar Medicamento (Fármaco y Presentación)</label>
                            <select class="form-control" name="medicamentos[]" id="medicamentos" multiple style="width: 100%;">
HTML;
    foreach ($presentaciones_agrupadas as $farmaco => $presentaciones) {
        echo '<optgroup label="' . htmlspecialchars($farmaco) . '">';
        foreach ($presentaciones as $pres) {
            echo '<option value="' . $pres['id'] . '">' . htmlspecialchars($pres['presentacion']) . '</option>';
        }
        echo '</optgroup>';
    }
    echo <<<HTML
                            </select>
                        </div>
                        <hr>
                        <h5 style="font-weight: bold;">Tratamientos Aplicados</h5>
                        <div class="form-group">
                            <select class="form-control" name="tratamientos[]" id="tratamientos" multiple style="width: 100%;">
HTML;
    foreach ($tratamientos_disponibles as $trat) {
        echo '<option value="' . $trat['id'] . '">' . htmlspecialchars($trat['nombre']) . '</option>';
    }
    echo <<<HTML
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="guardar_finalizacion" class="btn btn-success">Guardar Finalización</button></div>
                </div>
            </form>
        </div>
    </div>
HTML;
        echo $script_final; // Imprime el bloque de script al final
    }


    // --- 4. DASHBOARD DEL PACIENTE (SIN CAMBIOS FUNCIONALES, SOLO CÓDIGO LIMPIO) ---
   private function MostrarDashboardPaciente() {
    // --- 1. OBTENCIÓN DE DATOS ---
    $id_paciente = $_SESSION['id'];
    
    // Obtenemos los datos globales de la clínica (para el logo y el nombre principal)
    $info_clinica = ClinicaM::ObtenerDatosGlobalesM();
    
    // Obtenemos las próximas 3 citas del paciente
    $proximas_citas = CitasM::ObtenerProximasCitasPaciente($id_paciente, 3);
    
    // [NUEVO] Obtenemos la lista de TODAS las sedes/consultorios
    $sedes = ConsultoriosM::ListarTodosLosConsultoriosM();

    // --- 2. RENDERIZADO DE LA VISTA ---
    ?>

    <section class="content-header">
        <h1>Portal del Paciente</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Portal</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <!-- Columna Principal: Próximas Citas -->
            <div class="col-md-8">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calendar"></i> Mis Próximas Citas</h3>
                    </div>
                    <div class="box-body">
                        <?php if (empty($proximas_citas)): ?>
                            <div class="alert alert-info">
                                <h4><i class="icon fa fa-info-circle"></i> Sin Citas Pendientes</h4>
                                No tienes citas próximas programadas.
                            </div>
                        <?php else: ?>
                            <?php foreach ($proximas_citas as $cita): ?>
                                <div class="box box-widget widget-user-2" style="margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <div class="widget-user-header bg-green">
                                        <div class="widget-user-image">
                                            <img class="img-circle" src="<?= !empty($cita['foto_doctor']) ? BASE_URL . $cita['foto_doctor'] : BASE_URL . 'Vistas/img/user-default.png' ?>" alt="Foto Doctor">
                                        </div>
                                        <h3 class="widget-user-username"><?= htmlspecialchars($cita['nombre_doctor']) ?></h3>
                                        <h5 class="widget-user-desc"><?= htmlspecialchars($cita['nombre_consultorio']) ?></h5>
                                    </div>
                                    <div class="box-footer no-padding">
                                        <ul class="nav nav-stacked">
                                            <li><a><strong>Fecha:</strong> <span class="pull-right badge bg-blue"><?= date("d/m/Y H:i", strtotime($cita['inicio'])) ?> hs</span></a></li>
                                            <li><a><strong>Motivo:</strong> <span class="pull-right"><?= htmlspecialchars($cita['motivo']) ?></span></a></li>
                                        </ul>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Columna Lateral: Información de la Clínica y Sedes -->
            <div class="col-md-4">
                <!-- [NUEVO] Widget de Información de Sedes -->
                <div class="box box-solid">
                    <div class="box-header with-border text-center">
                        <?php if(!empty($info_clinica['logo_clinica'])): ?>
                            <img src="<?= BASE_URL . htmlspecialchars($info_clinica['logo_clinica']) ?>" style="max-height: 200px; margin-bottom: 10px;" alt="Logo Clínica">
                        <?php endif; ?>
                        <h3 class="box-title" style="display: block; width: 100%;"><?= htmlspecialchars($info_clinica['nombre_clinica']) ?></h3>
                    </div>
                    <div class="box-body">
                        <?php if(empty($sedes)): ?>
                            <p class="text-muted">No hay información de sedes disponible.</p>
                        <?php else: ?>
                            <?php foreach($sedes as $sede): ?>
                                <div style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f4f4f4;">
                                    <strong><i class="fa fa-hospital-o margin-r-5"></i> <?= htmlspecialchars($sede['nombre']) ?></strong>
                                    <address style="margin-left: 20px; margin-top: 5px; color: #666;">
                                        <i class="fa fa-map-marker"></i> <?= htmlspecialchars($sede['direccion'] ?? 'Dirección no disponible') ?><br>
                                        <i class="fa fa-phone"></i> <?= htmlspecialchars($sede['telefono'] ?? 'Teléfono no disponible') ?><br>
                                        <i class="fa fa-envelope"></i> <a href="mailto:<?= htmlspecialchars($sede['email'] ?? '') ?>"><?= htmlspecialchars($sede['email'] ?? 'Email no disponible') ?></a>
                                    </address>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- [NUEVO] Widget de "Call to Action" -->
                <div class="callout callout-info">
                    <h4>¿Necesitas una Cita?</h4>
                    <p>Para solicitar un nuevo turno, por favor póngase en contacto con la sede de su preferencia a través de los datos de contacto indicados arriba.</p>
                </div>
            </div>
        </div>
    </section>

 <style>
        
        .callout.callout-info {
            background-color: #f4f6f9 !important; /* Un fondo gris muy claro, casi blanco */
            border-left: 5px solid #00c0ef !important; /* Mantenemos el borde cian para identificarlo como 'info' */
            color: #333 !important; /* Texto principal oscuro para MÁXIMO contraste y legibilidad */
        }

        /* Damos un color específico al título para que destaque */
        .callout.callout-info h4 {
            color: #0073b7; /* Un azul más oscuro y legible */
        }
    </style>

    <?php
}

    // --- BIENVENIDA GENÉRICA ---
    private function MostrarBienvenidaGenerica() {
        $resultado = InicioM::MostrarInicioM("inicio", "1");
        echo '<section class="content-header"><h1>Bienvenido/a</h1></section><section class="content"><h3>' . htmlspecialchars($resultado["intro"]) . '</h3></section>';
    }
}