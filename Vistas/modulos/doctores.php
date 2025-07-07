<div class="content-wrapper">
    <section class="content-header">
        <h1>Gestión de Doctores</h1>
    </section>
    <script src="/clinica/Vistas/js/horarios.js"></script>
    <section class="content">
        <div class="box">
            <div class="box-header">
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalCrearDoctor">
                    <i class="fa fa-plus"></i> Nuevo Doctor
                </button>
            </div>

            <div class="box-body">
                <table id="tablaDoctores" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Consultorio</th>
                            <th>Horarios</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($doctores as $doctor): ?>
                        <tr>
                            <td><?= $doctor['id'] ?></td>
                            <td><?= htmlspecialchars($doctor['nombre'].' '.$doctor['apellido']) ?></td>
                            <td><?= htmlspecialchars($doctor['consultorio'] ?? 'Sin asignar') ?></td>
                            <td>
                                <button class="btn btn-info btn-xs btn-horarios" 
                                        data-id="<?= $doctor['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($doctor['nombre'].' '.$doctor['apellido']) ?>">
                                    <i class="fa fa-clock-o"></i> Ver Horarios
                                </button>
                            </td>
                            <td>
                                <!-- Botones de acciones -->
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- Modal para gestionar horarios -->
<div class="modal fade" id="modalHorariosDoctor">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Horarios del Doctor: <span id="nombreDoctorHorario"></span></h4>
            </div>
            <div class="modal-body">
                <div id="contenedorHorarios">
                    <!-- Aquí se cargarán los horarios dinámicamente -->
                </div>
                <button class="btn btn-success" id="btnAgregarHorario">
                    <i class="fa fa-plus"></i> Agregar Horario
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarHorarios">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    // Gestión de horarios
    $('.btn-horarios').click(function() {
        var idDoctor = $(this).data('id');
        var nombreDoctor = $(this).data('nombre');
        
        $('#nombreDoctorHorario').text(nombreDoctor);
        $('#modalHorariosDoctor').data('id-doctor', idDoctor);
        
        // Cargar horarios existentes
        $.ajax({
            url: 'Controladores/DoctoresC.php?action=ObtenerHorariosDoctor',
            type: 'POST',
            data: {id_doctor: idDoctor},
            success: function(horarios) {
                var html = '';
                if (horarios.length > 0) {
                    horarios.forEach(function(horario) {
                        html += generarFilaHorario(horario);
                    });
                } else {
                    html = '<p class="text-muted">No hay horarios asignados</p>';
                }
                $('#contenedorHorarios').html(html);
            }
        });
        
        $('#modalHorariosDoctor').modal('show');
    });

    // Generar fila de horario
    function generarFilaHorario(horario) {
        return `<div class="row fila-horario" data-id="${horario.id || 'new'}">
            <div class="col-md-3">
                <select class="form-control dia-semana">
                    <option value="1" ${horario.dia_semana==1?'selected':''}>Lunes</option>
                    <!-- otros días -->
                </select>
            </div>
            <div class="col-md-3">
                <input type="time" class="form-control hora-inicio" value="${horario.hora_inicio}">
            </div>
            <div class="col-md-3">
                <input type="time" class="form-control hora-fin" value="${horario.hora_fin}">
            </div>
            <div class="col-md-3">
                <button class="btn btn-danger btn-eliminar-horario">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>`;
    }
});
</script>