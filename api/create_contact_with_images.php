<?php
// Iniciar sesión
session_start();

// Log para depuración
error_log("create_contact_with_images.php - Método: " . $_SERVER['REQUEST_METHOD']);
error_log("Sesión actual: " . print_r($_SESSION, true));

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    // Log de error
    error_log("Error: Usuario no autenticado");
    
    // Responder con error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener el ID del usuario de la sesión
$userId = $_SESSION['user_id'];
error_log("ID de usuario: $userId");

// Incluir el archivo de conexión a la base de datos
require_once '../config/conexion.php';
$conexion = new Conexion();

// Función para validar los datos del contacto
function validateContactData($data) {
    $errors = [];
    
    // Validar nombre del contacto
    if (empty($data['nomContacto'])) {
        $errors[] = "El nombre del contacto es obligatorio";
    }
    
    // Validar teléfono
    if (empty($data['telefonoContacto'])) {
        $errors[] = "El teléfono es obligatorio";
    } elseif (!preg_match('/^[0-9+\s()-]{6,20}$/', $data['telefonoContacto'])) {
        $errors[] = "El formato del teléfono no es válido";
    }
    
    return $errors;
}

// Función para guardar una imagen y devolver su URI
function saveImage($file, $contactId, $fieldName) {
    // Directorio donde se guardarán las imágenes
    $uploadDir = '../assets/img/contacts/';
    
    // Crear el directorio si no existe
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generar un nombre único para el archivo
    $fileName = $contactId . '_' . $fieldName . '_' . time() . '.jpg';
    $filePath = $uploadDir . $fileName;
    
    // Mover el archivo subido al directorio de destino
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Devolver la URI relativa para guardar en la base de datos
        return 'assets/img/contacts/' . $fileName;
    }
    
    return null;
}

// Verificar si se recibieron datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Procesando solicitud POST para crear contacto con imágenes");
    
    // Obtener los datos del contacto (enviados como JSON en un campo del formulario)
    $contactData = isset($_POST['contactData']) ? json_decode($_POST['contactData'], true) : null;
    
    error_log("Datos del contacto recibidos: " . print_r($contactData, true));
    error_log("Archivos recibidos: " . print_r($_FILES, true));
    
    if (!$contactData) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No se recibieron datos del contacto']);
        exit;
    }
    
    // Validar datos
    $errors = validateContactData($contactData);
    
    if (!empty($errors)) {
        error_log("Errores de validación: " . implode(", ", $errors));
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
        exit;
    }
    
    try {
        // Preparar los datos para insertar
        $insertData = [
            'idUser' => $userId,
            'nomContacto' => trim($contactData['nomContacto']),
            'telefonoContacto' => trim($contactData['telefonoContacto']),
            'adress' => isset($contactData['adress']) ? trim($contactData['adress']) : '',
            'sexo' => isset($contactData['sexo']) ? trim($contactData['sexo']) : '',
            'creadoContacto' => date('Y-m-d H:i:s'),
            'enable' => 1
        ];
        
        // Insertar el contacto
        $contactId = $conexion->insert('agcontactos', $insertData);
        
        if (!$contactId) {
            error_log("Error al crear el contacto");
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No se pudo crear el contacto']);
            exit;
        }
        
        error_log("Contacto creado con ID: $contactId");
        
        // Procesar las imágenes si existen
        $imageFields = ['foto1', 'foto2', 'foto3'];
        $imageUpdates = [];
        
        foreach ($imageFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $imageUri = saveImage($_FILES[$field], $contactId, $field);
                
                if ($imageUri) {
                    $imageUpdates[$field] = $imageUri;
                    error_log("Imagen $field guardada en: $imageUri");
                }
            }
        }
        
        // Actualizar el contacto con las URIs de las imágenes si hay alguna
        if (!empty($imageUpdates)) {
            $result = $conexion->update('agcontactos', $imageUpdates, 'idContacto = ?', [$contactId]);
            error_log("Actualización de imágenes: " . ($result ? "Exitosa" : "Fallida"));
        }
        
        // Obtener el contacto recién creado
        $sql = "SELECT * FROM agcontactos WHERE idContacto = :idContacto";
        $newContact = $conexion->getRow($sql, [':idContacto' => $contactId]);
        
        // Responder con éxito
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Contacto creado correctamente',
            'contact' => $newContact
        ]);
    } catch (PDOException $e) {
        error_log("Error de PDO al crear contacto: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Error general al crear contacto: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
    }
} else {
    // Método no permitido
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}