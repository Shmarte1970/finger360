// Función para eliminar un contacto
window.deleteContact = function () {
  const contactId = document.getElementById("deleteContactId").value;

  if (!contactId) {
    console.error("No se proporcionó ID de contacto para eliminar");
    alert("Error: No se pudo identificar el contacto a eliminar");
    return;
  }

  console.log("Eliminando contacto con ID:", contactId);

  // Usar el endpoint específico para eliminar contactos
  fetch(`api/delete_contact.php?id=${contactId}`, {
    method: "POST",
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
        window.loadContacts(document.getElementById("searchAgcontactos").value);

        // Mostrar mensaje de éxito
        alert(data.message || "Contacto eliminado correctamente");
      } else {
        // Mostrar error
        alert(data.message || "Error al eliminar el contacto");
      }
    })
    .catch((error) => {
      console.error("Error al eliminar contacto:", error);
      alert("Error en el servidor. Intente nuevamente.");
    });
};
