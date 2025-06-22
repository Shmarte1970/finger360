/**
 * auto_logout.js
 * Sistema para cerrar la sesión automáticamente cuando se detecta que el navegador se ha cerrado
 */

(function () {
  // Configuración
  const HEARTBEAT_INTERVAL = 10000; // 10 segundos
  const BROWSER_ID_KEY = "browser_session_id";

  // Generar un ID único para esta instancia del navegador si no existe
  let browserId = localStorage.getItem(BROWSER_ID_KEY);
  if (!browserId) {
    browserId = Date.now().toString(36) + Math.random().toString(36).substr(2);
    localStorage.setItem(BROWSER_ID_KEY, browserId);
  }

  // Función para enviar heartbeat al servidor
  function sendHeartbeat() {
    fetch("session_heartbeat.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        timestamp: Date.now(),
        browser_id: browserId,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Heartbeat enviado:", data);

        // Si no hay navegadores activos, podría ser que la sesión se haya cerrado
        if (data.active_browsers <= 1) {
          console.log("Este es el único navegador activo");
        }
      })
      .catch((error) => {
        console.error("Error al enviar heartbeat:", error);
      });
  }

  // Función para verificar el estado de la sesión
  function checkSessionStatus() {
    fetch("check_session_status.php", {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Estado de sesión:", data);

        // Si la sesión ya no está activa, redirigir al login
        if (!data.active) {
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

  // Función para cerrar la sesión
  function logout() {
    fetch("logout_secure.php?auto=1", {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Sesión cerrada:", data);
        window.location.href =
          "index.php?msg=" +
          encodeURIComponent("Ha cerrado sesión de forma segura.");
      })
      .catch((error) => {
        console.error("Error al cerrar sesión:", error);
        // Intentar redirigir de todos modos
        window.location.href = "index.php";
      });
  }

  // Función para manejar el cierre del navegador
  function handleBeforeUnload(e) {
    // Intentar enviar un último heartbeat
    navigator.sendBeacon(
      "session_heartbeat.php?closing=1&browser_id=" +
        encodeURIComponent(browserId)
    );

    // Mensaje estándar de confirmación
    const message =
      "¿Está seguro que desea salir? Su sesión se cerrará automáticamente.";
    e.returnValue = message;
    return message;
  }

  // Inicializar el sistema
  function init() {
    // Enviar heartbeat inicial
    sendHeartbeat();

    // Configurar intervalo para enviar heartbeats periódicos
    setInterval(sendHeartbeat, HEARTBEAT_INTERVAL);

    // Verificar estado de la sesión periódicamente
    setInterval(checkSessionStatus, HEARTBEAT_INTERVAL * 2);

    // Registrar evento beforeunload
    window.addEventListener("beforeunload", handleBeforeUnload);

    // Registrar eventos de visibilidad
    document.addEventListener("visibilitychange", function () {
      if (document.visibilityState === "visible") {
        // La página es visible, enviar heartbeat inmediatamente
        sendHeartbeat();
      }
    });

    // Añadir botón de cierre seguro
    addLogoutButton();

    console.log("Sistema de auto-logout iniciado con ID:", browserId);
  }

  // Función para añadir un botón de cierre seguro
  /*   function addLogoutButton() {
             // Buscar el contenedor adecuado
             const container = document.querySelector(
               ".navbar .dropdown-menu, nav .dropdown-menu"
             );

             if (container) {
               // Crear elemento de lista
               const listItem = document.createElement("li");

               // Crear enlace
               const link = document.createElement("a");
               link.className = "dropdown-item text-danger";
               link.href = "#";
               link.innerHTML =
                 '<i class="fas fa-power-off me-1"></i> Cerrar sesión seguro';
                 link.onclick = function (e) {
                   e.preventDefault();
                   logout();
                 };

               // Añadir enlace al elemento de lista
               listItem.appendChild(link);

               // Añadir elemento de lista al contenedor
               container.appendChild(listItem);
             }
           }

           // Iniciar cuando el DOM esté listo
           if (document.readyState === "loading") {
             document.addEventListener("DOMContentLoaded", init);
           } else {
             init();
           } */
})();
