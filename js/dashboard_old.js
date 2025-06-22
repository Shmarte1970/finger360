// Funciones para gestionar contactos (definidas globalmente)
function searchAgcontactos(term) {
  // Cargar contactos con el término de búsqueda
  loadContacts(term);
}

function loadContacts(searchTerm = "") {
  console.log("Cargando contactos...");

  // Mostrar indicador de carga
  document.getElementById("loadingContacts").classList.remove("d-none");
  document.getElementById("noContactsMessage").classList.add("d-none");

  // Construir la URL con el término de búsqueda si existe
  let url = "api/get_contacts.php";
  if (searchTerm) {
    url += `?search=${encodeURIComponent(searchTerm)}`;
  }

  // Realizar la solicitud AJAX
  fetch(url)
    .then((response) => {
      console.log("Respuesta recibida:", response.status);
      return response.json();
    })
    .then((data) => {
      console.log("Datos recibidos:", data);

      // Ocultar indicador de carga
      document.getElementById("loadingContacts").classList.add("d-none");

      // Obtener el contenedor de contactos
      const contactsContainer = document.getElementById("contactsContainer");

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
        data.contacts.forEach((contact) => {
          contactsContainer.appendChild(createContactCard(contact));
        });
      } else {
        // Mostrar mensaje de no hay contactos
        document.getElementById("noContactsMessage").classList.remove("d-none");
      }
    })
    .catch((error) => {
      console.error("Error al cargar contactos:", error);
      document.getElementById("loadingContacts").classList.add("d-none");
      document.getElementById("noContactsMessage").classList.remove("d-none");
      document.getElementById("noContactsMessage").innerHTML = `
        <p class="text-danger">Error al cargar los contactos. Por favor, intenta de nuevo.</p>
        <button class="btn btn-outline-primary mt-2" onclick="loadContacts()">
          <i class="fas fa-sync-alt"></i> Reintentar
        </button>
      `;
    });
}

function createContactCard(contact) {
  const col = document.createElement("div");
  col.className = "mb-3"; // Quitamos la clase col-12 para que la tarjeta use su propio ancho

  // Determinar el icono según el sexo
  let genderIcon = "fa-user";
  let genderClass = "text-primary";

  // Usar el valor exacto del campo sexo
  let genderText = contact.sexo || ""; // Mostrar el valor exacto o cadena vacía si es null

  if (contact.sexo === "M") {
    genderIcon = "fa-male";
    genderClass = "text-primary";
  } else if (contact.sexo === "F") {
    genderIcon = "fa-female";
    genderClass = "text-danger";
  }

  col.innerHTML = `
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 text-truncate" style="max-width: 80%;">
          <i class="fas ${genderIcon} ${genderClass} me-2"></i>
          ${contact.nomContacto}
        </h5>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
            <i class="fas fa-ellipsis-v"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item edit-contact" href="#" data-id="${
              contact.id
            }">
              <i class="fas fa-edit me-2"></i> Editar
            </a></li>
            <li><a class="dropdown-item delete-contact" href="#" data-id="${
              contact.id
            }">
              <i class="fas fa-trash-alt me-2"></i> Eliminar
            </a></li>
          </ul>
        </div>
      </div>
      <div class="card-body">
        <div class="contact-info">
          <p class="card-text">
            <i class="fas fa-phone me-2 text-success"></i> <strong>Teléfono:</strong> ${
              contact.telefonoContacto
            }
          </p>
          
          ${
            contact.adress
              ? `
            <p class="card-text">
              <i class="fas fa-map-marker-alt me-2 text-danger"></i> <strong>Dirección:</strong> ${contact.adress}
            </p>
          `
              : '<p class="card-text text-muted"><i class="fas fa-map-marker-alt me-2 text-muted"></i> <strong>Dirección:</strong> Sin dirección</p>'
          }
          
          <p class="card-text">
            <i class="fas ${genderIcon} me-2 ${genderClass}"></i> <strong>Sexo:</strong> ${
    contact.sexo || "No especificado"
  }
          </p>
        </div>
      </div>
    </div>
  `;

  // Añadir eventos a los botones de editar y eliminar
  col.querySelector(".edit-contact").addEventListener("click", function (e) {
    e.preventDefault();
    openContactModal(contact);
  });

  col.querySelector(".delete-contact").addEventListener("click", function (e) {
    e.preventDefault();
    openDeleteModal(contact.id);
  });

  return col;
}

function openContactModal(contact = null) {
  // Obtener el modal y el formulario
  const modal = document.getElementById("contactModal");
  const form = document.getElementById("contactForm");
  const modalTitle = modal.querySelector(".modal-title");

  // Limpiar el formulario
  form.reset();
  document.getElementById("contactFormError").style.display = "none";

  if (contact) {
    // Modo edición
    modalTitle.textContent = "Editar Contacto";
    document.getElementById("contactId").value = contact.id;
    document.getElementById("nomContacto").value = contact.nomContacto;
    document.getElementById("telefonoContacto").value =
      contact.telefonoContacto;
    document.getElementById("adress").value = contact.adress || "";
    document.getElementById("sexo").value = contact.sexo || "";
  } else {
    // Modo creación
    modalTitle.textContent = "Nuevo Contacto";
    document.getElementById("contactId").value = "";
  }

  // Mostrar el modal
  const contactModal = new bootstrap.Modal(modal);
  contactModal.show();
}

function openDeleteModal(contactId) {
  document.getElementById("deleteContactId").value = contactId;
  const deleteModal = new bootstrap.Modal(
    document.getElementById("deleteContactModal")
  );
  deleteModal.show();
}

function saveContact() {
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

  // Si hay ID, es una actualización
  let url = "api/manage_contact.php";
  let method = "POST";

  if (contactId) {
    contactData.id = contactId;
    contactData._method = "PUT"; // Para simular PUT en servidores que no lo soportan
  }

  // Enviar los datos al servidor
  fetch(url, {
    method: method,
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(contactData),
  })
    .then((response) => {
      console.log("Respuesta recibida:", response.status);
      return response.json();
    })
    .then((data) => {
      console.log("Datos recibidos:", data);

      if (data.success) {
        // Cerrar el modal
        const modal = bootstrap.Modal.getInstance(
          document.getElementById("contactModal")
        );
        modal.hide();

        // Recargar los contactos
        loadContacts(document.getElementById("searchAgcontactos").value);

        // Mostrar mensaje de éxito
        alert(data.message);
      } else {
        // Mostrar error
        errorDiv.textContent = data.message || "Error al guardar el contacto";
        errorDiv.style.display = "block";
      }
    })
    .catch((error) => {
      console.error("Error al guardar contacto:", error);
      errorDiv.textContent = "Error en el servidor. Intente nuevamente.";
      errorDiv.style.display = "block";
    });
}

function deleteContact() {
  const contactId = document.getElementById("deleteContactId").value;

  if (!contactId) {
    console.error("No se proporcionó ID de contacto para eliminar");
    return;
  }

  console.log("Eliminando contacto con ID:", contactId);

  // Enviar solicitud de eliminación
  fetch(`api/manage_contact.php?id=${contactId}`, {
    method: "DELETE",
  })
    .then((response) => {
      console.log("Respuesta recibida:", response.status);
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
        loadContacts(document.getElementById("searchAgcontactos").value);

        // Mostrar mensaje de éxito
        alert(data.message);
      } else {
        // Mostrar error
        alert(data.message || "Error al eliminar el contacto");
      }
    })
    .catch((error) => {
      console.error("Error al eliminar contacto:", error);
      alert("Error en el servidor. Intente nuevamente.");
    });
}

// Función para mostrar errores
function showError(element, message) {
  element.textContent = message;
  element.style.display = "block";
  console.error("Error mostrado:", message);
}

// Evento DOMContentLoaded para inicializar la página
document.addEventListener("DOMContentLoaded", function () {
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

  // Funcionalidad de búsqueda para la tabla agcontactos
  const searchInput = document.getElementById("searchAgcontactos");
  const searchButton = document.getElementById("btnSearchAgcontactos");

  if (searchButton && searchInput) {
    searchButton.addEventListener("click", function () {
      const searchTerm = searchInput.value.toLowerCase();
      searchAgcontactos(searchTerm);
    });

    // También permitir búsqueda al presionar Enter
    searchInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        const searchTerm = searchInput.value.toLowerCase();
        searchAgcontactos(searchTerm);
      }
    });
  }

  // Funcionalidad para el cambio de contraseña desde Settings
  const saveSettingsPasswordBtn = document.getElementById(
    "saveSettingsPassword"
  );
  if (saveSettingsPasswordBtn) {
    saveSettingsPasswordBtn.addEventListener("click", function () {
      const currentPassword = document.getElementById("currentPassword").value;
      const newPassword = document.getElementById("settingsNewPassword").value;
      const confirmPassword = document.getElementById(
        "settingsConfirmPassword"
      ).value;
      const errorDiv = document.getElementById("settingsPasswordError");

      // Validar que las contraseñas coincidan
      if (newPassword !== confirmPassword) {
        errorDiv.textContent = "Las contraseñas no coinciden";
        errorDiv.style.display = "block";
        return;
      }

      // Validar longitud y complejidad de la contraseña
      if (
        newPassword.length < 12 ||
        !/[a-zA-Z]/.test(newPassword) ||
        !/[0-9]/.test(newPassword)
      ) {
        errorDiv.textContent =
          "La contraseña debe tener al menos 12 caracteres y contener letras y números";
        errorDiv.style.display = "block";
        return;
      }

      // Aquí iría el código para enviar la solicitud al servidor para cambiar la contraseña
      // Por ejemplo:
      /*
          fetch('change_password.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                  currentPassword: currentPassword,
                  newPassword: newPassword
              })
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  // Cerrar el modal y mostrar mensaje de éxito
                  const modal = bootstrap.Modal.getInstance(document.getElementById('settingsPasswordModal'));
                  modal.hide();
                  alert('Contraseña actualizada con éxito');
              } else {
                  // Mostrar error
                  errorDiv.textContent = data.message || 'Error al cambiar la contraseña';
                  errorDiv.style.display = 'block';
              }
          })
          .catch(error => {
              console.error('Error:', error);
              errorDiv.textContent = 'Error en el servidor. Intente nuevamente.';
              errorDiv.style.display = 'block';
          });
          */

      // Código temporal para demostración
      alert("Funcionalidad de cambio de contraseña en desarrollo");
      const modal = bootstrap.Modal.getInstance(
        document.getElementById("settingsPasswordModal")
      );
      modal.hide();
    });
  }

  // Funcionalidad para abrir el modal de edición de perfil
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

    // Cargar datos actuales del usuario
    loadUserData();

    // Mostrar el modal
    const modal = new bootstrap.Modal(
      document.getElementById("editProfileModal")
    );
    modal.show();
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
          showError(errorDiv, data.message || "Error al actualizar el perfil");
        }
      })
      .catch((error) => {
        console.error("Error en el guardado:", error);
        showError(errorDiv, "Error en el servidor. Intente nuevamente.");
      });
  }

  // Cargar contactos al iniciar la página
  if (document.getElementById("contactsContainer")) {
    loadContacts();
  }

  // Añadir event listeners para los botones de contactos
  const btnAddContact = document.getElementById("btnAddContact");
  if (btnAddContact) {
    btnAddContact.addEventListener("click", function () {
      openContactModal();
    });
  }

  const saveContactBtn = document.getElementById("saveContact");
  if (saveContactBtn) {
    saveContactBtn.addEventListener("click", saveContact);
  }

  const confirmDeleteBtn = document.getElementById("confirmDeleteContact");
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", deleteContact);
  }
});
