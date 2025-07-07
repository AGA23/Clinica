<?php
require_once __DIR__ . '/../config.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificación de sesión
$rolesPermitidos = ['Administrador', 'Doctor', 'Paciente', 'Secretaria'];
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $rolesPermitidos)) {
    header("Location: " . BASE_URL . "Vistas/plantilla.php");
    exit();
}

// Definición de rutas
$menuFile = 'menu' . $_SESSION['rol'] . '.php';
$menuPath = VIEWS_PATH . 'modulos/' . $menuFile;

// Verificar existencia del menú
if (!file_exists($menuPath)) {
    die("❌ Error: No se encontró el menú para el rol " . htmlspecialchars($_SESSION['rol']));
}

// Definir módulo
$modulo = $_GET['modulo'] ?? 'inicio';
$moduloPath = VIEWS_PATH . 'modulos/' . $modulo . '.php';
$contenidoDinamico = '';



// Cargar controlador si es necesario
$controladoresPorModulo = [
    'inicio' => __DIR__ . '/../Controladores/inicioC.php',
    // Podés agregar más controladores si tu sistema los necesita
];

if (isset($controladoresPorModulo[$modulo])) {
    require_once $controladoresPorModulo[$modulo];
}

// Cargar contenido del módulo
if (file_exists($moduloPath)) {
    ob_start();
    include $moduloPath;
    $contenidoDinamico = ob_get_clean();
} else {
    $contenidoDinamico = "<div class='alert alert-danger'>⚠️ Módulo <code>$modulo</code> no encontrado en <code>$moduloPath</code></div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clínica - <?= htmlspecialchars($_SESSION['rol']) ?></title>
    <base href="<?= BASE_URL ?>">

    <!-- CSS -->
    <link rel="stylesheet" href="Vistas/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="Vistas/dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="Vistas/dist/css/skins/skin-blue.min.css">
    <link rel="stylesheet" href="Vistas/bower_components/font-awesome/css/font-awesome.min.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">
        <!-- Cabecera -->
        <?php include VIEWS_PATH . 'modulos/cabecera.php'; ?>

        <!-- Menú lateral -->
        <?php include $menuPath; ?>

        <!-- Contenido principal -->
        <div class="content-wrapper">
            <section class="content-header">
                <h1>
                    <?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?>
                    <small><?= htmlspecialchars($_SESSION['rol']) ?></small>
                </h1>
            </section>

            <section class="content">
                <?= $contenidoDinamico ?>
            </section>
        </div>

        <!-- Pie de página -->
        <footer class="main-footer">
            <strong>Clínica &copy; <?= date('Y') ?></strong>
        </footer>
    </div>

    <!-- =============================================== -->
    <!-- JAVASCRIPT - ORDEN DE CARGA CORRECTO Y MODULAR -->
    <!-- =============================================== -->

    <!-- 1. LIBRERÍAS GLOBALES (se cargan siempre) -->
    <script src="Vistas/bower_components/jquery/dist/jquery.min.js"></script>
    <script src="Vistas/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="Vistas/dist/js/adminlte.min.js"></script>
    
    <!-- 2. LIBRERÍAS ESPECÍFICAS DEL MÓDULO (se cargan solo si son necesarias) -->
    <?php if ($modulo === 'citas_doctor'): ?>
        <!-- CSS y JS para la librería Select2, solo para la página de citas del doctor -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <?php endif; ?>

    <!-- 3. LÓGICA JAVASCRIPT ESPECÍFICA DEL MÓDULO -->
    <?php if ($modulo === 'citas_doctor'): ?>
    <script>
        // Todo el código JS para 'citas_doctor.php' se ejecuta aquí.
        // Esto garantiza que jQuery, Bootstrap y Select2 ya se han cargado.
        $(document).ready(function() {

            // --- LÓGICA PARA MODALES "FINALIZAR" Y "CANCELAR" ---
            $(document).on('click', '.btnCancelarCita', function() {
                $('#modalCancelarCitaId').val($(this).data('id-cita'));
                $('#motivo_cancelacion').val('');
            });

            $(document).on('click', '.btnFinalizarCita', function() {
                const modal = $('#modalFinalizarCita');
                // Usa los IDs corregidos del HTML de citas_doctor.php
                modal.find('#id_cita_finalizar').val($(this).data('id-cita'));
                modal.find('#motivo_finalizar').val($(this).data('motivo'));
                modal.find('#observaciones_finalizar, #peso_finalizar').val('');
                if (modal.find('#medicamentos').hasClass("select2-hidden-accessible")) {
                    modal.find('#medicamentos, #tratamientos').val(null).trigger('change');
                }
            });

                       // Inicializa Select2 solo cuando el modal se muestra
            $('#modalFinalizarCita').on('shown.bs.modal', function () {
                const dropdownParent = $(this).find('.modal-content');
                const medicamentosSelect = $('#medicamentos');
                const tratamientosSelect = $('#tratamientos');

                // Comprueba si el select de medicamentos YA ES un Select2
                if (medicamentosSelect.hasClass('select2-hidden-accessible')) {
                    // Si ya lo es, lo destruimos para reiniciarlo
                    medicamentosSelect.select2('destroy');
                }
                // Ahora, lo creamos (o recreamos)
                medicamentosSelect.select2({
                    placeholder: "Buscar medicamentos...",
                    width: '100%',
                    allowClear: true,
                    dropdownParent: dropdownParent
                });

                // Hacemos lo mismo para el select de tratamientos
                if (tratamientosSelect.hasClass('select2-hidden-accessible')) {
                    tratamientosSelect.select2('destroy');
                }
                tratamientosSelect.select2({
                    placeholder: "Buscar tratamientos...",
                    width: '100%',
                    allowClear: true,
                    dropdownParent: dropdownParent
                });
            });

            // --- LÓGICA PARA MODAL "CREAR CITA" (Horarios) ---
            if ($('#modalNuevaCita').length > 0) {
                const horariosConsultorio = <?= json_encode($horarios_consultorio, JSON_UNESCAPED_UNICODE) ?>;
                const horariosDoctor = <?= json_encode($horarios_doctor, JSON_UNESCAPED_UNICODE) ?>;
                const consultorioId = <?= json_encode($consultorio_id) ?>;

                // Usa los IDs corregidos del HTML de citas_doctor.php
                const fechaInput = document.getElementById('fecha_crear');
                const referenciaDisponible = document.getElementById('horario_doctor_referencia');
                const horaInicioInput = document.getElementById('hora_inicio_crear');
                const horaFinInput = document.getElementById('hora_fin_crear');

                function actualizarHorario() {
                    if (!fechaInput.value) { referenciaDisponible.value = "Seleccione una fecha"; horaInicioInput.disabled = true; horaFinInput.disabled = true; return; }
                    const fechaSeleccionada = new Date(fechaInput.value + 'T00:00:00');
                    let diaSemana = fechaSeleccionada.getUTCDay(); if (diaSemana === 0) diaSemana = 7;
                    const horarioDoctorDia = horariosDoctor.find(h => parseInt(h.dia_semana) === diaSemana && parseInt(h.id_consultorio) === parseInt(consultorioId));
                    const horarioConsultorioDia = horariosConsultorio.find(h => parseInt(h.dia_semana) === diaSemana && parseInt(h.id_consultorio) === parseInt(consultorioId));
                    if (!horarioDoctorDia || !horarioConsultorioDia) { referenciaDisponible.value = !horarioDoctorDia ? "Doctor no disponible este día." : "Consultorio cerrado."; horaInicioInput.disabled = true; horaFinInput.disabled = true; return; }
                    let horaInicioDisponible = (horarioDoctorDia.hora_inicio.slice(0, 5) > horarioConsultorioDia.hora_apertura.slice(0, 5)) ? horarioDoctorDia.hora_inicio.slice(0, 5) : horarioConsultorioDia.hora_apertura.slice(0, 5);
                    let horaFinDisponible = (horarioDoctorDia.hora_fin.slice(0, 5) < horarioConsultorioDia.hora_cierre.slice(0, 5)) ? horarioDoctorDia.hora_fin.slice(0, 5) : horarioConsultorioDia.hora_cierre.slice(0, 5);
                    if (horaInicioDisponible >= horaFinDisponible) { referenciaDisponible.value = "No hay horario de atención este día."; horaInicioInput.disabled = true; horaFinInput.disabled = true; return; }
                    const hoy = new Date();
                    if (fechaInput.value === hoy.toISOString().slice(0, 10)) {
                        const horaActual = hoy.toTimeString().slice(0, 5);
                        if (horaActual > horaInicioDisponible) { horaInicioDisponible = horaActual; }
                        if (horaInicioDisponible >= horaFinDisponible) { referenciaDisponible.value = "Horario para hoy ya finalizado."; horaInicioInput.disabled = true; horaFinInput.disabled = true; return; }
                    }
                    referenciaDisponible.value = `Disponible de ${horaInicioDisponible} a ${horaFinDisponible}`;
                    horaInicioInput.min = horaFinInput.min = horaInicioDisponible;
                    horaInicioInput.max = horaFinInput.max = horaFinDisponible;
                    horaInicioInput.value = horaInicioDisponible;
                    horaFinInput.value = horaFinDisponible;
                    horaInicioInput.disabled = false;
                    horaFinInput.disabled = false;
                }
                // Asignar los listeners a los elementos del formulario
                if(fechaInput) fechaInput.addEventListener('change', actualizarHorario);
                if(horaInicioInput) horaInicioInput.addEventListener('change', function () { if (horaFinInput.value < horaInicioInput.value) { horaFinInput.value = horaInicioInput.value; } horaFinInput.min = horaInicioInput.value; });
            }
        });

        // La función de validación puede quedar aquí
        function validarFinalizacion() {
            // Lógica de validación con los IDs corregidos
            const motivo = document.getElementById('motivo_finalizar');
            const observaciones = document.getElementById('observaciones_finalizar');
            const peso = document.getElementById('peso_finalizar');
            if (!motivo.value.trim() || !observaciones.value.trim()) { alert("Por favor, complete el motivo y las observaciones."); motivo.focus(); return false; }
            if (!peso.value || isNaN(parseFloat(peso.value)) || parseFloat(peso.value) <= 0 || parseFloat(peso.value) > 500) { alert("El peso debe ser un número válido entre 0.1 y 500 kg."); peso.focus(); return false; }
            return true;
        }
    </script>
    <?php endif; ?>

    <!-- JS del módulo si existe (se mantiene tu lógica original por si la usas para otros módulos) -->
    <?php if (file_exists("Vistas/js/{$modulo}.js")): ?>
        <script src="Vistas/js/<?= $modulo ?>.js"></script>
    <?php endif; ?>
</body>


</html>
