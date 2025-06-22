<?php
// Configurar manejo de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de conexión
require_once 'config/conexion.php';

// Crear una instancia de la conexión
$conexion = new Conexion();
$conn = $conexion->getConnection();

echo "<h2>Revirtiendo cambios en las tablas</h2>";

// 1. Eliminar la tabla contact_images si existe
try {
    $query = "DROP TABLE IF EXISTS contact_images";
    $conn->exec($query);
    echo "Tabla contact_images eliminada correctamente.<br>";
} catch (PDOException $e) {
    echo "Error al eliminar la tabla contact_images: " . $e->getMessage() . "<br>";
}

// 2. Verificar y eliminar las columnas de ruta de imagen de la tabla agcontactos
try {
    // Verificar si las columnas existen
    $checkColumns = $conn->query("SHOW COLUMNS FROM agcontactos LIKE 'foto%_path'");
    $columnsToRemove = [];
    
    while ($column = $checkColumns->fetch(PDO::FETCH_ASSOC)) {
        $columnsToRemove[] = $column['Field'];
    }
    
    if (count($columnsToRemove) > 0) {
        echo "Se encontraron las siguientes columnas para eliminar: " . implode(", ", $columnsToRemove) . "<br>";
        
        // Eliminar cada columna individualmente
        foreach ($columnsToRemove as $column) {
            try {
                $conn->exec("ALTER TABLE agcontactos DROP COLUMN `$column`");
                echo "Columna $column eliminada correctamente.<br>";
            } catch (PDOException $e1) {
                echo "Error al eliminar la columna $column: " . $e1->getMessage() . "<br>";
            }
        }
    } else {
        echo "No se encontraron columnas de ruta de imagen para eliminar.<br>";
    }
} catch (PDOException $e) {
    echo "Error al verificar las columnas: " . $e->getMessage() . "<br>";
}

// 3. Eliminar el directorio de imágenes si se creó
$uploadDir = 'uploads/contacts';
if (file_exists($uploadDir)) {
    echo "El directorio $uploadDir existe. Para eliminarlo manualmente, use el administrador de archivos.<br>";
    echo "No se eliminará automáticamente para evitar la pérdida accidental de datos.<br>";
}

echo "<h2>Proceso completado</h2>";
echo "Se han revertido los cambios en las tablas de la base de datos.<br>";
?>