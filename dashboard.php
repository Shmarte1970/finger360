<?php
// Iniciar sesión
session_start();

// Incluir verificación de sesión (sin modificar estructura existente)
if (file_exists('session_check.php')) {
    include 'session_check.php';
}

// Logs para depuración
error_log("Dashboard cargado. Sesión: " . print_r($_SESSION, true));

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    // Redirigir al login
    header('Location: index.php');
    exit;
}

// Obtener datos del usuario
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Verificar si se requiere cambio de contraseña
$require_password_change = isset($_SESSION['require_password_change']) && $_SESSION['require_password_change'] === true;

// Log para depuración
error_log("Valor de require_password_change: " . ($require_password_change ? 'true' : 'false'));

// Procesar cierre de sesión
if (isset($_GET['logout'])) {
    // Destruir la sesión
    session_destroy();
    
    // Redirigir al login
    header('Location: index.php');
    exit;
}

// Determinar la página activa
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0d6efd">
    <title>Dashboard - Sistema de Agentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/dashboard.css">
    <link rel="manifest" href="/manifest.json">
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/service-worker.js');
        });
    }
    </script>
    <script src="./js/window-resize.js"></script>
</head>

<body data-require-password-change="<?php echo $require_password_change ? 'true' : 'false'; ?>">
    <!-- Mostrar un mensaje de depuración temporal -->
    <?php if ($require_password_change): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Sistema:</strong> Se detectó que estás usando una contraseña temporal. Por favor, cámbiala.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="app-container">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2 d-md-block sidebar collapse" style="height: auto;">
                    <div class="sidebar-header">
                        <h4>Finger360</h4>
                    </div>
                    <ul class="sidebar-menu">
                        <li class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                            <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                        </li>
                        <li>
                            <a href="#" id="menuCreateContact"><i class="fas fa-plus-circle me-2"></i> Create</a>
                        </li>
                        <li class="<?php echo $page === 'settings' ? 'active' : ''; ?>">
                            <a href="dashboard.php?page=settings"><i class="fas fa-cog me-2"></i> Settings</a>
                        </li>
                        <li>
                            <a href="dashboard.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                        </li>
                    </ul>
                    <!-- Espacio adicional al final del sidebar -->
                    <div class="sidebar-footer"></div>
                </div>

                <!-- Main Content -->
                <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <nav class="navbar navbar-expand-lg navbar-light py-2">
                        <!-- Reducido el padding vertical -->
                        <div class="container-fluid">
                            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                                data-bs-target=".sidebar">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                            <div class="d-flex">
                                <div class="dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <?php echo htmlspecialchars($user_name); ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="dashboard.php?page=profile">Perfil</a></li>
                                        <li><a class="dropdown-item" href="dashboard.php?page=settings">Configuración</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="dashboard.php?logout=1">Cerrar Sesión</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </nav>

                    <!-- Contenido principal -->
                    <div class="content-container mt-4">
                        <?php if ($page === 'dashboard'): ?>
                        <!-- Contenido del Dashboard -->
                        <div class="row">
                            <div class="col-12">
                                <h2 style="text-align: left; width: 400px; margin: 0 0 15px 0;">
                                    Panel de Control</h2>

                                <!-- Barra de búsqueda para la tabla agcontactos -->
                                <div class="search-container mb-3" style="width: 400px; margin: 0;">
                                    <div class="input-group input-group-sm">
                                        <!-- Tamaño pequeño -->
                                        <input type="text" class="form-control" id="searchAgcontactos"
                                            placeholder="Buscar por nombre o teléfono">
                                        <button class="btn btn-outline-secondary d-none" type="button"
                                            id="btnClearSearch">
                                            <i class="fas fa-eraser"></i>
                                        </button>
                                        <button class="btn btn-primary" type="button" id="btnSearchAgcontactos">
                                            <i class="fas fa-search"></i> <span class="search-text">Buscar</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Botón para añadir contacto -->
                                <div class="button-container mb-3" style="width: 400px; margin: 0; text-align: left;">
                                    <button type="button" class="btn btn-success d-none" id="addNewContact" style="height: 45px; padding: 10px 20px; font-size: 1.1em;">
                                        <i class="fas fa-plus"></i> Añadir Nuevo
                                    </button>
                                </div>

                                <!-- Contenedor para las cards de contactos con scrollbar -->
                                <div class="contact-scroll-container"
                                    style="width: 400px; margin: 0; box-sizing: border-box;">
                                    <div class="d-flex flex-column align-items-start" id="contactsContainer"
                                        style="width: 100%; box-sizing: border-box;">
                                        <!-- Las cards de contactos se cargarán aquí dinámicamente -->
                                        <div class="py-5" id="loadingContacts" style="width: 100%;">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Cargando...</span>
                                            </div>
                                            <p class="mt-2">Cargando contactos...</p>
                                        </div>
                                        <div class="py-5 d-none" id="noContactsMessage" style="width: 100%;">
                                            <p class="text-muted">No se encontraron contactos. ¡Añade uno nuevo!</p>
                                            <button class="btn btn-sm btn-success mt-2" id="btnNoContactsAddEmpty" style="height: 45px; padding: 10px 20px; font-size: 1.1em;">
                                                <i class="fas fa-plus"></i> Añadir Contacto
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <?php elseif ($page === 'settings'): ?>
                        <!-- Contenido de Settings -->
                        <div class="row">
                            <div class="col-12">
                                <h2>Configuración</h2>
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Configuración de cuenta</h5>
                                        <button class="btn btn-primary mb-3" id="btnChangePassword"
                                            data-bs-toggle="modal" data-bs-target="#settingsPasswordModal">
                                            Cambiar contraseña
                                        </button>

                                        <h5 class="card-title mt-4">Otras configuraciones</h5>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="notificationsSwitch">
                                            <label class="form-check-label" for="notificationsSwitch">Activar
                                                notificaciones</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php else: ?>
                        <!-- Otras páginas pueden implementarse según sea necesario -->
                        <div class="row">
                            <div class="col-12">
                                <h2><?php echo ucfirst($page); ?></h2>
                                <p>Esta funcionalidad está en desarrollo.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para cambio de contraseña (contraseña temporal) -->
        <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog"
            aria-labelledby="changePasswordModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changePasswordModalLabel">Cambio de contraseña requerido</h5>
                    </div>
                    <div class="modal-body">
                        <p>Has iniciado sesión con una contraseña temporal. Por razones de seguridad, debes establecer
                            una nueva contraseña.</p>
                        <form id="changePasswordForm">
                            <div class="form-group mb-3">
                                <label for="newPassword">Nueva contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="newPassword" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button"
                                        data-target="newPassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">La contraseña debe tener al menos 12 caracteres y
                                    contener letras y números.</small>
                            </div>
                            <div class="form-group mb-3">
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

        <!-- Nuevo Modal para cambio de contraseña desde Settings -->
        <div class="modal fade" id="settingsPasswordModal" tabindex="-1" aria-labelledby="settingsPasswordModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="settingsPasswordModalLabel">Cambiar contraseña</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="settingsPasswordForm">
                            <div class="form-group mb-3">
                                <label for="currentPassword">Contraseña actual</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="currentPassword" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button"
                                        data-target="currentPassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="settingsNewPassword">Nueva contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="settingsNewPassword" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button"
                                        data-target="settingsNewPassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">La contraseña debe tener al menos 12 caracteres y
                                    contener letras y números.</small>
                            </div>
                            <div class="form-group mb-3">
                                <label for="settingsConfirmPassword">Confirmar nueva contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="settingsConfirmPassword" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button"
                                        data-target="settingsConfirmPassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="alert alert-danger" id="settingsPasswordError" style="display: none;"></div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="saveSettingsPassword">Guardar cambios</button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal para edición de perfil de usuario -->
        <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProfileModalLabel">Editar perfil de usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editProfileForm">
                            <div class="form-group mb-3">
                                <label for="editUsername">Nombre de usuario</label>
                                <input type="text" class="form-control" id="editUsername" name="nomUsuario" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="editEmail">Correo electrónico</label>
                                <input type="email" class="form-control" id="editEmail" name="emailUser" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="editPassword">Contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="editPassword" name="passwordUser"
                                        placeholder="Dejar en blanco para mantener la actual">
                                    <button class="btn btn-outline-secondary toggle-password" type="button"
                                        data-target="editPassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">Si desea cambiar la contraseña, la nueva debe tener
                                    al menos 12 caracteres y contener letras y números.</small>
                            </div>
                            <div class="form-group mb-3">
                                <label for="confirmEditPassword">Confirmar contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirmEditPassword"
                                        placeholder="Dejar en blanco si no cambia la contraseña">
                                    <button class="btn btn-outline-secondary toggle-password" type="button"
                                        data-target="confirmEditPassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="alert alert-danger" id="editProfileError" style="display: none;"></div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="saveProfileChanges">Guardar cambios</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal para crear/editar contacto -->
        <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="contactModalLabel">Nuevo Contacto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="contactForm">
                            <input type="hidden" id="contactId" value="">
                            <div class="form-group mb-3">
                                <label for="nomContacto">Nombre del Contacto</label>
                                <input type="text" class="form-control" id="nomContacto" name="nomContacto" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="telefonoContacto">Teléfono</label>
                                <input type="tel" class="form-control" id="telefonoContacto" name="telefonoContacto"
                                    placeholder="Ingrese el teléfono" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="adress">Dirección</label>
                                <input type="text" class="form-control" id="adress" name="adress">
                            </div>
                            <div class="form-group mb-3">
                                <label for="sexo">Sexo</label>
                                <select class="form-control" id="sexo" name="sexo">
                                    <option value="">Seleccionar...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                    <option value="O">Trans</option>
                                </select>
                            </div>

                            <!-- Campos para cargar imágenes -->
                            <div class="form-group mb-3">
                                <label>Imágenes del Contacto</label>
                                <div class="row">
                                    <!-- Foto 1 -->
                                    <div class="col-md-4 mb-2">
                                        <label for="foto1" class="form-label">Foto 1</label>
                                        <input class="form-control form-control-sm" type="file" id="foto1" name="foto1"
                                            accept="image/*">
                                    </div>

                                    <!-- Foto 2 -->
                                    <div class="col-md-4 mb-2">
                                        <label for="foto2" class="form-label">Foto 2</label>
                                        <input class="form-control form-control-sm" type="file" id="foto2" name="foto2"
                                            accept="image/*">
                                    </div>

                                    <!-- Foto 3 -->
                                    <div class="col-md-4 mb-2">
                                        <label for="foto3" class="form-label">Foto 3</label>
                                        <input class="form-control form-control-sm" type="file" id="foto3" name="foto3"
                                            accept="image/*">
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-danger" id="contactFormError" style="display: none;"></div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="saveContact">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación para eliminar contacto -->
        <div class="modal fade" id="deleteContactModal" tabindex="-1" aria-labelledby="deleteContactModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteContactModalLabel">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar este contacto? Esta acción no se puede deshacer.</p>
                        <input type="hidden" id="deleteContactId" value="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteContact">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- IMPORTANTE: Cargar bootstrap Y main.js, no dashboard.js -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>


        <!-- Script para verificar si se requiere cambio de contraseña -->
        <script src="js/main.js?v=<?php echo time(); ?>"></script>
        <script src="js/image-handler.js?v=<?php echo time(); ?>"></script>
        <script src="js/dashboard.js?v=<?php echo time(); ?>"></script>
        <script src="js/hover-effect.js?v=<?php echo time(); ?>"></script>

        <!-- Script para asegurar la carga de contactos -->
        <script>
        // Asegurar que los contactos se carguen después de que el script dashboard.js esté completamente cargado
        window.addEventListener('load', function() {
            setTimeout(function() {
                if (typeof window.loadContacts === 'function') {
                    console.log("Cargando contactos desde el evento load...");
                    window.loadContacts();
                }
            }, 800);
        });
        </script>

        <!-- Script para manejar el enlace Create del menú -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener el enlace Create del menú
            const menuCreateContact = document.getElementById('menuCreateContact');

            if (menuCreateContact) {
                // Añadir evento de clic para abrir el modal de contacto
                menuCreateContact.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevenir la navegación

                    // Usar la misma función que se usa en el dashboard para abrir el modal
                    if (typeof window.openContactModal === 'function') {
                        window.openContactModal();
                    }
                });
            }

            // Inicializar componentes responsivos inmediatamente
            initResponsiveComponents();

            // Cargar contactos iniciales después de un breve retraso para asegurar que todas las funciones estén disponibles
            setTimeout(function() {
                console.log("Intentando cargar contactos iniciales...");
                if (typeof window.loadContacts === 'function') {
                    console.log("Función loadContacts encontrada, cargando contactos...");
                    window.loadContacts();
                } else {
                    console.error("La función loadContacts no está disponible");
                }
            }, 500);
        });

        // Función para inicializar componentes responsivos
        function initResponsiveComponents() {
            // Detectar tipo de dispositivo
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            const isTablet = /iPad|Android(?!.*Mobile)/i.test(navigator.userAgent);

            // Aplicar clases según el dispositivo
            if (isMobile) {
                document.body.classList.add('mobile-device');

                // Detectar modelos específicos
                if (/SM-G99[8-9]|SM-S9[0-9]{2}/i.test(navigator.userAgent)) {
                    document.body.classList.add('samsung-galaxy-ultra');
                }

                if (/iPhone1[1-3]/i.test(navigator.userAgent)) {
                    document.body.classList.add('iphone-11-pro');
                }
            }

            if (isTablet) {
                document.body.classList.add('tablet-device');
            }

            // Asegurar que el contenedor de contactos tenga el ancho correcto
            const contactContainer = document.querySelector('.contact-scroll-container');
            if (contactContainer) {
                contactContainer.style.width = '400px';
                contactContainer.style.maxWidth = '100%';
                contactContainer.style.boxSizing = 'border-box';
            }

            // Inicializar búsqueda
            const searchInput = document.getElementById('searchAgcontactos');
            if (searchInput) {
                // Limpiar búsqueda al cargar
                searchInput.value = '';

                // Asegurarse de que lastSearchTerm esté inicializado correctamente
                if (typeof window.lastSearchTerm !== 'undefined') {
                    window.lastSearchTerm = '';
                }

                // Enfocar el campo de búsqueda después de un breve retraso
                setTimeout(() => {
                    searchInput.focus();
                }, 500);
            }

            // La carga de contactos se maneja en el evento DOMContentLoaded
        }
        </script>

        <!-- Script para abrir el modal y vincular el evento al botón "Guardar" -->
        <script>
        window.openContactModal = function(contact = null) {
            // ...código existente para rellenar el formulario...

            // Mostrar el modal
            const contactModal = new bootstrap.Modal(modal);
            contactModal.show();

            // Vincular el evento al botón Guardar cada vez que se abre el modal
            const saveContactBtn = document.getElementById("saveContact");
            if (saveContactBtn) {
                saveContactBtn.onclick = null; // Limpia cualquier handler anterior
                saveContactBtn.addEventListener("click", function() {
                    console.log("Botón guardar clickeado (desde openContactModal)");
                    window.saveContact();
                });
            }
        };
        </script>

        <!-- Script para manejar la eliminación de contacto -->
        <script>
        window.deleteContact = function() {
            console.log("Función deleteContact llamada");
            // ...resto del código...
        };
        </script>

        <!-- Script para abrir el modal y vincular el evento al botón "Eliminar" -->
        <script>
        window.openDeleteModal = function(contactId) {
            console.log("openDeleteModal recibió ID:", contactId, "Tipo:", typeof contactId);
            document.getElementById("deleteContactId").value = contactId;
            console.log("Valor asignado:", document.getElementById("deleteContactId").value);
            const deleteModal = new bootstrap.Modal(document.getElementById("deleteContactModal"));
            deleteModal.show();

            // Vincular el evento al botón Eliminar cada vez que se abre el modal
            const confirmDeleteBtn = document.getElementById("confirmDeleteContact");
            if (confirmDeleteBtn) {
                confirmDeleteBtn.onclick = null; // Limpia cualquier handler anterior
                confirmDeleteBtn.addEventListener("click", function() {
                    console.log("Botón eliminar clickeado (desde openDeleteModal)");
                    window.deleteContact();
                });
            }
        };
        </script>

        <!-- Sistema de auto-logout para detectar cierre del navegador -->
        <script src="./js/auto_logout.js"></script>

        <!-- Script para inicializar intl-tel-input -->
        <script>
        // Función para obtener el país del usuario
        async function getUserCountry() {
            try {
                const response = await fetch('https://ipapi.co/json/');
                const data = await response.json();
                return data.country_code.toLowerCase();
            } catch (error) {
                console.error('Error al detectar el país:', error);
                return 'es'; // País por defecto si hay error
            }
        }

        // Inicializar intl-tel-input con el país del usuario
        async function initializePhoneInput() {
            const input = document.querySelector("#telefonoContacto");
            const userCountry = await getUserCountry();
            
            const iti = window.intlTelInput(input, {
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js",
                preferredCountries: [userCountry, "ar", "uy", "cl", "py", "bo", "pe", "co", "ve", "ec"],
                separateDialCode: true,
                formatOnDisplay: true,
                autoPlaceholder: "aggressive",
                initialCountry: userCountry
            });

            // Validar el teléfono antes de enviar el formulario
            document.getElementById('contactForm').addEventListener('submit', function(e) {
                e.preventDefault();
                if (iti.isValidNumber()) {
                    const fullNumber = iti.getNumber();
                    document.getElementById('telefonoContacto').value = fullNumber;
                    this.submit();
                } else {
                    alert('Por favor, ingrese un número de teléfono válido');
                }
            });

            // Actualizar el placeholder cuando cambie el país
            input.addEventListener("countrychange", function() {
                input.placeholder = iti.getNumber();
            });
        }

        // Inicializar cuando el documento esté listo
        document.addEventListener('DOMContentLoaded', initializePhoneInput);
        </script>
</body>

</html>