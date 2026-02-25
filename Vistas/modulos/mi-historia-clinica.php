<?php
// En Vistas/modulos/mi-historia-clinica.php

// 1. SEGURIDAD
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "Paciente") { exit("Acceso no autorizado."); }

// 2. LÓGICA DE ACTUALIZACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_info_clinica'])) {
    (new PacientesC())->ActualizarInfoClinicaC();
}

// 3. CARGA DE DATOS
$paciente = (new PacientesC())->VerPerfilPacienteC();
if (!$paciente) {
    echo "<section class='content'><div class='alert alert-danger'>Error al cargar datos.</div></section>";
    return;
}

// Obtenemos las listas de condiciones
$alergias_paciente = PacientesM::ObtenerCondicionesClinicasM($paciente['id'], 'alergia');
$enfermedades_paciente = PacientesM::ObtenerCondicionesClinicasM($paciente['id'], 'enfermedad');

// Separamos las condiciones verificadas de las no verificadas para la vista
$alergias_verificadas = array_filter($alergias_paciente, fn($a) => $a['fecha_verificacion'] !== null);
$alergias_editables = array_filter($alergias_paciente, fn($a) => $a['fecha_verificacion'] === null);
$enfermedades_verificadas = array_filter($enfermedades_paciente, fn($e) => $e['fecha_verificacion'] !== null);
$enfermedades_editables = array_filter($enfermedades_paciente, fn($e) => $e['fecha_verificacion'] === null);
?>

<section class="content-header">
    <h1>Mi Historia Clínica</h1>
    <p class="lead">Esta información es confidencial y será revisada por su médico en su próxima consulta.</p>
</section>

<section class="content">
    <?php
    if (isset($_SESSION['mensaje_historia_clinica'])) {
        echo '<div class="alert alert-' . $_SESSION['tipo_mensaje_historia_clinica'] . ' alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $_SESSION['mensaje_historia_clinica'] . '</div>';
        unset($_SESSION['mensaje_historia_clinica'], $_SESSION['tipo_mensaje_historia_clinica']);
    }
    ?>
    <form method="post" action="<?= BASE_URL ?>index.php?url=mi-historia-clinica">
        <input type="hidden" name="actualizar_info_clinica" value="ok">
        
        <!-- Panel de Alergias -->
        <div class="box box-danger">
            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-warning"></i> Alergias Conocidas</h3></div>
            <div class="box-body">
                <?php if (!empty($alergias_verificadas)): ?>
                    <label>Alergias Clínicamente Verificadas:</label>
                    <div class="well well-sm" style="background-color: #f9f9f9;">
                        <?php foreach ($alergias_verificadas as $item): ?>
                            <span class="label label-success" style="font-size: 14px; margin: 2px;"><?= htmlspecialchars($item['nombre']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <p class="help-block">Esta información no puede ser modificada. Para realizar cambios, contacte a la clínica.</p>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Alergias Reportadas por Usted (Pendientes de Revisión):</label>
                    <select class="form-control select2-tags" name="alergias[]" multiple="multiple" style="width: 100%;">
                        <?php foreach ($alergias_editables as $item): ?>
                            <option value="<?= htmlspecialchars($item['nombre']) ?>" selected="selected"><?= htmlspecialchars($item['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="help-block">Escriba una alergia y presione Enter o Coma para añadirla a la lista. Serán revisadas en su próxima consulta.</p>
                </div>
            </div>
        </div>

        <!-- Panel de Enfermedades Preexistentes -->
        <div class="box box-primary">
             <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-medkit"></i> Enfermedades Preexistentes</h3></div>
             <div class="box-body">
                <?php if (!empty($enfermedades_verificadas)): ?>
                    <label>Condiciones Clínicamente Verificadas:</label>
                    <div class="well well-sm" style="background-color: #f9f9f9;">
                        <?php foreach ($enfermedades_verificadas as $item): ?>
                            <span class="label label-success" style="font-size: 14px; margin: 2px;"><?= htmlspecialchars($item['nombre']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <p class="help-block">Esta información no puede ser modificada.</p>
                <?php endif; ?>

                <div class="form-group">
                    <label>Condiciones Reportadas por Usted (Pendientes de Revisión):</label>
                    <select class="form-control select2-tags" name="enfermedades[]" multiple="multiple" style="width: 100%;">
                        <?php foreach ($enfermedades_editables as $item): ?>
                            <option value="<?= htmlspecialchars($item['nombre']) ?>" selected="selected"><?= htmlspecialchars($item['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="help-block">Escriba una condición y presione Enter o Coma.</p>
                </div>
             </div>
        </div>

        <div class="box-footer text-right">
             <button type="submit" class="btn btn-primary btn-flat">Guardar Mis Datos</button>
        </div>
    </form>
</section>

<?php ob_start(); ?>
<script>
$(function(){
    // Inicializar Select2 para permitir añadir nuevas etiquetas (tags)
    $('.select2-tags').select2({
        tags: true,
        tokenSeparators: [',']
    });
});
</script>
<?php $scriptDinamico = ob_get_clean(); ?>