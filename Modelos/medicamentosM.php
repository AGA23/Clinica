<?php


require_once 'ConexionBD.php';

class MedicamentosM
{
    // --- GESTIÓN DE FÁRMACOS (Nombres Genéricos) ---

    public static function ListarFarmacosM() {
        $stmt = ConexionBD::getInstancia()->prepare("SELECT * FROM farmacos ORDER BY nombre_generico ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public static function ObtenerPresentacionesAprobadasAgrupadasM() {
        $sql = "SELECT p.id, p.presentacion, f.nombre_generico 
                FROM medicamento_presentaciones p
                INNER JOIN farmacos f ON p.id_farmaco = f.id
                WHERE p.estado = 'aprobado'
                ORDER BY f.nombre_generico, p.presentacion ASC";
        
        $pdo = ConexionBD::getInstancia()->prepare($sql);
        $pdo->execute();
        
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);
        
        // Agrupar los resultados por nombre_generico
        $agrupado = [];
        foreach ($resultado as $row) {
            $agrupado[$row['nombre_generico']][] = [
                'id' => $row['id'],
                'presentacion' => $row['presentacion']
            ];
        }
        return $agrupado;
    }


    public static function CrearFarmacoM($nombre_generico) {
        $pdo = ConexionBD::getInstancia();
        $stmt_check = $pdo->prepare("SELECT id FROM farmacos WHERE TRIM(LOWER(nombre_generico)) = TRIM(LOWER(:nombre))");
        $stmt_check->execute([':nombre' => $nombre_generico]);
        if ($stmt_check->fetch()) { return 'duplicado'; }
        $stmt_insert = $pdo->prepare("INSERT INTO farmacos (nombre_generico, estado) VALUES (:nombre, 'aprobado')");
        return $stmt_insert->execute([':nombre' => $nombre_generico]);
    }

    // --- GESTIÓN DE PRESENTACIONES ---

    public static function ObtenerPresentacionesPorFarmacoM($id_farmaco) {
        $stmt = ConexionBD::getInstancia()->prepare("SELECT * FROM medicamento_presentaciones WHERE id_farmaco = :id_farmaco ORDER BY estado DESC, presentacion ASC");
        $stmt->bindParam(":id_farmaco", $id_farmaco, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function CrearPresentacionM($datos) {
        $sql = "INSERT INTO medicamento_presentaciones (id_farmaco, presentacion, es_cronico, observaciones, estado) VALUES (:id_farmaco, :presentacion, :es_cronico, :observaciones, 'aprobado')";
        $pdo = ConexionBD::getInstancia()->prepare($sql);
        return $pdo->execute([
            ':id_farmaco' => $datos['id_farmaco'],
            ':presentacion' => $datos['presentacion'],
            ':es_cronico' => $datos['es_cronico'],
            ':observaciones' => $datos['observaciones']
        ]);
    }

    public static function AprobarPresentacionM($id_presentacion) {
        $stmt = ConexionBD::getInstancia()->prepare("UPDATE medicamento_presentaciones SET estado = 'aprobado' WHERE id = :id AND estado = 'pendiente'");
        return $stmt->execute([':id' => $id_presentacion]);
    }

    public static function EliminarPresentacionM($id_presentacion) {
        $stmt = ConexionBD::getInstancia()->prepare("DELETE FROM medicamento_presentaciones WHERE id = :id");
        return $stmt->execute([':id' => $id_presentacion]);
    }

    // --- MÉTODOS PARA EL DOCTOR ---

    public static function ObtenerTodasPresentacionesAprobadasM() {
        $sql = "SELECT p.id, p.presentacion, p.es_cronico, f.nombre_generico, f.id as id_farmaco 
                FROM medicamento_presentaciones p
                INNER JOIN farmacos f ON p.id_farmaco = f.id
                WHERE p.estado = 'aprobado'
                ORDER BY f.nombre_generico, p.presentacion ASC";
        $pdo = ConexionBD::getInstancia()->prepare($sql);
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }

    

 static public function CrearFarmacoYPresentacionPendienteM($nombre_generico, $presentacion) {
    $pdo = ConexionBD::getInstancia();
    try {
        // --- SE HAN ELIMINADO beginTransaction, commit y rollback ---
        // La transacción ahora es manejada por el controlador que llama a este método.

        // 1. Busca si el fármaco genérico ya existe.
        $stmt_find_f = $pdo->prepare("SELECT id FROM farmacos WHERE TRIM(LOWER(nombre_generico)) = TRIM(LOWER(:nombre))");
        $stmt_find_f->execute([':nombre' => $nombre_generico]);
        $id_farmaco = $stmt_find_f->fetchColumn();

        if (!$id_farmaco) {
            // Si es nuevo, se crea con estado 'pendiente'.
            $stmt_create_f = $pdo->prepare("INSERT INTO farmacos (nombre_generico, estado) VALUES (:nombre, 'pendiente')");
            $stmt_create_f->execute([':nombre' => $nombre_generico]);
            $id_farmaco = $pdo->lastInsertId();
        }

        // 2. Busca si la presentación ya existe para ese fármaco.
        $stmt_find_p = $pdo->prepare("SELECT id FROM medicamento_presentaciones WHERE id_farmaco = :id_farmaco AND TRIM(LOWER(presentacion)) = TRIM(LOWER(:presentacion))");
        $stmt_find_p->execute([':id_farmaco' => $id_farmaco, ':presentacion' => $presentacion]);
        $id_presentacion = $stmt_find_p->fetchColumn();

        if ($id_presentacion) {
            // Si ya existe, simplemente devolvemos su ID.
            return $id_presentacion;
        } else {
            // Si no existe, la creamos con estado 'pendiente'.
            $stmt_create_p = $pdo->prepare("INSERT INTO medicamento_presentaciones (id_farmaco, presentacion, estado) VALUES (:id_farmaco, :presentacion, 'pendiente')");
            $stmt_create_p->execute([':id_farmaco' => $id_farmaco, ':presentacion' => $presentacion]);
            return $pdo->lastInsertId();
        }
    } catch (Exception $e) {
        // Ya no hacemos rollback aquí. Simplemente registramos el error y devolvemos false.
        // La excepción será capturada por el controlador, que se encargará del rollback.
        error_log("Error en CrearFarmacoYPresentacionPendienteM: " . $e->getMessage());
        // Lanzamos la excepción para que el controlador sepa que algo falló.
        throw $e;
    }
}

public static function AprobarFarmacoCompletoM($id_farmaco) {
        $pdo = ConexionBD::getInstancia();
        try {
            $pdo->beginTransaction();
            // 1. Aprueba el fármaco genérico principal
            $stmt1 = $pdo->prepare("UPDATE farmacos SET estado = 'aprobado' WHERE id = :id AND estado = 'pendiente'");
            $stmt1->execute([':id' => $id_farmaco]);
            
            // 2. Aprueba todas sus presentaciones que también estén pendientes
            $stmt2 = $pdo->prepare("UPDATE medicamento_presentaciones SET estado = 'aprobado' WHERE id_farmaco = :id AND estado = 'pendiente'");
            $stmt2->execute([':id' => $id_farmaco]);
            
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error en AprobarFarmacoCompletoM: " . $e->getMessage());
            return false;
        }
    }

    
    public static function RechazarFarmacoCompletoM($id_farmaco) {
        // Gracias a la restricción 'ON DELETE CASCADE' de tu base de datos,
        // al borrar un fármaco, se borrarán automáticamente todas sus presentaciones.
        $stmt = ConexionBD::getInstancia()->prepare("DELETE FROM farmacos WHERE id = :id");
        return $stmt->execute([':id' => $id_farmaco]);
    }
}


?>