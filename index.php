<?php
// Iniciar sesión
session_start();
//session_destroy();

// Incluir archivo de conexión
require_once 'config/conexion.php';

// Verificar si ya hay una sesión activa
if (isset($_SESSION['user_id'])) {
    // Redirigir al dashboard o página principal
    header('Location: dashboard.php');
    exit;
}

// Procesar el formulario de login
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que se enviaron los campos requeridos
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // Inicializar la conexión a la base de datos
        $database = new Conexion();
        
        // Buscar usuario por email
        $sql = "SELECT * FROM agusers WHERE emailUser = ?";
        $user = $database->getRow($sql, [$email]);
        
        // Verificar si se encontró el usuario y registrar información para depuración
        if ($user) {
            // Depuración - Mostrar información del usuario encontrado (comentar en producción)
            // echo "<pre>Usuario encontrado: " . print_r($user, true) . "</pre>";
            
            // Verificar contraseña
            if (password_verify($password, $user['passwordUser'])) {
                // Verificar si el usuario ha iniciado sesión con una contraseña temporal
                $recoverySql = "SELECT ar.is_temporary 
                               FROM agrecuperacion ar
                               WHERE ar.emailrecuperacion = ? 
                               ORDER BY ar.daterecuperacion DESC 
                               LIMIT 1";

                error_log("SQL consulta: " . $recoverySql);
                error_log("Verificando email: " . $email);               

                $recoveryData = $database->getRow($recoverySql, [$email]);
                
                error_log("Resultado recuperación: " . print_r($recoveryData, true));

                
                if ($recoveryData && isset($recoveryData['is_temporary'])) {
                    error_log("PasswordRecover: " . $recoveryData['passwordrecover']);
                    error_log("PasswordUser: " . $user['passwordUser']);
                    // Si is_temporary es 1 (true), el usuario ha iniciado sesión con una contraseña temporal
                    if ($recoveryData['is_temporary'] == 1) {
                        $_SESSION['require_password_change'] = true;
                    }
                }
                
                // Iniciar sesión - Asegurarse de usar el nombre de campo correcto
                $_SESSION['user_id'] = $user['idUser']; // Cambiado de 'idusers' a 'idUser'
                $_SESSION['user_name'] = $user['nomUsuario'];
                $_SESSION['user_email'] = $user['emailUser'];
                
                // Redirigir al dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error_message = 'Contraseña incorrecta';
                // Depuración - Verificar hash (comentar en producción)
                // echo "<p>Hash almacenado: " . $user['passwordUser'] . "</p>";
                error_log("Las contraseñas no coinciden, no es temporal");
            }
        } else {
            $error_message = 'Usuario no encontrado';
            error_log("No se encontraron datos de recuperación para este usuario");
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
    <title>Login - Finguer360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/main.css">
</head>

<body>
    <div class="container">
        <div class="login-container">
            <div class="login-logo">
                <h2>Finger360</h2>
            </div>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" class="form-control" id="email" name="email" required autocomplete="username">
                    <div id="email-feedback" class="invalid-feedback">
                        Por favor, introduce un correo electrónico válido.
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required
                            autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login">Iniciar Sesión</button>
                <div class="text-center mt-3">
                    <a href="#" id="forgot-password-link" data-bs-toggle="modal"
                        data-bs-target="#forgotPasswordModal">¿Olvidaste tu contraseña?</a>
                </div>
            </form>

            <div class="register-link">
                <p>¿No tienes una cuenta? <a href="api/register_form.php">Regístrate aquí</a></p>
            </div>

            <!-- Modal para recuperar contraseña -->
            <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="forgotPasswordModalLabel">Recuperar Contraseña</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="forgot-password-form">
                                <div class="mb-3">
                                    <label for="recovery-email" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="recovery-email" required>
                                    <div class="invalid-feedback">
                                        Por favor, introduce un correo electrónico válido.
                                    </div>
                                </div>
                                <div id="recovery-message" class="alert d-none"></div>
                            </form>
                            <!-- Mensaje de depuración -->
                            <div class="mt-3">
                                <small class="text-muted">Si tienes problemas, asegúrate de que tu correo esté
                                    registrado en el sistema.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="send-recovery-btn">Enviar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para cambio de contraseña obligatorio -->
            <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog"
                aria-labelledby="changePasswordModalLabel" aria-hidden="true" data-backdrop="static"
                data-keyboard="false">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="changePasswordModalLabel">Cambio de contraseña requerido</h5>
                        </div>
                        <div class="modal-body">
                            <p>Has iniciado sesión con una contraseña temporal. Por razones de seguridad, debes
                                establecer una nueva contraseña.</p>
                            <form id="changePasswordForm">
                                <div class="form-group">
                                    <label for="newPassword">Nueva contraseña</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="newPassword" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button"
                                            data-target="newPassword">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">La contraseña debe tener al menos 8 caracteres y
                                        contener letras y números.</small>
                                </div>
                                <div class="form-group">
                                    <label for="confirmPassword">Confirmar contraseña</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirmPassword" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button"
                                            data-target="confirmPassword">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="alert alert-danger" id="passwordError" style="display: none;"></div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" id="saveNewPassword">Guardar nueva
                                contraseña</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
</body>

</html>