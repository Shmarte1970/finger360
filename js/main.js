/**
 * Funciones principales para la aplicación
 */

document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM cargado completamente");

  // Inicializar componentes según la página actual
  initializeComponents();

  // Verificar si se requiere cambio de contraseña cuando se carga el dashboard
  // Esta sección es crítica para mostrar el modal de cambio de contraseña
  const requirePasswordChange = document.body.dataset.requirePasswordChange;
  console.log("Valor de data-require-password-change:", requirePasswordChange);

  if (requirePasswordChange === "true") {
    console.log("Intentando mostrar modal de cambio de contraseña");

    // Verificamos si el elemento del modal existe
    const changePasswordModal = document.getElementById("changePasswordModal");

    if (changePasswordModal) {
      console.log("Modal encontrado, iniciando...");

      // Asegurarse de que Bootstrap está cargado antes de instanciar el modal
      if (typeof bootstrap !== "undefined") {
        const modal = new bootstrap.Modal(changePasswordModal);
        console.log("Bootstrap Modal instanciado, mostrando...");
        modal.show();
      } else {
        console.error("Bootstrap no está cargado correctamente");
      }
    } else {
      console.error(
        "No se encontró el elemento del modal #changePasswordModal"
      );
    }
  } else {
    console.log(
      "No se requiere cambio de contraseña o el atributo no está establecido correctamente"
    );
  }
});

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
});

/**
 * Inicializa los componentes según la página actual
 */
function initializeComponents() {
  // Detectar qué página estamos viendo
  const currentPath = window.location.pathname;
  console.log("Ruta actual:", currentPath);

  // Inicializar componentes comunes
  initializePasswordToggles();
  initializeEmailValidation();

  // Inicializar componentes específicos de cada página
  if (currentPath.includes("register.php")) {
    initializePasswordValidation();
    initializeEmailExistenceCheck();
  } else if (
    currentPath.includes("index.php") ||
    currentPath.endsWith("/agent/")
  ) {
    initializePasswordRecovery();
  } else if (currentPath.includes("dashboard.php")) {
    // Inicializar funcionalidad para el dashboard, incluido el cambio de contraseña
    initializeChangePassword();
  }
}

/**
 * Inicializa la funcionalidad de cambio de contraseña
 */
function initializeChangePassword() {
  console.log("Inicializando funcionalidad de cambio de contraseña");

  // Manejar validación de contraseñas en el modal de cambio
  const confirmPasswordInput = document.getElementById("confirmPassword");
  const newPasswordInput = document.getElementById("newPassword");
  const saveNewPasswordBtn = document.getElementById("saveNewPassword");

  if (!confirmPasswordInput || !newPasswordInput || !saveNewPasswordBtn) {
    console.error(
      "No se encontraron todos los elementos para el cambio de contraseña"
    );
    return;
  }

  confirmPasswordInput.addEventListener("input", function () {
    validatePasswords(newPasswordInput, confirmPasswordInput);
  });

  newPasswordInput.addEventListener("input", function () {
    validatePasswords(newPasswordInput, confirmPasswordInput);
  });

  // Manejar el envío de la nueva contraseña
  saveNewPasswordBtn.addEventListener("click", function () {
    sendNewPassword(newPasswordInput, confirmPasswordInput);
  });
}

/**
 * Valida que las contraseñas coincidan y cumplan los requisitos
 */
function validatePasswords(newPasswordInput, confirmPasswordInput) {
  const newPassword = newPasswordInput.value;
  const confirmPassword = confirmPasswordInput.value;
  const passwordError = document.getElementById("passwordError");

  if (!passwordError) return;

  // Si confirmPassword está vacío, no mostrar error aún
  if (!confirmPassword) {
    passwordError.style.display = "none";
    return;
  }

  if (newPassword !== confirmPassword) {
    passwordError.textContent = "Las contraseñas no coinciden";
    passwordError.style.display = "block";
  } else {
    passwordError.style.display = "none";
  }
}

/**
 * Envía la solicitud para actualizar la contraseña
 */
function sendNewPassword(newPasswordInput, confirmPasswordInput) {
  const newPassword = newPasswordInput.value;
  const confirmPassword = confirmPasswordInput.value;
  const passwordError = document.getElementById("passwordError");
  const saveNewPasswordBtn = document.getElementById("saveNewPassword");

  if (!passwordError || !saveNewPasswordBtn) return;

  // Validar que las contraseñas coincidan
  if (newPassword !== confirmPassword) {
    passwordError.textContent = "Las contraseñas no coinciden";
    passwordError.style.display = "block";
    return;
  }

  // Validar requisitos de contraseña
  if (
    newPassword.length < 8 ||
    !/[A-Za-z]/.test(newPassword) ||
    !/[0-9]/.test(newPassword)
  ) {
    passwordError.textContent =
      "La contraseña debe tener al menos 8 caracteres y contener letras y números";
    passwordError.style.display = "block";
    return;
  }

  // Mostrar indicador de carga
  saveNewPasswordBtn.disabled = true;
  saveNewPasswordBtn.innerHTML =
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

  // Obtener la URL base del sitio y construir la URL completa para la API
  const baseUrl = window.location.origin;
  const updateUrl = `${baseUrl}/agent/api/update_password.php`;
  console.log("Enviando nueva contraseña a:", updateUrl);

  // Enviar solicitud al servidor
  fetch(updateUrl, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ newPassword: newPassword }),
  })
    .then((response) => {
      console.log("Respuesta recibida, status:", response.status);
      return response.text(); // Cambiar a text() en lugar de json()
    })
    .then((text) => {
      console.log("Respuesta como texto:", text);

      // Intentar parsear como JSON
      try {
        const data = JSON.parse(text);
        console.log("Datos JSON:", data);

        // Restablecer el botón
        saveNewPasswordBtn.disabled = false;
        saveNewPasswordBtn.textContent = "Guardar nueva contraseña";

        if (data.success) {
          // Ocultar el modal y mostrar mensaje de éxito
          if (typeof bootstrap !== "undefined") {
            const modal = bootstrap.Modal.getInstance(
              document.getElementById("changePasswordModal")
            );
            if (modal) {
              modal.hide();

              // Mostrar mensaje de éxito
              alert("Contraseña actualizada correctamente");

              // Recargar la página para reflejar los cambios
              window.location.reload();
            }
          } else {
            alert(
              "Contraseña actualizada correctamente. Por favor, recarga la página."
            );
          }
        } else {
          passwordError.textContent =
            data.message || "Error al actualizar la contraseña";
          passwordError.style.display = "block";
        }
      } catch (error) {
        console.error("Error al parsear JSON:", error);

        // Restablecer el botón
        saveNewPasswordBtn.disabled = false;
        saveNewPasswordBtn.textContent = "Guardar nueva contraseña";

        // Mostrar mensaje de error
        passwordError.textContent =
          "Error en el formato de respuesta del servidor";
        passwordError.style.display = "block";
      }
    })
    .catch((error) => {
      console.error("Error en la solicitud:", error);

      // Restablecer el botón
      saveNewPasswordBtn.disabled = false;
      saveNewPasswordBtn.textContent = "Guardar nueva contraseña";

      // Mostrar mensaje de error
      passwordError.textContent =
        "Error de conexión. Por favor, inténtalo de nuevo.";
      passwordError.style.display = "block";
    });
}

/**
 * Inicializa la validación de contraseña
 */
function initializePasswordValidation() {
  const passwordInput = document.getElementById("password");
  const confirmPasswordInput = document.getElementById("confirm_password");

  if (!passwordInput) return;

  // Crear elementos para mostrar el estado de validación
  const validationContainer = document.createElement("div");
  validationContainer.className = "password-requirements mt-2";
  passwordInput.parentNode.parentNode.appendChild(validationContainer);

  // Requisitos de contraseña
  const requirements = [
    { id: "length", text: "Mínimo 12 caracteres", regex: /.{12,}/ },
    { id: "symbol", text: "Al menos un símbolo (@ o #)", regex: /[@#]/ },
    { id: "uppercase", text: "Al menos una letra mayúscula", regex: /[A-Z]/ },
    { id: "number", text: "Al menos un número", regex: /[0-9]/ },
  ];

  // Crear elementos para cada requisito
  requirements.forEach((req) => {
    const reqElement = document.createElement("div");
    reqElement.className = "requirement";
    reqElement.id = `req-${req.id}`;
    reqElement.innerHTML = `<small><i class="fas fa-times text-danger"></i> ${req.text}</small>`;
    validationContainer.appendChild(reqElement);
  });

  // Función para validar la contraseña
  function validatePassword() {
    const password = passwordInput.value;

    requirements.forEach((req) => {
      const reqElement = document.getElementById(`req-${req.id}`);
      if (req.regex.test(password)) {
        reqElement.innerHTML = `<small><i class="fas fa-check text-success"></i> ${req.text}</small>`;
        reqElement.classList.add("valid");
        reqElement.classList.remove("invalid");
      } else {
        reqElement.innerHTML = `<small><i class="fas fa-times text-danger"></i> ${req.text}</small>`;
        reqElement.classList.add("invalid");
        reqElement.classList.remove("valid");
      }
    });
  }

  // Función para validar que las contraseñas coincidan
  function validatePasswordMatch() {
    if (!confirmPasswordInput) return;

    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    if (confirmPassword === "") {
      confirmPasswordInput.setCustomValidity("");
      return;
    }

    if (password === confirmPassword) {
      confirmPasswordInput.setCustomValidity("");
    } else {
      confirmPasswordInput.setCustomValidity("Las contraseñas no coinciden");
    }
  }

  // Eventos para validar en tiempo real
  passwordInput.addEventListener("input", validatePassword);
  passwordInput.addEventListener("input", validatePasswordMatch);

  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener("input", validatePasswordMatch);
  }

  // Validar al cargar la página
  validatePassword();
}

/**
 * Inicializa los botones para mostrar/ocultar contraseña
 */
function initializePasswordToggles() {
  const passwordInputs = document.querySelectorAll('input[type="password"]');

  passwordInputs.forEach((input) => {
    const toggleBtn = document.getElementById(
      `toggle${input.id.charAt(0).toUpperCase() + input.id.slice(1)}`
    );

    if (toggleBtn) {
      toggleBtn.addEventListener("click", function () {
        const type =
          input.getAttribute("type") === "password" ? "text" : "password";
        input.setAttribute("type", type);

        // Cambiar el icono
        const icon = toggleBtn.querySelector("i");
        if (type === "password") {
          icon.classList.remove("fa-eye-slash");
          icon.classList.add("fa-eye");
        } else {
          icon.classList.remove("fa-eye");
          icon.classList.add("fa-eye-slash");
        }
      });
    }
  });
}

/**
 * Inicializa la validación de correo electrónico
 */
function initializeEmailValidation() {
  const emailInput = document.getElementById("email");

  if (!emailInput) return;

  function validateEmail() {
    const email = emailInput.value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (email === "") {
      emailInput.classList.remove("is-invalid");
      return;
    }

    if (emailRegex.test(email)) {
      emailInput.classList.remove("is-invalid");
      emailInput.classList.add("is-valid");
    } else {
      emailInput.classList.remove("is-valid");
      emailInput.classList.add("is-invalid");
    }
  }

  // Eventos para validar en tiempo real
  emailInput.addEventListener("input", validateEmail);

  // Validar al cargar la página
  validateEmail();
}

/**
 * Inicializa la funcionalidad de recuperación de contraseña
 */
function initializePasswordRecovery() {
  const recoveryEmailInput = document.getElementById("recovery-email");
  const sendRecoveryBtn = document.getElementById("send-recovery-btn");
  const recoveryMessage = document.getElementById("recovery-message");
  const forgotPasswordModal = document.getElementById("forgotPasswordModal");
  const forgotPasswordForm = document.getElementById("forgot-password-form");

  if (!recoveryEmailInput || !sendRecoveryBtn || !recoveryMessage) {
    console.error(
      "No se encontraron todos los elementos necesarios para la recuperación de contraseña"
    );
    return;
  }

  console.log("Inicializando funcionalidad de recuperación de contraseña");

  // Función para validar el formato del email
  function validateRecoveryEmail() {
    const email = recoveryEmailInput.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (email === "") {
      recoveryEmailInput.classList.remove("is-valid", "is-invalid");
      return false;
    }

    if (emailRegex.test(email)) {
      recoveryEmailInput.classList.remove("is-invalid");
      recoveryEmailInput.classList.add("is-valid");
      return true;
    } else {
      recoveryEmailInput.classList.remove("is-valid");
      recoveryEmailInput.classList.add("is-invalid");
      return false;
    }
  }

  // Función para mostrar mensajes
  function showRecoveryMessage(message, type) {
    recoveryMessage.textContent = message;
    recoveryMessage.className = `alert alert-${type}`;
    recoveryMessage.classList.remove("d-none");
  }

  // Función para enviar la solicitud de recuperación de contraseña
  async function sendRecoveryRequest() {
    console.log("Iniciando solicitud de recuperación de contraseña");

    // Limpiar mensaje anterior
    recoveryMessage.classList.add("d-none");

    // Validar el formato del email
    if (!validateRecoveryEmail()) {
      console.log("Email inválido");
      showRecoveryMessage(
        "Por favor, introduce un correo electrónico válido.",
        "danger"
      );
      recoveryEmailInput.focus();
      return;
    }

    const email = recoveryEmailInput.value.trim();

    // Deshabilitar botón y mostrar estado de carga
    sendRecoveryBtn.disabled = true;
    sendRecoveryBtn.innerHTML =
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
    showRecoveryMessage("Procesando solicitud...", "info");

    try {
      // Obtener la URL base del sitio
      const baseUrl = window.location.origin;
      const apiUrl = `${baseUrl}/agent/api/recover_password.php`;

      console.log("Enviando solicitud a:", apiUrl);
      console.log("Datos a enviar:", { email });

      // Enviar solicitud al servidor
      const response = await fetch(apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({ email }),
      });

      console.log("Respuesta recibida, status:", response.status);

      // Usar text() en lugar de json() para poder inspeccionar respuestas incorrectas
      const text = await response.text();
      console.log("Respuesta como texto:", text);

      // Intentar parsear como JSON
      try {
        const data = JSON.parse(text);
        console.log("Datos JSON:", data);

        // Determinar el tipo de alerta según el estado
        let alertType = "danger";
        if (data.status === "success" || data.success === true) {
          alertType = "success";
          // Deshabilitar el campo de email si la operación fue exitosa
          recoveryEmailInput.disabled = true;

          // Cerrar el modal después de unos segundos en caso de éxito
          setTimeout(() => {
            if (typeof bootstrap !== "undefined") {
              const modal = bootstrap.Modal.getInstance(forgotPasswordModal);
              if (modal) {
                modal.hide();

                // Restablecer el estado del formulario después de cerrar
                setTimeout(() => {
                  recoveryEmailInput.disabled = false;
                  recoveryEmailInput.value = "";
                  recoveryEmailInput.classList.remove("is-valid", "is-invalid");
                  recoveryMessage.classList.add("d-none");
                }, 500);
              }
            }
          }, 5000);
        } else if (data.status === "warning") {
          alertType = "warning";
        }

        showRecoveryMessage(data.message || "Solicitud procesada", alertType);
      } catch (e) {
        console.error("Error al parsear JSON:", e);
        showRecoveryMessage(
          "Error al procesar la respuesta del servidor",
          "danger"
        );
      }
    } catch (error) {
      console.error("Error en la solicitud:", error);
      showRecoveryMessage(
        "Error al comunicarse con el servidor. Por favor, inténtalo más tarde.",
        "danger"
      );
    } finally {
      // Restaurar botón
      sendRecoveryBtn.disabled = false;
      sendRecoveryBtn.textContent = "Enviar";
    }
  }

  // Añadir evento al formulario para validación
  if (forgotPasswordForm) {
    forgotPasswordForm.addEventListener("submit", function (e) {
      e.preventDefault();
      sendRecoveryRequest();
    });
  }

  // Añadir evento al botón
  sendRecoveryBtn.addEventListener("click", function (e) {
    e.preventDefault();
    sendRecoveryRequest();
  });

  // También permitir enviar con Enter
  recoveryEmailInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      sendRecoveryRequest();
    }
  });

  // Validar email en tiempo real
  recoveryEmailInput.addEventListener("input", validateRecoveryEmail);

  // Limpiar el formulario cuando se cierra el modal
  if (forgotPasswordModal) {
    forgotPasswordModal.addEventListener("hidden.bs.modal", function () {
      recoveryEmailInput.value = "";
      recoveryEmailInput.disabled = false;
      recoveryEmailInput.classList.remove("is-valid", "is-invalid");
      recoveryMessage.classList.add("d-none");
      sendRecoveryBtn.disabled = false;
      sendRecoveryBtn.textContent = "Enviar";
    });
  }

  console.log(
    "Eventos de recuperación de contraseña registrados correctamente"
  );
}

/**
 * Inicializa la verificación de existencia de correo electrónico
 */
function initializeEmailExistenceCheck() {
  const form = document.querySelector("form");
  const emailInput = document.getElementById("email");

  if (!form || !emailInput) return;

  // Variable para almacenar si el correo ya existe
  let emailExists = false;

  // Función para verificar si el correo ya existe
  async function checkEmailExists(email) {
    try {
      const response = await fetch("api/check_email.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ email: email }),
      });

      const data = await response.json();

      if (data.exists) {
        // El correo ya existe
        emailExists = true;
        emailInput.classList.remove("is-valid");
        emailInput.classList.add("is-invalid");

        // Actualizar mensaje de error
        const feedbackElement = document.getElementById("email-feedback");
        if (feedbackElement) {
          feedbackElement.textContent =
            "El correo electrónico ya está registrado";
        }
      } else {
        // El correo no existe
        emailExists = false;
      }

      return data.exists;
    } catch (error) {
      console.error("Error al verificar el correo:", error);
      return false;
    }
  }

  // Verificar el correo cuando pierde el foco
  emailInput.addEventListener("blur", function () {
    const email = emailInput.value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (email && emailRegex.test(email)) {
      checkEmailExists(email);
    }
  });

  // Verificar antes de enviar el formulario
  form.addEventListener("submit", async function (event) {
    // Prevenir el envío del formulario
    event.preventDefault();

    const email = emailInput.value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (email && emailRegex.test(email)) {
      // Verificar si el correo ya existe
      const exists = await checkEmailExists(email);

      if (exists) {
        // Mostrar mensaje de error
        alert("No se puede registrar con este correo. Ya está en uso.");
        return;
      }

      // Si el correo no existe, enviar el formulario
      form.submit();
    } else {
      // Validar el formulario normalmente
      form.reportValidity();
    }
  });
}

function initPasswordToggles() {
  document.querySelectorAll(".toggle-password").forEach((button) => {
    button.onclick = function () {
      const targetId = this.getAttribute("data-target");
      const input = document.getElementById(targetId);
      if (!input) return;
      if (input.type === "password") {
        input.type = "text";
        this.innerHTML = '<i class="fa fa-eye-slash"></i>';
      } else {
        input.type = "password";
        this.innerHTML = '<i class="fa fa-eye"></i>';
      }
    };
  });
}

document.addEventListener("DOMContentLoaded", function () {
  initPasswordToggles();
  // Si usas Bootstrap 5, puedes volver a asociar los toggles cada vez que se muestre el modal:
  var changePasswordModal = document.getElementById("changePasswordModal");
  if (changePasswordModal) {
    changePasswordModal.addEventListener("shown.bs.modal", function () {
      initPasswordToggles();
    });
  }
});
