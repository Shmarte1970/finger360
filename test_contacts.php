<?php
// Iniciar sesión
session_start();

// Configurar cabeceras para mostrar texto plano
header('Content-Type: text/plain');

echo "=== Test de conexión a la base de datos y obtención de contactos ===\n\n";

// Verificar si hay una sesión activa
echo "Información de sesión:\n";
echo "SESSION: " . print_r($_SESSION, true) . "\n\n";

// Si no hay sesión, crear una temporal para pruebas
if (!isset($_SESSION['user_id'])) {
    echo "No hay sesión activa. Creando una sesión temporal para pruebas...\n";
    $_SESSION['user_id'] = 1; // Asumiendo que el ID 1 existe en la base de datos
    echo "ID de usuario temporal: " . $_SESSION['user_id'] . "\n\n";
}

// Incluir el archivo de conexión
echo "Incluyendo archivo de conexión...\n";
try {
    require_once 'config/conexion.php';
    echo "Archivo de conexión incluido correctamente.\n\n";
} catch (Exception $e) {
    echo "Error al incluir el archivo de conexión: " . $e->getMessage() . "\n";
    exit;
}

// Crear conexión
echo "Creando conexión a la base de datos...\n";
try {
    $conexion = new Conexion();
    $conn = $conexion->getConnection();
    echo "Conexión establecida correctamente.\n\n";
} catch (Exception $e) {
    echo "Error al conectar con la base de datos: " . $e->getMessage() . "\n";
    exit;
}

// Consultar contactos
echo "Consultando contactos para el usuario " . $_SESSION['user_id'] . "...\n";
try {
    $sql = "SELECT idContacto, idUser, nomContacto, telefonoContacto FROM agcontactos WHERE idUser = :user_id ORDER BY nomContacto ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Consulta ejecutada correctamente.\n";
    echo "Número de contactos encontrados: " . count($contacts) . "\n\n";
    
    if (count($contacts) > 0) {
        echo "Primeros 5 contactos:\n";
        $i = 0;
        foreach ($contacts as $contact) {
            echo "- ID: " . $contact['idContacto'] . ", Nombre: " . $contact['nomContacto'] . ", Teléfono: " . $contact['telefonoContacto'] . "\n";
            $i++;
            if ($i >= 5) break;
        }
        echo "\n";
    } else {
        echo "No se encontraron contactos para este usuario.\n\n";
    }
    
    // Intentar generar JSON
    echo "Intentando generar JSON con los contactos...\n";
    $response = [
        'success' => true,
        'contacts' => $contacts
    ];
    
    $json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if ($json === false) {
        echo "Error al generar JSON: " . json_last_error_msg() . "\n";
    } else {
        echo "JSON generado correctamente.\n";
        echo "Longitud del JSON: " . strlen($json) . " caracteres\n";
        echo "Primeros 200 caracteres del JSON:\n" . substr($json, 0, 200) . "...\n\n";
    }
    
} catch (PDOException $e) {
    echo "Error de PDO al consultar contactos: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error general al consultar contactos: " . $e->getMessage() . "\n";
}

echo "\n=== Fin del test ===\n";