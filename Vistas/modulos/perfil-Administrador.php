<?php


// Verificar si el usuario es un administrador
if ($_SESSION["rol"] != "Administrador") {
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
                <!-- Tabla para mostrar los datos del perfil -->
                <table class="table table-bordered table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Contraseña</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Foto</th>
                            <th>Editar</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        // Mostrar los datos del perfil
                        $perfil = new AdminC();
                        $perfil->VerPerfilAdminC();
                        ?>
                    </tbody>
                </table>

                <!-- Formulario de edición del perfil -->
                <?php
                $editarPerfil = new AdminC();
                $editarPerfil->EditarPerfilAdminC();
                $editarPerfil->ActualizarPerfilAdminC();
                ?>
            </div>
        </div>
    </section>
</div>