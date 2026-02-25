<?php
// =============================
// index.php - VERSION FINAL
// =============================

// Cargar entorno
require_once __DIR__ . '/loader.php';

// Enrutador
$url = $_GET['url'] ?? 'inicio';
if ($url === '') $url = 'inicio';

// =====================================================================
//  EXPORTAR EXCEL (ANTES DE MOSTRAR NADA)
// =====================================================================
if ($url === 'exportar-reportes' && ($_GET['action'] ?? '') === 'exportar_excel') {
    require_once __DIR__ . '/Controladores/ReportesC.php';
    (new ReportesC())->manejarExportarExcelGET();
    exit();
}



// =====================================================================
// LOGIN
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario-Ing'])) {
    (new LoginC())->procesarLogin();
}

if (!isset($_SESSION['Ingresar']) || $_SESSION['Ingresar'] !== true) {
    require_once __DIR__ . '/Vistas/login.php';
    exit();
}

// =====================================================================
// CARGA DE PLANTILLA
// =====================================================================

$rol = $_SESSION['rol'];

$mapa_menus = [
    'Administrador' => 'menuAdmin.php',
    'Doctor'        => 'menuDoctor.php',
    'Paciente'      => 'menuPaciente.php',
    'Secretario'    => 'menuSecretarios.php',
];

if (!isset($mapa_menus[$rol])) {
    die("Error: Rol no definido.");
}

$menuPath   = VIEWS_PATH . "modulos/" . $mapa_menus[$rol];
$moduloPath = VIEWS_PATH . "modulos/" . $url . ".php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Clínica - <?= htmlspecialchars($rol) ?></title>
    <base href="<?= BASE_URL ?>">

    <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>

    <!-- CSS -->
    <link rel="stylesheet" href="Vistas/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="Vistas/bower_components/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="Vistas/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="Vistas/bower_components/select2/dist/css/select2.min.css">
    <link rel="stylesheet" href="Vistas/dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="Vistas/dist/css/skins/skin-blue.min.css">
    <link rel="stylesheet" href="Vistas/plugins/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="Vistas/bower_components/fullcalendar/dist/fullcalendar.min.css">
</head>




<body class="hold-transition skin-blue sidebar-mini">



<div class="wrapper">

    <?php include VIEWS_PATH . 'modulos/cabecera.php'; ?>
    <?php include $menuPath; ?>

    <div class="content-wrapper">
        <?php include $moduloPath; ?>
    </div>

    <footer class="main-footer">
        <strong>Clínica © <?= date('Y') ?></strong>
    </footer>

</div>

<!-- JS (orden correcto) -->
<script src="Vistas/bower_components/jquery/dist/jquery.min.js"></script>
<script src="Vistas/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="Vistas/bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="Vistas/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script src="Vistas/bower_components/select2/dist/js/select2.full.min.js"></script>
<script src="Vistas/plugins/sweetalert2/sweetalert2.all.min.js"></script>

<script src="Vistas/bower_components/moment/moment.js"></script>
<script src="Vistas/bower_components/fullcalendar/dist/fullcalendar.min.js"></script>
<script src="Vistas/bower_components/fullcalendar/dist/locale/es.js"></script>

<!-- Calendario-admin.js CORRECTAMENTE UBICADO -->
<script src="Vistas/js/calendario-admin.js"></script>

<script src="Vistas/dist/js/adminlte.min.js"></script>

<?php if(isset($scriptDinamico)) echo $scriptDinamico; ?>
</body>
</html>
