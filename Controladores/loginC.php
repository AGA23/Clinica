<?php
// En Controladores/LoginC.php (VERSIÓN FINAL Y COMPLETA)

// No se necesita require_once aquí porque el loader.php, cargado por index.php,
// se encarga de encontrar la clase LoginM cuando se necesite.

class LoginC
{
    /**
     * Procesa el intento de inicio de sesión del usuario.
     */
    public function procesarLogin()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        $usuario_ingresado = $_POST['usuario-Ing'] ?? '';
        $clave_ingresada = $_POST['clave-Ing'] ?? '';

        if (empty($usuario_ingresado) || empty($clave_ingresada)) {
            $this->redireccionarConError('2');
        }

        $datosUsuario = LoginM::ObtenerUsuarioPorUsername($usuario_ingresado);

        if ($datosUsuario && password_verify($clave_ingresada, $datosUsuario['clave'])) {
            $this->crearSesionUsuario($datosUsuario);
            header("Location: " . BASE_URL . "inicio");
            exit();
        } else {
            $this->redireccionarConError('1');
        }
    }

    /**
     * Crea y establece todas las variables de sesión para el usuario autenticado.
     */
    private function crearSesionUsuario(array $datosUsuario)
    {
        session_regenerate_id(true);

        $_SESSION["Ingresar"] = true;
        $_SESSION["id"] = (int)$datosUsuario["id"];
        $_SESSION["rol"] = $datosUsuario["rol"];
        $_SESSION["nombre"] = $datosUsuario["nombre"];
        $_SESSION["apellido"] = $datosUsuario["apellido"];
        $_SESSION["usuario"] = $datosUsuario["usuario"];
        $_SESSION["foto"] = $datosUsuario["foto"];

        // ¡CAMBIO CLAVE!
        // Añadimos el id_consultorio a la sesión.
        // El '?? null' asegura que si el usuario no es un secretario (y por lo tanto
        // no tiene este dato), la variable de sesión se cree como nula y no cause un error.
        if (isset($datosUsuario['rol']) && $datosUsuario['rol'] === 'Secretaria') {
    $_SESSION["id_consultorio"] = !empty($datosUsuario["id_consultorio"]) ? $datosUsuario["id_consultorio"] : 0;
} else {
    $_SESSION["id_consultorio"] = null; // Otros roles no necesitan consultorio
}

    }

    /**
     * Redirige al usuario a la página de login con un código de error.
     */
    private function redireccionarConError(string $codigoError)
    {
        sleep(1);
        header("Location: " . BASE_URL . "index.php?error=" . $codigoError);
        exit();
    }
}