<?php
/**
 * API Endpoint para registro de usuarios
 * 
 * Permite crear nuevos usuarios en el sistema
 */

// Incluir archivos necesarios
require_once '../config/conexion.php';

// Configuración de cabeceras CORS
setCorsHeaders();

// Inicializar la conexión a la base de datos
$database = new Conexion();
$db = $database->getConnection();

// Si es una solicitud GET, mostrar el formulario HTML
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Aquí va el código HTML del formulario
    ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Finger360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/main.css">
</head>

<body>
    <div class="container">
        <div class="register-container">
            <div class="register-logo">
                <h2>Registro de Usuario</h2>
            </div>

            <div id="error-message" class="alert alert-danger d-none"></div>
            <div id="success-message" class="alert alert-success d-none"></div>

            <form id="register-form">
                <div class="form-group">
                    <label for="nombre">Nombre Completo</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div id="email-feedback" class="invalid-feedback">
                        Por favor, introduce un correo electrónico válido.
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="form-text text-muted">
                        La contraseña debe tener al menos 12 caracteres, incluir un símbolo (@, #), una letra mayúscula
                        y un número.
                    </small>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                            required>
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-success btn-register">Registrarse</button>
            </form>

            <div class="login-link">
                <p>¿Ya tienes una cuenta? <a href="../../index.php">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/main.js"></script>
    <script>
    // Código JavaScript para el formulario
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('register-form');
        const errorMessage = document.getElementById('error-message');
        const successMessage = document.getElementById('success-message');

        // Inicializar botones para mostrar/ocultar contraseña
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Cambiar icono
                const icon = togglePassword.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        }

        if (toggleConfirmPassword) {
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' :
                    'password';
                confirmPasswordInput.setAttribute('type', type);

                // Cambiar icono
                const icon = toggleConfirmPassword.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Limpiar mensajes previos
            errorMessage.classList.add('d-none');
            successMessage.classList.add('d-none');

            // Validaciones básicas
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (password !== confirmPassword) {
                errorMessage.textContent = 'Las contraseñas no coinciden';
                errorMessage.classList.remove('d-none');
                return;
            }

            // Validar requisitos de contraseña
            if (password.length < 12) {
                errorMessage.textContent = 'La contraseña debe tener al menos 12 caracteres';
                errorMessage.classList.remove('d-none');
                return;
            }

            if (!(/[@#]/.test(password))) {
                errorMessage.textContent =
                    'La contraseña debe contener al menos uno de estos símbolos: @ o #';
                errorMessage.classList.remove('d-none');
                return;
            }

            if (!(/[A-Z]/.test(password))) {
                errorMessage.textContent = 'La contraseña debe contener al menos una letra mayúscula';
                errorMessage.classList.remove('d-none');
                return;
            }

            if (!(/[0-9]/.test(password))) {
                errorMessage.textContent = 'La contraseña debe contener al menos un número';
                errorMessage.classList.remove('d-none');
                return;
            }

            // Mostrar mensaje de carga
            successMessage.textContent = 'Procesando solicitud...';
            successMessage.classList.remove('d-none');

            // Preparar datos para envío
            const formData = {
                nomUsuario: document.getElementById('nombre').value,
                emailUser: document.getElementById('email').value,
                passwordUser: password
            };

            // Enviar al API
            fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Registro exitoso
                        successMessage.textContent = data.message ||
                            'Usuario registrado correctamente';
                        successMessage.classList.remove('d-none');
                        errorMessage.classList.add('d-none');
                        form.reset();

                        // Redirigir al login después de 3 segundos
                        setTimeout(() => {
                            window.location.href = '../../index.php';
                        }, 3000);
                    } else {
                        // Error en el registro
                        errorMessage.textContent = data.message || 'Error al registrar usuario';
                        errorMessage.classList.remove('d-none');
                        successMessage.classList.add('d-none');
                    }
                })
                .catch(error => {
                    errorMessage.textContent = 'Error de conexión. Por favor, inténtalo de nuevo.';
                    errorMessage.classList.remove('d-none');
                    successMessage.classList.add('d-none');
                    console.error('Error:', error);
                });
        });
    });
    </script>
</body>

</html>
<?php
    exit;
}

// Si es una solicitud POST, procesar el registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"));
    
    // Verificar que todos los campos requeridos estén presentes
    if (!empty($data->nomUsuario) && !empty($data->emailUser) && !empty($data->passwordUser)) {
        
        // Validar formato de email
        if (!filter_var($data->emailUser, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'El formato del correo electrónico no es válido'
            ]);
            exit;
        }
        
        // Validar longitud mínima de contraseña
        if (strlen($data->passwordUser) < 12) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'La contraseña debe tener al menos 12 caracteres'
            ]);
            exit;
        }
        
        // Validar que la contraseña contenga al menos un símbolo requerido (@ o #)
        if (!preg_match('/[@#]/', $data->passwordUser)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'La contraseña debe contener al menos uno de estos símbolos: @ o #'
            ]);
            exit;
        }
        
        // Validar que la contraseña contenga al menos una letra mayúscula
        if (!preg_match('/[A-Z]/', $data->passwordUser)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'La contraseña debe contener al menos una letra mayúscula'
            ]);
            exit;
        }
        
        // Validar que la contraseña contenga al menos un número
        if (!preg_match('/[0-9]/', $data->passwordUser)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'La contraseña debe contener al menos un número'
            ]);
            exit;
        }
        
        // Verificar si el email ya existe
        $checkEmailSql = "SELECT COUNT(*) as count FROM agusers WHERE emailUser = ?";
        $result = $database->getRow($checkEmailSql, [$data->emailUser]);
        
        if ($result['count'] > 0) {
            // El email ya está registrado
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'El correo electrónico ya está registrado'
            ]);
            exit;
        }
        
        // Encriptar la contraseña
        $hashedPassword = password_hash($data->passwordUser, PASSWORD_DEFAULT);
        
        // Preparar datos para inserción
        $userData = [
            'nomUsuario' => $data->nomUsuario,
            'emailUser' => $data->emailUser,
            'passwordUser' => $hashedPassword,
            'creado' => date('Y-m-d H:i:s')
        ];
        
        // Insertar nuevo usuario
        $userId = $database->insert('agusers', $userData);
        
        if ($userId) {
            // Registro exitoso
            http_response_code(201);
            echo json_encode([
                'status' => 'success',
                'message' => 'Usuario registrado correctamente',
                'user_id' => $userId
            ]);
        } else {
            // Error al registrar
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se pudo registrar el usuario'
            ]);
        }
    } else {
        // Datos incompletos
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Datos incompletos. Se requieren nombre, email y contraseña'
        ]);
    }
} else {
    // Método no permitido
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido'
    ]);
}
?>