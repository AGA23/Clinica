<?php
// En Controladores/MedicamentosC.php (VERSIÓN FINAL, CORREGIDA Y COMPLETA)

class MedicamentosC {

    // --- MÉTODOS PARA LAS VISTAS ---

    /**
     * ¡CORREGIDO! Llama al método correcto del modelo para listar los fármacos.
     * Este método es utilizado por la vista 'medicamentos.php' para construir la tabla.
     * @return array
     */
    static public function ListarMedicamentosC() {
        return MedicamentosM::ListarFarmacosM();
    }

    /**
     * Procesa la creación de un nuevo FÁRMACO GENÉRICO desde el modal del admin.
     * Este método se asegura de que el fármaco no esté duplicado antes de crearlo.
     */
    public function CrearFarmacoC() {
        if (isset($_POST["crear_farmaco"]) && $_SESSION['rol'] === 'Administrador') {
            
            $nombre_generico = trim($_POST['nombre_generico']);

            if (empty($nombre_generico)) {
                $_SESSION['mensaje_medicamentos'] = "Error: El nombre del fármaco no puede estar vacío.";
                $_SESSION['tipo_mensaje_medicamentos'] = "warning";
            } else {
                $resultado = MedicamentosM::CrearFarmacoM($nombre_generico);

                if ($resultado === true) {
                    $_SESSION['mensaje_medicamentos'] = "Fármaco genérico creado y aprobado correctamente.";
                    $_SESSION['tipo_mensaje_medicamentos'] = "success";
                } elseif ($resultado === 'duplicado') {
                    $_SESSION['mensaje_medicamentos'] = "Error: Ya existe un fármaco con ese nombre.";
                    $_SESSION['tipo_mensaje_medicamentos'] = "warning";
                } else {
                    $_SESSION['mensaje_medicamentos'] = "Ocurrió un error al crear el fármaco.";
                    $_SESSION['tipo_mensaje_medicamentos'] = "danger";
                }
            }
            
            // Se redirige fuera del if/else para asegurar que siempre ocurra.
            header("Location: " . BASE_URL . "medicamentos");
            exit();
        }
    }
    
    // --- MÉTODOS PARA LAS ACCIONES AJAX ---

    /**
     * Llama al modelo para aprobar un fármaco completo y todas sus presentaciones.
     * Utilizado por el archivo ajax/medicamentosA.php.
     * @param int $id_farmaco
     * @return array
     */
    public function AprobarFarmacoC($id_farmaco) {
        if (empty($id_farmaco) || !is_numeric($id_farmaco)) {
            return ['success' => false, 'error' => 'ID de fármaco no proporcionado o inválido.'];
        }
        $resultado = MedicamentosM::AprobarFarmacoCompletoM($id_farmaco);
        if ($resultado) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'No se pudo aprobar el fármaco.'];
    }
    
    /**
     * Llama al modelo para rechazar (eliminar) un fármaco completo.
     * Utilizado por el archivo ajax/medicamentosA.php.
     * @param int $id_farmaco
     * @return array
     */
    public function RechazarFarmacoC($id_farmaco) {
        if (empty($id_farmaco) || !is_numeric($id_farmaco)) {
            return ['success' => false, 'error' => 'ID de fármaco no proporcionado o inválido.'];
        }
        $resultado = MedicamentosM::RechazarFarmacoCompletoM($id_farmaco);
        if ($resultado) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'No se pudo rechazar el fármaco. Es posible que tenga dependencias en otras tablas.'];
    }
}
?>