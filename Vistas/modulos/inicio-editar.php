<?php
// 1. Validar sesión y permisos PRIMERO
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] != "Administrador") {
    echo '<script>window.location = "inicio";</script>';
    exit(); // Usar exit() es más seguro que return para detener la ejecución
}

$editarInicio = new InicioC();

// 2. Procesar acciones (POST) ANTES del HTML
// Si el usuario envió el formulario, este método lo procesa y redirige.
// Si no hay $_POST, el método simplemente no hace nada y el código sigue bajando.
$editarInicio->ActualizarInicioC(); 
?>

<div class="content-wrapper">
    <section class="content">
        <div class="box">
            <div class="box-body">

                <?php
                // 4. Mostrar el formulario de edición
                // Asumiendo que este método trae los datos y dibuja (o incluye) el formulario
                $editarInicio->EditarInicioC();
                ?>
                
            </div>
        </div>
    </section>
</div>