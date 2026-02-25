<?php
// En Controladores/AdminC.php

class AdminC {

    // --- LÓGICA DE LOGIN ---
    public function IngresarAdminC() {
        if (isset($_POST["usuario-Ing"])) {
            if (isset($_POST["usuario-Ing"]) && isset($_POST["clave-Ing"])) {
                $tablaBD = "administradores";
                $datosC = ["usuario" => $_POST["usuario-Ing"]];
                
                $resultado = AdminM::IngresarAdminM($tablaBD, $datosC);

                // IMPORTANTE: Esta versión es insegura. Debes migrar a contraseñas hasheadas.
                if ($resultado && $_POST["clave-Ing"] == $resultado["clave"]) {
                    $_SESSION["Ingresar"] = true;
                    $_SESSION["id"] = $resultado["id"];
                    $_SESSION["usuario"] = $resultado["usuario"];
                    $_SESSION["nombre"] = $resultado["nombre"];
                    $_SESSION["apellido"] = $resultado["apellido"];
                    $_SESSION["foto"] = $resultado["foto"];
                    $_SESSION["rol"] = $resultado["rol"];
                    echo '<script>window.location = "inicio";</script>';
                } else {
                    echo '<br><div class="alert alert-danger">Usuario o contraseña incorrectos.</div>';
                }
            }
        }
    }

public function ObtenerPerfilAdminC() {
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Administrador' && isset($_SESSION['id'])) {

        return AdminM::VerPerfilAdminM("administradores", $_SESSION['id']);
    }
    return false;
}

   
    public function ActualizarPerfilAdminC() {
        if (isset($_POST["actualizarPerfilAdmin"]) && $_POST['idAdmin'] == $_SESSION['id']) {

            // Lógica para la contraseña: Mantiene la contraseña en texto plano como tu sistema actual.
            $clave = $_POST["claveE"];
            if (empty(trim($_POST["claveE"]))) {
                 $clave = $_POST["claveActual"]; // Mantiene la vieja si el campo está vacío.
            }

            // Lógica para la foto de perfil
            $rutaFoto = $_POST["fotoActual"];
            if (isset($_FILES["fotoE"]["tmp_name"]) && !empty($_FILES["fotoE"]["tmp_name"])) {
                if (!empty($_POST["fotoActual"]) && $_POST["fotoActual"] != 'Vistas/img/user-default.png') {
                    if(file_exists($_POST["fotoActual"])) unlink($_POST["fotoActual"]);
                }
                // Simplificamos la ruta de la imagen
                $nombreArchivo = "admin_" . $_POST['idAdmin'] . ".jpg";
                $rutaFoto = "Vistas/img/admins/" . $nombreArchivo;
                move_uploaded_file($_FILES["fotoE"]["tmp_name"], $rutaFoto);
            }

            // Prepara el array de datos para el modelo
            $datos = [
                "id" => $_POST["idAdmin"],
                "nombre" => trim($_POST["nombreE"]),
                "apellido" => trim($_POST["apellidoE"]),
                "usuario" => trim($_POST["usuarioE"]),
                "clave" => $clave,
                "foto" => $rutaFoto
            ];

            // Llama a tu método existente del modelo para actualizar
            $resultado = AdminM::ActualizarPerfilAdminM("administradores", $datos);

            if ($resultado) {
                // Actualizar la sesión con los nuevos datos
                $_SESSION['nombre'] = $datos['nombre'];
                $_SESSION['apellido'] = $datos['apellido'];
                $_SESSION['usuario'] = $datos['usuario'];
                $_SESSION['foto'] = $datos['foto'];
                
                $_SESSION['mensaje_perfil'] = "¡Perfil actualizado correctamente!";
                $_SESSION['tipo_mensaje_perfil'] = "success";
            } else {
                $_SESSION['mensaje_perfil'] = "Error al actualizar el perfil.";
                $_SESSION['tipo_mensaje_perfil'] = "danger";
            }

            // Redirige a la página de perfil
            echo '<script>window.location = "perfil-Administrador";</script>';
            exit();
        }
    }

    public static function ListarAdminsC() {
        // Llama al nuevo método del modelo que excluye al usuario actual.
        return AdminM::ListarAdminsM();
    }
    
    /**
     * Procesa el formulario para crear un nuevo administrador.
     */
    public function CrearAdminC() {
        if (isset($_POST["crear_admin"])) {
            // Seguridad: Doble verificación de que solo un admin puede crear otro.
            if ($_SESSION['rol'] !== 'Administrador') { return; }

            $datos = [
                "nombre"   => trim($_POST['nombre']),
                "apellido" => trim($_POST['apellido']),
                "usuario"  => trim($_POST['usuario']),
                "clave"    => password_hash(trim($_POST['clave']), PASSWORD_DEFAULT) // Siempre hashear contraseñas nuevas
            ];

            $respuesta = AdminM::CrearAdminM($datos); // Llama al nuevo método del modelo
            if ($respuesta) {
                $_SESSION['mensaje_admins'] = "¡Administrador creado con éxito!";
                $_SESSION['tipo_mensaje_admins'] = "success";
            } else {
                $_SESSION['mensaje_admins'] = "Error al crear el administrador. El usuario puede ya existir.";
                $_SESSION['tipo_mensaje_admins'] = "danger";
            }
            echo '<script>window.location = "admins";</script>';
            exit();
        }
    }

    /**
     * Procesa el formulario para actualizar los datos de otro administrador.
     */
    public function ActualizarAdminC() {
        if (isset($_POST["editar_admin"])) {
            if ($_SESSION['rol'] !== 'Administrador') { return; }

            $datos = [
                "id"       => $_POST['id_admin_editar'],
                "nombre"   => trim($_POST['nombre_editar']),
                "apellido" => trim($_POST['apellido_editar']),
                "usuario"  => trim($_POST['usuario_editar']),
                // Hashea la contraseña solo si se proporcionó una nueva
                "clave"    => !empty(trim($_POST['clave_editar'])) ? password_hash(trim($_POST['clave_editar']), PASSWORD_DEFAULT) : null
            ];
            
            $respuesta = AdminM::ActualizarAdminM($datos); // Llama al nuevo método del modelo
            if ($respuesta) {
                $_SESSION['mensaje_admins'] = "¡Administrador actualizado!";
                $_SESSION['tipo_mensaje_admins'] = "success";
            } else {
                $_SESSION['mensaje_admins'] = "Error al actualizar. El usuario puede ya existir.";
                $_SESSION['tipo_mensaje_admins'] = "danger";
            }
            echo '<script>window.location = "admins";</script>';
            exit();
        }
    }
}
