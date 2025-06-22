/**
 * session_heartbeat.js
 * Sistema de heartbeat para detectar cuando el navegador se cierra y cerrar la sesión automáticamente
 */

(function () {
  // Configuración
  const HEARTBEAT_INTERVAL = 10000; // 10 segundos
  const SESSION_TIMEOUT = 20000; // 20 segundos (tiempo sin heartbeat para considerar sesión cerrada)
  const STORAGE_KEY = "session_last_heartbeat";
  const SESSION_ID_KEY = "session_browser_id";

  // Generar un ID único para esta instancia del navegador
  const browserSessionId = generateUniqueId();

  // Almacenar el ID de sesión del navegador
  localStorage.setItem(SESSION_ID_KEY, browserSessionId);

  // Función para generar un ID único
  function generateUniqueId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
  }

  // Función para enviar heartbeat al servidor
  function sendHeartbeat() {
    // Registrar el tiempo del último heartbeat
    const timestamp = Date.now();
    localStorage.setItem(STORAGE_KEY, timestamp);

    // Enviar heartbeat al servidor
    fetch("session_heartbeat.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        timestamp: timestamp,
        browser_id: browserSessionId,
      }),
    }).catch((error) => {
      console.error("Error al enviar heartbeat:", error);
    });
  }

  // Función para verificar si otro navegador ha cerrado la sesión
  function checkSessionStatus() {
    fetch("check_session_status.php", {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => response.json())
      .then((data) => {
        if (!data.active) {
          // La sesión ya no está activa, redirigir al login
          window.location.href =
            "index.php?msg=" +
            encodeURIComponent(
              "Su sesión ha expirado o ha sido cerrada desde otro dispositivo."
            );
        }
      })
      .catch((error) => {
        console.error("Error al verificar estado de sesión:", error);
      });
  }

  // Iniciar el sistema de heartbeat
  function initHeartbeatSystem() {
    // Enviar heartbeat inicial
    sendHeartbeat();

    // Configurar intervalo para enviar heartbeats periódicos
    setInterval(sendHeartbeat, HEARTBEAT_INTERVAL);

    // Verificar estado de la sesión periódicamente
    setInterval(checkSessionStatus, HEARTBEAT_INTERVAL * 2);

    // Registrar eventos de visibilidad
    document.addEventListener("visibilitychange", function () {
      if (document.visibilityState === "visible") {
        // La página es visible, enviar heartbeat inmediatamente
        sendHeartbeat();
      }
    });

    console.log("Sistema de heartbeat iniciado con ID:", browserSessionId);
  }

  // Iniciar cuando el DOM esté listo
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHeartbeatSystem);
  } else {
    initHeartbeatSystem();
  }
})();
