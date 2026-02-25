<?php
// En Vistas/modulos/historia-clinica.php

// 1. SEGURIDAD: Solo personal autorizado puede ver esto
if (!isset($_SESSION["rol"]) || !in_array($_SESSION['rol'], ['Doctor', 'Secretario', 'Administrador'])) {
    exit("Acceso no autorizado.");
}

// 2. CARGA DE DATOS
$id_paciente = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_paciente) {
    echo "<section class='content'><div class='alert alert-danger'>ID de paciente no válido.</div></section>";
    return;
}
$paciente = (new PacientesC())->VerPacientePorIdC($id_paciente);
if (!$paciente) {
    echo "<section class='content'><div class='alert alert-danger'>Paciente no encontrado.</div></section>";
    return;
}

$alergias_paciente = PacientesM::ObtenerCondicionesClinicasM($paciente['id'], 'alergia');
$enfermedades_paciente = PacientesM::ObtenerCondicionesClinicasM($paciente['id'], 'enfermedad');
?>

<section class="content-header">
    <h1>Historia Clínica de: <?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']) ?></h1>
    <ol class="breadcrumb">
        <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
        <li><a href="mis-pacientes">Mis Pacientes</a></li>
        <li class="active">Historia Clínica</li>
    </ol>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-warning"></i> Alergias Registradas</h3></div>
                <div class="box-body">
                    <?php if (empty($alergias_paciente)): ?>
                        <p class="text-muted">El paciente no ha registrado alergias.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($alergias_paciente as $item): ?>
                                <li class="list-group-item">
                                    <?= htmlspecialchars($item['nombre']) ?>
                                    <div class="pull-right">
                                        <?php if ($item['fecha_verificacion']): ?>
                                            <span class="label label-success"><i class="fa fa-check-circle"></i> Verificado</span>
                                        <?php else: ?>
                                            <span class="label label-warning">Sin Verificar</span>
                                            <?php if ($_SESSION['rol'] === 'Doctor'): ?>
                                                <button class="btn btn-xs btn-success btn-validar-condicion" data-condicion-id="<?= $item['id'] ?>" data-paciente-id="<?= $paciente['id'] ?>" title="Marcar como verificado"><i class="fa fa-check"></i></button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
             <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-medkit"></i> Enfermedades Preexistentes</h3></div>
                <div class="box-body">
                     <?php if (empty($enfermedades_paciente)): ?>
                        <p class="text-muted">El paciente no ha registrado enfermedades preexistentes.</p>
                    <?php else: ?>
                        <ul class="list-group">
                             <?php foreach ($enfermedades_paciente as $item): ?>
                                <li class="list-group-item">
                                    <?= htmlspecialchars($item['nombre']) ?>
                                    <div class="pull-right">
                                        <?php if ($item['fecha_verificacion']): ?>
                                            <span class="label label-success"><i class="fa fa-check-circle"></i> Verificado</span>
                                        <?php else: ?>
                                            <span class="label label-warning">Sin Verificar</span>
                                            <?php if ($_SESSION['rol'] === 'Doctor'): ?>
                                                 <button class="btn btn-xs btn-success btn-validar-condicion" data-condicion-id="<?= $item['id'] ?>" data-paciente-id="<?= $paciente['id'] ?>" title="Marcar como verificado"><i class="fa fa-check"></i></button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($_SESSION['rol'] === 'Doctor'): ?>
<?php ob_start(); ?>
<script>
$(function(){
    // Se usa delegación de eventos en '.box-body' para asegurar que funcione siempre
    $('.box-body').on('click', '.btn-validar-condicion', function() {
        var btn = $(this);
        var condicionId = btn.data('condicion-id');
        var pacienteId = btn.data('paciente-id');
        
        Swal.fire({
            title: '¿Confirmar Verificación?',
            text: "Esta acción marcará esta condición como clínicamente verificada.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, verificar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // La llamada AJAX usa la ruta correcta para tu enrutador
                $.post('<?= BASE_URL ?>index.php?url=ajax/pacientes', { 
                    action: 'validarCondicionDoctor', 
                    id_paciente: pacienteId, 
                    id_condicion: condicionId 
                })
                .done(function(r){
                    if(r.success) {
                        Swal.fire('¡Verificado!', 'La condición ha sido marcada como verificada.', 'success');
                        
                        // [CORREGIDO] Lógica de actualización visual más robusta.
                        // Obtenemos el contenedor 'div.pull-right'
                        var container = btn.parent();
                        // Reemplazamos todo el contenido de ese div con la nueva etiqueta.
                        container.html('<span class="label label-success"><i class="fa fa-check-circle"></i> Verificado</span>');
                    } else {
                        Swal.fire('Error', r.error || 'No se pudo completar la acción.', 'error');
                    }
                }).fail(function() {
                    Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
                });
            }
        });
    });
});
</script>
<?php $scriptDinamico = ob_get_clean(); ?>
<?php endif; ?>