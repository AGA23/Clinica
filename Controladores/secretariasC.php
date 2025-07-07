<?php

require_once __DIR__ . '/../Modelos/SecretariasM.php';

class SecretariasC
{
    // Ingresar secretaria
    static public function IngresarSecretariaC($usuario, $clave)
    {
        $tablaBD = "secretarias";
        $datosC = array("usuario" => $usuario, "clave" => $clave);
        $respuesta = SecretariasM::IngresarSecretariaM($tablaBD, $datosC);
        if ($respuesta) {
            return $respuesta;
        }
        return false;
    }

    // Método para mostrar todas las secretarias
    static public function VerSecretariasC()
    {
        $tablaBD = "secretarias"; // Nombre de la tabla
        $resultado = SecretariasM::VerSecretariasM($tablaBD);
        return $resultado;
    }

    // Ver perfil de secretaria
    static public function VerPerfilSecretariaC($id)
    {
        $tablaBD = "secretarias";
        return SecretariasM::VerPerfilSecretariaM($tablaBD, $id);
    }

    // Actualizar perfil secretaria
    static public function ActualizarPerfilSecretariaC()
    {
        if (isset($_POST["id"])) {
            $tablaBD = "secretarias";

            // Manejar la subida de la foto
            $foto = $_POST["fotoActual"]; // Mantener la foto actual por defecto
            if (!empty($_FILES["foto"]["name"])) {
                // Si se sube una nueva foto, procesarla
                $foto = self::subirFoto($_FILES["foto"]);
            }

            // Datos a actualizar
            $datosC = array(
                "id" => $_POST["id"],
                "usuario" => $_POST["usuario"],
                "clave" => $_POST["clave"],
                "nombre" => $_POST["nombre"],
                "apellido" => $_POST["apellido"],
                "foto" => $foto
            );

            // Actualizar en la base de datos
            $respuesta = SecretariasM::ActualizarPerfilSecretariaM($tablaBD, $datosC);

            if ($respuesta == true) {
                // Actualizar la sesión con los nuevos datos
                $_SESSION['usuario'] = $_POST['usuario'];
                $_SESSION['nombre'] = $_POST['nombre'];
                $_SESSION['apellido'] = $_POST['apellido'];
                $_SESSION['foto'] = $foto;

                // Redirigir a la página de perfil
                echo '<script>
                    alert("Perfil actualizado correctamente");
                    window.location = "perfil-Secretaria"; // Redirigir a la página de perfil
                </script>';
            } else {
                echo '<script>
                    alert("Error al actualizar el perfil");
                </script>';
            }
        }
    }

    // Método para subir la foto
    static private function subirFoto($foto)
    {
        $rutaTemporal = $foto["tmp_name"];
        $nombreArchivo = $foto["name"];
        $rutaDestino = __DIR__ . "/../../Vistas/img/secretarias/" . $nombreArchivo;

        // Mover el archivo a la carpeta de destino
        if (move_uploaded_file($rutaTemporal, $rutaDestino)) {
            return "Vistas/img/secretarias/" . $nombreArchivo; // Ruta relativa para guardar en la base de datos
        } else {
            return $_POST["fotoActual"]; // Mantener la foto actual si no se puede subir la nueva
        }
    }

    // Editar perfil secretaria
    public function EditarPerfilSecretariaC()
    {
        $tablaBD = "secretarias";
        $id = $_SESSION["id"];
        $respuesta = SecretariasM::VerPerfilSecretariaM($tablaBD, $id);

        echo '<form method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 col-xs-12">
                        <h2>Nombre:</h2>
                        <input type="text" class="input-lg" name="nombre" value="' . $respuesta["nombre"] . '">
                        <input type="hidden" name="id" value="' . $respuesta["id"] . '">
                        <h2>Apellido:</h2>
                        <input type="text" class="input-lg" name="apellido" value="' . $respuesta["apellido"] . '">
                        <h2>Usuario:</h2>
                        <input type="text" class="input-lg" name="usuario" value="' . $respuesta["usuario"] . '">
                        <h2>Contraseña:</h2>
                        <input type="text" class="input-lg" name="clave" value="' . $respuesta["clave"] . '">
                        <h2>Foto:</h2>
                        <input type="file" name="foto">
                        <img src="http://localhost/clinica/' . $respuesta["foto"] . '" width="100px">
                        <input type="hidden" name="fotoActual" value="' . $respuesta["foto"] . '">
                        <br><br>
                        <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    </div>
                </div>
            </form>';
    }

    // Crear secretaria
    static public function CrearSecretariaC()
    {
        if (isset($_POST["apellido"])) {
            $tablaBD = "secretarias";
            $datosC = array(
                "nombre" => $_POST["nombre"],
                "apellido" => $_POST["apellido"],
                "usuario" => $_POST["usuario"],
                "clave" => $_POST["clave"],
                "rol" => $_POST["rolS"]
            );
            $respuesta = SecretariasM::CrearSecretariaM($tablaBD, $datosC);
            if ($respuesta == true) {
                echo '<script>
                window.location = "secretarias";
                </script>';
            }
        }
    }

    // Borrar secretaria
    static public function BorrarSecretariaC()
    {
        if (isset($_GET["Sid"])) {
            $tablaBD = "secretarias";
            $datosC = $_GET["Sid"];
            $respuesta = SecretariasM::BorrarSecretariaM($tablaBD, $datosC);
            if ($respuesta == true) {
                echo '<script>
                window.location = "secretarias";
                </script>';
            }
        }
    }
}
?>