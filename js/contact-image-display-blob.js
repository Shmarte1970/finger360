/**
 * Script para mostrar las imágenes de contactos usando el enfoque de BLOB
 * pero con carga diferida para evitar problemas de codificación JSON
 */
document.addEventListener("DOMContentLoaded", function () {
  // Función para actualizar las imágenes en las tarjetas de contacto
  function updateContactImages() {
    // Buscar todas las tarjetas de contacto
    const contactCards = document.querySelectorAll(".contact-card");

    contactCards.forEach((card) => {
      const contactId = card.getAttribute("data-contact-id");
      if (!contactId) return;

      // Buscar el contenedor de imágenes
      const imageContainer = card.querySelector(".contact-images");
      if (!imageContainer) return;

      // Limpiar imágenes existentes
      const existingImages = imageContainer.querySelectorAll("img");
      existingImages.forEach((img) => img.remove());

      // Agregar imágenes para cada campo
      for (let i = 1; i <= 3; i++) {
        const imageField = `foto${i}`;

        // Verificar si el contacto tiene esta imagen
        const hasImage = card.getAttribute(`data-has-${imageField}`) === "true";

        if (hasImage) {
          // Crear elemento de imagen
          const img = document.createElement("img");
          img.className = "contact-image";
          img.src = `api/get_contact_image_blob.php?id=${contactId}&field=${imageField}`;
          img.alt = `Foto ${i} del contacto`;
          img.loading = "lazy"; // Carga diferida

          // Agregar al contenedor
          imageContainer.appendChild(img);
        }
      }
    });
  }

  // Ejecutar cuando se carguen los contactos
  if (typeof window.loadContacts === "function") {
    const originalLoadContacts = window.loadContacts;

    window.loadContacts = function (searchTerm) {
      return originalLoadContacts(searchTerm).then(() => {
        // Actualizar imágenes después de cargar los contactos
        setTimeout(updateContactImages, 500);
      });
    };
  }
});
