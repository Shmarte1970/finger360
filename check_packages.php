<?php
// Verificar paquetes instalados
echo "<h1>Verificación de paquetes instalados</h1>";

// Verificar si el archivo composer.json existe
if (file_exists(__DIR__ . '/composer.json')) {
    echo "<p>El archivo composer.json existe.</p>";
    $composer_json = file_get_contents(__DIR__ . '/composer.json');
    echo "<pre>" . htmlspecialchars($composer_json) . "</pre>";
} else {
    echo "<p>El archivo composer.json no existe.</p>";
}

// Verificar si el directorio vendor existe
if (is_dir(__DIR__ . '/vendor')) {
    echo "<p>El directorio vendor existe.</p>";
    
    // Verificar si el archivo autoload.php existe
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        echo "<p>El archivo vendor/autoload.php existe.</p>";
    } else {
        echo "<p>El archivo vendor/autoload.php no existe.</p>";
    }
    
    // Verificar si el directorio sendgrid existe
    if (is_dir(__DIR__ . '/vendor/sendgrid')) {
        echo "<p>El directorio vendor/sendgrid existe.</p>";
    } else {
        echo "<p>El directorio vendor/sendgrid no existe.</p>";
    }
} else {
    echo "<p>El directorio vendor no existe.</p>";
}

// Verificar las clases disponibles
echo "<h2>Clases disponibles:</h2>";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Verificar si la clase SendGrid existe
    if (class_exists('SendGrid')) {
        echo "<p>La clase SendGrid existe.</p>";
    } else {
        echo "<p>La clase SendGrid no existe.</p>";
    }
    
    // Verificar si la clase SendGrid\Mail\Mail existe
    if (class_exists('SendGrid\Mail\Mail')) {
        echo "<p>La clase SendGrid\Mail\Mail existe.</p>";
    } else {
        echo "<p>La clase SendGrid\Mail\Mail no existe.</p>";
    }
} catch (Exception $e) {
    echo "<p>Error al cargar el autoloader: " . $e->getMessage() . "</p>";
}

// Verificar las extensiones de PHP
echo "<h2>Extensiones de PHP:</h2>";
$extensions = get_loaded_extensions();
echo "<pre>" . print_r($extensions, true) . "</pre>";

// Verificar la versión de PHP
echo "<h2>Versión de PHP:</h2>";
echo "<p>" . phpversion() . "</p>";
?>