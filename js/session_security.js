/**
 * session_security.js
 * Sistema de seguridad para gestión de sesiones y cierre seguro del navegador
 */

// Sistema de seguridad para cierre de sesión automático
// Implementación que no depende solo de beforeunload

// Variables de control
let sessionActive = true;
let lastActivity = Date.now();
let inactivityTimeout = 30 * 60 * 1000; // 30 minutos de inactividad
let checkInterval = 60 * 1000; // Verificar cada minuto
let dialogShown = false;
let sessionCheckTimer = null;
let visibilityTimer = null;
let sessionHeartbeat = null;

// Función para mostrar el diálogo de confirmación de cierre
function showLogoutConfirmation() {
  if (dialogShown) return; // Evitar mostrar múltiples diálogos
  dialogShown = true;

  // Crear un elemento de diálogo modal usando Bootstrap
  const modalHtml = `
    <div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-warning">
            <h5 class="modal-title" id="logoutConfirmModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Alerta de seguridad</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <h6>¿Está seguro que desea salir?</h6>
            <p>Su sesión podría quedar abierta si cierra el navegador directamente.</p>
            <p class="mb-0"><strong>Se recomienda cerrar sesión correctamente para mayor seguridad.</strong></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver a la aplicación</button>
            <a href="logout_secure.php" class="btn btn-danger"><i class="fas fa-power-off me-1"></i> Cerrar sesión ahora</a>
          </div>
        </div>
      </div>
    </div>
  `;

  // Añadir el modal al DOM
  const modalContainer = document.createElement("div");
  modalContainer.innerHTML = modalHtml;
  document.body.appendChild(modalContainer);

  try {
    // Mostrar el modal usando Bootstrap
    const modal = new bootstrap.Modal(
      document.getElementById("logoutConfirmModal")
    );
    modal.show();

    // Cuando se cierre el modal, resetear la variable
    document
      .getElementById("logoutConfirmModal")
      .addEventListener("hidden.bs.modal", function () {
        dialogShown = false;
        try {
          document.body.removeChild(modalContainer);
        } catch (e) {
          console.log("Error al eliminar el modal:", e);
        }
      });
  } catch (e) {
    console.error("Error al mostrar el modal:", e);
    // Fallback a confirm nativo si Bootstrap no está disponible
    if (
      confirm(
        "¿Está seguro que desea salir? Su sesión podría quedar abierta. Se recomienda cerrar sesión correctamente."
      )
    ) {
      window.location.href = "logout_secure.php";
    }
    dialogShown = false;
  }
}

// Función para registrar actividad del usuario
function registerActivity() {
  lastActivity = Date.now();
  sessionActive = true;
}

// Función para verificar el estado de la sesión
function checkSessionStatus() {
  const now = Date.now();
  const inactiveTime = now - lastActivity;

  // Si ha pasado demasiado tiempo sin actividad, cerrar sesión
  if (inactiveTime > inactivityTimeout && sessionActive) {
    console.log("Inactividad detectada, cerrando sesión automáticamente");
    sessionActive = false;
    performLogout(true);
  }
}

// Función para realizar el cierre de sesión
function performLogout(isAuto = false) {
  // Usar sendBeacon para enviar la solicitud de cierre de sesión de forma asíncrona
  if (navigator.sendBeacon) {
    navigator.sendBeacon(`logout_secure.php?auto=${isAuto ? 1 : 0}`);
  } else {
    // Fallback para navegadores que no soportan sendBeacon
    fetch(`logout_secure.php?auto=${isAuto ? 1 : 0}`, {
      method: "GET",
      keepalive: true, // Esto permite que la solicitud continúe incluso si la página se cierra
    }).catch((e) => console.error("Error en cierre de sesión:", e));
  }
}

// Función para iniciar el sistema de seguridad de sesión
function initSessionSecurity() {
  // Registrar eventos de actividad del usuario
  const activityEvents = [
    "mousedown",
    "mousemove",
    "keypress",
    "scroll",
    "touchstart",
    "click",
    "keydown",
  ];

  activityEvents.forEach((event) => {
    document.addEventListener(event, registerActivity, { passive: true });
  });

  // Iniciar verificación periódica de la sesión
  sessionCheckTimer = setInterval(checkSessionStatus, checkInterval);

  // Manejar cambios de visibilidad de la página
  document.addEventListener("visibilitychange", function () {
    if (document.visibilityState === "hidden") {
      // La página se ha ocultado (cambio de pestaña, minimizado, etc.)
      // Iniciar un temporizador para cerrar sesión si la página permanece oculta
      visibilityTimer = setTimeout(() => {
        if (document.visibilityState === "hidden" && sessionActive) {
          console.log("Página oculta por mucho tiempo, cerrando sesión");
          performLogout(true);
        }
      }, 5 * 60 * 1000); // 5 minutos
    } else {
      // La página es visible de nuevo, cancelar el temporizador
      if (visibilityTimer) {
        clearTimeout(visibilityTimer);
        visibilityTimer = null;
      }
      registerActivity(); // Registrar como actividad
    }
  });

  // Configurar heartbeat para mantener la sesión activa mientras el usuario está usando la aplicación
  sessionHeartbeat = setInterval(() => {
    if (sessionActive && document.visibilityState === "visible") {
      // Solo enviar heartbeat si la sesión está activa y la página es visible
      fetch("session_heartbeat.php", {
        method: "GET",
        headers: { "X-Requested-With": "XMLHttpRequest" },
      }).catch((e) => console.log("Error en heartbeat:", e));
    }
  }, 5 * 60 * 1000); // Cada 5 minutos

  // Manejar cierre de ventana/navegador con diferentes enfoques

  // 1. Enfoque tradicional con beforeunload
  window.addEventListener("beforeunload", function (e) {
    // Intentar cerrar la sesión de forma asíncrona
    performLogout(false);

    // Mostrar mensaje de confirmación estándar del navegador
    const confirmationMessage =
      "¿Está seguro que desea salir? Su sesión podría quedar abierta.";
    e.preventDefault();
    e.returnValue = confirmationMessage;
    return confirmationMessage;
  });

  // 2. Enfoque con unload como último recurso
  window.addEventListener("unload", function () {
    performLogout(false);
  });

  // 3. Crear un botón de cierre seguro en la interfaz
  const addSecureExitButton = () => {
    // Buscar el contenedor de la barra de navegación o header
    const navContainer = document.querySelector(
      "nav, header, .navbar, .header"
    );

    if (navContainer) {
      // Crear botón de cierre seguro
      const secureExitBtn = document.createElement("button");
      secureExitBtn.className = "btn btn-sm btn-outline-danger ms-2";
      secureExitBtn.innerHTML = '<i class="fas fa-power-off"></i> Salir seguro';
      secureExitBtn.title = "Cierre seguro de sesión";
      secureExitBtn.onclick = showLogoutConfirmation;

      // Añadir el botón al contenedor
      navContainer.appendChild(secureExitBtn);
    }
  };

  // Añadir el botón cuando el DOM esté listo
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", addSecureExitButton);
  } else {
    addSecureExitButton();
  }

  console.log("Sistema de seguridad de sesión iniciado");
}

// Crear un archivo PHP para el heartbeat
const createHeartbeatFile = () => {
  // Esta función es solo informativa, debes crear manualmente el archivo
  console.log(`
  Debes crear un archivo session_heartbeat.php con el siguiente contenido:
  
  <?php
  // session_heartbeat.php
  session_start();
  
  // Actualizar la marca de tiempo de la última actividad
  $_SESSION['last_activity'] = time();
  
  // Responder con un estado simple
  header('Content-Type: application/json');
  echo json_encode(['status' => 'active', 'timestamp' => $_SESSION['last_activity']]);
  `);
};

// Iniciar el sistema de seguridad cuando se carga la página
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initSessionSecurity);
} else {
  initSessionSecurity();
}

// Exportar funciones para uso externo
window.sessionSecurity = {
  showLogoutConfirmation,
  performLogout,
  registerActivity,
};
