/**
 * Manejador de imágenes para la aplicación de agentes
 * Gestiona la validación, redimensionamiento y carga de imágenes
 */

// Configuración para las imágenes
const IMAGE_CONFIG = {
  maxSizeKB: 100, // Tamaño máximo en KB
  maxWidth: 1024, // Ancho máximo en píxeles
  maxHeight: 768, // Alto máximo en píxeles
  format: "image/jpeg", // Formato de imagen permitido
  quality: 0.8, // Calidad de compresión (0-1)
  acceptedExtensions: ["jpg", "jpeg"], // Extensiones permitidas
};

// Clase para manejar las imágenes
class ImageHandler {
  constructor() {
    this.initializeImagePreviews();
  }

  /**
   * Inicializa los eventos para manejar imágenes
   */
  initializeImagePreviews() {
    // Configurar eventos para los inputs de imágenes
    for (let i = 1; i <= 3; i++) {
      const inputId = `foto${i}`;

      const inputElement = document.getElementById(inputId);
      if (inputElement) {
        inputElement.addEventListener("change", (event) => {
          this.handleImageSelection(event, inputId);
        });
      }
    }
  }

  /**
   * Maneja la selección de una imagen
   * @param {Event} event - Evento de cambio del input file
   * @param {string} inputId - ID del elemento input
   */
  handleImageSelection(event, inputId) {
    const file = event.target.files[0];

    if (!file) {
      return;
    }

    // Validar el tipo de archivo
    if (!this.validateFileType(file)) {
      this.showError(`Error: Solo se permiten imágenes en formato JPG/JPEG.`);
      event.target.value = ""; // Limpiar el input
      return;
    }

    // Validar el tamaño del archivo
    if (!this.validateFileSize(file)) {
      // Preguntar al usuario si desea redimensionar
      const fileSizeKB = Math.round(file.size / 1024);
      if (
        confirm(
          `La imagen tiene un tamaño de ${fileSizeKB}KB, que excede el límite máximo de ${IMAGE_CONFIG.maxSizeKB}KB.\n\n¿Desea redimensionar y comprimir la imagen automáticamente para cumplir con los requisitos?`
        )
      ) {
        this.resizeImage(file, event.target);
      } else {
        event.target.value = ""; // Limpiar el input
      }
      return;
    }

    // Verificar dimensiones
    this.checkImageDimensions(file, event.target);
  }

  /**
   * Valida el tipo de archivo
   * @param {File} file - Archivo a validar
   * @returns {boolean} - True si es válido, false si no
   */
  validateFileType(file) {
    const fileName = file.name.toLowerCase();
    const fileExtension = fileName.split(".").pop();
    return IMAGE_CONFIG.acceptedExtensions.includes(fileExtension);
  }

  /**
   * Valida el tamaño del archivo
   * @param {File} file - Archivo a validar
   * @returns {boolean} - True si es válido, false si no
   */
  validateFileSize(file) {
    const fileSizeKB = file.size / 1024;
    return fileSizeKB <= IMAGE_CONFIG.maxSizeKB;
  }

  /**
   * Verifica las dimensiones de la imagen
   * @param {File} file - Archivo de imagen
   * @param {HTMLInputElement} inputElement - Elemento input file
   */
  checkImageDimensions(file, inputElement) {
    const img = new Image();
    const objectURL = URL.createObjectURL(file);

    img.onload = () => {
      URL.revokeObjectURL(objectURL);

      if (
        img.width > IMAGE_CONFIG.maxWidth ||
        img.height > IMAGE_CONFIG.maxHeight
      ) {
        // Preguntar al usuario si desea redimensionar
        if (
          confirm(
            `La imagen tiene dimensiones de ${img.width}x${img.height} píxeles, que exceden el límite máximo de ${IMAGE_CONFIG.maxWidth}x${IMAGE_CONFIG.maxHeight} píxeles.\n\n¿Desea redimensionar la imagen automáticamente para cumplir con los requisitos?`
          )
        ) {
          this.resizeImage(file, inputElement);
        } else {
          inputElement.value = ""; // Limpiar el input
        }
      }
    };

    img.src = objectURL;
  }

  /**
   * Redimensiona una imagen
   * @param {File} file - Archivo de imagen
   * @param {HTMLInputElement} inputElement - Elemento input file
   */
  resizeImage(file, inputElement) {
    const img = new Image();
    const reader = new FileReader();

    reader.onload = (e) => {
      img.onload = () => {
        // Calcular nuevas dimensiones manteniendo la proporción
        let newWidth = img.width;
        let newHeight = img.height;

        if (newWidth > IMAGE_CONFIG.maxWidth) {
          newHeight = (IMAGE_CONFIG.maxWidth / newWidth) * newHeight;
          newWidth = IMAGE_CONFIG.maxWidth;
        }

        if (newHeight > IMAGE_CONFIG.maxHeight) {
          newWidth = (IMAGE_CONFIG.maxHeight / newHeight) * newWidth;
          newHeight = IMAGE_CONFIG.maxHeight;
        }

        // Crear canvas para redimensionar
        const canvas = document.createElement("canvas");
        canvas.width = newWidth;
        canvas.height = newHeight;

        // Dibujar imagen redimensionada
        const ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0, newWidth, newHeight);

        // Convertir a JPEG con la calidad especificada
        canvas.toBlob(
          (blob) => {
            // Crear un nuevo archivo a partir del blob
            const resizedFile = new File([blob], file.name, {
              type: "image/jpeg",
              lastModified: new Date().getTime(),
            });

            // Verificar si cumple con el tamaño máximo
            if (resizedFile.size / 1024 > IMAGE_CONFIG.maxSizeKB) {
              // Intentar con menor calidad
              this.resizeWithLowerQuality(
                img,
                newWidth,
                newHeight,
                file.name,
                inputElement
              );
            } else {
              // Reemplazar el archivo en el input (esto es un hack, ya que no se puede modificar directamente)
              // Guardamos el blob en una variable global para accederlo después
              window[`resizedImage_${inputElement.id}`] = resizedFile;

              // Mostrar mensaje de éxito
              const currentSizeKB = Math.round(resizedFile.size / 1024);
              this.showSuccess(
                `Imagen redimensionada correctamente a ${currentSizeKB}KB.`
              );
            }
          },
          IMAGE_CONFIG.format,
          IMAGE_CONFIG.quality
        );
      };

      img.src = e.target.result;
    };

    reader.readAsDataURL(file);
  }

  /**
   * Redimensiona con menor calidad si aún excede el tamaño
   * @param {HTMLImageElement} img - Elemento de imagen
   * @param {number} width - Ancho deseado
   * @param {number} height - Alto deseado
   * @param {string} fileName - Nombre del archivo
   * @param {HTMLInputElement} inputElement - Elemento input file
   */
  resizeWithLowerQuality(img, width, height, fileName, inputElement) {
    // Crear canvas para redimensionar
    const canvas = document.createElement("canvas");
    canvas.width = width;
    canvas.height = height;

    // Dibujar imagen redimensionada
    const ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0, width, height);

    // Intentar con calidad más baja
    const lowerQuality = IMAGE_CONFIG.quality * 0.7;

    canvas.toBlob(
      (blob) => {
        // Crear un nuevo archivo a partir del blob
        const resizedFile = new File([blob], fileName, {
          type: "image/jpeg",
          lastModified: new Date().getTime(),
        });

        // Verificar si ahora cumple con el tamaño máximo
        if (resizedFile.size / 1024 > IMAGE_CONFIG.maxSizeKB) {
          const currentSizeKB = Math.round(resizedFile.size / 1024);
          this.showError(
            `No se pudo reducir la imagen a menos de ${IMAGE_CONFIG.maxSizeKB}KB (tamaño actual: ${currentSizeKB}KB).\n\nPor favor, seleccione otra imagen con menos resolución o comprimida previamente.`
          );
          inputElement.value = ""; // Limpiar el input
        } else {
          // Reemplazar el archivo en el input (esto es un hack, ya que no se puede modificar directamente)
          window[`resizedImage_${inputElement.id}`] = resizedFile;

          // Mostrar mensaje de éxito
          const currentSizeKB = Math.round(resizedFile.size / 1024);
          this.showSuccess(
            `Imagen redimensionada correctamente a ${currentSizeKB}KB.`
          );
        }
      },
      IMAGE_CONFIG.format,
      lowerQuality
    );
  }

  /**
   * Muestra un mensaje de error
   * @param {string} message - Mensaje de error
   */
  showError(message) {
    const errorElement = document.getElementById("contactFormError");
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.className = "alert alert-danger";
      errorElement.style.display = "block";

      // Ocultar después de 5 segundos
      setTimeout(() => {
        errorElement.style.display = "none";
      }, 5000);
    } else {
      alert(message);
    }
  }

  /**
   * Muestra un mensaje de éxito
   * @param {string} message - Mensaje de éxito
   */
  showSuccess(message) {
    const errorElement = document.getElementById("contactFormError");
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.className = "alert alert-success";
      errorElement.style.display = "block";

      // Ocultar después de 3 segundos
      setTimeout(() => {
        errorElement.style.display = "none";
      }, 3000);
    } else {
      alert(message);
    }
  }

  /**
   * Obtiene los archivos de imagen (originales o redimensionados)
   * @returns {Object} - Objeto con los archivos de imagen
   */
  getImageFiles() {
    const imageFiles = {};

    for (let i = 1; i <= 3; i++) {
      const inputId = `foto${i}`;
      const inputElement = document.getElementById(inputId);

      if (inputElement && inputElement.files.length > 0) {
        // Verificar si hay una versión redimensionada
        if (window[`resizedImage_${inputId}`]) {
          imageFiles[`foto${i}`] = window[`resizedImage_${inputId}`];
        } else {
          imageFiles[`foto${i}`] = inputElement.files[0];
        }
      }
    }

    return imageFiles;
  }
}

// Exportar la clase
window.ImageHandler = ImageHandler;
