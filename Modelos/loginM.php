<?php
// En Modelos/LoginM.php (VERSIÓN FINAL Y COMPLETA)

require_once "ConexionBD.php";

class LoginM
{
    /**
     * Busca un usuario por su nombre de usuario en todas las tablas de roles.
     * Devuelve los datos del primer usuario que encuentre, incluyendo el hash de la contraseña.
     *
     * @param string $usuario
     * @return array|false
     */
    public static function ObtenerUsuarioPorUsername(string $usuario)
    {
        $conexion = ConexionBD::getInstancia();
        
        // Lista de tablas donde buscar al usuario, en orden de prioridad.
        $tablas_roles = ['administradores', 'doctores', 'secretarios', 'pacientes'];
        
        foreach ($tablas_roles as $tabla) {
            try {
                // ¡CAMBIO CLAVE!
                // La consulta SQL ahora es dinámica. Si estamos buscando en la tabla 'secretarios',
                // también pedimos la columna 'id_consultorio'.
                if ($tabla === 'secretarios') {
                    $sql = "SELECT id, usuario, clave, rol, nombre, apellido, foto, id_consultorio FROM $tabla WHERE usuario = :usuario LIMIT 1";
                } else {
                    $sql = "SELECT id, usuario, clave, rol, nombre, apellido, foto FROM $tabla WHERE usuario = :usuario LIMIT 1";
                }
                
                $stmt = $conexion->prepare($sql);
                $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
                $stmt->execute();
                
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Si encontramos al usuario en esta tabla, lo devolvemos y terminamos la búsqueda.
                if ($resultado) {
                    return $resultado;
                }

            } catch (PDOException $e) {
                error_log("Error buscando en la tabla '$tabla': " . $e->getMessage());
            }
        }
        
        // Si no lo encontramos en ninguna tabla, devolvemos false.
        return false;
    }
}