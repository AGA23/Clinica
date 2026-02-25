<?php

require_once "ConexionBD.php";


class ClinicaM {

    
    static public function ObtenerDatosGlobalesM() {
        try {
            // La consulta se basa en la estructura de tu tabla `inicio`.
            // Usamos alias (AS) para que las claves del array sean claras y no dependan
            // de los nombres de columna de la base de datos (ej. 'intro' se convierte en 'nombre_clinica').
            $sql = "SELECT 
                        intro AS nombre_clinica, 
                        cuit AS cuit_clinica,
                        logo AS logo_clinica
                    FROM inicio 
                    WHERE id = 1 
                    LIMIT 1";
            
            $stmt = ConexionBD::getInstancia()->prepare($sql);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si la consulta no devuelve ninguna fila (tabla 'inicio' vacía),
            // devolvemos un array con valores por defecto para evitar errores en la aplicación.
            if (!$resultado) {
                return [
                    'nombre_clinica' => 'Clínica No Configurada',
                    'cuit_clinica'   => 'CUIT No Configurado',
                    'logo_clinica'   => null
                ];
            }

            return $resultado;

        } catch (PDOException $e) {
            // Si ocurre un error de SQL, lo registramos para depuración.
            error_log("Error en ClinicaM::ObtenerDatosGlobalesM: " . $e->getMessage());
            
            // Devolvemos valores por defecto para que la aplicación pueda continuar
            // de forma controlada en lugar de romperse.
            return [
                'nombre_clinica' => 'Error al Cargar Nombre',
                'cuit_clinica'   => 'Error al Cargar CUIT',
                'logo_clinica'   => null
            ];
        }
    }

   public static function ActualizarDatosGlobalesM($datos) {
    try {
        // [CORREGIDO] La consulta usa los nombres de columna correctos de la tabla 'inicio'
        $sql = "UPDATE inicio SET 
                    intro = :intro,
                    cuit = :cuit,
                    logo = :logo,
                    correo = :correo
                WHERE id = 1"; // Se asume que siempre se edita la fila con id = 1
        
        $stmt = ConexionBD::getInstancia()->prepare($sql);
        
        $stmt->bindParam(":intro", $datos["intro"], PDO::PARAM_STR);
        $stmt->bindParam(":cuit", $datos["cuit"], PDO::PARAM_STR);
        $stmt->bindParam(":logo", $datos["logo"], PDO::PARAM_STR);
        $stmt->bindParam(":correo", $datos["correo"], PDO::PARAM_STR);
        
        return $stmt->execute();

    } catch (PDOException $e) {
        error_log("Error en ClinicaM::ActualizarDatosGlobalesM: " . $e->getMessage());
        return false;
    }

}

public static function MarcarAusentesPorCierreConsultorio($id_consultorio) {

    $dia_semana = date('N');
    $hoy = date('Y-m-d');
    $ahora = date('H:i:s');

    // 1. Obtener horario de cierre del consultorio hoy:
    $sql = "SELECT hora_cierre 
            FROM horarios_consultorios 
            WHERE id_consultorio = :id AND dia_semana = :dia";
    $stmt = ConexionBD::getInstancia()->prepare($sql);
    $stmt->execute([
        ':id' => $id_consultorio,
        ':dia' => $dia_semana
    ]);

    $horario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$horario || empty($horario['hora_cierre'])) return;

    $hora_cierre = $horario['hora_cierre'];

    // 2. Si aún no cerró, no marcamos nada:
    if ($ahora < $hora_cierre) return;

    // 3. Marcar como ausentes TODAS las citas del día que estén en estado "Pendiente"
    $sql = "UPDATE citas 
            SET estado = 'Ausente'
            WHERE fecha = :hoy
              AND id_consultorio = :id
              AND estado = 'Pendiente'";

    $stmt = ConexionBD::getInstancia()->prepare($sql);
    $stmt->execute([
        ':hoy' => $hoy,
        ':id' => $id_consultorio
    ]);
}

} 