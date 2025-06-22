// Variable para controlar si ya se ha mostrado el diálogo
let dialogShown = false;

// Función para mostrar el diálogo de confirmación
function showLogoutConfirmation() {
  if (dialogShown) return; // Evitar mostrar múltiples diálogos
  dialogShown = true;

  // Crear un elemento de diálogo modal usando Bootstrap si está disponible
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
      document.body.removeChild(modalContainer);
    });
}

// Detectar cuando el navegador se está cerrando
let logoutConfirmed = false;

// Controlar el aviso de salida solo al pulsar logout
let shouldWarnOnUnload = false;

// Listener beforeunload solo si shouldWarnOnUnload es true
window.addEventListener("beforeunload", function (e) {
  if (shouldWarnOnUnload) {
    e.preventDefault();
    e.returnValue = "";
    return "";
  }
});

// Activar el aviso solo al pulsar logout
// Para todos los enlaces de logout

document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll('a[href*="logout"]').forEach(function (link) {
    link.addEventListener("click", function () {
      shouldWarnOnUnload = true;
    });
  });
});

window.confirmarCierreSesion = function (event) {
  if (event) event.preventDefault();

  if (confirm("¿Está seguro que desea cerrar la sesión?")) {
    // Marcar que el cierre fue confirmado para evitar el segundo diálogo
    logoutConfirmed = true;
    // Redirigir al script de logout
    window.location.href = "logout.php";
  }
};

// Opcional: Agregar el evento de confirmación a todos los enlaces de cierre de sesión
document.addEventListener("DOMContentLoaded", function () {
  // Buscar todos los enlaces de logout
  const logoutLinks = document.querySelectorAll('a[href*="logout"]');
  logoutLinks.forEach(function (link) {
    link.addEventListener("click", window.confirmarCierreSesion);
  });
});

document.addEventListener("DOMContentLoaded", function () {
  // Inicializar el manejador de imágenes
  window.imageHandler = new ImageHandler();

  // Cargar contactos al inicio si estamos en la página de dashboard
  if (document.getElementById("contactsContainer")) {
    console.log("Cargando contactos desde DOMContentLoaded en dashboard.js");
    setTimeout(() => {
      if (typeof window.loadContacts === "function") {
        window.loadContacts();
      }
    }, 100);
  }

  // Añadir evento click a todos los botones de toggle de contraseña
  document.querySelectorAll(".toggle-password").forEach((button) => {
    button.addEventListener("click", function () {
      const targetId = this.getAttribute("data-target");
      const input = document.getElementById(targetId);

      // Alternar entre mostrar y ocultar la contraseña
      if (input.type === "password") {
        input.type = "text";
        this.innerHTML = '<i class="fa fa-eye-slash"></i>';
      } else {
        input.type = "password";
        this.innerHTML = '<i class="fa fa-eye"></i>';
      }
    });
  });

  // Funcionalidad de búsqueda mejorada para la tabla agcontactos
  const searchInput = document.getElementById("searchAgcontactos");
  const searchButton = document.getElementById("btnSearchAgcontactos");
  const clearButton = document.getElementById("btnClearSearch");
  let searchTimeout; // Variable para almacenar el timeout de búsqueda
  window.lastSearchTerm = ""; // Almacenar el último término de búsqueda (inicializado como cadena vacía y disponible globalmente)

  if (searchButton && searchInput) {
    // Mostrar/ocultar el botón de limpiar según si hay texto
    searchInput.addEventListener("input", function () {
      if (this.value.trim() !== "") {
        clearButton.classList.remove("d-none");
      } else {
        clearButton.classList.add("d-none");
      }
    });

    // Funcionalidad del botón de limpiar
    if (clearButton) {
      clearButton.addEventListener("click", function () {
        searchInput.value = "";
        searchInput.classList.remove("search-active");
        this.classList.add("d-none");
        window.lastSearchTerm = "";
        window.loadContacts(); // Cargar todos los contactos
        searchInput.focus(); // Devolver el foco al campo de búsqueda

        // Mostrar mensaje de búsqueda limpiada
        showSearchMessage(
          "Búsqueda limpiada. Mostrando todos los contactos.",
          "info",
          3000
        );
      });
    }

    // Búsqueda al hacer clic en el botón
    searchButton.addEventListener("click", function () {
      const searchTerm = searchInput.value.trim(); // Mantener mayúsculas/minúsculas originales
      if (searchTerm.toLowerCase() !== window.lastSearchTerm.toLowerCase()) {
        // Comparar en minúsculas
        window.lastSearchTerm = searchTerm;
        window.searchAgcontactos(searchTerm);
      }
    });

    // Búsqueda al presionar Enter
    searchInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        e.preventDefault(); // Prevenir el comportamiento por defecto
        const searchTerm = searchInput.value.trim(); // Mantener mayúsculas/minúsculas originales
        if (searchTerm.toLowerCase() !== window.lastSearchTerm.toLowerCase()) {
          // Comparar en minúsculas
          window.lastSearchTerm = searchTerm;
          window.searchAgcontactos(searchTerm);
        }
      }
    });

    // Búsqueda en tiempo real (después de una pausa de escritura)
    searchInput.addEventListener("input", function () {
      // Añadir clase de búsqueda activa
      searchInput.classList.add("search-active");

      // Limpiar el timeout anterior
      clearTimeout(searchTimeout);

      // Establecer un nuevo timeout para evitar muchas solicitudes
      searchTimeout = setTimeout(() => {
        const searchTerm = searchInput.value.trim(); // Mantener mayúsculas/minúsculas originales

        // Solo buscar si hay al menos 2 caracteres o si el campo está vacío
        // Y si el término de búsqueda ha cambiado
        if (
          (searchTerm.length >= 2 || searchTerm.length === 0) &&
          searchTerm.toLowerCase() !== window.lastSearchTerm.toLowerCase() // Comparar en minúsculas
        ) {
          window.lastSearchTerm = searchTerm;
          window.searchAgcontactos(searchTerm);
        }

        // Si el campo está vacío, quitar la clase de búsqueda activa
        if (searchTerm.length === 0) {
          searchInput.classList.remove("search-active");
        }
      }, 500); // Esperar 500ms después de que el usuario deje de escribir
    });
  }

  // Función para mostrar mensajes de búsqueda
  // Hacemos la función disponible globalmente
  window.showSearchMessage = function (
    message,
    type = "info",
    duration = 5000
  ) {
    const contactsContainer = document.getElementById("contactsContainer");
    if (!contactsContainer) return;

    // Crear elemento de mensaje
    const messageElement = document.createElement("div");
    messageElement.className = `alert alert-${type} mt-2 mb-3 search-message`;
    messageElement.style.fontSize = "0.9rem";
    messageElement.style.width = "100%";
    messageElement.style.boxSizing = "border-box";
    messageElement.innerHTML = `<i class="fas fa-${
      type === "info" ? "info-circle" : "exclamation-circle"
    }"></i> ${message}`;

    // Eliminar mensajes anteriores
    document.querySelectorAll(".search-message").forEach((el) => el.remove());

    // Insertar al principio del contenedor
    contactsContainer.insertBefore(
      messageElement,
      contactsContainer.firstChild
    );

    // Hacer que desaparezca después del tiempo especificado
    if (duration > 0) {
      setTimeout(() => {
        messageElement.classList.add("fade");
        setTimeout(() => messageElement.remove(), 500);
      }, duration);
    }

    return messageElement;
  };

  // Función para buscar contactos (devuelve una promesa)
  // Hacemos la función disponible globalmente
  window.searchAgcontactos = function (term) {
    // Mostrar un indicador visual de que la búsqueda está en progreso
    const searchButton = document.getElementById("btnSearchAgcontactos");
    if (searchButton) {
      searchButton.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <span class="search-text">Buscando...</span>';
      searchButton.disabled = true;
    }

    // Mostrar mensaje de búsqueda en progreso
    const searchingMessage = showSearchMessage(
      "Buscando contactos...",
      "info",
      0
    );

    // Cargar contactos con el término de búsqueda
    return loadContacts(term)
      .then((data) => {
        // Mostrar mensaje con resultados
        if (searchingMessage) searchingMessage.remove();

        if (term) {
          const count = data.contacts ? data.contacts.length : 0;
          if (count > 0) {
            showSearchMessage(
              `Se encontraron <strong>${count}</strong> contactos para "<strong>${term}</strong>"`,
              "info"
            );
          }
        }

        return data;
      })
      .catch((error) => {
        // Mostrar mensaje de error
        if (searchingMessage) searchingMessage.remove();
        showSearchMessage(`Error en la búsqueda: ${error.message}`, "danger");
        return Promise.reject(error);
      })
      .finally(() => {
        // Restaurar el botón de búsqueda cuando termine
        if (searchButton) {
          searchButton.innerHTML =
            '<i class="fas fa-search"></i> <span class="search-text">Buscar</span>';
          searchButton.disabled = false;
        }
      });
  };

  // Función para cargar los contactos (devuelve una promesa)
  // Hacemos la función disponible globalmente
  window.loadContacts = function (searchTerm = "") {
    console.log("Cargando contactos...");

    // Mostrar indicador de carga
    const loadingElement = document.getElementById("loadingContacts");
    const noContactsElement = document.getElementById("noContactsMessage");

    if (loadingElement) loadingElement.classList.remove("d-none");
    if (noContactsElement) noContactsElement.classList.add("d-none");

    // Construir la URL con el término de búsqueda si existe
    // Asegurarse de que la URL sea absoluta para evitar problemas de rutas relativas
    let url = window.location.origin + "/agent/api/get_contacts.php";
    console.log("URL base para API:", url);

    if (searchTerm) {
      url += `?search=${encodeURIComponent(searchTerm)}`;
    }

    console.log("URL completa para obtener contactos:", url);

    // Realizar la solicitud AJAX y devolver la promesa
    return fetch(url, {
      method: "GET",
      headers: {
        "Cache-Control": "no-cache",
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin",
    })
      .then((response) => {
        console.log("Respuesta recibida:", response.status);
        console.log("Headers:", [...response.headers.entries()]);

        if (!response.ok) {
          throw new Error(
            `Error de servidor: ${response.status} ${response.statusText}`
          );
        }

        return response.text().then((text) => {
          console.log("Respuesta en texto:", text);
          console.log("Longitud de la respuesta:", text.length);

          if (!text || text.trim() === "") {
            throw new Error("Respuesta vacía del servidor");
          }

          try {
            const data = JSON.parse(text);
            console.log("JSON parseado correctamente:", data);
            return data;
          } catch (e) {
            console.error("Error al analizar JSON. Texto recibido:", text);
            console.error("Detalles del error:", e);
            throw new Error(`Error al analizar JSON: ${e.message}`);
          }
        });
      })
      .then((data) => {
        console.log("Datos recibidos:", data);

        // Ocultar indicador de carga
        if (loadingElement) loadingElement.classList.add("d-none");

        // Obtener el contenedor de contactos
        const contactsContainer = document.getElementById("contactsContainer");
        if (!contactsContainer) {
          throw new Error("No se encontró el contenedor de contactos");
        }

        // Limpiar el contenedor (excepto los mensajes de carga/no hay contactos)
        const elementsToKeep = ["loadingContacts", "noContactsMessage"];
        Array.from(contactsContainer.children).forEach((child) => {
          if (!elementsToKeep.includes(child.id)) {
            contactsContainer.removeChild(child);
          }
        });

        // Verificar si hay contactos
        if (data.success && data.contacts && data.contacts.length > 0) {
          // Mostrar los contactos
          const fragment = document.createDocumentFragment(); // Usar fragment para mejor rendimiento

          // Crear el botón "Añadir Contacto"
          let addBtn = document.createElement("button");
          addBtn.className =
            "btn btn-sm btn-success mt-2 mb-2 floating-add-btn";
          addBtn.id = "btnNoContactsAdd";
          addBtn.innerHTML = '<i class="fas fa-plus"></i> Añadir Contacto';
          addBtn.onclick = function () {
            window.openContactModal(null);
          };

          // Insertar las cards
          data.contacts.forEach((contact) => {
            fragment.appendChild(createContactCard(contact));
          });

          // Insertar el botón y las cards
          contactsContainer.appendChild(addBtn);
          contactsContainer.appendChild(fragment);

          // Ocultar mensaje de no hay contactos
          if (noContactsElement) noContactsElement.classList.add("d-none");
        } else {
          // Mostrar mensaje de no hay contactos
          if (noContactsElement) {
            noContactsElement.classList.remove("d-none");

            // Personalizar el mensaje si es una búsqueda
            if (searchTerm) {
              noContactsElement.innerHTML = `
                <p class="text-muted">No se encontraron contactos para "<strong>${searchTerm}</strong>".</p>
                <p class="text-muted small">Prueba con otro término o <a href="#" class="clear-search">ver todos los contactos</a>.</p>
              `;

              // Agregar evento al enlace para limpiar la búsqueda
              const clearLink =
                noContactsElement.querySelector(".clear-search");
              if (clearLink) {
                clearLink.addEventListener("click", function (e) {
                  e.preventDefault();
                  const searchInput =
                    document.getElementById("searchAgcontactos");
                  const clearButton = document.getElementById("btnClearSearch");
                  if (searchInput) {
                    searchInput.value = "";
                    searchInput.classList.remove("search-active");
                  }
                  if (clearButton) clearButton.classList.add("d-none");
                  window.loadContacts();
                });
              }
            } else {
              noContactsElement.innerHTML = `
                <p class="text-muted">No se encontraron contactos. ¡Añade uno nuevo!</p>
                <button class="btn btn-sm btn-success mt-2 floating-add-btn" id="btnNoContactsAddEmpty">
                  <i class="fas fa-plus"></i> Añadir Contacto
                </button>
              `;

              // Agregar evento al botón para añadir contacto
              const addButton = noContactsElement.querySelector(
                "#btnNoContactsAddEmpty"
              );
              if (addButton) {
                addButton.addEventListener("click", function () {
                  window.openContactModal(null);
                });
              }
            }
          }
        }

        return data; // Devolver los datos para encadenar promesas
      })
      .catch((error) => {
        console.error("Error al cargar contactos:", error);

        if (loadingElement) loadingElement.classList.add("d-none");

        if (noContactsElement) {
          noContactsElement.classList.remove("d-none");
          noContactsElement.innerHTML = `
            <div class="alert alert-danger">
              <p><i class="fas fa-exclamation-triangle"></i> Error al cargar los contactos:</p>
              <p class="small">${error.message || "Error de conexión"}</p>
              <button class="btn btn-outline-primary btn-sm mt-2" id="btnRetryLoad">
                <i class="fas fa-sync-alt"></i> Reintentar
              </button>
            </div>
          `;

          // Agregar evento al botón para reintentar
          const retryButton = noContactsElement.querySelector("#btnRetryLoad");
          if (retryButton) {
            retryButton.addEventListener("click", function () {
              window.loadContacts(searchTerm);
            });
          }
        }

        return Promise.reject(error); // Re-lanzar el error para mantener la cadena de promesas
      });
  };

  // Función para crear una card de contacto
  // Hacemos la función disponible globalmente
  // Modificación en la función createContactCard para cambiar la dirección del dropdown

  window.createContactCard = function (contact) {
    const col = document.createElement("div");
    col.className = "mb-4 contact-card";
    col.style.width = "100%"; // Ocupa el 100% del contenedor padre
    col.style.marginBottom = "20px"; // Margen inferior entre tarjetas
    col.style.boxSizing = "border-box"; // Asegurar que el padding no afecte el ancho
    col.style.maxWidth = "400px"; // Ancho máximo fijo para todas las tarjetas
    col.style.minWidth = "100%"; // Ancho mínimo para asegurar que ocupe todo el espacio disponible

    // Determinar el icono según el sexo
    let genderIcon = "fa-genderless";
    let genderClass = "text-primary";
    let genderText = "No especificado";

    if (contact.sexo === "M") {
      genderIcon = "fa-solid fa-mars";
      genderClass = "text-primary";
      genderText = "Masculino";
    } else if (contact.sexo === "F") {
      genderIcon = "fa-solid fa-venus";
      genderClass = "text-danger";
      genderText = "Femenino";
    } else if (contact.sexo === "O") {
      genderIcon = "fa-solid fa-mars-and-venus";
      genderClass = "text-info";
      genderText = "Trans";
    }

    col.innerHTML = `
    <div class="card h-100" style="width: 100%; box-sizing: border-box; border-radius: 12px; overflow: hidden;">
      <div class="card-header d-flex justify-content-between align-items-start">
        <div class="d-flex align-items-center">
          <div class="d-flex flex-column align-items-center me-2">
            <div class="avatar-container mb-2">
              <img src="${
                contact.foto1
                  ? `api/get_contact_image.php?id=${
                      contact.idContacto
                    }&field=foto1&v=${new Date().getTime()}`
                  : "assets/img/placeholder.jpg"
              }" 
                   class="rounded-circle hover-zoom-image" 
                   alt="${contact.nomContacto}" 
                   style="width: 50px; height: 50px; object-fit: cover; border: 2px solid ${
                     contact.sexo === "M"
                       ? "#0d6efd"
                       : contact.sexo === "F"
                       ? "#dc3545"
                       : contact.sexo === "O"
                       ? "#0dcaf0"
                       : "#6c757d"
                   };" loading="lazy">
            </div>
            <h5 class="card-title mb-0 text-center" style="width: 100%;" title="${
              contact.nomContacto
            }">
              ${contact.nomContacto}
            </h5>
          </div>
          
          <div class="additional-images d-flex">
            ${
              contact.foto2
                ? `<div class="image-box me-1">
                <img src="api/get_contact_image.php?id=${
                  contact.idContacto
                }&field=foto2&v=${new Date().getTime()}" 
                     class="img-thumbnail hover-zoom-image" 
                     alt="Foto 2" loading="lazy">
              </div>`
                : ""
            }
            ${
              contact.foto3
                ? `<div class="image-box">
                <img src="api/get_contact_image.php?id=${
                  contact.idContacto
                }&field=foto3&v=${new Date().getTime()}" 
                     class="img-thumbnail hover-zoom-image" 
                     alt="Foto 3" loading="lazy">
              </div>`
                : ""
            }
          </div>
        </div>
        <div class="dropdown" style="position: absolute; top: 0.5rem; right: 0.5rem;">
          <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-ellipsis-v"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" style="min-width: auto; padding: 0.5rem 0;">
                <li>
                    <a class="dropdown-item edit-contact d-flex justify-content-center align-items-center" href="#" data-id="${
                      contact.idContacto || contact.id
                    }" style="padding: 0.5rem 1rem; min-width: 40px;">
                        <i class="fas fa-edit"></i>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item delete-contact d-flex justify-content-center align-items-center" href="#" data-id="${
                      contact.idContacto || contact.id
                    }" style="padding: 0.5rem 1rem; min-width: 40px;">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </li>
            </ul>
        </div>
      </div>
      <div class="card-body">
        <div class="contact-info">
          <p class="card-text">
            <span class="d-inline-flex align-items-center">
              <a href="tel:${contact.telefonoContacto}" 
                 class="btn btn-sm btn-primary rounded-circle me-2" 
                 title="Llamar a ${contact.nomContacto}">
                <i class="fas fa-phone-alt"></i>
              </a>
              <a href="https://wa.me/${contact.telefonoContacto.replace(
                /\D/g,
                ""
              )}" 
                 class="btn btn-sm btn-success rounded-circle me-2" 
                 target="_blank" 
                 title="Enviar mensaje por WhatsApp">
                <i class="fab fa-whatsapp"></i>
              </a>
              <span style="font-size: 1.1rem; color: #0d6efd;">${
                contact.telefonoContacto
              }</span>
            </span>
          </p>
          ${
            contact.adress
              ? `
            <p class="card-text">
              <i class="fas fa-map-marker-alt me-2 text-danger"></i> 
              <span title="${contact.adress}">${contact.adress}</span>
            </p>
          `
              : ""
          }
          <p class="card-text">
            <i class="fas ${genderIcon} me-2 ${genderClass}"></i> ${genderText}
          </p>
        </div>
      </div>
    </div>
  `;

    const additionalImagesHtml = `
  <div class="additional-images d-flex">
      ${
        contact.foto2
          ? `<div class="image-box me-1" style="position: relative; overflow: visible;">
              <img src="api/get_contact_image.php?id=${
                contact.idContacto
              }&field=foto2&v=${new Date().getTime()}" 
                  class="img-thumbnail hover-effect" 
                  alt="Foto 2" style="width: 50px; height: 50px; object-fit: cover;" loading="lazy">
            </div>`
          : ""
      }
      ${
        contact.foto3
          ? `<div class="image-box" style="position: relative; overflow: visible;">
              <img src="api/get_contact_image.php?id=${
                contact.idContacto
              }&field=foto3&v=${new Date().getTime()}" 
                  class="img-thumbnail hover-effect" 
                  alt="Foto 3" style="width: 50px; height: 50px; object-fit: cover;" loading="lazy">
            </div>`
          : ""
      }
  </div>
`;
    // Añadir eventos a los botones de editar y eliminar
    col.querySelector(".edit-contact").addEventListener("click", function (e) {
      e.preventDefault();
      openContactModal(contact);
    });

    col
      .querySelector(".delete-contact")
      .addEventListener("click", function (e) {
        e.preventDefault();
        const contactId = this.getAttribute("data-id");
        console.log(
          "ID obtenido del botón eliminar:",
          contactId,
          "Tipo:",
          typeof contactId
        );
        openDeleteModal(contactId);
      });

    // Añadir evento para hacer la tarjeta clickeable (opcional)
    const card = col.querySelector(".card");
    card.addEventListener("click", function (e) {
      // Solo activar si el clic no fue en un botón o enlace
      if (!e.target.closest("a") && !e.target.closest("button")) {
        openContactModal(contact);
      }
    });
    card.style.cursor = "pointer";

    // Añadir efecto hover
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-5px)";
      this.style.transition = "transform 0.3s ease";
      this.style.boxShadow = "0 5px 15px rgba(0,0,0,0.1)";
    });

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)";
      this.style.boxShadow = "0 2px 5px rgba(0,0,0,0.1)";
    });

    return col;
  };

  // Elimina todo el código JavaScript actual relacionado con el hover de imágenes

  // Función para abrir el modal de contacto (crear/editar)
  // Hacemos la función disponible globalmente
  window.openContactModal = function (contact = null) {
    // Obtener el modal y el formulario
    const modal = document.getElementById("contactModal");
    const form = document.getElementById("contactForm");
    const modalTitle = modal.querySelector(".modal-title");

    // Limpiar el formulario
    form.reset();
    document.getElementById("contactFormError").style.display = "none";

    // Restablecer las imágenes de vista previa
    for (let i = 1; i <= 3; i++) {
      const previewElement = document.getElementById(`preview-foto${i}`);
      if (previewElement) {
        previewElement.src = "assets/img/placeholder.jpg";
      }
    }

    if (contact) {
      // Modo edición
      modalTitle.textContent = "Editar Contacto";
      document.getElementById("contactId").value =
        contact.idContacto || contact.id;
      document.getElementById("nomContacto").value = contact.nomContacto;
      document.getElementById("telefonoContacto").value =
        contact.telefonoContacto;
      document.getElementById("adress").value = contact.adress || "";
      document.getElementById("sexo").value = contact.sexo || "";

      // Cargar imágenes si existen
      for (let i = 1; i <= 3; i++) {
        const fotoField = `foto${i}`;
        const previewElement = document.getElementById(`preview-${fotoField}`);

        if (contact[fotoField] && previewElement) {
          // Si la ruta de la imagen existe, mostrarla
          if (
            typeof contact[fotoField] === "string" &&
            contact[fotoField].trim() !== ""
          ) {
            previewElement.src = contact[fotoField];
          }
        }
      }
    } else {
      // Modo creación
      modalTitle.textContent = "Nuevo Contacto";
      document.getElementById("contactId").value = "";
    }

    // Mostrar el modal
    const contactModal = new bootstrap.Modal(modal);
    contactModal.show();

    // Reemplazar el botón Guardar por un clon limpio para evitar listeners duplicados
    const oldBtn = document.getElementById("saveContact");
    const newBtn = oldBtn.cloneNode(true);
    oldBtn.parentNode.replaceChild(newBtn, oldBtn);
    newBtn.addEventListener("click", function () {
      console.log("Botón guardar clickeado (desde openContactModal)");
      window.saveContact();
    });

    // En el modal de alta/edición de contacto:
    document.getElementById("telefonoContacto").setAttribute("type", "tel");
    document
      .getElementById("telefonoContacto")
      .setAttribute("pattern", "[0-9+ ]*");
  };

  // Función para abrir el modal de confirmación de eliminación
  // Hacemos la función disponible globalmente
  window.openDeleteModal = function (contactId) {
    console.log(
      "openDeleteModal recibió ID:",
      contactId,
      "Tipo:",
      typeof contactId
    );
    document.getElementById("deleteContactId").value = contactId;
    console.log(
      "Valor asignado:",
      document.getElementById("deleteContactId").value
    );
    const deleteModal = new bootstrap.Modal(
      document.getElementById("deleteContactModal")
    );
    deleteModal.show();

    // Vincular el evento al botón Eliminar cada vez que se abre el modal
    const confirmDeleteBtn = document.getElementById("confirmDeleteContact");
    if (confirmDeleteBtn) {
      confirmDeleteBtn.onclick = null; // Limpia cualquier handler anterior
      confirmDeleteBtn.addEventListener("click", function () {
        console.log("Botón eliminar clickeado (desde openDeleteModal)");
        window.deleteContact();
      });
    }
  };

  // Función para guardar un contacto (crear o actualizar)
  // Hacemos la función disponible globalmente
  window.saveContact = function () {
    console.log("Guardando contacto...");

    // Obtener los datos del formulario
    const contactId = document.getElementById("contactId").value;
    const nomContacto = document.getElementById("nomContacto").value;
    const telefonoContacto = document.getElementById("telefonoContacto").value;
    const adress = document.getElementById("adress").value;
    const sexo = document.getElementById("sexo").value;
    const errorDiv = document.getElementById("contactFormError");

    // Validar datos
    if (!nomContacto || !telefonoContacto) {
      errorDiv.textContent = "El nombre y el teléfono son obligatorios";
      errorDiv.style.display = "block";
      return;
    }

    // Preparar los datos
    const contactData = {
      nomContacto,
      telefonoContacto,
      adress,
      sexo,
    };

    // Determinar URL y método según si es creación o actualización
    let url;
    let method = "POST";
    let esEdicion = false;

    if (contactId) {
      // Es una actualización
      url = "api/update_contact_with_images.php";
      contactData.idContacto = contactId;
      esEdicion = true;
      console.log(
        "Modo edición: Usando update_contact_with_images.php con ID:",
        contactId
      );
    } else {
      // Es una creación
      url = "api/create_contact_with_images.php";
      console.log("Modo creación: Usando create_contact_with_images.php");
      // No enviar ningún campo 'id' ni 'idContacto'
      delete contactData.id;
      delete contactData.idContacto;
    }

    console.log("Enviando datos a:", url);
    console.log("Datos:", contactData);

    // Obtener los archivos de imagen
    const imageFiles = window.imageHandler.getImageFiles();
    console.log("Archivos de imagen:", imageFiles);

    // Crear un FormData para enviar tanto datos como archivos
    const formData = new FormData();

    // Añadir los datos del contacto
    formData.append("contactData", JSON.stringify(contactData));

    // Añadir los archivos de imagen si existen
    for (const [key, file] of Object.entries(imageFiles)) {
      formData.append(key, file);
    }

    // Mostrar indicador de carga
    const saveButton = document.getElementById("saveContact");
    const originalText = saveButton.innerHTML;
    saveButton.innerHTML =
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
    saveButton.disabled = true;

    // Enviar los datos al servidor
    fetch(url, {
      method: method,
      body: formData, // No establecer Content-Type, FormData lo hace automáticamente
      credentials: "same-origin",
    })
      .then((response) => {
        console.log("Respuesta recibida:", response.status);
        console.log("Headers:", [...response.headers.entries()]);

        if (!response.ok) {
          throw new Error(
            `Error de servidor: ${response.status} ${response.statusText}`
          );
        }

        return response.text().then((text) => {
          console.log("Respuesta en texto:", text);
          console.log("Longitud de la respuesta:", text.length);

          if (!text || text.trim() === "") {
            throw new Error("Respuesta vacía del servidor");
          }

          try {
            const data = JSON.parse(text);
            console.log("JSON parseado correctamente:", data);
            return data;
          } catch (e) {
            console.error("Error al analizar JSON. Texto recibido:", text);
            console.error("Detalles del error:", e);
            throw new Error(`Error al analizar JSON: ${e.message}`);
          }
        });
      })
      .then((data) => {
        console.log("Datos recibidos:", data);

        // Restaurar el botón de guardar
        const saveButton = document.getElementById("saveContact");
        saveButton.innerHTML = originalText;
        saveButton.disabled = false;

        if (data.success) {
          // Cerrar el modal
          const modal = bootstrap.Modal.getInstance(
            document.getElementById("contactModal")
          );
          modal.hide();

          // Recargar los contactos
          window.loadContacts(
            document.getElementById("searchAgcontactos").value
          );

          // Mostrar notificación visual de éxito
          if (esEdicion) {
            showWarningToast(
              data.message || "Datos actualizados correctamente"
            );
          } else {
            showSuccessToast(data.message || "Contacto añadido correctamente");
          }
        } else {
          // Mostrar error
          errorDiv.textContent = data.message || "Error al guardar el contacto";
          errorDiv.style.display = "block";
        }
      })
      .catch((error) => {
        console.error("Error al guardar contacto:", error);

        // Restaurar el botón de guardar
        const saveButton = document.getElementById("saveContact");
        saveButton.innerHTML = originalText;
        saveButton.disabled = false;

        errorDiv.textContent =
          error.message || "Error en el servidor. Intente nuevamente.";
        errorDiv.style.display = "block";
      });
  };

  // Toast de éxito (verde)
  function showSuccessToast(message) {
    // Si ya existe un toast, elimínalo
    const oldToast = document.getElementById("successToast");
    if (oldToast) oldToast.remove();

    // Buscar el contenedor de las cards
    const container = document.getElementById("contactsContainer");
    let left = 0;
    let width = 0;
    if (container) {
      const rect = container.getBoundingClientRect();
      left = rect.left + window.scrollX;
      width = rect.width;
    }

    // Crear el HTML del toast
    const toastHtml = `
      <div id="successToast" class="toast align-items-center text-bg-success border-0"
        role="alert" aria-live="assertive" aria-atomic="true"
        style="z-index: 99999; min-width: 220px; max-width: 90vw; position: fixed; bottom: 16px; left: ${
          left + width - 320
        }px; width: 300px; background: rgba(40,167,69,0.98); box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
        <div class="d-flex">
          <div class="toast-body" style="color: #fff;">
            <i class="fas fa-check-circle me-2"></i> ${message}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML("beforeend", toastHtml);
    const toastEl = document.getElementById("successToast");
    if (window.bootstrap && window.bootstrap.Toast) {
      const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
      toast.show();
    } else {
      // Fallback si Bootstrap Toast no está disponible
      alert(message);
    }
  }

  // Toast de advertencia (naranja)
  function showWarningToast(message) {
    // Si ya existe un toast, elimínalo
    const oldToast = document.getElementById("successToast");
    if (oldToast) oldToast.remove();

    // Buscar el contenedor de las cards
    const container = document.getElementById("contactsContainer");
    let left = 0;
    let width = 0;
    if (container) {
      const rect = container.getBoundingClientRect();
      left = rect.left + window.scrollX;
      width = rect.width;
    }

    // Crear el HTML del toast
    const toastHtml = `
      <div id="successToast" class="toast align-items-center border-0"
        role="alert" aria-live="assertive" aria-atomic="true"
        style="z-index: 99999; min-width: 220px; max-width: 90vw; position: fixed; bottom: 16px; left: ${
          left + width - 320
        }px; width: 300px; background: rgba(255, 193, 7, 0.98); box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
        <div class="d-flex">
          <div class="toast-body" style="color: #212529;">
            <i class="fas fa-edit me-2" style="color: #212529;"></i> ${message}
          </div>
          <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML("beforeend", toastHtml);
    const toastEl = document.getElementById("successToast");
    if (window.bootstrap && window.bootstrap.Toast) {
      const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
      toast.show();
    } else {
      // Fallback si Bootstrap Toast no está disponible
      alert(message);
    }
  }

  // Funcionalidad para abrir el modal de edición de perfil
  document.addEventListener("DOMContentLoaded", function () {
    console.log("Script de perfil cargado correctamente");

    // Obtener referencias a los elementos que abren el modal
    const settingsMenuItem = document.querySelector(
      '.sidebar-menu li a[href="dashboard.php?page=settings"]'
    );
    const profileMenuItem = document.querySelector(
      '.dropdown-menu li a[href="dashboard.php?page=profile"]'
    );

    console.log("Elementos del menú:", {
      settingsMenuItem: settingsMenuItem ? "Encontrado" : "No encontrado",
      profileMenuItem: profileMenuItem ? "Encontrado" : "No encontrado",
    });

    // Verificar si los elementos existen antes de agregar event listeners
    if (settingsMenuItem) {
      settingsMenuItem.addEventListener("click", function (e) {
        console.log("Clic en Settings");
        e.preventDefault(); // Prevenir navegación predeterminada
        openProfileModal();
      });
    }

    if (profileMenuItem) {
      profileMenuItem.addEventListener("click", function (e) {
        console.log("Clic en Perfil");
        e.preventDefault(); // Prevenir navegación predeterminada
        openProfileModal();
      });
    }

    // Función para abrir el modal y cargar los datos del usuario
    function openProfileModal() {
      console.log("Abriendo modal de perfil");

      // Cargar datos actuales del usuario desde la sesión o mediante una solicitud AJAX
      loadUserData();

      // Mostrar el modal
      const editProfileModal = new bootstrap.Modal(
        document.getElementById("editProfileModal")
      );
      editProfileModal.show();
    }

    // Función para cargar los datos del usuario
    function loadUserData() {
      console.log("Iniciando carga de datos del usuario");

      // Primero cargamos los datos de la sesión PHP que estén disponibles en JS como respaldo
      // Esto asegura que siempre tengamos algo para mostrar, incluso si falla la petición AJAX
      const usernameElement = document.querySelector(".dropdown-toggle");
      if (usernameElement) {
        const username = usernameElement.textContent.trim();
        document.getElementById("editUsername").value = username;
        console.log("Nombre de usuario cargado de la UI:", username);
      }

      // Realizar una solicitud AJAX para obtener los datos actuales del usuario
      fetch("api/update_profile.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache", // Evitar problemas de caché
        },
      })
        .then((response) => {
          console.log("Respuesta recibida:", response.status);
          // Clonar la respuesta para poder leerla como texto y luego como JSON
          const clonedResponse = response.clone();

          // Leer la respuesta como texto para depuración
          clonedResponse.text().then((text) => {
            console.log("Respuesta como texto:", text);
            // Intentar analizar manualmente el JSON para ver dónde está el error
            try {
              const jsonData = JSON.parse(text);
              console.log("JSON analizado manualmente:", jsonData);
            } catch (e) {
              console.error("Error al analizar JSON manualmente:", e);
            }
          });

          return response.json();
        })
        .then((data) => {
          console.log("Datos recibidos:", data);

          // Crear array para depuración
          const dataUser = {
            success: data.success,
            username: data.username,
            email: data.email,
          };
          console.log("dataUser:", dataUser);

          if (data.success) {
            // Cargar los datos recibidos en el formulario
            document.getElementById("editUsername").value = data.username;
            document.getElementById("editEmail").value = data.email;

            // La contraseña no se carga por seguridad
            document.getElementById("editPassword").value = "";
            document.getElementById("confirmEditPassword").value = "";

            console.log("Datos cargados correctamente en el formulario");
          } else {
            console.error("Error al cargar datos del usuario:", data.message);
          }
        })
        .catch((error) => {
          console.error("Error en la solicitud:", error);
          // Mensaje de error para el usuario
          alert(
            "No se pudieron cargar tus datos de perfil. Por favor, inténtalo de nuevo."
          );
        });
    }

    // Manejar el guardado de cambios en el perfil
    const saveProfileBtn = document.getElementById("saveProfileChanges");
    if (saveProfileBtn) {
      saveProfileBtn.addEventListener("click", function () {
        console.log("Clic en Guardar cambios");
        saveProfileChanges();
      });
    } else {
      console.error("No se encontró el botón de guardar cambios");
    }

    // Función para guardar los cambios del perfil
    function saveProfileChanges() {
      console.log("Iniciando guardado de cambios");

      // Obtener los valores del formulario
      const username = document.getElementById("editUsername").value;
      const email = document.getElementById("editEmail").value;
      const password = document.getElementById("editPassword").value;
      const confirmPassword = document.getElementById(
        "confirmEditPassword"
      ).value;
      const errorDiv = document.getElementById("editProfileError");

      console.log("Datos a guardar:", {
        username,
        email,
        password: password ? "********" : "No modificada",
      });

      // Validar los datos
      if (!username || !email) {
        showError(
          errorDiv,
          "El nombre de usuario y el correo electrónico son obligatorios"
        );
        return;
      }

      // Si se está cambiando la contraseña, validarla
      if (password) {
        if (password !== confirmPassword) {
          showError(errorDiv, "Las contraseñas no coinciden");
          return;
        }

        if (
          password.length < 12 ||
          !/[a-zA-Z]/.test(password) ||
          !/[0-9]/.test(password)
        ) {
          showError(
            errorDiv,
            "La contraseña debe tener al menos 12 caracteres y contener letras y números"
          );
          return;
        }
      }

      // Todo está validado, realizar la actualización
      // Preparar los datos para enviar
      const formData = new FormData();
      formData.append("nomUsuario", username);
      formData.append("emailUser", email);
      if (password) {
        formData.append("passwordUser", password);
      }

      // Enviar los datos al servidor para actualizar el registro
      fetch("api/update_profile.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          console.log("Respuesta de guardado recibida:", response.status);
          return response.json();
        })
        .then((data) => {
          console.log("Datos de respuesta:", data);

          if (data.success) {
            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(
              document.getElementById("editProfileModal")
            );
            modal.hide();

            // Mostrar mensaje de éxito
            alert("Perfil actualizado correctamente");

            // Recargar la página para mostrar los cambios
            window.location.reload();
          } else {
            window.showError(
              errorDiv,
              data.message || "Error al actualizar el perfil"
            );
          }
        })
        .catch((error) => {
          console.error("Error en el guardado:", error);
          window.showError(
            errorDiv,
            "Error en el servidor. Intente nuevamente."
          );
        });
    }

    // Función para mostrar errores
    // Hacemos la función disponible globalmente
    window.showError = function (element, message) {
      element.textContent = message;
      element.style.display = "block";
      console.error("Error mostrado:", message);
    };
  });

  // Función para eliminar un contacto
  window.deleteContact = function () {
    // Obtener el ID desde el campo oculto
    const deleteContactIdField = document.getElementById("deleteContactId");

    if (!deleteContactIdField) {
      console.error("Campo deleteContactId no encontrado");
      alert("Error: No se pudo encontrar el campo de ID");
      return;
    }

    const contactIdToDelete = deleteContactIdField.value;
    console.log(
      "ID a eliminar:",
      contactIdToDelete,
      "Tipo:",
      typeof contactIdToDelete
    );

    if (!contactIdToDelete) {
      console.error("No se proporcionó ID de contacto para eliminar");
      alert("Error: No se pudo identificar el contacto a eliminar");
      return;
    }

    // Enviar como JSON
    const url = "api/simple_delete.php";
    console.log("Enviando solicitud a:", url);

    fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ idContacto: contactIdToDelete }),
    })
      .then((response) => {
        console.log("Respuesta recibida:", response.status);
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        console.log("Datos recibidos:", data);

        // Cerrar el modal
        const modal = bootstrap.Modal.getInstance(
          document.getElementById("deleteContactModal")
        );
        modal.hide();

        if (data.success) {
          // Recargar los contactos
          window.loadContacts(
            document.getElementById("searchAgcontactos").value
          );

          // Mostrar toast de éxito en rojo
          showErrorToast(data.message || "Contacto eliminado correctamente");
        } else {
          // Mostrar error
          alert(data.message || "Error al eliminar el contacto");
        }
      })
      .catch((error) => {
        console.error("Error al eliminar contacto:", error);
        alert("Error en el servidor: " + error.message);
      });
  };

  // Toast de error (rojo)
  function showErrorToast(message) {
    // Si ya existe un toast, elimínalo
    const oldToast = document.getElementById("successToast");
    if (oldToast) oldToast.remove();

    // Buscar el contenedor de las cards
    const container = document.getElementById("contactsContainer");
    let left = 0;
    let width = 0;
    if (container) {
      const rect = container.getBoundingClientRect();
      left = rect.left + window.scrollX;
      width = rect.width;
    }

    // Crear el HTML del toast
    const toastHtml = `
      <div id="successToast" class="toast align-items-center border-0"
        role="alert" aria-live="assertive" aria-atomic="true"
        style="z-index: 99999; min-width: 220px; max-width: 90vw; position: fixed; bottom: 16px; left: ${
          left + width - 320
        }px; width: 300px; background: rgba(220,53,69,0.98); box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
        <div class="d-flex">
          <div class="toast-body" style="color: #fff;">
            <i class="fas fa-trash-alt me-2" style="color: #fff;"></i> ${message}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML("beforeend", toastHtml);
    const toastEl = document.getElementById("successToast");
    if (window.bootstrap && window.bootstrap.Toast) {
      const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
      toast.show();
    } else {
      // Fallback si Bootstrap Toast no está disponible
      alert(message);
    }
  }

  // Función para actualizar la posición del botón flotante
  function updateFloatingButtonPosition() {
    const button = document.querySelector(".floating-add-btn");
    if (!button) return;

    const container = document.getElementById("contactsContainer");
    if (!container) return;

    const cards = container.querySelectorAll(".contact-card");
    if (cards.length === 0) return;

    // Encontrar la primera card visible en el contenedor
    let firstVisibleCard = null;
    for (const card of cards) {
      const rect = card.getBoundingClientRect();
      const containerRect = container.getBoundingClientRect();
      // Si la card está visible en el contenedor
      if (rect.top >= containerRect.top && rect.bottom > containerRect.top) {
        firstVisibleCard = card;
        break;
      }
    }

    // Si no hay card visible, usar la primera card
    if (!firstVisibleCard) {
      firstVisibleCard = cards[0];
    }

    // Mover el botón antes de la card visible
    if (firstVisibleCard && button.nextSibling !== firstVisibleCard) {
      container.insertBefore(button, firstVisibleCard);
    }
  }

  // Actualizar la posición del botón al hacer scroll en el contenedor
  const contactsContainer = document.getElementById("contactsContainer");
  if (contactsContainer) {
    contactsContainer.addEventListener("scroll", () => {
      requestAnimationFrame(updateFloatingButtonPosition);
    });
  }
  // También al hacer scroll global por si acaso
  window.addEventListener("scroll", () => {
    requestAnimationFrame(updateFloatingButtonPosition);
  });
  window.addEventListener("resize", () => {
    requestAnimationFrame(updateFloatingButtonPosition);
  });
  // Actualizar cuando se carguen los contactos
  const originalLoadContacts = window.loadContacts;
  window.loadContacts = function (searchTerm) {
    return originalLoadContacts(searchTerm).then((data) => {
      setTimeout(() => {
        requestAnimationFrame(updateFloatingButtonPosition);
      }, 100);
      return data;
    });
  };
});

// Función para mostrar un modal de confirmación cuando el usuario intenta cerrar la ventana
let isClosing = false;
let modalActivo = false;

// Evento para detectar movimiento del ratón
document.addEventListener("mousemove", function (e) {
  // Si el ratón está muy cerca de la parte superior de la ventana (donde suele estar el botón de cerrar)
  // Y no estamos en un proceso de cierre controlado
  // Y el modal no está ya activo
  if (e.clientY < 20 && !isClosing && !modalActivo) {
    modalActivo = true; // Marcar el modal como activo ANTES de mostrarlo
    mostrarModalCierreSesion();
  }
});

// Función para mostrar un modal de confirmación
function mostrarModalCierreSesion() {
  // Verificar si ya existe un modal para no duplicarlo
  if (document.getElementById("sesion-modal")) return;

  // Crear el modal usando Bootstrap con un z-index muy alto
  const modalHTML = `
        <div class="modal fade" id="sesion-modal" tabindex="-1" aria-labelledby="sesionModalLabel" 
             aria-hidden="true" data-bs-backdrop="static" style="z-index: 9999;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title" id="sesionModalLabel"><i class="fas fa-exclamation-triangle"></i> Alerta de seguridad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                               onclick="modalActivo = false;"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Atención:</strong> se recomienda cerrar sesión antes de salir.</p>
                        <p>Ya que aunque existen medidas de seguridad podrían no ser compatibles con su navegador, especialmente con Google Chrome.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                               onclick="modalActivo = false;">Continuar trabajando</button>
                        <button type="button" class="btn btn-danger" id="btn-cerrar-sesion">
                            <i class="fas fa-power-off"></i> Cerrar Sesión Ahora
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

  // Agregar el modal al documento
  const modalContainer = document.createElement("div");
  modalContainer.innerHTML = modalHTML;
  document.body.appendChild(modalContainer.firstChild);

  // Mostrar el modal usando la API de Bootstrap
  const modal = new bootstrap.Modal(document.getElementById("sesion-modal"), {
    backdrop: "static", // Evita que se cierre al hacer clic fuera
    keyboard: false, // Evita que se cierre con la tecla Esc
  });
  modal.show();

  // Agregar evento al botón de cerrar sesión
  document
    .getElementById("btn-cerrar-sesion")
    .addEventListener("click", function () {
      // Marcar que estamos en proceso de cierre controlado
      isClosing = true;
      // Redirigir al script correcto de logout
      window.location.href = "logout.php";
    });

  // Asegurarnos de resetear la variable modalActivo cuando se cierre el modal
  document
    .getElementById("sesion-modal")
    .addEventListener("hidden.bs.modal", function () {
      modalActivo = false;
    });
}

// Evento beforeunload para mostrar diálogo del navegador
/*
window.addEventListener("beforeunload", function (event) {
  if (!isClosing) {
    // Intenta abrir la página de confirmación en una nueva ventana pequeña
    const logoutWindow = window.open(
      "",
      "confirm_logout",
      "width=500,height=320,menubar=no,toolbar=no,location=no,status=no,resizable=no"
    );

    // El navegador puede bloquear la apertura de la ventana, pero al menos lo intentamos

    // Texto estándar para diálogo del navegador
    const message = "¿Desea cerrar sesión antes de salir?";
    event.returnValue = message;
    return message;
  }
});
*/
// Función para confirmar cierre de sesión en enlaces de logout
window.confirmarCierreSesion = function (event) {
  if (event) event.preventDefault();
  if (confirm("¿Está seguro que desea cerrar la sesión?")) {
    // Marcar que estamos en proceso de cierre controlado
    isClosing = true;
    window.location.href = "logout.php";
  }
};

// Agregar confirmación a todos los enlaces de logout
document.addEventListener("DOMContentLoaded", function () {
  // Buscar todos los enlaces de logout
  const logoutLinks = document.querySelectorAll('a[href*="logout"]');
  logoutLinks.forEach(function (link) {
    link.addEventListener("click", window.confirmarCierreSesion);
  });
});
