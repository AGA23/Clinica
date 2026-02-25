<?php
// test_planes.php - PRUEBA AISLADA

// 1. Configuración manual de BD (Ajusta tus credenciales)
$host = "localhost";
$db   = "clinica";
$user = "root";
$pass = "";

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Prueba de Diagnóstico de Planes</h1>";

try {
    // 2. Conexión directa
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8");
    
    echo "<p style='color:green'>✅ Conexión a Base de Datos exitosa.</p>";

    // 3. Verificar si existe la tabla
    $stmt = $pdo->query("SHOW TABLES LIKE 'planes_obras'");
    if($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ Tabla 'planes_obras' encontrada.</p>";
    } else {
        die("<p style='color:red'>❌ ERROR: La tabla 'planes_obras' NO existe en la base de datos '$db'.</p>");
    }

    // 4. Verificar ID de Obra Social (CAMBIA ESTE ID POR UNO QUE SEPAS QUE TIENE PLANES)
    $id_prueba = 0; // <--- PONE AQUÍ EL ID DE LA OBRA SOCIAL QUE TIENE PLANES
    
    echo "<h3>Buscando planes para Obra Social ID: $id_prueba</h3>";

    $sql = "SELECT * FROM planes_obras WHERE id_obra_social = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":id", $id_prueba, PDO::PARAM_INT);
    $stmt->execute();
    $planes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($planes) > 0) {
        echo "<p style='color:green'>✅ ¡ÉXITO! Se encontraron " . count($planes) . " planes:</p>";
        echo "<pre>";
        print_r($planes);
        echo "</pre>";
    } else {
        echo "<p style='color:orange'>⚠️ La consulta funcionó, pero retornó 0 planes para el ID $id_prueba.</p>";
        
        // Ver qué hay en la tabla
        echo "<hr><strong>Contenido total de la tabla planes_obras:</strong><br>";
        $all = $pdo->query("SELECT * FROM planes_obras")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($all);
        echo "</pre>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>❌ ERROR FATAL DE CONEXIÓN/SQL: " . $e->getMessage() . "</p>";
}
?>