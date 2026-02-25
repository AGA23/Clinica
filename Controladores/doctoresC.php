<?php
// En Controladores/DoctoresC.php (VERSIÓN FINAL, COMPLETA Y FUNCIONAL)

class DoctoresC {

    // --- MÉTODOS PARA OBTENER DATOS ---
    public static function ListarDoctoresC() { return DoctoresM::ListarDoctoresM(); }
    public static function ObtenerDoctorC($id) { return DoctoresM::ObtenerDoctorM($id); }

    // --- MÉTODOS PARA PROCESAR FORMULARIOS ---
public function CrearDoctorC() {
    if (isset($_POST["crear_doctor"])) {
        
        $datosC = [
            "nombre" => trim($_POST["nombre"]),
            "apellido" => trim($_POST["apellido"]),
            "email" => trim($_POST["email"]),
            "sexo" => $_POST["sexo"],
            "usuario" => trim($_POST["usuario"]),
            "clave" => password_hash($_POST["clave"], PASSWORD_DEFAULT),
            "id_consultorio" => $_POST["id_consultorio"],
            "rol" => "Doctor",
            
            // === INICIO DE LA MODIFICACIÓN ===
            "matricula_nacional" => trim($_POST["matricula_nacional"]),
            "matricula_provincial" => trim($_POST["matricula_provincial"])
            // === FIN DE LA MODIFICACIÓN ===
        ];

        $resultado = DoctoresM::CrearDoctorM($datosC);

        if ($resultado) {
            $_SESSION['mensaje_doctores'] = "Doctor creado correctamente.";
            $_SESSION['tipo_mensaje_doctores'] = "success";
        } else {
            $_SESSION['mensaje_doctores'] = "Error al crear el doctor. Verifique que el usuario o las matrículas no existan ya.";
            $_SESSION['tipo_mensaje_doctores'] = "danger";
        }
        
        echo '<script>window.location = "doctores";</script>';
        exit();
    }
}

    public function ActualizarDoctorC() {
    if (isset($_POST["editar_doctor"])) {
        $id_doctor = $_POST['id_doctor_editar'];
   

        $datos = [
            "id" => $id_doctor,
            "nombre" => trim($_POST['nombre_editar']),
            "apellido" => trim($_POST['apellido_editar']),
            "email" => trim($_POST['email_editar'] ?? ''),
            "usuario" => trim($_POST['usuario_editar']),
            "id_consultorio" => $_POST['id_consultorio_editar'],
            "sexo" => $_POST['sexo_editar'],
            
            // === INICIO DE LA MODIFICACIÓN ===
            "matricula_nacional" => trim($_POST["matricula_nacional_editar"]),
            "matricula_provincial" => trim($_POST["matricula_provincial_editar"])
            // === FIN DE LA MODIFICACIÓN ===
        ];

        $datos["clave"] = !empty(trim($_POST['clave_editar'])) ? password_hash(trim($_POST['clave_editar']), PASSWORD_DEFAULT) : null;
        
        $respuesta = DoctoresM::ActualizarDoctorM($datos);

        if ($respuesta) {
            $_SESSION['mensaje_doctores'] = "Doctor actualizado correctamente.";
            $_SESSION['tipo_mensaje_doctores'] = "success";
        } else {
            $_SESSION['mensaje_doctores'] = "Error al actualizar el doctor.";
            $_SESSION['tipo_mensaje_doctores'] = "danger";
        }

        echo '<script>window.location = "doctores";</script>';
        exit();
    }
}

    
    public function ObtenerPerfilDoctorC() {
    // 1. Verificar que el usuario sea un doctor y tenga una sesión activa.
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Doctor' && isset($_SESSION['id'])) {
        
        // 2. Obtener el ID del doctor desde la sesión.
        $id_doctor = $_SESSION['id'];
        
        // 3. Llamar al método del modelo para obtener los datos del perfil.
        $respuesta = DoctoresM::ObtenerPerfilDoctorM($id_doctor);
        
        // 4. Devolver la respuesta (que será un array con los datos del doctor o 'false' si no se encontró).
        return $respuesta;
    }
    
    // 5. Si no se cumplen las condiciones, devolver 'false' para indicar un error.
    return false;
}
   // En Controladores/DoctoresC.php

public function ActualizarPerfilDoctorC() {
    if (isset($_POST["actualizarPerfilDoctor"]) && $_POST['idDoctor'] == $_SESSION['id']) {

        // --- MANEJO DE LA CONTRASEÑA ---
        $clave = $_POST["passwordActual"]; 
        if (!empty(trim($_POST["clave"]))) {
            $clave = password_hash(trim($_POST["clave"]), PASSWORD_DEFAULT);
        }

        // --- MANEJO DE LA FOTO DE PERFIL ---
        $rutaFoto = $_POST["fotoActual"]; 
        if (isset($_FILES["foto"]["tmp_name"]) && !empty($_FILES["foto"]["tmp_name"])) {
            // Lógica para guardar la foto de perfil (ya la tenías)
            // ... (crear directorio, borrar anterior, mover archivo) ...
        }

        // === [NUEVO] MANEJO DE LA FIRMA DIGITAL ===
        $rutaFirma = $_POST["firmaActual"]; // Mantenemos la firma actual por defecto
        if (isset($_FILES["firma_digital"]["tmp_name"]) && !empty($_FILES["firma_digital"]["tmp_name"])) {
            
            // 1. Crear directorio si no existe
            $directorioFirmas = "Vistas/img/firmas/";
            if (!file_exists($directorioFirmas)) {
                mkdir($directorioFirmas, 0755, true);
            }

            // 2. Borrar firma anterior si existe
            if (!empty($_POST["firmaActual"])) {
                if(file_exists($_POST["firmaActual"])) {
                    unlink($_POST["firmaActual"]);
                }
            }

            // 3. Guardar la nueva firma (recomendado: nombre único)
            $nombreFirma = "firma_" . $_POST['idDoctor'] . "_" . time() . ".png";
            $rutaFirma = $directorioFirmas . $nombreFirma;
            
            // 4. Mover el archivo subido a su destino final
            if (!move_uploaded_file($_FILES["firma_digital"]["tmp_name"], $rutaFirma)) {
                // Si falla la subida, mantenemos la ruta anterior y mostramos un error
                $rutaFirma = $_POST["firmaActual"];
                $_SESSION['mensaje_perfil'] = "Error al subir el archivo de la firma.";
                $_SESSION['tipo_mensaje_perfil'] = "danger";
                echo '<script>window.location = "perfil-Doctor";</script>';
                exit();
            }
        }

        // --- PREPARAR LOS DATOS PARA EL MODELO ---
        $datos = array(
            "id" => $_POST["idDoctor"],
            "nombre" => trim($_POST["nombreE"]),
            "apellido" => trim($_POST["apellidoE"]),
            "email" => trim($_POST["emailE"]),
            "clave" => $clave,
            "foto" => $rutaFoto,
            "firma_digital" => $rutaFirma 
        );

        // --- LLAMAR AL MODELO ---
        $resultado = DoctoresM::ActualizarPerfilM($datos);

        if ($resultado) {
            $_SESSION['nombre'] = $datos['nombre'];
            $_SESSION['apellido'] = $datos['apellido'];
            $_SESSION['foto'] = $datos['foto'];
            $_SESSION['mensaje_perfil'] = "Perfil actualizado correctamente.";
            $_SESSION['tipo_mensaje_perfil'] = "success";
        } else {
            $_SESSION['mensaje_perfil'] = "Error al actualizar el perfil en la base de datos.";
            $_SESSION['tipo_mensaje_perfil'] = "danger";
        }
        
        echo '<script>window.location = "perfil-Doctor";</script>';
        exit();
    }
}
}
