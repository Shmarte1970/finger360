<?php
// Incluir archivo de conexión
require_once '../config/conexion.php';

// Inicializar variables
$error_message = '';
$success_message = '';

// Procesar el formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que se enviaron los campos requeridos
    if (isset($_POST['nombre']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validar que todos los campos estén completos
        if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
            $error_message = 'Todos los campos son obligatorios';
        }
        // Validar formato de email
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'El formato del correo electrónico no es válido';
        }
        // Validar que las contraseñas coincidan
        elseif ($password !== $confirm_password) {
            $error_message = 'Las contraseñas no coinciden';
        }
        // Validar longitud mínima de contraseña
        elseif (strlen($password) < 12) {
            $error_message = 'La contraseña debe tener al menos 12 caracteres';
        }
        // Validar que la contraseña contenga al menos un símbolo requerido (@ o #)
        elseif (!preg_match('/[@#]/', $password)) {
            $error_message = 'La contraseña debe contener al menos uno de estos símbolos: @ o #';
        }
        // Validar que la contraseña contenga al menos una letra mayúscula
        elseif (!preg_match('/[A-Z]/', $password)) {
            $error_message = 'La contraseña debe contener al menos una letra mayúscula';
        }
        // Validar que la contraseña contenga al menos un número
        elseif (!preg_match('/[0-9]/', $password)) {
            $error_message = 'La contraseña debe contener al menos un número';
        }
        else {
            // Inicializar la conexión a la base de datos
            $database = new Conexion();
            
            // Verificar si el email ya existe
            $sql = "SELECT COUNT(*) as count FROM agusers WHERE emailUser = ?";
            $result = $database->getRow($sql, [$email]);
            
            if ($result['count'] > 0) {
                $error_message = 'El correo electrónico ya está registrado';
            } else {
                // Encriptar la contraseña
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Preparar datos para inserción
                $userData = [
                    'nomUsuario' => $nombre,
                    'emailUser' => $email,
                    'passwordUser' => $hashedPassword,
                    'creado' => date('Y-m-d H:i:s')
                ];
                
                // Insertar nuevo usuario
                $userId = $database->insert('agusers', $userData);
                
                if ($userId) {
                    $success_message = 'Usuario registrado correctamente. Ahora puedes iniciar sesión.';
                } else {
                    $error_message = 'Error al registrar el usuario. Por favor, inténtalo de nuevo.';
                }
            }
        }
    } else {
        $error_message = 'Por favor, complete todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Agentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
</head>

<body>
    <div class="container">
        <div class="register-container">
            <div class="register-logo">
                <h2>Registro de Usuario</h2>
            </div>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
                <p><a href="../index.php">Ir al inicio de sesión</a></p>
            </div>
            <?php else: ?>
            <form method="POST" action="">
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
                        La contraseña debe cumplir con los siguientes requisitos:
                        <ul>
                            <li>Mínimo 12 caracteres</li>
                            <li>Al menos un símbolo (@ o #)</li>
                            <li>Al menos una letra mayúscula</li>
                            <li>Al menos un número</li>
                        </ul>
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
            <?php endif; ?>

            <div class="login-link">
                <p>¿Ya tienes una cuenta? <a href="../index.php">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
</body>

</html>