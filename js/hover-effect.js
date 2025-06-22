/**
 * Script para mejorar el efecto hover en las imágenes
 */
document.addEventListener("DOMContentLoaded", function () {
  // Función para aplicar el efecto hover a las imágenes
  function applyHoverEffect() {
    // Seleccionar todas las imágenes con la clase hover-zoom-image
    const images = document.querySelectorAll(".hover-zoom-image");

    images.forEach((img) => {
      // Añadir evento mouseenter
      img.addEventListener("mouseenter", function () {
        // Asegurarse de que esta imagen tenga el z-index más alto
        document.querySelectorAll(".hover-zoom-image").forEach((otherImg) => {
          if (otherImg !== img) {
            otherImg.style.zIndex = "1";
          }
        });
        this.style.zIndex = "100";
      });
    });
  }

  // Aplicar el efecto inicialmente
  applyHoverEffect();

  // Observar cambios en el DOM para aplicar el efecto a nuevas imágenes
  const observer = new MutationObserver(function (mutations) {
    applyHoverEffect();
  });

  // Observar el contenedor de contactos
  const contactsContainer = document.getElementById("contactsContainer");
  if (contactsContainer) {
    observer.observe(contactsContainer, {
      childList: true,
      subtree: true,
    });
  }
});
