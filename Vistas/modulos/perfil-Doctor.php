<?php
session_start(); // Iniciar sesión

// Verificar si el usuario es un doctor
if ($_SESSION["rol"] != "Doctor") {
    echo '<script>window.location = "inicio";</script>';
    exit();
}
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Gestor de Perfil</h1>
    </section>

    <section class="content">
        <div class="box">
            <div class="box-body">
                <table class="table table-bordered table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Contraseña</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Foto</th>
                            <th>Consultorio</th>
                            <th>Horario</th>
                            <th>Editar</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $perfil = new DoctoresC();
                        $perfil->VerPerfilDoctorC();
                        ?>
                    </tbody>
                </table>

                <?php
                // Mostrar formulario de edición
                $editarPerfil = new DoctoresC();
                $editarPerfil->EditarPerfilDoctorC();
                $editarPerfil->ActualizarPerfilDoctorC();
                ?>
            </div>
        </div>
    </section>
</div>