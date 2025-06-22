<?php
/**
 * Script de prueba para envío de correo con cURL
 * Este script permite probar el envío de correo sin afectar la base de datos
 */

// Habilitar registro de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Crear directorio de logs si no existe
$log_dir = './logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Función para registrar mensajes de depuración
function debug_log($message) {
    $log_file = './logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

debug_log("=== INICIO DE PRUEBA DE ENVÍO DE CORREO ===");

// Función para enviar correo con SendGrid usando cURL
function testSendEmail($email, $api_key) {
    debug_log("Preparando envío de correo a: $email");
    
    $url = 'https://api.sendgrid.com/v3/mail/send';
    
    // Mensaje de prueba
    $password = "TestPassword123!";
    $contenido_texto = "Esta es una prueba de envío de correo.\n\n";
    $contenido_texto .= "Si has recibido este correo, la configuración de SendGrid está funcionando correctamente.";
    
    $contenido_html = "<h2>Prueba de Envío de Correo</h2>";
    $contenido_html .= "<p>Esta es una prueba de envío de correo.</p>";
    $contenido_html .= "<p>Si has recibido este correo, la configuración de SendGrid está funcionando correctamente.</p>";
    
    // Crear el cuerpo de la solicitud
    $data = [
        'personalizations' => [
            [
                'to' => [
                    ['email' => $email, 'name' => 'Usuario de Prueba']
                ],
                'subject' => "Prueba de Envío - " . date("Y-m-d H:i:s")
            ]
        ],
        'from' => [
            'email' => 'tu_correo@tudominio.com', // Reemplazar con tu correo verificado en SendGrid
            'name' => 'Sistema de Pruebas'
        ],
        'content' => [
            [
                'type' => 'text/plain',
                'value' => $contenido_texto
            ],
            [
                'type' => 'text/html',
                'value' => $contenido_html
            ]
        ]
    ];
    
    // Convertir a JSON
    $json_data = json_encode($data);
    debug_log("Datos JSON para SendGrid: " . $json_data);
    
    // Configurar cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    
    // Configuración del tiempo de espera
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Configuración SSL - Primero con verificación habilitada
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    // Ejecutar cURL
    debug_log("Enviando solicitud a SendGrid con SSL...");
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($error && (strpos($error, 'SSL') !== false || strpos($error, 'certificate') !== false)) {
        debug_log("Error SSL detectado, intentando sin verificación SSL: " . $error);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
    }
    
    debug_log("Respuesta de SendGrid: Código $status_code");
    debug_log("Error: " . ($error ?: 'Ninguno'));
    debug_log("Respuesta: " . ($response ?: 'Ninguna'));
    
    curl_close($ch);
    
    return [
        'success' => $status_code >= 200 && $status_code < 300,
        'status_code' => $status_code,
        'message' => $response ?: $error
    ];
}

// Formulario de prueba
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!empty($_POST['email']) && !empty($_POST['api_key'])) {
        $email = $_POST['email'];
        $api_key = $_POST['api_key'];
        
        debug_log("Iniciando prueba con email: $email");
        
        $result = testSendEmail($email, $api_key);
        
        echo json_encode([
            'status' => $result['success'] ? 'success' : 'error',
            'code' => $result['status_code'],
            'message' => $result['success'] ? 'Correo enviado correctamente' : 'Error al enviar el correo',
            'details' => $result['message']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Faltan campos requeridos (email y api_key)'
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Envío de Correo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Prueba de Envío de Correo con SendGrid</h3>
                    </div>
                    <div class="card-body">
                        <form id="testForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo de destino:</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="form-text">El correo donde quieres recibir el mensaje de prueba.</div>
                            </div>
                            <div class="mb-3">
                                <label for="api_key" class="form-label">API Key de SendGrid:</label>
                                <input type="text" class="form-control" id="api_key" name="api_key" required>
                                <div class="form-text">Tu API Key de SendGrid (comienza con SG.)</div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="submitBtn">Enviar Correo de
                                    Prueba</button>
                            </div>
                        </form>

                        <div class="mt-4 d-none" id="result">
                            <div class="card">
                                <div class="card-header" id="resultHeader">Resultado</div>
                                <div class="card-body">
                                    <div id="resultMessage"></div>
                                    <div class="mt-3">
                                        <strong>Detalles:</strong>
                                        <pre id="resultDetails" class="mt-2 p-3 bg-light"></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h4 class="card-title mb-0">Guía de Solución de Problemas</h4>
                    </div>
                    <div class="card-body">
                        <h5>Si el correo no llega:</h5>
                        <ol>
                            <li>Verifica que la API Key de SendGrid sea correcta</li>
                            <li>Asegúrate de que el dominio del remitente esté verificado en SendGrid</li>
                            <li>Revisa la carpeta de spam</li>
                            <li>Verifica los logs en la carpeta /logs/debug.log</li>
                        </ol>

                        <h5>Errores comunes:</h5>
                        <ul>
                            <li><strong>401</strong>: API Key inválida</li>
                            <li><strong>403</strong>: No tienes permiso para enviar desde ese remitente</li>
                            <li><strong>400</strong>: Error en el formato de la solicitud</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('testForm');
        const submitBtn = document.getElementById('submitBtn');
        const result = document.getElementById('result');
        const resultHeader = document.getElementById('resultHeader');
        const resultMessage = document.getElementById('resultMessage');
        const resultDetails = document.getElementById('resultDetails');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Mostrar estado de carga
            submitBtn.disabled = true;
            submitBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';

            // Obtener datos del formulario
            const formData = new FormData(form);

            // Enviar solicitud
            fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Mostrar resultado
                    result.classList.remove('d-none');

                    if (data.status === 'success') {
                        resultHeader.className = 'card-header bg-success text-white';
                        resultHeader.textContent = 'Éxito';
                        resultMessage.innerHTML = '<div class="alert alert-success">' + data
                            .message + '</div>';
                    } else {
                        resultHeader.className = 'card-header bg-danger text-white';
                        resultHeader.textContent = 'Error';
                        resultMessage.innerHTML = '<div class="alert alert-danger">' + data
                            .message + ' (Código: ' + (data.code || 'N/A') + ')</div>';
                    }

                    resultDetails.textContent = data.details || 'No hay detalles disponibles';
                })
                .catch(error => {
                    result.classList.remove('d-none');
                    resultHeader.className = 'card-header bg-danger text-white';
                    resultHeader.textContent = 'Error';
                    resultMessage.innerHTML =
                        '<div class="alert alert-danger">Error al procesar la solicitud</div>';
                    resultDetails.textContent = error.toString();
                })
                .finally(() => {
                    // Restaurar botón
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Enviar Correo de Prueba';
                });
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>