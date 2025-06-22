<?php
// Iniciar sesión
session_start();

// Log para depuración
error_log("update_contact_with_images.php - Método: " . $_SERVER['REQUEST_METHOD']);
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

// Función para eliminar una imagen antigua
function deleteOldImage($oldImagePath) {
    if (!empty($oldImagePath)) {
        $fullPath = '../' . $oldImagePath;
        if (file_exists($fullPath)) {
            error_log("Eliminando imagen antigua: " . $fullPath);
            unlink($fullPath);
            return true;
        } else {
            error_log("La imagen antigua no existe: " . $fullPath);
        }
    }
    return false;
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
    error_log("Procesando solicitud POST para actualizar contacto con imágenes");
    
    // Obtener los datos del contacto (enviados como JSON en un campo del formulario)
    $contactData = isset($_POST['contactData']) ? json_decode($_POST['contactData'], true) : null;
    
    error_log("Datos del contacto recibidos: " . print_r($contactData, true));
    error_log("Archivos recibidos: " . print_r($_FILES, true));
    
    if (!$contactData) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No se recibieron datos del contacto']);
        exit;
    }
    
    // Verificar que se proporcionó un ID de contacto
    if (!isset($contactData['idContacto']) || empty($contactData['idContacto'])) {
        error_log("Error: No se proporcionó ID de contacto");
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Se requiere ID de contacto']);
        exit;
    }
    
    $contactId = $contactData['idContacto'];
    
    // Validar datos
    $errors = validateContactData($contactData);
    
    if (!empty($errors)) {
        error_log("Errores de validación: " . implode(", ", $errors));
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
        exit;
    }
    
    try {
        // Verificar que el contacto pertenece al usuario
        $sql = "SELECT COUNT(*) FROM agcontactos WHERE idContacto = :idContacto AND idUser = :user_id";
        $count = $conexion->getRow($sql, [':idContacto' => $contactId, ':user_id' => $userId]);
        
        if (!$count || $count['COUNT(*)'] == 0) {
            error_log("Error: El contacto no pertenece al usuario o no existe");
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Contacto no encontrado o no autorizado']);
            exit;
        }
        
        // Preparar los datos para actualizar
        $updateData = [
            'nomContacto' => trim($contactData['nomContacto']),
            'telefonoContacto' => trim($contactData['telefonoContacto']),
            'adress' => isset($contactData['adress']) ? trim($contactData['adress']) : '',
            'sexo' => isset($contactData['sexo']) ? trim($contactData['sexo']) : ''
        ];
        
        // Actualizar el contacto
        $result = $conexion->update('agcontactos', $updateData, 'idContacto = ?', [$contactId]);
        
        if (!$result) {
            error_log("No se realizaron cambios en los datos básicos del contacto");
        } else {
            error_log("Datos básicos del contacto actualizados correctamente");
        }
        
        // Obtener las imágenes actuales del contacto
        $sql = "SELECT foto1, foto2, foto3 FROM agcontactos WHERE idContacto = :idContacto";
        $currentImages = $conexion->getRow($sql, [':idContacto' => $contactId]);
        error_log("Imágenes actuales: " . print_r($currentImages, true));
        
        // Procesar las imágenes si existen
        $imageFields = ['foto1', 'foto2', 'foto3'];
        $imageUpdates = [];
        
        foreach ($imageFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                // Eliminar la imagen antigua si existe
                if (!empty($currentImages[$field])) {
                    deleteOldImage($currentImages[$field]);
                }
                
                // Guardar la nueva imagen
                $imageUri = saveImage($_FILES[$field], $contactId, $field);
                
                if ($imageUri) {
                    $imageUpdates[$field] = $imageUri;
                    error_log("Imagen $field guardada en: $imageUri");
                }
            }
        }
        
        // Actualizar el contacto con las URIs de las imágenes si hay alguna
        if (!empty($imageUpdates)) {
            $imageResult = $conexion->update('agcontactos', $imageUpdates, 'idContacto = ?', [$contactId]);
            error_log("Actualización de imágenes: " . ($imageResult ? "Exitosa" : "Fallida"));
            $result = $result || $imageResult; // Considerar éxito si se actualizaron datos o imágenes
        }
        
        // Obtener el contacto actualizado
        $sql = "SELECT * FROM agcontactos WHERE idContacto = :idContacto";
        $updatedContact = $conexion->getRow($sql, [':idContacto' => $contactId]);
        
        // Responder con éxito
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => $result ? 'Contacto actualizado correctamente' : 'No se realizaron cambios',
            'contact' => $updatedContact
        ]);
    } catch (PDOException $e) {
        error_log("Error de PDO al actualizar contacto: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Error general al actualizar contacto: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
    }
} else {
    // Método no permitido
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}