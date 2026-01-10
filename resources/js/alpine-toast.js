/**
 * Alpine.js components para Toast Notifications
 */

/**
 * Componente para el contenedor de toasts
 */
export function toastContainer() {
    return {
        toasts: [],
        
        init() {
            // Inicializar el store global para que otros scripts puedan usar showToast
            if (window.initToastContainer) {
                window.initToastContainer(this);
            }
            
            // Inicializar mensajes de sesión después de un pequeño delay
            setTimeout(() => {
                if (window.initSessionMessages) {
                    window.initSessionMessages();
                }
            }, 100);
            
            // Escuchar eventos personalizados para mostrar toasts desde cualquier lugar
            window.addEventListener('show-toast', (e) => {
                const { message, type, duration, autoClose } = e.detail || {};
                this.addToast({
                    id: Date.now() + Math.random(),
                    message: message || '',
                    type: type || 'info',
                    duration: duration || 5000,
                    autoClose: autoClose !== false,
                });
            });
        },
        
        addToast(toast) {
            this.toasts.push(toast);
            
            // Limitar a 5 toasts máximo
            if (this.toasts.length > 5) {
                this.toasts.shift();
            }
            
            // Auto-eliminar después de la duración
            if (toast.autoClose && toast.duration > 0) {
                setTimeout(() => {
                    this.removeToast(toast.id);
                }, toast.duration);
            }
        },
        
        removeToast(toastId) {
            this.toasts = this.toasts.filter(t => t.id !== toastId);
        },
        
        clearAll() {
            this.toasts = [];
        }
    };
}

/**
 * Componente para una notificación toast individual
 */
export function toastNotification(config) {
    // Si config es un objeto toast completo, usarlo directamente
    const toastData = config.id ? config : config;
    
    return {
        id: toastData.id || Date.now(),
        message: toastData.message || '',
        type: toastData.type || 'info',
        duration: toastData.duration || 5000,
        autoClose: toastData.autoClose !== false,
        visible: true,
        progress: 100,
        progressInterval: null,
        containerStore: null,
        
        init() {
            // Buscar el contenedor padre para poder remover este toast
            let parent = this.$el.parentElement;
            while (parent && !parent.hasAttribute('x-data')) {
                parent = parent.parentElement;
            }
            if (parent && parent.__x && parent.__x.$data && parent.__x.$data.removeToast) {
                this.containerStore = parent.__x.$data;
            }
            
            if (this.autoClose && this.duration > 0) {
                this.startProgress();
            }
        },
        
        startProgress() {
            const startTime = Date.now();
            const updateProgress = () => {
                if (!this.visible) {
                    clearInterval(this.progressInterval);
                    return;
                }
                
                const elapsed = Date.now() - startTime;
                const remaining = Math.max(0, 100 - (elapsed / this.duration * 100));
                this.progress = remaining;
                
                if (remaining <= 0) {
                    this.close();
                }
            };
            
            // Actualizar cada 50ms para animación suave
            this.progressInterval = setInterval(updateProgress, 50);
        },
        
        close() {
            this.visible = false;
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
            }
            
            // Remover del contenedor si está disponible
            if (this.containerStore && this.containerStore.removeToast) {
                this.containerStore.removeToast(this.id);
            } else {
                // Fallback: remover del DOM después de la animación
                setTimeout(() => {
                    if (this.$el && this.$el.parentElement) {
                        this.$el.remove();
                    }
                }, 200);
            }
        }
    };
}
