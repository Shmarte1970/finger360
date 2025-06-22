<?php
// Iniciar sesión
session_start();

// Log para depuración
error_log("update_profile.php - Método: " . $_SERVER['REQUEST_METHOD']);
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
error_log("Intentando incluir el archivo de conexión: ../config/conexion.php");
try {
    require_once '../config/conexion.php';
    error_log("Archivo de conexión incluido correctamente");
} catch (Exception $e) {
    error_log("Error al incluir el archivo de conexión: " . $e->getMessage());
    
    // Responder con error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos: ' . $e->getMessage()]);
    exit;
}

// Crear una instancia de la clase Conexion
$conexion = new Conexion();
$conn = $conexion->getConnection();

// Modo GET: Obtener datos del perfil del usuario
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("Procesando solicitud GET para obtener datos del perfil");
    
    try {
        // Preparar la consulta para obtener los datos del usuario
        $sql = "SELECT nomUsuario, emailUser FROM agusers WHERE iduser = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        error_log("Consulta ejecutada: " . $sql);
        
        // Verificar si se encontró el usuario
        if ($stmt->rowCount() > 0) {
            // Obtener los datos
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Crear array para depuración
            $dataUser = [
                'username' => $user['nomUsuario'],
                'email' => $user['emailUser']
            ];
            
            error_log("Datos encontrados: " . print_r($dataUser, true));
            
            // Responder con los datos
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'username' => $user['nomUsuario'],
                'email' => $user['emailUser']
            ]);
        } else {
            // No se encontró el usuario
            error_log("Error: Usuario con ID $userId no encontrado en la base de datos");
            
            // Intentar usar datos de la sesión como respaldo
            $fallbackData = [
                'success' => true,
                'username' => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Usuario',
                'email' => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'email@example.com'
            ];
            
            error_log("Usando datos de respaldo: " . print_r($fallbackData, true));
            
            header('Content-Type: application/json');
            echo json_encode($fallbackData);
        }
    } catch (PDOException $e) {
        // Registrar el error
        error_log('Error de PDO al obtener datos de perfil: ' . $e->getMessage());
        
        // Responder con error
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error en la base de datos: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        // Capturar cualquier otro error
        error_log('Error general al obtener datos de perfil: ' . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error en el servidor: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Modo POST: Actualizar datos del perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Procesando solicitud POST para actualizar perfil");
    error_log("Datos recibidos: " . print_r($_POST, true));
    
    // Obtener los datos enviados
    $username = isset($_POST['nomUsuario']) ? trim($_POST['nomUsuario']) : '';
    $email = isset($_POST['emailUser']) ? trim($_POST['emailUser']) : '';
    $password = isset($_POST['passwordUser']) ? $_POST['passwordUser'] : '';

    // Validar datos
    if (empty($username) || empty($email)) {
        error_log("Error: Campos obligatorios vacíos");
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Nombre de usuario y correo electrónico son obligatorios']);
        exit;
    }

    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Error: Formato de email inválido: $email");
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'El formato del correo electrónico no es válido']);
        exit;
    }

    try {
        // Iniciar transacción
        $conn->beginTransaction();
        error_log("Transacción iniciada");

        // Preparar la consulta base
        $updateFields = "nomUsuario = :username, emailUser = :email, creado = NOW()";
        $params = [
            ':user_id' => $userId,
            ':username' => $username,
            ':email' => $email
        ];

        // Si se está actualizando la contraseña, incluirla en la actualización
        if (!empty($password)) {
            error_log("Actualizando también la contraseña");
            
            // Validar la contraseña
            if (strlen($password) < 12 || !preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                error_log("Error: Contraseña no cumple con los requisitos");
                
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 12 caracteres y contener letras y números']);
                exit;
            }

            // Encriptar la contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateFields .= ", passwordUser = :password";
            $params[':password'] = $hashedPassword;
        }

        // Construir la consulta completa
        $sql = "UPDATE agusers SET $updateFields WHERE iduser = :user_id";
        error_log("Consulta a ejecutar: " . $sql);
        error_log("Parámetros: " . print_r($params, true));
        
        // Preparar y ejecutar la consulta
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            // Confirmar transacción
            $conn->commit();
            error_log("Actualización exitosa, transacción confirmada");
            
            // Actualizar la información en la sesión
            $_SESSION['user_name'] = $username;
            $_SESSION['user_email'] = $email;
            
            // Responder con éxito
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente']);
        } else {
            // Revertir transacción en caso de error
            $conn->rollBack();
            error_log("Error en la ejecución de la consulta, transacción revertida");
            
            // Responder con error
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el perfil']);
        }
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Registrar el error
        error_log('Error de PDO al actualizar perfil: ' . $e->getMessage());
        
        // Responder con error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    } catch (Exception $e) {
        // Capturar cualquier otro error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        error_log('Error general al actualizar perfil: ' . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
    }
    exit;
}

// Si el método no es GET ni POST, responder con error
error_log("Método no permitido: " . $_SERVER['REQUEST_METHOD']);
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Método no permitido']);