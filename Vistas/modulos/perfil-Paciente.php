<?php
session_start(); // Iniciar sesión

// Verificar si el usuario es un paciente
if ($_SESSION["rol"] != "Paciente") {
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
                            <th>Documento</th>
                            <th>Editar</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $perfil = new PacientesC();
                        $perfil->VerPerfilPacienteC();
                        ?>
                    </tbody>
                </table>

                <?php
                // Mostrar formulario de edición
                $editarPerfil = new PacientesC();
                $editarPerfil->EditarPerfilPacienteC();
                $editarPerfil->ActualizarPerfilPacienteC();
                ?>
            </div>
        </div>
    </section>
</div>