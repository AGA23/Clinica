<?php
// verificar.php

// 1. Incluimos nuestro cargador principal, que se encarga de todo.
require_once 'loader.php'; 

// Las clases (CitasM) y la conexión a la BD ya están disponibles gracias al loader.

$uuid = isset($_GET['uuid']) ? trim($_GET['uuid']) : null;
$documento = null;

if ($uuid) {
    // Usamos el método que creamos en CitasM para buscar el UUID en ambas columnas.
    $documento = CitasM::mdlVerificarDocumentoPorUuid($uuid);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Documento Médico</title>
    
    <!-- Usamos un CDN de Bootstrap para un estilo rápido y limpio -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    
    <style>
        body { background-color: #f4f4f4; font-family: sans-serif; }
        .container { max-width: 800px; margin-top: 40px; }
        .document-content { border: 1px solid #ddd; padding: 20px; background: #fff; margin-top: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .panel-heading h4 { margin: 0; }
        code { background-color: #eee; padding: 2px 4px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center">
            <!-- Asumo que tienes un logo en esta ruta. Si no, quita o cambia la línea -->
            <img src="Vistas/img/plantilla/logo.png" alt="Logo Clínica" style="max-height: 80px; margin-bottom: 20px;">
        </div>

        <?php if ($documento && !empty($documento['contenido'])): ?>
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h4><i class="glyphicon glyphicon-ok-circle"></i> Documento Válido y Verificado</h4>
                </div>
                <div class="panel-body">
                    <p>Se ha verificado la autenticidad del siguiente documento con identificador único:</p>
                    <p><strong><code><?php echo htmlspecialchars($uuid); ?></code></strong></p>
                    <p><strong>Tipo de Documento:</strong> <?php echo htmlspecialchars($documento['tipo_doc']); ?></p>
                    
                    <div class="document-content">
                        <?php 
                            // Mostramos el contenido HTML tal cual está guardado en la base de datos.
                            // Esto es seguro porque tú controlas las plantillas y los reemplazos.
                            echo $documento['contenido']; 
                        ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="panel panel-danger">
                <div class="panel-heading">
                    <h4><i class="glyphicon glyphicon-remove-circle"></i> Documento Inválido o No Encontrado</h4>
                </div>
                <div class="panel-body">
                    <p>El identificador <strong><code><?php echo htmlspecialchars($uuid ?? 'inválido'); ?></code></strong> no corresponde a ningún documento emitido por nuestro sistema. Por favor, verifique el enlace o el código QR.</p>
                </div>
            </div>
        <?php endif; ?>
         <div class="text-center" style="margin-top: 20px; color: #777;">
            <p>&copy; <?php echo date("Y"); ?> Tu Clínica. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>