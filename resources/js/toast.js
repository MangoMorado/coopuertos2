/**
 * Sistema de Notificaciones Toast
 * Maneja la cola de notificaciones y las muestra con auto-cierre
 */

// Store global para el contenedor de toasts
let toastContainerStore = null;

/**
 * Inicializa el store del contenedor de toasts
 */
export function initToastContainer(store) {
    toastContainerStore = store;
}

/**
 * Muestra una notificación toast
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Duración en milisegundos (0 para no cerrar automáticamente)
 * @param {boolean} autoClose - Si debe cerrarse automáticamente
 */
export function showToast(message, type = 'info', duration = 5000, autoClose = true) {
    if (!toastContainerStore) {
        console.warn('Toast container no está inicializado');
        return;
    }

    const toast = {
        id: Date.now() + Math.random(),
        message,
        type,
        duration,
        autoClose,
    };

    toastContainerStore.addToast(toast);

    // Auto-eliminar después de la duración
    if (autoClose && duration > 0) {
        setTimeout(() => {
            toastContainerStore.removeToast(toast.id);
        }, duration);
    }

    return toast.id;
}

/**
 * Cierra un toast específico por ID
 */
export function closeToast(toastId) {
    if (!toastContainerStore) {
        return;
    }
    toastContainerStore.removeToast(toastId);
}

/**
 * Cierra todos los toasts
 */
export function closeAllToasts() {
    if (!toastContainerStore) {
        return;
    }
    toastContainerStore.clearAll();
}

/**
 * Funciones de conveniencia
 */
export const toast = {
    success: (message, duration = 5000) => showToast(message, 'success', duration),
    error: (message, duration = 6000) => showToast(message, 'error', duration),
    warning: (message, duration = 5000) => showToast(message, 'warning', duration),
    info: (message, duration = 5000) => showToast(message, 'info', duration),
};

/**
 * Integra mensajes de sesión de Laravel
 * Debe llamarse después de que la página cargue
 */
export function initSessionMessages() {
    // Obtener mensajes de sesión del HTML (si existen)
    const successMessage = document.querySelector('[data-session-success]');
    const errorMessage = document.querySelector('[data-session-error]');
    const warningMessage = document.querySelector('[data-session-warning]');
    const infoMessage = document.querySelector('[data-session-info]');

    if (successMessage) {
        const message = successMessage.getAttribute('data-session-success');
        if (message) {
            toast.success(message);
            // Limpiar el mensaje del DOM
            successMessage.remove();
        }
    }

    if (errorMessage) {
        const message = errorMessage.getAttribute('data-session-error');
        if (message) {
            toast.error(message);
            errorMessage.remove();
        }
    }

    if (warningMessage) {
        const message = warningMessage.getAttribute('data-session-warning');
        if (message) {
            toast.warning(message);
            warningMessage.remove();
        }
    }

    if (infoMessage) {
        const message = infoMessage.getAttribute('data-session-info');
        if (message) {
            toast.info(message);
            infoMessage.remove();
        }
    }
}

// Hacer disponible globalmente
window.showToast = showToast;
window.toast = toast;
window.closeToast = closeToast;
window.closeAllToasts = closeAllToasts;
