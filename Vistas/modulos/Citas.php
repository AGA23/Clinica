<?php
// En Vistas/modulos/citas.php

// 1. VERIFICACIÓN DE PERMISOS
// La sesión ya debe estar iniciada por index.php.
if (!isset($_SESSION["Ingresar"]) || $_SESSION["rol"] !== "Paciente") {
    // Si no es un paciente logueado, lo redirigimos a la página de inicio.
    echo '<script>window.location = "inicio";</script>';
    exit();
}

// 2. OBTENCIÓN DE DATOS Y CARGA DE CONTROLADORES
$id_paciente = $_SESSION["id"];

// Se asume que las clases ya están disponibles a través de un autoloader o un 'loader.php' principal.
// Si no es el caso, descomentar las siguientes líneas:
// require_once $_SERVER["DOCUMENT_ROOT"] . '/CLINICA/Controladores/citasC.php';
// require_once $_SERVER["DOCUMENT_ROOT"] . '/CLINICA/Controladores/doctoresC.php';

$citasController = new CitasC();

// 3. PROCESAMIENTO DE ACCIONES (Cancelación de cita)
if (isset($_GET["cancelar"])) {
    $id_cita_a_cancelar = filter_input(INPUT_GET, 'cancelar', FILTER_VALIDATE_INT);
    if ($id_cita_a_cancelar) {
        $resultado_cancelacion = $citasController->CancelarCitaC($id_cita_a_cancelar, "Cancelada por el paciente");
        
        // Guardar el mensaje en la sesión para mostrarlo después de redirigir.
        if (isset($resultado_cancelacion['error'])) {
            $_SESSION['mensaje_citas_paciente'] = $resultado_cancelacion['error'];
            $_SESSION['tipo_mensaje_citas_paciente'] = "danger";
        } else {
            $_SESSION['mensaje_citas_paciente'] = $resultado_cancelacion['success'] ?? 'Cita cancelada correctamente';
            $_SESSION['tipo_mensaje_citas_paciente'] = "success";
        }
    }
    // Redirigir a la misma página sin el parámetro para evitar re-cancelaciones al recargar.
    echo '<script>window.location = "citas";</script>';
    exit();
}

// 4. OBTENCIÓN DE DATOS PARA LA VISTA
$citas = $citasController->VerCitasPacienteC($id_paciente);

// --- MEJORA: Crear lista de doctores que han atendido al paciente ---
$doctores_paciente = [];
$ids_doctores_vistos = []; // Array auxiliar para evitar duplicados.
foreach ($citas as $cita) {
    $id_doctor_cita = $cita['id_doctor'];
    // Si el doctor no ha sido añadido a la lista, se agrega.
    if (!in_array($id_doctor_cita, $ids_doctores_vistos)) {
        $doctores_paciente[] = [
            'id' => $id_doctor_cita,
            'nombre_completo' => $cita['nombre_doctor'] // Se usa el nombre que ya viene en la consulta de citas.
        ];
        $ids_doctores_vistos[] = $id_doctor_cita;
    }
}

// 5. APLICAR FILTROS DE BÚSQUEDA
$filtro_doctor = $_GET['doctor'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';

// 6. CLASIFICACIÓN DE CITAS (PRÓXIMAS VS. PASADAS)
$citas_pasadas = [];
$citas_proximas = [];
$ahora = new DateTime();

foreach ($citas as $cita) {
    // Comprobar si la cita coincide con los filtros seleccionados.
    $pasa_filtro_doctor = empty($filtro_doctor) || $cita['id_doctor'] == $filtro_doctor;
    $pasa_filtro_fecha = empty($filtro_fecha) || date('Y-m-d', strtotime($cita['inicio'])) == $filtro_fecha;

    if ($pasa_filtro_doctor && $pasa_filtro_fecha) {
        $fecha_cita = new DateTime($cita['inicio']);
        if ($fecha_cita < $ahora) {
            $citas_pasadas[] = $cita;
        } else {
            $citas_proximas[] = $cita;
        }
    }
}

// 7. FUNCIÓN HELPER para etiquetas de estado
function etiquetaEstado($estado) {
    $clases = ["Pendiente" => "label-warning", "Completada" => "label-success", "Cancelada" => "label-danger"];
    return "<span class='label " . ($clases[$estado] ?? "label-default") . "'>" . htmlspecialchars($estado) . "</span>";
}
?>

<!-- El módulo ahora solo contiene las secciones que van DENTRO del <div class="content-wrapper"> -->

<section class="content-header">
    <h1>Mis Citas</h1>
    <ol class="breadcrumb">
        <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
        <li class="active">Mis Citas</li>
    </ol>
</section>

<section class="content">
    
    <!-- Mostrar mensajes de error o éxito desde la sesión -->
    <?php if (isset($_SESSION['mensaje_citas_paciente'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['tipo_mensaje_citas_paciente']) ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <?= htmlspecialchars($_SESSION['mensaje_citas_paciente']) ?>
        </div>
    <?php 
        // Limpiar los mensajes después de mostrarlos para que no reaparezcan.
        unset($_SESSION['mensaje_citas_paciente'], $_SESSION['tipo_mensaje_citas_paciente']); 
    endif; 
    ?>

    <!-- Filtros de búsqueda -->
    <div class="box box-default">
        <div class="box-body">
            <form method="get" class="form-inline">
                <div class="form-group">
                    <label for="doctor">Doctor:</label>
                    <select name="doctor" id="doctor" class="form-control" style="margin: 0 10px;">
                        <option value="">Todos</option>
                        <!-- ¡MEJORADO! Se itera sobre la lista de doctores filtrada. -->
                        <?php foreach ($doctores_paciente as $doc): ?>
                            <option value="<?= $doc['id'] ?>" <?= ($filtro_doctor == $doc['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doc['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="fecha">Fecha:</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" value="<?= htmlspecialchars($filtro_fecha) ?>" style="margin: 0 10px;">
                </div>
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="citas" class="btn btn-default">Limpiar Filtros</a>
            </form>
        </div>
    </div>

    <!-- Tabla de Próximas Citas -->
    <div class="box box-success">
        <div class="box-header with-border"><h3 class="box-title">Citas Próximas</h3></div>
        <div class="box-body table-responsive">
            <?php if (empty($citas_proximas)): ?>
                <div class="alert alert-info">No tienes citas próximas que coincidan con los filtros seleccionados.</div>
            <?php else: ?>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th><th>Doctor</th><th>Consultorio</th><th>Motivo</th><th>Estado</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citas_proximas as $cita): ?>
                            <tr>
                                <td><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></td>
                                <td><?= htmlspecialchars($cita["nombre_doctor"]) ?></td>
                                <td><?= htmlspecialchars($cita["nombre_consultorio"] ?? 'No asignado') ?></td>
                                <td><?= htmlspecialchars($cita["motivo"]) ?></td>
                                <td><?= etiquetaEstado($cita["estado"]) ?></td>
                                <td>
                                    <?php if ($cita["estado"] == "Pendiente"): ?>
                                        <a href="citas?cancelar=<?= $cita['id'] ?>" class="btn btn-danger btn-xs" 
                                           onclick="return confirm('¿Estás seguro de que deseas cancelar esta cita?')">
                                            Cancelar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabla de Historial de Citas -->
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Historial de Citas</h3></div>
        <div class="box-body table-responsive">
            <?php if (empty($citas_pasadas)): ?>
                <div class="alert alert-info">No tienes citas pasadas que coincidan con los filtros seleccionados.</div>
            <?php else: ?>
                <table class="table table-bordered table-striped dt-responsive" width="100%">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th><th>Doctor</th><th>Consultorio</th><th>Motivo</th><th>Diagnóstico/Observaciones</th><th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citas_pasadas as $cita): ?>
                            <tr>
                                <td><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></td>
                                <td><?= htmlspecialchars($cita["nombre_doctor"]) ?></td>
                                <td><?= htmlspecialchars($cita["nombre_consultorio"] ?? 'No asignado') ?></td>
                                <td><?= htmlspecialchars($cita["motivo"]) ?></td>
                                <td><?= nl2br(htmlspecialchars($cita["observaciones"] ?? 'N/A')) ?></td>
                                <td><?= etiquetaEstado($cita["estado"]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// Capturar el JavaScript para inyectarlo en el footer de la plantilla principal (index.php)
ob_start();
?>
<script>
$(function() {
    // Inicializar DataTables en la tabla de historial para que tenga paginación y búsqueda.
    $('.dt-responsive').DataTable({
        "language": {
            "url": "<?= BASE_URL ?>Vistas/plugins/datatables/Spanish.json"
        },
        "responsive": true,
        "autoWidth": false,
        "order": [[ 0, "desc" ]] // Ordenar por fecha descendente por defecto.
    });
});
</script>
<?php
// Guardar el script en una variable que tu index.php debe imprimir en el footer.
$scriptDinamico = ob_get_clean();
?>