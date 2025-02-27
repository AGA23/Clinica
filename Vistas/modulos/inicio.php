<?php
session_start(); // Iniciar sesión

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION["Ingresar"]) || $_SESSION["Ingresar"] != true) {
    header("Location: login.php"); // Redirigir al login si no ha iniciado sesión
    exit();
}
?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>Bienvenido, <?php echo $_SESSION["nombre"] . " " . $_SESSION["apellido"]; ?></h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- Default box -->
        <div class="box">
            <?php
            $inicio = new InicioC();
            $inicio->MostrarInicioC();

            // Mostrar botón de edición solo para administradores
            if ($_SESSION["rol"] == "Administrador") {
                echo '
                    <div class="box-footer">
                        <a href="inicio-editar">
                            <button class="btn btn-success btn-lg">Editar</button>
                        </a>
                    </div>';
            }

            // Enlace al perfil según el rol
            switch ($_SESSION["rol"]) {
                case "Administrador":
                    echo '<a href="perfil-administrador.php"><button class="btn btn-primary btn-lg">Ver Perfil</button></a>';
                    break;
                case "Doctor":
                    echo '<a href="perfil-doctor.php"><button class="btn btn-primary btn-lg">Ver Perfil</button></a>';
                    break;
                case "Paciente":
                    echo '<a href="perfil-paciente.php"><button class="btn btn-primary btn-lg">Ver Perfil</button></a>';
                    break;
                case "Secretaria":
                    echo '<a href="perfil-secretaria.php"><button class="btn btn-primary btn-lg">Ver Perfil</button></a>';
                    break;
            }
            ?>
            <!-- /.box-footer-->
        </div>
        <!-- /.box -->
    </section>
    <!-- /.content -->
</div>