<?php
session_start(); // Iniciar sesión

// Verificar si el usuario es una secretaria
if ($_SESSION["rol"] != "Secretaria") {
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
                            <th>Editar</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $idSecretaria = isset($_SESSION['id']) ? $_SESSION['id'] : null;

                        if ($idSecretaria) {
                            $perfil = SecretariasC::VerPerfilSecretariaC($idSecretaria);

                            if ($perfil) {
                                echo '<tr>';
                                echo '<td>' . $perfil['usuario'] . '</td>';
                                echo '<td>' . $perfil['clave'] . '</td>';
                                echo '<td>' . $perfil['nombre'] . '</td>';
                                echo '<td>' . $perfil['apellido'] . '</td>';
                                echo '<td><img src="http://localhost/clinica/' . $perfil['foto'] . '" alt="Foto" width="50"></td>';
                                echo '<td><button class="btn btn-warning">Editar</button></td>';
                                echo '</tr>';
                            } else {
                                echo '<tr><td colspan="6">No se encontró el perfil de la secretaria.</td></tr>';
                            }
                        } else {
                            echo '<tr><td colspan="6">Error: No se encontró el id de la secretaria.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>

                <?php
                // Mostrar formulario de edición
                $editarPerfil = new SecretariasC();
                $editarPerfil->EditarPerfilSecretariaC();
                $editarPerfil->ActualizarPerfilSecretariaC();
                ?>
            </div>
        </div>
    </section>
</div>