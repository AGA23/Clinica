<?php


class SecretariosC {

    // --- MÉTODOS PARA LA PÁGINA DE PERFIL ('perfil-secretario.php') ---

   
    public function ObtenerPerfilSecretarioC() {
        if (isset($_SESSION['id']) && $_SESSION['rol'] === 'Secretario') {
            return SecretariosM::ObtenerPerfilSecretarioM($_SESSION['id']);
        }
        return null;
    }

  
    public function ActualizarPerfilSecretarioC() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idSecretario'])) {
            $id_secretario = $_POST['idSecretario'];

            // Seguridad: Asegurarse de que el usuario solo se edita a sí mismo.
            if ($_SESSION['id'] != $id_secretario || $_SESSION['rol'] !== 'Secretario') {
                $_SESSION['mensaje_perfil'] = "Acción no permitida.";
                $_SESSION['tipo_mensaje_perfil'] = "danger";
                header("Location: " . BASE_URL . "inicio");
                exit();
            }
            
            // Lógica de subida de foto segura
            $rutaFoto = $_POST["fotoActual"];
            if (isset($_FILES["fotoE"]["tmp_name"]) && !empty($_FILES["fotoE"]["tmp_name"])) {
                if (!empty($_POST["fotoActual"]) && file_exists(ROOT_PATH . $_POST["fotoActual"]) && $_POST["fotoActual"] !== 'Vistas/img/defecto.png') {
                    unlink(ROOT_PATH . $_POST["fotoActual"]);
                }
                $extension = strtolower(pathinfo($_FILES["fotoE"]["name"], PATHINFO_EXTENSION));
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $directorioDestino = ROOT_PATH . "Vistas/img/secretarios/";
                    if (!file_exists($directorioDestino)) {
                        mkdir($directorioDestino, 0777, true);
                    }
                    $rutaFoto = "Vistas/img/secretarios/" . $_POST['idSecretario'] . "-" . time() . "." . $extension; 
                    move_uploaded_file($_FILES["fotoE"]["tmp_name"], ROOT_PATH . $rutaFoto);
                }
            }

            // Lógica de hasheo de contraseña
            $clave = !empty(trim($_POST['claveE'])) ? password_hash(trim($_POST['claveE']), PASSWORD_DEFAULT) : $_POST['claveActual'];

            $datos = [
                "id" => $id_secretario,
                "nombre" => trim($_POST['nombreE']),
                "apellido" => trim($_POST['apellidoE']),
                "usuario" => trim($_POST['usuarioE']),
                "clave" => $clave,
                "foto" => $rutaFoto
            ];

            $resultado = SecretariosM::ActualizarPerfilSecretarioM($datos); 

            if ($resultado) {
                $_SESSION['mensaje_perfil'] = "¡Perfil actualizado correctamente!";
                $_SESSION['tipo_mensaje_perfil'] = "success";
                $_SESSION['nombre'] = $datos['nombre'];
                $_SESSION['apellido'] = $datos['apellido'];
                $_SESSION['foto'] = $datos['foto'];
            } else {
                $_SESSION['mensaje_perfil'] = "Error: No se pudo actualizar el perfil.";
                $_SESSION['tipo_mensaje_perfil'] = "danger";
            }

            // ¡REDIRECCIÓN CORREGIDA!
            // Apunta a la URL limpia y en minúscula, que coincide con el nombre del archivo del módulo.
            header("Location: " . BASE_URL . "perfil-secretario");
            exit();
        }
    }

    // --- MÉTODOS DE GESTIÓN PARA ADMINISTRADORES ('secretarios.php') ---
    
    
    static public function VerSecretariosC()
    {
        return SecretariosM::VerSecretariosM();
    }

    
    static public function CrearSecretarioC()
    {
        if (isset($_POST["crearSecretario"])) {
            $datosC = [
                "nombre" => trim($_POST["nombre"]),
                "apellido" => trim($_POST["apellido"]),
                "usuario" => trim($_POST["usuario"]),
                "clave" => password_hash($_POST["clave"], PASSWORD_DEFAULT),
                "rol" => "Secretario"
            ];
            
            $respuesta = SecretariosM::CrearSecretarioM($datosC);
            
            if ($respuesta) {
                $_SESSION['mensaje_secretarios'] = 'Perfil de secretario/a creado correctamente.';
                $_SESSION['tipo_mensaje_secretarios'] = 'success';
            } else {
                $_SESSION['mensaje_secretarios'] = 'Error al crear el perfil.';
                $_SESSION['tipo_mensaje_secretarios'] = 'danger';
            }
            // Redirección con URL limpia
            echo '<script>window.location = "secretarios";</script>';
            exit();
        }
    }



     static public function ActualizarSecretarioC()
    {
        if (isset($_POST["editarSecretario"])) {
            $datosC = [
                "id" => $_POST["idSecretario"],
                "apellido" => trim($_POST["apellidoE"]),
                "nombre" => trim($_POST["nombreE"]),
                "usuario" => trim($_POST["usuarioE"]),
                "id_consultorio" => $_POST["consultorioE"],
                "clave" => null // Por defecto no cambiamos la clave
            ];

            if (!empty($_POST["claveE"])) {
                $datosC["clave"] = password_hash($_POST["claveE"], PASSWORD_DEFAULT);
            }

            $resultado = SecretariosM::ActualizarSecretarioAdminM($datosC);

            if ($resultado) {
                $_SESSION['mensaje_secretarios'] = "Perfil actualizado correctamente.";
                $_SESSION['tipo_mensaje_secretarios'] = "success";
            } else {
                $_SESSION['mensaje_secretarios'] = "Error al actualizar el perfil.";
                $_SESSION['tipo_mensaje_secretarios'] = "danger";
            }
            echo '<script>window.location = "secretarios";</script>';
            exit();
        }
    }
    
    static public function BorrarSecretarioC()
    {
        if (isset($_GET["borrar_id"])) {
            $id = $_GET["borrar_id"];
            
            if(isset($_GET['foto']) && !empty($_GET['foto']) && $_GET['foto'] !== 'Vistas/img/defecto.png') {
                if(file_exists(ROOT_PATH . $_GET['foto'])) {
                    unlink(ROOT_PATH . $_GET['foto']);
                }
            }

            $respuesta = SecretariosM::BorrarSecretarioM($id);
            
            if ($respuesta) {
                $_SESSION['mensaje_secretarios'] = 'Perfil eliminado correctamente.';
                $_SESSION['tipo_mensaje_secretarios'] = 'success';
            } else {
                $_SESSION['mensaje_secretarios'] = 'Error al eliminar el perfil.';
                $_SESSION['tipo_mensaje_secretarios'] = 'danger';
            }
            // Redirección con URL limpia
            echo '<script>window.location = "secretarios";</script>';
            exit();
        }
    }
}