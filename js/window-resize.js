/**
 * Script para ajustar automáticamente el tamaño de la ventana del navegador
 * al tamaño óptimo de la aplicación y manejar el comportamiento responsivo.
 */
document.addEventListener("DOMContentLoaded", function () {
  // Detectar tipo de dispositivo
  const isMobile =
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    );
  const isTablet = /iPad|Android(?!.*Mobile)/i.test(navigator.userAgent);

  // Detectar modelos específicos (Samsung Galaxy Ultra, iPhone 11 Pro)
  const isSamsungGalaxyUltra = /SM-G99[8-9]|SM-S9[0-9]{2}/i.test(
    navigator.userAgent
  );
  const isIPhone11Pro = /iPhone1[1-3]/i.test(navigator.userAgent);

  // Ajustar el tamaño de la ventana del navegador al tamaño de la aplicación
  function adjustWindowSize() {
    const appContainer = document.querySelector(".app-container");
    if (appContainer && !isMobile && !isTablet) {
      const appWidth = Math.min(1200, window.innerWidth);
      const appHeight = Math.max(800, appContainer.offsetHeight);

      // Solo ajustar si la ventana es más grande que la aplicación y no estamos en móvil
      if (window.outerWidth > appWidth + 100) {
        window.resizeTo(appWidth + 50, appHeight + 100);
      }
    }
  }

  // Aplicar ajustes específicos para dispositivos móviles
  function applyMobileOptimizations() {
    if (isMobile) {
      // Ajustes generales para móviles
      document.body.classList.add("mobile-device");

      // Ajustes específicos para Samsung Galaxy Ultra
      if (isSamsungGalaxyUltra) {
        document.body.classList.add("samsung-galaxy-ultra");
      }

      // Ajustes específicos para iPhone 11 Pro
      if (isIPhone11Pro) {
        document.body.classList.add("iphone-11-pro");
      }
    }

    // Ajustes para tablets
    if (isTablet) {
      document.body.classList.add("tablet-device");
    }
  }

  // Ajustar el tamaño después de que todo el contenido se haya cargado
  window.addEventListener("load", function () {
    adjustWindowSize();
    applyMobileOptimizations();
  });

  // Manejar cambios de orientación en dispositivos móviles
  window.addEventListener("orientationchange", function () {
    setTimeout(function () {
      // Dar tiempo a que el navegador actualice las dimensiones
      if (isMobile || isTablet) {
        // Forzar actualización del layout
        document.body.style.display = "none";
        document.body.offsetHeight; // Forzar reflow
        document.body.style.display = "";
      }
    }, 100);
  });
});
