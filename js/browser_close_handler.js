/**
 * browser_close_handler.js
 * Script especializado para detectar el cierre del navegador y mostrar un popup de confirmación
 */

(function () {
  // Función para crear y mostrar el popup
  function createPopup() {
    // Crear el contenedor principal
    const popupContainer = document.createElement("div");
    popupContainer.id = "secureLogoutPopup";
    popupContainer.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        `;

    // Crear el contenido del popup
    const popupContent = document.createElement("div");
    popupContent.style.cssText = `
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        `;

    // Encabezado
    const header = document.createElement("div");
    header.style.cssText = `
            background-color: #ffc107;
            margin: -20px -20px 15px -20px;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        `;

    const title = document.createElement("h5");
    title.textContent = "Alerta de seguridad";
    title.style.margin = "0";

    const closeBtn = document.createElement("button");
    closeBtn.innerHTML = "&times;";
    closeBtn.style.cssText = `
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        `;
    closeBtn.onclick = function () {
      document.body.removeChild(popupContainer);
    };

    header.appendChild(title);
    header.appendChild(closeBtn);

    // Cuerpo
    const body = document.createElement("div");
    body.innerHTML = `
            <h6 style="margin-top: 0;">¿Está seguro que desea salir?</h6>
            <p>Su sesión podría quedar abierta si cierra el navegador directamente.</p>
            <p style="margin-bottom: 0;"><strong>Se recomienda cerrar sesión correctamente para mayor seguridad.</strong></p>
        `;

    // Pie
    const footer = document.createElement("div");
    footer.style.cssText = `
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        `;

    const cancelBtn = document.createElement("button");
    cancelBtn.textContent = "Volver a la aplicación";
    cancelBtn.style.cssText = `
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
        `;
    cancelBtn.onclick = function () {
      document.body.removeChild(popupContainer);
    };

    const logoutBtn = document.createElement("button");
    logoutBtn.innerHTML =
      '<i style="margin-right: 5px;">⚡</i> Cerrar sesión ahora';
    logoutBtn.style.cssText = `
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
        `;
    logoutBtn.onclick = function () {
      window.location.href = "logout_secure.php";
    };

    footer.appendChild(cancelBtn);
    footer.appendChild(logoutBtn);

    // Ensamblar el popup
    popupContent.appendChild(header);
    popupContent.appendChild(body);
    popupContent.appendChild(footer);
    popupContainer.appendChild(popupContent);

    // Añadir al DOM
    document.body.appendChild(popupContainer);
  }

  // Función para cerrar sesión de forma asíncrona
  function performLogout() {
    // Usar sendBeacon para enviar la solicitud de cierre de sesión de forma asíncrona
    if (navigator.sendBeacon) {
      navigator.sendBeacon("logout_secure.php?auto=1");
    } else {
      // Fallback para navegadores que no soportan sendBeacon
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "logout_secure.php?auto=1", false); // Síncrono como último recurso
      try {
        xhr.send();
      } catch (err) {
        // Ignorar errores
      }
    }
  }

  // Función para mostrar el popup cuando el usuario intenta cerrar la ventana
  function handleBeforeUnload(e) {
    // Mostrar el popup personalizado
    createPopup();

    // Intentar cerrar la sesión de forma asíncrona
    performLogout();

    // Mostrar mensaje de confirmación estándar del navegador
    const confirmationMessage =
      "¿Está seguro que desea salir? Su sesión podría quedar abierta.";
    e.preventDefault();
    e.returnValue = confirmationMessage;
    return confirmationMessage;
  }

  // Función para añadir un botón de cierre seguro en la interfaz
  function addSecureExitButton() {
    // Buscar el contenedor de la barra de navegación o header
    const navContainer = document.querySelector(
      "nav, header, .navbar, .header, .dropdown-menu"
    );

    if (navContainer) {
      // Crear botón de cierre seguro
      const secureExitBtn = document.createElement("button");
      secureExitBtn.className = "btn btn-sm btn-outline-danger ms-2";
      secureExitBtn.innerHTML = '<i class="fas fa-power-off"></i> Salir seguro';
      secureExitBtn.title = "Cierre seguro de sesión";
      secureExitBtn.onclick = createPopup;

      // Añadir el botón al contenedor
      navContainer.appendChild(secureExitBtn);
    }
  }

  // Inicializar cuando el DOM esté listo
  function init() {
    // Registrar el evento beforeunload
    window.addEventListener("beforeunload", handleBeforeUnload);

    // Registrar el evento unload como respaldo
    window.addEventListener("unload", performLogout);

    // Añadir el botón de cierre seguro
    addSecureExitButton();

    console.log("Browser close handler inicializado");
  }

  // Iniciar cuando el DOM esté listo
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
